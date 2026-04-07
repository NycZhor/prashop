<?php
session_start();

// Proteksi: Jika belum login, tendang ke login.php
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Proteksi: Jika admin, tendang ke management_users.php
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: ../admin/management_users.php");
    exit();
}

// Koneksi database
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "prashop_db";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Ambil username dari session dan cari user_id
$username = $_SESSION['user'];
$user_id = 0;
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$user_id = $user_data['id'] ?? 0;

// Ambil semua transaksi user ini
$transactions = [];
if ($user_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prashop - My Purchase</title>
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=IM+FELL+French+Canon+SC&display=swap" rel="stylesheet">
    
    <style>
        .status-badge-trx.processing {
            background: #fff3e0 !important;
            color: #f57c00 !important;
        }
        .status-badge-trx.completed {
            background: #e8f5e9 !important;
            color: #388e3c !important;
        }
        .status-badge-trx.pending {
            background: #fff3e0 !important;
            color: #f57c00 !important;
        }
        .status-badge-trx.shipped {
            background: #e3f2fd !important;
            color: #1976d2 !important;
        }
        .status-badge-trx.cancelled {
            background: #ffebee !important;
            color: #d32f2f !important;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body class="home-body">

  <nav class="navbar">
    <div class="nav-logo">
        <img src="../asset/logo.png" alt="Prashop Logo" class="logo-img">
        <span class="logo-text">PRASHOP</span>
    </div>
    <div class="nav-links">
        <a href="home.php">Home</a>
        <a href="my_purchase.php" class="active">My Purchase</a>
        <a href="promotion.php">Promotion</a>
        <a href="cart.php">Cart</a>
        <a href="buying.php">Buying</a>
        <a href="profile.php">profile</a>
    </div>
     <div class="nav-auth">
         <a href="profile.php" class="nav-profile-btn" title="Profile">
        <i class="fas fa-user"></i> 
             </a>
        <a href="logout.php" class="logout-link">
            <img src="../asset/logo2.png" alt="Logout" class="logout-icon"> LOGOUT
        </a>
    </div>
</nav>

    <main class="content-container">
        <h1 class="page-title">My Purchase</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> 
                Order berhasil! Order ID: #<?php echo intval($_GET['order_id']); ?>
            </div>
        <?php endif; ?>
        
        <div class="purchase-list">
            <?php if (empty($transactions)): ?>
                <div style="text-align: center; padding: 80px 20px; color: #999;">
                    <i class="fas fa-shopping-bag" style="font-size: 80px; margin-bottom: 20px; display: block; opacity: 0.3;"></i>
                    <p style="font-size: 18px; margin: 0;">No purchases yet</p>
                    <a href="home.php" style="color: #3498db; text-decoration: none; margin-top: 20px; display: inline-block;">← Start Shopping</a>
                </div>
            <?php else: ?>
                <?php foreach ($transactions as $trx): ?>
                    <?php
                    // Ambil detail produk untuk transaksi ini
                    $stmt_detail = $conn->prepare("SELECT * FROM transaction_details WHERE transaction_id = ?");
                    $stmt_detail->bind_param("i", $trx['id']);
                    $stmt_detail->execute();
                    $details = $stmt_detail->get_result()->fetch_all(MYSQLI_ASSOC);
                    
                    // ✅ FIX: Format status dengan matching yang lebih fleksibel
                    $raw_db_status = $trx['status'] ?? '';
                    $clean_status = strtolower(trim($raw_db_status));
                    
                    // Mapping status yang mungkin ada typo/ variasi
                    $status_mapping = [
                        'processing' => 'processing',
                        'procesing' => 'processing',  // typo umum
                        'proses' => 'processing',
                        'process' => 'processing',
                        'pending' => 'pending',
                        'completed' => 'completed',
                        'complete' => 'completed',
                        'selesai' => 'completed',
                        'shipped' => 'shipped',
                        'kirim' => 'shipped',
                        'dikirim' => 'shipped',
                        'cancelled' => 'cancelled',
                        'cancel' => 'cancelled',
                        'batal' => 'cancelled'
                    ];
                    
                    // Tentukan status class
                    $status_class = $status_mapping[$clean_status] ?? 'pending';
                    
                    // Tentukan teks yang ditampilkan
                    $status_labels = [
                        'processing' => 'PROCESSING',
                        'pending' => 'PENDING',
                        'completed' => 'COMPLETED',
                        'shipped' => 'SHIPPED',
                        'cancelled' => 'CANCELLED'
                    ];
                    
                    $status_text = $status_labels[$status_class] ?? 'PENDING';
                    
                    // Format tanggal
                    $date_formatted = date('M d, Y', strtotime($trx['created_at']));
                    ?>
                    
                    <div class="order-card">
                        <div class="order-header">
                            <div class="order-info">
                                <p>Order Number: #<?php echo str_pad($trx['id'], 6, '0', STR_PAD_LEFT); ?></p>
                                <p>Date: <?php echo $date_formatted; ?></p>
                            </div>
                            <span class="status-badge-trx <?php echo $status_class; ?>" title="DB: <?php echo htmlspecialchars($raw_db_status); ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>
                        
                        <?php foreach ($details as $detail): ?>
                            <?php
                            // ✅ FIX: Ambil image dari products table berdasarkan product_id
                            $product_image = 'nb530.png'; // default fallback
                            if (!empty($detail['product_id'])) {
                                $stmt_img = $conn->prepare("SELECT image FROM products WHERE id = ?");
                                $stmt_img->bind_param("i", $detail['product_id']);
                                $stmt_img->execute();
                                $img_result = $stmt_img->get_result()->fetch_assoc();
                                if ($img_result && !empty($img_result['image'])) {
                                    $product_image = $img_result['image'];
                                }
                            }
                            ?>
                        <div class="order-content">
                            <img src="../asset/<?php echo htmlspecialchars($product_image); ?>" alt="<?php echo htmlspecialchars($detail['product_name']); ?>">
                            <div class="product-details">
                                <h3><?php echo htmlspecialchars($detail['product_name']); ?></h3>
                                <p>X<?php echo $detail['quantity']; ?></p>
                            </div>
                            <div class="order-total">
                                <span>TOTAL:</span>
                                <span>Rp <?php echo number_format($detail['price'] * $detail['quantity'], 0, ',', '.'); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; text-align: right;">
                            <strong>Grand Total: Rp <?php echo number_format($trx['total_amount'], 0, ',', '.'); ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
<?php $conn->close(); ?>