<?php
session_start();

// Jika sudah login, redirect ke home
if (isset($_SESSION['user'])) {
    header("Location: home.php");
    exit();
}

$error = "";
$success = "";

// Proses registrasi saat form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];
    
    // Koneksi ke database
    $host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "prashop_db";
    
    $conn = new mysqli($host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Cek apakah username sudah ada
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $error = "Username sudah digunakan!";
    } else {
        // Simpan user baru
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'user')");
        $stmt->bind_param("sss", $username, $password, $email);
        
        if ($stmt->execute()) {
            $success = "Registrasi berhasil! Silakan login.";
        } else {
            $error = "Gagal registrasi: " . $conn->error;
        }
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
    <title>Register - Prashop</title>
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
            <a href="register.php" class="btn-nav active">Sign Up</a>
        </div>
    </header>

    <div class="main-wrapper">
        <div class="left-side">
            <img src="../asset/hero-auth.png" alt="Prashop Hero">
        </div>
        <div class="right-side">
            <div class="auth-box">
                <h1>Register</h1>
                <?php if ($error): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-message"><?php echo $success; ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="input-group">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" placeholder="Username" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Email" required>
                    </div>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" placeholder="Password" required>
                    </div>
                    <button type="submit" class="btn-action">Register</button>
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
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
            font-family: 'IM FELL French Canon SC', serif;
        }
    </style>
</body>
</html>