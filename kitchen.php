<?php
session_start();
require_once __DIR__ . '/db.php';

// Role-based auth: cashier (kasa) or manager can access; keep kitchen role for compatibility
if (empty($_SESSION['user'])) {
  header('Location: login_kitchen.php');
  exit;
}
$userRole = $_SESSION['user']['role'] ?? '';
$allowed = ['cashier','manager','kitchen'];
if (!in_array($userRole, $allowed, true)) {
  header('Location: login_kitchen.php');
  exit;
}

$pdo = getPDO();

// change status action (simple POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id']) && isset($_POST['status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];
  // cashier primarily handles ready/served -> paid
  $allowed = ['open','preparing','ready','served','paid'];
    if (!in_array($status, $allowed)) {
        http_response_code(400);
        echo 'Invalid status';
        exit;
    }
    $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
    $stmt->execute([$status, $orderId]);
    header('Location: kitchen.php');
    exit;
}

$stmt = $pdo->query("SELECT id,table_id,total,created_at,status FROM orders WHERE status IN ('ready','served') ORDER BY created_at ASC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// fetch items for orders
$orderIds = array_column($orders, 'id');
$itemsByOrder = [];
if (!empty($orderIds)) {
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itStmt = $pdo->prepare("SELECT oi.order_id, oi.quantity, oi.price, p.name FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id WHERE oi.order_id IN ($placeholders) ORDER BY oi.id ASC");
    $itStmt->execute($orderIds);
    while ($r = $itStmt->fetch(PDO::FETCH_ASSOC)) {
        $oid = $r['order_id'];
        if (!isset($itemsByOrder[$oid])) $itemsByOrder[$oid] = [];
        $itemsByOrder[$oid][] = $r;
    }
}

// Masa bazlı ürün listesi (open/preparing) - kasa personeli için masalara göre ürünleri topla
$itemsByTable = [];
$tStmt = $pdo->prepare("SELECT o.table_id, p.name, oi.quantity, oi.price FROM order_items oi JOIN orders o ON o.id = oi.order_id LEFT JOIN products p ON p.id = oi.product_id WHERE o.status IN ('open','preparing') ORDER BY o.created_at ASC");
$tStmt->execute();
while($r = $tStmt->fetch(PDO::FETCH_ASSOC)){
  $table = $r['table_id'] ?? '-';
  $name = $r['name'] ?? 'Ürün';
  $qty = (int)($r['quantity'] ?? 1);
  $price = (float)($r['price'] ?? 0.0);
  if (!isset($itemsByTable[$table])) $itemsByTable[$table] = [];
  if (!isset($itemsByTable[$table][$name])) $itemsByTable[$table][$name] = ['qty'=>0,'unit_price'=>$price];
  $itemsByTable[$table][$name]['qty'] += $qty;
  // keep latest unit price if changed
  $itemsByTable[$table][$name]['unit_price'] = $price;
  if (!isset($tableTotals[$table])) $tableTotals[$table] = 0.0;
  $tableTotals[$table] += $qty * $price;
}

?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kasa - MOLA KAFE</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
  <style>.order-card { margin-bottom:10px; }</style>
</head>
<body>
  <div class="container py-4">
    <header class="site-header mb-3">
      <img src="Logo.png" alt="MOLA KAFE" class="logo">
  <div class="site-title">MOLA KAFE — Kasa</div>
    </header>

    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Kasa - Ödeme Noktası</h2>
      <div>
        <span class="me-3">Hoşgeldiniz, <strong><?php echo htmlspecialchars($_SESSION['user']['username']); ?></strong></span>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Çıkış</a>
        <button id="clearAllBtn" class="btn btn-sm btn-danger ms-2">Temizle</button>
      </div>
    </div>

    <!-- Masa bazlı ürün listeleri -->
    <div class="row mb-3">
      <?php for ($t = 1; $t <= 5; $t++):
        $products = $itemsByTable[$t] ?? [];
        $total = $tableTotals[$t] ?? 0.0;
      ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-3">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center">
                <h6 class="card-title mb-0">Masa <?php echo $t; ?></h6>
                <div><strong id="kasa-table-<?php echo $t; ?>-total"><?php echo number_format($total,2); ?> TL</strong></div>
              </div>
              <div id="kasa-table-<?php echo $t; ?>-contents">
                <?php if (empty($products)): ?>
                  <div class="text-muted">Masa <?php echo $t; ?> için henüz sipariş yok.</div>
                <?php else: ?>
                  <ul class="list-unstyled mb-0">
                    <?php foreach ($products as $pname => $meta): ?>
                      <li><?php echo htmlspecialchars($pname); ?> — <?php echo intval($meta['qty']); ?> x <?php echo number_format($meta['unit_price'],2); ?> TL <span class="badge bg-secondary ms-2"><?php echo intval($meta['qty']); ?></span></li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
                <div class="mt-2">
                  <button class="btn btn-sm btn-success pay-table-btn" data-table="<?php echo $t; ?>">Ödeme Yap</button>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endfor; ?>
    </div>

    <?php if (empty($orders)): ?>
      <div class="alert alert-secondary">Ödeme bekleyen sipariş yok.</div>
    <?php else: ?>
      <?php foreach ($orders as $o): ?>
        <div class="card order-card">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div><strong>#<?php echo $o['id']; ?></strong> — Masa <?php echo htmlspecialchars($o['table_id']); ?></div>
              <div><strong><?php echo number_format($o['total'],2); ?> TL</strong></div>
            </div>
            <div class="small text-muted"><?php echo $o['created_at']; ?> • <?php echo htmlspecialchars($o['status']); ?></div>
            <?php if (!empty($itemsByOrder[$o['id']])): ?>
              <ul class="mt-2">
                <?php foreach ($itemsByOrder[$o['id']] as $it): ?>
                  <li><?php echo htmlspecialchars($it['name'] ?? 'Ürün'); ?> — <?php echo (int)$it['quantity']; ?> x <?php echo number_format($it['price'],2); ?> TL</li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <div class="mt-2">
              <form method="post" class="d-inline">
                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                <input type="hidden" name="status" value="paid">
                <button class="btn btn-sm btn-primary">Ödendi</button>
              </form>
              <form method="post" class="d-inline ms-2">
                <input type="hidden" name="order_id" value="<?php echo $o['id']; ?>">
                <input type="hidden" name="status" value="served">
                <button class="btn btn-sm btn-secondary">Servis</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <script>
    // Poll server for current orders and update each Masa 1..5 card (open/preparing)
    async function fetchAndUpdateTables(){
      try{
        const res = await fetch('api/current_orders.php');
        const data = await res.json();
        if(!data.ok) return;
        const perTable = {};
        (data.orders||[]).forEach(o => {
          if (o.status !== 'open' && o.status !== 'preparing') return;
          const tid = o.table_id || '1';
          if (!perTable[tid]) perTable[tid] = {};
          (o.items||[]).forEach(it => {
            const name = it.name || 'Ürün';
            const qty = parseInt(it.qty) || 1;
            const unit = Number(it.price) || 0.0;
            if (!perTable[tid][name]) perTable[tid][name] = { qty: 0, unit_price: unit };
            perTable[tid][name].qty += qty;
            perTable[tid][name].unit_price = unit;
          });
        });
        for(let t=1;t<=5;t++){
          const container = document.getElementById('kasa-table-'+t+'-contents');
          const products = perTable[t] || {};
          if(!container) continue;
          let html = '';
          if(Object.keys(products).length===0){
            html += `<div class="text-muted">Masa ${t} için henüz sipariş yok.</div>`;
            const totEl = document.getElementById('kasa-table-'+t+'-total'); if (totEl) totEl.innerText = '0.00 TL';
          } else {
            html += '<ul class="list-unstyled mb-0">';
            let running = 0.0;
            for(const [pname,meta] of Object.entries(products)){
              const qty = meta.qty || 0;
              const unit = Number(meta.unit_price) || 0.0;
              running += qty * unit;
              html += `<li>${pname} — ${qty} x ${unit.toFixed(2)} TL <span class="badge bg-secondary ms-2">${qty}</span></li>`;
            }
            html += '</ul>';
            const totEl = document.getElementById('kasa-table-'+t+'-total'); if (totEl) totEl.innerText = running.toFixed(2) + ' TL';
          }
          // Always include the Ödeme Yap button so it remains visible after polling updates
          html += '<div class="mt-2"><button class="btn btn-sm btn-success pay-table-btn" data-table="' + t + '">Ödeme Yap</button></div>';
          container.innerHTML = html;
        }
      }catch(e){ console.error('fetchAndUpdateTables', e); }
    }
    fetchAndUpdateTables();
    setInterval(fetchAndUpdateTables, 5000);
    // handle Ödeme Yap buttons
    document.addEventListener('click', async function(e){
      const btn = e.target.closest && e.target.closest('.pay-table-btn');
      if (!btn) return;
      const tableId = btn.dataset.table;
      if (!confirm('Masa ' + tableId + ' için ödeme yapılıyor. Onaylıyor musunuz?')) return;
      // disable button to prevent double clicks
      btn.disabled = true;
      const origText = btn.innerText;
      btn.innerText = 'İşleniyor...';
      try{
        const fd = new FormData(); fd.append('table_id', tableId);
        const r = await fetch('api/pay_table.php', { method: 'POST', body: fd });
        const text = await r.text();
        let data = null;
        try { data = JSON.parse(text); } catch(parseErr) {
          console.error('pay_table non-json response:', text);
          alert('Sunucudan beklenmeyen cevap alındı. Konsolu kontrol edin.');
          return;
        }
        console.log('pay_table response', data);
        if (data.ok) {
          alert('Ödeme başarılı. ' + (data.deleted||0) + ' sipariş silindi.');
          // refresh whole page to reflect removed orders in list
          location.reload();
        } else {
          alert('Ödeme başarısız: ' + (data.error||'unknown'));
        }
      }catch(err){ console.error(err); alert('Ağ hatası'); }
      finally{
        btn.disabled = false;
        btn.innerText = origText;
      }
    });
  </script>
</body>
</html>
