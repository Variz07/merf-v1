<?php
require_once '../config.php';

// Cek login
if(!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Silakan login terlebih dahulu';
    header('Location: ../auth/signin.php');
    exit();
}

$page_title = 'Upload Produk';
$user_id = $_SESSION['user_id'];

// Cek role seller
$stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
$is_seller = ($user['role'] == 'seller' || $user['role'] == 'admin');

// Handle upgrade to seller
if(isset($_POST['upgrade_to_seller'])) {
    $stmt = $pdo->prepare("UPDATE users SET role = 'seller' WHERE user_id = ?");
    if($stmt->execute([$user_id])) {
        $_SESSION['user_role'] = 'seller';
        $_SESSION['success_message'] = 'Selamat! Anda sekarang adalah seller. Silakan upload produk Anda.';
        header('Location: upload-product.php');
        exit();
    }
}

// Handle product upload
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_product'])) {
    $name = trim($_POST['name']);
    $category = $_POST['category'];
    $subcategory = $_POST['subcategory'] ?? '';
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $discounted_price = !empty($_POST['discounted_price']) ? floatval($_POST['discounted_price']) : null;
    $stock = intval($_POST['stock'] ?? 1);
    $location = trim($_POST['location']);
    
    $errors = [];
    
    // Validasi
    if(empty($name)) $errors[] = 'Nama produk harus diisi';
    if(empty($category)) $errors[] = 'Kategori harus dipilih';
    if(empty($description)) $errors[] = 'Deskripsi produk harus diisi';
    if($price <= 0) $errors[] = 'Harga harus lebih dari 0';
    if(empty($location)) $errors[] = 'Lokasi harus diisi';
    
    // Upload gambar
    $image_name = '';
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            if($_FILES['image']['size'] <= 5 * 1024 * 1024) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_path = '../' . UPLOAD_PATH . $new_filename;
                
                // Buat folder jika belum ada
                $upload_dir = dirname($upload_path);
                if(!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if(move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_name = $new_filename;
                } else {
                    $errors[] = 'Gagal upload gambar. Coba lagi.';
                }
            } else {
                $errors[] = 'Ukuran gambar maksimal 5MB';
            }
        } else {
            $errors[] = 'Format gambar harus JPG, PNG, GIF, atau WebP';
        }
    } else {
        $errors[] = 'Gambar produk wajib diupload';
    }
    
    if(empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products 
                (seller_id, name, category, subcategory, description, price, discounted_price, 
                 quantity, image, location, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            if($stmt->execute([
                $user_id, $name, $category, $subcategory, $description, $price, $discounted_price,
                $stock, $image_name, $location
            ])) {
                $product_id = $pdo->lastInsertId();
                $_SESSION['success_message'] = 'Produk berhasil diupload!';
                header("Location: product-detail.php?id=$product_id");
                exit();
            }
        } catch(PDOException $e) {
            error_log("Upload Error: " . $e->getMessage());
            $errors[] = 'Gagal menyimpan produk. Silakan coba lagi.';
        }
    }
}

include '../header.php';
?>

