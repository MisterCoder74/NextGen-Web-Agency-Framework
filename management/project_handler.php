<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS requests for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class ProjectManager {
    private $filename = 'projects.json';
    private $clientsFilename = 'clients.json';
    
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
                $res = $this->addProject($data['data'] ?? []);
                if ($res['success']) $this->logEvent("Project Added: " . ($data['data']['nome_progetto'] ?? 'Unknown'), $username);
                return $res;
            case 'update':
                $id = $data['id'] ?? '';
                $newData = $data['data'] ?? [];
                
                // Get old data for comparison
                $oldProject = null;
                $projects = $this->loadProjects();
                foreach ($projects as $p) {
                    if ($p['id'] === $id) {
                        $oldProject = $p;
                        break;
                    }
                }
                
                $res = $this->updateProject($id, $newData);
                
                if ($res['success'] && $oldProject) {
                    $changes = [];
                    $fields = ['nome_progetto', 'tipologia', 'cliente_id', 'stato', 'data_inizio', 'data_fine', 'budget', 'priorita', 'descrizione'];
                    
                    foreach ($fields as $field) {
                        $oldVal = $oldProject[$field] ?? '';
                        $newVal = $newData[$field] ?? '';
                        
                        if ($field === 'budget') {
                            $oldVal = floatval($oldVal);
                            $newVal = floatval($newVal);
                        }
                        
                        if ($oldVal != $newVal) {
                            $changes[] = "$field: $oldVal -> $newVal";
                        }
                    }
                    
                    $changeLog = !empty($changes) ? " (Changes: " . implode(", ", $changes) . ")" : " (No field changes)";
                    $this->logEvent("Project Updated: " . ($newData['nome_progetto'] ?? $id) . $changeLog, $username);
                }
                return $res;
            case 'delete':
                $res = $this->deleteProject($data['id'] ?? '');
                if ($res['success']) $this->logEvent("Project Deleted: " . $data['id'], $username);
                return $res;
            case 'list':
                return $this->listProjects();
            case 'get':
                return $this->getProject($data['id'] ?? '');
            default:
                return $this->error('Action not recognized');
        }
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
    
    private function addProject($data) {
        if (!$this->validateProjectData($data)) {
            return $this->error('Invalid project data');
        }
        
        // Verify that client exists
        if (!$this->clientExists($data['cliente_id'])) {
            return $this->error('Invalid client selected');
        }
        
        // Check if project with same name already exists for same client
        $projects = $this->loadProjects();
        foreach ($projects as $project) {
            if (strtolower($project['nome_progetto']) === strtolower($data['nome_progetto']) && 
                $project['cliente_id'] === $data['cliente_id']) {
                return $this->error('A project with this name already exists for the selected client');
            }
        }
        
        $project = [
            'id' => $this->generateId(),
            'nome_progetto' => trim($data['nome_progetto']),
            'tipologia' => trim($data['tipologia']),
            'cliente_id' => trim($data['cliente_id']),
            'stato' => $data['stato'] ?? 'active',
            'data_inizio' => $data['data_inizio'] ?? null,
            'data_fine' => $data['data_fine'] ?? null,
            'budget' => floatval($data['budget'] ?? 0),
            'priorita' => $data['priorita'] ?? 'medium',
            'descrizione' => trim($data['descrizione'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $projects[] = $project;
        
        if ($this->saveProjects($projects)) {
            return $this->success('Project added successfully', $project);
        } else {
            return $this->error('Error saving');
        }
    }
    
    private function updateProject($id, $data) {
        if (empty($id) || !$this->validateProjectData($data)) {
            return $this->error('Invalid data');
        }
        
        // Verify that client exists
        if (!$this->clientExists($data['cliente_id'])) {
            return $this->error('Invalid client selected');
        }
        
        $projects = $this->loadProjects();
        $found = false;
        
        for ($i = 0; $i < count($projects); $i++) {
            if ($projects[$i]['id'] === $id) {
                // Check if project with same name already exists (excluding current one)
                foreach ($projects as $project) {
                    if ($project['id'] !== $id && 
                        strtolower($project['nome_progetto']) === strtolower($data['nome_progetto']) && 
                        $project['cliente_id'] === $data['cliente_id']) {
                        return $this->error('A project with this name already exists for the selected client');
                    }
                }
                
                $projects[$i]['nome_progetto'] = trim($data['nome_progetto']);
                $projects[$i]['tipologia'] = trim($data['tipologia']);
                $projects[$i]['cliente_id'] = trim($data['cliente_id']);
                $projects[$i]['stato'] = $data['stato'] ?? 'active';
                $projects[$i]['data_inizio'] = $data['data_inizio'] ?? null;
                $projects[$i]['data_fine'] = $data['data_fine'] ?? null;
                $projects[$i]['budget'] = floatval($data['budget'] ?? 0);
                $projects[$i]['priorita'] = $data['priorita'] ?? 'medium';
                $projects[$i]['descrizione'] = trim($data['descrizione'] ?? '');
                $projects[$i]['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return $this->error('Project not found');
        }
        
        if ($this->saveProjects($projects)) {
            return $this->success('Project updated successfully');
        } else {
            return $this->error('Error saving');
        }
    }
    
    private function deleteProject($id) {
        if (empty($id)) {
            return $this->error('Invalid project ID');
        }
        
        $projects = $this->loadProjects();
        $initialCount = count($projects);
        
        $projects = array_filter($projects, function($project) use ($id) {
            return $project['id'] !== $id;
        });
        
        $projects = array_values($projects); // Reindex
        
        if (count($projects) === $initialCount) {
            return $this->error('Project not found');
        }
        
        if ($this->saveProjects($projects)) {
            return $this->success('Project deleted successfully');
        } else {
            return $this->error('Error saving');
        }
    }
    
    private function listProjects() {
        $projects = $this->loadProjects();
        
        // Sort by priority and creation date
        usort($projects, function($a, $b) {
            $priorityOrder = ['urgent' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
            $aPriority = $priorityOrder[$a['priorita']] ?? 2;
            $bPriority = $priorityOrder[$b['priorita']] ?? 2;
            
            if ($aPriority !== $bPriority) {
                return $bPriority - $aPriority; // Descending priority
            }
            
            return strcmp($a['created_at'], $b['created_at']);
        });
        
        return $this->success('Project list loaded', $projects);
    }
    
    private function getProject($id) {
        if (empty($id)) {
            return $this->error('Invalid project ID');
        }
        
        $projects = $this->loadProjects();
        
        foreach ($projects as $project) {
            if ($project['id'] === $id) {
                return $this->success('Project found', $project);
            }
        }
        
        return $this->error('Project not found');
    }
    
    private function loadProjects() {
        if (!file_exists($this->filename)) {
            return [];
        }
        
        $content = file_get_contents($this->filename);
        $projects = json_decode($content, true);
        
        return is_array($projects) ? $projects : [];
    }
    
    private function saveProjects($projects) {
        $json = json_encode($projects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return file_put_contents($this->filename, $json) !== false;
    }
    
    private function clientExists($clientId) {
        if (!file_exists($this->clientsFilename)) {
            return false;
        }
        
        $content = file_get_contents($this->clientsFilename);
        $clients = json_decode($content, true);
        
        if (!is_array($clients)) {
            return false;
        }
        
        foreach ($clients as $client) {
            if ($client['id'] === $clientId) {
                return true;
            }
        }
        
        return false;
    }
    
    private function validateProjectData($data) {
        if (!is_array($data)) {
            return false;
        }
        
        // Project name is required
        if (empty(trim($data['nome_progetto'] ?? ''))) {
            return false;
        }
        
        // Type is required
        if (empty(trim($data['tipologia'] ?? ''))) {
            return false;
        }
        
        // Client is required
        if (empty(trim($data['cliente_id'] ?? ''))) {
            return false;
        }
        
        // Date validation if present
        if (!empty($data['data_inizio']) && !$this->isValidDate($data['data_inizio'])) {
            return false;
        }
        
        if (!empty($data['data_fine']) && !$this->isValidDate($data['data_fine'])) {
            return false;
        }
        
        // Enum validation
        $validStati = ['active', 'completed', 'suspended', 'cancelled'];
        if (!empty($data['stato']) && !in_array($data['stato'], $validStati)) {
            return false;
        }
        
        $validPriorita = ['low', 'medium', 'high', 'urgent'];
        if (!empty($data['priorita']) && !in_array($data['priorita'], $validPriorita)) {
            return false;
        }
        
        // Budget validation
        if (isset($data['budget']) && !is_numeric($data['budget'])) {
            return false;
        }
        
        return true;
    }
    
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    private function generateId() {
        return uniqid('project_', true);
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
    $manager = new ProjectManager();
    $response = $manager->handleRequest();
    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
    error_log('Project Manager Error: ' . $e->getMessage());
}
?>
