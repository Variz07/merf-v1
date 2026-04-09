<?php
require_once '../config.php';

// Redirect if already logged in
if(isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}

$page_title = 'Daftar Akun';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $errors = [];
    
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $student_id = trim($_POST['student_id']);
    $university = trim($_POST['university']);
    $is_student = $_POST['is_student'] ?? 'yes';
    $dob = $_POST['dob'];
    $gender = $_POST['gender'] ?? '';
    $address = trim($_POST['address']);
    $dorm_address = trim($_POST['dorm_address']);
    $bio = trim($_POST['bio']);
    
    // Validation rules
    if(empty($full_name)) $errors[] = 'Nama lengkap harus diisi';
    if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
    if(empty($password)) $errors[] = 'Password harus diisi';
    if(strlen($password) < 6) $errors[] = 'Password minimal 6 karakter';
    if($password !== $confirm_password) $errors[] = 'Konfirmasi password tidak sama';
    if($is_student == 'yes' && empty($dorm_address)) $errors[] = 'Alamat asrama/kost wajib diisi untuk mahasiswa';
    
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if($stmt->fetchColumn() > 0) {
        $errors[] = 'Email sudah terdaftar';
    }
    
    // Handle profile picture upload
    $profile_pic = 'default-profile.png';
    if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        
        if(in_array($file_ext, $allowed_types)) {
            if($_FILES['profile_pic']['size'] <= MAX_UPLOAD_SIZE) {
                $file_name = uniqid('profile_') . '.' . $file_ext;
                $upload_path = '../' . UPLOAD_PATH . $file_name;
                
                if(move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                    $profile_pic = $file_name;
                }
            } else {
                $errors[] = 'Ukuran foto terlalu besar. Maksimal 5MB';
            }
        } else {
            $errors[] = 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF';
        }
    }
    
    if(empty($errors)) {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Set role (default to customer, can upgrade to seller later)
        $role = 'customer';
        
        // Insert into database
        $stmt = $pdo->prepare("INSERT INTO users 
                              (full_name, email, phone, password, student_id, university, is_student, 
                               dob, gender, address, dorm_address, profile_pic, bio, role) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        try {
            $stmt->execute([
                $full_name, $email, $phone, $hashed_password, $student_id, $university, $is_student,
                $dob, $gender, $address, $dorm_address, $profile_pic, $bio, $role
            ]);
            
            // Get user ID
            $user_id = $pdo->lastInsertId();
            
            // Create session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['user_name'] = $full_name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = $role;
            $_SESSION['profile_pic'] = $profile_pic;
            
            // Set welcome notification
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message) 
                                  VALUES (?, 'system', 'Selamat Datang!', 'Terima kasih telah bergabung dengan MERF Marketplace')");
            $stmt->execute([$user_id]);
            
            // Redirect to homepage
            $_SESSION['success_message'] = 'Pendaftaran berhasil! Selamat datang di MERF Marketplace';
            header('Location: ../index.php');
            exit();
            
        } catch(PDOException $e) {
            $errors[] = 'Terjadi kesalahan saat mendaftar. Silakan coba lagi.';
        }
    }
}

include '../header.php';
?>

