<?php
function load_company_data() {
    $file = 'setup.json';
    if (!file_exists($file)) {
        return null;
    }
    $content = file_get_contents($file);
    return $content ? json_decode($content, true) : null;
}

function load_clients_data() {
    $file = 'clients.json';
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return $content ? json_decode($content, true) : [];
}

$company_data = load_company_data() ?: [
    'company' => [
        'name' => 'NextGen Web Agency',
        'address' => '123 Tech Lane, Silicon Valley',
        'phone' => '+1 234 567 890',
        'iva' => 'US123456789'
    ]
];

$clients = load_clients_data();

// Customizable default parameters
$default_params = [
    ['label'=>'Work Hours', 'key'=>'work_hours', 'unit'=>'h', 'unit_price' => 35],
    ['label'=>'Materials and Supplies', 'key'=>'materials', 'unit'=>'€', 'unit_price' => null],
    ['label'=>'Travel', 'key'=>'travel', 'unit'=>'km', 'unit_price' => 0.5],
    ['label'=>'Consulting', 'key'=>'consulting', 'unit'=>'h', 'unit_price' => 50],
    ['label'=>'Installation/Configuration', 'key'=>'installation', 'unit'=>'h', 'unit_price' => 40]
];

// Prices by job type (main hourly cost)
$job_prices = [
    'landing page' => 40,
    'website' => 35,
    'basic chatbot' => 45,
    'advanced chatbot' => 65,
    'third-party chatbot' => 55,
    'desktop app' => 60,
    'mail campaign' => 35,
    'consulting' => 50,
    'maintenance' => 40,
    'other' => 35
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vivacity NextGen Quotes Generator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@300;400;500;700&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-group label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: rgba(255,255,255,0.5);
            letter-spacing: 0.5px;
        }
        input[type="number"], select {
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #fff;
            padding: 10px 13px;
            font-size: 0.86rem;
            outline: none;
            transition: border-color 0.2s;
            font-family: inherit;
        }
        input:focus, select:focus {
            border-color: var(--cyan);
        }
        select option {
            background: #111520;
            color: #fff;
        }
        .param-box {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 10px;
            padding: 15px;
        }
        .result-section {
            border-left: 3px solid var(--green);
            background: rgba(16, 185, 129, 0.05);
        }
        .iva-section {
            background: rgba(245, 158, 11, 0.05);
            border: 1px solid rgba(245, 158, 11, 0.1);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }
        .total-section {
            background: var(--cyan-dim);
            border: 1px solid var(--cyan-glow);
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
        }
        .total-section #finalTotal {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--cyan);
            font-family: var(--font-mono);
        }
        #quoteDetails ul {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        #quoteDetails li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            font-size: 0.85rem;
            display: flex;
            justify-content: space-between;
        }
        #quoteDetails li strong { color: var(--cyan); }
    </style>
    <script>
        const params = <?=json_encode($default_params)?>;
        const jobPrices = <?=json_encode($job_prices)?>;
        const companyData = <?=json_encode($company_data['company'])?>;
        const clients = <?=json_encode($clients)?>;
    </script>
