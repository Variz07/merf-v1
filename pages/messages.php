<?php
require_once '../config.php';

if(!isLoggedIn()) {
    redirect('../auth/signin.php', 'Silakan login terlebih dahulu', 'error');
}

$page_title = 'Pesan';
$page_scripts = ['../assets/js/chat.js'];

// Get conversation parameters
$conversation_id = $_GET['conversation'] ?? 0;
$to_user_id = $_GET['to'] ?? 0;
$product_id = $_GET['product'] ?? 0;

// Get user conversations
$stmt = $pdo->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as other_user_id,
        u.full_name, u.profile_pic,
        MAX(m.created_at) as last_message_time,
        (SELECT content FROM messages m2 
         WHERE ((m2.sender_id = ? AND m2.receiver_id = other_user_id) 
                OR (m2.sender_id = other_user_id AND m2.receiver_id = ?))
         ORDER BY m2.created_at DESC LIMIT 1) as last_message,
        (SELECT COUNT(*) FROM messages m3 
         WHERE m3.receiver_id = ? AND m3.sender_id = other_user_id AND m3.is_read = 0) as unread_count
    FROM messages m
    JOIN users u ON (u.user_id = CASE 
        WHEN m.sender_id = ? THEN m.receiver_id 
        ELSE m.sender_id 
    END)
    WHERE ? IN (m.sender_id, m.receiver_id)
    GROUP BY other_user_id, u.full_name, u.profile_pic
    ORDER BY last_message_time DESC
");

$stmt->execute([
    $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'],
    $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']
]);

$conversations = $stmt->fetchAll();

// If starting new conversation
if($to_user_id && !$conversation_id) {
    // Check if conversation already exists
    $check_stmt = $pdo->prepare("
        SELECT m.message_id, m.content, m.created_at, 
               u.full_name, u.profile_pic
        FROM messages m
        JOIN users u ON u.user_id = m.sender_id
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?))
        ORDER BY m.created_at DESC 
        LIMIT 1
    ");
    
    $check_stmt->execute([$_SESSION['user_id'], $to_user_id, $to_user_id, $_SESSION['user_id']]);
    $existing = $check_stmt->fetch();
    
    if(!$existing) {
        // Create welcome message
        $welcome_msg = "Halo! Saya tertarik dengan produk/jasa Anda.";
        if($product_id) {
            $product_stmt = $pdo->prepare("SELECT name FROM products WHERE product_id = ?");
            $product_stmt->execute([$product_id]);
            $product_name = $product_stmt->fetchColumn();
            $welcome_msg = "Halo! Saya tertarik dengan: " . $product_name;
        }
        
        $insert_stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, product_id, content) 
            VALUES (?, ?, ?, ?)
        ");
        
        $insert_stmt->execute([$_SESSION['user_id'], $to_user_id, $product_id, $welcome_msg]);
        
        // Get the new conversation
        $conversation_id = $pdo->lastInsertId();
    }
}

// Get messages for current conversation
$messages = [];
$other_user = null;

if($conversation_id || $to_user_id) {
    if($conversation_id) {
        // Get conversation by message ID
        $conv_stmt = $pdo->prepare("
            SELECT sender_id, receiver_id 
            FROM messages 
            WHERE message_id = ?
        ");
        $conv_stmt->execute([$conversation_id]);
        $conv = $conv_stmt->fetch();
        
        if($conv) {
            $to_user_id = ($conv['sender_id'] == $_SESSION['user_id']) ? $conv['receiver_id'] : $conv['sender_id'];
        }
    }
    
    // Get other user info
    $user_stmt = $pdo->prepare("SELECT user_id, full_name, profile_pic FROM users WHERE user_id = ?");
    $user_stmt->execute([$to_user_id]);
    $other_user = $user_stmt->fetch();
    
    // Get messages
    $msg_stmt = $pdo->prepare("
        SELECT m.*, u.full_name, u.profile_pic 
        FROM messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE ((m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?))
        ORDER BY m.created_at ASC
    ");
    
    $msg_stmt->execute([$_SESSION['user_id'], $to_user_id, $to_user_id, $_SESSION['user_id']]);
    $messages = $msg_stmt->fetchAll();
    
    // Mark messages as read
    $read_stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
    ");
    $read_stmt->execute([$_SESSION['user_id'], $to_user_id]);
}

// Handle sending message
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $content = sanitize($_POST['message']);
    $to_user_id = $_POST['to_user_id'];
    
    if(empty($content)) {
        $_SESSION['error_message'] = 'Pesan tidak boleh kosong';
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, content) 
            VALUES (?, ?, ?)
        ");
        
        if($stmt->execute([$_SESSION['user_id'], $to_user_id, $content])) {
            // Send notification
            sendNotification(
                $to_user_id,
                'message',
                'Pesan Baru',
                $_SESSION['user_name'] . ' mengirim pesan: ' . substr($content, 0, 50) . '...',
                $pdo->lastInsertId()
            );
            
            // Refresh page
            redirect("messages.php?to=$to_user_id");
        }
    }
}

