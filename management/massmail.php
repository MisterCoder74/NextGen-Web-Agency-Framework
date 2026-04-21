<?php
// Handle email sending via PHP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_email') {
    header('Content-Type: application/json');
    
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $recipients = $_POST['recipients'] ?? [];
    
    $response = ['success' => false, 'message' => ''];
    
    // Validation
    if (empty($subject)) {
        $response['message'] = 'Subject is required';
        echo json_encode($response);
        exit;
    }
    
    if (empty($message)) {
        $response['message'] = 'Message is required';
        echo json_encode($response);
        exit;
    }
    
    if (empty($recipients) || !is_array($recipients)) {
        $response['message'] = 'Select at least one recipient';
        echo json_encode($response);
        exit;
    }
    
    // Email configuration
    $from_name = "NextGen Web Agency";
    $from_email = "info@nextgen-webagency.com";
    $reply_to = "info@nextgen-webagency.com";
    
    // Prepare message with signature
    $email_body = $message . "\n\n---\n";
    $email_body .= "NextGen Web Agency\n";
    $email_body .= "Email: info@nextgen-webagency.com\n";
    $email_body .= "Website: https://www.nextgen-webagency.com";
    
    // Headers for email
    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/plain; charset=UTF-8";
    $headers[] = "From: {$from_name} <{$from_email}>";
    $headers[] = "Reply-To: {$reply_to}";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    
    // Add all recipients in BCC
    $bcc_list = [];
    foreach ($recipients as $email) {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $bcc_list[] = $email;
        }
    }
    
    if (empty($bcc_list)) {
        $response['message'] = 'No valid email addresses found';
        echo json_encode($response);
        exit;
    }
    
    $headers[] = "Bcc: " . implode(', ', $bcc_list);
    
    // Send email
    $mail_sent = mail(
        $from_email, // TO (send to self)
        $subject,
        $email_body,
        implode("\r\n", $headers)
    );
    
    if ($mail_sent) {
        $response['success'] = true;
        $response['message'] = "Email sent successfully to " . count($bcc_list) . " recipients";
    } else {
        $response['message'] = 'Error while sending email';
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Marketing - NextGen Web Agency</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .upload-section {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .upload-section:hover {
            border-color: #3498db;
            background: #e8f4f8;
        }

        .upload-section.dragover {
            border-color: #2ecc71;
            background: #e8f5e8;
        }

        .upload-input {
            display: none;
        }

        .upload-button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }

        .upload-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.4);
        }

        .file-info {
            margin-top: 20px;
            padding: 15px;
            background: #e8f5e8;
            border-radius: 10px;
            display: none;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1em;
        }

        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1em;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .client-list {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            display: none;
        }

        .client-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .client-checkbox {
            margin-right: 15px;
            transform: scale(1.2);
        }

        .client-info {
            flex: 1;
        }

        .client-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .client-email {
            color: #7f8c8d;
            font-size: 0.9em;
        }

        .send-button {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
            color: white;
            padding: 18px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.2em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
            display: none;
            margin: 20px auto;
        }

        .send-button:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 204, 113, 0.4);
        }

        .send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .loading {
            opacity: 0.7;
            position: relative;
        }

        .loading::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            margin: -10px 0 0 -10px;
            width: 20px;
            height: 20px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .status-message {
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
            font-weight: 600;
            display: none;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            display: none;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #3498db;
        }

        .stat-label {
            color: #7f8c8d;
            margin-top: 5px;
        }

        .email-preview {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            display: none;
        }

        .email-header {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .email-field {
            margin: 5px 0;
            font-size: 0.9em;
        }

        .email-field strong {
            color: #2c3e50;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: white;
            text-decoration: none;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .container {
                margin: 10px;
            }
            
            .content {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📧 Email Marketing</h1>
            <p>Manage your email campaigns with NextGen Web Agency</p>
        </div>

        <div class="content">
            <!-- Upload Section -->
            <div class="upload-section" id="uploadSection">
                <h3>📁 Upload client JSON file</h3>
                <p style="margin: 15px 0; color: #666;">Drag the file here or click to select</p>
                <input type="file" id="fileInput" class="upload-input" accept=".json">
                <button class="upload-button" onclick="document.getElementById('fileInput').click()">
                    Select JSON File
                </button>
                <div class="file-info" id="fileInfo"></div>
            </div>

            <!-- Stats -->
            <div class="stats" id="stats">
                <div class="stat-card">
                    <div class="stat-number" id="totalClients">0</div>
                    <div class="stat-label">Total Clients</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="validEmails">0</div>
                    <div class="stat-label">Valid Emails</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="selectedClients">0</div>
                    <div class="stat-label">Selected</div>
                </div>
            </div>

            <!-- Client List -->
            <div class="client-list" id="clientList">
                <h3>👥 Select recipients</h3>
                <div style="margin: 15px 0;">
                    <label>
                        <input type="checkbox" id="selectAll"> Select all clients with valid email
                    </label>
                </div>
                <div id="clientItems"></div>
            </div>

            <!-- Email Form -->
            <div id="emailForm" style="display: none;">
                <h3>✉️ Compose your email</h3>
                
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" class="form-control" placeholder="Enter email subject" required>
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" class="form-control" placeholder="Write your message..." required></textarea>
                </div>

                <!-- Email Preview -->
                <div class="email-preview" id="emailPreview">
                    <h4>📋 Email Preview</h4>
                    <div class="email-header">
                        <div class="email-field"><strong>From:</strong> NextGen Web Agency &lt;info@nextgen-webagency.com&gt;</div>
                        <div class="email-field"><strong>To:</strong> <span id="previewRecipients">Recipients will be in BCC</span></div>
                        <div class="email-field"><strong>Subject:</strong> <span id="previewSubject">-</span></div>
                    </div>
                    <div id="previewMessage">-</div>
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 0.9em; color: #666;">
                        <strong>NextGen Web Agency</strong><br>
                        Email: info@nextgen-webagency.com<br>
                        Website: <a href="https://www.nextgen-webagency.com" target="_blank">https://www.nextgen-webagency.com</a>
                    </div>
                </div>

                <button class="send-button" id="sendButton" onclick="sendEmails()">
                    🚀 Send Email
                </button>
            </div>

            <div class="status-message" id="statusMessage"></div>
        </div>
    </div>
    <a href="dashboard.html" class="back-link">Back to Dashboard</a>

    <script>
        let clientsData = [];
        let selectedClients = [];

        // File upload handling
        document.getElementById('fileInput').addEventListener('change', handleFileSelect);
        
        // Drag & Drop
        const uploadSection = document.getElementById('uploadSection');
        uploadSection.addEventListener('dragover', handleDragOver);
        uploadSection.addEventListener('drop', handleDrop);
        uploadSection.addEventListener('dragenter', handleDragEnter);
        uploadSection.addEventListener('dragleave', handleDragLeave);

        function handleDragOver(e) {
            e.preventDefault();
        }

        function handleDragEnter(e) {
            e.preventDefault();
            uploadSection.classList.add('dragover');
        }

        function handleDragLeave(e) {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
        }

        function handleDrop(e) {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                processFile(files[0]);
            }
        }

        function handleFileSelect(e) {
            const file = e.target.files[0];
            if (file) {
                processFile(file);
            }
        }

        function processFile(file) {
            if (!file.name.endsWith('.json')) {
                showStatus('Error: File must be in JSON format', 'error');
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    clientsData = JSON.parse(e.target.result);
                    displayFileInfo(file, clientsData.length);
                    displayClients();
                    updateStats();
                } catch (error) {
                    showStatus('Error: Invalid JSON file', 'error');
                }
            };
            reader.readAsText(file);
        }

        function displayFileInfo(file, clientCount) {
            const fileInfo = document.getElementById('fileInfo');
            fileInfo.innerHTML = `
                <strong>✅ File uploaded successfully!</strong><br>
                Name: ${file.name}<br>
                Size: ${(file.size / 1024).toFixed(2)} KB<br>
                Clients found: ${clientCount}
            `;
            fileInfo.style.display = 'block';
        }

        function displayClients() {
            const clientItems = document.getElementById('clientItems');
            const validClients = clientsData.filter(client => client.email && client.email.trim() !== '');
            
            clientItems.innerHTML = validClients.map(client => `
                <div class="client-item">
                    <input type="checkbox" class="client-checkbox" data-client-id="${client.id}" onchange="updateSelection()">
                    <div class="client-info">
                        <div class="client-name">${client.nome || client.name}</div>
                        <div class="client-email">${client.email}</div>
                    </div>
                </div>
            `).join('');

            document.getElementById('clientList').style.display = 'block';
            document.getElementById('stats').style.display = 'grid';
            document.getElementById('emailForm').style.display = 'block';
            document.getElementById('emailPreview').style.display = 'block';
            
            // Setup select all
            document.getElementById('selectAll').addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.client-checkbox');
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateSelection();
            });
        }

        function updateSelection() {
            selectedClients = [];
            const checkboxes = document.querySelectorAll('.client-checkbox:checked');
            
            checkboxes.forEach(cb => {
                const clientId = cb.getAttribute('data-client-id');
                const client = clientsData.find(c => c.id === clientId);
                if (client) {
                    selectedClients.push(client);
                }
            });

            updateStats();
            updatePreview();
            
            const sendButton = document.getElementById('sendButton');
            sendButton.style.display = selectedClients.length > 0 ? 'block' : 'none';
        }

        function updateStats() {
            const totalClients = clientsData.length;
            const validEmails = clientsData.filter(c => c.email && c.email.trim() !== '').length;
            const selected = selectedClients.length;

            document.getElementById('totalClients').textContent = totalClients;
            document.getElementById('validEmails').textContent = validEmails;
            document.getElementById('selectedClients').textContent = selected;
        }

        function updatePreview() {
            const subject = document.getElementById('subject').value || '-';
            const message = document.getElementById('message').value || '-';
            
            document.getElementById('previewSubject').textContent = subject;
            document.getElementById('previewMessage').innerHTML = message.replace(/\n/g, '<br>');
            
            if (selectedClients.length > 0) {
                document.getElementById('previewRecipients').textContent = 
                    `${selectedClients.length} recipients selected (in BCC)`;
            }
        }

        // Preview event listeners
        document.getElementById('subject').addEventListener('input', updatePreview);
        document.getElementById('message').addEventListener('input', updatePreview);

        async function sendEmails() {
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();

            if (!subject || !message) {
                showStatus('Error: Subject and message are required', 'error');
                return;
            }

            if (selectedClients.length === 0) {
                showStatus('Error: Select at least one client', 'error');
                return;
            }

            const sendButton = document.getElementById('sendButton');
            sendButton.disabled = true;
            sendButton.classList.add('loading');
            sendButton.textContent = 'Sending...';

            try {
                // Prepare data for sending
                const formData = new FormData();
                formData.append('action', 'send_email');
                formData.append('subject', subject);
                formData.append('message', message);
                
                // Add all email addresses
                selectedClients.forEach(client => {
                    formData.append('recipients[]', client.email);
                });

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showStatus(result.message, 'success');
                    // Reset form
                    document.getElementById('subject').value = '';
                    document.getElementById('message').value = '';
                    updatePreview();
                } else {
                    showStatus(result.message, 'error');
                }

            } catch (error) {
                showStatus('Connection error. Please try again later.', 'error');
                console.error('Error:', error);
            } finally {
                sendButton.disabled = false;
                sendButton.classList.remove('loading');
                sendButton.textContent = '🚀 Send Email';
            }
        }

        function showStatus(message, type) {
            const statusDiv = document.getElementById('statusMessage');
            statusDiv.textContent = message;
            statusDiv.className = `status-message status-${type}`;
            statusDiv.style.display = 'block';
            
            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 8000);
        }
    </script>
</body>
</html>
