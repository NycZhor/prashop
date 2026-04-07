<?php
session_start();

// Proteksi: Jika belum login
if (!isset($_SESSION['user'])) {
    header("Location: ../user/login.php");
    exit();
}

// Proteksi: Hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/home.php");
    exit();
}

// Ambil notifikasi dari session
$error = isset($_SESSION['error']) ? $_SESSION['error'] : "";
$success = isset($_SESSION['success']) ? $_SESSION['success'] : "";

// Hapus notifikasi setelah diambil
unset($_SESSION['error']);
unset($_SESSION['success']);

// Proses hapus user
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    $host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "prashop_db";
    
    $conn = new mysqli($host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user_to_delete = $result->fetch_assoc();
        
        if ($user_to_delete['username'] === $_SESSION['user']) {
            $_SESSION['error'] = "Tidak bisa menghapus akun sendiri!";
        } else {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $delete_id);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = "User berhasil dihapus!";
            } else {
                $_SESSION['error'] = "Gagal menghapus user!";
            }
        }
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: management_users.php");
    exit();
}

// Proses tambah user
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password']; // PASSWORD PLAIN TEXT (TANPA HASH)
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    
    $host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "prashop_db";
    
    $conn = new mysqli($host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Cek username sudah ada atau belum
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $_SESSION['error'] = "Username sudah digunakan!";
    } else {
        // INSERT dengan password PLAIN TEXT (tanpa password_hash)
        $stmt = $conn->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $password, $email, $role);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "User berhasil ditambahkan!";
        } else {
            $_SESSION['error'] = "Gagal menambah user!";
        }
    }
    
    $stmt->close();
    $conn->close();
    
    header("Location: management_users.php");
    exit();
}

// Ambil semua user dari database
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "prashop_db";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$result = $conn->query("SELECT * FROM users ORDER BY id ASC");

$users = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prashop - Management Users</title>
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=IM+FELL+French+Canon+SC&display=swap" rel="stylesheet">
</head>
<body class="home-body">

    <!-- NAVBAR ADMIN -->
    <nav class="navbar">
        <div class="nav-logo">
            <img src="../asset/logo.png" alt="Prashop Logo" class="logo-img">
            <span class="logo-text">PRASHOP</span>
        </div>
        <div class="nav-links">
            <a href="management_users.php" class="active">
                MANAGEMENT<br>USER
            </a>
          <a href="management_product.php">
                MANAGEMENT<br>PRODUCT
            </a>
          <a href="management_transaction.php">   
                MANAGEMENT<br>TRANSACTION
            </a>
            <a href="backup_restore.php">
                BACK UP & RESTORE<br>DATA
            </a>
            <a href="generate_report.php">
                GENERATE<br>REPORT
            </a>
        </div>
        <div class="nav-auth">
            <a href="../user/logout.php" class="logout-link">
                <img src="../asset/logo2.png" alt="Logout" class="logout-icon"> LOGOUT
            </a>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="content-container">
        <h1 class="page-title">MANAGEMENT USERS</h1>
        <p class="page-subtitle">Manage all registered users and their roles.</p>
        
        <!-- Notifikasi -->
        <?php if ($error): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- Add User Button -->
        <div class="management-actions">
            <button class="btn-add-petugas" onclick="openModal()">Add User</button>
        </div>
        
        <!-- Users Table -->
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="role-badge <?php echo $user['role']; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['username'] !== $_SESSION['user']): ?>
                                <a href="management_users.php?delete_id=<?php echo $user['id']; ?>" 
                                   class="btn-delete" 
                                   onclick="return confirm('Yakin ingin menghapus user ini?')">
                                    Delete
                                </a>
                            <?php else: ?>
                                <span style="color: #999; font-size: 12px;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Add New User</h2>
            <form method="POST" action="">
                <!-- Username -->
                <div class="input-group-modal">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                
                <!-- Email -->
                <div class="input-group-modal">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                
                <!-- Password -->
                <div class="input-group-modal">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                
                <!-- Role dengan Select Wrapper -->
                <div class="input-group-modal">
                    <i class="fas fa-user-tag"></i>
                    <div class="select-wrapper">
                        <select name="role" required>
                            <option value="" disabled selected>Pilih Role</option>
                            <option value="user">User</option>
                            <option value="petugas">Petugas</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>
                
                <button type="submit" name="add_user" class="btn-action">Add User</button>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openModal() {
            const modal = document.getElementById('addUserModal');
            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            const modal = document.getElementById('addUserModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }
        
        // Event Listeners
        document.querySelector('.btn-add-petugas').addEventListener('click', openModal);
        document.querySelector('.close').addEventListener('click', closeModal);
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('addUserModal');
            if (event.target == modal) {
                closeModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
        
        // Auto hide notification setelah 3 detik
        document.addEventListener('DOMContentLoaded', function() {
            const errorMsg = document.querySelector('.error-message');
            const successMsg = document.querySelector('.success-message');
            
            if (errorMsg) {
                setTimeout(() => {
                    errorMsg.style.transition = 'opacity 0.5s';
                    errorMsg.style.opacity = '0';
                    setTimeout(() => errorMsg.remove(), 400);
                }, 1000);
            }
            
            if (successMsg) {
                setTimeout(() => {
                    successMsg.style.transition = 'opacity 0.5s';
                    successMsg.style.opacity = '0';
                    setTimeout(() => successMsg.remove(), 400);
                }, 1000);
            }
        });
    </script>

</body>
</html>