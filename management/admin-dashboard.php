<?php
session_start();

// Verifica se l'admin è loggato
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

$admin_username = $_SESSION['admin_username'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Amministratore</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header h1 {
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .welcome-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .welcome-section h2 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .welcome-section p {
            color: #666;
        }
        
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
            
            h3 {
            color: rebeccapurle;
                    margin-top: 48px;
        }
        
        .card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        .card-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .card h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }
        
        .card p {
            color: #666;
            line-height: 1.6;
        }
        
        .card-purge {
            border-top: 4px solid #ff4757;
        }
        
        .card-manage {
            border-top: 4px solid #2ecc71;
        }
            
        .card-mlist {
            border-top: 4px solid #cc2e7d;
        }
        .card-quotes {
            border-top: 4px solid #cccc2e;
        }              
        
        .card-disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .card-disabled:hover {
            transform: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        

            
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .main-content {
                padding: 0 1rem;
            }
            
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <h1>🚀 Dashboard Admin</h1>
            <div class="user-info">
                <span>Benvenuto, <?php echo htmlspecialchars($admin_username); ?></span>
                <a href="admin-logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="welcome-section">
            <h2>Pannello di Controllo</h2>
            <p>Gestisci il tuo sito web da questo pannello centralizzato. Seleziona una delle opzioni qui sotto per iniziare.</p>
        </div>
        <h3>Iscritti Sito</h3>
        <div class="cards-grid">
            <div class="card card-purge" onclick="location.href='admin-purge-users.php'">
                <div class="card-icon">🗑️</div>
                <h3>Purga Utenti</h3>
                <p>Elimina utenti inattivi o non verificati dal sistema. Mantieni pulito il database rimuovendo account obsoleti.</p>
            </div>
            
            <div class="card card-manage" onclick="location.href='admin-manage-users.php'">
                <div class="card-icon">👥</div>
                <h3>Gestisci Utenti</h3>
                <p>Visualizza e gestisci tutti gli utenti registrati. Controlla e modifica piani e informazioni degli account.</p>
            </div>
        </div>
        <h3>Iscritti Newsletter</h3>    
        <div class="cards-grid">  
            <div class="card card-purge" onclick="location.href='admin-list-mlist-users.php'">
                <div class="card-icon">👥</div>
                <h3>Visualizza Iscritti Newsletters</h3>
                <p>Controlla la lista degli utenti che hanno sottoscritto la Newsletter.</p>
            </div>    
            <div class="card card-mlist" onclick="location.href='admin-manage-mlist-users.php'">
                <div class="card-icon">📧</div>
                <h3>Manda Newsletter</h3>
                <p>Manda una email a tutti i visitatori che hanno sottoscritto alla Newsletter.</p>
            </div>     
        </div>
        <h3>Clienti e Progetti</h3>
        <div class="cards-grid">     
            <div class="card card-mlist" onclick="location.href='admin-massmail.php'">
                <div class="card-icon">📧</div>
                <h3>Manda Mail a Clienti</h3>
                <p>Manda una email a tutti i potenziali clienti caricando un file JSON di contatti.</p>
            </div>                  
                
            <div class="card card-quotes" onclick="location.href='./genera_preventivi/clients_manager.html'">
                <div class="card-icon">👥</div>
                <h3>Gestisci Clienti</h3>
                <p>Crea ed aggiorna l'anagrafica clienti per i progetti.</p>
            </div>
            <div class="card card-mlist" onclick="location.href='./genera_preventivi/project_manager.html'">
                <div class="card-icon">🏗️</div>
                <h3>Gestisci Progetti</h3>
                <p>Crea ed aggiorna l'anagrafica dei progetti per i tuoi clienti.</p>
            </div>   
            <div class="card card-purge" onclick="location.href='./genera_preventivi/profile.php'">
                <div class="card-icon">💶</div>
                <h3>Genera Preventivo</h3>
                <p>Modifica i dati dell'Attività e genera preventivi per i lavori, con sconti e IVA.</p>
            </div>
            <div class="card card-manage" onclick="location.href='./contratti/gestione_contratti.php'">
                <div class="card-icon">📃🖊</div>
                <h3>Gestione Contratti</h3>
                <p>Crea, salva e stampa un nuovo contratto di collaborazione occasionale o rivedi lo storico.</p>
            </div>
        </div>
        <h3>Sviluppo</h3>
        <div class="cards-grid">     
            <div class="card card-mlist" onclick="location.href='./DATABASE_PROGETTI/proj_manager.html'">
                <div class="card-icon">🏗️</div>
                <h3>Gestisci Sviluppo</h3>
                <p>Salva e visualizza i dati dei progetti sviluppati o in sviluppo.</p>
            </div>                  

        </div>            
    </main>
</body>
</html>