<div class="upload-container">
    <?php if(!$is_seller): ?>
    <!-- UPGRADE SELLER CARD -->
    <div class="upgrade-card">
        <div class="upgrade-icon">
            <i class="fas fa-crown"></i>
        </div>
        <h2>Jadi Seller Sekarang!</h2>
        <p>Upload produk, jasa, atau jastip dan mulai dapatkan penghasilan dari komunitas mahasiswa President University.</p>
        <div class="benefits">
            <div class="benefit-item">
                <i class="fas fa-check-circle"></i>
                <span>Jual makanan & minuman</span>
            </div>
            <div class="benefit-item">
                <i class="fas fa-check-circle"></i>
                <span>Jual barang preloved</span>
            </div>
            <div class="benefit-item">
                <i class="fas fa-check-circle"></i>
                <span>Tawarkan jasa & kursus</span>
            </div>
            <div class="benefit-item">
                <i class="fas fa-check-circle"></i>
                <span>Jastip & kebutuhan mendesak</span>
            </div>
        </div>
        <form method="POST">
            <button type="submit" name="upgrade_to_seller" class="btn-upgrade">
                <i class="fas fa-store"></i> Upgrade ke Seller
            </button>
        </form>
        <p class="upgrade-note">*Gratis, tidak dipungut biaya</p>
    </div>
    
    <?php else: ?>
    <!-- UPLOAD PRODUCT FORM - MINIMALIS -->
    <div class="upload-card">
        <div class="upload-header">
            <h1>Upload Produk</h1>
            <p>Jual produk atau jasa Anda di MERF Marketplace</p>
        </div>
        
        <?php if(!empty($errors)): ?>
        <div class="error-box">
            <ul>
                <?php foreach($errors as $error): ?>
                <li><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="upload-form">
            <input type="hidden" name="upload_product" value="1">
            
            <!-- FORM SECTION 1: INFORMASI DASAR -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-number">1</div>
                    <h3>Informasi Dasar</h3>
                </div>
                
                <div class="form-grid">
                    <!-- Nama Produk -->
                    <div class="form-group full-width">
                        <label>Nama Produk/Jasa <span class="required">*</span></label>
                        <input type="text" 
                               name="name" 
                               placeholder="Contoh: Jasa Laundry, Dimsum Mentai, Sweater Thrifting" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                               required>
                    </div>
                    
                    <!-- Kategori & Subkategori -->
                    <div class="form-group">
                        <label>Kategori <span class="required">*</span></label>
                        <select name="category" id="categorySelect" required>
                            <option value="" disabled <?php echo empty($_POST['category']) ? 'selected' : ''; ?>>Pilih Kategori</option>
                            <option value="food" <?php echo ($_POST['category'] ?? '') == 'food' ? 'selected' : ''; ?>>🍱 Makanan & Minuman</option>
                            <option value="preloved" <?php echo ($_POST['category'] ?? '') == 'preloved' ? 'selected' : ''; ?>>👕 Barang Preloved</option>
                            <option value="service" <?php echo ($_POST['category'] ?? '') == 'service' ? 'selected' : ''; ?>>💼 Jasa & Layanan</option>
                            <option value="urgent" <?php echo ($_POST['category'] ?? '') == 'urgent' ? 'selected' : ''; ?>>⚡ Kebutuhan Mendesak</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Subkategori (opsional)</label>
                        <select name="subcategory" id="subcategorySelect">
                            <option value="">Pilih Subkategori</option>
                        </select>
                    </div>
                    
                    <!-- Deskripsi -->
                    <div class="form-group full-width">
                        <label>Deskripsi <span class="required">*</span></label>
                        <textarea name="description" 
                                  rows="4" 
                                  placeholder="Jelaskan produk/jasa Anda secara detail. Semakin detail, semakin mudah pembeli percaya." 
                                  required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <div class="char-counter">
                            <span id="charCount">0</span>/500 karakter
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FORM SECTION 2: HARGA & STOK -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-number">2</div>
                    <h3>Harga & Stok</h3>
                </div>
                
                <div class="form-grid">
                    <!-- Harga -->
                    <div class="form-group">
                        <label>Harga <span class="required">*</span></label>
                        <div class="price-input">
                            <span class="currency">Rp</span>
                            <input type="number" 
                                   name="price" 
                                   placeholder="0" 
                                   value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
                                   min="0" 
                                   step="1000"
                                   required>
                        </div>
                    </div>
                    
                    <!-- Harga Diskon -->
                    <div class="form-group">
                        <label>Harga Diskon</label>
                        <div class="price-input">
                            <span class="currency">Rp</span>
                            <input type="number" 
                                   name="discounted_price" 
                                   placeholder="0 (opsional)" 
                                   value="<?php echo htmlspecialchars($_POST['discounted_price'] ?? ''); ?>" 
                                   min="0" 
                                   step="1000">
                        </div>
                        <small class="help-text">Kosongkan jika tidak ada diskon</small>
                    </div>
                    
                    <!-- Stok -->
                    <div class="form-group">
                        <label>Stok</label>
                        <input type="number" 
                               name="stock" 
                               placeholder="Jumlah barang" 
                               value="<?php echo htmlspecialchars($_POST['stock'] ?? '1'); ?>" 
                               min="1">
                    </div>
                    
                    <!-- Lokasi -->
                    <div class="form-group">
                        <label>Lokasi <span class="required">*</span></label>
                        <input type="text" 
                               name="location" 
                               placeholder="Contoh: SBH Tower A, President University" 
                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
            </div>
            
            <!-- FORM SECTION 3: GAMBAR PRODUK -->
            <div class="form-section">
                <div class="section-header">
                    <div class="section-number">3</div>
                    <h3>Gambar Produk</h3>
                </div>
                
                <div class="form-group full-width">
                    <div class="image-upload-area" id="imageUploadArea">
                        <div class="upload-prompt">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <h4>Upload Gambar Produk</h4>
                            <p>Klik atau drag & drop gambar di sini</p>
                            <span class="upload-hint">Format: JPG, PNG, GIF, WebP (Maks. 5MB)</span>
                        </div>
                        <input type="file" name="image" id="imageInput" accept="image/*" required>
                    </div>
                    <div class="image-preview" id="imagePreview"></div>
                </div>
            </div>
            
            <!-- SUBMIT BUTTON -->
            <div class="form-actions">
                <button type="submit" class="btn-submit">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Upload Produk
                </button>
                <a href="javascript:history.back()" class="btn-cancel">
                    Batal
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
// Subcategory data
const subcategories = {
    food: [
        { value: 'dimsum', label: '🥟 Dimsum' },
        { value: 'makanan', label: '🍚 Makanan Berat' },
        { value: 'minuman', label: '🥤 Minuman' },
        { value: 'snack', label: '🍪 Snack' },
        { value: 'kue', label: '🍰 Kue & Dessert' },
        { value: 'dessert', label: '🍨 Dessert' }
    ],
    preloved: [
        { value: 'clothes', label: '👕 Pakaian' },
        { value: 'skincare', label: '🧴 Skincare' },
        { value: 'electronics', label: '📱 Elektronik' },
        { value: 'books', label: '📚 Buku' },
        { value: 'accessories', label: '🕶️ Aksesoris' },
        { value: 'shoes', label: '👟 Sepatu' },
        { value: 'bags', label: '👜 Tas' }
    ],
    service: [
        { value: 'courses', label: '📖 Kursus' },
        { value: 'jastip', label: '🛵 Jastip' },
        { value: 'repair', label: '🔧 Perbaikan' },
        { value: 'beauty', label: '💄 Kecantikan' },
        { value: 'cleaning', label: '🧹 Cleaning Service' },
        { value: 'design', label: '🎨 Design' },
        { value: 'laundry', label: '🧺 Laundry' }
    ],
    urgent: [
        { value: 'food', label: '🍱 Makanan' },
        { value: 'items', label: '📦 Barang' },
        { value: 'services', label: '⚡ Jasa' },
        { value: 'transportation', label: '🚗 Transportasi' }
    ]
};

