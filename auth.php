<?php
/* ============================================================
   auth.php — User Authentication (Register / Login / Logout)
   POST /auth.php?action=register  → { name, email, password }
   POST /auth.php?action=login     → { email, password }
   POST /auth.php?action=logout
   GET  /auth.php?action=me        → returns session user
   ============================================================ */

require_once 'config.php';
session_start();

$action = $_GET['action'] ?? $_POST['action'] ?? '';

match ($action) {
    'register' => handleRegister(),
    'login'    => handleLogin(),
    'logout'   => handleLogout(),
    'me'       => handleMe(),
    default    => jsonResponse(['error' => 'Unknown action.'], 400),
};


/* ─── REGISTER ─── */
function handleRegister(): void {
    $body = getRequestBody();
    $name     = clean($body['name'] ?? '');
    $email    = clean($body['email'] ?? '');
    $password = $body['password'] ?? '';

    $errors = [];
    if (empty($name))     $errors[] = 'Name is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

    if (!empty($errors)) {
        jsonResponse(['success' => false, 'errors' => $errors], 422);
    }

    $pdo = getDB();

    // Check for existing user
    $check = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $check->execute([':email' => $email]);
    if ($check->fetch()) {
        jsonResponse(['success' => false, 'errors' => ['Email already registered.']], 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, created_at) VALUES (:name, :email, :password, NOW())");
    $stmt->execute([':name' => $name, ':email' => $email, ':password' => $hash]);

    $userId = $pdo->lastInsertId();
    $_SESSION['user'] = ['id' => $userId, 'name' => $name, 'email' => $email];

    jsonResponse(['success' => true, 'message' => 'Registration successful.', 'user' => $_SESSION['user']]);
}


/* ─── LOGIN ─── */
function handleLogin(): void {
    $body  = getRequestBody();
    $email = clean($body['email'] ?? '');
    $pass  = $body['password'] ?? '';

    if (empty($email) || empty($pass)) {
        jsonResponse(['success' => false, 'errors' => ['Email and password are required.']], 422);
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT id, name, email, password FROM users WHERE email = :email");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password'])) {
        jsonResponse(['success' => false, 'errors' => ['Invalid email or password.']], 401);
    }

    // Regenerate session ID on login (security)
    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id'    => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
    ];

    jsonResponse(['success' => true, 'message' => 'Login successful.', 'user' => $_SESSION['user']]);
}


/* ─── LOGOUT ─── */
function handleLogout(): void {
    session_destroy();
    jsonResponse(['success' => true, 'message' => 'Logged out.']);
}


/* ─── GET CURRENT USER ─── */
function handleMe(): void {
    if (isset($_SESSION['user'])) {
        jsonResponse(['success' => true, 'user' => $_SESSION['user']]);
    } else {
        jsonResponse(['success' => false, 'user' => null], 401);
    }
}


/* ─── MIDDLEWARE: Require Login ───
   Include this in any PHP file that needs authentication:

   require_once 'auth_check.php';

   Or inline:
   session_start();
   if (!isset($_SESSION['user'])) {
       jsonResponse(['error' => 'Unauthorized. Please log in.'], 401);
   }
*/
