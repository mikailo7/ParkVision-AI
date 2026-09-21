# Treniranje YOLO modela

Koristi se transfer learning: početni `yolo11n.pt` se dodatno trenira za klasu `license_plate`.

Skup podataka mora imati foldere `images/train`, `images/val`, `images/test` i odgovarajuće `labels` foldere. Svaka slika ima istoimeni `.txt` fajl sa YOLO oznakama `class_id x_center y_center width height`; koordinate su normalizovane od 0 do 1, a jedina klasa ima ID 0.

1. Kopirati `dataset/data.yaml.example` kao `dataset/data.yaml` i prilagoditi putanju.
2. Aktivirati Python okruženje i pokrenuti `python train.py`.
3. Kopirati dobijeni `runs/license_plate_yolo11n/weights/best.pt` u `models/best.pt`.
4. Pokrenuti `python app.py`.


