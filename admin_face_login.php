<?php
session_start();
require 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: index.php");
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['image_data'])) {
        // Facial recognition login
        $image_data = $_POST['image_data'];
        
        // Call Python Flask API for authentication
        $api_url = 'http://localhost:5000/api/authenticate';
        
        $data = json_encode([
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
                // Face recognized! Get admin from database
                $admin_id = $result['admin_id'];
                
                $stmt = $mysqli->prepare("SELECT * FROM users WHERE id=? AND role='admin' AND status='approved' LIMIT 1");
                $stmt->bind_param("i", $admin_id);
                $stmt->execute();
                $res = $stmt->get_result();
                
                if ($res->num_rows > 0) {
                    $user = $res->fetch_assoc();
                    
                    // Set session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = 'admin';
                    $_SESSION['face_auth'] = true;
                    $_SESSION['face_confidence'] = $result['confidence'];
                    
                    // Log successful face authentication
                    $log_stmt = $mysqli->prepare("INSERT INTO admin_login_logs(admin_id, login_type, confidence, login_at) VALUES(?,?,?,NOW())");
                    $login_type = 'face_recognition';
                    $confidence = $result['confidence'];
                    $log_stmt->bind_param("isd", $admin_id, $login_type, $confidence);
                    $log_stmt->execute();
                    
                    header("Location: admin_panel.php");
                    exit;
                } else {
                    $error = "Admin account not found or not approved.";
                }
            } else {
                $error = $result['message'] ?? 'Face authentication failed. Please try again.';
            }
        } else {
            $error = "Unable to connect to facial recognition service. Please try password login.";
        }
    } else {
        // Traditional password login
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (!$email || !$password) {
            $error = "Please enter both email and password.";
        } else {
            $stmt = $mysqli->prepare("SELECT * FROM users WHERE email=? AND role='admin' LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                $error = "Admin account not found.";
            } else {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    if ($user['status'] != 'approved') {
                        $error = "Account pending approval.";
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['role'] = 'admin';
                        $_SESSION['face_auth'] = false;
                        
                        // Log password authentication
                        $log_stmt = $mysqli->prepare("INSERT INTO admin_login_logs(admin_id, login_type, login_at) VALUES(?,?,NOW())");
                        $login_type = 'password';
                        $log_stmt->bind_param("is", $user['id'], $login_type);
                        $log_stmt->execute();
                        
                        header("Location: admin_panel.php");
                        exit;
                    }
                } else {
                    $error = "Incorrect password.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - Facial Recognition</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Roboto&display=swap" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(135deg, #0B0B0B, #1a0033);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    color: #fff;
}

body::before {
    content: '';
    position: absolute;
    top:0; left:0; width:100%; height:100%;
    background: radial-gradient(circle, rgba(31,143,255,0.15) 0%, transparent 70%);
    animation: pulse 6s infinite alternate;
    z-index:0;
}

@keyframes pulse {
    0% { transform: scale(1) translate(0,0);}
    100% { transform: scale(1.05) translate(-20px,-20px);}
}

.container {
    position: relative;
    z-index:1;
    background: rgba(20,20,20,0.85);
    backdrop-filter: blur(10px);
    padding: 40px 35px;
    border-radius: 20px;
    box-shadow: 0 0 40px rgba(31,143,255,0.5), 0 0 80px rgba(255,61,61,0.3);
    width: 450px;
    text-align: center;
}

h2 {
    font-family: 'Orbitron', sans-serif;
    font-size: 2.2rem;
    color: #1F8FFF;
    margin-bottom: 10px;
    text-shadow: 0 0 15px #1F8FFF, 0 0 30px #FF3D3D;
}

.subtitle {
    color: #aaa;
    margin-bottom: 30px;
    font-size: 0.95rem;
}

.tab-container {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
}

.tab-btn {
    flex: 1;
    padding: 12px;
    border: 2px solid #1F8FFF;
    background: rgba(31,143,255,0.1);
    color: #1F8FFF;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s;
    font-family: 'Orbitron', sans-serif;
}

.tab-btn.active {
    background: #1F8FFF;
    color: #fff;
    box-shadow: 0 0 20px #1F8FFF;
}

.tab-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 15px #1F8FFF;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* Video/Camera Section */
#video-container {
    position: relative;
    margin: 20px 0;
    border-radius: 15px;
    overflow: hidden;
    border: 3px solid #1F8FFF;
    box-shadow: 0 0 20px rgba(31,143,255,0.4);
}

#video {
    width: 100%;
    height: auto;
    display: block;
    background: #000;
}

#canvas {
    display: none;
}

