<?php
session_start();

// Jika sudah login, redirect ke home sesuai role
if (isset($_SESSION['user'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: ../admin/management_users.php");
    } elseif ($_SESSION['role'] === 'petugas') {
        header("Location: ../petugas/managament_product.php");
    } else {
        header("Location: home.php");
    }
    exit();
}

$error = "";

// Proses login saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Koneksi ke database (pastikan database 'ukom' sudah dibuat)
    $host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "prashop_db";
    
    $conn = new mysqli($host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Query untuk cek username dan password (PLAIN TEXT)
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Cek apakah user ditemukan
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Simpan data user ke session
        $_SESSION['user'] = $username;
        $_SESSION['role'] = $user['role'];
        
        // Redirect berdasarkan role
        if ($user['role'] === 'admin') {
            header("Location: ../admin/management_users.php");
        } elseif ($user['role'] === 'petugas') {
            header("Location: ../petugas/management_product.php");
        } else {
            header("Location: home.php");
        }
        exit();
    } else {
        $error = "Username atau password salah!";
    }
    
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Prashop</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=IM+FELL+French+Canon+SC&display=swap" rel="stylesheet">
</head>
<body>
    <header>
        <div class="logo">
            <img src="../asset/logo.png" alt="Prashop Logo" class="logo-img-auth">
            PRASHOP
        </div>
        <div class="auth-buttons">
            <a href="login.php" class="btn-nav">Sign In</a>
            <a href="register.php" class="btn-nav">Sign Up</a>
        </div>
    </header>

    <div class="main-wrapper">
        <div class="left-side">
            <img src="../asset/hero-auth.png" alt="Prashop Hero">
        </div>
        <div class="right-side">
            <div class="auth-box">
                <h1>Login</h1>
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="btn-action">Login</button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .error-message {
            background: #ffe6e6;
            color: #ff0000;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            font-family: 'IM FELL French Canon SC', serif;
        }
    </style>
</body>
</html>