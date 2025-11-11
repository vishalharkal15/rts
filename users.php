<?php include 'templates/header.php'; ?>

<?php if($_SESSION['role']=='admin'){ ?>
<h2>Pending Users</h2>
<table border="1" cellpadding="5" style="margin-top:15px; width:100%; border-collapse:collapse;">
<tr><th>ID</th><th>Name</th><th>Email</th><th>Profile</th><th>Actions</th></tr>
<?php
$res = $mysqli->query("SELECT * FROM users WHERE status='pending'");
while($u = $res->fetch_assoc()){ ?>
<tr>
<td><?php echo $u['id']; ?></td>
<td><?php echo $u['name']; ?></td>
<td><?php echo $u['email']; ?></td>
<td><?php echo $u['role']; ?></td>
<td>
    <a href="approve_user.php?id=<?php echo $u['id']; ?>">Approve</a> | 
    <a href="delete_user.php?id=<?php echo $u['id']; ?>">Delete</a>
</td>
</tr>
<?php } ?>
</table>
<?php } ?>

<?php include 'templates/footer.php'; ?>
