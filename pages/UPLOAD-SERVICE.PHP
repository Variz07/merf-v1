<?php
require_once '../config.php';

// Cek login
if(!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = 'Silakan login terlebih dahulu';
    header('Location: ../auth/signin.php');
    exit();
}

$page_title = 'Upload Jasa';

// Cek role seller
$stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$is_seller = ($user['role'] == 'seller' || $user['role'] == 'admin');

// Handle upgrade to seller
if(isset($_POST['upgrade_to_seller'])) {
    $stmt = $pdo->prepare("UPDATE users SET role = 'seller' WHERE user_id = ?");
    if($stmt->execute([$_SESSION['user_id']])) {
        $_SESSION['user_role'] = 'seller';
        $_SESSION['success_message'] = 'Selamat! Anda sekarang adalah seller. Silakan upload jasa Anda.';
        header('Location: upload-service.php');
        exit();
    }
}

// Handle service upload
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_service'])) {
    $name = trim($_POST['name']);
    $subcategory = $_POST['subcategory'];
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $discounted_price = !empty($_POST['discounted_price']) ? floatval($_POST['discounted_price']) : null;
    $location = trim($_POST['location']);
    $delivery_option = $_POST['delivery_option'] ?? 'both';
    $delivery_fee = floatval($_POST['delivery_fee'] ?? 0);
    
    // Fields khusus jastip
    $po_close = trim($_POST['po_close'] ?? '');
    $delivery_time = trim($_POST['delivery_time'] ?? '');
    $free_ongkir = isset($_POST['free_ongkir']) ? true : false;
    $payment_methods = $_POST['payment_methods'] ?? [];
    
    // Fields khusus kursus
    $mentor_experience = trim($_POST['mentor_experience'] ?? '');
    $session_duration = trim($_POST['session_duration'] ?? '');
    $includes_certificate = isset($_POST['includes_certificate']) ? true : false;
    
    $errors = [];
    
    // Validasi
    if(empty($name)) $errors[] = 'Nama jasa harus diisi';
    if(empty($subcategory)) $errors[] = 'Kategori jasa harus dipilih';
    if(empty($description)) $errors[] = 'Deskripsi jasa harus diisi';
    if($price <= 0) $errors[] = 'Harga harus lebih dari 0';
    if(empty($location)) $errors[] = 'Lokasi harus diisi';
    
    // Build deskripsi lengkap berdasarkan kategori
    $full_description = $description;
    
    if($subcategory == 'jastip') {
        $full_description .= "\n\n=== DETAIL JASTIP ===\n";
        if($po_close) $full_description .= "Close PO: $po_close\n";
        if($delivery_time) $full_description .= "Pengantaran: $delivery_time\n";
        if($free_ongkir) $full_description .= "FREE ONGKIR!!!\n";
        if(!empty($payment_methods)) {
            $full_description .= "Pembayaran: " . implode(', ', $payment_methods) . "\n";
        }
    }
    
    if($subcategory == 'courses') {
        $full_description .= "\n\n=== DETAIL KURSUS ===\n";
        if($mentor_experience) $full_description .= "Mentor: $mentor_experience\n";
        if($session_duration) $full_description .= "Durasi: $session_duration\n";
        if($includes_certificate) $full_description .= "✓ Termasuk Sertifikat\n";
        $full_description .= "✓ Komunitas belajar aktif\n";
        $full_description .= "✓ Modul pembelajaran eksklusif\n";
    }
    
    if($subcategory == 'repair') {
        $full_description .= "\n\n=== DETAIL SERVICE ===\n";
        $full_description .= "✓ Menerima berbagai macam kerusakan\n";
        if($discounted_price) {
            $discount = round((($price - $discounted_price) / $price) * 100);
            $full_description .= "✓ Diskon $discount% untuk semua jenis kerusakan\n";
        }
    }
    
    if($subcategory == 'cleaning') {
        $full_description .= "\n\n=== DETAIL CLEANING ===\n";
        $full_description .= "✓ Jasa cleaning room untuk mahasiswa\n";
        $full_description .= "✓ Peralatan lengkap\n";
        $full_description .= "✓ Hasil bersih dan rapi\n";
    }
    
    // Upload gambar
    $image_name = '';
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            if($_FILES['image']['size'] <= 5 * 1024 * 1024) {
                $new_filename = 'service_' . uniqid() . '.' . $ext;
                $upload_path = '../' . UPLOAD_PATH . $new_filename;
                
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
        // Default image berdasarkan kategori
        $default_images = [
            'jastip' => 'default-jastip.jpg',
            'courses' => 'default-courses.jpg',
            'repair' => 'default-repair.jpg',
            'beauty' => 'default-beauty.jpg',
            'cleaning' => 'default-cleaning.jpg',
            'design' => 'default-design.jpg',
            'laundry' => 'default-laundry.jpg',
            'photography' => 'default-photo.jpg',
            'tutoring' => 'default-tutoring.jpg',
            'other' => 'default-service.jpg'
        ];
        $image_name = $default_images[$subcategory] ?? 'default-service.jpg';
    }
    
    if(empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products 
                (seller_id, name, category, subcategory, description, price, discounted_price, 
                 quantity, image, location, delivery_option, delivery_fee, created_at) 
                VALUES (?, ?, 'service', ?, ?, ?, ?, 999, ?, ?, ?, ?, NOW())
            ");
            
            if($stmt->execute([
                $_SESSION['user_id'], $name, $subcategory, $full_description, $price, $discounted_price,
                $image_name, $location, $delivery_option, $delivery_fee
            ])) {
                $product_id = $pdo->lastInsertId();
                $_SESSION['success_message'] = 'Jasa berhasil diupload!';
                header("Location: product-detail.php?id=$product_id");
                exit();
            }
        } catch(PDOException $e) {
            error_log("Upload Service Error: " . $e->getMessage());
            $errors[] = 'Gagal menyimpan jasa. Silakan coba lagi.';
        }
    }
}

