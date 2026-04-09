<?php
require_once '../config.php';

$page_title = 'Lupa Password';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    
    if(empty($email)) {
        $error = 'Email harus diisi';
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT user_id, full_name FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if($user) {
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in database
            $stmt = $pdo->prepare("
                INSERT INTO password_resets (email, token, expires_at) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE token = ?, expires_at = ?
            ");
            $stmt->execute([$email, $token, $expires, $token, $expires]);
            
            // Send email (simulated - in production use actual email)
            $reset_link = SITE_URL . "auth/reset-password.php?token=$token";
            $subject = "Reset Password - MERF Marketplace";
            $message = "
                <h2>Reset Password</h2>
                <p>Halo {$user['full_name']},</p>
                <p>Kami menerima permintaan reset password untuk akun Anda.</p>
                <p>Klik link berikut untuk reset password:</p>
                <p><a href='$reset_link'>$reset_link</a></p>
                <p>Link ini akan kadaluarsa dalam 1 jam.</p>
                <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
            ";
            
            // In production, use actual email sending
            // mail($email, $subject, $message, "From: no-reply@merf.com");
            
            $_SESSION['success_message'] = 'Link reset password telah dikirim ke email Anda. Silakan cek inbox atau spam folder.';
            redirect('signin.php');
        } else {
            $error = 'Email tidak terdaftar';
        }
    }
}

include '../header.php';
?>

<div class="container">
    <div class="form-container">
        <h2 class="form-title">Lupa Password</h2>
        <p class="text-center mb-4">Masukkan email Anda untuk menerima link reset password</p>
        
        <?php if(isset($error)): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-block">Kirim Link Reset</button>
            </div>
            
            <div class="text-center">
                <p>Ingat password? <a href="signin.php">Masuk di sini</a></p>
            </div>
        </form>
    </div>
</div>

<?php include '../footer.php'; ?>