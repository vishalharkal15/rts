<?php
// JSON File-based Database (No MySQL/SQLite required!)

class JsonDB {
    private $dataDir;
    private $tables = ['users', 'tickets', 'chat_requests', 'chat_messages'];
    
    public function __construct() {
        $this->dataDir = __DIR__ . '/database_json/';
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0777, true);
        }
        $this->initializeTables();
    }
    
    private function initializeTables() {
        foreach ($this->tables as $table) {
            $file = $this->dataDir . $table . '.json';
            if (!file_exists($file)) {
                file_put_contents($file, json_encode([]));
            }
        }
        
        // Create default admin if users table is empty
        $users = $this->readTable('users');
        if (empty($users)) {
            $hash = password_hash('admin123', PASSWORD_DEFAULT);
            $this->insert('users', [
                'name' => 'Vikram',
                'email' => 'admin@rts.com',
                'password' => $hash,
                'role' => 'admin',
                'status' => 'approved',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->insert('users', [
                'name' => 'John Student',
                'email' => 'john@rts.com',
                'password' => $hash,
                'role' => 'student',
                'status' => 'approved',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->insert('users', [
                'name' => 'Sarah Trainer',
                'email' => 'sarah@rts.com',
                'password' => $hash,
                'role' => 'trainer',
                'status' => 'approved',
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->insert('users', [
                'name' => 'Mike Intern',
                'email' => 'mike@rts.com',
                'password' => $hash,
                'role' => 'intern',
                'status' => 'approved',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    private function readTable($table) {
        $file = $this->dataDir . $table . '.json';
        return json_decode(file_get_contents($file), true) ?: [];
    }
    
    private function writeTable($table, $data) {
        $file = $this->dataDir . $table . '.json';
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    public function insert($table, $data) {
        $records = $this->readTable($table);
        $data['id'] = empty($records) ? 1 : max(array_column($records, 'id')) + 1;
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        $records[] = $data;
        $this->writeTable($table, $records);
        return $data['id'];
    }
    
    public function select($table, $where = []) {
        $records = $this->readTable($table);
        if (empty($where)) {
            return $records;
        }
        return array_filter($records, function($record) use ($where) {
            foreach ($where as $key => $value) {
                if (!isset($record[$key]) || $record[$key] != $value) {
                    return false;
                }
            }
            return true;
        });
    }
    
    public function update($table, $data, $where) {
        $records = $this->readTable($table);
        foreach ($records as &$record) {
            $match = true;
            foreach ($where as $key => $value) {
                if (!isset($record[$key]) || $record[$key] != $value) {
                    $match = false;
                    break;
                }
            }
            if ($match) {
                foreach ($data as $key => $value) {
                    $record[$key] = $value;
                }
            }
        }
        $this->writeTable($table, $records);
    }
    
    public function delete($table, $where) {
        $records = $this->readTable($table);
        $records = array_filter($records, function($record) use ($where) {
            foreach ($where as $key => $value) {
                if (isset($record[$key]) && $record[$key] == $value) {
                    return false;
                }
            }
            return true;
        });
        $this->writeTable($table, array_values($records));
    }
}

// MySQLi compatibility wrapper
class MySQLiCompat {
    private $db;
    public $connect_error = null;
    public $error = null;
    
    public function __construct() {
        $this->db = new JsonDB();
    }
    
    public function query($sql) {
        // Parse simple SQL queries
        $sql = trim($sql);
        
        // SELECT queries
        if (stripos($sql, 'SELECT') === 0) {
            $result = $this->parseSelect($sql);
            return new ResultCompat($result);
        }
        
        // INSERT queries
        if (stripos($sql, 'INSERT') === 0) {
            $this->parseInsert($sql);
            return true;
        }
        
        // UPDATE queries
        if (stripos($sql, 'UPDATE') === 0) {
            $this->parseUpdate($sql);
            return true;
        }
        
        // DELETE queries
        if (stripos($sql, 'DELETE') === 0) {
            $this->parseDelete($sql);
            return true;
        }
        
        return true;
    }
    
    private function parseSelect($sql) {
        // Very basic SQL parser - handles common patterns
        if (preg_match('/FROM\s+(\w+)/i', $sql, $match)) {
            $table = $match[1];
            $records = $this->db->select($table);
            
            // Handle WHERE clause
            if (preg_match('/WHERE\s+(.+?)(?:ORDER|LIMIT|$)/is', $sql, $whereMatch)) {
                $whereClause = trim($whereMatch[1]);
                $records = array_filter($records, function($record) use ($whereClause) {
                    return $this->evaluateWhere($record, $whereClause);
                });
            }
            
            // Handle ORDER BY
            if (preg_match('/ORDER BY\s+(\w+)\s*(ASC|DESC)?/i', $sql, $orderMatch)) {
                $orderField = $orderMatch[1];
                $orderDir = strtoupper($orderMatch[2] ?? 'ASC');
                usort($records, function($a, $b) use ($orderField, $orderDir) {
                    $result = $a[$orderField] <=> $b[$orderField];
                    return $orderDir === 'DESC' ? -$result : $result;
                });
            }
            
            // Handle JOINs (basic support)
            if (preg_match_all('/LEFT JOIN\s+(\w+)\s+(\w+)\s+ON\s+([^W]+)/i', $sql, $joins, PREG_SET_ORDER)) {
                foreach ($joins as $join) {
                    $joinTable = $join[1];
                    $joinAlias = $join[2];
                    $joinRecords = $this->db->select($joinTable);
                    
                    foreach ($records as &$record) {
                        foreach ($joinRecords as $jr) {
                            // Simple join logic
                            $record[$joinAlias . '_name'] = $jr['name'] ?? null;
                            $record['assigned_name'] = $jr['name'] ?? null;
                            $record['requester_name'] = $jr['name'] ?? null;
                            $record['sender_name'] = $jr['name'] ?? null;
                        }
                    }
                }
            }
            
            return array_values($records);
        }
        return [];
    }
    
    private function evaluateWhere($record, $where) {
        // Simple WHERE evaluation
        if (preg_match('/(\w+)\s*=\s*[\'"](.+?)[\'"]/i', $where, $match)) {
            return isset($record[$match[1]]) && $record[$match[1]] == $match[2];
        }
        return true;
    }
    
    private function parseInsert($sql) {
        // Basic INSERT parser
        if (preg_match('/INSERT INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/i', $sql, $match)) {
            $table = $match[1];
            $columns = array_map('trim', explode(',', $match[2]));
            $values = array_map(function($v) {
                return trim($v, " '\"");
            }, explode(',', $match[3]));
            
            $data = array_combine($columns, $values);
            $this->db->insert($table, $data);
        }
    }
    
    private function parseUpdate($sql) {
        if (preg_match('/UPDATE\s+(\w+)\s+SET\s+(.+?)\s+WHERE\s+(.+)/i', $sql, $match)) {
            $table = $match[1];
            $setClause = $match[2];
            $whereClause = $match[3];
            
            // Parse SET
            $data = [];
            if (preg_match_all('/(\w+)\s*=\s*([^,]+)/i', $setClause, $sets, PREG_SET_ORDER)) {
                foreach ($sets as $set) {
                    $data[$set[1]] = trim($set[2], " '\"");
                }
            }
            
            // Parse WHERE
            $where = [];
            if (preg_match('/(\w+)\s*=\s*(.+)/i', $whereClause, $w)) {
                $where[$w[1]] = trim($w[2], " '\"");
            }
            
            $this->db->update($table, $data, $where);
        }
    }
    
    private function parseDelete($sql) {
        if (preg_match('/DELETE FROM\s+(\w+)\s+WHERE\s+(\w+)\s*=\s*(.+)/i', $sql, $match)) {
            $table = $match[1];
            $key = $match[2];
            $value = trim($match[3], " '\"");
            $this->db->delete($table, [$key => $value]);
        }
    }
    
    public function prepare($sql) {
        return new StatementCompat($this->db, $sql);
    }
    
    public function set_charset($charset) {
        return true;
    }
}

class ResultCompat {
    private $data;
    private $position = 0;
    public $num_rows = 0;
    
    public function __construct($data) {
        $this->data = array_values($data);
        $this->num_rows = count($this->data);
    }
    
    public function fetch_assoc() {
        if ($this->position < count($this->data)) {
            return $this->data[$this->position++];
        }
        return null;
    }
    
    public function fetch_all($mode = MYSQLI_ASSOC) {
        return $this->data;
    }
}

class StatementCompat {
    private $db;
    private $sql;
    private $params = [];
    private $result = [];
    public $affected_rows = 0;
    
    public function __construct($db, $sql) {
        $this->db = $db;
        $this->sql = $sql;
    }
    
    public function bind_param($types, ...$vars) {
        $this->params = $vars;
        return true;
    }
    
    public function execute() {
        $sql = $this->sql;
        foreach ($this->params as $param) {
            $sql = preg_replace('/\?/', "'" . $param . "'", $sql, 1);
        }
        
        // Execute different query types
        if (stripos($sql, 'SELECT') === 0) {
            $this->result = $this->parseSelect($sql);
            $this->affected_rows = count($this->result);
        } else if (stripos($sql, 'INSERT') === 0) {
            $this->parseInsert($sql);
            $this->affected_rows = 1;
        } else if (stripos($sql, 'UPDATE') === 0) {
            $this->parseUpdate($sql);
            $this->affected_rows = 1;
        } else if (stripos($sql, 'DELETE') === 0) {
            $this->parseDelete($sql);
            $this->affected_rows = 1;
        }
        
        return true;
    }
    
    private function parseSelect($sql) {
        $records = [];
        if (preg_match('/FROM\s+(\w+)/i', $sql, $match)) {
            $table = $match[1];
            $records = $this->db->select($table);
            
            // Handle WHERE with multiple conditions - FIXED for email matching
            if (preg_match('/WHERE\s+(.+?)(?:LIMIT|ORDER|$)/is', $sql, $whereMatch)) {
                $whereClause = trim($whereMatch[1]);
                
                // Better parsing for email and other fields with special chars
                $records = array_filter($records, function($record) use ($whereClause, $sql) {
                    // Extract field=value pairs, handling quoted values
                    if (preg_match('/(\w+)\s*=\s*\'([^\']+)\'/', $whereClause, $match)) {
                        $field = $match[1];
                        $value = $match[2];
                        return isset($record[$field]) && $record[$field] == $value;
                    }
                    // Try without quotes
                    if (preg_match('/(\w+)\s*=\s*([^\s]+)/', $whereClause, $match)) {
                        $field = $match[1];
                        $value = $match[2];
                        return isset($record[$field]) && $record[$field] == $value;
                    }
                    return true;
                });
            }
            
            // Handle LIMIT
            if (preg_match('/LIMIT\s+(\d+)/i', $sql, $limitMatch)) {
                $records = array_slice($records, 0, (int)$limitMatch[1]);
            }
            
            // Handle LEFT JOIN
            if (preg_match('/LEFT JOIN\s+(\w+)\s+(\w+)\s+ON/i', $sql, $joinMatch)) {
                $joinTable = $joinMatch[1];
                $joinRecords = $this->db->select($joinTable);
                $joinMap = [];
                foreach ($joinRecords as $jr) {
                    $joinMap[$jr['id']] = $jr;
                }
                
                foreach ($records as &$record) {
                    if (isset($record['assigned_to']) && isset($joinMap[$record['assigned_to']])) {
                        $record['assigned_name'] = $joinMap[$record['assigned_to']]['name'];
                    }
                    if (isset($record['requester_id']) && isset($joinMap[$record['requester_id']])) {
                        $record['requester_name'] = $joinMap[$record['requester_id']]['name'];
                    }
                    if (isset($record['sender_id']) && isset($joinMap[$record['sender_id']])) {
                        $record['sender_name'] = $joinMap[$record['sender_id']]['name'];
                    }
                    if (isset($record['receiver_id']) && isset($joinMap[$record['receiver_id']])) {
                        $record['receiver_name'] = $joinMap[$record['receiver_id']]['name'];
                    }
                }
            }
        }
        return array_values($records);
    }
    
    private function parseInsert($sql) {
        if (preg_match('/INSERT INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/is', $sql, $match)) {
            $table = $match[1];
            $columns = array_map('trim', explode(',', $match[2]));
            
            // Better value parsing - handle quoted strings properly
            $valuesStr = $match[3];
            $values = [];
            $currentValue = '';
            $inQuotes = false;
            $quoteChar = '';
            
            for ($i = 0; $i < strlen($valuesStr); $i++) {
                $char = $valuesStr[$i];
                
                if (($char === "'" || $char === '"') && ($i === 0 || $valuesStr[$i-1] !== '\\')) {
                    if (!$inQuotes) {
                        $inQuotes = true;
                        $quoteChar = $char;
                    } elseif ($char === $quoteChar) {
                        $inQuotes = false;
                        $quoteChar = '';
                    } else {
                        $currentValue .= $char;
                    }
                } elseif ($char === ',' && !$inQuotes) {
                    $values[] = trim($currentValue);
                    $currentValue = '';
                } else {
                    $currentValue .= $char;
                }
            }
            if ($currentValue !== '') {
                $values[] = trim($currentValue);
            }
            
            $data = array_combine($columns, $values);
            
            // Handle NULL values
            foreach ($data as $k => $v) {
                if ($v === 'NULL' || $v === '') {
                    $data[$k] = null;
                }
            }
            
            $this->db->insert($table, $data);
        }
    }
    
    private function parseUpdate($sql) {
        if (preg_match('/UPDATE\s+(\w+)\s+SET\s+(.+?)\s+WHERE\s+(.+)/is', $sql, $match)) {
            $table = $match[1];
            $setClause = $match[2];
            $whereClause = $match[3];
            
            $data = [];
            if (preg_match_all('/(\w+)\s*=\s*[\'"]?([^,\'"]+)[\'"]?/i', $setClause, $sets, PREG_SET_ORDER)) {
                foreach ($sets as $set) {
                    $value = trim($set[2]);
                    $data[$set[1]] = ($value === 'NULL') ? null : $value;
                }
            }
            
            $where = [];
            if (preg_match('/(\w+)\s*=\s*[\'"]?([^\s\'"]+)[\'"]?/i', $whereClause, $w)) {
                $where[$w[1]] = trim($w[2]);
            }
            
            $this->db->update($table, $data, $where);
        }
    }
    
    private function parseDelete($sql) {
        if (preg_match('/DELETE FROM\s+(\w+)\s+WHERE\s+(\w+)\s*=\s*[\'"]?([^\s\'"]+)[\'"]?/i', $sql, $match)) {
            $table = $match[1];
            $key = $match[2];
            $value = trim($match[3]);
            $this->db->delete($table, [$key => $value]);
        }
    }
    
    public function get_result() {
        return new ResultCompat($this->result);
    }
    
    public function store_result() {
        return true;
    }
}

// Create mysqli compatibility object
$mysqli = new MySQLiCompat();
?>