<div class="container">
    <div class="form-container">
        <h2 class="form-title">Daftar Akun Baru</h2>
        <p class="text-center mb-4">Bergabunglah dengan komunitas mahasiswa President University</p>
        
        <?php if(!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <!-- Personal Information -->
            <div class="form-section">
                <h3 class="form-section-title">Informasi Pribadi</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Foto Profil *</label>
                        <div class="file-upload" id="profileUpload">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Klik untuk upload foto</p>
                            <p class="form-text">Format: JPG, PNG, GIF | Maks: 5MB</p>
                            <input type="file" name="profile_pic" id="profileInput" accept="image/*">
                        </div>
                        <div class="image-preview mt-2" id="imagePreview"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="full_name" class="form-control" required value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nomor Telepon</label>
                        <input type="tel" name="phone" class="form-control" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="dob" class="form-control" value="<?php echo isset($_POST['dob']) ? htmlspecialchars($_POST['dob']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Jenis Kelamin</label>
                        <div class="form-check-group">
                            <label class="form-check">
                                <input type="radio" name="gender" value="Male" <?php echo isset($_POST['gender']) && $_POST['gender'] == 'Male' ? 'checked' : ''; ?>>
                                <span>Laki-laki</span>
                            </label>
                            <label class="form-check">
                                <input type="radio" name="gender" value="Female" <?php echo isset($_POST['gender']) && $_POST['gender'] == 'Female' ? 'checked' : ''; ?>>
                                <span>Perempuan</span>
                            </label>
                            <label class="form-check">
                                <input type="radio" name="gender" value="Other" <?php echo isset($_POST['gender']) && $_POST['gender'] == 'Other' ? 'checked' : ''; ?>>
                                <span>Lainnya</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Bio Singkat</label>
                    <textarea name="bio" class="form-control" rows="3" placeholder="Ceritakan sedikit tentang diri Anda..."><?php echo isset($_POST['bio']) ? htmlspecialchars($_POST['bio']) : ''; ?></textarea>
                </div>
            </div>
            
            <!-- University Information -->
            <div class="form-section">
                <h3 class="form-section-title">Informasi Pendidikan</h3>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <div class="form-check-group">
                        <label class="form-check">
                            <input type="radio" name="is_student" value="yes" checked <?php echo isset($_POST['is_student']) && $_POST['is_student'] == 'yes' ? 'checked' : ''; ?>>
                            <span>Masih Berkulia</span>
                        </label>
                        <label class="form-check">
                            <input type="radio" name="is_student" value="no" <?php echo isset($_POST['is_student']) && $_POST['is_student'] == 'no' ? 'checked' : ''; ?>>
                            <span>Tidak Berkulia</span>
                        </label>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nomor Induk Mahasiswa (NIM)</label>
                        <input type="text" name="student_id" class="form-control" value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>" placeholder="Opsional untuk mahasiswa PU">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Universitas</label>
                        <input type="text" name="university" class="form-control" value="<?php echo isset($_POST['university']) ? htmlspecialchars($_POST['university']) : ''; ?>" placeholder="President University">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Alamat Asrama/Kost *</label>
                    <textarea name="dorm_address" class="form-control" rows="2" placeholder="Alamat lengkap asrama atau kost Anda" required><?php echo isset($_POST['dorm_address']) ? htmlspecialchars($_POST['dorm_address']) : ''; ?></textarea>
                    <p class="form-text">Wajib diisi untuk memudahkan pengiriman</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Alamat Rumah (Asal)</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Alamat rumah di kota asal"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
            </div>
            
            <!-- Account Security -->
            <div class="form-section">
                <h3 class="form-section-title">Keamanan Akun</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password *</label>
                        <input type="password" name="password" class="form-control" required>
                        <p class="form-text">Minimal 6 karakter</p>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
            </div>
            
            <!-- Terms & Conditions -->
            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="terms" required>
                    <span>Saya menyetujui <a href="../pages/terms.php" target="_blank">Syarat & Ketentuan</a> dan <a href="../pages/privacy.php" target="_blank">Kebijakan Privasi</a></span>
                </label>
            </div>
            
            <!-- Submit Button -->
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Daftar Sekarang</button>
            </div>
            
            <div class="text-center">
                <p>Sudah punya akun? <a href="signin.php">Masuk di sini</a></p>
            </div>
        </form>
    </div>
</div>

<style>
/* ===== SIGNUP PAGE STYLES - ELEGANT & CLEAN ===== */
.signup-page {
    background: #F9F7F4;
    min-height: 100vh;
    padding: 40px 0;
}

.form-container {
    max-width: 800px;
    margin: 0 auto;
    background: white;
    border-radius: 24px;
    box-shadow: 0 10px 30px rgba(76,60,39,0.05);
    border: 1px solid #E8E3D9;
    overflow: hidden;
    padding: 40px;
}

.form-title {
    font-size: 28px;
    font-weight: 700;
    color: #4C3C27;
    margin-bottom: 10px;
    text-align: center;
    position: relative;
    padding-bottom: 15px;
}

.form-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 80px;
    height: 3px;
    background: linear-gradient(90deg, #4C3C27, #C9B59C);
    border-radius: 3px;
}

.text-center {
    text-align: center;
    color: #6D6D6D;
    margin-bottom: 30px;
    font-size: 16px;
}

/* FORM SECTIONS */
.form-section {
    margin-bottom: 35px;
    padding-bottom: 25px;
    border-bottom: 1px solid #F0EDE5;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.form-section-title {
    font-size: 18px;
    font-weight: 600;
    color: #4C3C27;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-section-title::before {
    content: '';
    width: 4px;
    height: 20px;
    background: linear-gradient(to bottom, #4C3C27, #C9B59C);
    border-radius: 2px;
    display: inline-block;
}

/* FORM ROWS & GROUPS */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-label {
    font-size: 14px;
    font-weight: 600;
    color: #2C2416;
    display: flex;
    align-items: center;
    gap: 4px;
}

.form-label .required {
    color: #DC3545;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 1.5px solid #E8E3D9;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s;
    background: white;
}

.form-control:focus {
    border-color: #C9B59C;
    outline: none;
    box-shadow: 0 0 0 4px rgba(201,181,156,0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 80px;
}

/* FORM HINT */
.form-text {
    font-size: 12px;
    color: #999;
    margin-top: 4px;
}

/* RADIO & CHECKBOX GROUPS */
.form-check-group {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    padding: 5px 0;
}

.form-check {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

.form-check input[type="radio"],
.form-check input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: #4C3C27;
    margin: 0;
}

.form-check span {
    font-size: 14px;
    color: #2C2416;
}

/* FILE UPLOAD */
.file-upload {
    border: 2px dashed #E8E3D9;
    border-radius: 16px;
    padding: 30px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #F9F7F4;
}

.file-upload:hover {
    border-color: #4C3C27;
    background: #F5F3EE;
}

.file-upload i {
    font-size: 48px;
    color: #C9B59C;
    margin-bottom: 10px;
}

.file-upload p {
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 5px;
}

.file-upload .form-text {
    margin: 0;
}

.file-upload input[type="file"] {
    display: none;
}

.image-preview {
    text-align: center;
    margin-top: 15px;
}

.image-preview img {
    max-width: 150px;
    border-radius: 12px;
    border: 2px solid #4C3C27;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

/* ALERTS */
.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
}

.alert-error {
    background: #FFEBEE;
    color: #C62828;
    border-left: 4px solid #C62828;
}

.alert ul {
    margin-left: 20px;
}

/* BUTTONS */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 28px;
    border-radius: 40px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    border: none;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 24px rgba(76,60,39,0.2);
}

.btn-block {
    width: 100%;
}

/* LINKS */
.text-center a {
    color: #4C3C27;
    font-weight: 600;
    text-decoration: none;
}

.text-center a:hover {
    text-decoration: underline;
}

/* RESPONSIVE */
@media (max-width: 768px) {
    .form-container {
        padding: 30px 20px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .form-section-title {
        font-size: 16px;
    }
    
    .btn {
        padding: 12px 24px;
    }
}

@media (max-width: 480px) {
    .form-container {
        padding: 20px 15px;
    }
    
    .form-title {
        font-size: 24px;
    }
}
</style>


<script>
// Profile image preview
document.getElementById('profileInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').innerHTML = `
                <img src="${e.target.result}" alt="Preview" style="max-width: 150px; border-radius: 10px;">
            `;
        }
        reader.readAsDataURL(file);
    }
});

// File upload click
document.getElementById('profileUpload').addEventListener('click', function() {
    document.getElementById('profileInput').click();
});
</script>

<?php include '../footer.php'; ?>