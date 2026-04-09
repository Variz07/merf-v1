<?php
require_once '../config.php';

$page_title = 'Blog & Artikel';
$page_scripts = ['../assets/js/blog.js'];

// Handle blog creation
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_blog'])) {
    if(!isLoggedIn()) {
        redirect('../auth/signin.php', 'Silakan login terlebih dahulu', 'error');
    }
    
    $title = sanitize($_POST['title']);
    $content = sanitize($_POST['content']);
    $category = sanitize($_POST['category']);
    
    $errors = [];
    
    if(empty($title)) $errors[] = 'Judul blog harus diisi';
    if(empty($content)) $errors[] = 'Konten blog harus diisi';
    
    if(empty($errors)) {
        // Insert blog post - TANPA GAMBAR
        $stmt = $pdo->prepare("INSERT INTO blogs 
                              (user_id, title, content, category, created_at) 
                              VALUES (?, ?, ?, ?, NOW())");
        
        try {
            $stmt->execute([
                $_SESSION['user_id'],
                $title,
                $content,
                $category
            ]);
            
            redirect('blog.php', 'Blog berhasil diposting!');
        } catch(PDOException $e) {
            $errors[] = 'Gagal memposting blog: ' . $e->getMessage();
        }
    }
}

// Get blog posts
$category = $_GET['category'] ?? '';
$author = $_GET['author'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 12; // 12 posts per page
$offset = ($page - 1) * $limit;

// Build query
$sql = "SELECT b.*, u.full_name, u.profile_pic,
        (SELECT COUNT(*) FROM blog_likes WHERE blog_id = b.blog_id) as like_count,
        (SELECT COUNT(*) FROM comments WHERE blog_id = b.blog_id) as comment_count
        FROM blogs b 
        JOIN users u ON b.user_id = u.user_id 
        WHERE b.is_published = 1";
$params = [];

if($category) {
    $sql .= " AND b.category = ?";
    $params[] = $category;
}

if($author) {
    $sql .= " AND u.user_id = ?";
    $params[] = $author;
}

if($search) {
    $sql .= " AND (b.title LIKE ? OR b.content LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM ($sql) as count_query";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_blogs = $stmt->fetchColumn();

// Add ordering and pagination
$sql .= " ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Execute
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$blogs = $stmt->fetchAll();

// Get blog categories
$blog_categories = ['tips', 'review', 'news', 'story'];

// Get popular authors
$popular_authors = [];
$author_stmt = $pdo->query("
    SELECT u.user_id, u.full_name, u.profile_pic, COUNT(b.blog_id) as blog_count
    FROM users u 
    JOIN blogs b ON u.user_id = b.user_id 
    WHERE b.is_published = 1 
    GROUP BY u.user_id 
    ORDER BY blog_count DESC 
    LIMIT 5
");
$popular_authors = $author_stmt->fetchAll();

include '../header.php';
?>

<div class="blog-container">


    <!-- PAGE HEADER - KEEP AS ORIGINAL -->
    <div class="blog-header">
        <div class="container">
            <h1 class="blog-title">Blog & Articles</h1>
            <p class="blog-subtitle">Sharing stories, tips, and experiences from the community</p>
        </div>
    </div>

    <div class="container">
        <!-- BLOG ACTIONS - WRITE BLOG & SEARCH -->
        <div class="blog-actions-wrapper">
            <?php if(isLoggedIn()): ?>
                <button class="btn-tulis-blog" onclick="showBlogForm()">
                    <i class="fas fa-pen"></i> Write Blog
                </button>
            <?php endif; ?>
            
            <form method="GET" class="blog-search-form">
                <div class="search-wrapper">
                    <input type="text" name="search" placeholder="Search articles..." value="<?php echo htmlspecialchars($search); ?>" class="blog-search-input">
                    <button type="submit" class="blog-search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>        <!-- CREATE BLOG FORM - HIDDEN BY DEFAULT -->
        <?php if(isLoggedIn()): ?>
        <div id="blogForm" class="create-blog-form" style="display: none;">
            <div class="form-header">
                <h3>Tulis Blog Baru</h3>
                <button type="button" class="btn-close" onclick="hideBlogForm()">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="create_blog" value="1">
                
                <div class="form-group">
                    <input type="text" name="title" placeholder="Judul blog..." class="form-control" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <select name="category" class="form-control">
                            <?php foreach($blog_categories as $cat): ?>
                            <option value="<?php echo $cat; ?>"><?php echo ucfirst($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <textarea name="content" rows="6" placeholder="Tulis cerita, tips, atau pengalaman Anda di sini..." class="form-control" required></textarea>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-publish">Posting Blog</button>
                    <button type="button" class="btn-cancel" onclick="hideBlogForm()">Batal</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- MAIN CONTENT - 2 KOLOM (SIDEBAR + BLOG GRID) -->
        <div class="blog-main-content">
            <!-- SIDEBAR - KATEGORI & PENULIS POPULER -->
            <div class="blog-sidebar">
                <!-- KATEGORI -->
                <div class="sidebar-card">
                    <h3 class="sidebar-title">Kategori</h3>
                    <div class="category-list">
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => '', 'page' => 1])); ?>" 
                           class="category-item <?php echo empty($category) ? 'active' : ''; ?>">
                            Semua Kategori
                        </a>
                        <?php foreach($blog_categories as $cat): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['category' => $cat, 'page' => 1])); ?>" 
                           class="category-item <?php echo $category == $cat ? 'active' : ''; ?>">
                            <?php echo ucfirst($cat); ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- PENULIS POPULER -->
                <div class="sidebar-card">
                    <h3 class="sidebar-title">Penulis Populer</h3>
                    <div class="author-list">
                        <?php foreach($popular_authors as $author_data): ?>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['author' => $author_data['user_id'], 'page' => 1])); ?>" 
                           class="author-item <?php echo $author == $author_data['user_id'] ? 'active' : ''; ?>">
                            <div class="author-avatar">
                                <img src="<?php echo getUserAvatar($author_data['user_id']); ?>" alt="<?php echo htmlspecialchars($author_data['full_name']); ?>">
                            </div>
                            <div class="author-info">
                                <h4><?php echo htmlspecialchars($author_data['full_name']); ?></h4>
                                <span class="blog-count"><?php echo $author_data['blog_count']; ?> artikel</span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- ARTIKEL TERBARU -->
                <div class="sidebar-card">
                    <h3 class="sidebar-title">Artikel Terbaru</h3>
                    <div class="recent-posts">
                        <?php 
                        $recent_stmt = $pdo->query("
                            SELECT b.blog_id, b.title, b.created_at, u.full_name 
                            FROM blogs b 
                            JOIN users u ON b.user_id = u.user_id 
                            WHERE b.is_published = 1 
                            ORDER BY b.created_at DESC 
                            LIMIT 5
                        ");
                        $recent_posts = $recent_stmt->fetchAll();
                        ?>
                        
                        <?php foreach($recent_posts as $post): ?>
                        <a href="blog-detail.php?id=<?php echo $post['blog_id']; ?>" class="recent-post-item">
                            <h4><?php echo htmlspecialchars($post['title']); ?></h4>
                            <div class="post-meta">
                                <span class="author"><?php echo htmlspecialchars($post['full_name']); ?></span>
                                <span class="separator">•</span>
                                <span class="date"><?php echo timeAgo($post['created_at']); ?></span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- BLOG GRID - NEAT BOX LAYOUT WITHOUT IMAGE -->
            <div class="blog-grid-section">
                <?php if(empty($blogs)): ?>
                <div class="empty-state">
                    <i class="fas fa-blog"></i>
                    <h3>No articles yet</h3>
                    <p>Be the first to write a blog!</p>
                    <?php if(isLoggedIn()): ?>
                    <button onclick="showBlogForm()" class="btn-tulis-pertama">Write the First Blog</button>
                    <?php else: ?>
                    <a href="../auth/signin.php" class="btn-login-pertama">Login to Write</a>
                    <?php endif; ?>
                </div>                <?php else: ?>
                <div class="blog-moment-grid">
                    <?php foreach($blogs as $blog): ?>
                    <div class="moment-card">
                        <!-- HEADER CARD - AUTHOR INFO -->
                        <div class="moment-header">
                            <div class="author-avatar-small">
                                <img src="<?php echo getUserAvatar($blog['user_id']); ?>" alt="<?php echo htmlspecialchars($blog['full_name']); ?>">
                            </div>
                            <div class="author-details">
                                <h4 class="moment-author"><?php echo htmlspecialchars($blog['full_name']); ?></h4>
                                <div class="moment-meta">
                                    <span class="moment-category"><?php echo ucfirst($blog['category']); ?></span>
                                    <span class="moment-time">• <?php echo timeAgo($blog['created_at']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- CONTENT CARD - TEXT ONLY -->
                        <div class="moment-content">
                            <h3 class="moment-title"><?php echo htmlspecialchars($blog['title']); ?></h3>
                            <p class="moment-text">
                                <?php 
                                $content = htmlspecialchars($blog['content']);
                                echo nl2br(substr($content, 0, 300));
                                if(strlen($content) > 300) echo '...';
                                ?>
                            </p>
                        </div>
                        
                        <!-- FOOTER CARD - STATS & ACTION -->
                        <div class="moment-footer">
                            <div class="moment-stats">
                                <span class="stat-item">
                                    <i class="far fa-heart"></i> <?php echo $blog['like_count']; ?>
                                </span>
                                <span class="stat-item">
                                    <i class="far fa-comment"></i> <?php echo $blog['comment_count']; ?>
                                </span>
                                <span class="stat-item">
                                    <i class="far fa-eye"></i> <?php echo $blog['views']; ?>
                                </span>
                            </div>
                            <a href="blog-detail.php?id=<?php echo $blog['blog_id']; ?>" class="btn-baca">
                                Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- PAGINATION -->
                <?php if($total_blogs > $limit): ?>
                <div class="pagination">
                    <?php
                    $total_pages = ceil($total_blogs / $limit);
                    $query_params = $_GET;
                    unset($query_params['page']);
                    $query_string = http_build_query($query_params);
                    ?>
                    
                    <?php if($page > 1): ?>
                    <a href="?<?php echo $query_string; ?>&page=<?php echo $page - 1; ?>" class="pagination-btn">
                        <i class="fas fa-chevron-left"></i> Sebelumnya
                    </a>
                    <?php endif; ?>
                    
                    <div class="pagination-numbers">
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $start_page + 4);
                        
                        for($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <a href="?<?php echo $query_string; ?>&page=<?php echo $i; ?>" 
                           class="page-number <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>
                    </div>
                    
                    <?php if($page < $total_pages): ?>
                    <a href="?<?php echo $query_string; ?>&page=<?php echo $page + 1; ?>" class="pagination-btn">
                        Berikutnya <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* ===== BLOG PAGE - TANPA FOTO, KOTAK MOMENT ===== */
.blog-container {
    background: #FFFEFC;
    min-height: 100vh;
}

/* HEADER - TETAP SEPERTI AWAL */
.blog-header {
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    padding: 50px 0;
    margin-bottom: 40px;
    color: white;
}

.blog-title {
    font-size: 36px;
    font-weight: 700;
    margin-bottom: 10px;
}

.blog-subtitle {
    font-size: 18px;
    opacity: 0.9;
}

/* BLOG ACTIONS */
.blog-actions-wrapper {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
}

.btn-tulis-blog {
    background: #4C3C27;
    color: white;
    border: none;
    padding: 12px 28px;
    border-radius: 30px;
    font-size: 15px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-tulis-blog:hover {
    background: #2C2416;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(76,60,39,0.2);
}

/* SEARCH */
.blog-search-form {
    width: 300px;
}

.search-wrapper {
    position: relative;
    width: 100%;
}

.blog-search-input {
    width: 100%;
    padding: 12px 50px 12px 20px;
    border: 2px solid #E8E3D9;
    border-radius: 30px;
    font-size: 14px;
    transition: all 0.3s;
}

.blog-search-input:focus {
    border-color: #C9B59C;
    outline: none;
    box-shadow: 0 0 0 3px rgba(201,181,156,0.1);
}

.blog-search-btn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #6D6D6D;
    cursor: pointer;
    padding: 8px;
}

.blog-search-btn:hover {
    color: #4C3C27;
}

/* CREATE BLOG FORM */
.create-blog-form {
    background: white;
    border-radius: 16px;
    padding: 25px;
    margin-bottom: 40px;
    border: 1px solid #E8E3D9;
    box-shadow: 0 5px 20px rgba(0,0,0,0.05);
}

.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.form-header h3 {
    font-size: 20px;
    font-weight: 600;
    color: #2C2416;
}

.btn-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #6D6D6D;
}

.btn-publish {
    background: #4C3C27;
    color: white;
    border: none;
    padding: 12px 30px;
    border-radius: 30px;
    font-weight: 600;
    cursor: pointer;
}

.btn-cancel {
    background: none;
    border: 2px solid #E8E3D9;
    padding: 12px 30px;
    border-radius: 30px;
    font-weight: 600;
    cursor: pointer;
    margin-left: 10px;
}

/* MAIN CONTENT - 2 KOLOM */
.blog-main-content {
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 30px;
}

/* SIDEBAR */
.blog-sidebar {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.sidebar-card {
    background: white;
    border-radius: 16px;
    padding: 20px;
    border: 1px solid #E8E3D9;
}

.sidebar-title {
    font-size: 16px;
    font-weight: 600;
    color: #4C3C27;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #C9B59C;
}

/* CATEGORY LIST */
.category-list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.category-item {
    padding: 8px 12px;
    border-radius: 8px;
    color: #2C2416;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.category-item:hover {
    background: #F5F3EE;
    color: #4C3C27;
}

.category-item.active {
    background: #4C3C27;
    color: white;
}

/* AUTHOR LIST */
.author-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.author-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.2s;
}

.author-item:hover {
    background: #F5F3EE;
}

.author-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #C9B59C;
}

