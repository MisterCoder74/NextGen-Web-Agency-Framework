<?php
session_start();

// Verifica se l'admin è loggato
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../index.php');
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';

// Funzioni per gestire i dati aziendali
function load_company_data() {
    $setup_file = 'setup.json';
    
    if (!file_exists($setup_file)) {
        // Crea file setup.json con dati di default se non esiste
        $default_data = [
            "company" => [
                "name" => "",
                "alias" => "",                    
                "address" => "",
                "phone" => "",
                "iva" => ""
            ]
        ];
        file_put_contents($setup_file, json_encode($default_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $default_data;
    }
    
    $json_data = file_get_contents($setup_file);
    $data = json_decode($json_data, true);
    
    if (!$data) {
        throw new Exception("Errore nel leggere il file setup.json");
    }
    
    return $data;
}

function save_company_data($company_data) {
    $setup_file = 'setup.json';
    
    try {
        $data = load_company_data();
        $data['company'] = $company_data;
        
        $json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (file_put_contents($setup_file, $json_data) === false) {
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Errore salvataggio setup.json: " . $e->getMessage());
        return false;
    }
}

function validate_iva($iva) {
    // Rimuove spazi e caratteri non numerici
    $iva = preg_replace('/[^0-9]/', '', $iva);
    
    // P.IVA italiana: 11 cifre
    if (strlen($iva) !== 11) {
        return false;
    }
    
    // Controllo algoritmo P.IVA italiana
    $sum = 0;
    for ($i = 0; $i < 10; $i++) {
        $digit = (int)$iva[$i];
        if ($i % 2 === 1) {
            $digit *= 2;
            if ($digit > 9) {
                $digit = $digit - 9;
            }
        }
        $sum += $digit;
    }
    
    $checkDigit = (10 - ($sum % 10)) % 10;
    return $checkDigit === (int)$iva[10];
}

// Carica i dati aziendali
$errors = [];
$success = false;

try {
    $setup_data = load_company_data();
    $company = $setup_data['company'] ?? [];
} catch (Exception $e) {
    $errors[] = "Errore nel caricamento dei dati: " . $e->getMessage();
    $company = ['name' => '', 'alias' => '', 'address' => '', 'phone' => '', 'iva' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = trim($_POST['company_name'] ?? '');
    $company_alias = trim($_POST['company_alias'] ?? '');        
    $company_address = trim($_POST['company_address'] ?? '');
    $company_phone = trim($_POST['company_phone'] ?? '');
    $iva = trim($_POST['company_iva'] ?? '');
    
    // Validazioni
    if (empty($company_name)) {
        $errors[] = "Il nome dell'azienda è obbligatorio.";
    }
    
    if (empty($company_address)) {
        $errors[] = "L'indirizzo dell'azienda è obbligatorio.";
    }
    
    if (empty($company_phone)) {
        $errors[] = "Il telefono dell'azienda è obbligatorio.";
    } elseif (!preg_match('/^[\d\s\+\-\(\)\.]{8,20}$/', $company_phone)) {
        $errors[] = "Formato telefono non valido.";
    }
    
    if (empty($iva)) {
        $errors[] = "La P.IVA è obbligatoria.";
    } elseif (!validate_iva($iva)) {
        $errors[] = "P.IVA non valida. Inserisci una P.IVA italiana di 11 cifre.";
    }
    
    if (empty($errors)) {
        $new_company_data = [
            'name' => $company_name,
            'alias' => $company_alias,                
            'address' => $company_address,
            'phone' => $company_phone,
            'iva' => preg_replace('/[^0-9]/', '', $iva) // Salva solo i numeri
        ];
        
        if (save_company_data($new_company_data)) {
            $success = true;
            $company = $new_company_data; // Aggiorna i dati visualizzati
        } else {
            $errors[] = "Errore nel salvataggio dei dati.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dati Aziendali - Amministrazione</title>
<link rel="stylesheet" href="css/style.css" />
<style>
.errors {
    background-color: #ffe6e6;
    color: #d8000c;
    border: 1px solid #d8000c;
    padding: 10px;
    margin: 10px 0;
    border-radius: 4px;
}
.success {
    background-color: #e6ffe6;
    color: #4caf50;
    border: 1px solid #4caf50;
    padding: 10px;
    margin: 10px 0;
    border-radius: 4px;
}
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
.form-group input {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box;
}
.form-group input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0,123,255,0.3);
}
.btn {
    background-color: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    text-decoration: none;
    display: inline-block;
}
.btn:hover {
    background-color: #0056b3;
}
.btn-secondary {
    background-color: #6c757d;
}
.btn-secondary:hover {
    background-color: #545b62;
}
</style>
</head>
<body>
<div class="container">
    <h1>Ciao, <?=htmlspecialchars($admin_username)?> - Dati Aziendali</h1>
    
    <nav style="margin-bottom: 20px;">
        <a href="quote.php" class="btn btn-secondary">Vai al generatore preventivi</a>
        <a href="../admin-dashboard.php" class="btn btn-secondary">Dashboard Admin</a>
        <a href="logout.php" class="btn btn-secondary">Disconnetti</a>
    </nav>
    
    <?php if($errors): ?>
        <div class="errors">
            <h3>Errori:</h3>
            <ul>
                <?php foreach($errors as $error): ?>
                    <li><?=htmlspecialchars($error)?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <?php if($success): ?>
        <div class="success">
            <strong>✓ Dati aziendali salvati con successo!</strong>
        </div>
    <?php endif; ?>
    
    <div style="background: #f8f9fa; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
        <h3>Informazioni</h3>
        <p>Questi dati vengono utilizzati per generare i preventivi e fatture. Assicurati che siano corretti e aggiornati.</p>
    </div>
    
    <form method="POST">
        <div class="form-group">
            <label for="company_name">Nome Azienda *</label>
            <input type="text" 
                   id="company_name" 
                   name="company_name" 
                   value="<?=htmlspecialchars($company['name'] ?? '')?>" 
                   required 
                   maxlength="100" />
        </div>
        <div class="form-group">
            <label for="company_alias">Alias Azienda</label>
            <input type="text" 
                   id="company_alias" 
                   name="company_alias" 
                   value="<?=htmlspecialchars($company['alias'] ?? '')?>" 
                   maxlength="100" />
        </div>            
        
        <div class="form-group">
            <label for="company_address">Indirizzo Azienda *</label>
            <input type="text" 
                   id="company_address" 
                   name="company_address" 
                   value="<?=htmlspecialchars($company['address'] ?? '')?>" 
                   required 
                   maxlength="200" />
        </div>
        
        <div class="form-group">
            <label for="company_phone">Telefono Azienda *</label>
            <input type="tel" 
                   id="company_phone" 
                   name="company_phone" 
                   value="<?=htmlspecialchars($company['phone'] ?? '')?>" 
                   required 
                   maxlength="20"
                   placeholder="Es: +39 328 08 75 031" />
        </div>
        
        <div class="form-group">
            <label for="company_iva">Partita IVA *</label>
            <input type="text" 
                   id="company_iva" 
                   name="company_iva" 
                   value="<?=htmlspecialchars($company['iva'] ?? '')?>" 
                   required 
                   pattern="[0-9]{11}"
                   maxlength="11"
                   placeholder="Es: 17770011009" />
            <small style="color: #666; font-size: 0.9em;">Inserisci 11 cifre della P.IVA italiana</small>
        </div>
        
        <button type="submit" class="btn">
            Salva Dati Aziendali
        </button>
    </form>
    
    <?php if(!empty($company['name'])): ?>
    <div style="margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 4px;">
        <h3>Anteprima Dati Correnti</h3>
        <p><strong>Nome:</strong> <?=htmlspecialchars($company['name'])?></p>
        <p><strong>Alias:</strong> <?=htmlspecialchars($company['alias'])?></p>            
        <p><strong>Indirizzo:</strong> <?=htmlspecialchars($company['address'])?></p>
        <p><strong>Telefono:</strong> <?=htmlspecialchars($company['phone'])?></p>
        <p><strong>P.IVA:</strong> <?=htmlspecialchars($company['iva'])?></p>
    </div>
    <?php endif; ?>
</div>

<script>
// Formattazione automatica P.IVA (solo numeri)
document.getElementById('company_iva').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
});

// Formattazione telefono
document.getElementById('company_phone').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9\s\+\-\(\)\.]/g, '');
});

// Conferma prima del salvataggio
document.querySelector('form').addEventListener('submit', function(e) {
    const name = document.getElementById('company_name').value;
    const alias = document.getElementById('company_alias').value;        
    const iva = document.getElementById('company_iva').value;
    
    if (!confirm(`Confermi di voler salvare i dati per: ${name} (P.IVA: ${iva})?`)) {
        e.preventDefault();
    }
});
</script>

</body>
</html>