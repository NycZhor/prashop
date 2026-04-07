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
// Handle Remove Item
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    if (isset($_SESSION['cart'][$remove_id])) {
        unset($_SESSION['cart'][$remove_id]);
    }
    header("Location: cart.php");
    exit();
}
// Handle Update Quantity
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_cart'])) {
    if (isset($_POST['quantities']) && is_array($_POST['quantities'])) {
        foreach ($_POST['quantities'] as $product_id => $quantity) {
            $qty = intval($quantity);
            if ($qty <= 0) {
                unset($_SESSION['cart'][$product_id]);
            } elseif (isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id]['quantity'] = $qty;
            }
        }
    }
    header("Location: cart.php");
    exit();
}
// Handle Clear Cart
if (isset($_GET['clear'])) {
    unset($_SESSION['cart']);
    header("Location: cart.php");
    exit();
}
// Get cart items with product data from database
$cart_items = [];
$total_amount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $product_id => $item) {
        // ✅ FIX: Ambil discount dari database
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if ($product) {
            $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
            $price = (int)$product['price'];
            $discount = (int)($product['discount'] ?? 0);
            
            // ✅ Hitung harga setelah discount
            $effective_price = $discount > 0 ? $price * (100 - $discount) / 100 : $price;
            $subtotal = $effective_price * $qty;
            $total_amount += $subtotal;
            
            $cart_items[] = [
                'id' => $product['id'],
                'name' => $product['product_name'],
                'price' => $effective_price,  // ✅ Simpan harga setelah discount
                'original_price' => $price,    // ✅ Simpan harga asli untuk referensi
                'discount' => $discount,       // ✅ Simpan discount untuk display
                'image' => $product['image'],
                'quantity' => $qty,
                'subtotal' => $subtotal,
                'stock' => $product['stock']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prashop - Cart</title>
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
        <a href="cart.php" class="active">Cart</a>
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
    <h1 class="cart-title">Cart</h1>
    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 80px 20px; color: #999;">
            <i class="fas fa-shopping-cart" style="font-size: 80px; margin-bottom: 20px; display: block; opacity: 0.3;"></i>
            <p style="font-size: 18px; margin: 0;">Your cart is empty</p>
            <a href="home.php" style="color: #3498db; text-decoration: none; margin-top: 20px; display: inline-block;">← Continue Shopping</a>
        </div>
    <?php else: ?>
    <form method="POST" action="" id="cartForm">
        <div class="cart-container">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): ?>
                    <tr class="cart-item" data-product-id="<?php echo $item['id']; ?>">
                        <td class="product-cell">
                            <?php
                            $imgPath = '../asset/' . $item['image'];
                            if (!file_exists(__DIR__ . '/../asset/' . $item['image'])) {
                                $imgPath = '../asset/nb530.png';
                            }
                            ?>
                            <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <span><?php echo htmlspecialchars($item['name']); ?></span>
                        </td>
                        <td class="price-cell" data-price="<?php echo $item['price']; ?>">
                            <!-- ✅ Tampilkan harga discount jika ada -->
                            <?php if (!empty($item['discount']) && $item['discount'] > 0): ?>
                                <span style="text-decoration: line-through; color: #999; font-size: 12px;">
                                    Rp <?php echo number_format($item['original_price'], 0, ',', '.'); ?>
                                </span><br>
                                <strong>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></strong>
                            <?php else: ?>
                                Rp <?php echo number_format($item['price'], 0, ',', '.'); ?>
                            <?php endif; ?>
                        </td>
                        <td class="quantity-cell">
                            <div class="quantity-controls">
                                <button type="button" class="qty-btn minus">-</button>
                                <input type="hidden" name="quantities[<?php echo $item['id']; ?>]"
                                       value="<?php echo $item['quantity']; ?>"
                                       class="qty-input"
                                       data-product-id="<?php echo $item['id']; ?>">
                                <span class="qty-value"><?php echo $item['quantity']; ?></span>
                                <button type="button" class="qty-btn plus">+</button>
                            </div>
                            <small style="color: #999; display: block; margin-top: 5px;">
                                Stock: <?php echo $item['stock']; ?>
                            </small>
                        </td>
                        <td class="total-cell" data-subtotal="<?php echo $item['subtotal']; ?>">
                            Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?>
                        </td>
                        <td>
                            <a href="?remove=<?php echo $item['id']; ?>"
                               class="btn-delete"
                               onclick="return confirm('Remove this item from cart?')"
                               style="padding: 6px 12px; font-size: 11px;">
                                <i class="fas fa-trash"></i> Remove
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="cart-summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="subtotal">Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping:</span>
                <span>Free</span>
            </div>
            <div class="summary-row total-row">
                <span>Total:</span>
                <span id="total">Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></span>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button type="submit" name="update_cart" class="btn-add-petugas" style="flex: 1; padding: 12px;">
                    <i class="fas fa-sync"></i> Update Cart
                </button>
                <a href="?clear=1" class="btn-delete" style="flex: 1; text-align: center; padding: 12px;"
                   onclick="return confirm('Clear all items from cart?')">
                    <i class="fas fa-trash-alt"></i> Clear All
                </a>
            </div>
            <button type="button" class="btn-checkout" onclick="proceedToCheckout()">
                <i class="fas fa-credit-card"></i> Proceed to Checkout
            </button>
        </div>
    </form>
    <?php endif; ?>
</main>
<script>
function formatRupiah(angka) {
    return 'Rp ' + angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}
function updateItemTotal(row) {
    const priceCell = row.querySelector('.price-cell');
    const qtyInput = row.querySelector('.qty-input');
    const qtyValue = row.querySelector('.qty-value');
    const totalCell = row.querySelector('.total-cell');
    // ✅ Ambil harga dari data-price (sudah discounted)
    const price = parseInt(priceCell.getAttribute('data-price'));
    const qty = parseInt(qtyInput.value);
    const total = price * qty;
    qtyValue.textContent = qty;
    totalCell.textContent = formatRupiah(total);
    totalCell.setAttribute('data-subtotal', total);
}
function updateCartSummary() {
    const totalCells = document.querySelectorAll('.total-cell');
    let subtotal = 0;
    totalCells.forEach(cell => {
        subtotal += parseInt(cell.getAttribute('data-subtotal')) || 0;
    });
    document.getElementById('subtotal').textContent = formatRupiah(subtotal);
    document.getElementById('total').textContent = formatRupiah(subtotal);
    document.querySelectorAll('.qty-input').forEach(input => {
        const row = input.closest('.cart-item');
        const qtyValue = row.querySelector('.qty-value');
        input.value = qtyValue.textContent;
    });
}
document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const row = this.closest('.cart-item');
        const qtyInput = row.querySelector('.qty-input');
        const qtyValue = row.querySelector('.qty-value');
        const stockText = row.querySelector('small');
        const stock = parseInt(stockText.textContent.replace('Stock: ', '')) || 999;
        let currentValue = parseInt(qtyInput.value);
        if (this.classList.contains('minus')) {
            currentValue = Math.max(1, currentValue - 1);
        } else if (this.classList.contains('plus')) {
            if (currentValue < stock) {
                currentValue += 1;
            } else {
                alert('Stock limited to ' + stock);
                return;
            }
        }
        qtyInput.value = currentValue;
        updateItemTotal(row);
        updateCartSummary();
    });
});
function proceedToCheckout() {
    document.querySelectorAll('.qty-input').forEach(input => {
        const row = input.closest('.cart-item');
        const qtyValue = row.querySelector('.qty-value');
        input.value = qtyValue.textContent;
    });
    const form = document.getElementById('cartForm');
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'update_cart';
    input.value = '1';
    form.appendChild(input);
    form.action = 'buying.php';
    form.submit();
}
document.addEventListener('DOMContentLoaded', function() {
    updateCartSummary();
});
</script>
</body>
</html>
<?php $conn->close(); ?>