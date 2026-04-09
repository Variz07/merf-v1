<?php
require_once '../config.php';

$page_title = 'Upload Food Product';

// Cek login
if(!isLoggedIn()) {
    redirect('../auth/signin.php', 'Please login first', 'error');
}

// Cek apakah user adalah seller atau admin
if(!isSeller() && !isAdmin()) {
    redirect('../index.php', 'You must be a seller to upload products', 'error');
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get food categories
$food_categories = getFoodCategories();

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_food'])) {
    $name = sanitize($_POST['name']);
    $category_key = $_POST['food_category'];
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $discounted_price = !empty($_POST['discounted_price']) ? floatval($_POST['discounted_price']) : null;
    $location = sanitize($_POST['location']);
    $stock = intval($_POST['stock'] ?? 1);
    
    // Get subcategory based on selected category
    $subcategory = getSubcategoryForCategory($category_key, $name);
    
    $errors = [];
    
    // Validasi
    if(empty($name)) $errors[] = 'Product name is required';
    if(empty($description)) $errors[] = 'Description is required';
    if($price <= 0) $errors[] = 'Price must be greater than 0';
    if(empty($location)) $errors[] = 'Location is required';
    
    // Upload gambar
    $image_name = '';
    if(isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        
        if(in_array($ext, $allowed)) {
            if($_FILES['image']['size'] <= 5 * 1024 * 1024) {
                $new_filename = 'food_' . uniqid() . '.' . $ext;
                $upload_path = '../' . UPLOAD_PATH . $new_filename;
                
                // Buat folder jika belum ada
                $upload_dir = dirname($upload_path);
                if(!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if(move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image_name = $new_filename;
                } else {
                    $errors[] = 'Failed to upload image. Please try again.';
                }
            } else {
                $errors[] = 'Image size must be less than 5MB';
            }
        } else {
            $errors[] = 'Image format must be JPG, PNG, GIF, or WebP';
        }
    } else {
        $errors[] = 'Product image is required';
    }
    
    if(empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products 
                (seller_id, name, category, subcategory, description, price, discounted_price, 
                 quantity, image, location, created_at) 
                VALUES (?, ?, 'food', ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            if($stmt->execute([
                $user_id, $name, $subcategory, $description, $price, $discounted_price,
                $stock, $image_name, $location
            ])) {
                $product_id = $pdo->lastInsertId();
                $_SESSION['success_message'] = 'Food product uploaded successfully!';
                header("Location: product-detail.php?id=$product_id");
                exit();
            }
        } catch(PDOException $e) {
            error_log("Upload Food Error: " . $e->getMessage());
            $errors[] = 'Failed to save product. Please try again.';
        }
    }
    
    if(!empty($errors)) {
        $error_message = implode('<br>', $errors);
    }
}

include '../header.php';
?>

<div class="upload-food-page">
    <div class="container">
        <div class="upload-food-container">
            <!-- HEADER -->
            <div class="upload-food-header">
                <h1>
                    <i class="fas fa-utensils"></i>
                    Upload Food Product
                </h1>
                <p>Share your culinary creations with the community</p>
            </div>
            
            <?php if($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <!-- UPLOAD FORM -->
            <form method="POST" enctype="multipart/form-data" class="upload-food-form">
                <input type="hidden" name="upload_food" value="1">
                
                <div class="form-layout">
                    <!-- LEFT COLUMN - PRODUCT INFO -->
                    <div class="form-left">
                        <h3>Product Information</h3>
                        
                        <div class="form-group">
                            <label for="name">
                                Product Name <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="name"
                                   name="name" 
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                   placeholder="e.g.: Dimsum Mentai, Nasi Goreng, Es Teh Manis"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="food_category">
                                Food Category <span class="required">*</span>
                            </label>
                            <select name="food_category" id="food_category" required>
                                <option value="">Select Category</option>
                                <?php foreach($food_categories as $key => $cat): ?>
                                <option value="<?php echo $key; ?>" 
                                        <?php echo (isset($_POST['food_category']) && $_POST['food_category'] == $key) ? 'selected' : ''; ?>>
                                    <?php echo $cat['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="field-hint">Select the appropriate category for your product</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">
                                Description <span class="required">*</span>
                            </label>
                            <textarea id="description"
                                      name="description" 
                                      rows="5" 
                                      placeholder="Describe your food product in detail. Include ingredients, taste, portion size, etc."
                                      required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                            <div class="char-counter">
                                <span id="descCount">0</span>/500 characters
                            </div>
                        </div>
                    </div>
                    
                    <!-- RIGHT COLUMN - PRICE & LOCATION -->
                    <div class="form-right">
                        <h3>Price & Location</h3>
                        
                        <div class="form-group">
                            <label for="price">
                                Price <span class="required">*</span>
                            </label>
                            <div class="price-input">
                                <span class="currency">Rp</span>
                                <input type="number" 
                                       id="price"
                                       name="price" 
                                       value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>" 
                                       placeholder="25000"
                                       min="0"
                                       step="1000"
                                       required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="discounted_price">
                                Discounted Price (optional)
                            </label>
                            <div class="price-input">
                                <span class="currency">Rp</span>
                                <input type="number" 
                                       id="discounted_price"
                                       name="discounted_price" 
                                       value="<?php echo isset($_POST['discounted_price']) ? htmlspecialchars($_POST['discounted_price']) : ''; ?>" 
                                       placeholder="20000"
                                       min="0"
                                       step="1000">
                            </div>
                            <small class="field-hint">Leave empty if no discount</small>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="stock">
                                    Stock
                                </label>
                                <input type="number" 
                                       id="stock"
                                       name="stock" 
                                       value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '1'; ?>" 
                                       min="1">
                            </div>
                            
                            <div class="form-group">
                                <label for="location">
                                    Location <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       id="location"
                                       name="location" 
                                       value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" 
                                       placeholder="e.g.: SBH Tower A"
                                       required>
                            </div>
                        </div>
                        
                        <h3 style="margin-top: 30px;">Product Image</h3>
                        
                        <div class="form-group">
                            <label for="image">
                                Product Image <span class="required">*</span>
                            </label>
                            <div class="image-upload-area" id="imageUploadArea">
                                <div class="upload-prompt">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <h4>Click to upload image</h4>
                                    <p>Drag & drop or click to select</p>
                                    <span class="upload-hint">JPG, PNG, GIF, WebP • Max 5MB</span>
                                </div>
                                <input type="file" name="image" id="imageInput" accept="image/*" required>
                            </div>
                            <div class="image-preview" id="imagePreview"></div>
                        </div>
                        
                        <!-- CATEGORY PREVIEW -->
                        <div class="category-preview" id="categoryPreview" style="display: none;">
                            <h4>Selected Category:</h4>
                            <div class="preview-category" id="previewCategory">
                                <i class="fas fa-utensils"></i>
                                <span></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- FORM ACTIONS -->
                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-cloud-upload-alt"></i>
                        Upload Food Product
                    </button>
                    <a href="food.php" class="btn-cancel">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
            
            <!-- CATEGORY GUIDE -->
            <div class="category-guide">
                <h3>Food Category Guide</h3>
                <div class="guide-grid">
                    <?php foreach($food_categories as $key => $cat): ?>
                    <div class="guide-item" style="border-left-color: <?php echo $cat['color']; ?>">
                        <div class="guide-icon" style="background: <?php echo $cat['color']; ?>">
                            <i class="fas <?php echo $cat['icon']; ?>"></i>
                        </div>
                        <div class="guide-content">
                            <h4><?php echo $cat['name']; ?></h4>
                            <p><?php echo implode(', ', array_slice($cat['subcategories'], 0, 4)); ?>...</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Character counter
const descField = document.getElementById('description');
if(descField) {
    descField.addEventListener('input', function() {
        document.getElementById('descCount').textContent = this.value.length;
    });
}

// Category preview
const categorySelect = document.getElementById('food_category');
const categoryPreview = document.getElementById('categoryPreview');
const previewCategory = document.getElementById('previewCategory');

const categories = {
    <?php foreach($food_categories as $key => $cat): ?>
    '<?php echo $key; ?>': {
        name: '<?php echo $cat['name']; ?>',
        icon: '<?php echo $cat['icon']; ?>',
        color: '<?php echo $cat['color']; ?>'
    },
    <?php endforeach; ?>
};

categorySelect.addEventListener('change', function() {
    const selected = this.value;
    if(selected && categories[selected]) {
        previewCategory.innerHTML = `
            <i class="fas ${categories[selected].icon}" style="color: ${categories[selected].color}"></i>
            <span>${categories[selected].name}</span>
        `;
        categoryPreview.style.display = 'block';
    } else {
        categoryPreview.style.display = 'none';
    }
});

// Image upload preview
const uploadArea = document.getElementById('imageUploadArea');
const imageInput = document.getElementById('imageInput');
const imagePreview = document.getElementById('imagePreview');

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

function handleImagePreview(file) {
    if(file.size > 5 * 1024 * 1024) {
        alert('File size must be less than 5MB');
        imageInput.value = '';
        return;
    }
    
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
        uploadArea.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function removeImage() {
    imageInput.value = '';
    imagePreview.innerHTML = '';
    uploadArea.style.display = 'flex';
}
</script>

<style>
/* ===== UPLOAD FOOD PAGE STYLES ===== */
.upload-food-page {
    background: #F9F7F4;
    min-height: 100vh;
    padding: 40px 0;
}

.upload-food-container {
    max-width: 1000px;
    margin: 0 auto;
    background: white;
    border-radius: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    border: 1px solid #E8E3D9;
    overflow: hidden;
}

/* HEADER */
.upload-food-header {
    padding: 30px;
    border-bottom: 1px solid #E8E3D9;
    background: linear-gradient(135deg, #FFF9E6, #FFF3CD);
}

.upload-food-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #856404;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.upload-food-header h1 i {
    background: #4C3C27;
    color: white;
    padding: 12px;
    border-radius: 16px;
    font-size: 24px;
}

.upload-food-header p {
    color: #6D6D6D;
    margin-left: 48px;
}

/* ALERT */
.alert {
    margin: 20px 30px;
    padding: 16px 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-error {
    background: #FFEBEE;
    color: #C62828;
    border-left: 4px solid #C62828;
}

/* FORM LAYOUT */
.form-layout {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    padding: 30px;
}

.form-left,
.form-right {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-left h3,
.form-right h3 {
    font-size: 18px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 10px;
    padding-bottom: 10px;
    border-bottom: 1px solid #E8E3D9;
}

/* FORM GROUP */
.form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.form-group label {
    font-size: 14px;
    font-weight: 600;
    color: #2C2416;
}

.required {
    color: #DC3545;
    margin-left: 4px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 16px;
    border: 1.5px solid #E8E3D9;
    border-radius: 12px;
    font-size: 14px;
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

.field-hint {
    font-size: 12px;
    color: #999;
}

/* PRICE INPUT */
.price-input {
    display: flex;
    align-items: center;
    border: 1.5px solid #E8E3D9;
    border-radius: 12px;
    overflow: hidden;
}

.price-input:focus-within {
    border-color: #4C3C27;
}

.currency {
    padding: 12px 16px;
    background: #F5F3EE;
    color: #4C3C27;
    font-weight: 600;
    border-right: 1.5px solid #E8E3D9;
}

.price-input input {
    border: none;
    border-radius: 0;
    padding: 12px 16px;
    flex: 1;
}

/* FORM ROW */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

/* CHARACTER COUNTER */
.char-counter {
    text-align: right;
    font-size: 12px;
    color: #999;
}

.char-counter span {
    font-weight: 600;
    color: #4C3C27;
}

/* IMAGE UPLOAD */
.image-upload-area {
    border: 2px dashed #E8E3D9;
    border-radius: 16px;
    padding: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #F9F7F4;
    min-height: 200px;
}

.image-upload-area:hover,
.image-upload-area.dragover {
    border-color: #4C3C27;
    background: #F5F3EE;
}

.upload-prompt {
    text-align: center;
}

.upload-prompt i {
    font-size: 48px;
    color: #C9B59C;
    margin-bottom: 15px;
}

.upload-prompt h4 {
    font-size: 18px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 8px;
}

.upload-prompt p {
    color: #6D6D6D;
    margin-bottom: 12px;
}

.upload-hint {
    font-size: 12px;
    color: #999;
}

.image-upload-area input {
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

/* CATEGORY PREVIEW */
.category-preview {
    margin-top: 20px;
    padding: 16px;
    background: #F9F7F4;
    border-radius: 12px;
    border: 1px solid #4C3C27;
}

.category-preview h4 {
    font-size: 14px;
    color: #2C2416;
    margin-bottom: 10px;
}

.preview-category {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: white;
    border-radius: 8px;
}

.preview-category i {
    font-size: 20px;
}

.preview-category span {
    font-weight: 600;
    color: #2C2416;
}

/* FORM ACTIONS */
.form-actions {
    display: flex;
    gap: 15px;
    padding: 30px;
    border-top: 1px solid #E8E3D9;
    background: #F9F7F4;
}

.btn-submit {
    flex: 2;
    padding: 16px 30px;
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    color: white;
    border: none;
    border-radius: 40px;
    font-size: 16px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-submit:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 24px rgba(76,60,39,0.2);
}

.btn-cancel {
    flex: 1;
    padding: 16px 30px;
    background: white;
    border: 1.5px solid #E8E3D9;
    border-radius: 40px;
    color: #6D6D6D;
    font-weight: 600;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    text-decoration: none;
    transition: all 0.3s;
}

.btn-cancel:hover {
    background: #F5F3EE;
    border-color: #999;
    color: #2C2416;
}

/* CATEGORY GUIDE */
.category-guide {
    padding: 30px;
    border-top: 1px solid #E8E3D9;
    background: #F9F7F4;
}

.category-guide h3 {
    font-size: 18px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 20px;
}

.guide-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.guide-item {
    background: white;
    border-radius: 12px;
    padding: 16px;
    border-left: 4px solid;
    box-shadow: 0 2px 8px rgba(0,0,0,0.02);
}

.guide-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    margin-bottom: 12px;
}

.guide-content h4 {
    font-size: 15px;
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 6px;
}

.guide-content p {
    font-size: 12px;
    color: #6D6D6D;
    line-height: 1.5;
}

/* RESPONSIVE */
@media (max-width: 992px) {
    .form-layout {
        grid-template-columns: 1fr;
    }
    
    .guide-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .upload-food-header h1 {
        font-size: 24px;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn-submit,
    .btn-cancel {
        width: 100%;
    }
}

@media (max-width: 576px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .guide-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../footer.php'; ?>