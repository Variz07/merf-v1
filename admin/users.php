<?php
require_once '../config.php';

// Check if user is admin
if(!isLoggedIn() || !isAdmin()) {
    redirect('../index.php', 'Akses ditolak', 'error');
}

$page_title = 'Kelola Pengguna';

// Handle actions
$action = $_GET['action'] ?? '';
$user_id = $_GET['id'] ?? 0;

if($action == 'delete' && $user_id) {
    // Prevent deleting yourself
    if($user_id == $_SESSION['user_id']) {
        redirect('users.php', 'Tidak dapat menghapus akun sendiri', 'error');
    }
    
    $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ?");
    if($stmt->execute([$user_id])) {
        redirect('users.php', 'Pengguna berhasil dinonaktifkan');
    }
}

if($action == 'activate' && $user_id) {
    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?");
    if($stmt->execute([$user_id])) {
        redirect('users.php', 'Pengguna berhasil diaktifkan');
    }
}

if($action == 'make_seller' && $user_id) {
    $stmt = $pdo->prepare("UPDATE users SET role = 'seller' WHERE user_id = ?");
    if($stmt->execute([$user_id])) {
        redirect('users.php', 'Pengguna berhasil dijadikan seller');
    }
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$sql = "SELECT * FROM users WHERE 1=1";
$params = [];

if($search) {
    $sql .= " AND (full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if($role) {
    $sql .= " AND role = ?";
    $params[] = $role;
}

if($status) {
    $sql .= " AND status = ?";
    $params[] = $status;
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM ($sql) as count_query";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_users = $stmt->fetchColumn();

// Add ordering and pagination
$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Execute
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Pengguna - Admin MERF</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <!-- Admin Sidebar -->
        <?php include 'admin-sidebar.php'; ?>
        
        <div class="admin-content">
            <!-- Page Header -->
            <div class="admin-header">
                <h1>Kelola Pengguna</h1>
                <div class="admin-actions">
                    <span>Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong></span>
                    <a href="../auth/logout.php" class="btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <!-- Filters -->
            <div class="admin-filters">
                <form method="GET" class="filter-form">
                    <div class="filter-row">
                        <input type="text" name="search" placeholder="Cari nama/email/telepon..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
                        
                        <select name="role" class="form-control">
                            <option value="">Semua Role</option>
                            <option value="customer" <?php echo $role == 'customer' ? 'selected' : ''; ?>>Customer</option>
                            <option value="seller" <?php echo $role == 'seller' ? 'selected' : ''; ?>>Seller</option>
                            <option value="admin" <?php echo $role == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                        
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="banned" <?php echo $status == 'banned' ? 'selected' : ''; ?>>Banned</option>
                        </select>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        
                        <a href="users.php" class="btn btn-outline">Reset</a>
                    </div>
                </form>
            </div>
            
            <!-- Users Table -->
            <div class="admin-table-container">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pengguna</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center" style="padding: 60px;">
                                <div class="empty-state">
                                    <i class="fas fa-users-slash"></i>
                                    <h3>Tidak ada pengguna</h3>
                                    <p>Belum ada pengguna yang ditemukan</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td><span style="font-weight: 600;">#<?php echo $user['user_id']; ?></span></td>
                            <td>
                                <div class="user-cell">
                                    <img src="<?php echo getUserAvatar($user['user_id']); ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>">
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                        <small><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role']; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-status-<?php echo $user['status']; ?>">
                                    <?php 
                                    echo $user['status'] == 'active' ? 'Aktif' : 
                                        ($user['status'] == 'inactive' ? 'Nonaktif' : 'Diblokir'); 
                                    ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="../pages/profile.php?id=<?php echo $user['user_id']; ?>" class="btn-action btn-view" title="Lihat Profil">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <a href="edit-user.php?id=<?php echo $user['user_id']; ?>" class="btn-action btn-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <?php if($user['status'] == 'active'): ?>
                                    <a href="?id=<?php echo $user['user_id']; ?>&action=deactivate" class="btn-action btn-warning" title="Nonaktifkan" onclick="return confirm('Yakin ingin menonaktifkan pengguna ini?')">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                    <?php else: ?>
                                    <a href="?id=<?php echo $user['user_id']; ?>&action=activate" class="btn-action btn-success" title="Aktifkan" onclick="return confirm('Yakin ingin mengaktifkan pengguna ini?')">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if($user['role'] == 'customer'): ?>
                                    <a href="?id=<?php echo $user['user_id']; ?>&action=make_seller" class="btn-action btn-info" title="Jadikan Seller" onclick="return confirm('Jadikan pengguna ini sebagai seller?')">
                                        <i class="fas fa-store"></i>
                                    </a>
                                    <?php endif; ?>
                                    
                                    <?php if($user['user_id'] != $_SESSION['user_id']): ?>
                                    <a href="?id=<?php echo $user['user_id']; ?>&action=delete" 
                                       class="btn-action btn-danger" 
                                       title="Hapus"
                                       onclick="return confirm('Yakin ingin menonaktifkan pengguna ini?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if($total_users > $limit): ?>
            <div class="admin-pagination">
                <?php
                $total_pages = ceil($total_users / $limit);
                $query_params = $_GET;
                unset($query_params['page']);
                $query_string = http_build_query($query_params);
                ?>
                
                <?php if($page > 1): ?>
                <a href="?<?php echo $query_string; ?>&page=<?php echo $page - 1; ?>" class="pagination-item">
                    <i class="fas fa-chevron-left"></i> Sebelumnya
                </a>
                <?php endif; ?>
                
                <div style="display: flex; gap: 5px;">
                    <?php
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    
                    for($i = $start_page; $i <= $end_page; $i++):
                    ?>
                    <a href="?<?php echo $query_string; ?>&page=<?php echo $i; ?>" 
                       class="pagination-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                </div>
                
                <?php if($page < $total_pages): ?>
                <a href="?<?php echo $query_string; ?>&page=<?php echo $page + 1; ?>" class="pagination-item">
                    Berikutnya <i class="fas fa-chevron-right"></i>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>