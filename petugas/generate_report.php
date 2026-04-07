<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../user/login.php");
    exit();
}

$conn = new mysqli("localhost","root","","prashop_db");
if ($conn->connect_error) die("DB Error");

// ======================
// STOCK DATA
// ======================
$stock = $conn->query("SELECT * FROM products ORDER BY id DESC");

// ======================
// SALES DATA
// ======================
$sales = $conn->query("
    SELECT td.product_name, td.quantity, td.price, 
           (td.price * td.quantity) as total_price,
           t.created_at
    FROM transaction_details td
    JOIN transactions t ON td.transaction_id = t.id
    ORDER BY t.created_at DESC
");

// ======================
// TRANSACTION DATA - ✅ TAMBAHKAN address, phone, email
// ======================
$transactions = $conn->query("
    SELECT t.id, t.customer_name, t.phone, t.email, t.address, t.payment_method, t.proof_path, td.product_name, td.quantity,
           t.created_at, td.price,
           (td.price * td.quantity) as total_price
    FROM transactions t
    JOIN transaction_details td ON t.id = td.transaction_id
    ORDER BY t.created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Generate Report</title>
<link rel="stylesheet" href="../style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            <a href="management_transaction.php">
                MANAGEMENT<br>TRANSACTION
            </a>
            <a href="generate_report.php" class="active">
                GENERATE<br>REPORT
            </a>
        </div>
        <div class="nav-auth">
            <a href="../user/logout.php" class="logout-link">
                <img src="../asset/logo2.png" alt="Logout" class="logout-icon"> LOGOUT
            </a>
        </div>
    </nav>

<div class="report-container">

<h1 style="text-align:center;margin-bottom:30px;">Generate Report</h1>

<div class="tabs">
    <div class="tab active" onclick="showTab('stock')">Stock</div>
    <div class="tab" onclick="showTab('sales')">Sales</div>
    <div class="tab" onclick="showTab('transaction')">Transaction</div>
</div>

<!-- ================= STOCK ================= -->
<div id="stock" class="section active">
<table class="report-table">
<tr>
<th>ID</th><th>Name</th><th>Category</th><th>Stock</th><th>Price</th>
</tr>
<?php while($row=$stock->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['product_name'] ?></td>
<td><?= $row['category'] ?></td>
<td><?= $row['stock'] ?></td>
<td>Rp <?= number_format($row['price']) ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<!-- ================= SALES ================= -->
<div id="sales" class="section">
<table class="report-table">
<tr>
<th>ID</th><th>Name</th><th>Date</th><th>Quantity</th><th>Total Price</th>
</tr>
<?php $i=1; while($row=$sales->fetch_assoc()): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= $row['product_name'] ?></td>
<td><?= date('d/m/Y',strtotime($row['created_at'])) ?></td>
<td><?= $row['quantity'] ?></td>
<td>Rp <?= number_format($row['total_price']) ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<!-- ================= TRANSACTION ================= -->
<div id="transaction" class="section">
<table class="report-table">
<tr>
<th>ID</th><th>Customer</th><th>Name</th><th>Quantity</th><th>Date</th><th>Total Price</th><th>Actions</th>
</tr>
<?php while($row=$transactions->fetch_assoc()): ?>
<tr>
<td><?= $row['id'] ?></td>
<td><?= $row['customer_name'] ?></td>
<td><?= $row['product_name'] ?></td>
<td><?= $row['quantity'] ?></td>
<td><?= date('d/m/Y',strtotime($row['created_at'])) ?></td>
<td>Rp <?= number_format($row['total_price']) ?></td>
<td><button onclick='showDetail(<?= htmlspecialchars(json_encode($row, JSON_UNESCAPED_SLASHES)); ?>)' class="btn-receipt">Detail</button></td>
</tr>
<?php endwhile; ?>
</table>
</div>

</div>

<!-- View Transaction Detail Modal -->
<div id="detailModal" class="modal">
    <div class="modal-content modal-large">
        <span class="close" onclick="closeDetailModal()">&times;</span>
        <h2>Transaction Details</h2>
        
        <div id="transactionDetail"></div>
    </div>
</div>

<!-- Image Preview Container -->
<div id="imagePreview" style="display:none; position:fixed; z-index:9999; padding-top:50px; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.9);">
    <span id="previewClose" style="position:absolute; top:15px; right:35px; color:#f1f1f1; font-size:40px; font-weight:bold; cursor:pointer;">&times;</span>
    <img id="previewImg" style="margin:auto; display:block; max-width:90%; max-height:85vh; border-radius:4px; box-shadow:0 0 20px rgba(0,0,0,0.5);">
    <div id="previewCaption" style="margin:auto; display:block; width:80%; max-width:700px; text-align:center; color:#ccc; padding:10px 0; font-size:14px;"></div>
</div>

<script>
function showTab(tab){
    document.querySelectorAll('.section').forEach(s=>s.classList.remove('active'));
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));

    document.getElementById(tab).classList.add('active');
    event.target.classList.add('active');
}

function showDetail(trx) {
    // Fix path untuk bukti pembayaran
    let proofPath = '';
    if (trx.proof_path) {
        if (trx.proof_path.startsWith('asset/')) {
            proofPath = '../' + trx.proof_path;
        } else if (trx.proof_path.startsWith('../')) {
            proofPath = trx.proof_path;
        } else {
            proofPath = '../asset/proofs/' + trx.proof_path;
        }
    }
    
    const hasProof = proofPath && trx.payment_method === 'transfer';
    
    let detailHTML = `
        <div style="background: #f8f8f8; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin-bottom: 15px; color: #5a4a4a;">Order Information</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <p><strong>Order Number:</strong> #${String(trx.id).padStart(6, '0')}</p>
                    <p><strong>Date:</strong> ${new Date(trx.created_at).toLocaleString('id-ID')}</p>
                </div>
                <div>
                    <p><strong>Payment Method:</strong> ${trx.payment_method ? trx.payment_method.toUpperCase() : '-'}</p>
                    <p><strong>Total:</strong> Rp ${parseInt(trx.total_price).toLocaleString('id-ID')}</p>
                </div>
            </div>
        </div>

        <!-- ✅ CUSTOMER SECTION DENGAN ALAMAT LENGKAP -->
        <div style="background: #f8f8f8; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin-bottom: 15px; color: #5a4a4a;"><i class="fas fa-user"></i> Customer</h3>
            <p><strong>Name:</strong> ${trx.customer_name || '-'}</p>
            <p><strong>Phone:</strong> ${trx.phone || '-'}</p>
            <p><strong>Email:</strong> ${trx.email || '-'}</p>
            <p><strong>Address:</strong><br><span style="color:#666;">${trx.address || '-'}</span></p>
        </div>

        <div style="background: #f8f8f8; padding: 20px; border-radius: 8px;">
            <h3 style="margin-bottom: 15px; color: #5a4a4a;"><i class="fas fa-box"></i> Product</h3>
            <p><strong>Name:</strong> ${trx.product_name}</p>
            <p><strong>Quantity:</strong> ${trx.quantity} pcs</p>
            <p><strong>Price:</strong> Rp ${parseInt(trx.price).toLocaleString('id-ID')}</p>
        </div>
    `;
    
    // Tampilkan bukti pembayaran jika ada
    if (hasProof) {
        detailHTML += `
            <div style="background: #f8f8f8; padding: 20px; border-radius: 8px; margin-top: 20px;">
                <h3 style="margin-bottom: 15px; color: #5a4a4a;"><i class="fas fa-image"></i> Payment Proof</h3>
                <div style="text-align:center;">
                    <img src="${proofPath}" 
                         style="max-width:100%; max-height:400px; border-radius:8px; border:2px solid #e0d5d5; cursor:zoom-in;"
                         onclick="openImagePreview('${proofPath}')"
                         onerror="this.onerror=null; this.src='../asset/nb530.png'; this.parentElement.innerHTML='<p style=\'color:#e74c3c;\'>Gambar tidak dapat dimuat</p>';">
                    <p style="font-size:13px; color:#777; margin-top:10px;">Klik gambar untuk memperbesar</p>
                </div>
            </div>
        `;
    } else if (trx.payment_method === 'transfer') {
        detailHTML += `
            <div style="background:#fff3cd; padding:15px; border-radius:8px; margin-top:20px;">
                <p style="color:#856404; margin:0;"><i class="fas fa-exclamation-triangle"></i> Bukti pembayaran belum diupload</p>
            </div>
        `;
    }
    
    document.getElementById('transactionDetail').innerHTML = detailHTML;
    document.getElementById('detailModal').style.display = 'block';
}

function closeDetailModal() {
    const modal = document.getElementById('detailModal');
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
    const modal = document.getElementById('detailModal');
    if (event.target == modal) {
        closeDetailModal();
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const lightbox = document.getElementById('imagePreview');
        if (lightbox.style.display === 'block') {
            closeImagePreview();
        } else {
            closeDetailModal();
        }
    }
});
</script>

</body>
</html>