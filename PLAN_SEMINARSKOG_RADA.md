# Naziv rada

**Razvoj web aplikacije za inteligentnu kontrolu pristupa parkingu primenom YOLO modela i automatskog prepoznavanja registarskih tablica**

# Sadržaj usklađen sa praktičnim projektom

## 1. UVOD — 3 strane

1.1. Predmet i cilj rada  
1.2. Motivacija za primenu veštačke inteligencije u internet aplikacijama  
1.3. Metodologija rada i struktura rada

Cilj: web sistem koji iz fotografije detektuje tablicu, pročita oznaku, proveri bazu dozvoljenih vozila i prikaže odluku.

## 2. VEŠTAČKA INTELIGENCIJA — 7 strana

2.1. Pojam i razvoj AI  
2.2. Mašinsko učenje  
2.3. Duboko učenje  
2.4. Neuronske mreže  
2.5. Savremeni trendovi i primene AI

## 3. RAČUNARSKI VID — 8 strana

3.1. Osnovni koncepti  
3.2. Digitalna slika i reprezentacija  
3.3. Predobrada slika  
3.4. Detekcija i klasifikacija objekata  
3.5. Konvolucione neuronske mreže  
3.6. Savremeni modeli

Objasniti bounding box, confidence, IoU i NMS, kao i razliku klasifikacije i detekcije.

## 4. RAČUNARSKI VID U WEB APLIKACIJAMA — 7 strana

4.1. Arhitektura AI sistema  
4.2. Klijent-server model  
4.3. Frontend tehnologije  
4.4. Backend tehnologije  
4.5. REST API komunikacija  
4.6. Bezbednost

Opisati komunikaciju browser → PHP → Flask AI servis → PHP → browser.

## 5. ALATI I TEHNOLOGIJE — 5 strana

5.1. Python  
5.2. OpenCV  
5.3. TensorFlow/Keras i PyTorch — uporedni pregled  
5.4. YOLO i Ultralytics  
5.5. HTML, CSS, JavaScript, PHP i SQLite

TensorFlow/Keras ostaje teorijsko poređenje. Praktična implementacija koristi PyTorch/Ultralytics. Ako profesor dozvoli, 5.3 preimenovati u „PyTorch i Ultralytics“.

## 6. PRAKTIČNA IMPLEMENTACIJA — 20 strana

6.1. Zahtevi — registracija, vozila, paketi, upload, detekcija, OCR, odluka i istorija.  
6.2. Arhitektura — komponente, tok podataka, model baze i HTTP komunikacija.  
6.3. Backend — PHP sesije, PDO, validacija slike, cURL, provera dozvole i događaji.  
6.4. AI — dataset, anotacije, train/val/test, transfer learning YOLO11n, `best.pt`, ROI, OpenCV i EasyOCR.  
6.5. Interfejs — FormData/fetch, pregled slike, tablica, pouzdanosti i odluka.  
6.6. Integracija — format zahteva/JSON odgovora, sekvencijalni dijagram i greške.  
6.7. Testiranje — poznata/nepoznata tablica, mutna slika, ugao, slika bez tablice i ugašen servis.

## 7. REZULTATI I DISKUSIJA — 3 strane

7.1. Precision, recall, mAP50, mAP50-95 i OCR tačnost  
7.2. Vreme YOLO detekcije, OCR-a i ukupnog odgovora  
7.3. Prednosti i ograničenja: osvetljenje, ugao, zamućenje i CPU brzina

## 8. ZAKLJUČAK — 2 strane

Rezime sistema i budući razvoj: video uživo, produkciona baza, pravo plaćanje i javno postavljanje.

## LITERATURA I PRILOZI

Navesti naučne radove, zvaničnu dokumentaciju i poreklo/licencu dataseta. Priložiti ključni kod, arhitekturni/sekvencijalni/ER dijagram, rezultate odobrenog i odbijenog pristupa, grafike treninga i matricu konfuzije.
