import base64
import os
import re
import time
import uuid
from pathlib import Path
import cv2
import easyocr
import numpy as np
from flask import Flask, jsonify, request, send_from_directory
from flask_cors import CORS
from ultralytics import YOLO

app = Flask(__name__)
CORS(app)
app.config["MAX_CONTENT_LENGTH"] = 500 * 1024 * 1024
model_path = Path(os.getenv("YOLO_MODEL", Path(__file__).parent / "models" / "best.pt"))
model = YOLO(str(model_path)) if model_path.exists() else None
vehicle_model_path = os.getenv("VEHICLE_MODEL", "yolo11n.pt")
vehicle_model = None
project_root = Path(__file__).resolve().parent.parent
camera_video = project_root / "assets" / "videos" / "nadzorna-kamera.mp4"
traffic_results = Path(__file__).resolve().parent / "traffic-results"
traffic_results.mkdir(exist_ok=True)
reader = easyocr.Reader(["en"], gpu=False)

def normalize(text: str) -> str:
    return re.sub(r"[^A-Z0-9]", "", text.upper())

SERBIAN_AREA_CODES = (
    "BG", "NS", "NI", "KG", "KV", "CA", "KS", "PA", "SU", "SO", "SM",
    "SA", "VA", "UE", "NP", "PI", "ZA", "ZR", "LE", "LO", "LU", "JA",
    "PO", "PR", "VR", "KO", "RU", "SD", "SP", "ST", "TO", "VB", "VP"
)

def correct_serbian_format(plate: str) -> str:
    match = re.fullmatch(r"([A-Z]{2,3})(\d{3,4})([A-Z]{2})", plate)
    if not match:
        return plate
    prefix, digits, suffix = match.groups()
    # Font na korišćenim srpskim tablicama ima precrtanu nulu koju EasyOCR
    # često klasifikuje kao 8 (npr. KS855HI umesto KS055HI).
    digits = digits.replace("8", "0")
    # Ako je OCR dodao slovo oko grba, najpre traži tačan dvoslovni kod.
    exact = [part for part in (prefix[:2], prefix[-2:]) if part in SERBIAN_AREA_CODES]
    if exact:
        return exact[0] + digits + suffix
    if len(prefix) == 2 and prefix not in SERBIAN_AREA_CODES:
        # Ispravlja jednu pogrešno pročitanu oznaku, npr. GG -> BG.
        distances = [(sum(a != b for a, b in zip(prefix, code)), code) for code in SERBIAN_AREA_CODES]
        distance, code = min(distances)
        if distance == 1:
            return code + digits + suffix
    return plate

def read_plate(crop: np.ndarray) -> tuple[str | None, float]:
    gray = cv2.cvtColor(crop, cv2.COLOR_BGR2GRAY)
    enlarged = cv2.resize(gray, None, fx=3, fy=3, interpolation=cv2.INTER_CUBIC)
    clahe = cv2.createCLAHE(clipLimit=2.0, tileGridSize=(8, 8)).apply(enlarged)
    threshold = cv2.threshold(clahe, 0, 255, cv2.THRESH_BINARY + cv2.THRESH_OTSU)[1]
    variants = [crop, enlarged, clahe, threshold]
    candidates = []

    for variant in variants:
        results = reader.readtext(
            variant,
            detail=1,
            paragraph=False,
            allowlist="ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
        )
        if not results:
            continue

        # EasyOCR često pročita tablicu kao tri dela: KS, 055 i HI.
        ordered = sorted(results, key=lambda item: min(point[0] for point in item[0]))
        heights = [max(point[1] for point in item[0]) - min(point[1] for point in item[0]) for item in ordered]
        max_height = max(heights)
        # Odbacuje sitan tekst: SRB oznaku, slova ispod grba i natpis na okviru tablice.
        main_line = [item for item, height in zip(ordered, heights) if height >= max_height * 0.55]
        joined = normalize("".join(item[1] for item in main_line))
        mean_confidence = sum(float(item[2]) for item in main_line) / len(main_line)

        texts = [(normalize(item[1]), float(item[2])) for item in ordered]
        texts.append((joined, mean_confidence))
        # Ako oko glavne oznake ostane višak teksta, izvlači standardni obrazac tablice.
        for match in re.findall(r"[A-Z]{2}\d{3,4}[A-Z]{2}", joined):
            texts.append((match, min(1.0, mean_confidence + 0.20)))
        for plate, confidence in texts:
            if 5 <= len(plate) <= 10 and any(c.isdigit() for c in plate) and any(c.isalpha() for c in plate):
                candidates.append((correct_serbian_format(plate), confidence))

    return max(candidates, key=lambda item: item[1]) if candidates else (None, 0.0)

