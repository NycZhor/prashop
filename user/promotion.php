<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
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

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] += $quantity;
    } else {
        $stmt = $conn->prepare("SELECT id, product_name, price, image FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if ($product) {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['product_name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity
            ];
        }
    }
    header("Location: promotion.php?added=1");
    exit();
}

// ✅ Tampilkan produk dengan discount > 0
$sql = "SELECT * FROM products WHERE status = 'active' AND discount > 0 ORDER BY id DESC";
$result = $conn->query($sql);
$products = [];
if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prashop - Promotion</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=IM+FELL+French+Canon+SC&display=swap" rel="stylesheet">
    <style>
        .cart-badge {
            background: #ff6b9d;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            margin-left: 5px;
            display: inline-block;
            min-width: 20px;
            text-align: center;
        }
        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #e74c3c;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .original-price {
            text-decoration: line-through;
            color: #999;
            font-size: 14px;
        }
        .promo-price {
            color: #e74c3c;
            font-weight: 600;
            font-size: 16px;
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
        <a href="my_purchase.php">My Purchase</a>
        <a href="promotion.php" class="active">Promotion</a>
        <a href="cart.php">
            Cart
            <?php
            $cart_count = 0;
            if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $item) {
                    $cart_count += isset($item['quantity']) ? intval($item['quantity']) : 0;
                }
            }
            if ($cart_count > 0):
            ?>
                <span class="cart-badge" id="cartBadge"><?php echo $cart_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="buying.php">Buying</a>

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
    <h1 style="text-align: center; margin-bottom: 30px; color: #5a4a4a;">🔥 Promotion Products</h1>
    <?php if (empty($products)): ?>
        <p style="text-align: center; color: #999; padding: 40px;">No promotion products available</p>
        <a href="home.php" style="color: #3498db; text-decoration: none;">← Back to Home</a>
    <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <form method="POST" action="">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="quantity" value="1">
                    <div class="product-img-holder">
                        <?php
                        $imgPath = '../asset/' . $product['image'];
                        if (!file_exists(__DIR__ . '/../asset/' . $product['image'])) {
                            $imgPath = '../asset/nb530.png';
                        }
                        ?>
                        <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                        <span class="discount-badge">-<?php echo $product['discount']; ?>%</span>
                    </div>
                    <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                    <p class="product-price">
                        <span class="original-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span>
                        <br>
                        <span class="promo-price">Rp <?php echo number_format($product['price'] * (100 - $product['discount']) / 100, 0, ',', '.'); ?></span>
                    </p>
                    <p style="font-size: 12px; color: #66bb6a; margin: 20px; bottom: 15px;">
                        <i class="fas fa-box"></i> Stock: <?php echo $product['stock']; ?>
                    </p>
                    <div class="product-actions">
                        <button type="submit" name="add_to_cart" class="btn-add">
                            <i class="fas fa-cart-plus"></i> Add
                        </button>
                        <button type="button" class="btn-buy" onclick="buyNow(<?php echo $product['id']; ?>)">
                            <i class="fas fa-bolt"></i> Buy
                        </button>
                    </div>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php if (isset($_GET['added'])): ?>
<div id="cartToast" style="position: fixed; bottom: 30px; right: 30px; background: #4CAF50; color: white; padding: 15px 25px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 1000;">
    <i class="fas fa-check-circle"></i> Product added to cart!
</div>
<script>
    setTimeout(function() {
        const toast = document.getElementById('cartToast');
        if (toast) {
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 0.3s';
            setTimeout(() => toast.remove(), 300);
        }
    }, 3000);
</script>
<?php endif; ?>
<script>
    function buyNow(productId) {
        window.location.href = 'buying.php?product=' + productId;
    }
</script>
</body>
</html>
<?php $conn->close(); ?>