include '../header.php';
?>

<div class="container">
    <div class="page-header">
        <h1 class="page-title">Pesan</h1>
        <p class="page-subtitle">Komunikasikan dengan pembeli atau penjual</p>
    </div>
    
    <div class="chat-container">
        <!-- Conversations List -->
        <div class="conversations-sidebar">
            <div class="conversations-header">
                <h3>Percakapan</h3>
                <button class="btn-new-chat" onclick="showNewChatModal()">
                    <i class="fas fa-plus"></i> Chat Baru
                </button>
            </div>
            
            <div class="conversations-list">
                <?php if(empty($conversations)): ?>
                <div class="empty-conversations">
                    <i class="fas fa-comments"></i>
                    <p>Belum ada percakapan</p>
                </div>
                <?php else: ?>
                <?php foreach($conversations as $conv): ?>
                <a href="messages.php?to=<?php echo $conv['other_user_id']; ?>" 
                   class="conversation-item <?php echo $to_user_id == $conv['other_user_id'] ? 'active' : ''; ?>">
                    <div class="conversation-avatar">
                        <img src="<?php echo getUserAvatar($conv['other_user_id']); ?>" alt="<?php echo htmlspecialchars($conv['full_name']); ?>">
                        <?php if($conv['unread_count'] > 0): ?>
                        <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="conversation-info">
                        <h4><?php echo htmlspecialchars($conv['full_name']); ?></h4>
                        <p class="last-message"><?php echo htmlspecialchars(substr($conv['last_message'] ?? '', 0, 50)); ?></p>
                        <small><?php echo timeAgo($conv['last_message_time']); ?></small>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="chat-area">
            <?php if($other_user): ?>
            <!-- Chat Header -->
            <div class="chat-header">
                <div class="chat-user">
                    <img src="<?php echo getUserAvatar($other_user['user_id']); ?>" alt="<?php echo htmlspecialchars($other_user['full_name']); ?>">
                    <div>
                        <h3><?php echo htmlspecialchars($other_user['full_name']); ?></h3>
                        <small>
                            <?php 
                            $last_seen = $pdo->prepare("SELECT last_login FROM users WHERE user_id = ?")->execute([$other_user['user_id']]) ? $pdo->fetchColumn() : null;
                            echo $last_seen ? 'Online ' . timeAgo($last_seen) : 'Offline';
                            ?>
                        </small>
                    </div>
                </div>
                <div class="chat-actions">
                    <a href="profile.php?id=<?php echo $other_user['user_id']; ?>" class="btn-action" title="Lihat Profil">
                        <i class="fas fa-user"></i>
                    </a>
                    <button class="btn-action" title="Laporkan" onclick="reportUser(<?php echo $other_user['user_id']; ?>)">
                        <i class="fas fa-flag"></i>
                    </button>
                </div>
            </div>
            
            <!-- Messages Container -->
            <div class="messages-container" id="messagesContainer">
                <?php if(empty($messages)): ?>
                <div class="no-messages">
                    <p>Mulai percakapan dengan <?php echo htmlspecialchars($other_user['full_name']); ?></p>
                </div>
                <?php else: ?>
                <?php foreach($messages as $message): ?>
                <div class="message-row <?php echo $message['sender_id'] == $_SESSION['user_id'] ? 'sent' : 'received'; ?>">
                    <div class="message">
                        <div class="message-content">
                            <p><?php echo nl2br(htmlspecialchars($message['content'])); ?></p>
                            <span class="message-time"><?php echo date('H:i', strtotime($message['created_at'])); ?></span>
                        </div>
                        <?php if($message['sender_id'] == $_SESSION['user_id']): ?>
                        <div class="message-status">
                            <?php if($message['is_read']): ?>
                            <i class="fas fa-check-double" title="Dibaca"></i>
                            <?php else: ?>
                            <i class="fas fa-check" title="Terkirim"></i>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Message Input -->
            <div class="message-input">
                <form method="POST" id="messageForm">
                    <input type="hidden" name="to_user_id" value="<?php echo $other_user['user_id']; ?>">
                    <div class="input-group">
                        <input type="text" name="message" placeholder="Ketik pesan..." class="message-text" required>
                        <button type="submit" name="send_message" class="btn-send">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>
            </div>
            
            <?php else: ?>
            <!-- No Conversation Selected -->
            <div class="no-conversation">
                <i class="fas fa-comment-alt"></i>
                <h3>Pilih percakapan</h3>
                <p>Pilih percakapan dari daftar atau mulai chat baru</p>
                <button class="btn btn-primary" onclick="showNewChatModal()">
                    <i class="fas fa-plus"></i> Mulai Chat Baru
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- New Chat Modal -->
<div id="newChatModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Chat Baru</h3>
            <button class="btn-close" onclick="hideNewChatModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="search-users">
                <input type="text" id="userSearch" placeholder="Cari pengguna..." class="form-control" onkeyup="searchUsers(this.value)">
            </div>
            <div class="users-list" id="usersList">
                <!-- Users will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Report Modal -->
