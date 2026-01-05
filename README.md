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
- Ana sayfa: `http://localhost/proje/` → ilgili panele giriş yapın.
- Müşteri panelinde bir sipariş oluşturun. Operatör panelinde "Yeni Siparişler" bölümünde onaylayın. Kasa panelinde toplamları görüp "Ödeme Yap" butonunu kullanın.

Notlar
- Eğer örnek kullanıcıları eklemek isterseniz phpMyAdmin veya bir SQL sorgusuyla `users` tablosuna ekleyin (README içinde hash oluşturma örneği bulunur).

Bu kadar — daha fazlasını isterseniz `dev_insert_orders.php` ekleyip örnek siparişler oluşturayım veya başka bir iyileştirme yapayım.

Hızlı Test (Login ve paneller)
------------------------------
1. Tarayıcınızda ana sayfayı açın:

   http://localhost/proje/index.php

2. Operatör paneline erişmeyi deneyin. Eğer oturum açılmamışsanız `operator.php` sizi `login.php` sayfasına yönlendirecektir.

   http://localhost/proje/login.php


 3. Kullanıcı yönetimi ve roller

- `init.sql` içinde örnek veriler bulunmaktadır ancak proje güncellenmiştir: örnek `products` INSERT blokları kaldırıldı (yorum halinde bırakıldı). Ürünleri sisteme eklemek için ya `init.sql`'deki örnekleri kullanabilir ya da `create_users.php` ve ileride ekleyeceğiniz yönetici araçlarıyla ekleyebilirsiniz.

- `login.php` artık geliştirme için placeholder parola bypass'ı içermez; parola doğrulaması gerçek bcrypt hash ile yapılır.

- Role-based giriş: `login.php?redirect=operator.php` ile gelen giriş denemeleri yalnızca `role = 'operator'` olan hesaplara izin verir. Benzer şekilde `?redirect=kitchen.php` ile gelenler yalnızca `role = 'kitchen'` hesaplarına izin verilir. Eğer redirect belirtilmezse, giriş yapan kullanıcının rolüne göre (`kitchen` → `kitchen.php`, diğerleri → `operator.php`) yönlendirme yapılır.

   - Güvenlik notu: Bu sadece geliştirme/test amaçlıdır. Gerçek bir kurulumda lütfen aşağıdaki adımla gerçek bir hash giriniz.

 Demo kullanıcı bilgileri (hızlı erişim)
 -----------------------------------
-- Operatör: `Akkaya` / `4578` (role: operator)
-- Kasa: `Hilmi` / `1111` (role: kitchen — Kasa hesabı olarak kullanılıyor)
-- Kasa: `Tunahan` / `2222` (role: kitchen — Kasa hesabı olarak kullanılıyor)

 Not: `create_users.php` script'i artık non-destructive çalışır:

- Eğer kullanıcı veritabanında yoksa, script yeni kullanıcıyı (username, password, role) INSERT eder.
- Eğer kullanıcı zaten varsa, script mevcut kullanıcının `password_hash` alanını değiştirmez; yalnızca `role` değerini günceller. Yani var olan şifreler korunur.

Kullanıcı ekleme (not)

- Projeyi basitçe test etmek için ana sayfayı (`http://localhost/proje/`) kullanın; bazı kurulumlarda `create_users.php` çalışmayabilir.
- Eğer script çalışmıyorsa, phpMyAdmin veya doğrudan SQL ile `users` tablosuna kullanıcı ekleyebilirsiniz. Örnek SQL:

```sql
INSERT INTO users (username,password_hash,role) VALUES ('Akkaya','<BURAYA_HASH>','operator');
```

Not: `<BURAYA_HASH>` kısmına güvenli bir `password_hash()` çıktısı yerleştirin (README içinde hash üretme örneği mevcut).

Gerçek admin parolası oluşturma (güvenli)
---------------------------------------
PHP CLI veya kısa bir PHP dosyası ile güvenli bcrypt hash oluşturup `users` tablosuna koyabilirsiniz. PHP CLI örneği:

```php
<?php
echo password_hash('sizinSifre', PASSWORD_DEFAULT) . PHP_EOL;
```

Çıktıyı kopyalayın ve phpMyAdmin'de `users` tablosunda ilgili kullanıcının `password_hash` alanını bu değerle güncelleyin.

Veya SQL ile yeni kullanıcı eklemek için (hash'ı ürettikten sonra):

```sql
INSERT INTO users (username, password_hash, role) VALUES ('operator1', '<BURAYA_HASH>', 'staff');
```

 Müşteri Panelini Deneme
 -----------------------
 1. `customer.php` sayfası ürünleri `products` tablosundan listeler. `init.sql` içindeki örnek ürün INSERT blokları projede kaldırıldı — bu yüzden yeni kurulumlarda ürünlerinizi ya `init.sql`'e elle eklemeli ya da bir admin aracı ile eklemelisiniz.
 2. Ürünlerin "Ekle" butonuna tıklayarak session tabanlı sepete ekleyebilirsiniz.
 3. Sepeti temizlemek için "Sepeti Temizle" butonunu kullanın.

 Sipariş Oluşturma
 -----------------
- Projede `create_order.php` ve ilgili API uç noktaları mevcuttur; müşteri sepetinden sipariş oluşturma ve `orders` ile `order_items` tablolarına yazma mantığı uygulandı. Eğer sizin kurulumunuzda bu akış çalışmıyorsa, test etmek için müşteri panelinden sepet oluşturup checkout akışını deneyin veya bana bildirip ben kontrol edeyim.

 Geliştirme için Önerilen Sonraki Adımlar
--------------------------------------
1. Checkout akışı: Müşteri sepetini siparişe dönüştürecek backend endpoint.
2. Operatör tarafında sipariş durumları: open -> preparing -> served -> paid gibi iş akışları.
3. Kullanıcı yönetimi: parola sıfırlama, yeni kullanıcı kayıt, roller (casher/manager).
4. Yazıcı/fiş desteği: basit yazdırma görünümü veya termal yazıcı entegrasyonu.
 5. Güvenlik: placeholder giriş kaldırıldı. HTTPS, CSRF koruması ve güçlü parola politikaları uygulanması önerilir.

Yardım/İstekler
---------------
Hangi özelliği öne alayım?
- Checkout (sepette -> sipariş kaydet) ekleyeyim mi?
- Operatör için sipariş detay ve durum değiştirme ekleyeyim mi?
- Hemen admin şifresini oluşturacak küçük bir UI/CLI yardımcı ekleyeyim mi?

İlgili dosyalar kısa açıklama
----------------------------
- `index.php` - Ana sayfa, iki panele link.
- `customer.php` - Menü ve sepet işlemleri (session).
- `operator.php` - Operatör paneli, giriş zorunlu.
- `login.php` / `logout.php` - Giriş ve çıkış.
- `db.php` - PDO bağlantı.
- `init.sql` - DB tabloları ve örnek veri.

İletişim
--------
Bu repo üzerinde isterseniz ben devam edebilirim: hangi adımı önce istediğinizi söyleyin; ben kodu ekleyip testlerini çalıştırırım.