</head>
<body>
    <div class="noise"></div>

    <div class="page">
        <div class="page-header">
            <div class="icon-badge">💶</div>
            <div class="page-header-text">
                <h1>Vivacity <em>NextGen Quotes Generator</em></h1>
                <p>Create professional PDF quotes for your clients</p>
            </div>
            <div class="header-meta" style="margin-left: auto; display: flex; align-items: center; gap: 12px;">
                <a href="dashboard.html" class="btn btn-secondary">Dashboard</a>
            </div>
        </div>

        <hr class="divider">

        <div class="scroll-area">
            <div class="card">
                <div class="section-title">Enter Quote Parameters</div>
                <form id="quoteForm">
                    <div class="form-grid" style="margin-bottom: 20px;">
                        <div class="form-group">
                            <label for="jobtype">Job Type *</label>
                            <select name="jobtype" id="jobtype" required onchange="updatePricesBasedOnJobType()">
                                <option value="">--Select job type--</option>
                                <option value="landing page">Landing Page (€40/h)</option>
                                <option value="website">Website (€35/h)</option>
                                <option value="basic chatbot">Basic Chatbot (€45/h)</option>
                                <option value="advanced chatbot">Advanced Chatbot (€65/h)</option>
                                <option value="third-party chatbot">Third-party Chatbot (€55/h)</option>
                                <option value="desktop app">Desktop App (€60/h)</option>
                                <option value="mail campaign">Mail Campaign (€35/h)</option>
                                <option value="consulting">Consulting (€50/h)</option>
                                <option value="maintenance">Maintenance (€40/h)</option>
                                <option value="other">Other (€35/h)</option>
                            </select>
                            <p style="font-size: 0.7rem; color: rgba(255,255,255,0.3); margin-top: 5px;">Hourly rate updates automatically based on job type</p>
                        </div>

                        <div class="form-group">
                            <label for="client_id">Client (Optional)</label>
                            <select name="client_id" id="client_id">
                                <option value="">--Select a client--</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= htmlspecialchars($client['id']) ?>"><?= htmlspecialchars($client['nominativo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p style="font-size: 0.7rem; color: rgba(255,255,255,0.3); margin-top: 5px;">Select a client to include their details in the quote</p>
                        </div>
                    </div>

                    <div id="paramsContainer" class="form-grid">
                        <!-- Fields will be inserted dynamically by JavaScript -->
                    </div>

                    <button type="button" class="btn btn-primary" onclick="calculateQuote()" style="margin-top: 10px;">
                        <span>▶</span> Calculate Quote
                    </button>
                </form>
            </div>

            <div id="result" style="display:none;">
                <div class="card result-section">
                    <div class="section-title">Generated Quote</div>
                    <div id="quoteDetails"></div>

                    <div class="iva-section">
                        <div class="section-title" style="color: var(--amber);">VAT and Discounts</div>
                        
                        <div class="form-grid" style="margin-top: 15px;">
                            <div class="form-group">
                                <label for="discountType">Apply discount:</label>
                                <select id="discountType" onchange="updateIvaCalculation()">
                                    <option value="0">No discount (0%)</option>
                                    <option value="10_new">New client (10%)</option>
                                    <option value="15_large">Large commission (15%)</option>
                                    <option value="10_loyalty">Loyalty reward (10%)</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="ivaRate">VAT Rate (%):</label>
                                <select id="ivaRate" onchange="updateIvaCalculation()">
                                    <option value="22">22% (Standard rate)</option>
                                    <option value="10">10% (Reduced rate)</option>
                                    <option value="4">4% (Super reduced rate)</option>
                                    <option value="0">0% (VAT Exempt)</option>
                                </select>
                            </div>
                        </div>

                        <div id="ivaCalculation" style="margin-top: 15px; font-size: 0.85rem; color: rgba(255,255,255,0.6);"></div>
                    </div>

                    <div class="total-section">
                        <div id="finalTotal"></div>
                    </div>

                    <div class="button-group" style="margin-top: 25px;">
                        <button id="downloadPdfBtn" class="btn btn-success"><span>📄</span> Download PDF</button>
                        <button id="saveQuoteBtn" class="btn btn-primary"><span>💾</span> Save Quote</button>
                    </div>
                    <div id="saveStatus" style="margin-top: 15px; font-weight: bold; font-size: 0.85rem;"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/jsPDF.min.js"></script>
    <script>
        // Use local jsPDF if available, or fall back to CDN
        if (typeof jspdf === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
            document.head.appendChild(script);
        }

        // Function to update prices based on job type
        function updatePricesBasedOnJobType() {
            const jobtype = document.getElementById('jobtype').value;
            
            if (jobtype && jobPrices[jobtype]) {
                const hourlyRate = jobPrices[jobtype];
                
                // Update prices in parameters
                params.forEach(param => {
                    if (param.key === 'work_hours') {
                        param.unit_price = hourlyRate;
                    }
                });
                
                // Regenerate fields with new prices
                regenerateParamFields();
            }
        }

        function regenerateParamFields() {
            const container = document.getElementById('paramsContainer');
            if (!container) return;
            
            // Save current values
            const currentValues = {};
            container.querySelectorAll('input').forEach(input => {
                currentValues[input.name] = input.value;
            });
            
            container.innerHTML = '';
            
            params.forEach(p => {
                const div = document.createElement('div');
                div.className = 'form-group param-box';
                
                const unitPriceText = p.unit_price !== null ? ` (€${p.unit_price}/${p.unit})` : '';
                
                div.innerHTML = `
                <label>${p.label}${unitPriceText}</label>
                <input type="number"
                min="0"
                step="1"
                name="${p.key}"
                value="${currentValues[p.key] || '0'}"
                placeholder="Enter ${p.label.toLowerCase()}" />
                `;
                container.appendChild(div);
            });
        }

        // Function to filter and sanitize job type
        function sanitizeJobType(jobtype) {
            if (!jobtype) return '';
            const allowedJobTypes = Object.keys(jobPrices);
            const cleanJobType = jobtype.toString().toLowerCase().trim();
            if (allowedJobTypes.includes(cleanJobType)) {
                return cleanJobType;
            }
            return '';
        }

        document.addEventListener('DOMContentLoaded', () => {
            regenerateParamFields();
        });

        let baseTotal = 0;

        function calculateQuote() {
            const form = document.getElementById('quoteForm');
            if (!form) return;
            
            const formData = new FormData(form);
            const jobtype = sanitizeJobType(formData.get('jobtype'));
            if (!jobtype) {
                alert('Please select a job type.');
                return;
            }
            
            let total = 0;
            let breakdown = [];
            
            params.forEach(p => {
                let val = parseFloat(formData.get(p.key)) || 0;
                if (val < 0) val = 0;
                
                let item_cost = p.unit_price === null ? val : val * p.unit_price;
                
                if (val > 0) {
                    breakdown.push({
                        label: p.label,
                        quantity: val,
                        unit: p.unit,
                        unit_price: p.unit_price,
                        cost: item_cost
                    });
                }
                total += item_cost;
            });
            
            if (breakdown.length === 0) {
                alert('Please enter at least one parameter greater than zero.');
                return;
            }
            
            baseTotal = total;
            const result = document.getElementById('result');
            const quoteDetails = document.getElementById('quoteDetails');
            
            const clientId = formData.get('client_id');
            const selectedClient = clients.find(c => c.id === clientId) || null;
            
            let detailsHtml = `<p style="font-size: 0.9rem; margin-bottom: 15px;">Job type: <strong style="color: #fff; text-transform: capitalize;">${jobtype}</strong></p>`;
            if (selectedClient) {
                detailsHtml += `<p style="font-size: 0.9rem; margin-bottom: 15px;">Client: <strong style="color: #fff;">${selectedClient.nominativo}</strong></p>`;
            }
            detailsHtml += `<div class="section-title">Service Details</div><ul>`;
            breakdown.forEach(item => {
                const quantityDisplay = item.unit_price !== null ? `<span style="color: rgba(255,255,255,0.4);">${item.quantity} ${item.unit} x €${item.unit_price}</span>` : '';
                detailsHtml += `<li>
                    <span><strong>${item.label}</strong></span>
                    <span>${quantityDisplay} <strong style="margin-left: 10px;">€${item.cost.toFixed(2)}</strong></span>
                </li>`;
            });
            detailsHtml += `</ul>`;
            detailsHtml += `<p style="text-align: right; margin-top: 15px; font-weight: 700;">Subtotal (taxable): <span style="color: var(--cyan); margin-left: 10px;">€${total.toFixed(2)}</span></p>`;
            
            quoteDetails.innerHTML = detailsHtml;
            result.style.display = 'block';
            
            updateIvaCalculation();
            
            window.currentQuote = {
                date: new Date().toISOString(),
                jobtype: jobtype,
                subtotal: total.toFixed(2),
                breakdown,
                company: companyData,
                client: selectedClient,
                discountRate: 0,
                discountLabel: '',
                discountAmount: '0.00',
                totalAfterDiscount: total.toFixed(2),
                ivaRate: 22,
                ivaAmount: (total * 0.22).toFixed(2),
                total: (total * 1.22).toFixed(2)
            };

            // Smooth scroll to result
            result.scrollIntoView({ behavior: 'smooth' });
        }

        function updateIvaCalculation() {
            if (baseTotal === 0) return;
            
            const discountSelect = document.getElementById('discountType');
            const discountValue = discountSelect.value;
            
            let discountRate = 0;
            let discountLabel = '';
            
            if (discountValue !== '0') {
                const [rate, type] = discountValue.split('_');
                discountRate = parseFloat(rate);
                
                switch(type) {
                    case 'new': discountLabel = 'New client'; break;
                    case 'large': discountLabel = 'Large commission'; break;
                    case 'loyalty': discountLabel = 'Loyalty reward'; break;
                }
            }
            
            const discountAmount = baseTotal * (discountRate / 100);
            const totalAfterDiscount = baseTotal - discountAmount;
            const ivaRate = parseFloat(document.getElementById('ivaRate').value);
            const ivaAmount = totalAfterDiscount * (ivaRate / 100);
            const finalTotal = totalAfterDiscount + ivaAmount;
            
            const ivaCalcDiv = document.getElementById('ivaCalculation');
            const finalTotalDiv = document.getElementById('finalTotal');
            
            let calculationHtml = `<div style="display: flex; flex-direction: column; gap: 8px;">`;
            calculationHtml += `<div style="display: flex; justify-content: space-between;"><span>Subtotal:</span> <span>€${baseTotal.toFixed(2)}</span></div>`;
            
            if (discountRate > 0) {
                calculationHtml += `<div style="display: flex; justify-content: space-between; color: var(--red);"><span><small>Discount ${discountLabel} (-${discountRate}%):</span> <span>-€${discountAmount.toFixed(2)}</small></span></div>`;
                calculationHtml += `<div style="display: flex; justify-content: space-between; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 8px;"><span>Total after discount:</span> <span>€${totalAfterDiscount.toFixed(2)}</span></div>`;
            }
            
            calculationHtml += `<div style="display: flex; justify-content: space-between;"><span>VAT (${ivaRate}%):</span> <span>€${ivaAmount.toFixed(2)}</span></div>`;
            calculationHtml += `</div>`;
            
            ivaCalcDiv.innerHTML = calculationHtml;
            finalTotalDiv.innerHTML = `QUOTE TOTAL: €${finalTotal.toFixed(2)}`;
            
            if (window.currentQuote) {
                window.currentQuote.discountRate = discountRate;
                window.currentQuote.discountLabel = discountLabel;
                window.currentQuote.discountAmount = discountAmount.toFixed(2);
                window.currentQuote.totalAfterDiscount = totalAfterDiscount.toFixed(2);
                window.currentQuote.ivaRate = ivaRate;
                window.currentQuote.ivaAmount = ivaAmount.toFixed(2);
                window.currentQuote.total = finalTotal.toFixed(2);
            }
        }

        function generatePdf(quote) {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            doc.setFontSize(20);
            doc.text('JOB QUOTE', 105, 20, { align: 'center' });
            
            doc.setFontSize(10);
            doc.text(`Date: ${new Date(quote.date).toLocaleDateString('en-US')}`, 10, 40);
            doc.text(`Job Type: ${quote.jobtype.toUpperCase()}`, 10, 48);
            
            doc.setFontSize(10);
            doc.text('FROM:', 10, 60);
            doc.setFontSize(11);
            doc.text(`${quote.company.name} - ${quote.company.alias} `, 10, 66);
            doc.text(`${quote.company.address}`, 10, 72);
            doc.text(`Phone: ${quote.company.phone}`, 10, 78);
            doc.text(`VAT: ${quote.company.iva}`, 10, 84);
            doc.text(`EMAIL: ${quote.company.email}`, 10, 90);    
            
            if (quote.client) {
                doc.setFontSize(10);
                doc.text('TO:', 110, 60);
                doc.setFontSize(11);
                doc.text(`${quote.client.nominativo}`, 110, 66);
                doc.text(`${quote.client.indirizzo || ''}`, 110, 72);
                doc.text(`${quote.client.email || ''}`, 110, 78);
            }

            doc.line(10, 98, 200, 98);
            doc.setFontSize(12);
            doc.text('SERVICE DETAILS', 10, 110);
            
            doc.setFontSize(10);
            let y = 124;
            quote.breakdown.forEach(item => {
                const quantityText = item.unit_price !== null ? `${item.quantity} ${item.unit} x €${item.unit_price}` : '';
                doc.text(item.label, 12, y);
                doc.text(quantityText, 100, y);
                doc.text(`€${item.cost.toFixed(2)}`, 180, y, { align: 'right' });
                y += 8;
            });
            
            y += 10;
            doc.line(130, y, 200, y);
            y += 10;
            doc.text('Subtotal:', 130, y);
            doc.text(`€${parseFloat(quote.subtotal).toFixed(2)}`, 180, y, { align: 'right' });
            
            if (quote.discountRate > 0) {
                y += 8;
                doc.text(`Discount (${quote.discountRate}%):`, 130, y);
                doc.text(`-€${parseFloat(quote.discountAmount).toFixed(2)}`, 180, y, { align: 'right' });
            }
            
            y += 8;
            doc.text(`VAT (${quote.ivaRate}%):`, 130, y);
            doc.text(`€${parseFloat(quote.ivaAmount).toFixed(2)}`, 180, y, { align: 'right' });
            
            y += 10;
            doc.line(130, y, 200, y);
            y += 10;                
            doc.setFontSize(14);
            doc.text('TOTAL:', 130, y);
            doc.text(`€${parseFloat(quote.total).toFixed(2)}`, 180, y, { align: 'right' });
            
            doc.save(`Quote_${quote.company.name}_${new Date().toISOString().slice(0,10)}.pdf`);
        }

        function saveQuote() {
            if (!window.currentQuote) return;
            
            const btn = document.getElementById('saveQuoteBtn');
            const statusDiv = document.getElementById('saveStatus');
            btn.disabled = true;
            statusDiv.textContent = "⏳ Saving...";
            
            fetch('save_quote.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify(window.currentQuote)
            }).then(res => res.json())
            .then(data => {
                if (data.success) {
                    statusDiv.textContent = "✅ Quote saved successfully!";
                    statusDiv.style.color = 'var(--green)';
                } else {
                    statusDiv.textContent = "❌ Error saving: " + (data.error || 'Unknown error');
                    statusDiv.style.color = 'var(--red)';
                }
            })
            .catch(err => {
                statusDiv.textContent = "❌ Connection error.";
                statusDiv.style.color = 'var(--red)';
            })
            .finally(() => {
                btn.disabled = false;
            });
        }

        document.addEventListener('click', function(event) {
            if(event.target.closest('#downloadPdfBtn')) {
                if (window.currentQuote) generatePdf(window.currentQuote);
            }
            if(event.target.closest('#saveQuoteBtn')) {
                saveQuote();
            }
        });
    </script>
</body>
</html>