// Update subcategory berdasarkan kategori
document.getElementById('categorySelect')?.addEventListener('change', function() {
    const category = this.value;
    const subSelect = document.getElementById('subcategorySelect');
    
    subSelect.innerHTML = '<option value="">Pilih Subkategori</option>';
    
    if(category && subcategories[category]) {
        subcategories[category].forEach(sub => {
            const option = document.createElement('option');
            option.value = sub.value;
            option.textContent = sub.label;
            subSelect.appendChild(option);
        });
    }
});

// Character counter untuk deskripsi
const descTextarea = document.querySelector('textarea[name="description"]');
if(descTextarea) {
    descTextarea.addEventListener('input', function() {
        const count = this.value.length;
        const counter = document.getElementById('charCount');
        if(counter) {
            counter.textContent = count;
            counter.style.color = count > 500 ? '#DC3545' : '#6D6D6D';
        }
    });
}

// Image upload preview
const uploadArea = document.getElementById('imageUploadArea');
const imageInput = document.getElementById('imageInput');
const imagePreview = document.getElementById('imagePreview');

uploadArea?.addEventListener('click', function() {
    imageInput.click();
});

uploadArea?.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('dragover');
});

uploadArea?.addEventListener('dragleave', function() {
    this.classList.remove('dragover');
});

