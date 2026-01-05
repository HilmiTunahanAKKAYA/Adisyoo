<?php
session_start();

// Basit yetkilendirme: sadece operator/manager rolleri erişebilir
if (empty($_SESSION['user'])) {
  header('Location: login_operator.php');
  exit;
}
$userRole = $_SESSION['user']['role'] ?? '';
$allowed = ['operator','manager'];
if (!in_array($userRole, $allowed, true)) {
  header('Location: login_operator.php');
  exit;
}

$username = htmlspecialchars($_SESSION['user']['username']);
require_once __DIR__ . '/db.php';
$pdo = getPDO();
$itemsByTable = [];
$tableTotals = [];
 $stmt = $pdo->prepare("SELECT o.table_id, p.name, oi.quantity, COALESCE(NULLIF(oi.price,0), p.price, 0) AS price FROM order_items oi JOIN orders o ON o.id = oi.order_id LEFT JOIN products p ON p.id = oi.product_id WHERE o.status IN ('open','preparing') ORDER BY o.created_at ASC");
$stmt->execute();
while($r = $stmt->fetch(PDO::FETCH_ASSOC)){
  $table = $r['table_id'] ?? '-';
  $name = $r['name'] ?? 'Ürün';
  $qty = (int)($r['quantity'] ?? 1);
      $price = (float)($r['price'] ?? 0.0);
  if (!isset($itemsByTable[$table])) $itemsByTable[$table] = [];
      if (!isset($itemsByTable[$table][$name])) $itemsByTable[$table][$name] = ['qty'=>0,'unit_price'=>$price];
      $itemsByTable[$table][$name]['qty'] += $qty;
      // keep latest unit price
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
  <title>MOLA KAFE — Operatör</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <div class="container py-4">
    <header class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center">
        <img src="Logo.png" alt="MOLA KAFE" style="height:48px; margin-right:12px;">
        <div>
          <div class="h4 mb-0">MOLA KAFE — Operatör</div>
          <div class="small text-muted">Operasyon paneli</div>
        </div>
      </div>
      <div class="text-end">
        <div class="small">Hoşgeldiniz, <strong><?php echo $username; ?></strong></div>
        <a href="logout.php" class="btn btn-outline-danger btn-sm mt-1">Çıkış</a>
      </div>
    </header>
    
    <!-- Yeni Siparişler (operator onayı bekliyor) -->
    <div class="mb-4">
      <h5>Yeni Siparişler (Onay Bekliyor)</h5>
      <div id="pending-orders">
        <div class="text-muted">Yükleniyor...</div>
      </div>
    </div>
    <!-- Sabit 5 masa için alanlar (Masa 1..5) -->
    <div class="row mb-3">
      <?php for ($t = 1; $t <= 5; $t++):
        $products = $itemsByTable[$t] ?? [];
      ?>
        <div class="col-12 col-sm-6 col-md-4 col-lg-2 mb-3">
          <div class="card h-100">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <h6 class="card-title mb-0">Masa <?php echo $t; ?></h6>
                  <div><strong id="table-<?php echo $t; ?>-total"><?php echo number_format($tableTotals[$t] ?? 0.0, 2); ?> TL</strong></div>
                </div>
                <div id="table-<?php echo $t; ?>-contents">
                  <?php if (empty($products)): ?>
                    <div class="text-muted">Masa <?php echo $t; ?> için henüz sipariş yok.</div>
                  <?php else: ?>
                    <ul class="list-unstyled mb-0">
                      <?php foreach ($products as $pname => $meta): ?>
                        <li><?php echo htmlspecialchars($pname); ?> — <?php echo intval($meta['qty']); ?> x <?php echo number_format($meta['unit_price'],2); ?> TL <span class="badge bg-secondary ms-2"><?php echo intval($meta['qty']); ?></span></li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </div>
              </div>
            </div>
        </div>
      <?php endfor; ?>
    </div>
  </div>

  <!-- Siparişler bölümü kaldırıldığı için JS yok -->
  <script>
    // Poll server for current orders and update each Masa 1..5 card
    async function fetchAndUpdateTables(){
      try{
        const res = await fetch('api/current_orders.php');
        const data = await res.json();
        if(!data.ok) return;
        // aggregate per table for statuses open/preparing
        const perTable = {};
        const pendingOrders = [];
        (data.orders||[]).forEach(o => {
          if (o.status === 'pending') {
            pendingOrders.push(o);
            return;
          }
          if (o.status !== 'open' && o.status !== 'preparing') return;
          const tid = o.table_id || '1';
          if (!perTable[tid]) perTable[tid] = {};
          (o.items||[]).forEach(it => {
            const name = it.name || 'Ürün';
            const qty = parseInt(it.qty) || 1;
            const unit = Number(it.price) || 0.0;
            if (!perTable[tid][name]) perTable[tid][name] = { qty: 0, unit_price: unit };
            perTable[tid][name].qty += qty;
            // keep latest known unit price
            perTable[tid][name].unit_price = unit;
          });
        });
        // update DOM for masa 1..5
        const tableTotals = {};
        for(let t=1;t<=5;t++){
          const container = document.getElementById('table-'+t+'-contents');
          const products = perTable[t] || {};
          if(!container) continue;
          if(Object.keys(products).length===0){
            container.innerHTML = `<div class="text-muted">Masa ${t} için henüz sipariş yok.</div>`;
            // update total display
            const totEl = document.getElementById('table-'+t+'-total'); if (totEl) totEl.innerText = '0.00 TL';
          } else {
            let html = '<ul class="list-unstyled mb-0">';
            let running = 0.0;
            for(const [pname,meta] of Object.entries(products)){
              const qty = meta.qty || 0;
              const unit = Number(meta.unit_price) || 0.0;
              running += qty * unit;
              html += `<li>${pname} — ${qty} x ${unit.toFixed(2)} TL <span class="badge bg-secondary ms-2">${qty}</span></li>`;
            }
            html += '</ul>';
            container.innerHTML = html;
            const totEl = document.getElementById('table-'+t+'-total'); if (totEl) totEl.innerText = running.toFixed(2) + ' TL';
          }
        }

        // Render pending orders for operator approval
        const pendCont = document.getElementById('pending-orders');
        if (pendCont) {
          if (pendingOrders.length === 0) {
            pendCont.innerHTML = '<div class="alert alert-secondary">Yeni onay bekleyen sipariş yok.</div>';
          } else {
            let html = '<table class="table table-sm"><thead><tr><th>Masa No</th><th>Ürün</th><th>Adet</th><th>Tutar</th></tr></thead><tbody>';
            pendingOrders.forEach(o => {
              // order header row with approve button
              html += `<tr class="table-active"><td colspan="4">#${o.id} — Masa ${o.table_id} • Toplam: ${Number(o.total).toFixed(2)} TL <button class="btn btn-sm btn-success ms-2 approve-order-btn" data-order="${o.id}">Onayla</button></td></tr>`;
              (o.items||[]).forEach(it => {
                const lineTotal = (Number(it.price) * Number(it.qty)).toFixed(2);
                html += `<tr data-order-row="${o.id}"><td>${o.table_id}</td><td>${it.name}</td><td>${it.qty}</td><td>${lineTotal} TL</td></tr>`;
              });
            });
            html += '</tbody></table>';
            pendCont.innerHTML = html;
          }
        }
      }catch(e){ console.error('fetchAndUpdateTables', e); }
    }
    // initial load
    fetchAndUpdateTables();
    // poll every 5s
    setInterval(fetchAndUpdateTables, 5000);

    // handle approve button clicks (delegated)
    document.addEventListener('click', async function(e){
      const btn = e.target.closest && e.target.closest('.approve-order-btn');
      if (!btn) return;
      const orderId = btn.dataset.order;
      if (!confirm('Siparişi onaylamak istiyor musunuz? Onaylandığında masa için görünecektir.')) return;
      try{
        const fd = new FormData(); fd.append('order_id', orderId); fd.append('status', 'open');
        const r = await fetch('api/update_order_status.php', { method: 'POST', body: fd });
        const data = await r.json();
        if (data.ok) {
          // refresh lists immediately
          fetchAndUpdateTables();
        } else {
          alert('Onay başarısız: ' + (data.error||'unknown'));
        }
      }catch(err){ console.error(err); alert('Ağ hatası'); }
    });
  </script>
</body>
</html>
