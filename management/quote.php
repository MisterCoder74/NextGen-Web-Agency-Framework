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
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Quote Generator</title>
<link rel="stylesheet" href="css/style.css" />
<style>
.param-box {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    padding: 15px;
    margin-bottom: 15px;
}
.param-box label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #495057;
}
.param-box input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}
.param-box input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0,123,255,0.3);
}
.calc-btn {
    background-color: #007bff;
    color: white;
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    margin-top: 20px;
}
.calc-btn:hover {
    background-color: #0056b3;
}
.result-section {
    background-color: #e8f5e8;
    border: 1px solid #28a745;
    border-radius: 5px;
    padding: 20px;
    margin-top: 20px;
}
.action-buttons {
    margin-top: 15px;
}
.action-buttons button {
    background-color: #28a745;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    margin-right: 10px;
    font-size: 14px;
}
.action-buttons button:hover {
    background-color: #1e7e34;
}
.iva-section {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 5px;
    padding: 15px;
    margin-top: 15px;
}
.total-section {
    background-color: #d1ecf1;
    border: 1px solid #bee5eb;
    border-radius: 5px;
    padding: 15px;
    margin-top: 10px;
    font-size: 18px;
    font-weight: bold;
}
</style>

<script>
const params = <?=json_encode($default_params)?>;
const jobPrices = <?=json_encode($job_prices)?>;
const companyData = <?=json_encode($company_data['company'])?>;

</script>

</head>
<body>
<div class="container">
<h1>Quote Generator</h1>

<nav style="margin-bottom: 30px;">
<a href="dashboard.html">Admin Dashboard</a>
</nav>

<form id="quoteForm">
<h3>Enter quote parameters:</h3>

<div class="param-box">
<label for="jobtype">Job Type *</label>
<select name="jobtype" id="jobtype" required onchange="updatePricesBasedOnJobType()" style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
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
<small style="color: #666; font-size: 0.9em;">Hourly rate updates automatically based on job type</small>
</div>

<div id="paramsContainer">
<!-- Fields will be inserted dynamically by JavaScript -->
</div>

<button type="button" class="calc-btn" onclick="calculateQuote()">Calculate Quote</button>
</form>

<div id="result" style="display:none;">
<div class="result-section">
<h2>Generated Quote</h2>
<div id="quoteDetails"></div>

<div class="iva-section">
<h4>VAT and Discounts</h4>

<div style="margin-bottom: 15px;">
<label for="discountType">Apply discount:</label>
<select id="discountType" onchange="updateIvaCalculation()" style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; margin-top: 5px;">
<option value="0">No discount (0%)</option>
<option value="10_new">New client (10%)</option>
<option value="15_large">Large commission (15%)</option>
<option value="10_loyalty">Loyalty reward (10%)</option>
</select>
</div>

<div style="margin-bottom: 15px;">
<label for="ivaRate">VAT Rate (%):</label>
<select id="ivaRate" onchange="updateIvaCalculation()" style="width: 100%; padding: 8px 12px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px; margin-top: 5px;">
<option value="22">22% (Standard rate)</option>
<option value="10">10% (Reduced rate)</option>
<option value="4">4% (Super reduced rate)</option>
<option value="0">0% (VAT Exempt)</option>
</select>
</div>

<div id="ivaCalculation" style="margin-top: 10px;"></div>
</div>

<div class="total-section">
<div id="finalTotal"></div>
</div>

<div class="action-buttons">
<button id="downloadPdfBtn">📄 Download PDF</button>
<button id="saveQuoteBtn">💾 Save Quote</button>
</div>
<div id="saveStatus" style="margin-top: 10px; font-weight: bold;"></div>
</div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
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
        
        // Show notification of price change
        showPriceUpdateNotification(hourlyRate);
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
        div.className = 'param-box';
        
        const unitPriceText = p.unit_price !== null ? ` (€${p.unit_price} per ${p.unit})` : '';
        
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