<div id="reportModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Laporkan Pengguna</h3>
            <button class="btn-close" onclick="hideReportModal()">&times;</button>
        </div>
        <form method="POST" action="../includes/report-user.php">
            <input type="hidden" id="reportUserId" name="user_id">
            <div class="modal-body">
                <div class="form-group">
                    <label>Alasan Pelaporan</label>
                    <select name="reason" class="form-control" required>
                        <option value="">Pilih Alasan</option>
                        <option value="spam">Spam atau Iklan</option>
                        <option value="scam">Penipuan</option>
                        <option value="harassment">Pelecehan</option>
                        <option value="inappropriate">Konten Tidak Pantas</option>
                        <option value="other">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Deskripsi (opsional)</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Jelaskan secara detail..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="hideReportModal()">Batal</button>
                <button type="submit" class="btn btn-danger">Laporkan</button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto scroll to bottom
function scrollToBottom() {
    const container = document.getElementById('messagesContainer');
    if(container) {
        container.scrollTop = container.scrollHeight;
    }
}

// Send message with AJAX
document.getElementById('messageForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        // Reload page to show new message
        window.location.reload();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Gagal mengirim pesan');
    });
});

// Modal functions
function showNewChatModal() {
    document.getElementById('newChatModal').style.display = 'flex';
    loadUsers();
}

function hideNewChatModal() {
    document.getElementById('newChatModal').style.display = 'none';
}

function showReportModal(userId) {
    document.getElementById('reportUserId').value = userId;
    document.getElementById('reportModal').style.display = 'flex';
}

function hideReportModal() {
    document.getElementById('reportModal').style.display = 'none';
}

// Load users for new chat
function loadUsers() {
    fetch('../includes/get-users.php')
        .then(response => response.text())
        .then(html => {
            document.getElementById('usersList').innerHTML = html;
        });
}

// Search users
function searchUsers(query) {
    if(query.length < 2) {
        loadUsers();
        return;
    }
    
    fetch('../includes/search-users.php?q=' + encodeURIComponent(query))
        .then(response => response.text())
        .then(html => {
            document.getElementById('usersList').innerHTML = html;
        });
}

// Auto refresh messages
setInterval(function() {
    if(window.location.pathname.includes('messages.php') && <?php echo $other_user ? 'true' : 'false'; ?>) {
        const lastMessageTime = document.querySelector('.message-time:last-child')?.textContent;
        
        fetch('../includes/get-new-messages.php?to=<?php echo $to_user_id; ?>&last_time=' + lastMessageTime)
            .then(response => response.json())
            .then(data => {
                if(data.new_messages && data.new_messages.length > 0) {
                    // Add new messages
                    data.new_messages.forEach(msg => {
                        const messageRow = document.createElement('div');
                        messageRow.className = 'message-row ' + (msg.sender_id == <?php echo $_SESSION['user_id']; ?> ? 'sent' : 'received');
                        messageRow.innerHTML = `
                            <div class="message">
                                <div class="message-content">
                                    <p>${msg.content}</p>
                                    <span class="message-time">${msg.time}</span>
                                </div>
                            </div>
                        `;
                        document.getElementById('messagesContainer').appendChild(messageRow);
                    });
                    
                    scrollToBottom();
                }
            });
    }
}, 5000); // Check every 5 seconds

// Initial scroll to bottom
window.addEventListener('load', scrollToBottom);
</script>

<style>
.chat-container {
    display: flex;
    height: 70vh;
    border: 1px solid var(--border-color);
    border-radius: 10px;
    overflow: hidden;
}

