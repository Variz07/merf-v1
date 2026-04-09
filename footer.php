<footer class="footer">
    <div class="container">
        <!-- FOOTER MAIN CONTENT - 4 COLUMNS -->
        <div class="footer-grid">
            
            <!-- COLUMN 1: ABOUT & SOCIAL -->
            <div class="footer-col">
                <div class="footer-logo">
                    <div class="logo-circle">
                        <img src="<?php echo SITE_URL; ?>assets/images/logo.png" alt="MERF Logo" class="logo-image">
                    </div>
                    <span class="footer-brand">MERF Marketplace</span>
                </div>
                <p class="footer-about">
                    Exclusive e-commerce platform for President University students and surrounding community. 
                    Find food, preloved items, services, and urgent needs all in one place.
                </p>
                <div class="social-wrapper">
                    <h4 class="footer-subtitle">Follow Us</h4>
                    <div class="social-links">
                        <a href="#" class="social-icon" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-icon" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-icon" aria-label="WhatsApp">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                        <a href="#" class="social-icon" aria-label="TikTok">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- COLUMN 2: QUICK LINKS -->
            <div class="footer-col">
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo SITE_URL; ?>index.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/food.php"><i class="fas fa-chevron-right"></i> Food</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/preloved.php"><i class="fas fa-chevron-right"></i> Preloved</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/service.php"><i class="fas fa-chevron-right"></i> Service</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/urgent.php"><i class="fas fa-chevron-right"></i> Urgent Needs</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/blog.php"><i class="fas fa-chevron-right"></i> Blog</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/help.php"><i class="fas fa-chevron-right"></i> Help Center</a></li>
                </ul>
            </div>
            
            <!-- COLUMN 3: CATEGORIES -->
            <div class="footer-col">
                <h4 class="footer-title">Categories</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo SITE_URL; ?>pages/food.php?category=dimsum"><i class="fas fa-chevron-right"></i> Dimsum & Snack</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/food.php?category=makanan"><i class="fas fa-chevron-right"></i> Main Meals</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/preloved.php?category=clothes"><i class="fas fa-chevron-right"></i> Clothing</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/preloved.php?category=skincare"><i class="fas fa-chevron-right"></i> Skincare</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/service.php?category=courses"><i class="fas fa-chevron-right"></i> Courses</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/service.php?category=jastip"><i class="fas fa-chevron-right"></i> Jastip</a></li>
                    <li><a href="<?php echo SITE_URL; ?>pages/service.php?category=repair"><i class="fas fa-chevron-right"></i> Repairs</a></li>
                </ul>
            </div>
            
            <!-- COLUMN 4: CONTACT & SUPPORT -->
            <div class="footer-col">
                <h4 class="footer-title">Contact & Support</h4>
                <ul class="contact-info">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <div class="contact-detail">
                            <strong>Address</strong>
                            <span>President University, Jl. Ki Hajar Dewantara, Jababeka, Cikarang</span>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <div class="contact-detail">
                            <strong>Email</strong>
                            <a href="mailto:support@merf.com">support@merf.com</a>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <div class="contact-detail">
                            <strong>Phone</strong>
                            <a href="tel:+6281234567890">+62 812 3456 7890</a>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <div class="contact-detail">
                            <strong>Operating Hours</strong>
                            <span>Mon - Fri, 08:00 - 20:00</span>
                        </div>
                    </li>
                </ul>
                
                <div class="support-badge">
                    <i class="fas fa-headset"></i>
                    <div>
                        <span>24/7 Customer Support</span>
                        <a href="<?php echo SITE_URL; ?>pages/help.php" class="support-link">Get Help →</a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FOOTER BOTTOM -->
        <div class="footer-bottom">
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> MERF Marketplace. All rights reserved.</p>
                <p class="credit">Made with <i class="fas fa-heart" style="color: #FF6B6B;"></i> for President University Students</p>
            </div>
            <div class="legal-links">
                <a href="<?php echo SITE_URL; ?>pages/terms.php">Terms of Service</a>
                <span class="separator">•</span>
                <a href="<?php echo SITE_URL; ?>pages/privacy.php">Privacy Policy</a>
                <span class="separator">•</span>
                <a href="<?php echo SITE_URL; ?>pages/cookie.php">Cookie Policy</a>
            </div>
        </div>
    </div>
</footer>

<style>
/* ===== FOOTER MODERN - ELEGAN & FLEKSIBEL ===== */
.footer {
    background: linear-gradient(135deg, #1a1a1a 0%, #2C2416 100%);
    color: #fff;
    padding: 60px 0 30px;
    margin-top: 60px;
    position: relative;
    overflow: hidden;
}

.footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #C9B59C, #4C3C27, #C9B59C);
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    position: relative;
    z-index: 2;
}

/* FOOTER GRID - 4 KOLOM FLEKSIBEL */
.footer-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 40px;
    margin-bottom: 50px;
}

/* FOOTER COLUMNS */
.footer-col {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

/* LOGO SECTION - BULAT SEMPURNA TANPA BACKGROUND */
.footer-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 5px;
}

