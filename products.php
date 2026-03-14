<?php
/* ============================================================
   products.php — Products API (Dynamic Content + DB Ops)
   GET  /products.php              → list all products
   GET  /products.php?id=1         → get one product
   GET  /products.php?search=shoes → search products
   POST /products.php              → add a product (admin)
   PUT  /products.php?id=1         → update a product (admin)
   DELETE /products.php?id=1       → delete a product (admin)
   ============================================================ */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$search = clean($_GET['search'] ?? '');

match ($method) {
    'GET'    => $id ? getProduct($id) : getProducts($search),
    'POST'   => addProduct(),
    'PUT'    => updateProduct($id),
    'DELETE' => deleteProduct($id),
    default  => jsonResponse(['error' => 'Method not allowed.'], 405),
};


/* ─── GET ALL / SEARCH ─── */
function getProducts(string $search): void {
    $pdo = getDB();

    if ($search) {
        $stmt = $pdo->prepare("
            SELECT * FROM products
            WHERE name LIKE :q OR description LIKE :q OR category LIKE :q
            ORDER BY created_at DESC
        ");
        $stmt->execute([':q' => "%$search%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
    }

    $products = $stmt->fetchAll();
    jsonResponse(['success' => true, 'products' => $products]);
}


/* ─── GET ONE ─── */
function getProduct(int $id): void {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $product = $stmt->fetch();

    if (!$product) jsonResponse(['error' => 'Product not found.'], 404);

    jsonResponse(['success' => true, 'product' => $product]);
}


/* ─── ADD PRODUCT ─── */
function addProduct(): void {
    requireAuth();

    $body     = getRequestBody();
    $name     = clean($body['name'] ?? '');
    $price    = (float)($body['price'] ?? 0);
    $category = clean($body['category'] ?? '');
    $desc     = clean($body['description'] ?? '');
    $image    = clean($body['image'] ?? '');
    $stock    = (int)($body['stock'] ?? 0);

    if (empty($name) || $price <= 0) {
        jsonResponse(['success' => false, 'errors' => ['Name and a valid price are required.']], 422);
    }

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO products (name, price, category, description, image, stock, created_at)
        VALUES (:name, :price, :category, :description, :image, :stock, NOW())
    ");
    $stmt->execute([
        ':name'        => $name,
        ':price'       => $price,
        ':category'    => $category,
        ':description' => $desc,
        ':image'       => $image,
        ':stock'       => $stock,
    ]);

    jsonResponse(['success' => true, 'message' => 'Product added.', 'id' => $pdo->lastInsertId()], 201);
}


/* ─── UPDATE PRODUCT ─── */
function updateProduct(?int $id): void {
    requireAuth();

    if (!$id) jsonResponse(['error' => 'Product ID required.'], 400);

    $body     = getRequestBody();
    $name     = clean($body['name'] ?? '');
    $price    = (float)($body['price'] ?? 0);
    $category = clean($body['category'] ?? '');
    $desc     = clean($body['description'] ?? '');
    $image    = clean($body['image'] ?? '');
    $stock    = (int)($body['stock'] ?? 0);

    $pdo  = getDB();
    $stmt = $pdo->prepare("
        UPDATE products
        SET name=:name, price=:price, category=:category,
            description=:description, image=:image, stock=:stock
        WHERE id=:id
    ");
    $stmt->execute([
        ':name'        => $name,
        ':price'       => $price,
        ':category'    => $category,
        ':description' => $desc,
        ':image'       => $image,
        ':stock'       => $stock,
        ':id'          => $id,
    ]);

    jsonResponse(['success' => true, 'message' => 'Product updated.']);
}


/* ─── DELETE PRODUCT ─── */
function deleteProduct(?int $id): void {
    requireAuth();

    if (!$id) jsonResponse(['error' => 'Product ID required.'], 400);

    $pdo  = getDB();
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);

    jsonResponse(['success' => true, 'message' => 'Product deleted.']);
}


/* ─── AUTH GUARD ─── */
function requireAuth(): void {
    session_start();
    if (!isset($_SESSION['user'])) {
        jsonResponse(['error' => 'Unauthorized. Please log in.'], 401);
    }
}