// Get service categories
$service_categories = [
    'jastip' => [
        'name' => 'Jastip Food',
        'icon' => 'fa-shopping-bag',
        'description' => 'Titip beli makanan, minuman, atau barang',
        'placeholder' => 'Contoh: JASTIP GACOAN, JASTIP DONATSU, JASTIP CHEESE CUT'
    ],
    'courses' => [
        'name' => 'Courses & Tutoring',
        'icon' => 'fa-graduation-cap',
        'description' => 'Kursus, bimbingan belajar, les privat',
        'placeholder' => 'Contoh: Calculus Course, Statistics Course, Coding Class'
    ],
    'repair' => [
        'name' => 'Repair & Maintenance',
        'icon' => 'fa-tools',
        'description' => 'Service laptop, HP, elektronik, dll',
        'placeholder' => 'Contoh: Service Laptop, Service HP, Service Gadget'
    ],
    'beauty' => [
        'name' => 'Beauty & Nail Art',
        'icon' => 'fa-spa',
        'description' => 'Nail art, makeup, perawatan kecantikan',
        'placeholder' => 'Contoh: Nail Art, Makeup Artist, Facial Treatment'
    ],
    'cleaning' => [
        'name' => 'Cleaning Service',
        'icon' => 'fa-broom',
        'description' => 'Jasa kebersihan kamar, kost, rumah',
        'placeholder' => 'Contoh: Cleaning Service, Laundry, Cuci Sepatu'
    ],
    'design' => [
        'name' => 'Design & Creative',
        'icon' => 'fa-paint-brush',
        'description' => 'Desain grafis, editing video, photography',
        'placeholder' => 'Contoh: Desain Logo, Edit Video, Fotografi'
    ],
    'laundry' => [
        'name' => 'Laundry',
        'icon' => 'fa-tshirt',
        'description' => 'Jasa laundry, cuci setrika, dry clean',
        'placeholder' => 'Contoh: Laundry Kiloan, Cuci Setrika, Sepatu'
    ],
    'photography' => [
        'name' => 'Photography',
        'icon' => 'fa-camera',
        'description' => 'Jasa foto, video, editing',
        'placeholder' => 'Contoh: Wedding Photography, Product Photography'
    ],
    'tutoring' => [
        'name' => 'Tutoring',
        'icon' => 'fa-chalkboard-teacher',
        'description' => 'Les privat, bimbingan belajar',
        'placeholder' => 'Contoh: Les Matematika, Les Bahasa Inggris'
    ],
    'other' => [
        'name' => 'Other Services',
        'icon' => 'fa-hands-helping',
        'description' => 'Jasa lainnya',
        'placeholder' => 'Contoh: Jasa Titip, Jasa Antar, dll'
    ]
];

include '../header.php';
?>

