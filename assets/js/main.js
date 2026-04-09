// Main JavaScript file for MERF Marketplace

// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileMenuClose = document.getElementById('mobileMenuClose');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if(mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', () => {
            mobileMenu.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    }
    
    if(mobileMenuClose) {
        mobileMenuClose.addEventListener('click', () => {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        if(mobileMenu && mobileMenu.classList.contains('active') && 
           !mobileMenu.contains(e.target) && 
           e.target !== mobileMenuToggle) {
            mobileMenu.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // Notification dropdown
    const notificationBtn = document.getElementById('notificationBtn');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    if(notificationBtn && notificationDropdown) {
        notificationBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationDropdown.classList.toggle('show');
        });
        
        // Load notifications via AJAX
        loadNotifications();
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if(!notificationBtn.contains(e.target) && !notificationDropdown.contains(e.target)) {
                notificationDropdown.classList.remove('show');
            }
        });
    }
    
    // User dropdown
    const userBtn = document.querySelector('.user-btn');
    const userDropdown = document.querySelector('.user-dropdown-content');
    
    if(userBtn && userDropdown) {
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });
        
        document.addEventListener('click', (e) => {
            if(!userBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('show');
            }
        });
    }
    
    // Back to top button
    const backToTop = document.getElementById('backToTop');
    if(backToTop) {
        window.addEventListener('scroll', () => {
            if(window.scrollY > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });
        
        backToTop.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // Auto-hide alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
    
    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-toggle="tooltip"]');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', function() {
            const title = this.getAttribute('title');
            if(title) {
                const tooltipEl = document.createElement('div');
                tooltipEl.className = 'custom-tooltip';
                tooltipEl.textContent = title;
                document.body.appendChild(tooltipEl);
                
                const rect = this.getBoundingClientRect();
                tooltipEl.style.top = (rect.top - tooltipEl.offsetHeight - 10) + 'px';
                tooltipEl.style.left = (rect.left + rect.width/2 - tooltipEl.offsetWidth/2) + 'px';
                
                this.setAttribute('title', '');
            }
        });
        
        tooltip.addEventListener('mouseleave', function() {
            const tooltipEl = document.querySelector('.custom-tooltip');
            if(tooltipEl) {
                this.setAttribute('title', tooltipEl.textContent);
                tooltipEl.remove();
            }
        });
    });
});

// Load notifications via AJAX
function loadNotifications() {
    const notificationList = document.getElementById('notificationList');
    if(!notificationList) return;
    
    fetch('../includes/get-notifications.php')
        .then(response => response.text())
        .then(html => {
            notificationList.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
        });
}

// Mark notification as read
function markNotificationRead(notificationId) {
    fetch('../includes/mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Remove notification badge
            const badge = document.querySelector('.notification-badge');
            if(badge) {
                const count = parseInt(badge.textContent);
                if(count > 1) {
                    badge.textContent = count - 1;
                } else {
                    badge.remove();
                }
            }
            
            // Remove notification from list
            const notificationItem = document.querySelector(`[data-notification="${notificationId}"]`);
            if(notificationItem) {
                notificationItem.remove();
            }
        }
    });
}

// Toggle favorite product
function toggleFavorite(productId) {
    fetch('../includes/toggle-favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'product_id=' + productId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Update button appearance
            const buttons = document.querySelectorAll(`[onclick="toggleFavorite(${productId})"]`);
            buttons.forEach(btn => {
                btn.classList.toggle('active', data.is_favorited);
                const icon = btn.querySelector('i');
                if(icon) {
                    icon.className = data.is_favorited ? 'fas fa-heart' : 'far fa-heart';
                }
            });
            
            // Show toast notification
            showToast(data.is_favorited ? 'Ditambahkan ke favorit' : 'Dihapus dari favorit');
        }
    });
}

