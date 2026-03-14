<?php
/* ============================================================
   db.php — Database Connection (MySQLi)
   Used by login.php, register.php, contact.php, etc.
   ============================================================ */

$host   = "localhost";
$user   = "root";
$pass   = "";            // ← change to your DB password
$dbname = "scure_login"; // ← matches your original DB name

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Database Connection Failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>
