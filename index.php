<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NextGen Web Agency Framework - Login</title>
    <?php 
    $version = time(); 
    $setup_file = 'management/setup.json';
    $app_mode = 'SYNC';
    if (file_exists($setup_file)) {
        $setup_data = json_decode(file_get_contents($setup_file), true);
        if (isset($setup_data['mode'])) {
            $app_mode = strtoupper($setup_data['mode']);
        }
    }
    $mode_class = ($app_mode === 'CONTROL') ? 'mode-control' : 'mode-sync';
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@300;400;500;700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <script src="management/js/identity.js?v=<?php echo $version; ?>"></script>
    <link rel="stylesheet" href="tools/css/global.css?v=<?php echo $version; ?>">
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
            padding: 30px 16px;
            max-width: 980px;
        }

        .hero-section h1 {
            font-size: 3.2rem;
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
            
        .middle-container {
            display: flex;
            justify-content: center;
            gap: 0 20px;
            align-items: center;
        }

        .middle-container img {
            width: 400px;
            height: auto;
            border: 1px solid rgba(255, 255, 255, 0.1);    
            border-radius: 20px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            margin-bottom: 40px;    
        }     
            
        .login-card {
            width: 400px;
            padding: 48px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            backdrop-filter: blur(12px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            margin-bottom: 40px;
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

        /* --- MODAL BASE --- */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(7, 9, 15, 0.88);
            backdrop-filter: blur(8px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s;
        }

        .modal-overlay.open {
            opacity: 1;
            pointer-events: all;
        }

        .modal {
            background: #111520;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            width: 100%;
            max-width: 700px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
            transform: scale(0.96) translateY(16px);
            transition: transform 0.3s cubic-bezier(0.34, 1.2, 0.64, 1);
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }

        .modal-overlay.open .modal {
            transform: scale(1) translateY(0);
        }

        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .modal-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #5eead4;
            flex: 1;
        }

        .modal-close {
            background: none;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            color: rgba(255, 255, 255, 0.5);
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-close:hover {
            background: rgba(255, 107, 107, 0.1);
            border-color: #ff6b6b;
            color: #ff6b6b;
        }

        .modal-body {
            overflow-y: auto;
            flex: 1;
            padding: 24px;
            font-family: 'Syne', sans-serif;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.6;
        }

        .modal-body h3 {
            color: #fff;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }

        .modal-body h3:first-child {
            margin-top: 0;
        }

        .modal-body p {
            margin-bottom: 16px;
            font-size: 0.9rem;
        }

        .code-output {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
        }

        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }
    </style>
</head>
<body>

    <div class="hero-section">
        <h1>NextGen Web Agency<br><small>Framework</small><span class="mode-label <?php echo $mode_class; ?>"><?php echo $app_mode; ?></span></h1>
        <p>Empower your workflow with next-generation generative artificial intelligence.</p>
        <div class="middle-container">        
        <div class="login-card">
            <h2>Access the Suite</h2>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="error-msg">
                    <?php
                        switch($_GET['error']) {
                            case '1': echo "Invalid credentials. Please try again."; break;
                            case '2': echo "System error: User archive not found."; break;
                            case '3': echo "Please fill in all fields."; break;
                            case '5': echo "This workspace has been suspended. Please contact support."; break;
                            default: echo "An error occurred during login.";
                        }
                    ?>
                </div>
            <?php endif; ?>

            <?php $tenantParam = isset($_GET['tenant']) ? htmlspecialchars($_GET['tenant']) : ''; ?>
            <?php if ($tenantParam): ?>
                <input type="hidden" name="tenant" value="<?php echo $tenantParam; ?>">
                <div style="text-align: center; margin-bottom: 16px; font-size: 0.72rem; color: rgba(255,255,255,0.4); background: rgba(20,184,166,0.08); border: 1px solid rgba(20,184,166,0.15); border-radius: 8px; padding: 6px 12px;">
                    Tenant: <strong style="color: #5eead4;"><?php echo $tenantParam; ?></strong>
                </div>
            <?php endif; ?>
            <form action="login.php" method="POST">
                <?php if ($tenantParam): ?>
                    <input type="hidden" name="tenant" value="<?php echo $tenantParam; ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-login">Enter the Future</button>
            </form>
         </div>       
            <img src="NextGen_WAF.jpg?v=<?php echo $version; ?>"> 
         </div>
    </div>

    <footer>
        <div class="footer-content">
            <div class="footer-links">
                <a href="javascript:void(0)" onclick="openModal('tos-modal')">Terms of Service</a>
                <a href="javascript:void(0)" onclick="openModal('pp-modal')">Privacy Policy</a>
                <a href="javascript:void(0)" onclick="openModal('cp-modal')">Cookie Policy</a>
            </div>
            <div class="footer-details">
                &copy; <?php echo date('Y'); ?> <strong>Vivacity Design</strong>. All rights reserved.<br>
                The intellectual property of this framework and all the code that constitutes it remains the exclusive property of Vivacity Design.<br>
                <strong>Data Controller:</strong> Alessandro Demontis | <strong>Contact:</strong> info@vivacitydesign.net<br>
                The framework uses technical sessions and cookies to manage authentication and security, in compliance with GDPR.
            </div>
        </div>
    </footer>

    <!-- Terms of Service Modal -->
    <div class="modal-overlay" id="tos-modal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Terms of Service</div>
                <button class="modal-close" onclick="closeModal('tos-modal')">✕</button>
            </div>
            <div class="modal-body">
                <h3>1. Acceptance of Terms</h3>
                <p>By accessing the NextGen Web Agency Framework, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use the framework.</p>
                
                <h3>2. Intellectual Property</h3>
                <p>The framework, including its architecture, tools, and generated code patterns, is the exclusive property of Vivacity Design. Users are granted a limited license to utilize the tools for their professional web development projects.</p>
                
                <h3>3. AI Usage and Responsibility</h3>
                <p>This suite leverages OpenAI's GPT models. Users are responsible for the inputs they provide and the generated outputs. Vivacity Design is not responsible for any inaccuracies or issues caused by the AI-generated code.</p>
                
                <h3>4. Usage Restrictions</h3>
                <p>Users may not attempt to reverse engineer, decompile, or disassemble the core framework files. Unauthorized distribution of the framework is strictly prohibited.</p>
                
                <h3>5. Limitation of Liability</h3>
                <p>Vivacity Design provides this framework "as is" without any warranties. We shall not be liable for any direct, indirect, or incidental damages resulting from the use or inability to use the suite.</p>
            </div>
        </div>
    </div>

    <!-- Privacy Policy Modal -->
    <div class="modal-overlay" id="pp-modal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Privacy Policy</div>
                <button class="modal-close" onclick="closeModal('pp-modal')">✕</button>
            </div>
            <div class="modal-body">
                <h3>1. Data Governance</h3>
                <p>NextGen Web Agency Framework is designed with privacy in mind. We collect minimal information necessary for the operation of the tools.</p>
                
                <h3>2. Local Storage vs. Server Storage</h3>
                <p>All sensitive information, including API keys and user preferences, is stored locally in your browser's localStorage. This data is never transmitted to Vivacity Design servers.</p>
                
                <h3>3. Third-Party AI Services</h3>
                <p>When using AI-powered tools, your prompts are sent to OpenAI for processing. We recommend not sharing highly sensitive or personal information within the prompts.</p>
                
                <h3>4. No Tracking</h3>
                <p>We do not use tracking pixels, analytics scripts, or any other form of user behavior monitoring. Your workflow remains private.</p>
            </div>
        </div>
    </div>

    <!-- Cookie Policy Modal -->
    <div class="modal-overlay" id="cp-modal">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-title">Cookie Policy</div>
                <button class="modal-close" onclick="closeModal('cp-modal')">✕</button>
            </div>
            <div class="modal-body">
                <h3>1. Technical Cookies</h3>
                <p>This framework uses essential technical cookies to manage user sessions and authentication. These cookies are necessary for the secure operation of the platform and do not require prior consent.</p>
                
                <h3>2. Local Storage Usage</h3>
                <p>We use browser <strong>LocalStorage</strong> to keep your API keys and project settings persistent. This data stays on your machine and is never sent to our servers.</p>
                
                <h3>3. GDPR Compliance</h3>
                <p>Our use of cookies is limited to what is strictly necessary for the service requested by the user. We do not use any tracking or profiling cookies.</p>
            </div>
        </div>
    </div>

    <script>
        function openModal(id) {
            document.getElementById(id).classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(id) {
            document.getElementById(id).classList.remove('open');
            if (!document.querySelector('.modal-overlay.open')) {
                document.body.style.overflow = 'auto';
            }
        }

        // Close on overlay click
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    closeModal(overlay.id);
                }
            });
        });
    </script>
</body>
</html>
