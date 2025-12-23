# Vitrin - Modern Uygulama Marketi

Vitrin, kendi geliştirdiğiniz veya favori uygulamalarınızı sergileyebileceğiniz, veritabanı gerektirmeyen, hafif ve modern bir web uygulamasıdır. PHP ve JSON tabanlı yapısı sayesinde kurulumu son derece basittir.

![Vitrin Önizleme](https://via.placeholder.com/800x400?text=Vitrin+App+Store)

## 🌟 Özellikler

*   **Veritabanısız Yapı:** Tüm veriler `data.json` dosyasında tutulur. MySQL vs. kurulumu gerektirmez.
*   **Modern Tasarım:** Tailwind CSS ile hazırlanmış şık, duyarlı (responsive) ve "Glassmorphism" etkili arayüz.
*   **Karanlık Mod (Dark Mode):** Kullanıcı tercihine veya sistem ayarına göre otomatik değişen tema.
*   **Yönetim Paneli:**
    *   Uygulama Ekleme / Düzenleme / Silme.
    *   **Otomatik İkon:** Uygulama URL'ini girdiğinizde favicon otomatik çekilir.
    *   Basit şifreli giriş koruması.
*   **Etkileşim:**
    *   **Puanlama Sistemi:** Ziyaretçiler uygulamaları 1-5 yıldız ile oylayabilir.
    *   **Kategorileme:** Uygulamalar kategoriye göre otomatik filtrelenebilir.

## 🛠️ Teknolojiler

*   **Frontend:** HTML5, Tailwind CSS (CDN), Alpine.js
*   **Backend:** PHP (Basit dosya yönetimi için)
*   **Veri:** JSON

## 🚀 Kurulum

Projeyi çalıştırmak için PHP desteği olan herhangi bir sunucu yeterlidir.

1.  Dosyaları sunucunuza yükleyin (`index.html`, `admin.html`, `save.php`, `data.json`, `js/app.js`).
2.  `data.json` dosyasının **yazma izinlerini** (CHMOD 777 veya 755) ayarlayın.
3.  Tarayıcıdan sitenize girin.

### Yönetici Girişi
*   Adres: `siteniz.com/admin.html`
*   Varsayılan Şifre: **admin123**
    *   *Şifreyi değiştirmek için `save.php` dosyasındaki `$adminPassword` değişkenini düzenleyin.*

## 💻 Yerel Geliştirme (Localhost)

PHP kurulu değilse, geliştirdiğimiz Node.js sunucusunu kullanabilirsiniz:

1.  Terminali açın:
    ```bash
    node server.js
    ```
2.  Tarayıcıda `http://localhost:8000` adresine gidin.

## 📂 Dosya Yapısı

*   `Index.html`: Ziyaretçilerin gördüğü ana vitrin sayfası.
*   `admin.html`: Uygulama yönetim paneli.
*   `save.php`: Verileri `data.json` dosyasına kaydeden backend scripti.
*   `data.json`: Uygulama verilerinin tutulduğu dosya.
*   `js/app.js`: Frontend mantığı (Alpine.js store).
*   `server.js`: Yerel geliştirme için PHP simülasyonu (Node.js).

## 📝 Lisans

Bu proje açık kaynaktır. İstediğiniz gibi kullanabilir ve değiştirebilirsiniz.