// Show toast notification
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="toast-close">&times;</button>
    `;
    
    document.body.appendChild(toast);
    
    // Close button
    toast.querySelector('.toast-close').addEventListener('click', () => {
        toast.remove();
    });
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if(toast.parentNode) {
            toast.remove();
        }
    }, 3000);
}

// Add to cart
function addToCart(productId, quantity = 1) {
    fetch('../includes/add-to-cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            showToast('Ditambahkan ke keranjang');
            // Update cart count
            updateCartCount(data.cart_count);
        } else {
            showToast(data.message, 'error');
        }
    });
}

// Update cart count in header
function updateCartCount(count) {
    const cartCount = document.querySelector('.cart-count');
    if(cartCount) {
        cartCount.textContent = count;
        cartCount.style.display = count > 0 ? 'flex' : 'none';
    }
}

// Search functionality
function performSearch(query) {
    if(query.trim().length < 2) return;
    
    window.location.href = `../pages/search.php?q=${encodeURIComponent(query)}`;
}

// Debounce function for search
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Initialize search with debounce
const searchInput = document.querySelector('.search-input');
if(searchInput) {
    const debouncedSearch = debounce(function() {
        performSearch(this.value);
    }, 500);
    
    searchInput.addEventListener('input', debouncedSearch);
}

// Rating stars
function initializeRatingStars() {
    document.querySelectorAll('.star-rating').forEach(rating => {
        const stars = rating.querySelectorAll('input[type="radio"]');
        stars.forEach(star => {
            star.addEventListener('change', function() {
                const ratingValue = this.value;
                // Update visual stars
                const labels = rating.querySelectorAll('label');
                labels.forEach((label, index) => {
                    const icon = label.querySelector('i');
                    if(icon) {
                        if(index < ratingValue) {
                            icon.classList.add('fas');
                            icon.classList.remove('far');
                        } else {
                            icon.classList.add('far');
                            icon.classList.remove('fas');
                        }
                    }
                });
            });
        });
    });
}

// Image zoom
function initializeImageZoom() {
    const mainImages = document.querySelectorAll('.main-image img');
    mainImages.forEach(img => {
        img.addEventListener('click', function() {
            const modal = document.createElement('div');
            modal.className = 'image-zoom-modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <img src="${this.src}" alt="${this.alt}">
                    <button class="modal-close">&times;</button>
                </div>
            `;
            
            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';
            
            modal.querySelector('.modal-close').addEventListener('click', () => {
                modal.remove();
                document.body.style.overflow = '';
            });
            
            modal.addEventListener('click', (e) => {
                if(e.target === modal) {
                    modal.remove();
                    document.body.style.overflow = '';
                }
            });
        });
    });
}

// Quantity input controls
function initializeQuantityInputs() {
    document.querySelectorAll('.quantity-input').forEach(input => {
        const minus = input.querySelector('button:first-child');
        const plus = input.querySelector('button:last-child');
        const numberInput = input.querySelector('input[type="number"]');
        
        if(minus && plus && numberInput) {
            minus.addEventListener('click', () => {
                let value = parseInt(numberInput.value);
                if(value > parseInt(numberInput.min)) {
                    numberInput.value = value - 1;
                    numberInput.dispatchEvent(new Event('change'));
                }
            });
            
            plus.addEventListener('click', () => {
                let value = parseInt(numberInput.value);
                if(value < parseInt(numberInput.max)) {
                    numberInput.value = value + 1;
                    numberInput.dispatchEvent(new Event('change'));
                }
            });
        }
    });
}

// Form validation
function validateForm(form) {
    let isValid = true;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    
    inputs.forEach(input => {
        if(!input.value.trim()) {
            input.classList.add('error');
            isValid = false;
            
            // Add error message
            let errorMsg = input.nextElementSibling;
            if(!errorMsg || !errorMsg.classList.contains('error-message')) {
                errorMsg = document.createElement('div');
                errorMsg.className = 'error-message';
                errorMsg.textContent = 'Field ini wajib diisi';
                input.parentNode.insertBefore(errorMsg, input.nextSibling);
            }
        } else {
            input.classList.remove('error');
            const errorMsg = input.nextElementSibling;
            if(errorMsg && errorMsg.classList.contains('error-message')) {
                errorMsg.remove();
            }
        }
    });
    
    return isValid;
}

// Initialize all
document.addEventListener('DOMContentLoaded', function() {
    initializeRatingStars();
    initializeImageZoom();
    initializeQuantityInputs();
    
    // Form validation
    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if(!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
});

// Add custom tooltip styles
const style = document.createElement('style');
style.textContent = `
.custom-tooltip {
    position: fixed;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 12px;
    z-index: 10000;
    pointer-events: none;
}

.toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-width: 300px;
    z-index: 10000;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.toast-success { border-left: 4px solid #28a745; }
.toast-error { border-left: 4px solid #dc3545; }
.toast-warning { border-left: 4px solid #ffc107; }

.toast-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #666;
}

.image-zoom-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.image-zoom-modal img {
    max-width: 90%;
    max-height: 90%;
    object-fit: contain;
}

.modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    background: none;
    border: none;
    color: white;
    font-size: 30px;
    cursor: pointer;
}
`;
document.head.appendChild(style);