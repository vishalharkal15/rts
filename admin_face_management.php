<?php
/**
 * Admin Face Management Panel
 * Register, view, and manage facial recognition for admins
 */

require_once 'config.php';
include 'templates/header.php';

// Admin access check
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    echo "<div style='padding:20px; text-align:center; font-weight:bold; color:red;'>Access Denied. Admins only.</div>";
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Check if face API is running
function checkFaceAPI() {
    $ch = curl_init('http://localhost:5000/api/health');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($http_code == 200);
}

$api_running = checkFaceAPI();

// Handle face registration
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_face'])) {
    $image_data = $_POST['image_data'] ?? '';
    
    if ($image_data) {
        $api_url = 'http://localhost:5000/api/register';
        
        $data = json_encode([
            'admin_id' => $user_id,
            'admin_name' => $_SESSION['name'],
            'image' => $image_data
        ]);
        
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code == 200 && $response) {
            $result = json_decode($response, true);
            
            if ($result['success']) {
                // Update database
                $stmt = $mysqli->prepare("UPDATE users SET face_registered=1, face_registered_at=NOW() WHERE id=?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                
                $message = "Face registered successfully! You can now use facial recognition to login.";
            } else {
                $error = $result['message'] ?? 'Face registration failed.';
            }
        } else {
            $error = "Unable to connect to facial recognition service.";
        }
    }
}

