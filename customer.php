<?php
session_start();
require_once __DIR__ . '/db.php';

$pdo = getPDO();

// Basit sepet işlemleri (session tabanlı)
if (isset($_GET['action']) && $_GET['action'] === 'add' && isset($_GET['id'])) {
  $id = (int)$_GET['id'];
  if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
  if (!isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id] = 0;
  $_SESSION['cart'][$id] += 1;
  header('Location: customer.php');
  exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'clear') {
  unset($_SESSION['cart']);
  unset($_SESSION['cart_samples']);
  header('Location: customer.php');
  exit;
}

// Örnek ürünler için session tabanlı ekleme (DB'ye yazmadan demo için)
if (isset($_GET['action']) && $_GET['action'] === 'add_sample' && isset($_GET['name']) && isset($_GET['price'])) {
  $name = trim((string)$_GET['name']);
  $price = (float)$_GET['price'];
  if ($name !== '' && $price > 0) {
    if (!isset($_SESSION['cart_samples'])) $_SESSION['cart_samples'] = [];
    // Eğer aynı isimde sample varsa miktarı artır
    $found = false;
    foreach ($_SESSION['cart_samples'] as &$s) {
      if ($s['name'] === $name && abs($s['price'] - $price) < 0.001) {
        $s['qty'] += 1;
        $found = true;
        break;
      }
    }
    unset($s);
    if (!$found) {
      $_SESSION['cart_samples'][] = ['name' => $name, 'price' => $price, 'qty' => 1];
    }
  }
  header('Location: customer.php');
  exit;
}

// Masa QR ile seçildiğinde (örnek: customer.php?table=3) -> oturulan masayı session'a kaydet
if (isset($_GET['table'])) {
  $tableId = (int)$_GET['table'];
  if ($tableId > 0) {
    $_SESSION['table'] = $tableId;
  }
  // temiz redirect URL için yönlendir
  header('Location: customer.php');
  exit;
}

// Masa seçimini iptal etme / değiştirme
if (isset($_GET['unset_table'])) {
  unset($_SESSION['table']);
  header('Location: customer.php');
  exit;
}

$products = $pdo->query('SELECT p.id,p.name,p.price,c.name AS category FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY c.name, p.name')->fetchAll();

function cartItemsSummary($pdo)
{
  $items = [];
  // DB kaynaklı ürünler
  if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    if (!empty($ids)) {
      $placeholders = implode(',', array_fill(0, count($ids), '?'));
      $stmt = $pdo->prepare("SELECT id,name,price FROM products WHERE id IN ($placeholders)");
      $stmt->execute($ids);
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      foreach ($rows as $r) {
        $qty = $_SESSION['cart'][$r['id']];
        $items[] = [
          'id' => $r['id'],
          'name' => $r['name'],
          'price' => $r['price'],
          'qty' => $qty,
          'subtotal' => $r['price'] * $qty
        ];
      }
    }
  }

  // Session-sample ürünler
  if (!empty($_SESSION['cart_samples'])) {
    foreach ($_SESSION['cart_samples'] as $s) {
      $items[] = [
        'id' => null,
        'name' => $s['name'],
        'price' => $s['price'],
        'qty' => $s['qty'],
        'subtotal' => $s['price'] * $s['qty']
      ];
    }
  }

  return $items;
}

$cartItems = cartItemsSummary($pdo);
$total = array_sum(array_column($cartItems, 'subtotal'));

$currentTable = $_SESSION['table'] ?? null;

