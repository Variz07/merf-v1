<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => ''];

try {
    // Get current user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    // Update basic info
    if (isset($_POST['full_name']) && !empty($_POST['full_name'])) {
        $full_name = sanitize($_POST['full_name']);
        $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE user_id = ?");
        $stmt->execute([$full_name, $user_id]);
        $_SESSION['user_name'] = $full_name; // UPDATE SESSION
        $response['new_name'] = $full_name;
    }

    if (isset($_POST['bio'])) {
        $bio = sanitize($_POST['bio']);
        $stmt = $pdo->prepare("UPDATE users SET bio = ? WHERE user_id = ?");
        $stmt->execute([$bio, $user_id]);
        $response['new_bio'] = $bio;
    }

    if (isset($_POST['phone'])) {
        $phone = sanitize($_POST['phone']);
        $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE user_id = ?");
        $stmt->execute([$phone, $user_id]);
        $response['new_phone'] = $phone;
    }

    if (isset($_POST['location'])) {
        $location = sanitize($_POST['location']);
        $stmt = $pdo->prepare("UPDATE users SET dorm_address = ? WHERE user_id = ?");
        $stmt->execute([$location, $user_id]);
        $response['new_location'] = $location;
    }

    // Handle password change
    if (!empty($_POST['current_password']) && !empty($_POST['new_password'])) {
        if (!password_verify($_POST['current_password'], $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }
        
        if (strlen($_POST['new_password']) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters']);
            exit;
        }
        
        if ($_POST['new_password'] !== $_POST['confirm_password']) {
            echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
            exit;
        }
        
        $hashed = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashed, $user_id]);
    }

    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            if ($_FILES['profile_pic']['size'] <= 5 * 1024 * 1024) {
                // Generate unique filename
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path = '../' . UPLOAD_PATH . $new_filename;
                
                // Create directory if not exists
                $upload_dir = dirname($upload_path);
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Delete old photo if not default
                if ($user['profile_pic'] && $user['profile_pic'] != 'default-profile.png') {
                    $old_path = '../' . UPLOAD_PATH . $user['profile_pic'];
                    if (file_exists($old_path)) {
                        unlink($old_path);
                    }
                }
                
                // Upload new photo
                if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_path)) {
                    $stmt = $pdo->prepare("UPDATE users SET profile_pic = ? WHERE user_id = ?");
                    $stmt->execute([$new_filename, $user_id]);
                    
                    // UPDATE SESSION with new photo
                    $_SESSION['profile_pic'] = $new_filename;
                    
                    $response['new_avatar'] = SITE_URL . 'assets/images/uploads/' . $new_filename;
                    $response['new_filename'] = $new_filename;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB']);
                exit;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'File must be JPG, PNG, GIF, or WebP']);
            exit;
        }
    }
    
    // Handle photo removal
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1') {
        if ($user['profile_pic'] && $user['profile_pic'] != 'default-profile.png') {
            $old_path = '../' . UPLOAD_PATH . $user['profile_pic'];
            if (file_exists($old_path)) {
                unlink($old_path);
            }
        }
        
        $stmt = $pdo->prepare("UPDATE users SET profile_pic = 'default-profile.png' WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // UPDATE SESSION
        $_SESSION['profile_pic'] = 'default-profile.png';
        
        $response['new_avatar'] = SITE_URL . 'assets/images/default-profile.png';
    }

    // Refresh session data
    refreshUserSession($user_id);

    $response['success'] = true;
    $response['message'] = 'Profile updated successfully';
    $response['session'] = [
        'name' => $_SESSION['user_name'],
        'profile_pic' => $_SESSION['profile_pic']
    ];

} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

echo json_encode($response);
?>