.author-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.author-info h4 {
    font-size: 14px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 4px;
}

.blog-count {
    font-size: 12px;
    color: #6D6D6D;
}

/* RECENT POSTS */
.recent-posts {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.recent-post-item {
    text-decoration: none;
    padding-bottom: 15px;
    border-bottom: 1px solid #E8E3D9;
}

.recent-post-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.recent-post-item h4 {
    font-size: 14px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 6px;
    line-height: 1.4;
}

.post-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #6D6D6D;
}

/* ===== BLOG GRID - MOMENT CARDS TANPA FOTO ===== */
.blog-moment-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 25px;
}

.moment-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #E8E3D9;
    padding: 20px;
    transition: all 0.3s;
    display: flex;
    flex-direction: column;
}

.moment-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(76,60,39,0.08);
    border-color: #C9B59C;
}

/* MOMENT HEADER */
.moment-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.author-avatar-small {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #C9B59C;
    flex-shrink: 0;
}

.author-avatar-small img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.author-details {
    flex: 1;
}

.moment-author {
    font-size: 15px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 4px;
}

.moment-meta {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: #6D6D6D;
}

.moment-category {
    background: #F5F3EE;
    padding: 3px 10px;
    border-radius: 20px;
    color: #4C3C27;
    font-weight: 500;
}

