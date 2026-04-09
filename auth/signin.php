<?php
require_once '../config.php';

// Redirect if already logged in
if(isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$page_title = 'Masuk ke Akun';

// Handle login
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    
    if(empty($email)) {
        $errors[] = 'Email harus diisi';
    }
    
    if(empty($password)) {
        $errors[] = 'Password harus diisi';
    }
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['profile_pic'] = $user['profile_pic'];
            
            // Update last login
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            
            // Redirect
            $_SESSION['success_message'] = 'Login berhasil! Selamat datang kembali.';
            header('Location: ../index.php');
            exit();
        } else {
            $errors[] = 'Email atau password salah';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - MERF Marketplace</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #4C3C27, #300C0C);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
            padding: 40px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #4C3C27, #300C0C);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 3px solid #C9B59C;
        }
        .logo-circle img {
            width: 50px;
            height: 50px;
        }
        .logo h1 {
            color: #4C3C27;
            font-size: 24px;
        }
        h2 {
            text-align: center;
            color: #2C2416;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #2C2416;
            font-weight: 500;
        }
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #E8E3D9;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s;
        }
        input:focus {
            border-color: #C9B59C;
            outline: none;
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #4C3C27, #300C0C);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(76,60,39,0.2);
        }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #4C3C27;
            text-decoration: none;
            font-size: 14px;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .error-box {
            background: #ffebee;
            color: #c62828;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .error-box ul {
            margin-left: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-circle">
                <img src="../assets/images/logo.png" alt="MERF" onerror="this.src='../assets/images/default-logo.png'">
            </div>
            <h1>MERF Marketplace</h1>
        </div>
        
        <h2>Selamat Datang Kembali</h2>
        <p class="subtitle">Masuk ke akun Anda</p>
        
        <?php if(!empty($errors)): ?>
        <div class="error-box">
            <ul>
                <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-login">Masuk</button>
            
            <div class="links">
                <p>Belum punya akun? <a href="signup.php">Daftar Sekarang</a></p>
                <p style="margin-top: 10px;"><a href="forgot-password.php">Lupa Password?</a></p>
            </div>
        </form>
    </div>
</body>
</html>