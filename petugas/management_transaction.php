<?php
session_start();

// Proteksi: Jika belum login
if (!isset($_SESSION['user'])) {
    header("Location: ../user/login.php");
    exit();
}

// Proteksi: Hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'petugas') {
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
$conn->set_charset("utf8mb4"); // ✅ Tambahkan ini untuk encoding yang konsisten

// Inisialisasi variabel
$success = "";
$error = "";
$transactions = [];
$status_counts = ['all' => 0, 'pending' => 0, 'processing' => 0, 'shipped' => 0, 'completed' => 0, 'cancelled' => 0];

// Update status transaction
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $transaction_id = intval($_POST['transaction_id']);
    $new_status = trim($_POST['status']); // ✅ Trim spasi
    
    // Validasi status yang diizinkan
    $allowed_status = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
    if (in_array($new_status, $allowed_status)) {
        $stmt = $conn->prepare("UPDATE transactions SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $transaction_id);
            if ($stmt->execute()) {
                $success = "Status transaksi berhasil diupdate!";
            } else {
                $error = "Gagal update status: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Prepare statement failed: " . $conn->error;
        }
    } else {
        $error = "Status tidak valid!";
    }
}

// Filter status
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Query transaksi
$sql = "SELECT t.*, u.username as user_name 
        FROM transactions t 
        LEFT JOIN users u ON t.user_id = u.id";

if (!empty($status_filter)) {
    $sql .= " WHERE t.status = ?";
}
$sql .= " ORDER BY t.created_at DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($status_filter)) {
        $stmt->bind_param("s", $status_filter);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
    }
    $stmt->close();
}

