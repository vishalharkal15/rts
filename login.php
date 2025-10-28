<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$message = '';

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
                    header("Location: index.php");
                    exit;
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

/* Animated Background (optional) */
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

/* Form Container */
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

/* Heading */
form h2 {
    font-family: 'Orbitron', sans-serif;
    font-size: 2.5rem;
    color: #1F8FFF;
    margin-bottom: 20px;
    text-shadow: 0 0 10px #1F8FFF, 0 0 20px #FF3D3D;
}

/* Input Fields */
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

/* Button */
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

/* Error Message */
.error {
    color: #FF3D3D;
    margin-bottom: 10px;
    font-weight: bold;
}

/* Register Link */
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
</body>
</html>
