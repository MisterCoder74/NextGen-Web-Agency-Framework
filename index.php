<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vivacity NextGen Web Agency Framework - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@300;400;500;700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="tools/css/global.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            margin: 0;
            overflow-y: auto;
            font-family: 'Syne', sans-serif;
        }

        .hero-section {
            text-align: center;
            padding: 60px 20px;
            max-width: 900px;
        }

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 20px;
            background: linear-gradient(90deg, #14b8a6, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.1;
        }

        .hero-section p {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 40px;
            font-family: 'Instrument Serif', serif;
            font-style: italic;
        }

        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(12px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            margin-bottom: 60px;
        }

        .login-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #5eead4;
            margin-bottom: 24px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.5);
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-group input {
            width: 100%;
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            padding: 12px 16px;
            font-size: 1rem;
            outline: none;
            transition: all 0.2s;
            font-family: 'JetBrains Mono', monospace;
        }

        .form-group input:focus {
            border-color: #14b8a6;
            background: rgba(255, 255, 255, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            margin-top: 10px;
            font-size: 1rem;
            letter-spacing: 1px;
        }

        .error-msg {
            background: rgba(255, 107, 107, 0.1);
            color: #ff6b6b;
            border: 1px solid rgba(255, 107, 107, 0.2);
            padding: 10px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 20px;
            text-align: center;
        }

        footer {
            width: 100%;
            padding: 40px 20px;
            background: rgba(0, 0, 0, 0.2);
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.4);
        }

        .footer-content {
            max-width: 1000px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .footer-links {
            margin-bottom: 15px;
        }

        .footer-links a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            margin: 0 10px;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: #5eead4;
        }

        .footer-details {
            margin-top: 10px;
        }

        .footer-details strong {
            color: rgba(255, 255, 255, 0.7);
        }
    </style>
</head>
<body>

    <div class="hero-section">
        <h1>Vivacity NextGen<br>Web Agency Framework</h1>
        <p>Potenzia il tuo workflow con l'intelligenza artificiale generativa di nuova generazione.</p>
        
        <div class="login-card">
            <h2>Accedi alla Suite</h2>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="error-msg">
                    <?php
                        switch($_GET['error']) {
                            case '1': echo "Credenziali non valide. Riprova."; break;
                            case '2': echo "Errore di sistema: Archivio utenti non trovato."; break;
                            case '3': echo "Per favore, compila tutti i campi."; break;
                            default: echo "Si è verificato un errore durante l'accesso.";
                        }
                    ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-login">Entra nel Futuro</button>
            </form>
        </div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="#">Termini di Servizio</a>
                <a href="#">Privacy Policy</a>
                <a href="#">Cookie Policy</a>
            </div>
            <div class="footer-details">
                &copy; <?php echo date('Y'); ?> <strong>Vivacity Design</strong>. Tutti i diritti riservati.<br>
                La proprietà intellettuale di questo framework e di tutti i contenuti generati rimane di esclusiva pertinenza di Vivacity Design.<br>
                <strong>Titolare del Trattamento:</strong> Alessandro Demontis | <strong>Contatto:</strong> info@vivacitydesign.net<br>
                Conformità GDPR per l'Italia e l'Unione Europea.
            </div>
        </div>
    </footer>

</body>
</html>