/* MOMENT CONTENT - TEXT ONLY */
.moment-content {
    flex: 1;
    margin-bottom: 20px;
}

.moment-title {
    font-size: 18px;
    font-weight: 700;
    color: #2C2416;
    margin-bottom: 12px;
    line-height: 1.4;
}

.moment-text {
    font-size: 14px;
    line-height: 1.6;
    color: #4A4A4A;
    display: -webkit-box;
    -webkit-line-clamp: 4;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* MOMENT FOOTER */
.moment-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #E8E3D9;
}

.moment-stats {
    display: flex;
    gap: 15px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 13px;
    color: #6D6D6D;
}

.stat-item i {
    color: #C9B59C;
}

.btn-baca {
    background: none;
    border: none;
    color: #4C3C27;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-baca:hover {
    color: #2C2416;
    gap: 8px;
}

/* EMPTY STATE */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    border: 1px solid #E8E3D9;
}

.empty-state i {
    font-size: 48px;
    color: #C9B59C;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 20px;
    color: #2C2416;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6D6D6D;
    margin-bottom: 20px;
}

.btn-tulis-pertama,
.btn-login-pertama {
    display: inline-block;
    padding: 12px 30px;
    background: #4C3C27;
    color: white;
    text-decoration: none;
    border-radius: 30px;
    font-weight: 600;
    border: none;
    cursor: pointer;
}

