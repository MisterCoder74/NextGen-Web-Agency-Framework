<?php
function load_company_data() {
    $file = 'setup.json';
    if (!file_exists($file)) {
        return null;
    }
    $content = file_get_contents($file);
    return $content ? json_decode($content, true) : null;
}

$company_data = load_company_data() ?: [
    'company' => [
        'name' => 'NextGen Web Agency',
        'address' => '123 Tech Lane, Silicon Valley',
        'phone' => '+1 234 567 890',
        'iva' => 'US123456789'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vivacity NextGen Contracts Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@300;400;500;700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .tabs { display: flex; gap: 8px; margin-bottom: 20px; }
        .tab-btn {
            padding: 9px 18px; border-radius: 10px; cursor: pointer;
            background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.6); font-size: 0.8rem; font-weight: 700;
            transition: all 0.2s; font-family: inherit;
        }
        .tab-btn.active { background: rgba(20,184,166,0.2); border-color: rgba(20,184,166,0.5); color: #5eead4; }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        
        .section { margin-bottom: 25px; padding: 20px; background: rgba(255,255,255,0.03); border-radius: 12px; border: 1px solid rgba(255,255,255,0.06); }
        .section h2 { 
            font-size: 0.75rem; font-weight: 700; letter-spacing: 1px;
            text-transform: uppercase; color: var(--cyan); margin-bottom: 15px; 
            display: flex; align-items: center; gap: 8px;
        }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        label { font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.5); text-transform: uppercase; }
        
        input, select, textarea { 
            background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px; color: #fff; padding: 10px 12px; font-size: 0.85rem;
            outline: none; transition: border-color 0.2s; font-family: inherit;
        }
        input:focus, select:focus, textarea:focus { border-color: var(--cyan); }
        select option { background: #111520; color: #fff; }
        
        .contratto-card { 
            background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.08);
            border-radius: 12px; padding: 18px; margin-bottom: 15px; transition: all 0.2s;
        }
        .contratto-card:hover { border-color: var(--cyan-glow); background: rgba(255,255,255,0.06); }
        
        .empty { text-align: center; padding: 60px; color: rgba(255,255,255,0.3); font-family: var(--font-mono); }
    </style>
</head>
<body>
    <div class="noise"></div>

    <div class="page">
        <div class="page-header">
            <div class="icon-badge">📄</div>
            <div class="page-header-text">
                <h1>Vivacity <em>NextGen Contracts Manager</em></h1>
                <p>Generate and manage freelance work contracts</p>
            </div>
            <div class="header-meta" style="margin-left: auto; display: flex; align-items: center; gap: 12px;">
                <a href="dashboard.html" class="btn btn-secondary">Dashboard</a>
            </div>
        </div>

        <hr class="divider">

        <div class="scroll-area">
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('form')">New Contract</button>
                <button class="tab-btn" onclick="switchTab('lista')">Saved Contracts</button>
            </div>

            <div id="alert"></div>

            <div id="tab-form" class="tab-content active">
                <form id="contractForm">
                    <div class="section">
                        <h2><span>👤</span> CLIENT</h2>
                        <div class="form-grid">
                            <div class="form-group"><label>First Name *</label><input type="text" name="c_nome" required></div>
                            <div class="form-group"><label>Last Name *</label><input type="text" name="c_cognome" required></div>
                            <div class="form-group"><label>Birth Date *</label><input type="date" name="c_data" required></div>
                            <div class="form-group"><label>Birth Place *</label><input type="text" name="c_luogo" required></div>
                            <div class="form-group full"><label>Residence *</label><input type="text" name="c_res" required></div>
                            <div class="form-group"><label>Tax ID / Fiscal Code *</label><input type="text" name="c_cf" required></div>
                            <div class="form-group"><label>Phone *</label><input type="tel" name="c_tel" required></div>
                        </div>
                    </div>

                    <div class="section">
                        <h2><span>🏢</span> SERVICE PROVIDER</h2>
                        <div class="form-grid">
                            <div class="form-group"><label>First Name *</label><input type="text" name="p_nome" required></div>
                            <div class="form-group"><label>Last Name *</label><input type="text" name="p_cognome" required></div>
                            <div class="form-group"><label>Birth Date *</label><input type="date" name="p_data" required></div>
                            <div class="form-group"><label>Birth Place *</label><input type="text" name="p_luogo" required></div>
                            <div class="form-group full"><label>Residence *</label><input type="text" name="p_res" required></div>
                            <div class="form-group"><label>Tax ID / Fiscal Code *</label><input type="text" name="p_cf" required></div>
                            <div class="form-group"><label>Phone *</label><input type="tel" name="p_tel" required></div>
                        </div>
                    </div>

                    <div class="section">
                        <h2><span>🛠</span> ACTIVITY / PROJECT</h2>
                        <div class="form-grid">
                            <div class="form-group full"><label>Description *</label><textarea name="a_desc" required></textarea></div>
                            <div class="form-group full"><label>Place of Execution *</label><input type="text" name="a_luogo" required></div>
                            <div class="form-group"><label>Start Date *</label><input type="date" name="a_inizio" required></div>
                            <div class="form-group"><label>End Date *</label><input type="date" name="a_fine" required></div>
                            <div class="form-group"><label>Duration *</label><input type="number" name="a_durata" required></div>
                            <div class="form-group"><label>Unit</label><select name="a_unita"><option>hours</option><option>days</option></select></div>
                            <div class="form-group full"><label>Schedule</label><input type="text" name="a_orari"></div>
                        </div>
                    </div>

                    <div class="section">
                        <h2><span>💰</span> COMPENSATION</h2>
                        <div class="form-grid">
                            <div class="form-group"><label>Amount € *</label><input type="number" name="comp_importo" required max="5000"></div>
                            <div class="form-group"><label>Method</label><select name="comp_mod"><option>bank transfer</option><option>card</option><option>cash</option></select></div>
                            <div class="form-group"><label>Bank</label><input type="text" name="comp_banca"></div>
                            <div class="form-group"><label>IBAN</label><input type="text" name="comp_iban"></div>
                            <div class="form-group"><label>Payment Days</label><input type="number" name="comp_gg" value="30"></div>
                        </div>
                    </div>

                    <div class="section">
                        <h2><span>📝</span> OTHER</h2>
                        <div class="form-grid">
                            <div class="form-group"><label>Notice Period (Days)</label><input type="number" name="o_preav" value="7"></div>
                            <div class="form-group"><label>Contract Date *</label><input type="date" name="o_data" required></div>
                            <div class="form-group full"><label>Contract Location *</label><input type="text" name="o_luogo" required></div>
                        </div>
                    </div>

                    <div class="button-group" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary"><span>💾</span> Save Contract</button>
                        <button type="button" class="btn btn-success" onclick="printContract()"><span>🖨️</span> Print Contract</button>
                        <button type="button" class="btn btn-danger" onclick="resetForm()"><span>🔄</span> Reset</button>
                        <label class="btn btn-secondary" style="cursor:pointer">
                            <span>📂</span> Load JSON
                            <input type="file" id="fileInput" accept=".json" style="display:none" onchange="loadJSON(event)">
                        </label>
                    </div>
                </form>
            </div>

            <div id="tab-list" class="tab-content">
                <div class="button-group" style="margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="switchTab('form')"><span>➕</span> New Contract</button>
                    <button class="btn btn-secondary" onclick="loadContracts()"><span>🔄</span> Refresh List</button>
                </div>
                <div id="lista"></div>
            </div>
        </div>
    </div>

    <script>
        const companyData = <?=json_encode($company_data['company'])?>;
        let curr = null;

        function formatDateTime(ts) {
            const date = new Date(ts);
            const yyyy = date.getFullYear();
            const mm = String(date.getMonth() + 1).padStart(2, '0');
            const dd = String(date.getDate()).padStart(2, '0');
            const hh = String(date.getHours()).padStart(2, '0');
            const min = String(date.getMinutes()).padStart(2, '0');
            return `${yyyy}${mm}${dd}_${hh}${min}`;
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (companyData) {
                const f = document.getElementById('contractForm');
                // Attempt to split name if it's a single string
                if (companyData.name) {
                    const names = companyData.name.split(' ');
                    if (f.elements['p_nome'] && !f.elements['p_nome'].value) f.elements['p_nome'].value = names[0] || '';
                    if (f.elements['p_cognome'] && !f.elements['p_cognome'].value) f.elements['p_cognome'].value = names.slice(1).join(' ') || '';
                }
                if (f.elements['p_res'] && !f.elements['p_res'].value) f.elements['p_res'].value = companyData.address || '';
                if (f.elements['p_tel'] && !f.elements['p_tel'].value) f.elements['p_tel'].value = companyData.phone || '';
                if (f.elements['p_cf'] && !f.elements['p_cf'].value) f.elements['p_cf'].value = companyData.iva || '';
            }
        });

        function switchTab(t) {
            document.querySelectorAll('.tab-btn, .tab-content').forEach(e => e.classList.remove('active'));
            if(t === 'form') {
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
                document.getElementById('tab-form').classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('tab-list').classList.add('active');
                loadContracts();
            }
        }

        function showAlert(msg) {
            const container = document.getElementById('alert');
            container.innerHTML = `<div class="success-message" style="margin-bottom: 15px;">${msg}</div>`;
            setTimeout(() => container.innerHTML = '', 4000);
        }

        document.getElementById('contractForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const fd = new FormData(this);
            const data = {ts: Date.now()};
            fd.forEach((v, k) => data[k] = v);
            
            // Save in localStorage
            localStorage.setItem(`contratto_${data.ts}`, JSON.stringify(data));
            
            // Download JSON
            const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            const lastName = (data.c_cognome || 'Client').replace(/\s+/g, '_');
            const dateTime = formatDateTime(data.ts);
            a.download = `Contract_${lastName}_${dateTime}.json`;
            a.click();
            URL.revokeObjectURL(url);
            
            showAlert('✅ Contract saved and JSON downloaded!');
            curr = data;
        });

        function loadContracts() {
            const c = document.getElementById('lista');
            const keys = Object.keys(localStorage).filter(k => k.startsWith('contratto_'));
            
            if(!keys.length) {
                c.innerHTML = '<div class="empty"><h2>📭 No saved contracts found</h2><p>Contracts you create will appear here.</p></div>';
                return;
            }
            
            const arr = keys.map(k => {
                try {
                    return {key: k, ...JSON.parse(localStorage.getItem(k))};
                } catch { return null; }
            }).filter(x => x).sort((a,b) => b.ts - a.ts);
            
            c.innerHTML = arr.map(x => `
                <div class="contratto-card">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                        <strong style="color: #fff; font-size: 1rem;">${x.c_nome} ${x.c_cognome} ➔ ${x.p_nome} ${x.p_cognome}</strong>
                        <span class="badge" style="background: var(--cyan-dim); color: var(--cyan);">€${x.comp_importo}</span>
                    </div>
                    <p style="font-size: 0.8rem; color: rgba(255,255,255,0.4); margin-bottom: 12px; line-height: 1.4;">${x.a_desc}</p>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.7rem; font-family: var(--font-mono); color: rgba(255,255,255,0.3);">📅 ${new Date(x.ts).toLocaleString('en-US')}</span>
                        <div class="button-group">
                            <button class="btn btn-secondary btn-small" style="font-size: 0.7rem; padding: 5px 10px;" onclick="viewContract('${x.key}')">View</button>
                            <button class="btn btn-danger btn-small" style="font-size: 0.7rem; padding: 5px 10px;" onclick="deleteContract('${x.key}')">Delete</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function viewContract(k) {
            const d = JSON.parse(localStorage.getItem(k));
            curr = d;
            const f = document.getElementById('contractForm');
            Object.keys(d).forEach(key => {
                if(f.elements[key]) f.elements[key].value = d[key];
            });
            switchTab('form');
            showAlert('✅ Contract loaded into form!');
        }

        function deleteContract(k) {
            if(confirm('⚠️ Are you sure you want to delete this contract?')) {
                localStorage.removeItem(k);
                showAlert('✅ Contract deleted!');
                loadContracts();
            }
        }

        function resetForm() {
            if(confirm('Do you want to reset the form?')) {
                document.getElementById('contractForm').reset();
                curr = null;
                showAlert('✅ Form reset!');
            }
        }

        function formatDate(d) {
            return d ? new Date(d).toLocaleDateString('en-US') : '';
        }

        function loadJSON(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = JSON.parse(e.target.result);
                    curr = data;
                    
                    const f = document.getElementById('contractForm');
                    Object.keys(data).forEach(key => {
                        if(f.elements[key]) f.elements[key].value = data[key];
                    });
                    
                    showAlert('✅ JSON file loaded successfully!');
                } catch(error) {
                    alert('❌ Error loading JSON file: ' + error.message);
                }
            };
            reader.readAsText(file);
            event.target.value = '';
        }

        function printContract() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            const f = document.getElementById('contractForm');
            const fd = new FormData(f);
            const d = curr || {};
            fd.forEach((v, k) => d[k] = v);

            // --- Branding & Header ---
            doc.setFillColor(20, 184, 166); // Cyan-ish color
            doc.rect(0, 0, 210, 40, 'F');
            
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(22);
            doc.setFont('helvetica', 'bold');
            doc.text('WORK CONTRACT', 105, 25, { align: 'center' });
            
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(10);
            
            let y = 55;
            
            // --- Two Columns: Client & Provider ---
            // Left Column: FROM (Service Provider)
            doc.setFont('helvetica', 'bold');
            doc.text('SERVICE PROVIDER (FROM):', 15, y);
            doc.setFont('helvetica', 'normal');
            y += 6;
            doc.setFontSize(11);
            doc.setFont('helvetica', 'bold');
            doc.text(`${d.p_nome} ${d.p_cognome}`, 15, y);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            y += 5;
            doc.text(`Residence: ${d.p_res}`, 15, y);
            y += 5;
            doc.text(`Tax ID: ${d.p_cf}`, 15, y);
            y += 5;
            doc.text(`Phone: ${d.p_tel}`, 15, y);
            y += 5;
            doc.text(`Birth: ${d.p_luogo}, ${formatDate(d.p_data)}`, 15, y);

            // Right Column: TO (Client)
            let yRight = 55;
            doc.setFont('helvetica', 'bold');
            doc.text('CLIENT (TO):', 110, yRight);
            doc.setFont('helvetica', 'normal');
            yRight += 6;
            doc.setFontSize(11);
            doc.setFont('helvetica', 'bold');
            doc.text(`${d.c_nome} ${d.c_cognome}`, 110, yRight);
            doc.setFontSize(10);
            doc.setFont('helvetica', 'normal');
            yRight += 5;
            doc.text(`Residence: ${d.c_res}`, 110, yRight);
            yRight += 5;
            doc.text(`Tax ID: ${d.c_cf}`, 110, yRight);
            yRight += 5;
            doc.text(`Phone: ${d.c_tel}`, 110, yRight);
            yRight += 5;
            doc.text(`Birth: ${d.c_luogo}, ${formatDate(d.c_data)}`, 110, yRight);

            y = Math.max(y, yRight) + 15;

            // --- Preamble ---
            doc.setFillColor(240, 240, 240);
            doc.rect(15, y, 180, 8, 'F');
            doc.setFont('helvetica', 'bold');
            doc.text('PREAMBLE', 20, y + 5.5);
            y += 15;
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            const preambleText = [
                "• The client requires a freelance work performance from the service provider;",
                "• The service provider has declared their willingness to carry out the requested activity on an occasional and non-continuous basis;",
                "• The parties have agreed on the terms and conditions of the performance."
            ];
            preambleText.forEach(line => {
                doc.text(line, 15, y);
                y += 5;
            });

            y += 5;
            doc.setFont('helvetica', 'bold');
            doc.text('THE PARTIES AGREE AS FOLLOWS:', 15, y);
            y += 10;

            const checkPageBreak = (needed) => {
                if (y + needed > 275) {
                    doc.addPage();
                    y = 20;
                }
            };

            // Articles
            const articles = [
                { title: "Article 1 – Subject of the contract", content: `Activity Description: ${d.a_desc}\nPlace of Execution: ${d.a_luogo}\nStart Date: ${formatDate(d.a_inizio)}  |  End Date: ${formatDate(d.a_fine)}\nTotal Duration: ${d.a_durata} ${d.a_unita}\nWork Schedule: ${d.a_orari || 'To be agreed'}` },
                { title: "Article 2 – Compensation", content: `The client agrees to pay the service provider a total compensation of € ${d.comp_importo}, which will be paid via ${d.comp_mod}.${d.comp_mod === 'bank transfer' ? `\nBank: ${d.comp_banca} | IBAN: ${d.comp_iban}` : ''}\nPayment will be made within ${d.comp_gg || 30} days from the date of completion of the performance.` },
                { title: "Article 3 – Method of execution", content: "The performance must be carried out by the service provider in full autonomy and without subordination constraints." },
                { title: "Article 4 – Characteristics of the performance", content: "The performance is of an occasional, non-continuous nature, and does not imply the establishment of a subordinate employment relationship." },
                { title: "Article 5 – Compensation Limits", content: "The total compensation must not exceed 5,000 euros per year, as provided by law." },
                { title: "Article 6 – Withholding Tax", content: "The compensation is subject to a 20% withholding tax, as provided by current tax regulations." },
                { title: "Article 7 – Early Termination", content: `Both parties may terminate the contract with ${d.o_preav || 7} days' notice.` },
                { title: "Article 8 – Insurance", content: "No insurance coverage is provided. The service provider assumes responsibility for any damage." },
                { title: "Article 9 – Applicable Law", content: "The contract is governed by Italian law." }
            ];

            articles.forEach(art => {
                checkPageBreak(30);
                doc.setFont('helvetica', 'bold');
                doc.text(art.title.toUpperCase(), 15, y);
                y += 6;
                doc.setFont('helvetica', 'normal');
                const splitContent = doc.splitTextToSize(art.content, 180);
                doc.text(splitContent, 15, y);
                y += (splitContent.length * 5) + 8;
            });

            // --- Signature ---
            checkPageBreak(50);
            y += 10;
            doc.text(`Date: ${formatDate(d.o_data)}`, 15, y);
            y += 5;
            doc.text(`Location: ${d.o_luogo}`, 15, y);
            
            y += 25;
            doc.line(15, y, 90, y);
            doc.line(120, y, 195, y);
            y += 5;
            doc.setFontSize(8);
            doc.text("CLIENT'S SIGNATURE", 52.5, y, { align: 'center' });
            doc.text("SERVICE PROVIDER'S SIGNATURE", 157.5, y, { align: 'center' });

            const lastName = (d.c_cognome || 'Client').replace(/\s+/g, '_');
            const dateTime = formatDateTime(d.ts || Date.now());
            doc.save(`Contract_${lastName}_${dateTime}.pdf`);
        }
    </script>
    <script src="js/jsPDF.min.js"></script>
    <script>
        // Use local jsPDF if available, or fall back to CDN
        if (typeof jspdf === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            document.head.appendChild(script);
        }
    </script>
</body>
</html>
