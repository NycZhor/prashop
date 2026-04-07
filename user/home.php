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

// Handle AJAX cart count
if (isset($_POST['ajax_count'])) {
    $count = 0;
    if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += isset($item['quantity']) ? intval($item['quantity']) : 0;
        }
    }
    echo $count;
    exit();
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
        $stmt = $conn->prepare("SELECT id, product_name, price, image, stock FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        if ($product) {
            $_SESSION['cart'][$product_id] = [
                'id' => $product['id'],
                'name' => $product['product_name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'quantity' => $quantity,
                'stock' => $product['stock']
            ];
        }
    }
    header("Location: home.php?added=1");
    exit();
}

// ✅ Ambil produk promotion untuk banner (discount > 0)
$banner_sql = "SELECT * FROM products WHERE status = 'active' AND discount > 0 ORDER BY id DESC LIMIT 5";
$banner_result = $conn->query($banner_sql);
$banner_products = [];
if ($banner_result && $banner_result->num_rows > 0) {
    while($row = $banner_result->fetch_assoc()) {
        $banner_products[] = $row;
    }
}

// ✅ Ambil semua produk untuk grid
// ✅ Ambil produk untuk grid (kecuali yang sudah di promotion/discount > 0)
$sql = "SELECT * FROM products WHERE status = 'active' AND (discount = 0 OR discount IS NULL) ORDER BY id DESC";
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
    <title>Prashop - Home</title>
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
        <a href="home.php" class="active">Home</a>
        <a href="my_purchase.php">My Purchase</a>
        <a href="promotion.php">Promotion</a>
        <a href="cart.php">Cart
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
    <!-- ✅ Hero Banner Slider - Produk Promotion -->
    <?php if (!empty($banner_products)): ?>
    <section class="hero-banner">
        <div class="banner-slider">
            <?php 
            $index = 0;
            foreach ($banner_products as $bp): 
                $imgPath = '../asset/' . $bp['image'];
                if (!file_exists(__DIR__ . '/../asset/' . $bp['image'])) {
                    $imgPath = '../asset/nb530.png';
                }
                $discount = (int)($bp['discount'] ?? 0);
                $original_price = $bp['price'];
                $discounted_price = $original_price * (100 - $discount) / 100;
            ?>
            <div class="banner-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                <div class="banner-content">
                    <div class="banner-text">
                        <span class="discount-badge-banner">
                            <i class="fas fa-tag"></i> DISCOUNT <?php echo $discount; ?>%
                        </span>
                        <p class="quote">"Special promotion! Get this amazing product with exclusive discount."</p>
                        <p class="product-name-banner"><?php echo htmlspecialchars($bp['product_name']); ?></p>
                        <p class="product-price-banner">Rp <?php echo number_format($discounted_price, 0, ',', '.'); ?></p>
                        <span class="original-price-banner">Rp <?php echo number_format($original_price, 0, ',', '.'); ?></span>
                        <a href="buying.php?product=<?php echo $bp['id']; ?>" class="btn-banner">
                            <i class="fas fa-shopping-bag"></i> Shop Now
                        </a>
                    </div>
                    <div class="banner-image">
                        <img src="<?php echo $imgPath; ?>" alt="<?php echo htmlspecialchars($bp['product_name']); ?>">
                    </div>
                </div>
            </div>
            <?php $index++; endforeach; ?>
        </div>
        
        <!-- Banner Navigation Dots -->
        <?php if (count($banner_products) > 1): ?>
        <div class="banner-dots">
            <?php for ($i = 0; $i < count($banner_products); $i++): ?>
            <button class="banner-dot <?php echo $i === 0 ? 'active' : ''; ?>" data-slide="<?php echo $i; ?>"></button>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php else: ?>
    <!-- Fallback banner jika tidak ada produk promotion -->
    <section class="hero-banner">
        <div class="banner-content">
            <div class="banner-text">
                <p class="quote">"Step into the perfect blend of nostalgia and modern comfort with the New Balance 530."</p>
            </div>
            <div class="banner-image">
                <img src="../asset/nb530.png" alt="Featured New Balance 530">
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Product Grid -->
    <section class="product-section">
        <h2 class="section-title">Our Products</h2>
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <p>No products available yet</p>
            </div>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <?php
                    $stock = (int)$product['stock'];
                    if ($stock == 0) {
                        $stock_class = 'stock-out';
                        $stock_text = 'OUT OF STOCK';
                    } elseif ($stock <= 5) {
                        $stock_class = 'stock-low';
                        $stock_text = 'Low: ' . $stock;
                    } else {
                        $stock_class = 'stock-available';
                        $stock_text = 'Stock: ' . $stock;
                    }
                    $is_out = ($stock == 0);
                    ?>
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
                            <?php if (!empty($product['discount']) && $product['discount'] > 0): ?>
                                <span class="discount-badge">-<?php echo $product['discount']; ?>%</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                        <p class="product-price">
                            <?php if (!empty($product['discount']) && $product['discount'] > 0): ?>
                                <span class="original-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></span><br>
                                <span class="promo-price">Rp <?php echo number_format($product['price'] * (100 - $product['discount']) / 100, 0, ',', '.'); ?></span>
                            <?php else: ?>
                                Rp <?php echo number_format($product['price'], 0, ',', '.'); ?>
                            <?php endif; ?>
                        </p>
                        <p class="product-stock">
                            <i class="fas fa-box"></i> Stock: <?php echo $product['stock']; ?>
                        </p>
                        <div class="product-actions">
                            <button type="submit" name="add_to_cart" class="btn-add">
                                <i class="fas fa-cart-plus"></i> Add
                            </button>
                            <button type="button" class="btn-buy <?php echo $is_out ? 'btn-disabled' : ''; ?>" onclick="<?php echo $is_out ? 'alert(\'Product is out of stock!\');return false;' : 'buyNow('.$product['id'].')'; ?>">
                                <i class="fas fa-bolt"></i> Buy
                            </button>
                        </div>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php if (isset($_GET['added'])): ?>
