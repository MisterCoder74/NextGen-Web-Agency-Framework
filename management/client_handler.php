<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gestisce le richieste OPTIONS per CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

class ClientManager {
    private $filename = 'clients.json';
    
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
                return $this->addClient($data['data'] ?? []);
            case 'update':
                return $this->updateClient($data['id'] ?? '', $data['data'] ?? []);
            case 'delete':
                return $this->deleteClient($data['id'] ?? '');
            case 'list':
                return $this->listClients();
            case 'get':
                return $this->getClient($data['id'] ?? '');
            default:
                return $this->error('Azione non riconosciuta');
        }
    }
    
    private function addClient($data) {
        if (!$this->validateClientData($data)) {
            return $this->error('Dati cliente non validi');
        }
        
        // Controlla se l'email esiste già
        $clients = $this->loadClients();
        foreach ($clients as $client) {
            if (strtolower($client['email']) === strtolower($data['email'])) {
                return $this->error('Email già esistente');
            }
        }
        
        $client = [
            'id' => $this->generateId(),
            'nominativo' => trim($data['nominativo']),
            'email' => trim(strtolower($data['email'])),
            'indirizzo' => trim($data['indirizzo'] ?? ''),
            'note' => trim($data['note'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $clients[] = $client;
        
        if ($this->saveClients($clients)) {
            return $this->success('Cliente aggiunto con successo', $client);
        } else {
            return $this->error('Errore nel salvataggio');
        }
    }
    
    private function updateClient($id, $data) {
        if (empty($id) || !$this->validateClientData($data)) {
            return $this->error('Dati non validi');
        }
        
        $clients = $this->loadClients();
        $found = false;
        
        for ($i = 0; $i < count($clients); $i++) {
            if ($clients[$i]['id'] === $id) {
                // Controlla se l'email esiste già (escludendo il cliente corrente)
                foreach ($clients as $client) {
                    if ($client['id'] !== $id && 
                        strtolower($client['email']) === strtolower($data['email'])) {
                        return $this->error('Email già esistente');
                    }
                }
                
                $clients[$i]['nominativo'] = trim($data['nominativo']);
                $clients[$i]['email'] = trim(strtolower($data['email']));
                $clients[$i]['indirizzo'] = trim($data['indirizzo'] ?? '');
                $clients[$i]['note'] = trim($data['note'] ?? '');
                $clients[$i]['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            return $this->error('Cliente non trovato');
        }
        
        if ($this->saveClients($clients)) {
            return $this->success('Cliente aggiornato con successo');
        } else {
            return $this->error('Errore nel salvataggio');
        }
    }
    
    private function deleteClient($id) {
        if (empty($id)) {
            return $this->error('ID cliente non valido');
        }
        
        $clients = $this->loadClients();
        $initialCount = count($clients);
        
        $clients = array_filter($clients, function($client) use ($id) {
            return $client['id'] !== $id;
        });
        
        $clients = array_values($clients); // Riordina gli indici
        
        if (count($clients) === $initialCount) {
            return $this->error('Cliente non trovato');
        }
        
        if ($this->saveClients($clients)) {
            return $this->success('Cliente eliminato con successo');
        } else {
            return $this->error('Errore nel salvataggio');
        }
    }
    
    private function listClients() {
        $clients = $this->loadClients();
        
        // Ordina per nome
        usort($clients, function($a, $b) {
            return strcmp(strtolower($a['nominativo']), strtolower($b['nominativo']));
        });
        
        return $this->success('Lista clienti caricata', $clients);
    }
    
    private function getClient($id) {
        if (empty($id)) {
            return $this->error('ID cliente non valido');
        }
        
        $clients = $this->loadClients();
        
        foreach ($clients as $client) {
            if ($client['id'] === $id) {
                return $this->success('Cliente trovato', $client);
            }
        }
        
        return $this->error('Cliente non trovato');
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
        
        // Nominativo obbligatorio
        if (empty(trim($data['nominativo'] ?? ''))) {
            return false;
        }
        
        // Email obbligatoria e valida
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

// Gestisce gli errori in modo elegante
try {
    $manager = new ClientManager();
    $response = $manager->handleRequest();
    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Errore interno del server'
    ]);
    error_log('Client Manager Error: ' . $e->getMessage());
}
?>