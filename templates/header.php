<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id   = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Guest';
$role      = strtolower($_SESSION['role'] ?? 'user');
?>

<header class="rts-header">
    <div class="logo">
        <a href="index.php">
            <img src="assets/images/rts_logo.png" alt="RTS Logo">
        </a>
    </div>

    <nav class="nav-links">
        <span class="user-info">
            Hello, <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($role); ?>)
        </span>
        <a href="index.php">Home</a>
        <?php if ($role === 'admin'): ?>
            <a href="admin_panel.php">Admin Panel</a>
        <?php endif; ?>
        <a href="chat.php">Live Chat</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<style>
/* Header Container */
.rts-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 30px;
    background: #0B0B0B;
    border-bottom: 2px solid #1F8FFF;
    box-shadow: 0 4px 15px rgba(31,143,255,0.3);
    position: sticky;
    top: 0;
    z-index: 1000;
}

/* Logo */
.rts-header .logo img {
    height: 55px;
    transition: transform 0.3s ease;
}
.rts-header .logo img:hover {
    transform: scale(1.05) rotate(-2deg);
}

/* Navigation Links */
.nav-links {
    display: flex;
    align-items: center;
    gap: 20px;
    font-family: 'Orbitron', sans-serif;
    font-weight: 500;
}

.nav-links .user-info {
    color: #FF3D3D;
    margin-right: 10px;
    font-size: 0.95rem;
}

.nav-links a {
    color: #1F8FFF;
    text-decoration: none;
    padding: 6px 14px;
    border-radius: 6px;
    position: relative;
    transition: all 0.3s ease;
}

.nav-links a::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 0%;
    height: 2px;
    background: #FF3D3D;
    transition: width 0.3s;
}

.nav-links a:hover {
    color: #FF3D3D;
    box-shadow: 0 0 10px #1F8FFF, 0 0 20px #FF3D3D;
}

.nav-links a:hover::after {
    width: 100%;
}

/* Responsive */
@media (max-width: 768px) {
    .rts-header {
        flex-direction: column;
        gap: 10px;
    }
    .nav-links {
        flex-direction: column;
        gap: 8px;
    }
}
</style>