<div class="cart-toast" id="cartToast">
    <i class="fas fa-check-circle"></i>
    <span>Product added to cart!</span>
</div>
<script>
    setTimeout(function() {
        const toast = document.getElementById('cartToast');
        if (toast) {
            toast.style.animation = 'slideOut 0.3s ease forwards';
            setTimeout(() => {
                toast.remove();
                const url = new URL(window.location.href);
                url.searchParams.delete('added');
                window.history.replaceState({}, document.title, url.pathname);
            }, 300);
        }
    }, 3000);
</script>
<?php endif; ?>

<script>
// ✅ Banner Slider Script
let currentSlide = 0;
const slides = document.querySelectorAll('.banner-slide');
const dots = document.querySelectorAll('.banner-dot');
const totalSlides = slides.length;
let autoSlideInterval;

function showSlide(index) {
    // Hide all slides
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    // Show current slide
    slides[index].classList.add('active');
    if (dots[index]) dots[index].classList.add('active');
}

function changeSlide(direction) {
    if (totalSlides === 0) return;
    
    currentSlide = (currentSlide + direction + totalSlides) % totalSlides;
    showSlide(currentSlide);
    resetAutoSlide();
}

function goToSlide(index) {
    if (totalSlides === 0) return;
    
    currentSlide = index;
    showSlide(currentSlide);
    resetAutoSlide();
}

function resetAutoSlide() {
    clearInterval(autoSlideInterval);
    if (totalSlides > 1) {
        autoSlideInterval = setInterval(() => {
            changeSlide(1);
        }, 5000);
    }
}

// Initialize dots click events
dots.forEach((dot, index) => {
    dot.addEventListener('click', () => {
        goToSlide(index);
    });
});

// Start auto slide
resetAutoSlide();

// Buy Now function
function buyNow(productId) {
    window.location.href = 'buying.php?product=' + productId;
}

// Update cart badge via AJAX
function updateCartBadge() {
    fetch('home.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'ajax_count=1'
    })
    .then(response => response.text())
    .then(data => {
        const count = parseInt(data) || 0;
        let badge = document.getElementById('cartBadge');
        if (count > 0) {
            if (!badge) {
                const cartLink = document.querySelector('a[href="cart.php"]');
                if (cartLink) {
                    badge = document.createElement('span');
                    badge.id = 'cartBadge';
                    badge.className = 'cart-badge';
                    cartLink.appendChild(badge);
                }
            }
            if (badge) badge.textContent = count;
        } else if (badge) {
            badge.remove();
        }
    });
}

window.addEventListener('focus', () => setTimeout(updateCartBadge, 500));
document.addEventListener('DOMContentLoaded', () => {
    updateCartBadge();
});
</script>

</body>
</html>
<?php $conn->close(); ?>