// Get status counts
$count_result = $conn->query("SELECT status, COUNT(*) as count FROM transactions GROUP BY status");
if ($count_result) {
    while($row = $count_result->fetch_assoc()) {
        if (isset($status_counts[$row['status']])) {
            $status_counts[$row['status']] = $row['count'];
            $status_counts['all'] += $row['count'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prashop - Management Transactions</title>
    
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
            <a href="management_product.php">
                MANAGEMENT<br>PRODUCT
            </a>
            <a href="management_transaction.php" class="active">
                MANAGEMENT<br>TRANSACTION
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
        <h1 class="page-title">MANAGEMENT TRANSACTIONS</h1>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-group">
                <label>Status</label>
                <select id="statusFilter" onchange="filterTransactions()">
                    <option value="" <?php echo $status_filter === '' ? 'selected' : ''; ?>>
                        All Status (<?php echo $status_counts['all']; ?>)
                    </option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>
                        Pending (<?php echo $status_counts['pending']; ?>)
                    </option>
                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>
                        Processing (<?php echo $status_counts['processing']; ?>)
                    </option>
                    <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>
                        Shipped (<?php echo $status_counts['shipped']; ?>)
                    </option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>
                        Completed (<?php echo $status_counts['completed']; ?>)
                    </option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>
                        Cancelled (<?php echo $status_counts['cancelled']; ?>)
                    </option>
                </select>
            </div>
        </div>
        
        <!-- Notifikasi -->
        <?php if (!empty($error)): ?>
            <div class="error-message" id="errorMessage"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="success-message" id="successMessage"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- Transactions Table -->
        <div class="transactions-table-container">
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>ORDER NUMBER</th>
                        <th>CUSTOMER</th>
                        <th>DATE</th>
                        <th>TOTAL</th>
                        <th>PAYMENT</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 80px 20px;">
                            <i class="fas fa-receipt" style="font-size: 80px; margin-bottom: 20px; display: block; opacity: 0.3;"></i>
                            <p style="font-size: 18px; margin: 0; color: #999;">No transactions found</p>
                            <p style="font-size: 14px; margin: 10px 0 0; color: #bbb;">Transactions will appear here when customers make purchases</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($transactions as $trx): ?>
                    <tr>
                        <td><strong>#<?php echo str_pad($trx['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                        <td>
                            <?php echo htmlspecialchars($trx['customer_name']); ?>
                            <br><small style="color: #999; font-size: 12px;">
                                <?php echo htmlspecialchars($trx['email'] ?? '-'); ?>
                            </small>
                        </td>
                        <td><?php echo date('d M Y, H:i', strtotime($trx['created_at'])); ?></td>
                        <td><strong>Rp <?php echo number_format($trx['total_amount'], 0, ',', '.'); ?></strong></td>
                        <td>
                            <span class="payment-badge <?php echo htmlspecialchars($trx['payment_method']); ?>">
                                <?php echo strtoupper(htmlspecialchars($trx['payment_method'])); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $status = strtolower(trim($trx['status'] ?? 'pending'));
                            $valid = ['pending','processing','shipped','completed','cancelled'];
                            $s_class = in_array($status, $valid) ? $status : 'pending';
                            $s_text = strtoupper($s_class);
                            ?>
                            <span class="status-badge-trx <?php echo $s_class; ?>">
                                <?php echo $s_text; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn-action-view" onclick='viewTransaction(<?php echo htmlspecialchars(json_encode($trx, JSON_UNESCAPED_SLASHES)); ?>)'>
                                <i class="fas fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- View Transaction Detail Modal -->
    <div id="viewTransactionModal" class="modal">
        <div class="modal-content modal-large">
            <span class="close" onclick="closeViewModal()">&times;</span>
            <h2>Transaction Details</h2>
            
            <div id="transactionDetail"></div>
            
            <!-- Update Status Form -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #f0e6e6;">
                <h3 style="margin-bottom: 15px; color: #5a4a4a;">Update Status</h3>
                <form method="POST" action="">
                    <input type="hidden" name="transaction_id" id="update_transaction_id">
                    <div class="input-group-modal">
                        <i class="fas fa-sync-alt"></i>
                        <div class="select-wrapper" style="flex: 1;">
                            <select name="status" id="update_status" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="update_status" class="btn-action" style="margin-top: 15px;">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Image Preview Container -->
    <div id="imagePreview" style="display:none; position:fixed; z-index:9999; padding-top:50px; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.9);">
        <span id="previewClose" style="position:absolute; top:15px; right:35px; color:#f1f1f1; font-size:40px; font-weight:bold; cursor:pointer;">&times;</span>
        <img id="previewImg" style="margin:auto; display:block; max-width:90%; max-height:85vh; border-radius:4px; box-shadow:0 0 20px rgba(0,0,0,0.5);">
        <div id="previewCaption" style="margin:auto; display:block; width:80%; max-width:700px; text-align:center; color:#ccc; padding:10px 0; font-size:14px;"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const errorMsg = document.getElementById('errorMessage');
            const successMsg = document.getElementById('successMessage');
            
            if (errorMsg) {
                setTimeout(() => {
                    errorMsg.style.transition = 'opacity 0.5s';
                    errorMsg.style.opacity = '0';
                    setTimeout(() => {
                        errorMsg.remove();
                        const url = new URL(window.location.href);
                        url.searchParams.delete('error');
                        window.history.replaceState({}, document.title, url.pathname);
                    }, 500);
                }, 3000);
            }
            
            if (successMsg) {
                setTimeout(() => {
                    successMsg.style.transition = 'opacity 0.5s';
                    successMsg.style.opacity = '0';
                    setTimeout(() => {
                        successMsg.remove();
                        const url = new URL(window.location.href);
                        url.searchParams.delete('success');
                        window.history.replaceState({}, document.title, url.pathname);
                    }, 500);
                }, 3000);
            }
        });
        
        function filterTransactions() {
            const status = document.getElementById('statusFilter').value;
            if (status) {
                window.location.href = '?status=' + status;
            } else {
                window.location.href = window.location.pathname;
            }
        }
        
        function viewTransaction(trx) {
            document.getElementById('update_transaction_id').value = trx.id;
            document.getElementById('update_status').value = trx.status || 'pending';

            const hasProof = trx.proof_path && trx.proof_path.trim() !== '' && trx.payment_method === 'transfer';

            let finalProofPath = '';
            if (hasProof) {
                if (trx.proof_path.startsWith('asset/')) {
                    finalProofPath = '../' + trx.proof_path;
                } else if (trx.proof_path.startsWith('../')) {
                    finalProofPath = trx.proof_path;
                } else {
                    finalProofPath = '../asset/proofs/' + trx.proof_path;
                }
            }

            let detailHTML = `
                <div style="background: #f8f8f8; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="margin-bottom: 15px; color: #5a4a4a;">Order Information</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div>
                            <p><strong>Order Number:</strong> #${String(trx.id).padStart(6, '0')}</p>
                            <p><strong>Date:</strong> ${new Date(trx.created_at).toLocaleString('id-ID')}</p>
                            <p><strong>Status:</strong> <span class="status-badge-trx ${(trx.status||'pending').toLowerCase()}">${(trx.status||'pending').toUpperCase()}</span></p>
                        </div>
                        <div>
                            <p><strong>Payment:</strong> ${(trx.payment_method||'').toUpperCase()}</p>
                            <p><strong>Total:</strong> Rp ${parseInt(trx.total_amount||0).toLocaleString('id-ID')}</p>
                        </div>
                    </div>
                </div>

                <div style="background: #f8f8f8; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="margin-bottom: 15px; color: #5a4a4a;">Customer</h3>
                    <p><strong>Name:</strong> ${trx.customer_name||'-'}</p>
                    <p><strong>Email:</strong> ${trx.email||'-'}</p>
                    <p><strong>Phone:</strong> ${trx.phone||'-'}</p>
                    <p><strong>Address:</strong><br>${trx.address||'-'}</p>
                </div>
            `;

            if (hasProof) {
                detailHTML += `
                <div style="background: #f8f8f8; padding: 20px; border-radius: 8px;">
                    <h3 style="margin-bottom: 15px; color: #5a4a4a;">Payment Proof</h3>
                    <div style="text-align:center;">
                        <img src="${finalProofPath}" 
                             style="max-width:100%; max-height:400px; border-radius:8px; border:2px solid #e0d5d5; cursor:zoom-in;"
                             onclick="openImagePreview('${finalProofPath}')"
                             onerror="this.onerror=null; this.src='../asset/nb530.png';">
                        <p style="font-size:13px; color:#777;">Klik gambar untuk memperbesar</p>
                    </div>
                </div>`;
            } else if (trx.payment_method === 'transfer') {
                detailHTML += `
                <div style="background:#fff3cd; padding:15px; border-radius:8px;">
                    Bukti pembayaran belum diupload.
                </div>`;
            }

            document.getElementById('transactionDetail').innerHTML = detailHTML;
            document.getElementById('viewTransactionModal').style.display = 'block';
        } 
        
        function closeViewModal() {
            const modal = document.getElementById('viewTransactionModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }, 300);
        }

        function openImagePreview(src, caption) {
            const preview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');
            const captionEl = document.getElementById('previewCaption');
            
            preview.style.display = 'block';
            previewImg.src = src;
            captionEl.innerHTML = caption || '';
            document.body.style.overflow = 'hidden';
        }
        
        function closeImagePreview() {
            document.getElementById('imagePreview').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        document.getElementById('previewClose').addEventListener('click', closeImagePreview);
        
        window.addEventListener('click', function(event) {
            const lightbox = document.getElementById('imagePreview');
            if (event.target == lightbox) {
                closeImagePreview();
            }
            const modal = document.getElementById('viewTransactionModal');
            if (event.target == modal) {
                closeViewModal();
            }
        });
        
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const lightbox = document.getElementById('imagePreview');
                if (lightbox.style.display === 'block') {
                    closeImagePreview();
                } else {
                    closeViewModal();
                }
            }
        });
    </script>

</body>
</html>
<?php $conn->close(); ?>