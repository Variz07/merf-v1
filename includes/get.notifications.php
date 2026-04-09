<?php
require_once '../config.php';

if(!isLoggedIn()) {
    echo '<div class="notification-empty">Silakan login untuk melihat notifikasi</div>';
    exit();
}

$user_id = $_SESSION['user_id'];

// Get unread notifications
$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

if(empty($notifications)) {
    echo '<div class="notification-empty">Tidak ada notifikasi</div>';
} else {
    foreach($notifications as $notification) {
        $icon = '';
        $color = '';
        
        switch($notification['type']) {
            case 'message':
                $icon = 'fa-envelope';
                $color = 'text-primary';
                break;
            case 'order':
                $icon = 'fa-shopping-bag';
                $color = 'text-success';
                break;
            case 'like':
                $icon = 'fa-heart';
                $color = 'text-danger';
                break;
            case 'comment':
                $icon = 'fa-comment';
                $color = 'text-info';
                break;
            case 'rating':
                $icon = 'fa-star';
                $color = 'text-warning';
                break;
            default:
                $icon = 'fa-bell';
                $color = 'text-secondary';
        }
        
        $is_read = $notification['is_read'] ? '' : 'unread';
        
        echo '<div class="notification-item ' . $is_read . '" data-notification="' . $notification['notification_id'] . '" onclick="markNotificationRead(' . $notification['notification_id'] . ')">';
        echo '<div class="notification-icon ' . $color . '">';
        echo '<i class="fas ' . $icon . '"></i>';
        echo '</div>';
        echo '<div class="notification-content">';
        echo '<h4>' . htmlspecialchars($notification['title']) . '</h4>';
        echo '<p>' . htmlspecialchars($notification['message']) . '</p>';
        echo '<small>' . timeAgo($notification['created_at']) . '</small>';
        echo '</div>';
        if(!$notification['is_read']) {
            echo '<span class="notification-dot"></span>';
        }
        echo '</div>';
    }
    
    // Mark all as read button
    echo '<div class="notification-footer">';
    echo '<button onclick="markAllNotificationsRead()" class="btn-mark-all-read">Tandai Semua Sudah Dibaca</button>';
    echo '</div>';
}
?>

<script>
function markNotificationRead(notificationId) {
    fetch('includes/mark-notification-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Remove unread styling
            const notificationItem = document.querySelector(`[data-notification="${notificationId}"]`);
            if(notificationItem) {
                notificationItem.classList.remove('unread');
                const dot = notificationItem.querySelector('.notification-dot');
                if(dot) dot.remove();
            }
            
            // Update badge count
            updateNotificationBadge();
        }
    });
}

function markAllNotificationsRead() {
    fetch('includes/mark-all-notifications-read.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Remove all unread styling
            document.querySelectorAll('.notification-item.unread').forEach(item => {
                item.classList.remove('unread');
                const dot = item.querySelector('.notification-dot');
                if(dot) dot.remove();
            });
            
            // Update badge count
            updateNotificationBadge();
        }
    });
}

function updateNotificationBadge() {
    const badge = document.querySelector('.notification-badge');
    if(badge) {
        const unreadCount = document.querySelectorAll('.notification-item.unread').length;
        if(unreadCount > 0) {
            badge.textContent = unreadCount;
        } else {
            badge.remove();
        }
    }
}
</script>

<style>
.notification-item {
    padding: 12px;
    border-bottom: 1px solid var(--border-light);
    cursor: pointer;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    transition: background-color 0.2s ease;
}

.notification-item:hover {
    background-color: var(--bg-hover);
}

.notification-item.unread {
    background-color: rgba(67, 97, 238, 0.05);
}

.notification-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: var(--bg-light);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-icon i {
    font-size: 16px;
}

.notification-content {
    flex: 1;
}

.notification-content h4 {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 4px;
    color: var(--text-primary);
}

.notification-content p {
    font-size: 13px;
    color: var(--text-secondary);
    margin-bottom: 4px;
    line-height: 1.4;
}

.notification-content small {
    font-size: 11px;
    color: var(--text-light);
}

.notification-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background-color: var(--primary-color);
    margin-top: 14px;
    flex-shrink: 0;
}

.notification-empty {
    padding: 40px 20px;
    text-align: center;
    color: var(--text-light);
}

.notification-footer {
    padding: 12px;
    text-align: center;
    border-top: 1px solid var(--border-light);
}

.btn-mark-all-read {
    background: none;
    border: none;
    color: var(--primary-color);
    font-size: 12px;
    cursor: pointer;
    font-weight: 500;
}

.btn-mark-all-read:hover {
    text-decoration: underline;
}
</style>