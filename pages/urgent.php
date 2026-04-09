<?php
require_once '../config.php';

$page_title = 'Urgent Needs';

// Handle posting urgent need
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_urgent'])) {
    if(!isLoggedIn()) {
        redirect('../auth/signin.php', 'Please login first', 'error');
    }
    
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $location = sanitize($_POST['location']);
    $max_budget = !empty($_POST['max_budget']) ? floatval($_POST['max_budget']) : null;
    $urgency = sanitize($_POST['urgency'] ?? 'medium');
    
    $errors = [];
    
    if(empty($title)) $errors[] = 'Title is required';
    if(empty($description)) $errors[] = 'Description is required';
    if(empty($location)) $errors[] = 'Location is required';
    
    // Handle image upload (optional)
    $image_name = null;
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            if($_FILES['image']['size'] <= 5 * 1024 * 1024) {
                $new_filename = 'urgent_' . uniqid() . '.' . $ext;
                $upload_path = '../' . UPLOAD_PATH . $new_filename;
                
                if(move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_name = $new_filename;
                }
            }
        }
    }
    
    if(empty($errors)) {
        $stmt = $pdo->prepare("
            INSERT INTO urgent_needs 
            (user_id, title, description, location, max_budget, image, urgency, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        if($stmt->execute([
            $_SESSION['user_id'],
            $title,
            $description,
            $location,
            $max_budget,
            $image_name,
            $urgency
        ])) {
            redirect('urgent.php', 'Your urgent need has been posted!');
        }
    }
}

// Get urgent needs with filters
$status = $_GET['status'] ?? 'open';
$location = $_GET['location'] ?? '';
$urgency_filter = $_GET['urgency'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

// Build query
$sql = "SELECT un.*, u.full_name, u.phone as user_phone, u.profile_pic,
        TIMESTAMPDIFF(MINUTE, un.created_at, NOW()) as minutes_ago
        FROM urgent_needs un
        JOIN users u ON un.user_id = u.user_id
        WHERE un.status = 'open'";
$params = [];

if($location) {
    $sql .= " AND un.location LIKE ?";
    $params[] = "%$location%";
}

if($urgency_filter) {
    $sql .= " AND un.urgency = ?";
    $params[] = $urgency_filter;
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM ($sql) as count_query";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_needs = $stmt->fetchColumn();

// Add ordering and pagination
$sql .= " ORDER BY 
            CASE un.urgency 
                WHEN 'critical' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'medium' THEN 3 
                WHEN 'low' THEN 4 
            END ASC,
            un.created_at DESC 
          LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Execute
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$urgent_needs = $stmt->fetchAll();

// Get user's urgent needs if logged in
$my_needs = [];
if(isLoggedIn()) {
    $stmt = $pdo->prepare("
        SELECT * FROM urgent_needs 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $my_needs = $stmt->fetchAll();
}

include '../header.php';
?>

<div class="urgent-page">
    <!-- HERO SECTION -->
    <div class="urgent-hero">
        <div class="container">
            <h1 class="urgent-hero-title">Urgent Needs</h1>
            <p class="urgent-hero-subtitle">Need help immediately? Post your request and others will help you</p>
            
            <!-- QUICK POST BUTTON -->
            <?php if(isLoggedIn()): ?>
            <button class="btn-post-urgent" onclick="showPostForm()">
                <i class="fas fa-plus-circle"></i> Post Urgent Need
            </button>
            <?php else: ?>
            <a href="../auth/signin.php" class="btn-post-urgent">
                <i class="fas fa-sign-in-alt"></i> Login to Post
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <!-- POST FORM - HIDDEN BY DEFAULT -->
        <?php if(isLoggedIn()): ?>
        <div id="postForm" class="post-urgent-form" style="display: none;">
            <div class="form-header">
                <div class="form-header-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h3>Post Urgent Need</h3>
                </div>
                <button class="btn-close" onclick="hidePostForm()">&times;</button>
            </div>
            
            <?php if(!empty($errors)): ?>
            <div class="error-alert">
                <i class="fas fa-exclamation-circle"></i>
                <ul>
                    <?php foreach($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="urgent-form">
                <input type="hidden" name="post_urgent" value="1">
                
                <div class="form-grid">
                    <!-- Title -->
                    <div class="form-group full-width">
                        <label>
                            Title <span class="required">*</span>
                            <span class="label-hint">Be specific and clear</span>
                        </label>
                        <input type="text" 
                               name="title" 
                               placeholder="e.g.: Need Jastip from Ciwalk, Need calculator for exam, etc."
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>"
                               required>
                    </div>
                    
                    <!-- Urgency Level -->
                    <div class="form-group">
                        <label>
                            Urgency Level <span class="required">*</span>
                        </label>
                        <select name="urgency" required>
                            <option value="low">🟢 Low - Not urgent</option>
                            <option value="medium" selected>🟡 Medium - Need within today</option>
                            <option value="high">🟠 High - Need within hours</option>
                            <option value="critical">🔴 Critical - Need immediately!</option>
                        </select>
                    </div>
                    
                    <!-- Location -->
                    <div class="form-group">
                        <label>
                            Location <span class="required">*</span>
                            <span class="label-hint">Where do you need help?</span>
                        </label>
                        <input type="text" 
                               name="location" 
                               placeholder="e.g.: SBH Tower A, President University"
                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                               required>
                    </div>
                    
                    <!-- Description -->
                    <div class="form-group full-width">
                        <label>
                            Description <span class="required">*</span>
                            <span class="label-hint">Explain your need in detail</span>
                        </label>
                        <textarea name="description" 
                                  rows="4" 
                                  placeholder="Describe what you need, when you need it, and any specific requirements..."
                                  required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        <div class="char-counter">
                            <span id="charCount">0</span>/500 characters
                        </div>
                    </div>
                    
                    <!-- Max Budget -->
                    <div class="form-group">
                        <label>
                            Max Budget (optional)
                            <span class="label-hint">Your budget limit</span>
                        </label>
                        <div class="price-input">
                            <span class="currency">Rp</span>
                            <input type="number" 
                                   name="max_budget" 
                                   placeholder="50000"
                                   value="<?php echo htmlspecialchars($_POST['max_budget'] ?? ''); ?>"
                                   min="0"
                                   step="1000">
                        </div>
                    </div>
                    
                    <!-- Image Upload (Optional) -->
                    <div class="form-group">
                        <label>
                            Image (optional)
                            <span class="label-hint">Add photo to clarify</span>
                        </label>
                        <div class="file-upload-area" id="fileUploadArea">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>Click to upload image</p>
                            <span class="upload-hint">JPG, PNG, GIF • Max 5MB</span>
                            <input type="file" name="image" id="imageInput" accept="image/*">
                        </div>
                        <div id="imagePreview" class="image-preview" style="display: none;"></div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn-submit-urgent">
                        <i class="fas fa-paper-plane"></i>
                        Post Now
                    </button>
                    <button type="button" class="btn-cancel" onclick="hidePostForm()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- MAIN CONTENT - 2 COLUMNS -->
        <div class="urgent-main-content">
            
            <!-- LEFT COLUMN - URGENT NEEDS LIST -->
            <div class="urgent-list-section">
                <!-- FILTER BAR -->
                <div class="urgent-filter-bar">
                    <div class="filter-tabs">
                        <a href="?status=open" class="filter-tab <?php echo $status == 'open' ? 'active' : ''; ?>">
                            <i class="fas fa-clock"></i> Open Requests
                        </a>
                        <a href="?status=in_progress" class="filter-tab <?php echo $status == 'in_progress' ? 'active' : ''; ?>">
                            <i class="fas fa-spinner"></i> In Progress
                        </a>
                        <a href="?status=completed" class="filter-tab <?php echo $status == 'completed' ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i> Completed
                        </a>
                    </div>
                    
                    <div class="filter-dropdown">
                        <select onchange="window.location.href='?urgency='+this.value">
                            <option value="">All Urgency</option>
                            <option value="critical" <?php echo $urgency_filter == 'critical' ? 'selected' : ''; ?>>🔴 Critical</option>
                            <option value="high" <?php echo $urgency_filter == 'high' ? 'selected' : ''; ?>>🟠 High</option>
                            <option value="medium" <?php echo $urgency_filter == 'medium' ? 'selected' : ''; ?>>🟡 Medium</option>
                            <option value="low" <?php echo $urgency_filter == 'low' ? 'selected' : ''; ?>>🟢 Low</option>
                        </select>
                    </div>
                </div>
                
                <!-- URGENT NEEDS LIST -->
                <?php if(empty($urgent_needs)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No urgent needs yet</h3>
                    <p>Be the first to post an urgent request!</p>
                    <?php if(isLoggedIn()): ?>
                    <button onclick="showPostForm()" class="btn-empty">
                        <i class="fas fa-plus-circle"></i> Post Urgent Need
                    </button>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="urgent-list">
                    <?php foreach($urgent_needs as $need): 
                        // Determine urgency color
                        $urgency_colors = [
                            'critical' => '#DC3545',
                            'high' => '#FD7E14',
                            'medium' => '#FFC107',
                            'low' => '#28A745'
                        ];
                        $urgency_labels = [
                            'critical' => 'CRITICAL',
                            'high' => 'HIGH',
                            'medium' => 'MEDIUM',
                            'low' => 'LOW'
                        ];
                        $urgency_color = $urgency_colors[$need['urgency']] ?? '#6C757D';
                        
                        // Format WhatsApp link
                        $phone = $need['user_phone'] ?? '';
                        $wa_number = preg_replace('/[^0-9]/', '', $phone);
                        if(substr($wa_number, 0, 1) == '0') {
                            $wa_number = '62' . substr($wa_number, 1);
                        }
                        if(substr($wa_number, 0, 2) != '62' && strlen($wa_number) > 0) {
                            $wa_number = '62' . $wa_number;
                        }
                        
                        $message = "Hello%20" . urlencode($need['full_name']) . "%2C%0A%0A";
                        $message .= "I%20want%20to%20help%20with%20your%20request%3A%0A";
                        $message .= "*" . urlencode($need['title']) . "*%0A%0A";
                        $message .= "I%20can%20help%20you%20with%20this.%20Please%20let%20me%20know%20the%20details.";
                        
                        $wa_link = $wa_number ? "https://wa.me/$wa_number?text=$message" : '#';
                    ?>
                    <div class="urgent-card" style="border-left-color: <?php echo $urgency_color; ?>;">
                        <div class="urgent-card-header">
                            <div class="urgent-user">
                                <div class="user-avatar">
                                    <img src="<?php echo getUserAvatar($need['user_id']); ?>" alt="<?php echo htmlspecialchars($need['full_name']); ?>">
                                </div>
                                <div class="user-info">
                                    <h4><?php echo htmlspecialchars($need['full_name']); ?></h4>
                                    <span class="post-time">
                                        <i class="far fa-clock"></i>
                                        <?php 
                                        if($need['minutes_ago'] < 1) {
                                            echo 'Just now';
                                        } elseif($need['minutes_ago'] < 60) {
                                            echo $need['minutes_ago'] . ' minutes ago';
                                        } elseif($need['minutes_ago'] < 1440) {
                                            echo floor($need['minutes_ago'] / 60) . ' hours ago';
                                        } else {
                                            echo floor($need['minutes_ago'] / 1440) . ' days ago';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <div class="urgency-badge" style="background: <?php echo $urgency_color; ?>;">
                                <?php echo $urgency_labels[$need['urgency']] ?? 'MEDIUM'; ?>
                            </div>
                        </div>
                        
                        <div class="urgent-card-content">
                            <h3 class="urgent-title"><?php echo htmlspecialchars($need['title']); ?></h3>
                            <p class="urgent-description">
                                <?php echo nl2br(htmlspecialchars($need['description'])); ?>
                            </p>
                            
                            <div class="urgent-details">
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($need['location']); ?></span>
                                </div>
                                <?php if($need['max_budget']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <span>Budget: Max <?php echo formatCurrency($need['max_budget']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="urgent-card-footer">
                            <?php if($wa_number): ?>
                            <a href="<?php echo $wa_link; ?>" 
                               target="_blank" 
                               class="btn-contact-wa">
                                <i class="fab fa-whatsapp"></i>
                                Contact Now
                            </a>
                            <?php else: ?>
                            <button class="btn-contact-disabled" disabled>
                                <i class="fas fa-phone-slash"></i>
                                No Contact
                            </button>
                            <?php endif; ?>
                            
                            <?php if(isLoggedIn() && $_SESSION['user_id'] == $need['user_id']): ?>
                            <div class="owner-actions">
                                <button class="btn-edit" onclick="editNeed(<?php echo $need['need_id']; ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn-delete" onclick="deleteNeed(<?php echo $need['need_id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- PAGINATION -->
                <?php if($total_needs > $limit): ?>
                <div class="urgent-pagination">
                    <?php
                    $total_pages = ceil($total_needs / $limit);
                    $query_params = $_GET;
                    unset($query_params['page']);
                    $query_string = http_build_query($query_params);
                    ?>
                    
                    <div class="pagination-wrapper">
                        <?php if($page > 1): ?>
                        <a href="?<?php echo $query_string; ?>&page=<?php echo $page - 1; ?>" class="pagination-prev">
                            <i class="fas fa-chevron-left"></i> Previous
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
                        <a href="?<?php echo $query_string; ?>&page=<?php echo $page + 1; ?>" class="pagination-next">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            
            <!-- RIGHT COLUMN - SIDEBAR -->
            <div class="urgent-sidebar">
                <!-- LOCATION FILTER -->
                <div class="sidebar-card">
                    <h3 class="sidebar-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Location
                    </h3>
                    <form method="GET" class="location-filter">
                        <input type="text" 
                               name="location" 
                               placeholder="Search location..." 
                               value="<?php echo htmlspecialchars($location); ?>"
                               class="location-input">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <!-- URGENCY LEVELS -->
                <div class="sidebar-card">
                    <h3 class="sidebar-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Urgency Level
                    </h3>
                    <div class="urgency-levels">
                        <a href="?urgency=critical" class="level-item critical">
                            <span class="level-dot"></span>
                            <span class="level-name">Critical</span>
                            <span class="level-count">
                                <?php 
                                $count = $pdo->query("SELECT COUNT(*) FROM urgent_needs WHERE urgency = 'critical' AND status = 'open'")->fetchColumn();
                                echo $count;
                                ?>
                            </span>
                        </a>
                        <a href="?urgency=high" class="level-item high">
                            <span class="level-dot"></span>
                            <span class="level-name">High</span>
                            <span class="level-count">
                                <?php 
                                $count = $pdo->query("SELECT COUNT(*) FROM urgent_needs WHERE urgency = 'high' AND status = 'open'")->fetchColumn();
                                echo $count;
                                ?>
                            </span>
                        </a>
                        <a href="?urgency=medium" class="level-item medium">
                            <span class="level-dot"></span>
                            <span class="level-name">Medium</span>
                            <span class="level-count">
                                <?php 
                                $count = $pdo->query("SELECT COUNT(*) FROM urgent_needs WHERE urgency = 'medium' AND status = 'open'")->fetchColumn();
                                echo $count;
                                ?>
                            </span>
                        </a>
                        <a href="?urgency=low" class="level-item low">
                            <span class="level-dot"></span>
                            <span class="level-name">Low</span>
                            <span class="level-count">
                                <?php 
                                $count = $pdo->query("SELECT COUNT(*) FROM urgent_needs WHERE urgency = 'low' AND status = 'open'")->fetchColumn();
                                echo $count;
                                ?>
                            </span>
                        </a>
                    </div>
                </div>
                
                <!-- MY URGENT NEEDS (if logged in) -->
                <?php if(isLoggedIn() && !empty($my_needs)): ?>
                <div class="sidebar-card">
                    <h3 class="sidebar-title">
                        <i class="fas fa-clock"></i>
                        My Requests
                    </h3>
                    <div class="my-requests">
                        <?php foreach($my_needs as $need): ?>
                        <a href="?id=<?php echo $need['need_id']; ?>" class="request-item">
                            <span class="request-title"><?php echo htmlspecialchars($need['title']); ?></span>
                            <span class="request-status status-<?php echo $need['status']; ?>">
                                <?php echo ucfirst($need['status']); ?>
                            </span>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- TIPS CARD -->
                <div class="sidebar-card tips-card">
                    <div class="tips-header">
                        <i class="fas fa-lightbulb"></i>
                        <h3>Quick Tips</h3>
                    </div>
                    <ul class="tips-list">
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Be specific about what you need</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Include location and time</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Set a realistic budget</span>
                        </li>
                        <li>
                            <i class="fas fa-check-circle"></i>
                            <span>Respond quickly to helpers</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Show/hide post form
function showPostForm() {
    document.getElementById('postForm').style.display = 'block';
    window.scrollTo({ top: document.getElementById('postForm').offsetTop - 100, behavior: 'smooth' });
}

function hidePostForm() {
    document.getElementById('postForm').style.display = 'none';
}

// Character counter
const descTextarea = document.querySelector('textarea[name="description"]');
if(descTextarea) {
    descTextarea.addEventListener('input', function() {
        const count = this.value.length;
        document.getElementById('charCount').textContent = count;
    });
}

// Image upload preview
const uploadArea = document.getElementById('fileUploadArea');
const imageInput = document.getElementById('imageInput');
const imagePreview = document.getElementById('imagePreview');

if(uploadArea && imageInput) {
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
}

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
        imagePreview.style.display = 'block';
        uploadArea.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function removeImage() {
    imageInput.value = '';
    imagePreview.innerHTML = '';
    imagePreview.style.display = 'none';
    uploadArea.style.display = 'flex';
}

// Edit and delete functions
function editNeed(needId) {
    window.location.href = 'edit-urgent.php?id=' + needId;
}

function deleteNeed(needId) {
    if(confirm('Are you sure you want to delete this request?')) {
        window.location.href = 'delete-urgent.php?id=' + needId;
    }
}
</script>

<style>
/* ===== URGENT NEEDS PAGE - CLEAN & FUNCTIONAL ===== */
.urgent-page {
    background: #FFFEFC;
    min-height: 100vh;
}

/* HERO SECTION */
.urgent-hero {
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    padding: 50px 0 60px;
    margin-bottom: 30px;
    position: relative;
    color: white;
}

.urgent-hero::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 40px;
    background: linear-gradient(to right bottom, transparent 50%, #FFFEFC 50%);
}

.urgent-hero-title {
    font-size: 42px;
    font-weight: 800;
    margin-bottom: 12px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.urgent-hero-subtitle {
    font-size: 18px;
    opacity: 0.95;
    margin-bottom: 30px;
}

/* POST BUTTON */
.btn-post-urgent {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: white;
    color: #4C3C27;
    padding: 16px 32px;
    border-radius: 50px;
    font-size: 18px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

.btn-post-urgent:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.25);
    background: #F5F3EE;
}

/* POST FORM */
.post-urgent-form {
    background: white;
    border-radius: 24px;
    padding: 30px;
    margin-bottom: 40px;
    border: 1px solid #E8E3D9;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
}

.form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #E8E3D9;
}

.form-header-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.form-header-title i {
    font-size: 28px;
    color: #DC3545;
}

.form-header-title h3 {
    font-size: 24px;
    font-weight: 700;
    color: #2C2416;
}

.btn-close {
    background: none;
    border: none;
    font-size: 32px;
    cursor: pointer;
    color: #999;
    transition: color 0.3s;
}

.btn-close:hover {
    color: #DC3545;
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

.form-group label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 14px;
    font-weight: 600;
    color: #2C2416;
}

.label-hint {
    font-size: 12px;
    font-weight: 400;
    color: #999;
}

.required {
    color: #DC3545;
    margin-left: 4px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 14px 16px;
    border: 2px solid #E8E3D9;
    border-radius: 12px;
    font-size: 15px;
    transition: all 0.3s;
    background: white;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #4C3C27;
    outline: none;
    box-shadow: 0 0 0 4px rgba(76,60,39,0.05);
}

/* PRICE INPUT */
.price-input {
    display: flex;
    align-items: center;
    border: 2px solid #E8E3D9;
    border-radius: 12px;
    overflow: hidden;
}

.price-input:focus-within {
    border-color: #4C3C27;
}

.currency {
    padding: 14px 16px;
    background: #F5F3EE;
    color: #4C3C27;
    font-weight: 600;
    border-right: 2px solid #E8E3D9;
}

.price-input input {
    border: none;
    border-radius: 0;
    padding: 14px 16px;
    flex: 1;
}

/* FILE UPLOAD */
.file-upload-area {
    border: 2px dashed #E8E3D9;
    border-radius: 12px;
    padding: 30px 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #F9F7F4;
}

.file-upload-area:hover,
.file-upload-area.dragover {
    border-color: #4C3C27;
    background: #F5F3EE;
}

.file-upload-area i {
    font-size: 36px;
    color: #C9B59C;
    margin-bottom: 10px;
}

.file-upload-area p {
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 5px;
}

.upload-hint {
    font-size: 12px;
    color: #999;
}

.file-upload-area input {
    display: none;
}

/* IMAGE PREVIEW */
.image-preview {
    margin-top: 15px;
}

.preview-item {
    position: relative;
    display: inline-block;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #4C3C27;
}

.preview-item img {
    max-width: 200px;
    max-height: 150px;
    object-fit: cover;
}

.remove-image {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 32px;
    height: 32px;
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
    margin-top: 30px;
}

.btn-submit-urgent {
    flex: 1;
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    color: white;
    border: none;
    padding: 16px 30px;
    border-radius: 50px;
    font-size: 16px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-submit-urgent:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 20px rgba(76,60,39,0.2);
}

.btn-cancel {
    padding: 16px 30px;
    border: 2px solid #E8E3D9;
    border-radius: 50px;
    color: #6D6D6D;
    font-weight: 600;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-cancel:hover {
    background: #F5F3EE;
    border-color: #999;
}

/* MAIN CONTENT - 2 COLUMNS */
.urgent-main-content {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 30px;
    margin-bottom: 50px;
}

/* FILTER BAR */
.urgent-filter-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    background: white;
    padding: 15px 20px;
    border-radius: 12px;
    border: 1px solid #E8E3D9;
}

.filter-tabs {
    display: flex;
    gap: 10px;
}

.filter-tab {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 30px;
    color: #6D6D6D;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s;
}

.filter-tab:hover {
    background: #F5F3EE;
    color: #4C3C27;
}

.filter-tab.active {
    background: #4C3C27;
    color: white;
}

.filter-tab.active i {
    color: white;
}

.filter-dropdown select {
    padding: 8px 30px 8px 16px;
    border: 1px solid #E8E3D9;
    border-radius: 30px;
    font-size: 14px;
    color: #2C2416;
    background: white;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%234C3C27' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
}

/* URGENT CARD */
.urgent-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.urgent-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #E8E3D9;
    border-left-width: 6px;
    padding: 20px;
    transition: all 0.3s;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}

.urgent-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.06);
}

.urgent-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.urgent-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid #C9B59C;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-info h4 {
    font-size: 16px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 4px;
}

.post-time {
    display: flex;
    align-items: center;
    gap: 5px;
    color: #999;
    font-size: 12px;
}

.urgency-badge {
    padding: 6px 14px;
    border-radius: 30px;
    color: white;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.urgent-card-content {
    margin-bottom: 20px;
}

.urgent-title {
    font-size: 18px;
    font-weight: 700;
    color: #2C2416;
    margin-bottom: 10px;
    line-height: 1.4;
}

.urgent-description {
    color: #5C5C5C;
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 15px;
    white-space: pre-line;
}

.urgent-details {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    padding-top: 12px;
    border-top: 1px solid #F0EDE5;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6D6D6D;
    font-size: 14px;
}

.detail-item i {
    color: #4C3C27;
}

.urgent-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 15px;
    border-top: 1px solid #F0EDE5;
}

.btn-contact-wa {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #25D366;
    color: white;
    padding: 12px 24px;
    border-radius: 40px;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s;
}

.btn-contact-wa:hover {
    background: #128C7E;
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(37,211,102,0.2);
}

.btn-contact-disabled {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #E8E3D9;
    color: #999;
    padding: 12px 24px;
    border-radius: 40px;
    border: none;
    font-weight: 600;
    font-size: 14px;
    cursor: not-allowed;
}

.owner-actions {
    display: flex;
    gap: 8px;
}

.btn-edit,
.btn-delete {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 1px solid #E8E3D9;
    background: white;
    color: #6D6D6D;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-edit:hover {
    background: #FFC107;
    color: white;
    border-color: #FFC107;
}

.btn-delete:hover {
    background: #DC3545;
    color: white;
    border-color: #DC3545;
}

/* SIDEBAR */
.urgent-sidebar {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.sidebar-card {
    background: white;
    border-radius: 16px;
    border: 1px solid #E8E3D9;
    padding: 20px;
}

.sidebar-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 16px;
    font-weight: 700;
    color: #2C2416;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 1px solid #E8E3D9;
}

.sidebar-title i {
    color: #4C3C27;
}

/* LOCATION FILTER */
.location-filter {
    display: flex;
    gap: 10px;
}

.location-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #E8E3D9;
    border-radius: 30px;
    font-size: 14px;
}

.btn-filter {
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: #4C3C27;
    color: white;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-filter:hover {
    background: #2C2416;
    transform: scale(1.05);
}

/* URGENCY LEVELS */
.urgency-levels {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.level-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.2s;
}

.level-item:hover {
    background: #F5F3EE;
}

.level-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.level-item.critical .level-dot { background: #DC3545; }
.level-item.high .level-dot { background: #FD7E14; }
.level-item.medium .level-dot { background: #FFC107; }
.level-item.low .level-dot { background: #28A745; }

.level-name {
    flex: 1;
    color: #2C2416;
    font-size: 14px;
    font-weight: 500;
}

.level-count {
    color: #999;
    font-size: 13px;
    font-weight: 600;
}

/* MY REQUESTS */
.my-requests {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.request-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #F9F7F4;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.3s;
}

.request-item:hover {
    background: #F0EDE5;
}

.request-title {
    color: #2C2416;
    font-size: 13px;
    font-weight: 500;
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-right: 10px;
}

.request-status {
    font-size: 11px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 30px;
}

.status-open { background: #28A745; color: white; }
.status-in_progress { background: #FFC107; color: #2C2416; }
.status-completed { background: #6C757D; color: white; }

/* TIPS CARD */
.tips-card {
    background: linear-gradient(135deg, #FFF9E6, #FFF3CD);
    border: 1px solid #FFD700;
}

.tips-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 15px;
}

.tips-header i {
    font-size: 28px;
    color: #FFC107;
}

.tips-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #856404;
}

.tips-list {
    list-style: none;
}

.tips-list li {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    color: #5C5C5C;
    font-size: 14px;
}

.tips-list li i {
    color: #28A745;
    font-size: 16px;
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
    font-size: 64px;
    color: #C9B59C;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 22px;
    color: #2C2416;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6D6D6D;
    margin-bottom: 25px;
}

.btn-empty {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 28px;
    background: #4C3C27;
    color: white;
    border: none;
    border-radius: 40px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-empty:hover {
    background: #2C2416;
    transform: translateY(-2px);
}

/* PAGINATION */
.urgent-pagination {
    display: flex;
    justify-content: center;
    margin-top: 40px;
}

.pagination-wrapper {
    display: flex;
    align-items: center;
    gap: 20px;
    background: white;
    padding: 10px 20px;
    border-radius: 50px;
    border: 1px solid #E8E3D9;
}

.pagination-prev,
.pagination-next {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 20px;
    color: #2C2416;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    border-radius: 30px;
    transition: all 0.3s;
}

.pagination-prev:hover,
.pagination-next:hover {
    background: #F5F3EE;
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
    transition: all 0.3s;
}

.page-number:hover {
    background: #F5F3EE;
}

.page-number.active {
    background: #4C3C27;
    color: white;
}

/* RESPONSIVE */
@media (max-width: 1024px) {
    .urgent-main-content {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .urgent-hero {
        padding: 40px 0 50px;
    }
    
    .urgent-hero-title {
        font-size: 32px;
    }
    
    .urgent-hero-subtitle {
        font-size: 16px;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-group.full-width {
        grid-column: span 1;
    }
    
    .urgent-filter-bar {
        flex-direction: column;
        gap: 15px;
    }
    
    .filter-tabs {
        width: 100%;
        overflow-x: auto;
        padding-bottom: 5px;
    }
    
    .filter-tab {
        white-space: nowrap;
    }
    
    .filter-dropdown {
        width: 100%;
    }
    
    .filter-dropdown select {
        width: 100%;
    }
    
    .urgent-card-footer {
        flex-direction: column;
        gap: 15px;
    }
    
    .btn-contact-wa {
        width: 100%;
        justify-content: center;
    }
    
    .owner-actions {
        width: 100%;
        justify-content: flex-end;
    }
}

@media (max-width: 480px) {
    .urgent-card-header {
        flex-direction: column;
        gap: 12px;
    }
    
    .urgency-badge {
        align-self: flex-start;
    }
    
    .urgent-details {
        flex-direction: column;
        gap: 10px;
    }
    
    .pagination-wrapper {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>

<?php include '../footer.php'; ?>