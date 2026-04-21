<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestisce le richieste OPTIONS per CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class ProjectManager {
    private $filename = 'projects.json';
    private $clientsFilename = 'clients.json';
    
    public function __construct() {
        // Crea il file se non esiste
        if (!file_exists($this->filename)) {
            file_put_contents($this->filename, json_encode([]));
        }
    }
    
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->error('Metodo non supportato');
        }
        
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!$data || !isset($data['action'])) {
            return $this->error('Dati non validi');
        }
        
        switch ($data['action']) {
            case 'add':
                return $this->addProject($data['data'] ?? []);
            case 'update':
                return $this->updateProject($data['id'] ?? '', $data['data'] ?? []);
            case 'delete':
                return $this->deleteProject($data['id'] ?? '');
            case 'list':
                return $this->listProjects();
            case 'get':
                return $this->getProject($data['id'] ?? '');
            default:
                return $this->error('Azione non riconosciuta');
        }
    }
    
    private function addProject($data) {
        if (!$this->validateProjectData($data)) {
            return $this->error('Dati progetto non validi');
        }
        
        // Verifica che il cliente esista
        if (!$this->clientExists($data['cliente_id'])) {
            return $this->error('Cliente selezionato non valido');
        }
        
        // Controlla se esiste già un progetto con lo stesso nome per lo stesso cliente
        $projects = $this->loadProjects();
        foreach ($projects as $project) {
            if (strtolower($project['nome_progetto']) === strtolower($data['nome_progetto']) && 
                $project['cliente_id'] === $data['cliente_id']) {
                return $this->error('Esiste già un progetto con questo nome per il cliente selezionato');
            }
        }
        
        $project = [
            'id' => $this->generateId(),
            'nome_progetto' => trim($data['nome_progetto']),
            'tipologia' => trim($data['tipologia']),
            'cliente_id' => trim($data['cliente_id']),
            'stato' => $data['stato'] ?? 'attivo',
            'data_inizio' => $data['data_inizio'] ?? null,
            'data_fine' => $data['data_fine'] ?? null,
            'budget' => floatval($data['budget'] ?? 0),
            'priorita' => $data['priorita'] ?? 'media',
            'descrizione' => trim($data['descrizione'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $projects[] = $project;
        
        if ($this->saveProjects($projects)) {
            return $this->success('Progetto aggiunto con successo', $project);
        } else {
            return $this->error('Errore nel salvataggio');
        }
    }
    
    private function updateProject($id, $data) {
        if (empty($id) || !$this->validateProjectData($data)) {
            return $this->error('Dati non validi');
        }
        
        // Verifica che il cliente esista
        if (!$this->clientExists($data['cliente_id'])) {
            return $this->error('Cliente selezionato non valido');
        }
        
        $projects = $this->loadProjects();
        $found = false;
        
        for ($i = 0; $i < count($projects); $i++) {
            if ($projects[$i]['id'] === $id) {
                // Controlla se esiste già un progetto con lo stesso nome (escludendo quello corrente)
                foreach ($projects as $project) {
                    if ($project['id'] !== $id && 
                        strtolower($project['nome_progetto']) === strtolower($data['nome_progetto']) && 
                        $project['cliente_id'] === $data['cliente_id']) {
                        return $this->error('Esiste già un progetto con questo nome per il cliente selezionato');
                    }
                }
                
                $projects[$i]['nome_progetto'] = trim($data['nome_progetto']);
                $projects[$i]['tipologia'] = trim($data['tipologia']);
                $projects[$i]['cliente_id'] = trim($data['cliente_id']);
                $projects[$i]['stato'] = $data['stato'] ?? 'attivo';
                $projects[$i]['data_inizio'] = $data['data_inizio'] ?? null;
                $projects[$i]['data_fine'] = $data['data_fine'] ?? null;
                $projects[$i]['budget'] = floatval($data['budget'] ?? 0);
                $projects[$i]['priorita'] = $data['priorita'] ?? 'media';
                $projects[$i]['descrizione'] = trim($data['descrizione'] ?? '');
                $projects[$i]['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return $this->error('Progetto non trovato');
        }
        
        if ($this->saveProjects($projects)) {
            return $this->success('Progetto aggiornato con successo');
        } else {
            return $this->error('Errore nel salvataggio');
        }
    }
    
    private function deleteProject($id) {
        if (empty($id)) {
            return $this->error('ID progetto non valido');
        }
        
        $projects = $this->loadProjects();
        $initialCount = count($projects);
        
        $projects = array_filter($projects, function($project) use ($id) {
            return $project['id'] !== $id;
        });
        
        $projects = array_values($projects); // Riordina gli indici
        
        if (count($projects) === $initialCount) {
            return $this->error('Progetto non trovato');
        }
        
        if ($this->saveProjects($projects)) {
            return $this->success('Progetto eliminato con successo');
        } else {
            return $this->error('Errore nel salvataggio');
        }
    }
    
    private function listProjects() {
        $projects = $this->loadProjects();
        
        // Ordina per priorità e data di creazione
        usort($projects, function($a, $b) {
            $priorityOrder = ['urgente' => 4, 'alta' => 3, 'media' => 2, 'bassa' => 1];
            $aPriority = $priorityOrder[$a['priorita']] ?? 2;
            $bPriority = $priorityOrder[$b['priorita']] ?? 2;
            
            if ($aPriority !== $bPriority) {
                return $bPriority - $aPriority; // Ordine decrescente per priorità
            }
            
            return strcmp($a['created_at'], $b['created_at']);
        });
        
        return $this->success('Lista progetti caricata', $projects);
    }
    
    private function getProject($id) {
        if (empty($id)) {
            return $this->error('ID progetto non valido');
        }
        
        $projects = $this->loadProjects();
        
        foreach ($projects as $project) {
            if ($project['id'] === $id) {
                return $this->success('Progetto trovato', $project);
            }
        }
        
        return $this->error('Progetto non trovato');
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
        
        // Nome progetto obbligatorio
        if (empty(trim($data['nome_progetto'] ?? ''))) {
            return false;
        }
        
        // Tipologia obbligatoria
        if (empty(trim($data['tipologia'] ?? ''))) {
            return false;
        }
        
        // Cliente obbligatorio
        if (empty(trim($data['cliente_id'] ?? ''))) {
            return false;
        }
        
        // Validazione date se presenti
        if (!empty($data['data_inizio']) && !$this->isValidDate($data['data_inizio'])) {
            return false;
        }
        
        if (!empty($data['data_fine']) && !$this->isValidDate($data['data_fine'])) {
            return false;
        }
        
        // Validazione valori enum
        $validStati = ['attivo', 'completato', 'sospeso', 'annullato'];
        if (!empty($data['stato']) && !in_array($data['stato'], $validStati)) {
            return false;
        }
        
        $validPriorita = ['bassa', 'media', 'alta', 'urgente'];
        if (!empty($data['priorita']) && !in_array($data['priorita'], $validPriorita)) {
            return false;
        }
        
        // Validazione budget
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

// Gestisce gli errori in modo elegante
try {
    $manager = new ProjectManager();
    $response = $manager->handleRequest();
    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno del server'
    ]);
    error_log('Project Manager Error: ' . $e->getMessage());
}
?>