.logo-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: transparent; /* TRANSPARAN - TIDAK ADA BACKGROUND COKLAT */
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border: 2px solid #C9B59C; /* BORDER TIPIS UNTUK DEFINISI */
    padding: 3px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.logo-image {
    width: 100%;
    height: 100%;
    object-fit: contain;
    display: block;
}

.footer-brand {
    font-size: 20px;
    font-weight: 700;
    color: white;
    letter-spacing: 1px;
    background: linear-gradient(135deg, #C9B59C, #FFFFFF);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ABOUT TEXT */
.footer-about {
    font-size: 14px;
    line-height: 1.7;
    color: rgba(255,255,255,0.8);
    margin-bottom: 10px;
}

/* SUBTITLE */
.footer-subtitle {
    font-size: 14px;
    font-weight: 600;
    color: #C9B59C;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

/* SOCIAL LINKS */
.social-wrapper {
    margin-top: 10px;
}

.social-links {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.social-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    transition: all 0.3s ease;
    text-decoration: none;
    border: 1px solid rgba(255,255,255,0.1);
}

.social-icon:hover {
    background: #C9B59C;
    color: #2C2416;
    transform: translateY(-4px);
    border-color: transparent;
    box-shadow: 0 8px 16px rgba(201,181,156,0.3);
}

/* FOOTER TITLES */
.footer-title {
    font-size: 18px;
    font-weight: 700;
    color: white;
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 12px;
}

.footer-title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 40px;
    height: 3px;
    background: #C9B59C;
    border-radius: 2px;
}

/* FOOTER LINKS */
.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.footer-links a i {
    font-size: 12px;
    color: #C9B59C;
    transition: transform 0.3s ease;
}

.footer-links a:hover {
    color: white;
    transform: translateX(5px);
}

.footer-links a:hover i {
    transform: translateX(3px);
    color: white;
}

/* CONTACT INFO */
.contact-info {
    list-style: none;
    padding: 0;
    margin: 0 0 20px 0;
}

.contact-info li {
    display: flex;
    gap: 15px;
    margin-bottom: 18px;
    color: rgba(255,255,255,0.8);
    font-size: 14px;
    line-height: 1.6;
}

.contact-info li i {
    font-size: 18px;
    color: #C9B59C;
    margin-top: 3px;
    min-width: 20px;
}

.contact-detail {
    display: flex;
    flex-direction: column;
}

.contact-detail strong {
    color: white;
    font-weight: 600;
    margin-bottom: 4px;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.contact-detail a {
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: color 0.3s;
}

.contact-detail a:hover {
    color: #C9B59C;
}

.contact-detail span {
    color: rgba(255,255,255,0.8);
}

/* SUPPORT BADGE */
.support-badge {
    background: rgba(201,181,156,0.1);
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 15px;
    border: 1px solid rgba(201,181,156,0.2);
    margin-top: 10px;
    backdrop-filter: blur(5px);
}

.support-badge i {
    font-size: 28px;
    color: #C9B59C;
}

.support-badge div {
    display: flex;
    flex-direction: column;
}

.support-badge span {
    font-size: 13px;
    color: rgba(255,255,255,0.9);
    font-weight: 600;
    margin-bottom: 4px;
}

.support-link {
    color: #C9B59C;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    transition: gap 0.3s;
}

.support-link:hover {
    gap: 10px;
    color: white;
}

/* FOOTER BOTTOM */
.footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 30px;
    border-top: 1px solid rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.7);
    font-size: 13px;
}

.copyright p {
    margin-bottom: 5px;
}

.credit {
    font-size: 12px;
    opacity: 0.8;
}

.credit i {
    color: #FF6B6B;
    animation: heartbeat 1.5s ease infinite;
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.legal-links {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.legal-links a {
    color: rgba(255,255,255,0.7);
    text-decoration: none;
    transition: color 0.3s;
    font-size: 13px;
}

.legal-links a:hover {
    color: #C9B59C;
}

.separator {
    color: rgba(255,255,255,0.3);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 992px) {
    .footer-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 40px 30px;
    }
}

@media (max-width: 768px) {
    .footer {
        padding: 50px 0 25px;
    }
    
    .footer-grid {
        grid-template-columns: 1fr;
        gap: 35px;
    }
    
    .footer-bottom {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .legal-links {
        justify-content: center;
    }
    
    .logo-circle {
        width: 45px;
        height: 45px;
    }
    
    .footer-brand {
        font-size: 18px;
    }
}

@media (max-width: 480px) {
    .footer {
        padding: 40px 0 20px;
    }
    
    .social-links {
        justify-content: center;
    }
    
    .footer-title::after {
        left: 50%;
        transform: translateX(-50%);
    }
    
    .footer-title {
        text-align: center;
    }
    
    .footer-links a {
        justify-content: center;
    }
    
    .contact-info li {
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 8px;
    }
    
    .contact-detail {
        align-items: center;
    }
    
    .support-badge {
        flex-direction: column;
        text-align: center;
    }
}

/* HOVER EFFECTS */
.footer-col {
    animation: fadeInUp 0.6s ease backwards;
}

.footer-col:nth-child(1) { animation-delay: 0.1s; }
.footer-col:nth-child(2) { animation-delay: 0.2s; }
.footer-col:nth-child(3) { animation-delay: 0.3s; }
.footer-col:nth-child(4) { animation-delay: 0.4s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>