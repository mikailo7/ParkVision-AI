from pathlib import Path
from ultralytics import YOLO

root = Path(__file__).parent
data = root / "dataset" / "data.yaml"
if not data.exists():
    raise SystemExit("Nedostaje ai-service/dataset/data.yaml. Pogledajte TRAINING.md.")

model = YOLO("yolo11n.pt")
model.train(data=str(data), epochs=40, imgsz=640, batch=16, patience=10,
            project=str(root / "runs"), name="license_plate_yolo11n")
print("Kopirajte runs/license_plate_yolo11n/weights/best.pt u models/best.pt")
