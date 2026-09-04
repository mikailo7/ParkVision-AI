# ParkVision AI — YOLO kontrola pristupa parkingu

PHP/JavaScript web aplikacija, SQLite baza i Python AI servis. ESP32 i fizička rampa nisu deo ove verzije.

## Tok sistema

Korisnik registruje vozilo i aktivira paket. Fotografija ide browser → PHP → Flask. YOLO detektuje tablicu, EasyOCR čita oznaku, a PHP proverava bazu i vraća `PRISTUP ODOBREN` ili `PRISTUP ODBIJEN`, zajedno sa slikom detekcije i pouzdanostima.

## Web aplikacija

Kopirati folder u `C:\xampp\htdocs\smart-parking`, uključiti Apache i otvoriti `http://localhost/smart-parking/`. U `php.ini` moraju biti uključeni `pdo_sqlite`, `sqlite3`, `curl` i `fileinfo`.

Demo korisnik: `miki@parking.local` / `miki123`  
Administrator: `admin@parking.local` / `admin123`

## AI servis

Preporučen je Python 3.11.

```powershell
cd C:\xampp\htdocs\smart-parking\ai-service
py -3.11 -m venv venv
venv\Scripts\activate
python -m pip install --upgrade pip
pip install -r requirements.txt
python app.py
```

Istrenirani model treba da bude `ai-service\models\best.pt`. Uputstvo za treniranje je u `ai-service/TRAINING.md`. Provera servisa: `http://127.0.0.1:5001/health`; `yolo_model_loaded` mora biti `true`.

Trening se pokreće sa `python train.py` nakon pripreme dataseta u YOLO formatu. Koristi se transfer learning od `yolo11n.pt`, 40 epoha i klasa `license_plate`.

Rezervni ručni unos je samo fallback za demonstraciju. Za evaluaciju se koristi automatska YOLO + OCR putanja.

Važne datoteke: `scan.php`, `api/scan.php`, `ai-service/app.py`, `ai-service/train.py`, `database/schema.sql` i `PLAN_SEMINARSKOG_RADA.md`.