uploadArea?.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('dragover');
    
    if(e.dataTransfer.files.length) {
        imageInput.files = e.dataTransfer.files;
        handleImagePreview(e.dataTransfer.files[0]);
    }
});

imageInput?.addEventListener('change', function() {
    if(this.files.length) {
        handleImagePreview(this.files[0]);
    }
});

function handleImagePreview(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        imagePreview.innerHTML = `
            <div class="preview-item">
                <img src="${e.target.result}" alt="Preview">
                <button type="button" class="remove-image" onclick="removeImage()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        uploadArea.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function removeImage() {
    imageInput.value = '';
    imagePreview.innerHTML = '';
    uploadArea.style.display = 'flex';
}

// Trigger category change on load
window.addEventListener('load', function() {
    const categorySelect = document.getElementById('categorySelect');
    if(categorySelect.value) {
        categorySelect.dispatchEvent(new Event('change'));
    }
});
</script>

<style>
/* ===== UPLOAD PAGE - MINIMALIS & ELEGAN ===== */
.upload-container {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
}

/* UPGRADE CARD */
.upgrade-card {
    background: white;
    border-radius: 24px;
    padding: 50px 40px;
    text-align: center;
    border: 1px solid #E8E3D9;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    max-width: 600px;
    margin: 0 auto;
}

.upgrade-icon {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FFD700, #FFA500);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
}

.upgrade-icon i {
    font-size: 42px;
    color: white;
}

.upgrade-card h2 {
    font-size: 32px;
    font-weight: 700;
    color: #2C2416;
    margin-bottom: 15px;
}

.upgrade-card p {
    color: #6D6D6D;
    font-size: 16px;
    margin-bottom: 30px;
    line-height: 1.6;
}

.benefits {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 35px;
    text-align: left;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #2C2416;
    font-size: 14px;
}

.benefit-item i {
    color: #28A745;
}

.btn-upgrade {
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    color: white;
    border: none;
    padding: 16px 40px;
    border-radius: 40px;
    font-size: 18px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-upgrade:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(76,60,39,0.2);
}

.upgrade-note {
    font-size: 12px;
    color: #999;
    margin-top: 20px;
}

/* UPLOAD CARD */
.upload-card {
    background: white;
    border-radius: 24px;
    padding: 40px;
    border: 1px solid #E8E3D9;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
}

.upload-header {
    text-align: center;
    margin-bottom: 40px;
}

.upload-header h1 {
    font-size: 32px;
    font-weight: 700;
    color: #4C3C27;
    margin-bottom: 10px;
}

.upload-header p {
    color: #6D6D6D;
    font-size: 16px;
}

/* ERROR BOX */
.error-box {
    background: #FEF2F2;
    border-left: 4px solid #DC3545;
    border-radius: 12px;
    padding: 20px 25px;
    margin-bottom: 30px;
}

.error-box ul {
    list-style: none;
}

.error-box li {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #DC3545;
    font-size: 14px;
    margin-bottom: 8px;
}

.error-box li:last-child {
    margin-bottom: 0;
}

/* FORM SECTIONS */
.form-section {
    margin-bottom: 40px;
    padding-bottom: 40px;
    border-bottom: 1px solid #F0EDE5;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

.section-number {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #4C3C27;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
}

.section-header h3 {
    font-size: 20px;
    font-weight: 600;
    color: #2C2416;
}

/* FORM GRID */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group.full-width {
    grid-column: span 2;
}

label {
    font-size: 14px;
    font-weight: 600;
    color: #2C2416;
    display: flex;
    align-items: center;
    gap: 4px;
}

.required {
    color: #DC3545;
    font-size: 16px;
}

input, select, textarea {
    width: 100%;
    padding: 14px 16px;
    border: 1.5px solid #E8E3D9;
    border-radius: 12px;
    font-size: 15px;
    transition: all 0.3s;
    background: white;
}

input:focus, select:focus, textarea:focus {
    border-color: #C9B59C;
    outline: none;
    box-shadow: 0 0 0 4px rgba(201,181,156,0.1);
}

/* PRICE INPUT */
.price-input {
    display: flex;
    align-items: center;
    border: 1.5px solid #E8E3D9;
    border-radius: 12px;
    overflow: hidden;
    background: white;
}

.price-input:focus-within {
    border-color: #C9B59C;
    box-shadow: 0 0 0 4px rgba(201,181,156,0.1);
}

.currency {
    padding: 14px 16px;
    background: #F5F3EE;
    color: #4C3C27;
    font-weight: 600;
    border-right: 1.5px solid #E8E3D9;
}

.price-input input {
    border: none;
    border-radius: 0;
    padding: 14px 16px;
}

.price-input input:focus {
    box-shadow: none;
}

/* HELP TEXT */
.help-text {
    font-size: 12px;
    color: #999;
    margin-top: 4px;
}

/* CHAR COUNTER */
.char-counter {
    text-align: right;
    font-size: 12px;
    color: #6D6D6D;
    margin-top: 8px;
}

#charCount {
    font-weight: 600;
}

/* IMAGE UPLOAD */
.image-upload-area {
    border: 2px dashed #E8E3D9;
    border-radius: 16px;
    padding: 40px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #F9F7F4;
    min-height: 250px;
}

.image-upload-area:hover,
.image-upload-area.dragover {
    border-color: #4C3C27;
    background: #F5F3EE;
}

.upload-prompt {
    text-align: center;
}

.upload-prompt i {
    font-size: 48px;
    color: #C9B59C;
    margin-bottom: 15px;
}

.upload-prompt h4 {
    font-size: 18px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 8px;
}

.upload-prompt p {
    color: #6D6D6D;
    margin-bottom: 12px;
}

.upload-hint {
    font-size: 13px;
    color: #999;
}

.image-upload-area input[type="file"] {
    display: none;
}

/* IMAGE PREVIEW */
.image-preview {
    margin-top: 20px;
}

.preview-item {
    position: relative;
    display: inline-block;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #C9B59C;
}

.preview-item img {
    max-width: 300px;
    max-height: 200px;
    object-fit: cover;
    display: block;
}

.remove-image {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(0,0,0,0.7);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.remove-image:hover {
    background: #DC3545;
    transform: scale(1.1);
}

/* FORM ACTIONS */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
}

.btn-submit {
    flex: 1;
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    color: white;
    border: none;
    padding: 16px 30px;
    border-radius: 40px;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-submit:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(76,60,39,0.2);
}

.btn-cancel {
    padding: 16px 30px;
    border: 1.5px solid #E8E3D9;
    border-radius: 40px;
    color: #6D6D6D;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-cancel:hover {
    background: #F5F3EE;
    border-color: #C9B59C;
    color: #2C2416;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .upload-card {
        padding: 30px 20px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group.full-width {
        grid-column: span 1;
    }
    
    .benefits {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .upload-header h1 {
        font-size: 28px;
    }
}

@media (max-width: 480px) {
    .upload-container {
        padding: 0 15px;
    }
    
    .section-header h3 {
        font-size: 18px;
    }
    
    .btn-submit,
    .btn-cancel {
        padding: 14px 20px;
        font-size: 15px;
    }
}
</style>

<?php include '../footer.php'; ?>