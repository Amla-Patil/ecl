<?php
/* ============================================================
   captcha.php — Generates a numeric CAPTCHA
   GET  → returns plain number (used inline via include)
   GET ?json=1 → returns {"captcha":"1234"} for AJAX refresh
   ============================================================ */

session_start();

$captcha = rand(1000, 9999);
$_SESSION['captcha'] = (string)$captcha;

// AJAX refresh: return JSON
if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode(['captcha' => $captcha]);
    exit;
}

// Inline include: just echo the number
echo $captcha;
?>
