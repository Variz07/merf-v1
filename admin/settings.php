<?php
require_once '../config.php';

// Check if user is admin
if(!isLoggedIn() || !isAdmin()) {
    redirect('../index.php', 'Akses ditolak', 'error');
}

$page_title = 'Pengaturan Website';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach($_POST as $key => $value) {
        if(strpos($key, 'setting_') === 0) {
            $setting_key = substr($key, 8);
            $setting_value = trim($value);
            
            $stmt = $pdo->prepare("
                INSERT INTO site_settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?
            ");
            
            $stmt->execute([$setting_key, $setting_value, $setting_value]);
        }
    }
    
    redirect('settings.php', 'Pengaturan berhasil disimpan');
}

// Get current settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM site_settings");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

include '../header.php';
?>

<div class="admin-container">
    <?php include 'admin-sidebar.php'; ?>
    
    <div class="admin-content">
        <!-- Page Header -->
        <div class="admin-header">
            <h1>Pengaturan Website</h1>
        </div>
        
        <!-- Settings Form -->
        <div class="admin-form">
            <form method="POST">
                <!-- General Settings -->
                <div class="form-section">
                    <h3 class="form-section-title">Pengaturan Umum</h3>
                    
                    <div class="form-group">
                        <label>Nama Website</label>
                        <input type="text" name="setting_site_name" 
                               value="<?php echo htmlspecialchars($settings['site_name'] ?? 'MERF Marketplace'); ?>" 
                               class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Deskripsi Website</label>
                        <textarea name="setting_site_description" class="form-control" rows="3"><?php 
                            echo htmlspecialchars($settings['site_description'] ?? 'Platform E-Commerce untuk Mahasiswa President University'); 
                        ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Admin</label>
                        <input type="email" name="setting_admin_email" 
                               value="<?php echo htmlspecialchars($settings['admin_email'] ?? 'admin@merf.com'); ?>" 
                               class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>Currency</label>
                        <input type="text" name="setting_currency" 
                               value="<?php echo htmlspecialchars($settings['currency'] ?? 'IDR'); ?>" 
                               class="form-control">
                    </div>
                </div>
                
                <!-- Registration Settings -->
                <div class="form-section">
                    <h3 class="form-section-title">Pengaturan Pendaftaran</h3>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="setting_enable_registration" value="true" 
                                   <?php echo ($settings['enable_registration'] ?? 'true') == 'true' ? 'checked' : ''; ?>>
                            Aktifkan Pendaftaran Pengguna Baru
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="setting_require_student_id" value="true"
                                   <?php echo ($settings['require_student_id'] ?? 'false') == 'true' ? 'checked' : ''; ?>>
                            Wajibkan NIM untuk Mahasiswa
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="setting_auto_verify_students" value="true"
                                   <?php echo ($settings['auto_verify_students'] ?? 'true') == 'true' ? 'checked' : ''; ?>>
                            Verifikasi Otomatis untuk Mahasiswa PU
                        </label>
                    </div>
                </div>
                
                <!-- Product Settings -->
                <div class="form-section">
                    <h3 class="form-section-title">Pengaturan Produk</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Harga Minimum Produk</label>
                            <input type="number" name="setting_min_product_price" 
                                   value="<?php echo htmlspecialchars($settings['min_product_price'] ?? '1000'); ?>" 
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Maksimal Gambar per Produk</label>
                            <input type="number" name="setting_max_product_images" 
                                   value="<?php echo htmlspecialchars($settings['max_product_images'] ?? '5'); ?>" 
                                   class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Ukuran Maksimal Upload (MB)</label>
                        <input type="number" name="setting_max_upload_size" 
                               value="<?php echo htmlspecialchars($settings['max_upload_size'] ?? '5'); ?>" 
                               class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="setting_auto_approve_products" value="true"
                                   <?php echo ($settings['auto_approve_products'] ?? 'true') == 'true' ? 'checked' : ''; ?>>
                            Setujui Produk Otomatis
                        </label>
                    </div>
                </div>
                
                <!-- Order Settings -->
                <div class="form-section">
                    <h3 class="form-section-title">Pengaturan Order</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Waktu Kadaluarsa Order Pending (menit)</label>
                            <input type="number" name="setting_order_expiry" 
                                   value="<?php echo htmlspecialchars($settings['order_expiry'] ?? '60'); ?>" 
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label>Maksimal Order per User per Hari</label>
                            <input type="number" name="setting_max_orders_per_day" 
                                   value="<?php echo htmlspecialchars($settings['max_orders_per_day'] ?? '10'); ?>" 
                                   class="form-control">
                        </div>
                    </div>
                </div>
                
                <!-- Maintenance Mode -->
                <div class="form-section">
                    <h3 class="form-section-title">Mode Maintenance</h3>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="setting_maintenance_mode" value="true"
                                   <?php echo ($settings['maintenance_mode'] ?? 'false') == 'true' ? 'checked' : ''; ?>
                                   onchange="toggleMaintenanceMessage(this)">
                            Aktifkan Mode Maintenance
                        </label>
                    </div>
                    
                    <div class="form-group" id="maintenanceMessageGroup" 
                         style="<?php echo ($settings['maintenance_mode'] ?? 'false') == 'true' ? '' : 'display: none;'; ?>">
                        <label>Pesan Maintenance</label>
                        <textarea name="setting_maintenance_message" class="form-control" rows="3"><?php 
                            echo htmlspecialchars($settings['maintenance_message'] ?? 'Website sedang dalam perawatan. Silakan kembali beberapa saat lagi.'); 
                        ?></textarea>
                    </div>
                </div>
                
                <!-- Submit Button -->
                <div class="form-section">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleMaintenanceMessage(checkbox) {
    const messageGroup = document.getElementById('maintenanceMessageGroup');
    if(checkbox.checked) {
        messageGroup.style.display = 'block';
    } else {
        messageGroup.style.display = 'none';
    }
}
</script>

<style>
.admin-form {
    background: var(--bg-card);
    border-radius: 10px;
    padding: 30px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.form-section-title {
    font-size: 18px;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 20px;
}

.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 15px;
}

.form-row .form-group {
    flex: 1;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--text-primary);
}

.form-group input[type="text"],
.form-group input[type="email"],
.form-group input[type="number"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(76, 60, 39, 0.1);
}

.form-group input[type="checkbox"] {
    margin-right: 8px;
}
</style>

<?php include '../footer.php'; ?>