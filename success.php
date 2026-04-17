<?php
/* ============================================================
   success.php — Post-login landing page
   Checks session so it can't be accessed directly without login
   ============================================================ */

session_start();

// If not logged in, redirect to login
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$username = htmlspecialchars($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome | Glow Beauty</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <style>
    .success-box {
      text-align: center;
      max-width: 480px;
      margin: 80px auto;
      background: #fff;
      border-radius: 20px;
      padding: 50px 40px;
      box-shadow: 0 20px 60px rgba(233,30,99,0.15);
    }
    .success-box .emoji { font-size: 56px; margin-bottom: 16px; }
    .success-box h2 {
      font-family: 'Playfair Display', serif;
      color: #e91e63;
      font-size: 28px;
      margin-bottom: 10px;
    }
    .success-box p { color: #777; margin-bottom: 28px; }
    .btn-home {
      display: inline-block;
      background: #e91e63;
      color: white;
      padding: 12px 32px;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      font-family: 'DM Sans', sans-serif;
      transition: background 0.2s;
    }
    .btn-home:hover { background: #c2185b; }
    .logout-link {
      display: block;
      margin-top: 16px;
      color: #aaa;
      font-size: 13px;
      text-decoration: none;
    }
    .logout-link:hover { color: #e91e63; }
  </style>
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
    <a href="logout.php">Logout</a>
  </div>
</nav>

<div class="success-box">
  <div class="emoji"></div>
  <h2>Login Successful!</h2>
  <p>Welcome back, <strong><?= $username ?></strong>! We're so glad you're here.</p>
  <a href="index.html" class="btn-home">Shop Now </a>
  <a href="logout.php" class="logout-link">Not you? Log out</a>
</div>

<footer><p>© 2026 Glow Beauty. All rights reserved.</p></footer>
</body>
</html>
