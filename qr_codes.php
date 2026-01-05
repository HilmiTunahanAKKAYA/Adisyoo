<?php
// Basit QR kod sayfası - her masa için QR gösterir.
// QR kodları Google Chart API kullanılarak oluşturulur (özgün URL enkodlanır).

// Masa sayısı
$tables = range(1,5);
$base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['REQUEST_URI']);
// normalize base to project root path
$base = rtrim($base, '/') . '/customer.php';

?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Masa QR Kodları - MOLA KAFE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="container py-4">
    <header class="site-header mb-4">
      <img src="Logo.png" alt="MOLA KAFE" class="logo">
      <div class="site-title">MOLA KAFE — Masa QR Kodları</div>
    </header>

    <p>Her masanın QR kodunu telefonunuzla okutup o masa için sipariş verebilirsiniz. Aşağıdaki QR kodlarının hedefi örnektir; örn. Masa 3 için <code>customer.php?table=3</code>.</p>

    <div class="row">
      <?php
        // possible folders where user might have placed images (try variants)
        $imgDirs = ['qrİmages','qrImages','qrimages','qr_images','qr-images'];
        $namePatterns = [
          'masa_%d.png','masa-%d.png','Masa_%d.png','Masa-%d.png',
          'masa%d.png','table%d.png','qr-%d.png','%d.png'
        ];
      ?>
      <?php foreach ($tables as $t):
        $url = $base . '?table=' . $t;
        $qr = 'https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=' . urlencode($url);

        // try to find a local image in candidate dirs
        $localImg = null;
        foreach ($imgDirs as $d) {
          foreach ($namePatterns as $p) {
            $fn = sprintf($p, $t);
            $path = __DIR__ . DIRECTORY_SEPARATOR . $d . DIRECTORY_SEPARATOR . $fn;
            if (file_exists($path)) {
              // use web path relative to project
              $localImg = $d . '/' . $fn;
              break 2;
            }
          }
        }
      ?>
      <div class="col-md-4 mb-4">
        <div class="card text-center">
          <div class="card-body">
            <h5 class="card-title">Masa <?php echo $t; ?></h5>
            <?php if ($localImg): ?>
              <img src="<?php echo htmlspecialchars($localImg); ?>" alt="QR Masa <?php echo $t; ?>" class="img-fluid" />
              <p class="small mt-2 mb-0 text-muted">(Yerel QR görseli bulundu: <?php echo htmlspecialchars($localImg); ?>)</p>
            <?php else: ?>
              <img src="<?php echo $qr; ?>" alt="QR Masa <?php echo $t; ?>" class="img-fluid" />
            <?php endif; ?>
            <p class="small mt-2 mb-0">URL: <a href="<?php echo htmlspecialchars($url); ?>"><?php echo htmlspecialchars($url); ?></a></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</body>
</html>
