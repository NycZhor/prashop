<?php
session_start();
if (!isset($_SESSION['user'])) { header("Location: login.php"); exit(); }

include 'koneksi.php';
$username = $_SESSION['user'];
$success = $error = "";
$is_editing = isset($_GET['edit']) && $_GET['edit'] == '1';

$stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$data_user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_email = $_POST['email'];
    $new_phone = $_POST['phone'];
    $new_address = $_POST['address'];
    
    $update = $conn->prepare("UPDATE users SET email = ?, phone = ?, address = ? WHERE username = ?");
    $update->bind_param("ssss", $new_email, $new_phone, $new_address, $username);
    
    if ($update->execute()) {
        $success = "Profile berhasil diperbarui!";
        $stmt->execute();
        $data_user = $stmt->get_result()->fetch_assoc();
        $is_editing = false;
    } else {
        $error = "Gagal memperbarui data.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prashop - My Profile</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=IM+FELL+French+Canon+SC&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<nav class="navbar">
    <div class="nav-logo">
        <img src="../asset/logo.png" alt="Prashop Logo" class="logo-img">
        <span class="logo-text">PRASHOP</span>
    </div>
    <div class="nav-links">
        <a href="home.php">Home</a>
        <a href="my_purchase.php">My Purchase</a>
        <a href="promotion.php">Promotion</a>
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
        <a href="logout.php" class="logout-link">
            <img src="../asset/logo2.png" alt="Logout" class="logout-icon"> LOGOUT
        </a>
    </div>
</nav>

    <main class="profile-container">
        
        <!-- BANNER DATA USER -->
        <div class="profile-banner">
            <div class="profile-banner-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="profile-banner-content">
                <h1><?php echo htmlspecialchars($data_user['username']); ?></h1>
                <div class="profile-banner-details">
                    <div class="banner-detail-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo htmlspecialchars($data_user['email']); ?></span>
                    </div>
                    <div class="banner-detail-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo htmlspecialchars($data_user['phone'] ?? 'Belum diatur'); ?></span>
                    </div>
                    <div class="banner-detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo htmlspecialchars($data_user['address'] ?? 'Belum diatur'); ?></span>
                    </div>
                </div>
                <div class="profile-banner-actions">
                    <?php if ($is_editing): ?>
                        <a href="profile.php" class="btn-cancel"><i class="fas fa-times"></i> Batal</a>
                    <?php else: ?>
                        <a href="?edit=1" class="btn-edit-profile"><i class="fas fa-edit"></i> Edit Profile</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>

        <!-- FORM EDIT (Muncul hanya saat tombol Edit diklik) -->
        <?php if ($is_editing): ?>
        <div class="profile-edit-form">
            <h2><i class="fas fa-user-edit"></i> Edit Informasi Profile</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Username</label>
                    <input type="text" value="<?php echo htmlspecialchars($data_user['username']); ?>" readonly>
                    <small>Username tidak dapat diubah</small>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($data_user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($data_user['phone'] ?? ''); ?>" placeholder="08123456789">
                    </div>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Alamat Lengkap</label>
                    <textarea name="address" rows="3" placeholder="Masukkan alamat lengkap Anda..."><?php echo htmlspecialchars($data_user['address'] ?? ''); ?></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" name="update_profile" class="btn-save"><i class="fas fa-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

    </main>
</body>
</html>