/* PAGINATION */
.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-top: 50px;
}

.pagination-numbers {
    display: flex;
    gap: 8px;
}

.page-number {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: #2C2416;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.page-number:hover {
    background: #F5F3EE;
}

.page-number.active {
    background: #4C3C27;
    color: white;
}

.pagination-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: 1px solid #E8E3D9;
    border-radius: 30px;
    color: #2C2416;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.pagination-btn:hover {
    background: #F5F3EE;
    border-color: #C9B59C;
}

/* RESPONSIVE */
@media (max-width: 992px) {
    .blog-main-content {
        grid-template-columns: 1fr;
    }
    
    .blog-moment-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .blog-moment-grid {
        grid-template-columns: 1fr;
    }
    
    .blog-actions-wrapper {
        flex-direction: column;
        gap: 15px;
    }
    
    .blog-search-form {
        width: 100%;
    }
    
    .blog-title {
        font-size: 28px;
    }
    
    .blog-subtitle {
        font-size: 16px;
    }
}

@media (max-width: 480px) {
    .moment-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
</style>

<script>
function showBlogForm() {
    document.getElementById('blogForm').style.display = 'block';
    window.scrollTo({ top: document.getElementById('blogForm').offsetTop - 100, behavior: 'smooth' });
}

function hideBlogForm() {
    document.getElementById('blogForm').style.display = 'none';
}

// Toggle favorite untuk blog (jika diperlukan)
function likeBlog(blogId) {
    if(!<?php echo isLoggedIn() ? 'true' : 'false'; ?>) {
        window.location.href = '../auth/signin.php';
        return;
    }
    
    fetch('../includes/like-blog.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'blog_id=' + blogId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        }
    });
}
</script>

<?php include '../footer.php'; ?>