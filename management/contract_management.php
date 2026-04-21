<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contract Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 15px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 40px; }
        h1 { text-align: center; color: #333; margin-bottom: 30px; }
        .tabs { display: flex; gap: 10px; margin-bottom: 30px; justify-content: center; }
        .tab-btn { padding: 12px 30px; border: none; background: #e0e0e0; cursor: pointer; border-radius: 8px; font-weight: 600; }
        .tab-btn.active { background: #667eea; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .section { margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px; }
        .section h2 { color: #667eea; margin-bottom: 20px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full { grid-column: 1 / -1; }
        label { font-weight: 600; margin-bottom: 5px; color: #555; font-size: 14px; }
        input, select, textarea { padding: 12px; border: 2px solid #ddd; border-radius: 8px; font-size: 14px; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #667eea; }
        textarea { resize: vertical; min-height: 80px; font-family: inherit; }
        .btn-group { display: flex; gap: 15px; justify-content: center; margin-top: 30px; flex-wrap: wrap; }
        .btn { padding: 15px 30px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn-primary { background: #667eea; color: white; }
        .btn-success { background: #51cf66; color: white; }
        .btn-danger { background: #ff6b6b; color: white; }
        .contratto-card { background: white; border: 2px solid #e0e0e0; border-radius: 10px; padding: 20px; margin-bottom: 15px; }
        .contratto-card:hover { border-color: #667eea; }
        .alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d3f9d8; color: #2b8a3e; }
        .empty { text-align: center; padding: 60px; color: #999; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: white; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 Freelance Work Contract Management</h1>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('form')">New Contract</button>
            <button class="tab-btn" onclick="switchTab('lista')">Saved Contracts</button>
        </div>

        <div id="alert"></div>

        <div id="tab-form" class="tab-content active">
            <form id="contractForm">
                <div class="section">
                    <h2>CLIENT</h2>
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
                    <h2>SERVICE PROVIDER</h2>
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
                    <h2>ACTIVITY / PROJECT</h2>
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
                    <h2>COMPENSATION</h2>
                    <div class="form-grid">
                        <div class="form-group"><label>Amount € *</label><input type="number" name="comp_importo" required max="5000"></div>
                        <div class="form-group"><label>Method</label><select name="comp_mod"><option>bank transfer</option><option>card</option><option>cash</option></select></div>
                        <div class="form-group"><label>Bank</label><input type="text" name="comp_banca"></div>
                        <div class="form-group"><label>IBAN</label><input type="text" name="comp_iban"></div>
                        <div class="form-group"><label>Payment Days</label><input type="number" name="comp_gg" value="30"></div>
                    </div>
                </div>

                <div class="section">
                    <h2>OTHER</h2>
                    <div class="form-grid">
                        <div class="form-group"><label>Notice Period (Days)</label><input type="number" name="o_preav" value="7"></div>
                        <div class="form-group"><label>Contract Date *</label><input type="date" name="o_data" required></div>
                        <div class="form-group full"><label>Contract Location *</label><input type="text" name="o_luogo" required></div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">💾 Save</button>
                    <button type="button" class="btn btn-success" onclick="printContract()">🖨️ Print</button>
                    <button type="button" class="btn btn-danger" onclick="resetForm()">🔄 Reset</button>
                    <label class="btn btn-primary" style="cursor:pointer">
                        📂 Load JSON
                        <input type="file" id="fileInput" accept=".json" style="display:none" onchange="loadJSON(event)">
                    </label>
                </div>
            </form>
        </div>

        <div id="tab-list" class="tab-content">
            <div class="btn-group">
                <button class="btn btn-primary" onclick="switchTab('form')">➕ New</button>
                <button class="btn btn-success" onclick="loadContracts()">🔄 Refresh</button>
            </div>
            <div id="lista"></div>
        </div>
    </div>
    <a href="dashboard.html" class="back-link">Back to Dashboard</a>

    <script>
        let curr = null;

        function switchTab(t) {
            document.querySelectorAll('.tab-btn, .tab-content').forEach(e => e.classList.remove('active'));
            if(t === 'form') {
                document.querySelector('.tab-btn').classList.add('active');
                document.getElementById('tab-form').classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('tab-list').classList.add('active');
                loadContracts();
            }
        }

        function showAlert(msg) {
            document.getElementById('alert').innerHTML = `<div class="alert alert-success">${msg}</div>`;
            setTimeout(() => document.getElementById('alert').innerHTML = '', 3000);
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
            a.download = `contract_${data.ts}.json`;
            a.click();
            URL.revokeObjectURL(url);
            
            showAlert('✅ Contract saved successfully!');
            curr = data;
        });

        function loadContracts() {
            const c = document.getElementById('lista');
            const keys = Object.keys(localStorage).filter(k => k.startsWith('contratto_'));
            
            if(!keys.length) {
                c.innerHTML = '<div class="empty"><h2>📭 No saved contracts</h2><p>Create your first contract from the "New Contract" tab</p></div>';
                return;
            }
            
            const arr = keys.map(k => {
                try {
                    return {key: k, ...JSON.parse(localStorage.getItem(k))};
                } catch { return null; }
            }).filter(x => x).sort((a,b) => b.ts - a.ts);
            
            c.innerHTML = arr.map(x => `
                <div class="contratto-card">
                    <strong>${x.c_nome} ${x.c_cognome} → ${x.p_nome} ${x.p_cognome}</strong><br>
                    <small style="color:#666">${x.a_desc}</small><br>
                    <small><strong>📅</strong> ${new Date(x.ts).toLocaleString('en-US')} | <strong>💰</strong> €${x.comp_importo}</small>
                    <div style="margin-top:10px">
                        <button class="btn btn-primary" style="padding:8px 15px;font-size:14px" onclick="viewContract('${x.key}')">👁️ View</button>
                        <button class="btn btn-danger" style="padding:8px 15px;font-size:14px" onclick="deleteContract('${x.key}')">🗑️ Delete</button>
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
            showAlert('✅ Contract loaded!');
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
                    
                    // Populate form
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
            
            // Reset input to allow loading the same file multiple times
            event.target.value = '';
        }

        function printContract() {
            const f = document.getElementById('contractForm');
            const fd = new FormData(f);
            const d = curr || {};
            fd.forEach((v, k) => d[k] = v);

            const w = window.open('', '_blank');
            w.document.write(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Contract</title><style>body{font-family:Arial;margin:40px;line-height:1.6}h1{text-align:center;text-transform:uppercase}h2{margin-top:20px;border-bottom:2px solid #333;padding-bottom:5px}.field{margin:8px 0}.label{font-weight:bold;min-width:150px;display:inline-block}p{margin:10px 0}.firma{margin-top:60px;display:flex;justify-content:space-between}.firma div{width:45%;text-align:center}.firma-line{border-top:1px solid #000;margin-top:40px;padding-top:10px}@media print{body{margin:20mm}}</style></head><body>
            <h1>Freelance Work Contract</h1>
            <h2>BETWEEN - CLIENT</h2>
            <div class="field"><span class="label">First Name:</span> ${d.c_nome||''}</div>
            <div class="field"><span class="label">Last Name:</span> ${d.c_cognome||''}</div>
            <div class="field"><span class="label">Date of Birth:</span> ${formatDate(d.c_data)}</div>
            <div class="field"><span class="label">Place of Birth:</span> ${d.c_luogo||''}</div>
            <div class="field"><span class="label">Residence:</span> ${d.c_res||''}</div>
            <div class="field"><span class="label">Tax ID:</span> ${d.c_cf||''}</div>
            <div class="field"><span class="label">Phone:</span> ${d.c_tel||''}</div>
            
            <h2>AND - SERVICE PROVIDER</h2>
            <div class="field"><span class="label">First Name:</span> ${d.p_nome||''}</div>
            <div class="field"><span class="label">Last Name:</span> ${d.p_cognome||''}</div>
            <div class="field"><span class="label">Date of Birth:</span> ${formatDate(d.p_data)}</div>
            <div class="field"><span class="label">Place of Birth:</span> ${d.p_luogo||''}</div>
            <div class="field"><span class="label">Residence:</span> ${d.p_res||''}</div>
            <div class="field"><span class="label">Tax ID:</span> ${d.p_cf||''}</div>
            <div class="field"><span class="label">Phone:</span> ${d.p_tel||''}</div>
            
            <h2>Preamble</h2>
            <p>• The client requires a freelance work performance from the service provider;</p>
            <p>• The service provider has declared their willingness to carry out the requested activity on an occasional and non-continuous basis;</p>
            <p>• The parties have agreed on the terms and conditions of the performance.</p>
            
            <h2>The parties agree as follows:</h2>
            
            <h2>Article 1 – Subject of the contract</h2>
            <div class="field"><span class="label">Activity Description:</span> ${d.a_desc||''}</div>
            <div class="field"><span class="label">Place of Execution:</span> ${d.a_luogo||''}</div>
            <div class="field"><span class="label">Start Date:</span> ${formatDate(d.a_inizio)}</div>
            <div class="field"><span class="label">End Date:</span> ${formatDate(d.a_fine)}</div>
            <div class="field"><span class="label">Total Duration:</span> ${d.a_durata||''} ${d.a_unita||'hours'}</div>
            <div class="field"><span class="label">Work Schedule:</span> ${d.a_orari||'To be agreed'}</div>
            
            <h2>Article 2 – Compensation</h2>
            <p>The client agrees to pay the service provider a total compensation of € ${d.comp_importo||''}, which will be paid via ${d.comp_mod||'bank transfer'}.</p>
            ${d.comp_mod==='bank transfer'?`<div class="field"><span class="label">Bank:</span> ${d.comp_banca||''}</div><div class="field"><span class="label">IBAN:</span> ${d.comp_iban||''}</div>`:''}
            <p>Payment will be made within ${d.comp_gg||30} days from the date of completion of the performance.</p>
            
            <h2>Article 3 – Method of execution</h2>
            <p>The performance must be carried out by the service provider in full autonomy and without subordination constraints.</p>
            
            <h2>Article 4 – Characteristics of the performance</h2>
            <p>The performance is of an occasional, non-continuous nature, and does not imply the establishment of a subordinate employment relationship.</p>
            
            <h2>Article 5 – Compensation Limits</h2>
            <p>The total compensation must not exceed 5,000 euros per year, as provided by law.</p>
            
            <h2>Article 6 – Withholding Tax</h2>
            <p>The compensation is subject to a 20% withholding tax, as provided by current tax regulations.</p>
            
            <h2>Article 7 – Early Termination</h2>
            <p>Both parties may terminate the contract with ${d.o_preav||7} days' notice.</p>
            
            <h2>Article 8 – Insurance</h2>
            <p>No insurance coverage is provided. The service provider assumes responsibility for any damage.</p>
            
            <h2>Article 9 – Applicable Law</h2>
            <p>The contract is governed by Italian law.</p>
            
            <h2>Signature of the parties</h2>
            <div class="field"><span class="label">Date:</span> ${formatDate(d.o_data)}</div>
            <div class="field"><span class="label">Location:</span> ${d.o_luogo||''}</div>
            
            <div class="firma">
                <div><div class="firma-line">Client's Signature</div></div>
                <div><div class="firma-line">Service Provider's Signature</div></div>
            </div>
            </body></html>`);
            w.document.close();
            setTimeout(() => w.print(), 500);
        }
    </script>
</body>
</html>