// Get current admin's face status
$stmt = $mysqli->prepare("SELECT face_registered, face_registered_at FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Get all admins with face registration status
$all_admins = $mysqli->query("
    SELECT id, name, email, face_registered, face_registered_at 
    FROM users 
    WHERE role='admin' AND status='approved'
    ORDER BY name ASC
");

// Get login logs
$logs = $mysqli->query("
    SELECT l.*, u.name as admin_name 
    FROM admin_login_logs l
    LEFT JOIN users u ON l.admin_id = u.id
    ORDER BY l.login_at DESC
    LIMIT 20
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Face Recognition Management</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Roboto&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(135deg, #0B0B0B, #1a1a2e);
    margin:0; padding:20px;
    color:#fff;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
}

h1, h2 {
    font-family: 'Orbitron', sans-serif;
    color: #1F8FFF;
    text-shadow: 0 0 10px #1F8FFF;
}

.status-banner {
    background: rgba(20,20,20,0.8);
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 5px solid #1F8FFF;
}

.status-banner.error {
    border-left-color: #FF3D3D;
}

.status-banner.success {
    border-left-color: #4caf50;
}

.api-status {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: bold;
}

.api-status.online {
    background: #4caf50;
    color: #fff;
}

.api-status.offline {
    background: #FF3D3D;
    color: #fff;
}

.card {
    background: rgba(20,20,20,0.8);
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 0 30px rgba(31,143,255,0.3);
    margin-bottom: 25px;
}

.video-container {
    position: relative;
    max-width: 640px;
    margin: 20px auto;
    border-radius: 15px;
    overflow: hidden;
    border: 3px solid #1F8FFF;
}

#video {
    width: 100%;
    height: auto;
    display: block;
}

#canvas {
    display: none;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    text-transform: uppercase;
    margin: 5px;
    font-family: 'Orbitron', sans-serif;
}

.btn-primary {
    background: #1F8FFF;
    color: #fff;
}

.btn-primary:hover {
    background: #0d7ee0;
    box-shadow: 0 0 20px #1F8FFF;
    transform: scale(1.05);
}

.btn-success {
    background: #4caf50;
    color: #fff;
}

.btn-danger {
    background: #c62828;
    color: #fff;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

th {
    background: rgba(31,143,255,0.2);
    font-family: 'Orbitron', sans-serif;
    color: #1F8FFF;
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 0.85rem;
    font-weight: bold;
}

.badge-yes {
    background: #4caf50;
    color: #fff;
}

.badge-no {
    background: #666;
    color: #fff;
}

.badge-face {
    background: #1F8FFF;
    color: #fff;
}

.badge-password {
    background: #ff9800;
    color: #fff;
}

.controls {
    text-align: center;
    margin: 20px 0;
}

#status-text {
    margin: 15px 0;
    padding: 10px;
    border-radius: 6px;
    font-weight: bold;
}

.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.stat-card {
    background: rgba(31,143,255,0.1);
    padding: 20px;
    border-radius: 10px;
    border: 2px solid #1F8FFF;
    text-align: center;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    color: #1F8FFF;
    font-family: 'Orbitron', sans-serif;
}

.stat-label {
    color: #aaa;
    margin-top: 5px;
}
</style>
</head>
<body>

<div class="container">
    <h1>🎭 Face Recognition Management</h1>
    
    <!-- API Status -->
    <div class="status-banner">
        <strong>Facial Recognition API:</strong> 
        <span class="api-status <?php echo $api_running ? 'online' : 'offline'; ?>">
            <?php echo $api_running ? '● Online' : '● Offline'; ?>
        </span>
        <?php if(!$api_running): ?>
            <p style="margin-top:10px; color:#FF3D3D;">
                ⚠️ Face API is offline. Start it with: <code>python3 face_recognition/face_api.py</code>
            </p>
        <?php endif; ?>
    </div>
    
    <?php if($message): ?>
        <div class="status-banner success">✓ <?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    
    <?php if($error): ?>
        <div class="status-banner error">✗ <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $all_admins->num_rows; ?></div>
            <div class="stat-label">Total Admins</div>
        </div>
        <div class="stat-card">
            <?php
            $face_count = $mysqli->query("SELECT COUNT(*) as cnt FROM users WHERE role='admin' AND face_registered=1")->fetch_assoc()['cnt'];
            ?>
            <div class="stat-number"><?php echo $face_count; ?></div>
            <div class="stat-label">Face Registered</div>
        </div>
        <div class="stat-card">
            <?php
            $face_logins = $mysqli->query("SELECT COUNT(*) as cnt FROM admin_login_logs WHERE login_type='face_recognition' AND login_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetch_assoc()['cnt'];
            ?>
            <div class="stat-number"><?php echo $face_logins; ?></div>
            <div class="stat-label">Face Logins (24h)</div>
        </div>
    </div>
    
    <!-- Register Your Face -->
    <div class="card">
        <h2>📸 Register Your Face</h2>
        <p>
            Status: 
            <?php if($admin['face_registered']): ?>
                <span class="badge badge-yes">✓ Face Registered</span>
                <span style="color:#aaa; font-size:0.9rem;">
                    (Registered: <?php echo date('Y-m-d H:i', strtotime($admin['face_registered_at'])); ?>)
                </span>
            <?php else: ?>
                <span class="badge badge-no">✗ Not Registered</span>
            <?php endif; ?>
        </p>
        
        <?php if($api_running): ?>
            <div class="video-container">
                <video id="video" autoplay playsinline></video>
            </div>
            <canvas id="canvas"></canvas>
            
            <div class="controls">
                <button class="btn btn-primary" id="start-camera" onclick="startCamera()">
                    📷 Start Camera
                </button>
                <button class="btn btn-success" id="capture-btn" onclick="captureFace()" style="display:none;">
                    💾 Capture & Register
                </button>
            </div>
            
            <div id="status-text"></div>
            
            <form id="register-form" method="POST" style="display:none;">
                <input type="hidden" name="image_data" id="image_data">
                <input type="hidden" name="register_face" value="1">
            </form>
        <?php else: ?>
            <p style="color:#FF3D3D;">Cannot register face while API is offline.</p>
        <?php endif; ?>
    </div>
    
    <!-- All Admins -->
    <div class="card">
        <h2>👥 All Admins</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Face Registered</th>
                <th>Registered At</th>
            </tr>
            <?php while($admin_row = $all_admins->fetch_assoc()): ?>
            <tr>
                <td><?php echo $admin_row['id']; ?></td>
                <td><?php echo htmlspecialchars($admin_row['name']); ?></td>
                <td><?php echo htmlspecialchars($admin_row['email']); ?></td>
                <td>
                    <?php if($admin_row['face_registered']): ?>
                        <span class="badge badge-yes">✓ Yes</span>
                    <?php else: ?>
                        <span class="badge badge-no">✗ No</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php echo $admin_row['face_registered_at'] ? date('Y-m-d H:i', strtotime($admin_row['face_registered_at'])) : '-'; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
    
    <!-- Recent Login Activity -->
    <div class="card">
        <h2>📊 Recent Login Activity</h2>
        <table>
            <tr>
                <th>Admin</th>
                <th>Login Type</th>
                <th>Confidence</th>
                <th>Time</th>
            </tr>
            <?php while($log = $logs->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($log['admin_name']); ?></td>
                <td>
                    <?php if($log['login_type'] == 'face_recognition'): ?>
                        <span class="badge badge-face">👤 Face</span>
                    <?php else: ?>
                        <span class="badge badge-password">🔑 Password</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php echo $log['confidence'] ? round($log['confidence'], 1) . '%' : '-'; ?>
                </td>
                <td><?php echo date('Y-m-d H:i:s', strtotime($log['login_at'])); ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
    
    <div style="text-align:center; margin-top:30px;">
        <a href="admin_face_login.php" class="btn btn-primary">🔐 Try Face Login</a>
        <a href="admin_panel.php" class="btn btn-danger">← Back to Admin Panel</a>
    </div>
</div>

<script>
let video = document.getElementById('video');
let canvas = document.getElementById('canvas');
let stream = null;

async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { width: { ideal: 640 }, height: { ideal: 480 } } 
        });
        video.srcObject = stream;
        
        document.getElementById('start-camera').style.display = 'none';
        document.getElementById('capture-btn').style.display = 'inline-block';
        
        showStatus('Camera ready! Position your face in center', '#1F8FFF');
    } catch(err) {
        console.error('Error accessing camera:', err);
        showStatus('Unable to access camera. Check permissions.', '#FF3D3D');
    }
}

function stopCamera() {
    if(stream) {
        stream.getTracks().forEach(track => track.stop());
        video.srcObject = null;
        stream = null;
    }
}

function captureFace() {
    showStatus('Capturing face...', '#1F8FFF');
    
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    let ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    
    let imageData = canvas.toDataURL('image/jpeg');
    document.getElementById('image_data').value = imageData;
    
    showStatus('Registering face... Please wait', '#ff9800');
    
    stopCamera();
    document.getElementById('register-form').submit();
}

function showStatus(message, color) {
    let statusDiv = document.getElementById('status-text');
    statusDiv.textContent = message;
    statusDiv.style.background = `rgba(${color === '#1F8FFF' ? '31,143,255' : color === '#FF3D3D' ? '255,61,61' : '255,152,0'}, 0.2)`;
    statusDiv.style.border = `1px solid ${color}`;
    statusDiv.style.color = color;
}

window.addEventListener('beforeunload', stopCamera);
</script>

</body>
</html>
