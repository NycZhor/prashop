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

// 🔥 HANDLER: Konfirmasi pesanan diterima (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_received'])) {
    $order_id = intval($_POST['order_id']);
    
    // Validasi keamanan: hanya owner & status tidak boleh completed/cancelled
    $stmt_check = $conn->prepare("SELECT id FROM transactions WHERE id = ? AND user_id = ? AND status NOT IN ('completed', 'cancelled', 'selesai', 'batal', 'complete', 'cancel')");
    $stmt_check->bind_param("ii", $order_id, $user_id);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $stmt_update = $conn->prepare("UPDATE transactions SET status = 'completed' WHERE id = ?");
        $stmt_update->bind_param("i", $order_id);
        $stmt_update->execute();
    }
    
    // Redirect (PRG Pattern) agar form tidak ter-submit ulang saat refresh
    header("Location: my_purchase.php?success_received=1");
    exit();
}

// 🔥 HANDLER: Batalkan pesanan (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = intval($_POST['order_id']);
    
    // Validasi keamanan: hanya owner & status tidak boleh completed/cancelled
    $stmt_check = $conn->prepare("SELECT id FROM transactions WHERE id = ? AND user_id = ? AND status NOT IN ('completed', 'cancelled', 'selesai', 'batal', 'complete', 'cancel')");
    $stmt_check->bind_param("ii", $order_id, $user_id);
    $stmt_check->execute();
    
    if ($stmt_check->get_result()->num_rows > 0) {
        $stmt_update = $conn->prepare("UPDATE transactions SET status = 'cancelled' WHERE id = ?");
        $stmt_update->bind_param("i", $order_id);
        $stmt_update->execute();
    }
    
    // Redirect
    header("Location: my_purchase.php?success_cancelled=1");
    exit();
}

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
    <!-- Pastikan link CSS eksternal Anda sudah benar di sini -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=IM+FELL+French+Canon+SC&display=swap" rel="stylesheet">
    
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
        
        <?php if (isset($_GET['success_received'])): ?>
            <div class="success-toast">
                <i class="fas fa-check-circle"></i> 
                Pesanan berhasil dikonfirmasi! Terima kasih telah berbelanja di Prashop 🙏
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['success_cancelled'])): ?>
            <div class="success-toast" style="background-color: #e74c3c;">
                <i class="fas fa-times-circle"></i> 
                Pesanan berhasil dibatalkan.
            </div>
        <?php endif; ?>
        
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
                        
                        <!-- Tombol Konfirmasi Pesanan -->
                        <div class="order-actions" style="margin-top: 20px;">
                            <?php if (in_array($status_class, ['pending', 'processing', 'shipped'])): ?>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Apakah Anda yakin pesanan ini sudah diterima / selesai?');">
                                    <input type="hidden" name="order_id" value="<?php echo $trx['id']; ?>">
                                    <button type="submit" name="confirm_received" class="confirm-btn" style="background-color: #2ecc71; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; font-family: inherit;">
                                        <i class="fas fa-check-circle"></i> Konfirmasi Selesai
                                    </button>
                                </form>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pesanan ini?');">
                                    <input type="hidden" name="order_id" value="<?php echo $trx['id']; ?>">
                                    <button type="submit" name="cancel_order" class="confirm-btn" style="background-color: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-weight: bold; margin-left: 10px; font-family: inherit;">
                                        <i class="fas fa-times-circle"></i> Batalkan
                                    </button>
                                </form>
                            <?php elseif ($status_class === 'completed'): ?>
                                <span style="color: #388e3c; font-weight: 500; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-check-circle"></i> Diterima
                                </span>
                            <?php elseif ($status_class === 'cancelled'): ?>
                                <span style="color: #e74c3c; font-weight: 500; display: flex; align-items: center; gap: 6px;">
                                    <i class="fas fa-times-circle"></i> Dibatalkan
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
<?php $conn->close(); ?>