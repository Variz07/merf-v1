<?php
require_once '../config.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

$allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$max_size = 5 * 1024 * 1024; // 5MB
$upload_dir = '../' . UPLOAD_PATH;

if(!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada file yang diupload']);
    exit();
}

$file = $_FILES['image'];
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Validate file type
if(!in_array($file_ext, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP']);
    exit();
}

// Validate file size
if($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 5MB']);
    exit();
}

// Validate file upload
if($file['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'File terlalu besar',
        UPLOAD_ERR_FORM_SIZE => 'File terlalu besar',
        UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
        UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
        UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file',
        UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP'
    ];
    
    $message = $error_messages[$file['error']] ?? 'Unknown error';
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// Create upload directory if not exists
if(!file_exists($upload_dir)) {
    if(!mkdir($upload_dir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Gagal membuat folder upload']);
        exit();
    }
}

// Generate unique filename
$filename = uniqid('img_') . '_' . time() . '.' . $file_ext;
$filepath = $upload_dir . $filename;

// Move uploaded file
if(move_uploaded_file($file['tmp_name'], $filepath)) {
    // Compress image if needed
    compressImage($filepath, $file_ext);
    
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'url' => UPLOAD_PATH . $filename,
        'message' => 'Gambar berhasil diupload'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file']);
}

function compressImage($source, $ext, $quality = 85) {
    $info = getimagesize($source);
    
    if($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
        imagejpeg($image, $source, $quality);
    } elseif($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
        imagepng($image, $source, 9); // PNG compression level (0-9)
    } elseif($info['mime'] == 'image/gif') {
        $image = imagecreatefromgif($source);
        imagegif($image, $source);
    } elseif($info['mime'] == 'image/webp') {
        $image = imagecreatefromwebp($source);
        imagewebp($image, $source, $quality);
    }
    
    if(isset($image)) {
        imagedestroy($image);
    }
}
?>