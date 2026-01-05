<!-- Kitchen-specific login page — posts to generic login handler with redirect -->
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Mutfak Girişi</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/style.css">
  <style>
    body { min-height:100vh; display:flex; align-items:center; justify-content:center; background: linear-gradient(135deg, var(--brand-primary) 0%, #052a2b 100%); }
    .login-shell { width:360px; border-radius:16px; padding:28px; box-shadow: 0 12px 40px rgba(2,18,23,0.6); background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); color:var(--brand-contrast); }
    .login-shell h2 { text-align:center; font-weight:700; margin-bottom:18px; }
    .form-control { background: rgba(255,255,255,0.03); border:1px solid rgba(255,255,255,0.08); color:#fff; border-radius:999px; padding:14px 18px; }
    .input-icon { position:relative; }
    .input-icon svg { position:absolute; right:16px; top:50%; transform:translateY(-50%); opacity:0.6; }
    .btn-login { background:#fff; color:var(--brand-primary); border-radius:999px; padding:10px 18px; font-weight:600; }
    .small-row { display:flex; justify-content:space-between; align-items:center; margin-top:8px; color:rgba(255,255,255,0.85); }
    a.text-link { color:rgba(255,255,255,0.9); text-decoration:underline; }
  </style>
</head>
<body>
  <div class="login-shell">
    <h2>Login</h2>
    <?php if (!empty($_GET['error'])): ?>
      <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars((string)$_GET['error']); ?></div>
    <?php endif; ?>
    <form method="post" action="login.php">
      <input type="hidden" name="redirect" value="kitchen.php">
      <div class="mb-3">
        <input name="username" class="form-control" placeholder="Username" required>
      </div>
      <div class="mb-3 input-icon">
        <input name="password" type="password" class="form-control" placeholder="Password" required>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M17 11H7a1 1 0 0 0-1 1v7a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-7a1 1 0 0 0-1-1z" stroke="#ffffff" stroke-opacity="0.8" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/><path d="M7 11V8a5 5 0 0 1 10 0v3" stroke="#ffffff" stroke-opacity="0.8" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>
      <div class="small-row">
        <div><input type="checkbox" id="remember"> <label for="remember">Remember me</label></div>
        <div><a class="text-link" href="#">Forgot password?</a></div>
      </div>
      <div class="d-grid mt-3">
        <button class="btn btn-login" type="submit">Login</button>
      </div>
      <div class="text-center mt-3" style="color:rgba(255,255,255,0.9)">Don't have a account? <a class="text-link" href="#">Register</a></div>
    </form>
  </div>
</body>
</html>
