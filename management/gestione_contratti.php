<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Contratti</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 Gestione Contratti di Prestazione Occasionale</h1>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('form')">Nuovo Contratto</button>
            <button class="tab-btn" onclick="switchTab('lista')">Contratti Salvati</button>
        </div>

        <div id="alert"></div>

        <div id="tab-form" class="tab-content active">
            <form id="contractForm">
                <div class="section">
                    <h2>COMMITTENTE</h2>
                    <div class="form-grid">
                        <div class="form-group"><label>Nome *</label><input type="text" name="c_nome" required></div>
                        <div class="form-group"><label>Cognome *</label><input type="text" name="c_cognome" required></div>
                        <div class="form-group"><label>Data nascita *</label><input type="date" name="c_data" required></div>
                        <div class="form-group"><label>Luogo nascita *</label><input type="text" name="c_luogo" required></div>
                        <div class="form-group full"><label>Residenza *</label><input type="text" name="c_res" required></div>
                        <div class="form-group"><label>Codice fiscale *</label><input type="text" name="c_cf" required></div>
                        <div class="form-group"><label>Telefono *</label><input type="tel" name="c_tel" required></div>
                    </div>
                </div>

                <div class="section">
                    <h2>PRESTATORE</h2>
                    <div class="form-grid">
                        <div class="form-group"><label>Nome *</label><input type="text" name="p_nome" required></div>
                        <div class="form-group"><label>Cognome *</label><input type="text" name="p_cognome" required></div>
                        <div class="form-group"><label>Data nascita *</label><input type="date" name="p_data" required></div>
                        <div class="form-group"><label>Luogo nascita *</label><input type="text" name="p_luogo" required></div>
                        <div class="form-group full"><label>Residenza *</label><input type="text" name="p_res" required></div>
                        <div class="form-group"><label>Codice fiscale *</label><input type="text" name="p_cf" required></div>
                        <div class="form-group"><label>Telefono *</label><input type="tel" name="p_tel" required></div>
                    </div>
                </div>

                <div class="section">
                    <h2>ATTIVITÀ</h2>
                    <div class="form-grid">
                        <div class="form-group full"><label>Descrizione *</label><textarea name="a_desc" required></textarea></div>
                        <div class="form-group full"><label>Luogo esecuzione *</label><input type="text" name="a_luogo" required></div>
                        <div class="form-group"><label>Data inizio *</label><input type="date" name="a_inizio" required></div>
                        <div class="form-group"><label>Data fine *</label><input type="date" name="a_fine" required></div>
                        <div class="form-group"><label>Durata *</label><input type="number" name="a_durata" required></div>
                        <div class="form-group"><label>Unità</label><select name="a_unita"><option>ore</option><option>giorni</option></select></div>
                        <div class="form-group full"><label>Orari</label><input type="text" name="a_orari"></div>
                    </div>
                </div>

                <div class="section">
                    <h2>COMPENSO</h2>
                    <div class="form-grid">
                        <div class="form-group"><label>Importo € *</label><input type="number" name="comp_importo" required max="5000"></div>
                        <div class="form-group"><label>Modalità</label><select name="comp_mod"><option>bonifico bancario</option><option>carta</option><option>contanti</option></select></div>
                        <div class="form-group"><label>Banca</label><input type="text" name="comp_banca"></div>
                        <div class="form-group"><label>IBAN</label><input type="text" name="comp_iban"></div>
                        <div class="form-group"><label>Gg pagamento</label><input type="number" name="comp_gg" value="30"></div>
                    </div>
                </div>

                <div class="section">
                    <h2>ALTRO</h2>
                    <div class="form-grid">
                        <div class="form-group"><label>Gg preavviso</label><input type="number" name="o_preav" value="7"></div>
                        <div class="form-group"><label>Data contratto *</label><input type="date" name="o_data" required></div>
                        <div class="form-group full"><label>Luogo contratto *</label><input type="text" name="o_luogo" required></div>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">💾 Salva</button>
                    <button type="button" class="btn btn-success" onclick="stampa()">🖨️ Stampa</button>
                    <button type="button" class="btn btn-danger" onclick="resetForm()">🔄 Reset</button>
                    <label class="btn btn-primary" style="cursor:pointer">
                        📂 Carica JSON
                        <input type="file" id="fileInput" accept=".json" style="display:none" onchange="caricaJSON(event)">
                    </label>
                </div>
            </form>
        </div>

        <div id="tab-lista" class="tab-content">
            <div class="btn-group">
                <button class="btn btn-primary" onclick="switchTab('form')">➕ Nuovo</button>
                <button class="btn btn-success" onclick="carica()">🔄 Aggiorna</button>
            </div>
            <div id="lista"></div>
        </div>
    </div>

    <script>
        let curr = null;

        function switchTab(t) {
            document.querySelectorAll('.tab-btn, .tab-content').forEach(e => e.classList.remove('active'));
            if(t === 'form') {
                document.querySelector('.tab-btn').classList.add('active');
                document.getElementById('tab-form').classList.add('active');
            } else {
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
                document.getElementById('tab-lista').classList.add('active');
                carica();
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
            
            // Salva in localStorage
            localStorage.setItem(`contratto_${data.ts}`, JSON.stringify(data));
            
            // Download JSON
            const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `contratto_${data.ts}.json`;
            a.click();
            URL.revokeObjectURL(url);
            
            showAlert('✅ Contratto salvato con successo!');
            curr = data;
        });

        function carica() {
            const c = document.getElementById('lista');
            const keys = Object.keys(localStorage).filter(k => k.startsWith('contratto_'));
            
            if(!keys.length) {
                c.innerHTML = '<div class="empty"><h2>📭 Nessun contratto salvato</h2><p>Crea il tuo primo contratto dalla tab "Nuovo Contratto"</p></div>';
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
                    <small><strong>📅</strong> ${new Date(x.ts).toLocaleString('it-IT')} | <strong>💰</strong> €${x.comp_importo}</small>
                    <div style="margin-top:10px">
                        <button class="btn btn-primary" style="padding:8px 15px;font-size:14px" onclick="vis('${x.key}')">👁️ Visualizza</button>
                        <button class="btn btn-danger" style="padding:8px 15px;font-size:14px" onclick="elimina('${x.key}')">🗑️ Elimina</button>
                    </div>
                </div>
            `).join('');
        }

        function vis(k) {
            const d = JSON.parse(localStorage.getItem(k));
            curr = d;
            const f = document.getElementById('contractForm');
            Object.keys(d).forEach(key => {
                if(f.elements[key]) f.elements[key].value = d[key];
            });
            switchTab('form');
            showAlert('✅ Contratto caricato!');
        }

        function elimina(k) {
            if(confirm('⚠️ Sei sicuro di voler eliminare questo contratto?')) {
                localStorage.removeItem(k);
                showAlert('✅ Contratto eliminato!');
                carica();
            }
        }

        function resetForm() {
            if(confirm('Vuoi resettare il form?')) {
                document.getElementById('contractForm').reset();
                curr = null;
                showAlert('✅ Form resettato!');
            }
        }

        function formatDate(d) {
            return d ? new Date(d).toLocaleDateString('it-IT') : '';
        }

        function caricaJSON(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const data = JSON.parse(e.target.result);
                    curr = data;
                    
                    // Popola il form
                    const f = document.getElementById('contractForm');
                    Object.keys(data).forEach(key => {
                        if(f.elements[key]) f.elements[key].value = data[key];
                    });
                    
                    showAlert('✅ File JSON caricato con successo!');
                } catch(error) {
                    alert('❌ Errore nel caricamento del file JSON: ' + error.message);
                }
            };
            reader.readAsText(file);
            
            // Reset input per permettere di caricare lo stesso file più volte
            event.target.value = '';
        }

        function stampa() {
            const f = document.getElementById('contractForm');
            const fd = new FormData(f);
            const d = curr || {};
            fd.forEach((v, k) => d[k] = v);

            const w = window.open('', '_blank');
            w.document.write(`<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Contratto</title><style>body{font-family:Arial;margin:40px;line-height:1.6}h1{text-align:center;text-transform:uppercase}h2{margin-top:20px;border-bottom:2px solid #333;padding-bottom:5px}.field{margin:8px 0}.label{font-weight:bold;min-width:150px;display:inline-block}p{margin:10px 0}.firma{margin-top:60px;display:flex;justify-content:space-between}.firma div{width:45%;text-align:center}.firma-line{border-top:1px solid #000;margin-top:40px;padding-top:10px}@media print{body{margin:20mm}}</style></head><body>
            <h1>Contratto di Prestazione Occasionale</h1>
            <h2>TRA - COMMITTENTE</h2>
            <div class="field"><span class="label">Nome:</span> ${d.c_nome||''}</div>
            <div class="field"><span class="label">Cognome:</span> ${d.c_cognome||''}</div>
            <div class="field"><span class="label">Data di nascita:</span> ${formatDate(d.c_data)}</div>
            <div class="field"><span class="label">Luogo di nascita:</span> ${d.c_luogo||''}</div>
            <div class="field"><span class="label">Residenza:</span> ${d.c_res||''}</div>
            <div class="field"><span class="label">Codice fiscale:</span> ${d.c_cf||''}</div>
            <div class="field"><span class="label">Telefono:</span> ${d.c_tel||''}</div>
            
            <h2>E - PRESTATORE</h2>
            <div class="field"><span class="label">Nome:</span> ${d.p_nome||''}</div>
            <div class="field"><span class="label">Cognome:</span> ${d.p_cognome||''}</div>
            <div class="field"><span class="label">Data di nascita:</span> ${formatDate(d.p_data)}</div>
            <div class="field"><span class="label">Luogo di nascita:</span> ${d.p_luogo||''}</div>
            <div class="field"><span class="label">Residenza:</span> ${d.p_res||''}</div>
            <div class="field"><span class="label">Codice fiscale:</span> ${d.p_cf||''}</div>
            <div class="field"><span class="label">Telefono:</span> ${d.p_tel||''}</div>
            
            <h2>Premesso che</h2>
            <p>• Il committente ha necessità di una prestazione occasionale di lavoro da parte del prestatore;</p>
            <p>• Il prestatore ha dichiarato di voler svolgere l'attività richiesta in modo occasionale e non continuativo;</p>
            <p>• Le parti si sono accordate sui termini e le condizioni della prestazione.</p>
            
            <h2>Si conviene quanto segue:</h2>
            
            <h2>Articolo 1 – Oggetto del contratto</h2>
            <div class="field"><span class="label">Descrizione attività:</span> ${d.a_desc||''}</div>
            <div class="field"><span class="label">Luogo di esecuzione:</span> ${d.a_luogo||''}</div>
            <div class="field"><span class="label">Data di inizio:</span> ${formatDate(d.a_inizio)}</div>
            <div class="field"><span class="label">Data di fine:</span> ${formatDate(d.a_fine)}</div>
            <div class="field"><span class="label">Durata complessiva:</span> ${d.a_durata||''} ${d.a_unita||'ore'}</div>
            <div class="field"><span class="label">Orari di lavoro:</span> ${d.a_orari||'Da concordare'}</div>
            
            <h2>Articolo 2 – Compenso</h2>
            <p>Il committente si impegna a corrispondere al prestatore un compenso totale di € ${d.comp_importo||''}, che verrà pagato tramite ${d.comp_mod||'bonifico bancario'}.</p>
            ${d.comp_mod==='bonifico bancario'?`<div class="field"><span class="label">Banca:</span> ${d.comp_banca||''}</div><div class="field"><span class="label">IBAN:</span> ${d.comp_iban||''}</div>`:''}
            <p>Il pagamento avverrà entro ${d.comp_gg||30} giorni dalla data di completamento della prestazione.</p>
            
            <h2>Articolo 3 – Modalità di esecuzione</h2>
            <p>La prestazione dovrà essere eseguita dal prestatore in piena autonomia e senza vincoli di subordinazione.</p>
            
            <h2>Articolo 6 – Caratteristiche della prestazione</h2>
            <p>La prestazione è di natura occasionale, non continuativa, e non implica l'instaurazione di un rapporto di lavoro subordinato.</p>
            
            <h2>Articolo 7 – Limiti di compenso</h2>
            <p>Il compenso complessivo non deve superare i 5.000 euro annui, come previsto dalla legge.</p>
            
            <h2>Articolo 8 – Ritenuta d'acconto</h2>
            <p>Il compenso è soggetto a ritenuta d'acconto del 20%, come previsto dalla normativa fiscale vigente.</p>
            
            <h2>Articolo 9 – Risoluzione anticipata</h2>
            <p>Entrambe le parti possono risolvere il contratto con un preavviso di ${d.o_preav||7} giorni.</p>
            
            <h2>Articolo 10 – Assicurazione</h2>
            <p>Non sono previste coperture assicurative. Il prestatore si assume la responsabilità di eventuali danni.</p>
            
            <h2>Articolo 11 – Legge applicabile</h2>
            <p>Il contratto è regolato dalla legge italiana.</p>
            
            <h2>Firma delle parti</h2>
            <div class="field"><span class="label">Data:</span> ${formatDate(d.o_data)}</div>
            <div class="field"><span class="label">Luogo:</span> ${d.o_luogo||''}</div>
            
            <div class="firma">
                <div><div class="firma-line">Firma del committente</div></div>
                <div><div class="firma-line">Firma del prestatore</div></div>
            </div>
            </body></html>`);
            w.document.close();
            setTimeout(() => w.print(), 500);
        }
    </script>
</body>
</html>