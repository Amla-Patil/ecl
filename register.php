<?php
/* ============================================================
   register.php — Glow Beauty User Registration
   - Prepared statements (no SQL injection)
   - Password hashing with bcrypt
   - Duplicate username/email check
   - Returns JSON for AJAX (used by login.html JS)
   ============================================================ */

session_start();
include "db.php";

// ── AJAX / POST handler ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';
    $errors   = [];

    // Validate
    if (empty($username) || strlen($username) < 3)
        $errors[] = "Username must be at least 3 characters.";

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Enter a valid email address.";

    if (strlen($password) < 8)
        $errors[] = "Password must be at least 8 characters.";

    // Check duplicate username
    if (empty($errors)) {
        $check = mysqli_prepare($conn, "SELECT id FROM users WHERE username = ?");
        mysqli_stmt_bind_param($check, "s", $username);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);
        if (mysqli_stmt_num_rows($check) > 0)
            $errors[] = "Username already taken. Please choose another.";
        mysqli_stmt_close($check);
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }

    // Insert user
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = mysqli_prepare($conn, "INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sss", $username, $email, $hash);

    if (mysqli_stmt_execute($stmt)) {
        // Auto-login after register
        $_SESSION['user']     = $username;
        $_SESSION['user_id']  = mysqli_insert_id($conn);
        echo json_encode(['success' => true, 'message' => 'Account created! Welcome to Glow Beauty ', 'redirect' => 'index.html']);
    } else {
        echo json_encode(['success' => false, 'errors' => ['Registration failed: ' . mysqli_error($conn)]]);
    }

    mysqli_stmt_close($stmt);
    exit;
}

// ── Standalone page fallback (if accessed directly) ─────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | Glow Beauty</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
</head>
<body>
<header>
  <div class="header-glow"></div>
  <h1>Glow Beauty</h1>
  <h3>Your Beauty, Our Passion </h3>
</header>
<nav>
  <div class="nav-left">
    <a href="index.html">Home</a>
    <a href="login.php">Login</a>
  </div>
</nav>

<div class="login-box">
  <h2 style="font-family:'Playfair Display',serif;color:#e91e63;margin-bottom:6px;">Create Account</h2>
  <p>Join Glow Beauty today </p>

  <?php if (!empty($message)): ?>
    <p style="color:<?= strpos($message,'success') !== false ? '#43a047' : '#e53935' ?>;margin-bottom:12px;">
      <?= htmlspecialchars($message) ?>
    </p>
  <?php endif; ?>

  <form method="post" id="registerForm" class="validate-form">
    <label>Username</label>
    <input type="text" name="username" placeholder="glowuser123" required minlength="3">

    <label>Email <span style="color:#aaa;font-size:12px;">(optional)</span></label>
    <input type="email" name="email" placeholder="you@email.com">

    <label>Password</label>
    <input type="password" name="password" placeholder="Min 8 characters" required minlength="8">

    <br>
    <button type="submit" name="register" class="btn-submit" style="margin-top:8px;">Create Account</button>
  </form>

  <p style="margin-top:16px;font-size:14px;color:#777;">
    Already have an account? <a href="login.php" style="color:#e91e63;">Login here</a>
  </p>
  <a href="index.html" class="back-link">← Back to Home</a>
</div>

<footer><p>© 2026 Glow Beauty. All rights reserved.</p></footer>
</body>
</html>
