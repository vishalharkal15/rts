<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = '';
$is_admin = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $message = "Please enter both email and password.";
    } else {
        $stmt = $mysqli->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $message = "User not found.";
        } else {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                if ($user['status'] != 'approved') {
                    $message = "Account pending approval by admin.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['role'] = $user['role'];

                    if ($user['role'] == 'admin') {
                        $is_admin = true;
                    }

                    $message = 'Logging you in... Please wait for recognition.';
                }
            } else {
                $message = "Incorrect password.";
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
<title>RTS Login</title>
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Roboto&display=swap" rel="stylesheet">
<style>
/* General Reset */
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(135deg, #0B0B0B, #121212);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    color: #fff;
}

body::before {
    content: '';
    position: absolute;
    top:0; left:0;
    width:100%; height:100%;
    background: radial-gradient(circle, rgba(31,143,255,0.1) 0%, transparent 70%);
    animation: pulse 6s infinite alternate;
    z-index:0;
}
@keyframes pulse {
    0% { transform: scale(1) translate(0,0);}
    100% { transform: scale(1.05) translate(-20px,-20px);}
}

form {
    position: relative;
    z-index:1;
    background: rgba(20,20,20,0.8);
    backdrop-filter: blur(10px);
    padding: 40px 30px;
    border-radius: 15px;
    box-shadow: 0 0 30px rgba(31,143,255,0.4);
    width: 350px;
    text-align: center;
}

form h2 {
    font-family: 'Orbitron', sans-serif;
    font-size: 2.5rem;
    color: #1F8FFF;
    margin-bottom: 20px;
    text-shadow: 0 0 10px #1F8FFF, 0 0 20px #FF3D3D;
}

input[type="email"], input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    margin: 12px 0;
    border-radius: 8px;
    border: 1px solid #1F8FFF;
    background: rgba(255,255,255,0.05);
    color: #fff;
    outline: none;
    transition: all 0.3s ease;
}
input:focus {
    border-color: #FF3D3D;
    box-shadow: 0 0 10px #FF3D3D;
}

button {
    width: 100%;
    padding: 12px;
    margin-top: 15px;
    border: none;
    border-radius: 8px;
    background: #c62828;
    color: #fff;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 1px;
    box-shadow: 0 0 10px #c62828;
}
button:hover {
    background: #b71c1c;
    box-shadow: 0 0 20px #FF3D3D, 0 0 40px #1F8FFF;
    transform: scale(1.05);
}

.error {
    color: #FF3D3D;
    margin-bottom: 10px;
    font-weight: bold;
}

.register-link {
    margin-top: 20px;
    font-size: 0.9rem;
}
.register-link a {
    color: #1F8FFF;
    text-decoration: none;
    transition: all 0.3s ease;
}
.register-link a:hover {
    text-decoration: underline;
    color: #FF3D3D;
}
</style>
</head>
<body>
<form method="POST">
    <h2>RTS Login</h2>
    <?php if($message) echo "<p class='error'>$message</p>"; ?>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit">Login</button>
    <div class="register-link">
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</form>

<script>
<?php if ($is_admin): ?>
    let recognized = false;
    let timeoutHandle;

    // Start webcam and recognition
    const startRecognition = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            const video = document.createElement('video');
            video.srcObject = stream;
            video.play();
            document.body.appendChild(video);
            video.style.position = 'absolute';
            video.style.bottom = '20px';
            video.style.right = '20px';
            video.style.width = '200px';
            video.style.border = '2px solid #1F8FFF';
            video.style.borderRadius = '10px';
            video.style.zIndex = '9999';

            const captureInterval = setInterval(async () => {
                if (recognized) return;

                const canvas = document.createElement('canvas');
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const imageData = canvas.toDataURL('image/jpeg');

                try {
                    const response = await fetch('https://192.168.1.15:5173/recognize', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ image: imageData })
                    });
                    const data = await response.json();

                    if (data.faces && data.faces.length > 0 && data.faces[0].name === 'Vikram') {
                        recognized = true;
                        clearInterval(captureInterval);
                        clearTimeout(timeoutHandle);
                        stream.getTracks().forEach(track => track.stop());
                        alert("Welcome Vikram!");
                        window.location.href = "index.php";
                    } else {
                        console.log("Face not recognized yet...");
                    }
                } catch (err) {
                    console.error("Error calling recognition API:", err);
                }
            }, 15000);

            // Timeout: 5 seconds to recognize
            timeoutHandle = setTimeout(() => {
                if (!recognized) {
                    stream.getTracks().forEach(track => track.stop());
                    alert("Face not recognized within 10 seconds. Logging out...");
                    window.location.href = "logout.php";
                }
            }, 25000);

        } catch (err) {
            console.error("Camera access error:", err);
            alert("Camera access denied. Logging out...");
            window.location.href = "logout.php";
        }
    };

    startRecognition();
<?php endif; ?>
</script>
</body>
</html>