function showPriceUpdateNotification(hourlyRate) {
    // Remove previous notifications
    const existingNotif = document.getElementById('priceNotification');
    if (existingNotif) {
        existingNotif.remove();
    }
    
    // Create new notification
    const notification = document.createElement('div');
    notification.id = 'priceNotification';
    notification.style.cssText = `
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
    padding: 10px;
    margin: 10px 0;
    border-radius: 4px;
    font-size: 14px;
    `;
    notification.innerHTML = `💰 Hourly rate updated to €${hourlyRate}/h for this job type`;
    
    // Insert after job type field
    const jobtypeField = document.getElementById('jobtype').closest('.param-box');
    jobtypeField.parentNode.insertBefore(notification, jobtypeField.nextSibling);
    
    // Remove after 3 seconds
    setTimeout(() => {
        if (notification && notification.parentNode) {
            notification.remove();
        }
    }, 3000);
}

// Function to filter and sanitize job type
function sanitizeJobType(jobtype) {
    if (!jobtype) return '';
    
    // List of allowed job types
    const allowedJobTypes = [
        'landing page',
        'website',
        'basic chatbot',
        'advanced chatbot',
        'third-party chatbot',
        'desktop app',
        'mail campaign',
        'consulting',
        'maintenance',
        'other'
    ];
    
    // Convert to lowercase and remove extra spaces
    const cleanJobType = jobtype.toString().toLowerCase().trim();
    
    // Check if type is in allowed list
    if (allowedJobTypes.includes(cleanJobType)) {
        return cleanJobType;
    }
    
    return '';
}

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('paramsContainer');
    if (!container) return;
    
    container.innerHTML = '';
    
    params.forEach(p => {
        const div = document.createElement('div');
        div.className = 'param-box';
        
        const unitPriceText = p.unit_price !== null ? ` (€${p.unit_price} per ${p.unit})` : '';
        
        div.innerHTML = `
        <label>${p.label}${unitPriceText}</label>
        <input type="number"
        min="0"
        step="1"
        name="${p.key}"
        value="0"
        placeholder="Enter ${p.label.toLowerCase()}" />
        `;
        container.appendChild(div);
    });
});

let baseTotal = 0;

function calculateQuote() {
    const form = document.getElementById('quoteForm');
    if (!form) return alert("Form not found.");
    
    const formData = new FormData(form);
    
    // Validate and filter job type
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
        
        let item_cost = 0;
        if (p.unit_price === null) {
            item_cost = val;
        } else {
            item_cost = val * p.unit_price;
        }
        
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
    
    let detailsHtml = `<h4>Job type: <em>${jobtype}</em></h4>`;
    detailsHtml += `<h4>Service details:</h4><ul>`;
    breakdown.forEach(item => {
        const unitPriceDisplay = item.unit_price !== null ? `€${item.unit_price}` : '';
        const quantityDisplay = item.unit_price !== null ? `${item.quantity} ${item.unit} x ` : '';
        detailsHtml += `<li><strong>${item.label}</strong>: ${quantityDisplay}${unitPriceDisplay} = €${item.cost.toFixed(2)}</li>`;
    });
    detailsHtml += `</ul>`;
    detailsHtml += `<p><strong>Subtotal (taxable): €${total.toFixed(2)}</strong></p>`;
    
    quoteDetails.innerHTML = detailsHtml;
    result.style.display = 'block';
    
    // Calculate initial VAT
    updateIvaCalculation();
    
    // Save global data for PDF and saving
    window.currentQuote = {
        date: new Date().toISOString(),
        jobtype: jobtype,
        subtotal: total.toFixed(2),
        breakdown,
        company: companyData,
        discountRate: 0,
        discountLabel: '',
        discountAmount: '0.00',
        totalAfterDiscount: total.toFixed(2),
        ivaRate: 22, // Default VAT 22%
        ivaAmount: (total * 0.22).toFixed(2),
        total: (total * 1.22).toFixed(2)
    };
}

