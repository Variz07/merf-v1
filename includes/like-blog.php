<?php
require_once '../config.php';

header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Silakan login terlebih dahulu']);
    exit();
}

$blog_id = $_POST['blog_id'] ?? 0;

if(!$blog_id) {
    echo json_encode(['success' => false, 'message' => 'Blog tidak valid']);
    exit();
}

// Check if blog exists
$stmt = $pdo->prepare("SELECT blog_id FROM blogs WHERE blog_id = ? AND is_published = 1");
$stmt->execute([$blog_id]);
$blog = $stmt->fetch();

if(!$blog) {
    echo json_encode(['success' => false, 'message' => 'Blog tidak ditemukan']);
    exit();
}

// Check if already liked
$stmt = $pdo->prepare("SELECT like_id FROM blog_likes WHERE blog_id = ? AND user_id = ?");
$stmt->execute([$blog_id, $_SESSION['user_id']]);
$like = $stmt->fetch();

if($like) {
    // Remove like
    $stmt = $pdo->prepare("DELETE FROM blog_likes WHERE blog_id = ? AND user_id = ?");
    $result = $stmt->execute([$blog_id, $_SESSION['user_id']]);
    
    if($result) {
        // Update like count
        $stmt = $pdo->prepare("UPDATE blogs SET likes = likes - 1 WHERE blog_id = ?");
        $stmt->execute([$blog_id]);
        
        // Get updated count
        $count_stmt = $pdo->prepare("SELECT likes FROM blogs WHERE blog_id = ?");
        $count_stmt->execute([$blog_id]);
        $like_count = $count_stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'liked' => false,
            'like_count' => $like_count,
            'message' => 'Like dihapus'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menghapus like']);
    }
} else {
    // Add like
    $stmt = $pdo->prepare("INSERT INTO blog_likes (blog_id, user_id) VALUES (?, ?)");
    $result = $stmt->execute([$blog_id, $_SESSION['user_id']]);
    
    if($result) {
        // Update like count
        $stmt = $pdo->prepare("UPDATE blogs SET likes = likes + 1 WHERE blog_id = ?");
        $stmt->execute([$blog_id]);
        
        // Get updated count
        $count_stmt = $pdo->prepare("SELECT likes FROM blogs WHERE blog_id = ?");
        $count_stmt->execute([$blog_id]);
        $like_count = $count_stmt->fetchColumn();
        
        // Send notification to blog author
        $author_stmt = $pdo->prepare("
            SELECT user_id FROM blogs WHERE blog_id = ?
        ");
        $author_stmt->execute([$blog_id]);
        $author_id = $author_stmt->fetchColumn();
        
        if($author_id && $author_id != $_SESSION['user_id']) {
            $blog_stmt = $pdo->prepare("SELECT title FROM blogs WHERE blog_id = ?");
            $blog_stmt->execute([$blog_id]);
            $blog_title = $blog_stmt->fetchColumn();
            
            sendNotification(
                $author_id,
                'like',
                'Blog Disukai',
                'Blog Anda "' . $blog_title . '" disukai oleh ' . $_SESSION['user_name']
            );
        }
        
        echo json_encode([
            'success' => true,
            'liked' => true,
            'like_count' => $like_count,
            'message' => 'Blog disukai'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan like']);
    }
}
?>