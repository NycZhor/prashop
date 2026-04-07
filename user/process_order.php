<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: buying.php");
    exit();
}

$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "prashop_db";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

/* =========================
   VALIDASI CART
========================= */
$cart = [];
if (isset($_POST['cart']) && is_array($_POST['cart'])) {
    $cart = $_POST['cart'];
}

if (empty($cart)) {
    header("Location: buying.php?error=empty_cart");
    exit();
}

/* =========================
   AMBIL USER ID
========================= */
$username = $_SESSION['user'];
$user_id = 0;

$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$user_id = $user_data['id'] ?? 0;

/* =========================
   AMBIL DATA FORM
========================= */
$name = $_POST['name'] ?? '';
$phone = '+62' . preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
$email = $_POST['email'] ?? '';
$payment_method = $_POST['payment_method'] ?? 'cod';
$address = $_POST['address'] ?? '-';
$total_amount = (int)($_POST['total_amount'] ?? 0);

/* =========================
   UPLOAD BUKTI TRANSFER
========================= */
$proof_path = null;

if ($payment_method === 'transfer' && !empty($_FILES['proof']['name']) && $_FILES['proof']['error'] === UPLOAD_ERR_OK) {

    // folder sesuai admin panel
    $target_dir = "../asset/proofs/";

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $ext = strtolower(pathinfo($_FILES["proof"]["name"], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];

    if (in_array($ext, $allowed)) {

        $file_name = time() . '_' . uniqid() . '.' . $ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["proof"]["tmp_name"], $target_file)) {
            // path yang disimpan ke database
            $proof_path = 'asset/proofs/' . $file_name;
        }
    }
}

/* =========================
   CEK STRUKTUR TABEL
========================= */
$columns = [];
$result = $conn->query("DESCRIBE transactions");
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

/* =========================
   FIELD MAPPING
========================= */
$available_columns = [];
$values = [];
$types = '';

$field_mapping = [
    'user_id' => ['value' => $user_id, 'type' => 'i'],
    'customer_name' => ['value' => $name, 'type' => 's'],
    'phone' => ['value' => $phone, 'type' => 's'],
    'email' => ['value' => $email, 'type' => 's'],
    'payment_method' => ['value' => $payment_method, 'type' => 's'],
    'address' => ['value' => $address, 'type' => 's'],
    'proof_path' => ['value' => $proof_path, 'type' => 's'],
    'total_amount' => ['value' => $total_amount, 'type' => 'i'],
    'status' => ['value' => 'pending', 'type' => 's'],
    'created_at' => ['value' => date('Y-m-d H:i:s'), 'type' => 's'],
];

foreach ($field_mapping as $col_name => $data) {
    if (in_array($col_name, $columns)) {
        $available_columns[] = $col_name;
        $values[] = $data['value'];
        $types .= $data['type'];
    }
}

if (empty($available_columns)) {
    header("Location: buying.php?error=db_error");
    exit();
}

/* =========================
   INSERT TRANSACTION
========================= */
$columns_str = implode(', ', $available_columns);
$placeholders = implode(', ', array_fill(0, count($available_columns), '?'));

$stmt = $conn->prepare("INSERT INTO transactions ($columns_str) VALUES ($placeholders)");
$stmt->bind_param($types, ...$values);
$stmt->execute();
$order_id = $conn->insert_id;

/* =========================
   INSERT DETAIL + KURANGI STOCK
========================= */
$stmt_detail = $conn->prepare("INSERT INTO transaction_details (transaction_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)");

foreach ($cart as $item) {
    if (!is_array($item)) continue;

    $pid = (int)$item['id'];
    $pname = $item['name'];
    $pprice = (int)$item['price'];
    $pqty = (int)$item['quantity'];

    if ($pid > 0 && $pprice > 0) {
        $stmt_detail->bind_param("iisii", $order_id, $pid, $pname, $pprice, $pqty);
        $stmt_detail->execute();

        // kurangi stok
        $stmt_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
        $stmt_stock->bind_param("iii", $pqty, $pid, $pqty);
        $stmt_stock->execute();
        $stmt_stock->close();
    }
}

/* =========================
   CLEAR CART & REDIRECT
========================= */
unset($_SESSION['cart']);

header("Location: my_purchase.php?success=1&order_id=" . $order_id);
exit();
?>