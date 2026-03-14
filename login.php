<?php
/* ============================================================
   login.php — Glow Beauty Login
   - Prepared statements (no SQL injection)
   - CAPTCHA validation
   - Session-based auth
   - Returns JSON for AJAX (used by login.html JS)
   ============================================================ */

session_start();
include "db.php";

// ── AJAX / POST handler ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password']      ?? '';
    $captcha  = trim($_POST['captcha']  ?? '');

    // CAPTCHA check
    if (!isset($_SESSION['captcha']) || $captcha !== $_SESSION['captcha']) {
        echo json_encode(['success' => false, 'errors' => ['Invalid CAPTCHA. Please try again.']]);
        exit;
    }

    // Invalidate used CAPTCHA immediately
    unset($_SESSION['captcha']);

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'errors' => ['Username and password are required.']]);
        exit;
    }

    // Fetch user with prepared statement
    $stmt = mysqli_prepare($conn, "SELECT id, username, password FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$row || !password_verify($password, $row['password'])) {
        echo json_encode(['success' => false, 'errors' => ['Invalid username or password.']]);
        exit;
    }

    // Regenerate session (prevent fixation)
    session_regenerate_id(true);
    $_SESSION['user']    = $row['username'];
    $_SESSION['user_id'] = $row['id'];

    echo json_encode([
        'success'  => true,
        'message'  => 'Welcome back, ' . htmlspecialchars($row['username']) . '! 💖',
        'redirect' => 'index.html'
    ]);
    exit;
}

// ── Standalone page (also used by login.html as fallback) ────
$captchaCode = rand(1000, 9999);
$_SESSION['captcha'] = (string)$captchaCode;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Glow Beauty</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
<div id="toastContainer"></div>

<header>
  <div class="header-glow"></div>
  <h1>Glow Beauty</h1>
  <h3>Your Beauty, Our Passion ✨</h3>
</header>
<nav>
  <div class="nav-left">
    <a href="index.html">Home</a>
    <a href="login.php">Login</a>
  </div>
</nav>

<div class="login-box reveal">

  <!-- TAB SWITCHER -->
  <div class="auth-tabs">
    <button class="tab-btn active" data-tab="login">Login</button>
    <button class="tab-btn" data-tab="register">Register</button>
  </div>

  <!-- ── LOGIN FORM ── -->
  <div id="loginTab" class="tab-content active">
    <p>Welcome back! Please log in.</p>
    <form method="post" id="loginForm">
      <label>Username</label>
      <input type="text" name="username" placeholder="glowuser123" required>

      <label>Password</label>
      <input type="password" name="password" placeholder="••••••••" required>

      <label>CAPTCHA — enter the code below</label>
      <div class="captcha-row">
        <span id="captchaDisplay" class="captcha-code"><?= $captchaCode ?></span>
        <button type="button" id="refreshCaptcha" class="captcha-refresh" title="Refresh">↻</button>
      </div>
      <input type="text" name="captcha" id="captchaInput" placeholder="Enter code above" required maxlength="4">

      <button type="submit" name="login" class="btn-submit" id="loginBtn">Login</button>
      <p id="loginError" class="auth-error" style="display:none;"></p>
    </form>
  </div>

  <!-- ── REGISTER FORM ── -->
  <div id="registerTab" class="tab-content" style="display:none">
    <p>Create your account!</p>
    <form method="post" id="registerForm">
      <label>Username</label>
      <input type="text" name="username" placeholder="glowuser123" required minlength="3">

      <label>Email <span style="color:#aaa;font-size:12px;">(optional)</span></label>
      <input type="email" name="email" placeholder="you@email.com">

      <label>Password</label>
      <input type="password" name="password" id="regPassword" placeholder="Min 8 characters" required minlength="8">

      <label>Confirm Password</label>
      <input type="password" name="confirm_password" placeholder="Repeat password" required data-match="regPassword">

      <button type="submit" name="register" class="btn-submit" id="registerBtn">Create Account</button>
      <p id="registerError" class="auth-error" style="display:none;"></p>
    </form>
  </div>

  <a href="index.html" class="back-link">← Back to Home</a>
</div>

<footer><p>© 2026 Glow Beauty. All rights reserved.</p></footer>

<script src="main.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

  /* ── Tab switching ── */
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
      btn.classList.add('active');
      document.getElementById(btn.dataset.tab + 'Tab').style.display = 'block';
    });
  });

  /* ── CAPTCHA refresh (AJAX) ── */
  document.getElementById('refreshCaptcha')?.addEventListener('click', async () => {
    try {
      const res  = await fetch('captcha.php?json=1');
      const data = await res.json();
      document.getElementById('captchaDisplay').textContent = data.captcha;
      document.getElementById('captchaInput').value = '';
    } catch {
      showToast('Could not refresh CAPTCHA.', 3000);
    }
  });

  /* ── LOGIN submit ── */
  document.getElementById('loginForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn    = document.getElementById('loginBtn');
    const errEl  = document.getElementById('loginError');
    const form   = e.target;

    btn.textContent = 'Logging in...';
    btn.disabled    = true;
    errEl.style.display = 'none';

    const body = new URLSearchParams({
      login:    '1',
      username: form.username.value,
      password: form.password.value,
      captcha:  form.captcha.value,
    });

    try {
      const res  = await fetch('login.php', { method: 'POST', body });
      const data = await res.json();

      if (data.success) {
        showToast(data.message);
        setTimeout(() => window.location.href = data.redirect || 'index.html', 1500);
      } else {
        errEl.textContent   = (data.errors || ['Login failed.']).join(' ');
        errEl.style.display = 'block';
        // Refresh CAPTCHA on failure
        document.getElementById('refreshCaptcha').click();
      }
    } catch {
      errEl.textContent   = 'Network error. Please try again.';
      errEl.style.display = 'block';
    } finally {
      btn.textContent = 'Login';
      btn.disabled    = false;
    }
  });

  /* ── REGISTER submit ── */
  document.getElementById('registerForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn   = document.getElementById('registerBtn');
    const errEl = document.getElementById('registerError');
    const form  = e.target;

    // Client-side password match check
    if (form.password.value !== form.confirm_password.value) {
      errEl.textContent   = 'Passwords do not match.';
      errEl.style.display = 'block';
      return;
    }

    btn.textContent = 'Creating account...';
    btn.disabled    = true;
    errEl.style.display = 'none';

    const body = new URLSearchParams({
      register: '1',
      username: form.username.value,
      email:    form.email?.value || '',
      password: form.password.value,
    });

    try {
      const res  = await fetch('register.php', { method: 'POST', body });
      const data = await res.json();

      if (data.success) {
        showToast(data.message);
        setTimeout(() => window.location.href = data.redirect || 'index.html', 1500);
      } else {
        errEl.textContent   = (data.errors || ['Registration failed.']).join(' ');
        errEl.style.display = 'block';
      }
    } catch {
      errEl.textContent   = 'Network error. Please try again.';
      errEl.style.display = 'block';
    } finally {
      btn.textContent = 'Create Account';
      btn.disabled    = false;
    }
  });

});
</script>
</body>
</html>
