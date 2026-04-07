<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: ../admin/management_users.php");
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

// ✅ Ambil data user dari database
$username = $_SESSION['user'];
$user_data = null;
$stmt = $conn->prepare("SELECT username, phone, email, address FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
}
$stmt->close();

// ✅ Handle Direct Buy dari Home/Promotion
if (isset($_GET['product']) && is_numeric($_GET['product'])) {
    $product_id = intval($_GET['product']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    if ($product) {
        $price = (int)$product['price'];
        $discount = (int)($product['discount'] ?? 0);
        $effective_price = $discount > 0 ? $price * (100 - $discount) / 100 : $price;
        
        $_SESSION['cart'] = [
            $product_id => [
                'id' => $product['id'],
                'name' => $product['product_name'],
                'price' => $effective_price,
                'image' => $product['image'],
                'quantity' => 1
            ]
        ];
        header("Location: buying.php");
        exit();
    }
}
// ✅ Hitung total dari session cart + apply discount
$cart_items = [];
$total_amount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if ($product) {
            $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
            $price = (int)$product['price'];
            $discount = (int)($product['discount'] ?? 0);
            
            $effective_price = $discount > 0 ? $price * (100 - $discount) / 100 : $price;
            $subtotal = $effective_price * $qty;
            $total_amount += $subtotal;
            
            $cart_items[] = [
                'id' => $product['id'],
                'name' => $product['product_name'],
                'price' => $effective_price,
                'quantity' => $qty,
                'subtotal' => $subtotal
            ];
        }
    }
}
$totalFormatted = number_format($total_amount, 0, ',', '.');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prashop - Buying</title>
    <link rel="stylesheet" href="../style.css">
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
        <a href="my_purchase.php">My Purchase</a>
        <a href="promotion.php">Promotion</a>
        <a href="cart.php">Cart</a>
        <a href="buying.php" class="active">Buying</a>
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
    <h1 class="buying-title">BUYING</h1>
    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 80px 20px; color: #999;">
            <p style="font-size: 18px;">No items to checkout</p>
            <a href="home.php" style="color: #3498db; text-decoration: none;">← Back to Home</a>
        </div>
    <?php else: ?>
    <div class="shipping-section">
        <h2 class="section-title">Shipping Information</h2>
        <form class="shipping-form" id="buyingForm" method="POST" action="process_order.php" enctype="multipart/form-data">
            <?php foreach ($cart_items as $index => $item): ?>
                <input type="hidden" name="cart[<?= $index ?>][id]" value="<?= htmlspecialchars($item['id']) ?>">
                <input type="hidden" name="cart[<?= $index ?>][name]" value="<?= htmlspecialchars($item['name']) ?>">
                <input type="hidden" name="cart[<?= $index ?>][price]" value="<?= $item['price'] ?>">
                <input type="hidden" name="cart[<?= $index ?>][quantity]" value="<?= $item['quantity'] ?>">
            <?php endforeach; ?>
            <input type="hidden" name="total_amount" value="<?= $total_amount ?>">
            <input type="hidden" name="payment_method" id="paymentMethod" value="cod">
            <div class="form-group">
                <label>Name</label>
                <input type="text" name="name" placeholder="Enter your name" required value="<?= htmlspecialchars($user_data['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <div class="phone-input">
                    <span class="country-code">+62</span>
                    <input type="tel" name="phone" placeholder="Phone number" required value="<?= htmlspecialchars($user_data['phone'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email address" required value="<?= htmlspecialchars($user_data['email'] ?? '') ?>">
            </div>
            <div class="payment-section">
                <div class="payment-methods">
                    <button type="button" class="payment-btn" data-method="transfer">TRANSFER</button>
                    <button type="button" class="payment-btn active" data-method="cod">COD</button>
                </div>
                <div class="payment-info" id="transferInfo" style="display: none;">
                    <p class="transfer-text">TRANSFER HERE : <span class="phone-number">087864200621</span> ( <span class="payment-app">VIA DANA</span> )</p>
                    <div class="upload-section">
                        <input type="text" name="address" placeholder="Input address" class="address-input" value="<?= htmlspecialchars($user_data['address'] ?? '') ?>">
                        <label class="file-upload">
                            <input type="file" name="proof" accept="image/*" style="display: none;">
                            <span>Choose File</span>
                        </label>
                    </div>
                </div>
                <div class="payment-info cod-info" id="codInfo">
                    <p class="cod-text">Bayar di tempat saat pesanan tiba</p>
                </div>
            </div>
            <div class="order-summary">
                <div class="total-display">
                    <span>TOTAL :</span>
                    <span class="total-price" id="totalPrice" data-total="<?= $total_amount ?>">RP. <?= $totalFormatted ?></span>
                </div>
                <button type="submit" class="btn-order">ORDER</button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</main>
<script>
const paymentBtns = document.querySelectorAll('.payment-btn');
const transferInfo = document.getElementById('transferInfo');
const codInfo = document.getElementById('codInfo');
const paymentMethodInput = document.getElementById('paymentMethod');
const proofInput = document.querySelector('input[name="proof"]');
const addressInput = document.querySelector('input[name="address"]');
paymentBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        paymentBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        if (this.dataset.method === 'transfer') {
            transferInfo.style.display = 'block';
            codInfo.style.display = 'none';
            paymentMethodInput.value = 'transfer';
            if(proofInput) proofInput.required = true;
            if(addressInput) addressInput.required = true;
        } else {
            transferInfo.style.display = 'none';
            codInfo.style.display = 'block';
            paymentMethodInput.value = 'cod';
            if(proofInput) proofInput.required = false;
            if(addressInput) addressInput.required = false;
        }
    });
});
const fileInput = document.querySelector('input[type="file"]');
const fileLabel = document.querySelector('.file-upload span');
if (fileInput && fileLabel) {
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            fileLabel.textContent = this.files[0].name;
        } else {
            fileLabel.textContent = 'Choose File';
        }
    });
}
document.addEventListener('DOMContentLoaded', function() {
    const totalPriceEl = document.getElementById('totalPrice');
    const totalFromPHP = totalPriceEl.dataset.total;
    if (totalFromPHP && !isNaN(totalFromPHP)) {
        const formatted = 'RP. ' + parseInt(totalFromPHP).toLocaleString('id-ID');
        totalPriceEl.textContent = formatted;
    }
});
</script>
</body>
</html>
<?php $conn->close(); ?>