<?php
/* ============================================================
   contact.php — Contact Form Handler
   POST: name, email, subject, message
   ============================================================ */

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed.'], 405);
}

$body = getRequestBody();

// --- Validate ---
$errors = [];
$name    = clean($body['name'] ?? '');
$email   = clean($body['email'] ?? '');
$subject = clean($body['subject'] ?? 'No Subject');
$message = clean($body['message'] ?? '');

if (empty($name))               $errors[] = 'Name is required.';
if (empty($email))              $errors[] = 'Email is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
if (empty($message))            $errors[] = 'Message is required.';
if (strlen($message) < 10)      $errors[] = 'Message must be at least 10 characters.';

if (!empty($errors)) {
    jsonResponse(['success' => false, 'errors' => $errors], 422);
}

// --- Save to DB ---
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO contact_messages (name, email, subject, message, created_at)
        VALUES (:name, :email, :subject, :message, NOW())
    ");
    $stmt->execute([
        ':name'    => $name,
        ':email'   => $email,
        ':subject' => $subject,
        ':message' => $message,
    ]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => 'Could not save message.'], 500);
}

// --- Send Email (optional) ---
$to      = 'you@yourdomain.com'; // ← your email
$headers = "From: $email\r\nReply-To: $email\r\nContent-Type: text/plain; charset=UTF-8";
$body    = "Name: $name\nEmail: $email\n\n$message";
// mail($to, $subject, $body, $headers); // Uncomment to enable email sending

jsonResponse(['success' => true, 'message' => 'Your message has been sent. Thank you!']);
