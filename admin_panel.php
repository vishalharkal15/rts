<?php
require_once 'config.php';
include 'templates/header.php';

// Admin access check
if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
    echo "<div style='padding:20px; text-align:center; font-weight:bold; color:red;'>Access Denied. Admins only.</div>";
    exit;
}

// Fetch all users
$result = $mysqli->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Panel - RTS</title>
<link rel="icon" type="image/x-icon" href="favicon.ico">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Roboto&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(135deg, #2c2c54, #b33939);
    margin:0; padding:20px;
    color:#fff;
}

h2 {
    text-align:center;
    font-family: 'Orbitron', sans-serif;
    color:#ffd32a;
    text-shadow:0 0 10px #ff6b6b;
    margin-bottom: 25px;
}

.table-container {
    background:#1e272e;
    padding:20px;
    border-radius:12px;
    box-shadow:0 8px 25px rgba(0,0,0,0.6);
    overflow-x:auto;
}

table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 10px;
    border-radius:12px;
    overflow:hidden;
}

th, td {
    padding:12px;
    text-align:left;
    border-bottom:1px solid rgba(255,255,255,0.15);
    color:#eee;
}

th {
    background:#ff6b6b;
    color:#fff;
    font-family:'Orbitron', sans-serif;
    text-transform:uppercase;
    letter-spacing:1px;
}

tr:hover { background: rgba(255,255,255,0.08); }

button {
    padding:6px 12px;
    margin:2px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-weight:bold;
    transition: all 0.3s ease;
}

.approve {
    background:#4cd137;
    color:#fff;
}
.approve:hover {
    background:#44bd32;
    box-shadow:0 0 12px #4cd137;
}

.delete {
    background:#e84118;
    color:#fff;
}
.delete:hover {
    background:#c23616;
    box-shadow:0 0 12px #e84118;
}

.status-badge {
    padding:4px 10px;
    border-radius:12px;
    font-size:12px;
    font-weight:bold;
    text-transform:capitalize;
}
.status-approved { background:#44bd32; color:#fff; }
.status-pending { background:#e1b12c; color:#fff; }
.status-rejected { background:#c23616; color:#fff; }

#notification-box {
    position:fixed;
    top:15px;
    right:15px;
    background: #ffd32a;
    color:#000;
    padding:12px 22px;
    border-radius:8px;
    display:none;
    box-shadow:0 4px 12px rgba(0,0,0,0.4);
    font-weight:bold;
    font-family:'Orbitron', sans-serif;
    z-index:1000;
}

@media(max-width:768px){
    th, td { font-size:13px; }
    button { font-size:12px; padding:5px 10px; }
}
</style>
</head>
<body>

<div id="notification-box">⚡ New user registration received!</div>

<h2>Admin Panel - User Management</h2>

<div class="table-container">
<table>
<tr>
    <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th>
</tr>
<?php foreach($users as $user): ?>
<tr>
    <td><?= $user['id'] ?></td>
    <td><?= htmlspecialchars($user['name']) ?></td>
    <td><?= htmlspecialchars($user['email']) ?></td>
    <td><?= ucfirst($user['role']) ?></td>
    <td>
        <span class="status-badge status-<?= strtolower($user['status']); ?>">
            <?= htmlspecialchars($user['status']); ?>
        </span>
    </td>
    <td>
        <?php if($user['status'] === 'pending'): ?>
            <button class="approve" onclick="approveUser(<?= $user['id'] ?>)">Approve</button>
        <?php endif; ?>
        <button class="delete" onclick="deleteUser(<?= $user['id'] ?>)">Delete</button>
    </td>
</tr>
<?php endforeach; ?>
</table>
</div>

<script>
function approveUser(id){
    if(confirm('Approve this user?')){
        $.post('api.php?action=approve_user',{id:id},function(res){
            const r = typeof res === 'string' ? JSON.parse(res) : res;
            if(r.success){ location.reload(); } else { alert(r.error); }
        });
    }
}

function deleteUser(id){
    if(confirm('Delete this user?')){
        $.post('api.php?action=delete_user',{id:id},function(res){
            const r = typeof res === 'string' ? JSON.parse(res) : res;
            if(r.success){ location.reload(); } else { alert(r.error); }
        });
    }
}

// Real-time notification for new registrations
function checkNewRegistrations() {
    $.get('api.php?action=pending_users_count', function(data){
        const res = typeof data === 'string' ? JSON.parse(data) : data;
        if(res.count > 0){
            $('#notification-box').fadeIn().delay(4000).fadeOut();
        }
    });
}

// Poll every 10 seconds
setInterval(checkNewRegistrations, 10000);
checkNewRegistrations();
</script>

</body>
</html>
