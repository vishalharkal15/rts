<?php
// Start session only if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// SQLite Database configuration (alternative to MySQL)
$DB_FILE = __DIR__ . "/rts_database.sqlite";

try {
    // Create SQLite connection
    $pdo = new PDO("sqlite:" . $DB_FILE);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create tables if they don't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            role TEXT DEFAULT 'student',
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS tickets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ticket_token TEXT UNIQUE NOT NULL,
            title TEXT NOT NULL,
            description TEXT NOT NULL,
            requester_id INTEGER NOT NULL,
            assigned_to INTEGER,
            status TEXT DEFAULT 'open',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            closed_at DATETIME,
            FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
        );
        
        CREATE TABLE IF NOT EXISTS chat_requests (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            sender_id INTEGER NOT NULL,
            receiver_id INTEGER NOT NULL,
            status TEXT DEFAULT 'pending',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            started_at DATETIME,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
        );
        
        CREATE TABLE IF NOT EXISTS chat_messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chat_id INTEGER NOT NULL,
            sender_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (chat_id) REFERENCES chat_requests(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        );
    ");
    
    // Insert default admin if not exists
    $check = $pdo->query("SELECT COUNT(*) FROM users WHERE email='admin@rts.com'")->fetchColumn();
    if ($check == 0) {
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (name, email, password, role, status) 
                    VALUES ('Vikram', 'admin@rts.com', '$hash', 'admin', 'approved')");
        
        // Add test users
        $pdo->exec("INSERT INTO users (name, email, password, role, status) VALUES 
            ('John Student', 'john@rts.com', '$hash', 'student', 'approved'),
            ('Sarah Trainer', 'sarah@rts.com', '$hash', 'trainer', 'approved'),
            ('Mike Intern', 'mike@rts.com', '$hash', 'intern', 'approved')");
    }
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// For backward compatibility with mysqli code, create a wrapper
class MySQLiCompat {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function query($sql) {
        $stmt = $this->pdo->query($sql);
        return new ResultCompat($stmt);
    }
    
    public function prepare($sql) {
        // Convert ? placeholders to :param format for PDO
        return new StatementCompat($this->pdo->prepare($sql));
    }
    
    public function set_charset($charset) {
        // SQLite handles UTF-8 natively
        return true;
    }
    
    public $connect_error = null;
    public $error = null;
}

class ResultCompat {
    private $stmt;
    public $num_rows = 0;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
        if ($stmt) {
            $this->num_rows = $stmt->rowCount();
        }
    }
    
    public function fetch_assoc() {
        return $this->stmt ? $this->stmt->fetch(PDO::FETCH_ASSOC) : null;
    }
    
    public function fetch_all($mode = MYSQLI_ASSOC) {
        return $this->stmt ? $this->stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }
}

class StatementCompat {
    private $stmt;
    public $affected_rows = 0;
    
    public function __construct($stmt) {
        $this->stmt = $stmt;
    }
    
    public function bind_param($types, &...$vars) {
        // Bind parameters
        foreach ($vars as $i => $var) {
            $this->stmt->bindValue($i + 1, $var);
        }
        return true;
    }
    
    public function execute() {
        $result = $this->stmt->execute();
        $this->affected_rows = $this->stmt->rowCount();
        return $result;
    }
    
    public function get_result() {
        return new ResultCompat($this->stmt);
    }
    
    public function store_result() {
        return true;
    }
}

// Create mysqli compatibility object
$mysqli = new MySQLiCompat($pdo);
?>