<div class="upload-service-container">
    <?php if(!$is_seller): ?>
    <!-- UPGRADE SELLER CARD -->
    <div class="upgrade-seller-card">
        <div class="upgrade-icon">
            <i class="fas fa-crown"></i>
        </div>
        <h2>Jadi Seller Sekarang!</h2>
        <p>Upload jasa, kursus, atau jastip dan mulai dapatkan penghasilan dari komunitas mahasiswa President University.</p>
        <div class="benefits-grid">
            <div class="benefit">
                <i class="fas fa-check-circle"></i>
                <span>Gratis selamanya</span>
            </div>
            <div class="benefit">
                <i class="fas fa-check-circle"></i>
                <span>Jangkau 1000+ mahasiswa</span>
            </div>
            <div class="benefit">
                <i class="fas fa-check-circle"></i>
                <span>Terima pembayaran via WA</span>
            </div>
            <div class="benefit">
                <i class="fas fa-check-circle"></i>
                <span>Dukungan 24/7</span>
            </div>
        </div>
        <form method="POST">
            <button type="submit" name="upgrade_to_seller" class="btn-upgrade-seller">
                <i class="fas fa-store"></i>
                Upgrade ke Seller Sekarang
            </button>
        </form>
        <p class="upgrade-note">*Gratis, tidak dipungut biaya apapun</p>
    </div>
    
    <?php else: ?>
    <!-- UPLOAD SERVICE FORM -->
    <div class="upload-service-card">
        <div class="upload-header">
            <h1>Upload Jasa & Layanan</h1>
            <p>Bagikan keahlianmu dan dapatkan penghasilan tambahan</p>
        </div>
        
        <?php if(!empty($errors)): ?>
        <div class="error-alert">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Gagal mengupload jasa:</strong>
                <ul>
                    <?php foreach($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data" class="upload-service-form">
            <input type="hidden" name="upload_service" value="1">
            
            <!-- STEP 1: KATEGORI JASA -->
            <div class="form-step">
                <div class="step-header">
                    <div class="step-number">1</div>
                    <h3>Pilih Kategori Jasa</h3>
                </div>
                
                <div class="category-grid">
                    <?php foreach($service_categories as $key => $cat): ?>
                    <label class="category-option">
                        <input type="radio" name="subcategory" value="<?php echo $key; ?>" required>
                        <div class="category-card">
                            <div class="category-icon">
                                <i class="fas <?php echo $cat['icon']; ?>"></i>
                            </div>
                            <h4><?php echo $cat['name']; ?></h4>
                            <p><?php echo $cat['description']; ?></p>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- STEP 2: INFORMASI DASAR -->
            <div class="form-step">
                <div class="step-header">
                    <div class="step-number">2</div>
                    <h3>Informasi Dasar</h3>
                </div>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label>Nama Jasa <span class="required">*</span></label>
                        <input type="text" name="name" id="serviceName" 
                               placeholder="Contoh: JASTIP GACOAN, Calculus Course, Service Laptop" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        <small class="form-hint">Gunakan nama yang mudah diingat dan dicari</small>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Deskripsi Jasa <span class="required">*</span></label>
                        <textarea name="description" id="serviceDesc" rows="4" 
                                  placeholder="Jelaskan jasa yang kamu tawarkan secara detail..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <div class="char-counter">
                            <span id="charCount">0</span>/500 karakter
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Harga <span class="required">*</span></label>
                        <div class="price-input">
                            <span class="currency">Rp</span>
                            <input type="number" name="price" placeholder="0" 
                                   value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
                                   min="0" step="1000" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Harga Diskon (opsional)</label>
                        <div class="price-input">
                            <span class="currency">Rp</span>
                            <input type="number" name="discounted_price" placeholder="0" 
                                   value="<?php echo htmlspecialchars($_POST['discounted_price'] ?? ''); ?>" 
                                   min="0" step="1000">
                        </div>
                        <small class="form-hint">Kosongkan jika tidak ada diskon</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Lokasi <span class="required">*</span></label>
                        <input type="text" name="location" placeholder="Contoh: SBH Tower A, President University" 
                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Metode Pengiriman</label>
                        <select name="delivery_option">
                            <option value="both">Bisa Ambil/Diantar</option>
                            <option value="pickup">Ambil Sendiri</option>
                            <option value="delivery">Diantar</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Biaya Pengiriman</label>
                        <div class="price-input">
                            <span class="currency">Rp</span>
                            <input type="number" name="delivery_fee" placeholder="0" 
                                   value="<?php echo htmlspecialchars($_POST['delivery_fee'] ?? '0'); ?>" 
                                   min="0" step="1000">
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- STEP 3: DETAIL JASA (DINAMIS) -->
            <div class="form-step" id="jastipFields" style="display: none;">
                <div class="step-header">
                    <div class="step-number">3</div>
                    <h3>Detail Jastip</h3>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Close PO</label>
                        <input type="text" name="po_close" placeholder="Contoh: 19.15">
                    </div>
                    
                    <div class="form-group">
                        <label>Waktu Pengantaran</label>
                        <input type="text" name="delivery_time" placeholder="Contoh: 19.40">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-label">
                            <input type="checkbox" name="free_ongkir" id="freeOngkir">
                            <label for="freeOngkir">FREE ONGKIR</label>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Metode Pembayaran</label>
                        <div class="checkbox-group">
                            <label class="checkbox-item">
                                <input type="checkbox" name="payment_methods[]" value="QRIS">
                                <span>QRIS</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="payment_methods[]" value="Transfer Bank">
                                <span>Transfer Bank</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="payment_methods[]" value="Cash">
                                <span>Cash</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="payment_methods[]" value="OVO">
                                <span>OVO</span>
                            </label>
                            <label class="checkbox-item">
                                <input type="checkbox" name="payment_methods[]" value="GoPay">
                                <span>GoPay</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-step" id="coursesFields" style="display: none;">
                <div class="step-header">
                    <div class="step-number">3</div>
                    <h3>Detail Kursus</h3>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Pengalaman Mentor</label>
                        <input type="text" name="mentor_experience" placeholder="Contoh: 5 tahun berpengalaman">
                    </div>
                    
                    <div class="form-group">
                        <label>Durasi Sesi</label>
                        <input type="text" name="session_duration" placeholder="Contoh: 90 menit">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-label">
                            <input type="checkbox" name="includes_certificate" id="includesCertificate">
                            <label for="includesCertificate">Termasuk Sertifikat</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- STEP 4: GAMBAR JASA -->
            <div class="form-step">
                <div class="step-header">
                    <div class="step-number">4</div>
                    <h3>Gambar Jasa</h3>
                </div>
                
                <div class="form-group full-width">
                    <div class="image-upload-area" id="imageUploadArea">
                        <div class="upload-prompt">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <h4>Upload Gambar Jasa</h4>
                            <p>Klik atau drag & drop gambar di sini</p>
                            <span class="upload-hint">Format: JPG, PNG, GIF, WebP (Maks. 5MB)</span>
                        </div>
                        <input type="file" name="image" id="imageInput" accept="image/*">
                    </div>
                    <div class="image-preview" id="imagePreview"></div>
                    <small class="form-hint">*Kosongkan untuk menggunakan gambar default</small>
                </div>
            </div>
            
            <!-- SUBMIT BUTTON -->
            <div class="form-actions">
                <button type="submit" class="btn-submit-service">
                    <i class="fas fa-cloud-upload-alt"></i>
                    Upload Jasa
                </button>
                <a href="service.php" class="btn-cancel-service">
                    Batal
                </a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<script>
// Show/hide dynamic fields based on category
document.querySelectorAll('input[name="subcategory"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const jastipFields = document.getElementById('jastipFields');
        const coursesFields = document.getElementById('coursesFields');
        
        jastipFields.style.display = 'none';
        coursesFields.style.display = 'none';
        
        if(this.value === 'jastip') {
            jastipFields.style.display = 'block';
        } else if(this.value === 'courses' || this.value === 'tutoring') {
            coursesFields.style.display = 'block';
        }
    });
});

