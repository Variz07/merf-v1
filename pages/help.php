<?php
require_once '../config.php';

$page_title = 'Help Center';

include '../header.php';
?>

<div class="help-container">
    <!-- ===== HEADER HERO ===== -->
    <div class="help-hero">
        <div class="container">
            <h1 class="help-title">Help Center</h1>
            <p class="help-subtitle">Find the help and support you need</p>
            
            <!-- SEARCH BAR -->
            <div class="help-search">
                <form action="" method="GET" class="search-form">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" name="search" placeholder="Search questions, topics, or keywords..." class="help-search-input">
                    <button type="submit" class="help-search-btn">Search</button>
                </form>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- ===== QUICK CATEGORIES ===== -->
        <div class="quick-categories">
            <div class="category-chip active">
                <i class="fas fa-circle-info"></i>
                <span>All</span>
            </div>
            <div class="category-chip">
                <i class="fas fa-user"></i>
                <span>Account</span>
            </div>
            <div class="category-chip">
                <i class="fas fa-cart-shopping"></i>
                <span>Purchases</span>
            </div>
            <div class="category-chip">
                <i class="fas fa-store"></i>
                <span>Selling</span>
            </div>
            <div class="category-chip">
                <i class="fas fa-truck"></i>
                <span>Shipping</span>
            </div>
            <div class="category-chip">
                <i class="fas fa-money-bill"></i>
                <span>Payments</span>
            </div>
            <div class="category-chip">
                <i class="fas fa-shield"></i>
                <span>Security</span>
            </div>
        </div>
        <!-- ===== MAIN CONTENT - 2 KOLOM ===== -->
