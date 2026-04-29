<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class ClientManager {
    private $filename = 'clients.json';
    
    public function __construct() {
        // Create file if it doesn't exist
        if (!file_exists($this->filename)) {
            file_put_contents($this->filename, json_encode([]));
        }
    }
    
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error('Method not supported');
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['action'])) {
            return $this->error('Invalid data');
        }
        
        $username = $data['username'] ?? 'Anonymous';
        
        switch ($data['action']) {
            case 'add':
                if (!$this->checkPermission($username)) return $this->error('Permission denied: Managers only in CONTROL mode');
                $res = $this->addClient($data['data'] ?? []);
                if ($res['success']) $this->logEvent("Client Added: " . ($data['data']['nominativo'] ?? 'Unknown'), $username);
                return $res;
            case 'update':
                if (!$this->checkPermission($username)) return $this->error('Permission denied: Managers only in CONTROL mode');
                $res = $this->updateClient($data['id'] ?? '', $data['data'] ?? []);
                if ($res['success']) $this->logEvent("Client Updated: " . ($data['data']['nominativo'] ?? $data['id']), $username);
                return $res;
            case 'delete':
                if (!$this->checkPermission($username)) return $this->error('Permission denied: Managers only in CONTROL mode');
                $res = $this->deleteClient($data['id'] ?? '');
                if ($res['success']) $this->logEvent("Client Deleted: " . $data['id'], $username);
                return $res;
            case 'list':
                return $this->listClients($username);
            case 'get':
                return $this->getClient($data['id'] ?? '', $username);
            default:
                return $this->error('Action not recognized');
        }
    }

    private function checkPermission($username) {
        $config = $this->getSetupConfig();
        $mode = strtolower($config['mode'] ?? 'sync');
        $role = $this->getUserRole($username);
        
        if ($mode === 'control' && $role === 'technician') {
            return false;
        }
        return true;
    }

    private function getUserRole($username) {
        $usersFile = __DIR__ . '/../users.json';
        if (!file_exists($usersFile)) return 'technician';
        
        $users = json_decode(file_get_contents($usersFile), true);
        if (!is_array($users)) return 'technician';
        
        foreach ($users as $user) {
            if ($user['username'] === $username) {
                return $user['role'] ?? 'technician';
            }
        }
        return 'technician';
    }

    private function getSetupConfig() {
        $setupFile = __DIR__ . '/setup.json';
        if (file_exists($setupFile)) {
            return json_decode(file_get_contents($setupFile), true);
        }
        return [];
    }

    private function logEvent($action, $username = 'Anonymous') {
        $logFile = __DIR__ . '/../audit_log.json';
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action'    => $action,
            'user'      => $username,
            'ip'        => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'user_agent'=> $_SERVER['HTTP_USER_AGENT'] ?? 'None'
        ];

        $logs = [];
        if (file_exists($logFile)) {
            $logs = json_decode(file_get_contents($logFile), true) ?: [];
        }
        $logs[] = $entry;
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
    }
    
    private function addClient($data) {
        if (!$this->validateClientData($data)) {
            return $this->error('Invalid client data');
        }
        
        // Check if email already exists
        $clients = $this->loadClients();
        foreach ($clients as $client) {
            if (strtolower($client['email']) === strtolower($data['email'])) {
                return $this->error('Email already exists');
            }
        }
        
        $client = [
            'id' => $this->generateId(),
            'nominativo' => trim($data['nominativo']),
            'email' => trim(strtolower($data['email'])),
            'indirizzo' => trim($data['indirizzo'] ?? ''),
            'note' => trim($data['note'] ?? ''),
            'intelligence' => trim($data['intelligence'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $clients[] = $client;
        
        if ($this->saveClients($clients)) {
            return $this->success('Client added successfully', $client);
        } else {
            return $this->error('Error saving');
        }
    }
    
    private function updateClient($id, $data) {
        if (empty($id) || !$this->validateClientData($data)) {
            return $this->error('Invalid data');
        }
        
        $clients = $this->loadClients();
        $found = false;
        
        for ($i = 0; $i < count($clients); $i++) {
            if ($clients[$i]['id'] === $id) {
                // Check if email already exists (excluding current client)
                foreach ($clients as $client) {
                    if ($client['id'] !== $id && 
                        strtolower($client['email']) === strtolower($data['email'])) {
                        return $this->error('Email already exists');
                    }
                }
                
                $clients[$i]['nominativo'] = trim($data['nominativo']);
                $clients[$i]['email'] = trim(strtolower($data['email']));
                $clients[$i]['indirizzo'] = trim($data['indirizzo'] ?? '');
                $clients[$i]['note'] = trim($data['note'] ?? '');
                
                // Preserve intelligence if not provided in the update
                if (isset($data['intelligence'])) {
                    $clients[$i]['intelligence'] = trim($data['intelligence']);
                }
                
                $clients[$i]['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return $this->error('Client not found');
        }
        
        if ($this->saveClients($clients)) {
            return $this->success('Client updated successfully');
        } else {
            return $this->error('Error saving');
        }
    }
    
    private function deleteClient($id) {
        if (empty($id)) {
            return $this->error('Invalid client ID');
        }
        
        $clients = $this->loadClients();
        $initialCount = count($clients);
        
        $clients = array_filter($clients, function($client) use ($id) {
            return $client['id'] !== $id;
        });
        
        $clients = array_values($clients); // Reindex
        
        if (count($clients) === $initialCount) {
            return $this->error('Client not found');
        }
        
        if ($this->saveClients($clients)) {
            return $this->success('Client deleted successfully');
        } else {
            return $this->error('Error saving');
        }
    }
    
    private function listClients($username = 'Anonymous') {
        $config = $this->getSetupConfig();
        $mode = strtolower($config['mode'] ?? 'sync');
        $role = $this->getUserRole($username);
        
        $clients = $this->loadClients();
        
        if ($mode === 'control' && $role === 'technician') {
            $projectsFile = __DIR__ . '/projects.json';
            $assignedClientIds = [];
            if (file_exists($projectsFile)) {
                $projects = json_decode(file_get_contents($projectsFile), true) ?: [];
                foreach ($projects as $p) {
                    if (($p['assigned_to'] ?? '') === $username) {
                        $assignedClientIds[] = $p['cliente_id'];
                    }
                }
            }
            
            $clients = array_filter($clients, function($c) use ($assignedClientIds) {
                return in_array($c['id'], $assignedClientIds);
            });
            $clients = array_values($clients);
        }
        
        // Sort by name
        usort($clients, function($a, $b) {
            return strcmp(strtolower($a['nominativo']), strtolower($b['nominativo']));
        });
        
        return $this->success('Client list loaded', $clients);
    }
    
    private function getClient($id, $username = 'Anonymous') {
        if (empty($id)) {
            return $this->error('Invalid client ID');
        }

        $config = $this->getSetupConfig();
        $mode = strtolower($config['mode'] ?? 'sync');
        $role = $this->getUserRole($username);

        if ($mode === 'control' && $role === 'technician') {
            $projectsFile = __DIR__ . '/projects.json';
            $hasAccess = false;
            if (file_exists($projectsFile)) {
                $projects = json_decode(file_get_contents($projectsFile), true) ?: [];
                foreach ($projects as $p) {
                    if (($p['assigned_to'] ?? '') === $username && $p['cliente_id'] === $id) {
                        $hasAccess = true;
                        break;
                    }
                }
            }
            if (!$hasAccess) {
                return $this->error('Access denied to this client');
            }
        }
        
        $clients = $this->loadClients();
        
        foreach ($clients as $client) {
            if ($client['id'] === $id) {
                return $this->success('Client found', $client);
            }
        }
        
        return $this->error('Client not found');
    }
    
    private function loadClients() {
        if (!file_exists($this->filename)) {
            return [];
        }
        
        $content = file_get_contents($this->filename);
        $clients = json_decode($content, true);
        
        return is_array($clients) ? $clients : [];
    }
    
    private function saveClients($clients) {
        $json = json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($this->filename, $json) !== false;
    }
    
    private function validateClientData($data) {
        if (!is_array($data)) {
            return false;
        }
        
        // Name is required
        if (empty(trim($data['nominativo'] ?? ''))) {
            return false;
        }
        
        // Email is required and valid
        $email = trim($data['email'] ?? '');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        return true;
    }
    
    private function generateId() {
        return uniqid('client_', true);
    }
    
    private function success($message, $data = null) {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return $response;
    }
    
    private function error($message) {
        return [
            'success' => false,
            'message' => $message
        ];
    }
}

// Handle errors gracefully
try {
    $manager = new ClientManager();
    $response = $manager->handleRequest();
    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
    error_log('Client Manager Error: ' . $e->getMessage());
}
?>
