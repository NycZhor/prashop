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

// Koneksi database
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "prashop_db";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Setup backup directory
$backupDir = __DIR__ . "/../backups/";
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$success = "";
$error = "";

// Handle Backup Database
if (isset($_GET['action']) && $_GET['action'] === 'backup') {
    try {
        $filename = "BACKUP_" . date('Y-m-d_H-i-s') . ".sql";
        $filepath = $backupDir . $filename;
        
        $command = "mysqldump --user={$db_user} --password={$db_pass} --host={$host} {$db_name} > {$filepath}";
        
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            $success = "Backup berhasil! File: " . $filename;
        } else {
            $tables = [];
            $result = $conn->query("SHOW TABLES");
            while ($row = $result->fetch_row()) {
                $tables[] = $row[0];
            }
            
            $sql = "-- Database Backup\n";
            $sql .= "-- Database: {$db_name}\n";
            $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
            
            foreach ($tables as $table) {
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $result = $conn->query("SHOW CREATE TABLE `{$table}`");
                $create = $result->fetch_row();
                $sql .= $create[1] . ";\n\n";
                
                $result = $conn->query("SELECT * FROM `{$table}`");
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $values = array_map(function($val) use ($conn) {
                            return $val === null ? 'NULL' : "'" . $conn->real_escape_string($val) . "'";
                        }, array_values($row));
                        $sql .= "INSERT INTO `{$table}` VALUES (" . implode(", ", $values) . ");\n";
                    }
                }
                $sql .= "\n";
            }
            
            $sql .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
            file_put_contents($filepath, $sql);
            $success = "Backup berhasil! File: " . $filename;
        }
    } catch (Exception $e) {
        $error = "Backup gagal: " . $e->getMessage();
    }
}

// Handle Restore Database
if (isset($_POST['restore'])) {
    if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['backup_file']['tmp_name'];
        $filename = $_FILES['backup_file']['name'];
        
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (strtolower($ext) === 'sql') {
            $sql = file_get_contents($tmp_name);
            
            // ✅ FIX: Nonaktifkan foreign key checks
            $conn->query("SET FOREIGN_KEY_CHECKS = 0");
            
            $conn->multi_query($sql);
            
            while ($conn->more_results()) {
                $conn->next_result();
            }
            
            $conn->query("SET FOREIGN_KEY_CHECKS = 1");
            
            $success = "Restore berhasil! Database telah dikembalikan.";
        } else {
            $error = "File harus berformat .sql";
        }
    } else {
        $error = "Pilih file backup untuk di-restore";
    }
}

// Handle Download Backup
if (isset($_GET['download'])) {
    $filename = basename($_GET['download']);
    $filepath = $backupDir . $filename;
    
    if (file_exists($filepath)) {
        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit();
    }
}

// Handle Delete Backup
if (isset($_GET['delete'])) {
    $filename = basename($_GET['delete']);
    $filepath = $backupDir . $filename;
    
    if (file_exists($filepath)) {
        unlink($filepath);
        $success = "Backup dihapus: " . $filename;
    }
}

// Get Backup History
$backup_files = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $filepath = $backupDir . $file;
            $backup_files[] = [
                'name' => $file,
                'date' => date('d F Y, H:i', filemtime($filepath)),
                'size' => filesize($filepath)
            ];
        }
    }
    usort($backup_files, function($a, $b) {
        return strcmp($b['date'], $a['date']);
    });
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prashop - Backup/Restore Data</title>
    
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
            <a href="management_users.php">
                MANAGEMENT<br>USER
            </a>
            <a href="management_product.php">
                MANAGEMENT<br>PRODUCT
            </a>
            <a href="management_transaction.php">
                MANAGEMENT<br>TRANSACTION
            </a>
            <a href="backup_restore.php" class="active">
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
    <main class="backup-restore-container">
        <div class="page-header">
            <h1>BACKUP/ RESTORE DATA</h1>
        </div>
        
        <!-- Notifikasi -->
        <?php if ($error): ?>
            <div class="alert alert-error" id="errorMessage">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" id="successMessage">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="backup-restore-grid">
            <!-- Backup Section -->
            <div class="backup-section">
                <h2 class="section-title-backup">BACKUP DATA</h2>
                <p class="section-description">
                    Click the button below to download a full backup of the database.
                </p>
                <a href="?action=backup" class="btn-backup" onclick="return confirm('Create database backup?')">
                    <i class="fas fa-cloud-download-alt"></i>
                    DOWNLOAD BACKUP
                </a>
            </div>
            
            <!-- Restore Section -->
            <div class="restore-section">
                <h2 class="section-title-backup">RESTORE DATA</h2>
                <p class="section-description">
                    Select a backup file to restore the database to its previous state.
                </p>
                <form method="POST" enctype="multipart/form-data">
                    <div class="file-input-wrapper">
                        <input type="file" name="backup_file" accept=".sql" required>
                    </div>
                    <button type="submit" name="restore" class="btn-restore" onclick="return confirm('Restore database? This will overwrite current data!')">
                        <i class="fas fa-sync-alt"></i>
                        RESTORE BACKUP
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Backup History -->
        <div class="backup-history">
            <h2 class="history-title">Backup History</h2>
            
            <?php if (empty($backup_files)): ?>
                <div class="empty-backup">
                    <i class="fas fa-folder-open"></i>
                    <p>No backup files found</p>
                </div>
            <?php else: ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>FILE NAME</th>
                            <th>DATE</th>
                            <th>SIZE</th>
                            <th style="text-align: center;">ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        foreach ($backup_files as $file): 
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($file['name']); ?></td>
                            <td><?php echo $file['date']; ?></td>
                            <td><?php echo number_format($file['size'] / 1024, 2); ?> KB</td>
                            <td style="text-align: center;">
                                <a href="?download=<?php echo urlencode($file['name']); ?>" class="btn-download">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <a href="?delete=<?php echo urlencode($file['name']); ?>" 
                                   class="btn-delete-action" 
                                   onclick="return confirm('Delete this backup file?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <!-- ✅ Script Auto-Hide Notification -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const errorMsg = document.getElementById('errorMessage');
            const successMsg = document.getElementById('successMessage');
            
            // Auto-hide error message setelah 3 detik
            if (errorMsg) {
                setTimeout(() => {
                    errorMsg.style.transition = 'opacity 0.5s';
                    errorMsg.style.opacity = '0';
                    setTimeout(() => {
                        errorMsg.remove();
                        // Hapus parameter URL tanpa reload
                        const url = new URL(window.location.href);
                        url.searchParams.delete('error');
                        window.history.replaceState({}, document.title, url.pathname);
                    }, 500);
                }, 3000);
            }
            
            // Auto-hide success message setelah 3 detik
            if (successMsg) {
                setTimeout(() => {
                    successMsg.style.transition = 'opacity 0.5s';
                    successMsg.style.opacity = '0';
                    setTimeout(() => {
                        successMsg.remove();
                        // Hapus parameter URL tanpa reload
                        const url = new URL(window.location.href);
                        url.searchParams.delete('success');
                        window.history.replaceState({}, document.title, url.pathname);
                    }, 500);
                }, 3000);
            }
        });
    </script>

</body>
</html>
<?php
$conn->close();
?>