.conversations-sidebar {
    width: 300px;
    border-right: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
}

.conversations-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.conversations-header h3 {
    margin: 0;
    font-size: 18px;
}

.btn-new-chat {
    background: var(--primary-color);
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.conversations-list {
    flex: 1;
    overflow-y: auto;
}

.conversation-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid var(--border-light);
    text-decoration: none;
    color: var(--text-primary);
    transition: background-color 0.2s ease;
}

.conversation-item:hover,
.conversation-item.active {
    background-color: var(--bg-hover);
}

.conversation-avatar {
    position: relative;
    margin-right: 12px;
}

.conversation-avatar img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
}

.unread-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: var(--danger-color);
    color: white;
    font-size: 10px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.conversation-info {
    flex: 1;
    min-width: 0;
}

.conversation-info h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
    font-weight: 600;
}

.last-message {
    margin: 0 0 4px 0;
    font-size: 13px;
    color: var(--text-secondary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-info small {
    font-size: 11px;
    color: var(--text-light);
}

.empty-conversations {
    padding: 40px 20px;
    text-align: center;
    color: var(--text-light);
}

.empty-conversations i {
    font-size: 48px;
    margin-bottom: 10px;
    opacity: 0.5;
}

.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.chat-header {
    padding: 15px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--bg-card);
}

.chat-user {
    display: flex;
    align-items: center;
    gap: 12px;
}

.chat-user img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
}

.chat-user h3 {
    margin: 0 0 4px 0;
    font-size: 16px;
}

.chat-user small {
    font-size: 12px;
    color: var(--text-light);
}

.chat-actions {
    display: flex;
    gap: 10px;
}

.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid var(--border-color);
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: var(--text-secondary);
}

.btn-action:hover {
    background: var(--bg-hover);
    color: var(--primary-color);
}

.messages-container {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    background: var(--bg-light);
}

.message-row {
    display: flex;
    margin-bottom: 15px;
}

.message-row.sent {
    justify-content: flex-end;
}

.message-row.received {
    justify-content: flex-start;
}

.message {
    max-width: 70%;
    display: flex;
    align-items: flex-end;
    gap: 8px;
}

.message-row.sent .message {
    flex-direction: row-reverse;
}

.message-content {
    background: white;
    padding: 10px 15px;
    border-radius: 18px;
    position: relative;
}

.message-row.sent .message-content {
    background: var(--primary-color);
    color: white;
    border-bottom-right-radius: 5px;
}

.message-row.received .message-content {
    background: white;
    border-bottom-left-radius: 5px;
    border: 1px solid var(--border-color);
}

.message-content p {
    margin: 0 0 5px 0;
    font-size: 14px;
    line-height: 1.4;
}

.message-time {
    font-size: 11px;
    opacity: 0.7;
    display: block;
    text-align: right;
}

.message-status {
    font-size: 12px;
    color: var(--text-light);
}

.message-input {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    background: var(--bg-card);
}

.input-group {
    display: flex;
    gap: 10px;
}

.message-text {
    flex: 1;
    padding: 12px 15px;
    border: 1px solid var(--border-color);
    border-radius: 24px;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}

.message-text:focus {
    border-color: var(--primary-color);
}

.btn-send {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    transition: background-color 0.2s;
}

.btn-send:hover {
    background: var(--dark-color);
}

.no-conversation {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: var(--text-light);
}

.no-conversation i {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-conversation h3 {
    margin: 0 0 10px 0;
    font-size: 20px;
    color: var(--text-primary);
}

.no-conversation p {
    margin: 0 0 20px 0;
    text-align: center;
    max-width: 300px;
}

.no-messages {
    text-align: center;
    padding: 40px;
    color: var(--text-light);
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.modal-content {
    background: white;
    border-radius: 10px;
    max-width: 500px;
    width: 90%;
    max-height: 80vh;
    overflow: hidden;
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
}

.btn-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: var(--text-light);
}

.modal-body {
    padding: 20px;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    padding: 15px 20px;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.search-users {
    margin-bottom: 15px;
}

.users-list {
    max-height: 300px;
    overflow-y: auto;
}

.user-item {
    display: flex;
    align-items: center;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.2s;
}

.user-item:hover {
    background: var(--bg-hover);
}

.user-item img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    margin-right: 12px;
    object-fit: cover;
}

.user-info h4 {
    margin: 0 0 4px 0;
    font-size: 14px;
}

.user-info p {
    margin: 0;
    font-size: 12px;
    color: var(--text-light);
}
</style>

<?php include '../footer.php'; ?>