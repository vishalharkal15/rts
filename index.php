<?php
session_start();
require_once 'config.php'; 

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? 'Guest';
$role = strtolower($_SESSION['role'] ?? 'user');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>RTS Ticket Platform</title>
<link rel="icon" type="image/x-icon" href="favicon.ico">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&family=Roboto&display=swap" rel="stylesheet">
<style>
body {
    font-family: 'Roboto', sans-serif;
    background: #0B0B0B;
    margin: 0;
    color: #fff;
}

/* Header */
header {
    display:flex; justify-content:space-between; align-items:center; padding:12px 30px;
    background:#0B0B0B; border-bottom: 2px solid #1F8FFF; box-shadow:0 4px 15px rgba(31,143,255,0.3);
    position: sticky; top:0; z-index:1000;
}
header .logo img { height:55px; transition: transform 0.3s ease; }
header .logo img:hover { transform: scale(1.05) rotate(-2deg); }
nav { display:flex; align-items:center; gap:20px; font-family: 'Orbitron', sans-serif; font-weight:500; }
nav .user-info { color: #FF3D3D; margin-right:10px; font-size:0.95rem; }
nav a { color:#1F8FFF; text-decoration:none; padding:6px 14px; border-radius:6px; position:relative; transition:all 0.3s ease; }
nav a::after { content:''; position:absolute; left:0; bottom:0; width:0%; height:2px; background:#FF3D3D; transition: width 0.3s; }
nav a:hover { color:#FF3D3D; box-shadow:0 0 10px #1F8FFF,0 0 20px #FF3D3D; }
nav a:hover::after { width:100%; }

/* Dashboard */
.dashboard-container { max-width:1200px; margin:20px auto; padding:10px; }

/* Create Ticket Box */
.create-ticket-box {
    background: rgba(20,20,20,0.8); backdrop-filter: blur(10px);
    padding:30px; border-radius:15px; box-shadow:0 0 30px rgba(31,143,255,0.4);
    margin-bottom:30px;
}
.create-ticket-box h2 { font-family:'Orbitron', sans-serif; color:#1F8FFF; text-shadow:0 0 10px #1F8FFF,0 0 20px #FF3D3D; }
.create-ticket-box input, .create-ticket-box textarea, .create-ticket-box select {
    width:100%; padding:12px; margin:10px 0; border-radius:8px; border:1px solid #1F8FFF;
    background: rgba(255,255,255,0.05); color:#fff; outline:none; transition:0.3s;
}
.create-ticket-box input:focus, .create-ticket-box textarea:focus, .create-ticket-box select:focus { border-color:#FF3D3D; box-shadow:0 0 10px #FF3D3D; }
.create-ticket-box button {
    padding:12px 20px; background:#c62828; color:#fff; border:none; border-radius:8px;
    cursor:pointer; font-weight:bold; letter-spacing:1px; transition:0.3s; box-shadow:0 0 10px #c62828;
}
.create-ticket-box button:hover { background:#b71c1c; box-shadow:0 0 20px #FF3D3D,0 0 40px #1F8FFF; transform: scale(1.05); }

/* Tickets Grid */
.tickets-container { display:grid; grid-template-columns: repeat(auto-fill,minmax(300px,1fr)); gap:20px; }

/* Ticket Card */
.ticket-card {
    background: rgba(20,20,20,0.85); backdrop-filter: blur(8px); padding:20px;
    border-radius:15px; box-shadow:0 0 20px rgba(31,143,255,0.4); position:relative;
    transition: transform 0.3s, box-shadow 0.3s;
}
.ticket-card:hover { transform: scale(1.02); box-shadow:0 0 25px #FF3D3D,0 0 50px #1F8FFF; }

.ticket-card h3 { margin:0 0 10px 0; color:#1F8FFF; text-shadow:0 0 5px #1F8FFF; }
.ticket-card p { margin:5px 0; }
.status-badge { position:absolute; top:20px; right:20px; padding:5px 10px; border-radius:6px; color:#fff; font-weight:bold; text-transform:uppercase; font-size:12px; box-shadow:0 0 10px #000; }
.status-open { background:#f44336; }
.status-in_progress { background:#ff9800; }
.status-closed { background:#4caf50; }

.ticket-card button { margin-right:5px; padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; transition:0.3s; }
.ticket-card button.edit-btn { background:#1976d2; color:#fff; }
.ticket-card button.delete-btn { background:#e53935; color:#fff; }
.ticket-card button.edit-btn:hover { background:#1565c0; }
.ticket-card button.delete-btn:hover { background:#c62828; }

.ticket-card select { margin-top:10px; width:100%; padding:8px; border-radius:6px; border:none; }
.ticket-card .timer, .ticket-card .created_at { font-size:12px; color:#aaa; margin-top:5px; }
</style>
</head>
<body>

<?php include 'templates/header.php'; ?>

<div class="dashboard-container">

    <!-- Create Ticket -->
    <div class="create-ticket-box">
        <h2>Create Ticket / Request</h2>
        <input type="text" id="title" placeholder="Ticket Title">
        <textarea id="description" placeholder="Ticket Description"></textarea>
        <select id="assigned_to"><option value="">Unassigned</option></select>
        <button onclick="createTicket()">Create Ticket</button>
    </div>

    <!-- Tickets -->
    <h2>All Tickets</h2>
    <div class="tickets-container" id="tickets"></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
const role = '<?php echo $role; ?>';
const userId = '<?php echo $user_id; ?>';

function fetchUsers(){
    $.get('api.php?action=get_users', function(data){
        let users = typeof data==='string'?JSON.parse(data):data;
        $('#assigned_to').html('<option value="">Unassigned</option>');
        users.forEach(u=>$('#assigned_to').append(`<option value="${u.id}">${u.name} (${u.role})</option>`));
    });
}

let ticketIntervals = {};
function fetchTickets(){
    $.get('api.php?action=get_tickets', function(data){
        let tickets = typeof data==='string'?JSON.parse(data):data;
        $('#tickets').html('');
        tickets.forEach(t=>{
            if(role!=='admin' && t.assigned_to!=userId && t.requester_id!=userId) return;
            const statusClass = t.status.replace('_','-');
            const deleteBtn = role==='admin'?`<button class="delete-btn" onclick="deleteTicket('${t.ticket_token}')">Delete</button>`:'';
            const createdAt = t.created_at;
            $('#tickets').append(`
                <div class="ticket-card" id="ticket-${t.ticket_token}">
                    <span class="status-badge status-${statusClass}">${t.status.replace('_',' ')}</span>
                    <h3>${t.title}</h3>
                    <p><strong>Assigned:</strong> ${t.assigned_name || 'Unassigned'}</p>
                    <p><strong>Requested By:</strong> ${t.requester_name}</p>
                    <p>${t.description}</p>
                    <div class="created_at">Created At: ${createdAt}</div>
                    <div class="timer" id="timer-${t.ticket_token}"></div>
                    <select onchange="updateStatus('${t.ticket_token}', this.value)">
                        <option ${t.status=='open'?'selected':''} value="open">Open</option>
                        <option ${t.status=='in_progress'?'selected':''} value="in_progress">In Progress</option>
                        <option ${t.status=='closed'?'selected':''} value="closed">Closed</option>
                    </select>
                    <br><br>
                    <button class="edit-btn" onclick="editTicket('${t.ticket_token}')">Edit</button>
                    ${deleteBtn}
                </div>
            `);
            if(ticketIntervals[t.ticket_token]) clearInterval(ticketIntervals[t.ticket_token]);
            if(t.status!=='closed'){
                let startTime = new Date(createdAt).getTime();
                ticketIntervals[t.ticket_token] = setInterval(function(){
                    let now = new Date().getTime();
                    let diff = Math.floor((now - startTime)/1000);
                    let hours = Math.floor(diff/3600);
                    let mins = Math.floor((diff%3600)/60);
                    let secs = diff%60;
                    $('#timer-'+t.ticket_token).text(`Open for: ${hours}h ${mins}m ${secs}s`);
                },1000);
            } else { $('#timer-'+t.ticket_token).text('Ticket closed'); }
        });
    });
}

function createTicket(){
    const title = $('#title').val();
    const description = $('#description').val();
    const assigned_to = $('#assigned_to').val();
    if(!title || !description){ alert('Title and description required'); return; }
    $.post('api.php?action=create_ticket',{title, description, assigned_to}, function(res){
        let r=typeof res==='string'?JSON.parse(res):res;
        if(r.success){ fetchTickets(); $('#title,#description').val(''); $('#assigned_to').val(''); }
        else{ alert(r.error); }
    });
}

function updateStatus(token,status){ $.post('api.php?action=update_status',{token,status},function(){fetchTickets();}); }
function editTicket(token){ window.location='edit_ticket.php?token='+token; }
function deleteTicket(token){ if(!confirm('Delete this ticket?')) return; $.post('api.php?action=delete_ticket',{token},function(res){let r=typeof res==='string'?JSON.parse(res):res; if(r.success) fetchTickets(); else alert(r.error);}); }

fetchUsers();
fetchTickets();
setInterval(fetchTickets,10000);
</script>
</body>
</html>