<div class="help-main-content">
    
    <!-- LEFT COLUMN - FAQ SECTION -->
    <div class="faq-section">
        <h2 class="section-title">
            <i class="fas fa-question-circle"></i>
            Frequently Asked Questions
        </h2>
        
        <div class="faq-list">
            <!-- FAQ ITEM 1 -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>What is MERF Marketplace?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>MERF Marketplace is an exclusive e-commerce platform for President University students and the surrounding community. We provide a place to buy and sell food, preloved items, services, and urgent assistance.</p>
                </div>
            </div>
            
            <!-- FAQ ITEM 2 -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Who can use MERF?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>MERF is open to:</p>
                    <ul>
                        <li>✓ President University students (with a valid student ID)</li>
                        <li>✓ President University lecturers and staff</li>
                        <li>✓ Communities around President University</li>
                        <li>✓ All users who register with a valid email</li>
                    </ul>
                </div>
            </div>
            
            <!-- FAQ ITEM 3 -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How do I order a product?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <ol>
                        <li>Log in to your MERF account</li>
                        <li>Search for the desired product</li>
                        <li>Click the "Order via WhatsApp" button</li>
                        <li>You will be directly connected with the seller</li>
                        <li>Negotiate and complete the transaction via WhatsApp</li>
                    </ol>
                </div>
            </div>
            
            <!-- FAQ ITEM 4 -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How do I become a seller?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <ol>
                        <li>Log in to your MERF account</li>
                        <li>Click the "Sell" button in the top-right corner</li>
                        <li>Click "Upgrade to Seller"</li>
                        <li>Fill in the product information</li>
                        <li>Upload product photos and descriptions</li>
                        <li>Your product will be published immediately</li>
                    </ol>
                </div>
            </div>
            
            <!-- FAQ ITEM 5 -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>Is there any fee to sell?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p><strong>NO FEES!</strong> MERF Marketplace is currently FREE for all users. There are no registration fees, selling fees, or commissions. We only act as a connector between buyers and sellers.</p>
                </div>
            </div>
            
            <!-- FAQ ITEM 6 -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>How do I change my password?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <ol>
                        <li>Click your profile photo in the top-right corner</li>
                        <li>Select "Account Settings"</li>
                        <li>Choose the "Security" menu</li>
                        <li>Click "Change Password"</li>
                        <li>Enter your old password and new password</li>
                        <li>Click "Save Changes"</li>
                    </ol>
                </div>
            </div>
            
            <!-- FAQ ITEM 7 -->
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span>What if fraud occurs?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-answer">
                    <p>If you experience fraud or suspicious activity, please report it immediately via:</p>
                    <ul>
                        <li>📧 Email: support@merfmarketplace.com</li>
                        <li>📱 WhatsApp: +62 812 3456 7890</li>
                        <li>⚠️ "Report" feature on each product page</li>
                    </ul>
                    <p>Our team will process your report within 24 hours.</p>
                </div>
            </div>
        </div>
        
        <!-- VIEW ALL FAQ -->
        <div class="view-more-faq">
            <a href="#" class="btn-view-all">
                View All FAQs
                <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
    
    <!-- RIGHT COLUMN - CONTACT FORM & SUPPORT -->
    <div class="support-section">
        <!-- CONTACT CARD -->
        <div class="contact-card">
            <div class="contact-header">
                <div class="contact-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h3>Need More Help?</h3>
                <p>Send us a message and our support team will contact you within 24 hours</p>
            </div>
            
            <form method="POST" class="contact-form">
                <div class="form-group">
                    <label>Full Name <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" placeholder="Enter your full name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" placeholder="Enter your email address" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Subject <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-tag"></i>
                        <select name="subject" required>
                            <option value="" disabled selected>Select Subject</option>
                            <option value="technical">🔧 Technical Issue</option>
                            <option value="account">👤 Account Issue</option>
                            <option value="order">📦 Order Issue</option>
                            <option value="report">⚠️ User Report</option>
                            <option value="suggestion">💡 Suggestions & Feedback</option>
                            <option value="other">❓ Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Message <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-comment"></i>
                        <textarea name="message" rows="4" placeholder="Write your message in detail..."></textarea>
                    </div>
                </div>
                
                <div class="form-group attachment">
                    <label>Attachment (optional)</label>
                    <div class="file-upload">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p>Click or drag & drop files here</p>
                        <span>Format: JPG, PNG, PDF (Max: 5MB)</span>
                        <input type="file" name="attachment">
                    </div>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i>
                    Send Message
                </button>
            </form>
        </div>
        
        <!-- QUICK SUPPORT -->
        <div class="quick-support">
            <h4>Need Quick Help?</h4>
            <div class="support-options">
                <a href="#" class="support-option">
                    <i class="fab fa-whatsapp"></i>
                    <div>
                        <strong>WhatsApp</strong>
                        <span>+62 812 3456 7890</span>
                    </div>
                </a>
                <a href="#" class="support-option">
                    <i class="far fa-envelope"></i>
                    <div>
                        <strong>Email</strong>
                        <span>support@merf.com</span>
                    </div>
                </a>
                <a href="#" class="support-option">
                    <i class="far fa-clock"></i>
                    <div>
                        <strong>Operating Hours</strong>
                        <span>Monday - Friday, 08:00 - 20:00</span>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>
<style>
/* ===== HELP CENTER - MODERN & RAPI ===== */
.help-container {
    background: #FFFEFC;
    min-height: 100vh;
}

/* HERO SECTION */
.help-hero {
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    padding: 70px 0 90px;
    margin-bottom: 40px;
    position: relative;
    color: white;
}

.help-hero::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    width: 100%;
    height: 60px;
    background: linear-gradient(to right bottom, transparent 50%, #FFFEFC 50%);
}