.face-indicator {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 200px;
    height: 200px;
    border: 3px dashed #1F8FFF;
    border-radius: 50%;
    animation: scan 2s infinite;
}

@keyframes scan {
    0%, 100% { border-color: #1F8FFF; }
    50% { border-color: #FF3D3D; }
}

.camera-controls {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

button {
    flex: 1;
    padding: 14px;
    border: none;
    border-radius: 8px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-family: 'Orbitron', sans-serif;
}

.btn-primary {
    background: #1F8FFF;
    color: #fff;
    box-shadow: 0 0 15px rgba(31,143,255,0.5);
}

.btn-primary:hover {
    background: #0d7ee0;
    box-shadow: 0 0 25px #1F8FFF;
    transform: scale(1.05);
}

.btn-danger {
    background: #c62828;
    color: #fff;
    box-shadow: 0 0 15px rgba(198,40,40,0.5);
}

.btn-danger:hover {
    background: #b71c1c;
    box-shadow: 0 0 25px #FF3D3D;
    transform: scale(1.05);
}

/* Password Form */
input[type="email"], input[type="password"] {
    width: 100%;
    padding: 14px 18px;
    margin: 12px 0;
    border-radius: 8px;
    border: 1px solid #1F8FFF;
    background: rgba(255,255,255,0.05);
    color: #fff;
    outline: none;
    transition: all 0.3s ease;
    font-size: 1rem;
}

input:focus {
    border-color: #FF3D3D;
    box-shadow: 0 0 15px rgba(255,61,61,0.5);
}

.error {
    background: rgba(255,61,61,0.2);
    border: 1px solid #FF3D3D;
    color: #FF3D3D;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-weight: bold;
}

.success {
    background: rgba(76,175,80,0.2);
    border: 1px solid #4caf50;
    color: #4caf50;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-weight: bold;
}

.status-text {
    margin: 15px 0;
    padding: 10px;
    border-radius: 6px;
    font-size: 0.95rem;
    font-weight: bold;
}

.status-scanning {
    background: rgba(31,143,255,0.2);
    border: 1px solid #1F8FFF;
    color: #1F8FFF;
}

.status-success {
    background: rgba(76,175,80,0.2);
    border: 1px solid #4caf50;
    color: #4caf50;
}

.status-failed {
    background: rgba(255,61,61,0.2);
    border: 1px solid #FF3D3D;
    color: #FF3D3D;
}

.loader {
    border: 4px solid rgba(31,143,255,0.2);
    border-top: 4px solid #1F8FFF;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 20px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.back-link {
    margin-top: 20px;
    color: #1F8FFF;
    text-decoration: none;
    transition: color 0.3s;
}

.back-link:hover {
    color: #FF3D3D;
    text-decoration: underline;
}

#confidence-meter {
    margin-top: 15px;
}

.confidence-bar {
    width: 100%;
    height: 8px;
    background: rgba(255,255,255,0.1);
    border-radius: 4px;
    overflow: hidden;
}

.confidence-fill {
    height: 100%;
    background: linear-gradient(90deg, #FF3D3D, #ffa500, #4caf50);
    transition: width 0.5s;
    width: 0%;
}
</style>
</head>
<body>

<div class="container">
    <h2>🔐 Admin Login</h2>
    <p class="subtitle">Secure Facial Recognition Authentication</p>
    
    <?php if($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <!-- Tab Buttons -->
    <div class="tab-container">
        <button class="tab-btn active" onclick="switchTab('face')">👤 Face Recognition</button>
        <button class="tab-btn" onclick="switchTab('password')">🔑 Password</button>
    </div>
    
    <!-- Face Recognition Tab -->
    <div id="face-tab" class="tab-content active">
        <div id="video-container">
            <video id="video" autoplay playsinline></video>
            <div class="face-indicator"></div>
        </div>
        <canvas id="canvas"></canvas>
        
        <div id="status"></div>
        
        <div class="camera-controls">
            <button id="start-camera" class="btn-primary" onclick="startCamera()">
                📷 Start Camera
            </button>
            <button id="capture-btn" class="btn-primary" onclick="authenticateFace()" style="display:none;">
                🔍 Authenticate
            </button>
        </div>
        
        <div id="confidence-meter" style="display:none;">
            <p>Confidence: <span id="confidence-value">0%</span></p>
            <div class="confidence-bar">
                <div class="confidence-fill" id="confidence-fill"></div>
            </div>
        </div>
    </div>
    
    <!-- Password Tab -->
    <div id="password-tab" class="tab-content">
        <form method="POST">
            <input type="email" name="email" placeholder="Admin Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" class="btn-danger">Login with Password</button>
        </form>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="login.php" class="back-link">← Back to User Login</a>
    </div>
</div>

<script>
let video = document.getElementById('video');
let canvas = document.getElementById('canvas');
let stream = null;
let isAuthenticating = false;

function switchTab(tab) {
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Update tab content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    if(tab === 'face') {
        document.getElementById('face-tab').classList.add('active');
    } else {
        document.getElementById('password-tab').classList.add('active');
        stopCamera();
    }
}

async function startCamera() {
    try {
        stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 640 },
                height: { ideal: 480 }
            } 
        });
        video.srcObject = stream;
        
        document.getElementById('start-camera').style.display = 'none';
        document.getElementById('capture-btn').style.display = 'block';
        
        showStatus('Camera ready! Position your face in the circle', 'scanning');
    } catch(err) {
        console.error('Error accessing camera:', err);
        showStatus('Unable to access camera. Please check permissions.', 'failed');
    }
}

