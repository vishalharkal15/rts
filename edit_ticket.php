<?php
include 'templates/header.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$token = $_GET['token'] ?? '';

if(!$token){
    echo "Invalid ticket.";
    exit;
}

// Fetch ticket from DB
$stmt = $mysqli->prepare("SELECT t.*, r.name AS requester_name, a.name AS assigned_name
                          FROM tickets t
                          LEFT JOIN users r ON t.requester_id = r.id
                          LEFT JOIN users a ON t.assigned_to = a.id
                          WHERE t.ticket_token = ? LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows == 0){
    echo "Ticket not found.";
    exit;
}
$ticket = $res->fetch_assoc();

// Permission check: Only admin or assigned user
if($role != 'admin' && $ticket['assigned_to'] != $user_id && $ticket['requester_id'] != $user_id){
    echo "Access denied.";
    exit;
}

// Handle update form
$message = '';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? null;
    $status = $_POST['status'] ?? $ticket['status'];

    if(!$title || !$description){
        $message = "Title and description cannot be empty.";
    } else {
        // Only admin can reassign
        if($role != 'admin'){
            $assigned_to = $ticket['assigned_to'];
        }

        $stmt = $mysqli->prepare("UPDATE tickets SET title=?, description=?, assigned_to=?, status=? WHERE ticket_token=?");
        $stmt->bind_param("ssiss", $title, $description, $assigned_to, $status, $token);
        $stmt->execute();
        $message = "Ticket updated successfully.";
        // Refresh ticket data
        $ticket['title'] = $title;
        $ticket['description'] = $description;
        $ticket['assigned_to'] = $assigned_to;
        $ticket['status'] = $status;
    }
}

// Fetch users for assignment dropdown
$users_res = $mysqli->query("SELECT id, name, role FROM users WHERE status='approved'");
$users = [];
while($u = $users_res->fetch_assoc()){
    $users[] = $u;
}
?>

<div class="edit-ticket-container">
    <h2>Edit Ticket</h2>
    <?php if($message) echo "<p style='color:green;'>$message</p>"; ?>
    <form method="POST">
        <label>Title:</label>
        <input type="text" name="title" value="<?php echo htmlspecialchars($ticket['title']); ?>" required>
        
        <label>Description:</label>
        <textarea name="description" required><?php echo htmlspecialchars($ticket['description']); ?></textarea>
        
        <?php if($role == 'admin'): ?>
        <label>Assign To:</label>
        <select name="assigned_to">
            <option value="">Unassigned</option>
            <?php foreach($users as $u): ?>
                <option value="<?php echo $u['id']; ?>" <?php echo ($ticket['assigned_to']==$u['id'])?'selected':''; ?>>
                    <?php echo $u['name'] . " (" . $u['role'] . ")"; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>

        <label>Status:</label>
        <select name="status">
            <option value="open" <?php echo ($ticket['status']=='open')?'selected':''; ?>>Open</option>
            <option value="in_progress" <?php echo ($ticket['status']=='in_progress')?'selected':''; ?>>In Progress</option>
            <option value="closed" <?php echo ($ticket['status']=='closed')?'selected':''; ?>>Closed</option>
        </select>
        <br><br>
        <button type="submit">Update Ticket</button>
        <a href="index.php" style="margin-left:10px;">Back to Dashboard</a>
    </form>
</div>

<style>
.edit-ticket-container { max-width:600px; margin:30px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); }
.edit-ticket-container h2 { color:#c62828; }
.edit-ticket-container input, .edit-ticket-container textarea, .edit-ticket-container select {
    width:100%; padding:10px; margin:10px 0; border:1px solid #ccc; border-radius:4px;
}
.edit-ticket-container button {
    padding:10px 20px; background:#c62828; color:#fff; border:none; border-radius:4px; cursor:pointer;
}
.edit-ticket-container button:hover { background:#b71c1c; }
</style>

<?php include 'templates/footer.php'; ?>