function updateIvaCalculation() {
    if (baseTotal === 0) return;
    
    // Calculate discount
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
    
    // Calculate VAT on discounted total
    const ivaRate = parseFloat(document.getElementById('ivaRate').value);
    const ivaAmount = totalAfterDiscount * (ivaRate / 100);
    const finalTotal = totalAfterDiscount + ivaAmount;
    
    const ivaCalcDiv = document.getElementById('ivaCalculation');
    const finalTotalDiv = document.getElementById('finalTotal');
    
    let calculationHtml = `<p><strong>Calculation summary:</strong></p>`;
    calculationHtml += `<p>Subtotal: €${baseTotal.toFixed(2)}</p>`;
    
    if (discountRate > 0) {
        calculationHtml += `<p style="color: #dc3545;">Discount ${discountLabel} (-${discountRate}%): -€${discountAmount.toFixed(2)}</p>`;
        calculationHtml += `<p>Total after discount: €${totalAfterDiscount.toFixed(2)}</p>`;
    }
    
    calculationHtml += `<p>VAT (${ivaRate}%): €${ivaAmount.toFixed(2)}</p>`;
    
    ivaCalcDiv.innerHTML = calculationHtml;
    finalTotalDiv.innerHTML = `QUOTE TOTAL: €${finalTotal.toFixed(2)}`;
    
    // Update current quote with complete data
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
    
    // Header
    doc.setFontSize(20);
    doc.setTextColor(0, 0, 0);
    doc.text('QUOTE', 105, 20, { align: 'center' });
    
    // Company data
    doc.setFontSize(12);
    doc.text(`Date: ${new Date(quote.date).toLocaleDateString('en-US')}`, 10, 40);
    doc.text(`Job Type: ${quote.jobtype || 'Not specified'}`, 10, 48);
    doc.text(`Company: ${quote.company.name}`, 10, 58);
    doc.text(`Address: ${quote.company.address}`, 10, 66);
    doc.text(`Phone: ${quote.company.phone}`, 10, 74);
    doc.text(`VAT: ${quote.company.iva}`, 10, 82);
    
    // Separator line
    doc.line(10, 88, 200, 88);
    
    // Service details
    doc.setFontSize(14);
    doc.text('Service details:', 10, 98);
    
    doc.setFontSize(10);
    let y = 108;
    quote.breakdown.forEach(item => {
        const unitPrice = item.unit_price !== null ? `€${item.unit_price}` : '';
        const quantity = item.unit_price !== null ? `${item.quantity} ${item.unit} x ` : '';
        const line = `${item.label}: ${quantity}${unitPrice} = €${item.cost}`;
        doc.text(line, 12, y);
        y += 6;
    });
    
    // Totals
    y += 10;
    doc.line(10, y, 200, y);
    y += 8;
    
    doc.setFontSize(12);
    doc.text(`Taxable: €${quote.subtotal || '0.00'}`, 10, y);
    y += 8;
    doc.text(`VAT (${quote.ivaRate || 22}%): €${quote.ivaAmount || '0.00'}`, 10, y);
    y += 8;
    
    doc.setFontSize(14);
    doc.setTextColor(0, 100, 0);
    doc.text(`TOTAL: €${quote.total || quote.subtotal || '0.00'}`, 10, y);
    
    doc.save(`Quote_${quote.company.name}_${new Date().toISOString().slice(0,10)}.pdf`);
}

function saveQuote() {
    if (!window.currentQuote) {
        alert("Please calculate the quote first.");
        return;
    }
    
    fetch('save_quote.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(window.currentQuote)
    }).then(res => res.json())
    .then(data => {
        const statusDiv = document.getElementById('saveStatus');
        if (data.success) {
            statusDiv.textContent = "✅ Quote saved successfully!";
            statusDiv.style.color = 'green';
        } else {
            statusDiv.textContent = "❌ Error saving: " + (data.error || 'Unknown error');
            statusDiv.style.color = 'red';
        }
    })
    .catch(err => {
        const statusDiv = document.getElementById('saveStatus');
        statusDiv.textContent = "❌ Connection error.";
        statusDiv.style.color = 'red';
    });
}

// Button events
document.addEventListener('click', function(event) {
    if(event.target.matches('button')) {
        const id = event.target.id;
        
        switch(id) {
            case 'downloadPdfBtn':
                if (!window.currentQuote) {
                    alert("Please calculate the quote first.");
                    return;
                }
                generatePdf(window.currentQuote);
                break;
            case 'saveQuoteBtn':
                saveQuote();
                break;
        }
    }
});

</script>
</body>
</html>