def plate_from_vehicle(vehicle_crop: np.ndarray) -> tuple[str | None, float]:
    if model is None or vehicle_crop.size == 0:
        return None, 0.0
    result = model.predict(vehicle_crop, conf=0.22, verbose=False)[0]
    if len(result.boxes) == 0:
        return None, 0.0
    box = max(result.boxes, key=lambda item: float(item.conf[0]))
    x1, y1, x2, y2 = map(int, box.xyxy[0].tolist())
    h, w = vehicle_crop.shape[:2]
    x1, y1, x2, y2 = max(0, x1), max(0, y1), min(w, x2), min(h, y2)
    return read_plate(vehicle_crop[y1:y2, x1:x2])

def crossed(previous: float, current: float, line: float) -> bool:
    return previous < line <= current

def vehicle_label(class_id: int) -> str:
    return {2: "Automobil", 3: "Motocikl", 5: "Autobus", 7: "Kamion"}.get(class_id, "Vozilo")

@app.post("/traffic/analyze")
def analyze_traffic():
    global vehicle_model
    if not camera_video.exists():
        return jsonify(error="Nedostaje assets/videos/nadzorna-kamera.mp4"), 404
    if model is None:
        return jsonify(error="Nedostaje ai-service/models/best.pt"), 503
    if vehicle_model is None:
        vehicle_model = YOLO(vehicle_model_path)

    payload = request.get_json(silent=True) or {}
    distance_m = max(1.0, float(payload.get("distance_m", 10)))
    speed_limit = max(5, int(payload.get("speed_limit", 40)))
    line_a_ratio = min(0.80, max(0.15, float(payload.get("line_a", 0.42))))
    line_b_ratio = min(0.92, max(line_a_ratio + 0.08, float(payload.get("line_b", 0.68))))
    capture = cv2.VideoCapture(str(camera_video))
    if not capture.isOpened():
        return jsonify(error="Video nije moguće otvoriti"), 422

    fps = capture.get(cv2.CAP_PROP_FPS) or 25.0
    width = int(capture.get(cv2.CAP_PROP_FRAME_WIDTH))
    height = int(capture.get(cv2.CAP_PROP_FRAME_HEIGHT))
    total_frames = int(capture.get(cv2.CAP_PROP_FRAME_COUNT))
    line_a, line_b = int(height * line_a_ratio), int(height * line_b_ratio)
    result_name = f"traffic-{uuid.uuid4().hex[:10]}.mp4"
    writer = cv2.VideoWriter(str(traffic_results / result_name), cv2.VideoWriter_fourcc(*"mp4v"), fps, (width, height))
    tracks, events, frame_no = {}, [], 0
    started = time.time()

    while True:
        ok, frame = capture.read()
        if not ok:
            break
        frame_no += 1
        result = vehicle_model.track(frame, persist=True, classes=[2, 3, 5, 7], conf=0.25,
                                     tracker="bytetrack.yaml", verbose=False)[0]
        cv2.line(frame, (0, line_a), (width, line_a), (255, 255, 255), 2)
        cv2.line(frame, (0, line_b), (width, line_b), (180, 180, 180), 2)
        cv2.putText(frame, f"A  distance={distance_m:g}m", (12, max(25, line_a-8)), cv2.FONT_HERSHEY_SIMPLEX, .65, (255,255,255), 2)
        cv2.putText(frame, "B", (12, max(25, line_b-8)), cv2.FONT_HERSHEY_SIMPLEX, .65, (220,220,220), 2)
        if result.boxes.id is not None:
            ids = result.boxes.id.int().cpu().tolist()
            boxes = result.boxes.xyxy.int().cpu().tolist()
            classes = result.boxes.cls.int().cpu().tolist()
            for track_id, (x1, y1, x2, y2), class_id in zip(ids, boxes, classes):
                center_y = (y1+y2)/2
                state = tracks.setdefault(track_id, {"previous_y":center_y,"a_frame":None,"measured":False,"speed":None,"plate":None})
                if state["a_frame"] is None and crossed(state["previous_y"], center_y, line_a):
                    state["a_frame"] = frame_no
                if state["a_frame"] is not None and not state["measured"] and crossed(state["previous_y"], center_y, line_b):
                    elapsed = (frame_no-state["a_frame"])/fps
                    if elapsed > 0:
                        speed = distance_m/elapsed*3.6
                        crop = frame[max(0,y1):min(height,y2),max(0,x1):min(width,x2)]
                        plate, ocr_conf = plate_from_vehicle(crop)
                        state.update(measured=True,speed=speed,plate=plate)
                        events.append({"plate":plate or "NEOCITANA","vehicle_type":vehicle_label(class_id),
                            "speed_kmh":round(speed,1),"speed_limit":speed_limit,
                            "status":"PREKORACENJE" if speed>speed_limit else "U REDU",
                            "video_time":round(frame_no/fps,2),"ocr_confidence":round(ocr_conf,3)})
                state["previous_y"] = center_y
                color = (40,40,230) if state["speed"] and state["speed"]>speed_limit else (70,220,100)
                cv2.rectangle(frame,(x1,y1),(x2,y2),color,2)
                label=f"ID {track_id}"
                if state["plate"]: label += f"  {state['plate']}"
                if state["speed"] is not None: label += f"  {state['speed']:.1f} km/h"
                cv2.putText(frame,label,(x1,max(25,y1-8)),cv2.FONT_HERSHEY_SIMPLEX,.6,color,2)
        writer.write(frame)

    capture.release(); writer.release()
    return jsonify(ok=True,events=events,
        result_url=request.host_url.rstrip("/")+"/traffic/results/"+result_name,
        fps=round(fps,2),frames=total_frames,processing_seconds=round(time.time()-started,1),
        distance_m=distance_m,speed_limit=speed_limit)

