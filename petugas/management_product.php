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

// Setup upload directory
$uploadDir = __DIR__ . "/../asset/uploads/";
$uploadUrl = "uploads/";

// Buat folder uploads jika belum ada
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Inisialisasi variabel notifikasi
$success = "";
$error = "";

// Fungsi upload gambar
function uploadImage($file, $uploadDir, $uploadUrl) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error code: ' . $file['error']];
    }
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'message' => 'Tipe file tidak diperbolehkan: ' . $file['type']];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File terlalu besar (max 5MB)'];
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'product_' . uniqid() . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        chmod($targetPath, 0644);
        return ['success' => true, 'filename' => 'uploads/' . $filename];
    } else {
        return ['success' => false, 'message' => 'Gagal menyimpan file'];
    }
}

// Fungsi hapus gambar lama
function deleteImage($imagePath, $uploadDir) {
    if ($imagePath && $imagePath !== 'nb530.png' && strpos($imagePath, 'uploads/') === 0) {
        $filename = str_replace('uploads/', '', $imagePath);
        $filePath = $uploadDir . $filename;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}

// Proses hapus product
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Ambil gambar untuk dihapus
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        deleteImage($row['image'], $uploadDir);
    }
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $success = "Product berhasil dihapus!";
    } else {
        $error = "Gagal menghapus product!";
    }
}

// Proses tambah product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $product_name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $discount = intval($_POST['discount'] ?? 0); // ✅ TAMBAH: Ambil discount
    $stock = intval($_POST['stock']);
    $status = $stock > 0 ? 'active' : 'empty';
    $image = 'nb530.png'; // Default
    
    // Upload gambar jika ada
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['product_image'], $uploadDir, $uploadUrl);
        if ($uploadResult['success']) {
            $image = $uploadResult['filename'];
        }
    }
    
    // ✅ TAMBAH: discount di INSERT query
    $stmt = $conn->prepare("INSERT INTO products (product_name, category, status, price, discount, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdiis", $product_name, $category, $status, $price, $discount, $stock, $image);
    
    if ($stmt->execute()) {
        $success = "Product berhasil ditambahkan!";
    } else {
        $error = "Gagal menambah product!";
    }
}

// Proses update product
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $id = intval($_POST['product_id']);
    $product_name = trim($_POST['product_name']);
    $category = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $discount = intval($_POST['discount'] ?? 0); // ✅ TAMBAH: Ambil discount
    $stock = intval($_POST['stock']);
    $status = $stock > 0 ? 'active' : 'empty';
    
    // Ambil data product lama
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $oldProduct = $result->fetch_assoc();
    $image = $oldProduct['image']; // Default pakai gambar lama
    
    // Upload gambar baru jika ada
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadImage($_FILES['product_image'], $uploadDir, $uploadUrl);
        if ($uploadResult['success']) {
            // Hapus gambar lama
            deleteImage($image, $uploadDir);
            $image = $uploadResult['filename'];
        }
    }
    
    // ✅ TAMBAH: discount di UPDATE query
    $stmt = $conn->prepare("UPDATE products SET product_name = ?, category = ?, price = ?, discount = ?, stock = ?, status = ?, image = ? WHERE id = ?");
    $stmt->bind_param("ssdiissi", $product_name, $category, $price, $discount, $stock, $status, $image, $id);
    
    if ($stmt->execute()) {
        $success = "Product berhasil diupdate!";
    } else {
        $error = "Gagal update product!";
    }
}