.help-title {
    font-size: 48px;
    font-weight: 800;
    margin-bottom: 15px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.help-subtitle {
    font-size: 20px;
    opacity: 0.95;
    margin-bottom: 35px;
}

/* SEARCH BAR */
.help-search {
    max-width: 700px;
    margin-top: 30px;
}

.help-search .search-form {
    position: relative;
    display: flex;
    align-items: center;
    background: white;
    border-radius: 60px;
    padding: 5px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.search-icon {
    position: absolute;
    left: 25px;
    color: #999;
    font-size: 18px;
}

.help-search-input {
    flex: 1;
    padding: 18px 25px 18px 55px;
    border: none;
    border-radius: 60px;
    font-size: 16px;
    background: transparent;
}

.help-search-input:focus {
    outline: none;
}

.help-search-btn {
    background: #4C3C27;
    color: white;
    border: none;
    padding: 14px 35px;
    border-radius: 50px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    margin-right: 5px;
}

.help-search-btn:hover {
    background: #2C2416;
    transform: translateY(-2px);
}

/* QUICK CATEGORIES */
.quick-categories {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 50px;
    margin-top: 20px;
}

.category-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 22px;
    background: white;
    border: 1.5px solid #E8E3D9;
    border-radius: 40px;
    font-size: 14px;
    font-weight: 500;
    color: #2C2416;
    cursor: pointer;
    transition: all 0.3s;
}

.category-chip i {
    font-size: 16px;
    color: #6D6D6D;
    transition: all 0.3s;
}

.category-chip:hover {
    background: #F5F3EE;
    border-color: #C9B59C;
}

.category-chip.active {
    background: #4C3C27;
    border-color: #4C3C27;
    color: white;
}

.category-chip.active i {
    color: white;
}

/* MAIN CONTENT - 2 KOLOM */
.help-main-content {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 40px;
    margin-bottom: 60px;
}

/* LEFT COLUMN - FAQ */
.faq-section {
    background: white;
    border-radius: 24px;
    padding: 30px;
    border: 1px solid #E8E3D9;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 24px;
    font-weight: 700;
    color: #4C3C27;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #C9B59C;
}

.section-title i {
    font-size: 28px;
}

.faq-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 30px;
}

.faq-item {
    border: 1px solid #E8E3D9;
    border-radius: 16px;
    overflow: hidden;
    transition: all 0.3s;
}

.faq-item:hover {
    border-color: #C9B59C;
}

.faq-question {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 18px 22px;
    background: white;
    cursor: pointer;
    font-weight: 600;
    color: #2C2416;
    transition: all 0.3s;
}

.faq-question:hover {
    background: #F9F7F4;
}

.faq-question i {
    color: #C9B59C;
    transition: transform 0.3s;
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.faq-answer {
    max-height: 0;
    padding: 0 22px;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    background: #F9F7F4;
    color: #4A4A4A;
    line-height: 1.6;
}

.faq-item.active .faq-answer {
    max-height: 500px;
    padding: 22px;
}

.faq-answer ul,
.faq-answer ol {
    margin-left: 20px;
    margin-top: 10px;
}

.faq-answer li {
    margin-bottom: 8px;
}

.view-more-faq {
    text-align: center;
    margin-top: 20px;
}

.btn-view-all {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #4C3C27;
    font-weight: 600;
    text-decoration: none;
    padding: 12px 25px;
    border-radius: 30px;
    transition: all 0.3s;
}

.btn-view-all:hover {
    background: #F5F3EE;
    gap: 12px;
}

/* RIGHT COLUMN - CONTACT CARD */
.contact-card {
    background: white;
    border-radius: 24px;
    padding: 35px;
    border: 1px solid #E8E3D9;
    margin-bottom: 25px;
}

.contact-header {
    text-align: center;
    margin-bottom: 30px;
}

.contact-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
}

.contact-icon i {
    font-size: 36px;
    color: white;
}

.contact-header h3 {
    font-size: 22px;
    font-weight: 700;
    color: #2C2416;
    margin-bottom: 10px;
}

.contact-header p {
    color: #6D6D6D;
    font-size: 15px;
    line-height: 1.6;
}

/* FORM STYLES */
.contact-form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

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
}

.input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.input-wrapper i {
    position: absolute;
    left: 16px;
    color: #999;
    font-size: 16px;
}

.input-wrapper input,
.input-wrapper select,
.input-wrapper textarea {
    width: 100%;
    padding: 14px 16px 14px 48px;
    border: 1.5px solid #E8E3D9;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s;
    background: white;
}

