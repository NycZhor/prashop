<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = new mysqli("localhost","root","","prashop_db");
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$transaction_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($transaction_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid transaction ID']);
    exit();
}

// Get transaction main data
$stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ?");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();

if (!$transaction) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit();
}

// Get transaction details (items)
$stmt = $conn->prepare("SELECT * FROM transaction_details WHERE transaction_id = ?");
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Combine data
$response = [
    'success' => true,
    'data' => [
        'id' => $transaction['id'],
        'customer_name' => $transaction['customer_name'],
        'phone' => $transaction['phone'],
        'email' => $transaction['email'],
        'address' => $transaction['address'],
        'payment_method' => $transaction['payment_method'],
        'total_amount' => $transaction['total_amount'],
        'status' => $transaction['status'] ?? 'pending',
        'proof_path' => $transaction['proof_path'],
        'created_at' => $transaction['created_at'],
        'items' => $items
    ]
];

echo json_encode($response);

$stmt->close();
$conn->close();
?>