// Ambil semua products
$result = $conn->query("SELECT * FROM products ORDER BY id ASC");
$products = [];
if ($result->num_rows > 0) {
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
    <title>Prashop - Management Products</title>
    
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
            <a href="management_product.php"class="active">
                MANAGEMENT<br>PRODUCT
            </a>
            <a href="management_transaction.php">
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
        <div class="product-header">
            <h1 class="product-title">PRODUCT</h1>
            <button class="btn-add-product" onclick="openAddModal()">Add Product</button>
        </div>
        
        <!-- Notifikasi -->
        <?php if ($error): ?>
            <div class="error-message" id="errorMessage"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-message" id="successMessage"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- Products Table -->
        <div class="products-table-container">
            <table class="products-table">
                <thead>
                    <tr>
                        <th>PRODUCT NAME</th>
                        <th>CATEGORY</th>
                        <th>PRICE</th>
                        <th>DISCOUNT</th>
                        <th>STOCK</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <div class="product-image-cell">
                                <?php 
                                $imgSrc = '../asset/' . htmlspecialchars($product['image']);
                                if (!file_exists(__DIR__ . "/../asset/" . $product['image'])) {
                                    $imgSrc = '../asset/nb530.png';
                                }
                                ?>
                                <img src="<?php echo $imgSrc; ?>" 
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                     onerror="this.src='../asset/nb530.png'">
                                <span class="product-name-cell"><?php echo htmlspecialchars($product['product_name']); ?></span>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                        <!-- ✅ TAMBAH: Kolom Discount di Table -->
                        <td>
                            <?php if (!empty($product['discount']) && $product['discount'] > 0): ?>
                                <span style="background:#e74c3c;color:white;padding:3px 8px;border-radius:4px;font-size:11px;font-weight:600;">
                                    -<?php echo $product['discount']; ?>%
                                </span>
                            <?php else: ?>
                                <span style="color:#999;font-size:12px;">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $product['stock']; ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action-edit" onclick='openEditModal(<?php echo htmlspecialchars(json_encode($product, JSON_UNESCAPED_SLASHES)); ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <a href="?delete_id=<?php echo $product['id']; ?>" 
                                   class="btn-action-delete" 
                                   onclick="return confirm('Yakin ingin menghapus product ini?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <!-- Add Product Modal -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddModal()">&times;</span>
            <h2>Add New Product</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="input-group-modal">
                    <i class="fas fa-box"></i>
                    <input type="text" name="product_name" placeholder="Product Name" required>
                </div>
                
                <div class="input-group-modal">
                    <i class="fas fa-tags"></i>
                    <div class="select-wrapper">
                        <select name="category" required>
                            <option value="" disabled selected>Pilih Category</option>
                            <option value="Fashion">Fashion</option>
                            <option value="Sports">Sports</option>
                            <option value="Casual">Casual</option>
                            <option value="Running">Running</option>
                        </select>
                    </div>
                </div>
                
                <div class="input-group-modal">
                    <i class="fas fa-money-bill-wave"></i>
                    <input type="number" name="price" placeholder="Price" min="0" step="0.01" required>
                </div>
                
                <!-- ✅ TAMBAH: Input Discount -->
                <div class="input-group-modal">
                    <i class="fas fa-percent"></i>
                    <input type="number" name="discount" placeholder="Discount (%)" min="0" max="100" value="0">
                    <small style="color:#999;font-size:11px;margin-left:35px;">0 = no discount</small>
                </div>
                
                <div class="input-group-modal">
                    <i class="fas fa-boxes"></i>
                    <input type="number" name="stock" placeholder="Stock" min="0" required>
                </div>
                
                <div class="input-group-modal">
                    <i class="fas fa-image"></i>
                    <input type="file" name="product_image" id="add_image" accept="image/*" onchange="previewImage(this, 'add_preview')">
                </div>
                
                <div id="add_preview" class="image-preview" style="display: none;">
                    <img src="" alt="Preview" style="max-width: 100%; border-radius: 8px; margin-top: 10px;">
                </div>
                
                <button type="submit" name="add_product" class="btn-action">Add Product</button>
            </form>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Product</h2>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="product_id" id="edit_id">
                
                <div class="input-group-modal">
                    <i class="fas fa-box"></i>
                    <input type="text" name="product_name" id="edit_product_name" required>
                </div>
                
                <div class="input-group-modal">
                    <i class="fas fa-tags"></i>
                    <div class="select-wrapper">
                        <select name="category" id="edit_category" required>
                            <option value="Fashion">Fashion</option>
                            <option value="Sports">Sports</option>
                            <option value="Casual">Casual</option>
                            <option value="Running">Running</option>
                        </select>
                    </div>
                </div>
                
                <div class="input-group-modal">
                    <i class="fas fa-money-bill-wave"></i>
                    <input type="number" name="price" id="edit_price" min="0" step="0.01" required>
                </div>
                
                <!-- ✅ TAMBAH: Input Discount di Edit Modal -->
                <div class="input-group-modal">
                    <i class="fas fa-percent"></i>
                    <input type="number" name="discount" id="edit_discount" min="0" max="100">
                    <small style="color:#999;font-size:11px;margin-left:35px;">0 = no discount</small>
                </div>
                
                <div class="input-group-modal">
                    <i class="fas fa-boxes"></i>
                    <input type="number" name="stock" id="edit_stock" min="0" required>
                </div>
                
                <!-- Current Image -->
                <div class="input-group-modal">
                    <i class="fas fa-image"></i>
                    <div style="flex: 1;">
                        <p style="margin-bottom: 10px; font-size: 13px; color: #666;">Current Image:</p>
                        <img id="edit_current_image" src="" alt="Current" style="max-width: 100px; border-radius: 8px; margin-bottom: 10px; display: block;">
                        <input type="hidden" id="edit_current_image_path">
                    </div>
                </div>
                
                <!-- Upload New Image (Optional) -->
                <div class="input-group-modal">
                    <i class="fas fa-upload"></i>
                    <input type="file" name="product_image" id="edit_image" accept="image/*" onchange="previewImage(this, 'edit_preview')">
                </div>
                
                <p style="font-size: 12px; color: #999; margin: 10px 0;">* Kosongkan jika tidak ingin mengganti gambar</p>
                
                <div id="edit_preview" class="image-preview" style="display: none;">
                    <p style="font-size: 13px; color: #666; margin-bottom: 5px;">New Image Preview:</p>
                    <img src="" alt="Preview" style="max-width: 100%; border-radius: 8px; margin-top: 5px;">
                </div>
                
                <button type="submit" name="update_product" class="btn-action">Update Product</button>
            </form>
        </div>
    </div>

    <script>
        // Auto reload setelah 3 detik jika ada notifikasi
        <?php if ($success || $error): ?>
        setTimeout(function() {
            window.location.href = window.location.pathname;
        }, 3000);
        <?php endif; ?>
        
        // Preview Image Function
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const img = preview.querySelector('img');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Add Modal Functions
        function openAddModal() {
            const modal = document.getElementById('addProductModal');
            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            document.body.style.overflow = 'hidden';
        }
        
        function closeAddModal() {
            const modal = document.getElementById('addProductModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                modal.querySelector('form').reset();
                document.getElementById('add_preview').style.display = 'none';
            }, 300);
        }
        
        // Edit Modal Functions - FIX: Handle image path correctly + discount
        function openEditModal(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_product_name').value = product.product_name;
            document.getElementById('edit_category').value = product.category;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_discount').value = product.discount || 0; // ✅ Set discount value
            document.getElementById('edit_stock').value = product.stock;
            
            // Set current image dengan path yang benar
            const currentImg = document.getElementById('edit_current_image');
            const imagePath = document.getElementById('edit_current_image_path');
            
            // Simpan path gambar untuk form
            imagePath.value = product.image;
            
            // Tampilkan gambar dengan path yang benar
            if (product.image && product.image !== 'nb530.png') {
                currentImg.src = '../asset/' + product.image;
            } else {
                currentImg.src = '../asset/nb530.png';
            }
            
            // Handle error loading image
            currentImg.onerror = function() {
                this.src = '../asset/nb530.png';
            };
            
            // Reset preview dan file input
            document.getElementById('edit_preview').style.display = 'none';
            document.getElementById('edit_image').value = '';
            
            const modal = document.getElementById('editProductModal');
            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('show');
            }, 10);
            document.body.style.overflow = 'hidden';
        }
        
        function closeEditModal() {
            const modal = document.getElementById('editProductModal');
            modal.classList.remove('show');
            setTimeout(() => {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
                modal.querySelector('form').reset();
                document.getElementById('edit_preview').style.display = 'none';
            }, 300);
        }
        
        // Event Listeners
        document.querySelector('.btn-add-product').addEventListener('click', openAddModal);
        document.querySelector('#addProductModal .close').addEventListener('click', closeAddModal);
        document.querySelector('#editProductModal .close').addEventListener('click', closeEditModal);
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const addModal = document.getElementById('addProductModal');
            const editModal = document.getElementById('editProductModal');
            if (event.target == addModal) {
                closeAddModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeAddModal();
                closeEditModal();
            }
        });
    </script>

</body>
</html>
<?php
$conn->close();
?>