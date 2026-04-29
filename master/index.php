<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Workspace - Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@300;400;500;700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="tools/css/global.css?v=<?php echo time(); ?>">
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

        .login-card {
            width: 400px;
            padding: 48px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(12px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            margin: 60px 0;
        }

        .login-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #5eead4;
            margin-bottom: 24px;
            text-align: center;
        }

        .tenant-badge {
            font-size: 0.7rem;
            font-weight: 700;
            background: rgba(20, 184, 166, 0.2);
            border: 1px solid rgba(20, 184, 166, 0.3);
            color: #5eead4;
            padding: 4px 12px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
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
    </style>
</head>
<body>

    <div class="login-card">
        <div class="tenant-badge">Tenant Workspace</div>
        <h2>Access Your Workspace</h2>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-msg">
                <?php
                    switch($_GET['error']) {
                        case '1': echo "Invalid credentials. Please try again."; break;
                        case '2': echo "System error: User archive not found."; break;
                        case '3': echo "Please fill in all fields."; break;
                        case '5': echo "This workspace has been suspended."; break;
                        default: echo "An error occurred during login.";
                    }
                ?>
            </div>
        <?php endif; ?>

        <?php $tenantParam = isset($_GET['tenant']) ? '&tenant=' . urlencode($_GET['tenant']) : ''; ?>
        <?php $tenantValue = isset($_GET['tenant']) ? htmlspecialchars($_GET['tenant']) : ''; ?>

        <form action="login.php" method="POST">
            <?php if ($tenantValue): ?>
                <input type="hidden" name="tenant" value="<?php echo $tenantValue; ?>">
            <?php endif; ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="off">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary btn-login">Enter Your Workspace</button>
        </form>
    </div>

    <footer>
        <strong>Vivacity Design</strong> — NextGen Web Agency Framework
    </footer>

</body>
</html>
