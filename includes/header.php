<?php
// Header include — set $pageTitle = '...' before including if needed
$pageTitle = $pageTitle ?? 'MOLA KAFE';
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
  <meta name="robots" content="noindex">
</head>
<body>
<?php if (empty($noSiteHeader)): ?>
  <div class="container py-4">
    <header class="site-header mb-3">
      <img src="Logo.png" alt="MOLA KAFE" class="logo">
      <div class="site-title">MOLA KAFE</div>
    </header>
<?php endif; ?>
