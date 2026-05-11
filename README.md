# Metin2 Maintenance & Hotfix API

Bu proje, Gameforge Metin2 forumundaki bakım (Wartung), hotfix ve yeniden başlatma (Neustart) duyurularını otomatik olarak okuyup, Almanya saatinden (Europe/Berlin) Türkiye saatine (Europe/Istanbul) çevirerek bir REST API olarak sunan Flask tabanlı bir servistir.

API yalnızca en son forum mesajını analiz eder ve gerçek bakım zamanını JSON formatında verir.

---

## Özellikler

- Gameforge Metin2 bakım konusunun son mesajını okur  
- Bakım, hotfix, restart gibi anahtar kelimeleri algılar  
- Cümle içinden tarih ve saat bilgisini çıkarır  
- Almanya saatini Türkiye saatine dönüştürür  
- Sonucu JSON REST API olarak verir  

---

## API Endpoint

GET /maintenance

Örnek cevap:

{
  "maintenanceFound": true,
  "berlinTime": "2026-01-28 09:00",
  "turkeyTime": "2026-01-28 11:00",
  "sentence": "Liebe Community, die nächste wöchentliche Wartung & Hotfix findet am Mittwoch, den 28.01., um 09:00 Uhr statt.",
  "checkedAt": "2026-01-27 14:32:10"
}

---

## Gereksinimler

Python 3.8 veya üzeri gereklidir.

Bağımlılıkları yüklemek için:

pip install flask==2.2.5 werkzeug==2.2.3 requests beautifulsoup4 pytz

---

## Çalıştırma

Uygulamayı başlatmak için:

python flaskapi.py

Sunucu çalıştıktan sonra API şu adreste erişilebilir olur:

http://127.0.0.1:5000/maintenance



--

## Live Demo

[URL’ye tıkla](https://gokhanaltun.com/demo/metin2mainistance/)

---

## Çalışma Mantığı

1. API Gameforge forum sayfasını indirir  
2. Sayfadaki tüm mesajları okur  
3. En son mesajı seçer  
4. Bakım ile ilgili anahtar kelimeleri içeren satırı bulur  
5. Tarih ve saat bilgisini ayıklar  
6. Zamanı Berlin’den İstanbul’a çevirir  
7. Sonucu JSON olarak döner  

---

## Zaman Dilimi

Forumda verilen saatler Europe/Berlin olarak kabul edilir.  
API, bu saatleri Europe/Istanbul zaman dilimine çevirir.

Örnek:

09:00 Berlin → 11:00 İstanbul

---

## Dosya Yapısı


flaskapi.py  
requirements.txt  
README.md  

---

## Notlar

- API her çağrıda forumu canlı olarak okur  
- Sadece son post analiz edilir  
- Eğer bakım bulunamazsa şu çıktı döner:

{ "maintenanceFound": false }

---

## Lisans

Bu proje otomasyon ve entegrasyon amaçlı serbest kullanım için sağlanmıştır.