function stopCamera() {
    if(stream) {
        stream.getTracks().forEach(track => track.stop());
        video.srcObject = null;
        stream = null;
    }
    document.getElementById('start-camera').style.display = 'block';
    document.getElementById('capture-btn').style.display = 'none';
}

async function authenticateFace() {
    if(isAuthenticating) return;
    isAuthenticating = true;
    
    showStatus('Analyzing face... Please hold still', 'scanning');
    document.getElementById('capture-btn').disabled = true;
    
    // Capture image from video
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    let ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);
    
    // Convert to base64
    let imageData = canvas.toDataURL('image/jpeg');
    
    // Send to server
    try {
        let response = await fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'image_data=' + encodeURIComponent(imageData)
        });
        
        // Reload page to handle server-side redirect
        location.reload();
        
    } catch(err) {
        console.error('Authentication error:', err);
        showStatus('Authentication failed. Please try again.', 'failed');
        document.getElementById('capture-btn').disabled = false;
        isAuthenticating = false;
    }
}

function showStatus(message, type) {
    let statusDiv = document.getElementById('status');
    statusDiv.innerHTML = `<div class="status-text status-${type}">${message}</div>`;
}

function updateConfidence(value) {
    document.getElementById('confidence-meter').style.display = 'block';
    document.getElementById('confidence-value').textContent = value + '%';
    document.getElementById('confidence-fill').style.width = value + '%';
}

// Auto-start camera on page load
window.addEventListener('load', () => {
    setTimeout(startCamera, 500);
});

// Cleanup on page unload
window.addEventListener('beforeunload', stopCamera);
</script>

</body>
</html>