// Character counter
const descTextarea = document.getElementById('serviceDesc');
if(descTextarea) {
    descTextarea.addEventListener('input', function() {
        const count = this.value.length;
        document.getElementById('charCount').textContent = count;
    });
}

// Image upload preview
const uploadArea = document.getElementById('imageUploadArea');
const imageInput = document.getElementById('imageInput');
const imagePreview = document.getElementById('imagePreview');

uploadArea.addEventListener('click', () => imageInput.click());

uploadArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadArea.classList.add('dragover');
});

uploadArea.addEventListener('dragleave', () => {
    uploadArea.classList.remove('dragover');
});

uploadArea.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadArea.classList.remove('dragover');
    if(e.dataTransfer.files.length) {
        imageInput.files = e.dataTransfer.files;
        handleImagePreview(e.dataTransfer.files[0]);
    }
});

imageInput.addEventListener('change', function() {
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
</script>

<style>
/* ===== UPLOAD SERVICE PAGE STYLES ===== */
.upload-service-container {
    max-width: 1000px;
    margin: 40px auto;
    padding: 0 20px;
}

/* UPGRADE SELLER CARD */
.upgrade-seller-card {
    background: linear-gradient(135deg, #FFF9E6, #FFF3CD);
    border-radius: 30px;
    padding: 50px 40px;
    text-align: center;
    border: 2px solid #FFD700;
    max-width: 700px;
    margin: 0 auto;
}

.upgrade-icon {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: linear-gradient(135deg, #FFD700, #FFA500);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
}

.upgrade-icon i {
    font-size: 48px;
    color: white;
}

.upgrade-seller-card h2 {
    font-size: 32px;
    font-weight: 800;
    color: #2C2416;
    margin-bottom: 15px;
}

.upgrade-seller-card p {
    color: #6D6D6D;
    font-size: 16px;
    margin-bottom: 30px;
}

.benefits-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
    margin-bottom: 35px;
}

.benefit {
    display: flex;
    align-items: center;
    gap: 10px;
    color: #2C2416;
    font-size: 14px;
}

.benefit i {
    color: #28A745;
}

.btn-upgrade-seller {
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

.btn-upgrade-seller:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(76,60,39,0.2);
}

.upgrade-note {
    font-size: 12px;
    color: #999;
    margin-top: 20px;
}

/* UPLOAD SERVICE CARD */
.upload-service-card {
    background: white;
    border-radius: 30px;
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

/* ERROR ALERT */
.error-alert {
    background: #FEF2F2;
    border-left: 4px solid #DC3545;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
    display: flex;
    gap: 15px;
}

.error-alert i {
    font-size: 24px;
    color: #DC3545;
}

.error-alert strong {
    color: #DC3545;
    display: block;
    margin-bottom: 8px;
}

.error-alert ul {
    margin-left: 20px;
    color: #DC3545;
}

/* FORM STEPS */
.form-step {
    margin-bottom: 40px;
    padding-bottom: 40px;
    border-bottom: 1px solid #F0EDE5;
}

.form-step:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.step-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 25px;
}

.step-number {
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

.step-header h3 {
    font-size: 20px;
    font-weight: 600;
    color: #2C2416;
}

/* CATEGORY GRID */
.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 15px;
}

.category-option {
    cursor: pointer;
}

.category-option input[type="radio"] {
    display: none;
}

.category-card {
    background: white;
    border: 2px solid #E8E3D9;
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s;
}

.category-option input[type="radio"]:checked + .category-card {
    border-color: #4C3C27;
    background: #F5F3EE;
}

.category-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #F5F3EE;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
}

.category-icon i {
    font-size: 24px;
    color: #4C3C27;
}

.category-card h4 {
    font-size: 15px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 6px;
}

.category-card p {
    font-size: 12px;
    color: #6D6D6D;
}

/* FORM GRID */
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
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
}

.required {
    color: #DC3545;
}

input, select, textarea {
    padding: 12px 16px;
    border: 1.5px solid #E8E3D9;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s;
}

input:focus, select:focus, textarea:focus {
    border-color: #C9B59C;
    outline: none;
    box-shadow: 0 0 0 4px rgba(201,181,156,0.1);
}

.form-hint {
    font-size: 12px;
    color: #999;
}

/* PRICE INPUT */
.price-input {
    display: flex;
    align-items: center;
    border: 1.5px solid #E8E3D9;
    border-radius: 12px;
    overflow: hidden;
}

.price-input:focus-within {
    border-color: #C9B59C;
    box-shadow: 0 0 0 4px rgba(201,181,156,0.1);
}

.currency {
    padding: 12px 16px;
    background: #F5F3EE;
    color: #4C3C27;
    font-weight: 600;
    border-right: 1.5px solid #E8E3D9;
}

.price-input input {
    border: none;
    border-radius: 0;
    padding: 12px 16px;
    flex: 1;
}

/* CHECKBOX GROUP */
.checkbox-group {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
}

.checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.checkbox-item input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin: 0;
}

/* IMAGE UPLOAD */
.image-upload-area {
    border: 2px dashed #E8E3D9;
    border-radius: 16px;
    padding: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #F9F7F4;
    min-height: 200px;
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
    font-size: 12px;
    color: #999;
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
    border: 2px solid #4C3C27;
}

.preview-item img {
    max-width: 300px;
    max-height: 200px;
    object-fit: cover;
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
}

/* FORM ACTIONS */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 40px;
}

.btn-submit-service {
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

.btn-submit-service:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 30px rgba(76,60,39,0.2);
}

.btn-cancel-service {
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

.btn-cancel-service:hover {
    background: #F5F3EE;
    border-color: #C9B59C;
    color: #2C2416;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .upload-service-card {
        padding: 30px 20px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group.full-width {
        grid-column: span 1;
    }
    
    .category-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .benefits-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../footer.php'; ?>