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
    <title>Email Marketing — Vivacity Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@300;400;500;700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .upload-section {
            background: rgba(20, 184, 166, 0.05);
            border: 2px dashed rgba(20, 184, 166, 0.3);
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            margin-bottom: 25px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .upload-section:hover {
            background: rgba(20, 184, 166, 0.1);
            border-color: var(--cyan);
        }
        .upload-section.dragover {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--green);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }
        .stat-card {
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        .stat-number { font-size: 1.8rem; font-weight: 800; color: var(--cyan); font-family: var(--font-mono); }
        .stat-label { font-size: 0.7rem; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-top: 5px; }
        
        .client-list {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 12px;
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 25px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .client-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
        }
        .client-item:last-child { border-bottom: none; }
        .client-info { display: flex; flex-direction: column; }
        .client-name { font-size: 0.85rem; font-weight: 700; color: #fff; }
        .client-email { font-size: 0.75rem; color: rgba(255,255,255,0.4); }
        
        .email-preview {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px;
            padding: 25px;
            margin-top: 25px;
            font-size: 0.9rem;
        }
        .preview-header {
            border-bottom: 1px solid rgba(255,255,255,0.08);
            padding-bottom: 15px;
            margin-bottom: 15px;
            font-size: 0.8rem;
            color: rgba(255,255,255,0.5);
        }
        .preview-header strong { color: var(--cyan); margin-right: 8px; }
        .preview-body { line-height: 1.6; white-space: pre-wrap; }
        
        .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        .form-group label { font-size: 0.75rem; font-weight: 700; color: rgba(255,255,255,0.5); text-transform: uppercase; }
        input[type="text"], textarea {
            background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px; color: #fff; padding: 12px 15px; font-size: 0.9rem;
            outline: none; transition: border-color 0.2s; font-family: inherit;
        }
        input:focus, textarea:focus { border-color: var(--cyan); }
    </style>
</head>
<body>
    <div class="noise"></div>

    <div class="page">
        <div class="page-header">
            <div class="icon-badge">📧</div>
            <div class="page-header-text">
                <h1>Vivacity <em>Email Marketing</em></h1>
                <p>Send bulk updates and marketing emails to your client base</p>
            </div>
            <div class="header-meta" style="margin-left: auto; display: flex; align-items: center; gap: 12px;">
                <a href="dashboard.html" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </div>

        <hr class="divider">

        <div class="scroll-area">
            <div class="card">
                <div class="section-title">Step 1: Upload Recipients</div>
                <div class="upload-section" id="uploadSection" onclick="document.getElementById('fileInput').click()">
                    <div style="font-size: 3rem; margin-bottom: 10px;">📂</div>
                    <h3 style="color: #fff;">Upload client JSON file</h3>
                    <p style="color: rgba(255,255,255,0.4); margin-top: 5px;">Drag and drop here or click to browse</p>
                    <input type="file" id="fileInput" style="display: none;" accept=".json">
                    <div id="fileInfo" style="display:none; margin-top: 15px; padding: 10px; background: var(--green-dim); border: 1px solid var(--green); border-radius: 8px; color: var(--green); font-size: 0.8rem;"></div>
                </div>

                <div class="stats-grid" id="stats" style="display: none;">
                    <div class="stat-card">
                        <div class="stat-number" id="totalClients">0</div>
                        <div class="stat-label">Total in File</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="validEmails">0</div>
                        <div class="stat-label">With Email</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" id="selectedClients">0</div>
                        <div class="stat-label">Selected</div>
                    </div>
                </div>

                <div class="client-list" id="clientList" style="display: none;">
                    <div class="section-title" style="margin-bottom: 15px;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; text-transform: none;">
                            <input type="checkbox" id="selectAll" style="accent-color: var(--cyan);"> Select all valid recipients
                        </label>
                    </div>
                    <div id="clientItems"></div>
                </div>
            </div>

            <div id="emailForm" class="card" style="display: none;">
                <div class="section-title">Step 2: Compose Message</div>
                
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" placeholder="Enter campaign subject" required>
                </div>

                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" rows="8" placeholder="Write your message here..." required></textarea>
                </div>

                <div class="email-preview" id="emailPreview">
                    <div class="section-title" style="color: var(--amber);">Live Preview</div>
                    <div class="preview-header">
                        <div><strong>From:</strong> NextGen Web Agency &lt;info@nextgen-webagency.com&gt;</div>
                        <div><strong>BCC:</strong> <span id="previewRecipients">No recipients selected</span></div>
                        <div style="margin-top: 5px;"><strong>Subject:</strong> <span id="previewSubject" style="color: #fff;">-</span></div>
                    </div>
                    <div class="preview-body" id="previewMessage" style="color: rgba(255,255,255,0.8);">-</div>
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.08); font-size: 0.8rem; color: rgba(255,255,255,0.4);">
                        <strong style="color: rgba(255,255,255,0.6);">NextGen Web Agency</strong><br>
                        Email: info@nextgen-webagency.com<br>
                        Website: nextgen-webagency.com
                    </div>
                </div>

                <button class="btn btn-primary" id="sendButton" onclick="sendEmails()" style="margin-top: 25px; width: 100%; display: none; height: 50px; font-size: 1rem;">
                    <span>🚀</span> Launch Campaign
                </button>
            </div>

            <div id="statusMessage" style="display: none; margin-top: 20px;"></div>
        </div>
    </div>

    <script>
        let clientsData = [];
        let selectedClients = [];

        document.getElementById('fileInput').addEventListener('change', handleFileSelect);
        
        const uploadSection = document.getElementById('uploadSection');
        uploadSection.addEventListener('dragover', e => { e.preventDefault(); uploadSection.classList.add('dragover'); });
        uploadSection.addEventListener('dragleave', () => uploadSection.classList.remove('dragover'));
        uploadSection.addEventListener('drop', e => {
            e.preventDefault();
            uploadSection.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) processFile(e.dataTransfer.files[0]);
        });

        function handleFileSelect(e) {
            if (e.target.files[0]) processFile(e.target.files[0]);
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

        function displayFileInfo(file, count) {
            const info = document.getElementById('fileInfo');
            info.innerHTML = `<strong>✅ ${file.name}</strong> — ${count} clients detected`;
            info.style.display = 'block';
        }

        function displayClients() {
            const container = document.getElementById('clientItems');
            const valid = clientsData.filter(c => c.email && c.email.trim() !== '');
            
            container.innerHTML = valid.map(c => `
                <div class="client-item">
                    <input type="checkbox" class="client-checkbox" style="accent-color: var(--cyan);" data-client-id="${c.id}" onchange="updateSelection()">
                    <div class="client-info">
                        <div class="client-name">${c.nome || c.name || c.nominativo}</div>
                        <div class="client-email">${c.email}</div>
                    </div>
                </div>
            `).join('');

            document.getElementById('clientList').style.display = 'block';
            document.getElementById('stats').style.display = 'grid';
            document.getElementById('emailForm').style.display = 'block';
            
            document.getElementById('selectAll').onclick = function() {
                document.querySelectorAll('.client-checkbox').forEach(cb => cb.checked = this.checked);
                updateSelection();
            };
        }

        function updateSelection() {
            selectedClients = [];
            document.querySelectorAll('.client-checkbox:checked').forEach(cb => {
                const id = cb.getAttribute('data-client-id');
                const client = clientsData.find(c => c.id == id);
                if (client) selectedClients.push(client);
            });

            updateStats();
            updatePreview();
            document.getElementById('sendButton').style.display = selectedClients.length > 0 ? 'flex' : 'none';
        }

        function updateStats() {
            document.getElementById('totalClients').textContent = clientsData.length;
            document.getElementById('validEmails').textContent = clientsData.filter(c => c.email && c.email.trim() !== '').length;
            document.getElementById('selectedClients').textContent = selectedClients.length;
        }

        function updatePreview() {
            const subject = document.getElementById('subject').value || '-';
            const message = document.getElementById('message').value || '...';
            document.getElementById('previewSubject').textContent = subject;
            document.getElementById('previewMessage').textContent = message;
            document.getElementById('previewRecipients').textContent = selectedClients.length > 0 ? `${selectedClients.length} recipients selected` : 'None selected';
        }

        document.getElementById('subject').addEventListener('input', updatePreview);
        document.getElementById('message').addEventListener('input', updatePreview);

        async function sendEmails() {
            const subject = document.getElementById('subject').value.trim();
            const message = document.getElementById('message').value.trim();
            if (!subject || !message || selectedClients.length === 0) return;

            const btn = document.getElementById('sendButton');
            btn.disabled = true;
            btn.innerHTML = '<span>⏳</span> Sending...';

            try {
                const formData = new FormData();
                formData.append('action', 'send_email');
                formData.append('subject', subject);
                formData.append('message', message);
                selectedClients.forEach(c => formData.append('recipients[]', c.email));

                const res = await fetch(window.location.href, { method: 'POST', body: formData });
                const result = await res.json();

                if (result.success) {
                    showStatus(result.message, 'success');
                    document.getElementById('subject').value = '';
                    document.getElementById('message').value = '';
                    updatePreview();
                } else {
                    showStatus(result.message, 'error');
                }
            } catch (error) {
                showStatus('Connection error.', 'error');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>🚀</span> Launch Campaign';
            }
        }

        function showStatus(msg, type) {
            const div = document.getElementById('statusMessage');
            const className = type === 'success' ? 'success-message' : 'error-message';
            div.innerHTML = `<div class="${className}">${msg}</div>`;
            div.style.display = 'block';
            setTimeout(() => div.style.display = 'none', 6000);
        }
    </script>
</body>
</html>
