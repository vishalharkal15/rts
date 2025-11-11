<?php
session_start();
require_once 'config.php'; // DB connection

header('Content-Type: application/json');

// Ensure user is logged in
if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'error'=>'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = strtolower($_SESSION['role'] ?? 'user'); // normalize to lowercase
$action = $_GET['action'] ?? '';

switch($action){

    // Fetch all approved users for dropdown
    case 'get_users':
        $users = [];
        $res = $mysqli->query("SELECT id, name, role FROM users WHERE status='approved' ORDER BY name ASC");
        while($row = $res->fetch_assoc()){
            $users[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'role' => $row['role']
            ];
        }
        echo json_encode($users);
    break;

    // Fetch tickets visible to the user
    case 'get_tickets':
    $tickets = [];
    $res = $mysqli->query("
        SELECT t.*, 
               r.name AS requester_name,
               a.name AS assigned_name
        FROM tickets t
        LEFT JOIN users r ON t.requester_id = r.id
        LEFT JOIN users a ON t.assigned_to = a.id
        ORDER BY t.id DESC
    ");

    if($res){
        while($row = $res->fetch_assoc()){
            // Non-admins see only tickets requested by them or assigned to them
            if($role !== 'admin' && $row['requester_id'] != $user_id && $row['assigned_to'] != $user_id){
                continue;
            }

            // Make sure assigned_to is integer or null
            $row['assigned_to'] = $row['assigned_to'] !== null ? intval($row['assigned_to']) : null;
            $tickets[] = $row;
        }
    }

    echo json_encode($tickets);
    exit;
break;


    // Create ticket
    case 'create_ticket':
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $assigned_to = $_POST['assigned_to'] ?? null;

        if(!$title || !$description){
            echo json_encode(['success'=>false,'error'=>'Title and description required']);
            exit;
        }

        $token = bin2hex(random_bytes(8));
        $status = 'open';

        $assigned_to_param = ($assigned_to === '' || $assigned_to === null) ? null : intval($assigned_to);

        $stmt = $mysqli->prepare("INSERT INTO tickets(title, description, assigned_to, requester_id, status, ticket_token) VALUES(?,?,?,?,?,?)");
        $stmt->bind_param("ssisss", $title, $description, $assigned_to_param, $user_id, $status, $token);

        if(!$stmt){
            echo json_encode(['success'=>false,'error'=>$mysqli->error]);
            exit;
        }

        $stmt->execute();

        if($stmt->affected_rows > 0){
            echo json_encode(['success'=>true, 'ticket_token'=>$token]);
        } else {
            echo json_encode(['success'=>false,'error'=>'Failed to create ticket']);
        }
    break;

    // Update ticket status
    case 'update_status':
        $token = $_POST['token'] ?? '';
        $status = $_POST['status'] ?? '';

        if(!in_array($status, ['open','in_progress','closed'])){
            echo json_encode(['success'=>false,'error'=>'Invalid status']);
            exit;
        }

        // Fetch ticket
        $stmt = $mysqli->prepare("SELECT * FROM tickets WHERE ticket_token=? LIMIT 1");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res->num_rows == 0){
            echo json_encode(['success'=>false,'error'=>'Ticket not found']);
            exit;
        }
        $ticket = $res->fetch_assoc();

        if($ticket['assigned_to'] != $user_id && $role != 'admin'){
            echo json_encode(['success'=>false,'error'=>'Access denied']);
            exit;
        }

        $closed_at = ($status === 'closed') ? date('Y-m-d H:i:s') : null;

        $stmt = $mysqli->prepare("UPDATE tickets SET status=?, closed_at=? WHERE ticket_token=?");
        $stmt->bind_param("sss", $status, $closed_at, $token);
        $stmt->execute();

        echo json_encode(['success'=>true]);
    break;

    // Delete ticket (admin only)
    case 'delete_ticket':
        if($role != 'admin'){
            echo json_encode(['success'=>false,'error'=>'Access denied']);
            exit;
        }
        $token = $_POST['token'] ?? '';
        $stmt = $mysqli->prepare("DELETE FROM tickets WHERE ticket_token=?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        echo json_encode(['success'=>true]);
    break;

    // Approve user (admin only)
    case 'approve_user':
        if($role != 'admin'){
            echo json_encode(['success'=>false,'error'=>'Access denied']);
            exit;
        }
        $id = intval($_POST['id'] ?? 0);
        $stmt = $mysqli->prepare("UPDATE users SET status='approved' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success'=>true]);
    break;

    // Delete user (admin only)
    case 'delete_user':
        if($role != 'admin'){
            echo json_encode(['success'=>false,'error'=>'Access denied']);
            exit;
        }
        $id = intval($_POST['id'] ?? 0);
        $stmt = $mysqli->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success'=>true]);
    break;

    // Pending users count (for admin notifications)
    case 'pending_users_count':
        if($role != 'admin'){
            echo json_encode(['count'=>0]);
            exit;
        }
        $res = $mysqli->query("SELECT COUNT(*) AS cnt FROM users WHERE status='pending'");
        $row = $res->fetch_assoc();
        echo json_encode(['count'=>$row['cnt']]);
    break;

    default:
        echo json_encode(['success'=>false,'error'=>'Invalid action']);
    break;
}
