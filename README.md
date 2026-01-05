# MOLA KAFE — Kısa Tanım ve Hızlı Kullanım

Bu proje küçük bir XAMPP uyumlu PHP POS (adisyon) örneğidir. Üç ana rol/alan vardır: müşteri, operatör ve kasa. Amaç hızlıca masa siparişleri almak, operatör onayıyla masalara düşürmek ve kasadan ödemeyi tamamlamaktır.

Nasıl çalışır (kısaca)
- Müşteri paneli (`customer.php`): Menüden ürün seçip sepete ekler, sipariş oluşturur. Yeni siparişler `pending` statüsüyle kaydedilir.
- Operatör paneli (`operator.php`): `pending` siparişleri onaylar (Onayla → status = `open`), masa kartlarında ürünler ve toplam görünür.
- Kasa paneli (`kitchen.php`): Masa bazlı toplamları gösterir; "Ödeme Yap" ile seçili masaya ait siparişler veritabanından silinir.

Kullanılan teknolojiler
- PHP (PDO)
- MySQL / MariaDB
- Bootstrap 5 (frontend)
- Vanilla JavaScript (fetch polling)

Hızlı başlatma (XAMPP)
1) Proje klasörünü `C:\xampp\htdocs\proje` içine kopyalayın.
2) Apache ve MySQL servislerini XAMPP Kontrol Paneli'nden başlatın.
3) Veritabanını import edin (`init.sql`) veya `create_db.php` çalıştırın.
4) Tarayıcıda: `http://localhost/proje/`

Demo kullanıcılar (hızlı test için)
- Operatör: `Akkaya` / `4578` (role: operator)
- Kasa: `Hilmi` / `1111` (role: kitchen — kasa için kullanın)
- Kasa: `Tunahan` / `2222` (role: kitchen — kasa için kullanın)

Hızlı test
- Müşteri panelinde bir sipariş oluşturun. Operatör panelinde "Yeni Siparişler" bölümünde onaylayın. Kasa panelinde toplamları görüp "Ödeme Yap" butonunu kullanın.

Notlar
- Eğer örnek kullanıcıları eklemek isterseniz phpMyAdmin veya bir SQL sorgusuyla `users` tablosuna ekleyin 