?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Müşteri Paneli</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="container py-4">
    <header class="site-header mb-3">
      <img src="Logo.png" alt="MOLA KAFE" class="logo">
      <div class="site-title">MOLA KAFE</div>
    </header>
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Menü</h2>
      <a href="index.php" class="btn btn-outline-secondary">Geri</a>
    </div>

    <?php if ($currentTable): ?>
      <div class="alert alert-info d-flex justify-content-between align-items-center">
        <div>Oturulan Masa: <strong>Masa <?php echo htmlspecialchars($currentTable); ?></strong></div>
        <div>
          <a href="customer.php?unset_table=1" class="btn btn-sm btn-outline-secondary">Masa Değiştir</a>
        </div>
      </div>
    <?php else: ?>
      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title">Masa Seçimi</h5>
          <p>Devam etmeden önce lütfen oturduğunuz masayı seçin. İsterseniz QR kodunu okutabilir veya aşağıdan masanızı manuel seçebilirsiniz.</p>
          <div class="d-flex gap-2 align-items-center">
            <form class="d-flex" method="get" action="customer.php">
              <select name="table" class="form-select me-2" style="width:140px;">
                <?php for ($i=1;$i<=5;$i++): ?>
                  <option value="<?php echo $i; ?>">Masa <?php echo $i; ?></option>
                <?php endfor; ?>
              </select>
              <button class="btn btn-primary" type="submit">Masa Seç</button>
            </form>
            <a href="qr_codes.php" class="btn btn-outline-primary">QR ile Seç</a>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <div class="row">
      <div class="col-md-8">
        <!-- Menu Sections (placeholders) -->
        <?php
          $sections = [
            'Sıcak İçecekler',
            'Soğuk içecekler',
            'Tatlılar',
            'Atıştırmalıklar'
          ];
        ?>

        <?php
          // Örnek ürün listesi (isim ve fiyat)
          $samples = [
            'Sıcak İçecekler' => [
              ['name' => 'Espresso', 'price' => 25.00],
              ['name' => 'Latte', 'price' => 32.00],
              ['name' => 'Türk Kahvesi', 'price' => 20.00],
            ],
            'Soğuk içecekler' => [
              ['name' => 'Kola', 'price' => 15.00],
              ['name' => 'Soğuk Çay', 'price' => 12.00],
              ['name' => 'Buzlu Latte', 'price' => 34.00],
            ],
            'Tatlılar' => [
              ['name' => 'Sütlaç', 'price' => 28.00],
              ['name' => 'Cheesecake', 'price' => 45.00],
            ],
            'Atıştırmalıklar' => [
              ['name' => 'Patates Kızartması', 'price' => 30.00],
              ['name' => 'Simit', 'price' => 10.00],
            ],
          ];
        ?>

        <?php foreach ($sections as $sec): ?>
          <div class="mb-4">
            <h3 class="mb-2"><?php echo htmlspecialchars($sec); ?></h3>
            <div class="row">
              <?php if (!empty($samples[$sec])): ?>
                <?php foreach ($samples[$sec] as $p): ?>
                  <div class="col-md-6 mb-3">
                    <div class="card h-100">
                      <div class="card-body d-flex flex-column justify-content-between">
                        <div>
                          <h5 class="card-title"><?php echo htmlspecialchars($p['name']); ?></h5>
                          <p class="card-text small text-muted"><?php echo number_format($p['price'],2); ?> TL</p>
                        </div>
                        <div class="mt-2 text-end">
                          <a href="customer.php?action=add_sample&name=<?php echo urlencode($p['name']); ?>&price=<?php echo $p['price']; ?>" class="btn btn-sm btn-success">Ekle</a>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="col-12">
                  <div class="card">
                    <div class="card-body">
                      <p class="text-muted mb-0">Henüz ürün eklenmedi. (Buraya <?php echo htmlspecialchars($sec); ?> ürünleri gelecek.)</p>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="col-md-4">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title">Siparişlerim</h5>
            <?php if (empty($cartItems)): ?>
              <p>Sepetiniz boş.</p>
            <?php else: ?>
              <ul class="list-group mb-2">
                <?php foreach ($cartItems as $it): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <div class="fw-semibold"><?php echo htmlspecialchars($it['name']); ?></div>
                      <div class="small text-muted"><?php echo $it['qty']; ?> x <?php echo number_format($it['price'],2); ?> TL</div>
                    </div>
                    <div><?php echo number_format($it['subtotal'],2); ?> TL</div>
                  </li>
                <?php endforeach; ?>
              </ul>
              <p class="fw-bold">Toplam: <?php echo number_format($total,2); ?> TL</p>
              <a href="customer.php?action=clear" class="btn btn-sm btn-outline-danger">Siparişleri Temizle</a>
              <form method="post" action="create_order.php" class="d-inline">
                <button class="btn btn-primary btn-sm" type="submit">Siparişi Gönder</button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
