<?php
session_start();
require 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';

    if(!$name || !$email || !$password){
        $message = "All fields are required.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $message = "Invalid email address.";
    } else {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res->num_rows > 0){
            $message = "Email already registered.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $status = 'pending';
            $stmt = $mysqli->prepare("INSERT INTO users(name,email,password,role,status) VALUES(?,?,?,?,?)");
            $stmt->bind_param("sssss", $name, $email, $hash, $role, $status);
            $stmt->execute();
            $message = "Registration successful! Wait for admin approval.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RTS Registration</title>
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Roboto&display=swap" rel="stylesheet">
<style>
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
    width: 380px;
    text-align: center;
}

form h2 {
    font-family: 'Orbitron', sans-serif;
    font-size: 2.5rem;
    color: #1F8FFF;
    margin-bottom: 20px;
    text-shadow: 0 0 10px #1F8FFF, 0 0 20px #FF3D3D;
}

input, select {
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

input:focus, select:focus {
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

.message {
    color: #00ff99;
    margin-bottom: 10px;
    font-weight: bold;
}

.error {
    color: #FF3D3D;
    margin-bottom: 10px;
    font-weight: bold;
}

p a {
    color: #1F8FFF;
    text-decoration: none;
    transition: all 0.3s ease;
}
p a:hover {
    text-decoration: underline;
    color: #FF3D3D;
}
</style>
</head>
<body>
<form method="POST">
    <h2>RTS Registration</h2>
    <?php if($message) echo "<p class='".(strpos($message,'successful')!==false?'message':'error')."'>$message</p>"; ?>
    <input type="text" name="name" placeholder="Full Name" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>
    <select name="role" required>
        <option value="student">Student</option>
        <option value="trainer">Trainer</option>
        <option value="intern">Intern</option>
        <option value="management">Management</option>
    </select>
    <button type="submit">Register</button>
    <p style="margin-top:10px;">Already registered? <a href="login.php">Login here</a></p>
</form>
</body>
</html>