.input-wrapper textarea {
    padding-top: 16px;
    resize: vertical;
}

.input-wrapper input:focus,
.input-wrapper select:focus,
.input-wrapper textarea:focus {
    border-color: #C9B59C;
    outline: none;
    box-shadow: 0 0 0 4px rgba(201,181,156,0.1);
}

/* FILE UPLOAD */
.file-upload {
    border: 2px dashed #E8E3D9;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #F9F7F4;
}

.file-upload:hover {
    border-color: #C9B59C;
    background: #F5F3EE;
}

.file-upload i {
    font-size: 32px;
    color: #C9B59C;
    margin-bottom: 10px;
}

.file-upload p {
    font-weight: 600;
    color: #2C2416;
    margin-bottom: 5px;
}

.file-upload span {
    font-size: 12px;
    color: #999;
}

.file-upload input {
    display: none;
}

/* SUBMIT BUTTON */
.btn-submit {
    background: linear-gradient(135deg, #4C3C27, #300C0C);
    color: white;
    border: none;
    padding: 16px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 10px;
}

.btn-submit:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(76,60,39,0.2);
}

.btn-submit i {
    font-size: 18px;
}

/* QUICK SUPPORT */
.quick-support {
    background: linear-gradient(135deg, #FFF9E6, #FFF3CD);
    border-radius: 24px;
    padding: 30px;
    border: 1px solid #FFD700;
}

.quick-support h4 {
    font-size: 18px;
    font-weight: 700;
    color: #856404;
    margin-bottom: 20px;
}

.support-options {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.support-option {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 15px;
    background: white;
    border-radius: 12px;
    text-decoration: none;
    color: #2C2416;
    transition: all 0.3s;
}

.support-option:hover {
    transform: translateX(5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

.support-option i {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #4C3C27;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.support-option div {
    display: flex;
    flex-direction: column;
}

.support-option strong {
    font-size: 15px;
    margin-bottom: 3px;
}

.support-option span {
    font-size: 13px;
    color: #6D6D6D;
}

/* RESPONSIVE */
@media (max-width: 992px) {
    .help-main-content {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .help-title {
        font-size: 40px;
    }
}

@media (max-width: 768px) {
    .help-hero {
        padding: 50px 0 70px;
    }
    
    .help-title {
        font-size: 32px;
    }
    
    .help-subtitle {
        font-size: 18px;
    }
    
    .help-search-input {
        padding: 15px 20px 15px 50px;
        font-size: 15px;
    }
    
    .help-search-btn {
        padding: 12px 25px;
        font-size: 15px;
    }
    
    .quick-categories {
        gap: 10px;
    }
    
    .category-chip {
        padding: 8px 18px;
        font-size: 13px;
    }
}

@media (max-width: 576px) {
    .help-search .search-form {
        flex-direction: column;
        background: transparent;
        box-shadow: none;
        padding: 0;
        gap: 15px;
    }
    
    .help-search-input {
        background: white;
        border-radius: 50px;
        padding: 15px 25px 15px 50px;
    }
    
    .help-search-btn {
        width: 100%;
        margin-right: 0;
    }
    
    .faq-section,
    .contact-card {
        padding: 25px 20px;
    }
    
    .section-title {
        font-size: 20px;
    }
}
</style>

<script>
function toggleFaq(element) {
    const faqItem = element.closest('.faq-item');
    faqItem.classList.toggle('active');
}

// File upload click
document.querySelectorAll('.file-upload').forEach(upload => {
    upload.addEventListener('click', function() {
        this.querySelector('input[type="file"]').click();
    });
});

// Preview file name
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function() {
        if(this.files.length > 0) {
            const parent = this.closest('.file-upload');
            const p = parent.querySelector('p');
            p.innerHTML = `<i class="fas fa-check-circle"></i> ${this.files[0].name}`;
            p.style.color = '#4C3C27';
        }
    });
});
</script>

<?php include '../footer.php'; ?>