@app.get("/traffic/results/<path:filename>")
def traffic_result(filename):
    return send_from_directory(traffic_results, filename)

@app.get("/traffic/status")
def traffic_status():
    return jsonify(ok=True,video_exists=camera_video.exists(),video=str(camera_video),
                   plate_model_loaded=model is not None,vehicle_model=vehicle_model_path)

@app.post("/recognize")
def recognize():
    if model is None:
        return jsonify(error="YOLO model nije pronađen. Stavite best.pt u ai-service/models/"), 503
    uploaded = request.files.get("image")
    if not uploaded:
        return jsonify(error="Slika nije poslata"), 400
    image = cv2.imdecode(np.frombuffer(uploaded.read(), np.uint8), cv2.IMREAD_COLOR)
    if image is None:
        return jsonify(error="Slika nije ispravna"), 422
    prediction = model.predict(image, conf=0.25, verbose=False)[0]
    if len(prediction.boxes) == 0:
        return jsonify(plate=None, error="YOLO nije pronašao registarsku tablicu"), 422
    best_box = max(prediction.boxes, key=lambda box: float(box.conf[0]))
    x1, y1, x2, y2 = map(int, best_box.xyxy[0].tolist())
    h, w = image.shape[:2]
    x1, y1, x2, y2 = max(0, x1), max(0, y1), min(w, x2), min(h, y2)
    plate, ocr_confidence = read_plate(image[y1:y2, x1:x2])
    annotated = image.copy()
    color = (65, 220, 120) if plate else (60, 80, 240)
    cv2.rectangle(annotated, (x1, y1), (x2, y2), color, 3)
    cv2.putText(annotated, plate or "PLATE", (x1, max(25, y1 - 10)), cv2.FONT_HERSHEY_SIMPLEX, 0.8, color, 2)
    ok, encoded = cv2.imencode(".jpg", annotated, [cv2.IMWRITE_JPEG_QUALITY, 82])
    data_url = "data:image/jpeg;base64," + base64.b64encode(encoded).decode() if ok else None
    return jsonify(plate=plate, yolo_confidence=float(best_box.conf[0]), ocr_confidence=ocr_confidence,
                   bbox=[x1, y1, x2, y2], annotated_image=data_url)

@app.get("/health")
def health():
    return jsonify(ok=True, yolo_model_loaded=model is not None, model_path=str(model_path))

if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5001, debug=False)
