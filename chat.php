<?php
// chat.php — Fully functional secure AJAX chat system

if (session_status() == PHP_SESSION_NONE) session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id   = (int) $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? $_SESSION['name'] ?? 'Guest';
include 'templates/header.php';
// Chat log folder
$chat_dir = __DIR__ . '/system/Ch@tr@@m';
if (!is_dir($chat_dir)) mkdir($chat_dir, 0777, true);

header_remove("X-Powered-By"); // Security header

// ============================
// AJAX HANDLER SECTION
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'];

    switch ($action) {

        // 1️⃣ Send Request
        case 'send_request':
            $receiver_id = intval($_POST['receiver_id'] ?? 0);
            if ($receiver_id <= 0 || $receiver_id === $user_id) {
                echo json_encode(['success' => false, 'message' => 'Invalid target']);
                exit;
            }

            $check = $mysqli->prepare("
                SELECT id FROM chat_requests 
                WHERE ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?))
                AND status IN ('pending','accepted')
            ");
            $check->bind_param("iiii", $user_id, $receiver_id, $receiver_id, $user_id);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                echo json_encode(['success' => false, 'message' => 'Chat already exists']);
                exit;
            }

            $stmt = $mysqli->prepare("INSERT INTO chat_requests(sender_id,receiver_id,status,created_at) VALUES(?,?,'pending',NOW())");
            $stmt->bind_param("ii", $user_id, $receiver_id);
            $ok = $stmt->execute();
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Request sent' : 'Failed']);
            exit;

        // 2️⃣ Fetch Requests
        case 'fetch_requests':
            $stmt = $mysqli->prepare("
                SELECT r.*, u.name AS sender_name 
                FROM chat_requests r 
                LEFT JOIN users u ON u.id=r.sender_id 
                WHERE (r.sender_id=? OR r.receiver_id=?) 
                AND r.status IN ('pending','accepted')
                ORDER BY r.created_at DESC
            ");
            $stmt->bind_param("ii", $user_id, $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $out = [];
            while ($row = $res->fetch_assoc()) {
                $row['sender_name'] = htmlspecialchars($row['sender_name'] ?? 'Unknown', ENT_QUOTES);
                $out[] = $row;
            }
            echo json_encode($out);
            exit;

        // 3️⃣ Respond to Request
        case 'respond_request':
            $rid = intval($_POST['request_id'] ?? 0);
            $status = $_POST['status'] === 'accept' ? 'accepted' : 'rejected';
            if ($rid <= 0) { echo json_encode(['success'=>false]); exit; }

            if ($status === 'accepted') {
                $stmt = $mysqli->prepare("UPDATE chat_requests SET status='accepted', started_at=NOW() WHERE id=? AND receiver_id=?");
                $stmt->bind_param("ii", $rid, $user_id);
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $r = $mysqli->query("SELECT started_at FROM chat_requests WHERE id=$rid");
                    $row = $r->fetch_assoc();
                    echo json_encode(['success'=>true,'chat_id'=>$rid,'started_at'=>$row['started_at']]);
                } else echo json_encode(['success'=>false]);
            } else {
                $stmt = $mysqli->prepare("UPDATE chat_requests SET status='rejected' WHERE id=? AND receiver_id=?");
                $stmt->bind_param("ii",$rid,$user_id);
                $stmt->execute();
                echo json_encode(['success'=>true]);
            }
            exit;

        // 4️⃣ Send Message
        case 'send_message':
            $chat_id = intval($_POST['chat_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            if ($chat_id <= 0 || $message === '') {
                echo json_encode(['success'=>false]); exit;
            }

            $check = $mysqli->prepare("SELECT id FROM chat_requests WHERE id=? AND status='accepted' AND (sender_id=? OR receiver_id=?)");
            $check->bind_param("iii",$chat_id,$user_id,$user_id);
            $check->execute();
            $check->store_result();
            if ($check->num_rows === 0) {
                echo json_encode(['success'=>false,'message'=>'Not authorized']); exit;
            }

            $safe = htmlspecialchars($message, ENT_QUOTES);
            $stmt = $mysqli->prepare("INSERT INTO chat_messages(chat_id,sender_id,message,created_at) VALUES(?,?,?,NOW())");
            $stmt->bind_param("iis",$chat_id,$user_id,$safe);
            echo json_encode(['success'=>$stmt->execute()]);
            exit;

        // 5️⃣ Fetch Messages
        case 'fetch_messages':
            $chat_id = intval($_POST['chat_id'] ?? 0);
            $stmt = $mysqli->prepare("
                SELECT m.*,u.name AS sender_name FROM chat_messages m
                LEFT JOIN users u ON u.id=m.sender_id
                WHERE m.chat_id=? ORDER BY m.created_at ASC
            ");
            $stmt->bind_param("i",$chat_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $msgs=[];
            while($row=$res->fetch_assoc()){
                $row['sender_name']=htmlspecialchars($row['sender_name']??'Unknown',ENT_QUOTES);
                $row['message']=htmlspecialchars($row['message']??'',ENT_QUOTES);
                $msgs[]=$row;
            }
            echo json_encode($msgs);
            exit;

        // 6️⃣ Close Chat
        case 'close_chat':
            $chat_id=intval($_POST['chat_id']??0);
            if($chat_id<=0){echo json_encode(['success'=>false]);exit;}
            $res=$mysqli->query("SELECT u.name,m.message,m.created_at FROM chat_messages m LEFT JOIN users u ON u.id=m.sender_id WHERE chat_id=$chat_id ORDER BY m.created_at ASC");
            $log='';
            while($r=$res->fetch_assoc()){
                $log.="[".$r['created_at']."] ".$r['name'].": ".$r['message']."\n";
            }
            $fname=$chat_dir.'/'.bin2hex(random_bytes(8));
            file_put_contents($fname,$log);
            $mysqli->query("DELETE FROM chat_messages WHERE chat_id=$chat_id");
            $mysqli->query("DELETE FROM chat_requests WHERE id=$chat_id");
            echo json_encode(['success'=>true,'file'=>$fname]);
            exit;
    }

    echo json_encode(['success'=>false,'message'=>'Unknown action']);
    exit;
}

// ============================
// UI BELOW
// ============================
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>RTS — Secure Team Chat</title>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<style>
body{font-family:Arial;background:#eef2f5;margin:0;padding:20px}
.container{max-width:850px;margin:auto;background:#fff;padding:20px;border-radius:10px;box-shadow:0 6px 16px rgba(0,0,0,0.1)}
.header{display:flex;justify-content:space-between;align-items:center}
.chat-box{height:300px;border:1px solid #ddd;border-radius:8px;padding:10px;overflow:auto;margin-top:10px;background:#fafafa}
.msg{margin-bottom:10px}
.msg .who{font-weight:bold;color:#1976d2}
.footer{display:flex;gap:8px;margin-top:10px}
input,select,button{padding:10px;border-radius:6px;border:1px solid #ccc}
button{background:#1976d2;color:white;cursor:pointer}
button.danger{background:#e53935}
.pending{background:#fff7e6;padding:10px;border-radius:8px;margin:6px 0;border:1px solid #ffe6a7}
</style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>Team Chat</h2>
        <div>
            <select id="user_list"><option value="">Select User</option></select>
            <button id="btn_request">Send Request</button>
        </div>
    </div>

    <div id="requests"></div>

    <div id="chat_area" style="display:none">
        <div class="chat-box" id="messages"></div>
        <div class="footer">
            <input type="text" id="msg" placeholder="Type a message...">
            <button id="send">Send</button>
            <button id="close" class="danger">Close</button>
        </div>
    </div>
</div>

<script>
let currentChat=null;
function loadUsers(){
    $.get('api.php?action=get_users',res=>{
        let users=(typeof res==='string')?JSON.parse(res):res;
        let s=$('#user_list');s.empty().append('<option value="">Select User</option>');
        users.forEach(u=>{
            if(u.id!=<?=json_encode($user_id)?>)
                s.append(`<option value="${u.id}">${u.name}</option>`);
        });
    });
}
$('#btn_request').click(()=>{
    let id=$('#user_list').val(); if(!id)return alert('Select user');
    $.post('chat.php',{action:'send_request',receiver_id:id},r=>{
        alert(r.message||'Done'); fetchRequests();
    },'json');
});
function fetchRequests(){
    $.post('chat.php',{action:'fetch_requests'},res=>{
        let c=$('#requests'); c.empty();
        res.forEach(r=>{
            if(r.status==='pending' && r.receiver_id==<?=json_encode($user_id)?>){
                c.append(`<div class="pending">${r.sender_name} wants to chat 
                <button onclick="respond(${r.id},'accept')">Accept</button>
                <button onclick="respond(${r.id},'reject')">Reject</button></div>`);
            } else if(r.status==='accepted'){
                c.append(`<div class="pending" style="background:#e8f5e9">Chat active with ${r.sender_name}</div>`);
                if(!currentChat){openChat(r.id);}
            }
        });
    },'json');
}
function respond(id,st){
    $.post('chat.php',{action:'respond_request',request_id:id,status:st},r=>{
        if(st==='accept'&&r.success){openChat(r.chat_id);}
        fetchRequests();
    },'json');
}
function openChat(id){
    currentChat=id; $('#chat_area').show(); fetchMessages();
}
$('#send').click(()=>{
    let msg=$('#msg').val(); if(!msg)return;
    $.post('chat.php',{action:'send_message',chat_id:currentChat,message:msg},()=>{$('#msg').val('');fetchMessages();},'json');
});
function fetchMessages(){
    if(!currentChat)return;
    $.post('chat.php',{action:'fetch_messages',chat_id:currentChat},res=>{
        let box=$('#messages'); box.empty();
        res.forEach(m=>{
            box.append(`<div class="msg"><span class="who">${m.sender_name}:</span> <span>${m.message}</span></div>`);
        });
        box.scrollTop(box.prop('scrollHeight'));
    },'json');
}
$('#close').click(()=>{
    if(confirm('Close this chat?')){
        $.post('chat.php',{action:'close_chat',chat_id:currentChat},r=>{
            if(r.success){currentChat=null;$('#chat_area').hide();fetchRequests();}
        },'json');
    }
});
setInterval(()=>{fetchRequests();if(currentChat)fetchMessages();},3000);
loadUsers();fetchRequests();
</script>
</body>
</html>
