<?php
require_once 'include/session_config.php';
require_once 'include/dbconnect.php';

requireLoginAndAccess('Quotation.php');

// ✅ MAIN DB
$conn = new mysqli("15.207.37.132", "cloud", "TUCKER_ser_sql", "marketing_new");

// ✅ SECOND DB (already from dbconnect.php → $liveconnect)

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$final_data = [];
$cpo_map = [];

/* =========================
   ✅ STEP 1: GET CPO DATA
========================= */
$sql = "SELECT cpo_id, cpo_name FROM fca_cpo ORDER BY sno ASC";
$cpo_result = $liveconnect->query($sql);

while ($row = $cpo_result->fetch_assoc()) {
    $cpo_map[$row['cpo_id']] = $row['cpo_name']; // 🔥 mapping
}

/* =========================
   ✅ STEP 2: MAIN QUERY
========================= */
$sql = "
    SELECT 
        GROUP_CONCAT(q.id) AS quotation_ids,
        le.id AS lead_id,
        q.cpo_id AS qcpo_id,
        SUM(s.grand_total) AS total_grand,
        SUM(s.subtotal) AS total_sub
    FROM quotations q
    INNER JOIN summary s ON s.quotation_id = q.id
    LEFT JOIN leads le ON q.client_id = le.id
    WHERE q.order_status = 'Y'
    GROUP BY q.client_id
    ORDER BY q.created_at DESC
";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {

    $quotation_ids = $row['quotation_ids'];

    /* =========================
       ✅ STEP 3: PAYMENT QUERY
    ========================= */
    $pay_sql = "
        SELECT 
            SUM(total_amount) AS total_amount, 
            SUM(amount) AS amount 
        FROM payments 
        WHERE related_id IN ($quotation_ids)
    ";

    $pay_result = $conn->query($pay_sql);
    $pay_row = $pay_result->fetch_assoc();

    // ✅ Payment Data
    $row['paid_amount'] = $pay_row['amount'] ?? 0;
    // $row['total_payment'] = $pay_row['total_amount'] ?? 0;

    // ✅ Payment Status
    $row['half_payment'] = (!empty($pay_row['amount']) && $pay_row['amount'] > 0) ? 'Y' : 'N';

    /* =========================
       ✅ STEP 4: MAP CPO NAME
    ========================= */
    $row['cpo_name'] = $cpo_map[$row['qcpo_id']] ?? '-';

    $final_data[] = $row;
}

/* =========================
   ✅ OUTPUT
========================= */
// echo "<pre>";
// print_r($final_data);
// echo "</pre>";
// exit;
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Quotation Management | Tucker Motors</title>

    <!-- Fonts & Icons -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="https://station.cms.tuckermotors.com/asset/images/favicon.ico">
    <style>
        /* =========================================
           1. CSS VARIABLES
        ========================================= */
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338ca;
            --primary-light: #EEF2FF;
            --primary-gradient: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
            --card-bg: #FFFFFF;
            --border-color: #E2E8F0;
            --bg-body: #F3F4F6;
            --text-main: #111827;
            --text-muted: #6B7280;
            --sidebar-width: 250px;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            font-size: 14px;
        }

        /* =========================================
           2. SIDEBAR & MOBILE MENU (OLD STYLE)
        ========================================= */

        /* 1. Ensure Sidebar is below Modal Backdrop (1050) but above content */
        .sidebar {
            z-index: 900 !important;
        }

        /* 2. Desktop Layout */
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 25px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        /* 3. Mobile Header */
        .mobile-header {
            display: none;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .mobile-toggle {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
        }

        /* 4. Responsive Logic */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .mobile-header {
                display: flex;
            }

            h1 {
                display: none;
            }

            /* Mobile Sidebar Logic: Hidden by default (via negative margin or transform in sidebar.php CSS usually, but forcing here) */
            /* Assuming sidebar.php has class 'sidebar' */
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                width: var(--sidebar-width);
                background: white;
                transform: translateX(-100%);
                /* Start hidden */
                transition: transform 0.3s ease;
            }

            /* Class added by JS to show sidebar */
            body.sidebar-open .sidebar {
                transform: translateX(0);
            }

            /* Overlay */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 905;
                /* Above sidebar, below modal */
                backdrop-filter: blur(2px);
            }

            body.sidebar-open .sidebar-overlay {
                display: block;
            }

            /* Prevent scrolling when menu open */
            body.sidebar-open {
                overflow: hidden;
            }
        }

        /* =========================================
           3. UI ELEMENTS & COLORFUL MODAL
        ========================================= */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-box {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background: var(--card-bg);
            outline: none;
        }

        .search-box i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .btn-add {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-add:hover {
            background: var(--primary-hover);
            color: white;
            text-decoration: none;
        }

        /* Card Tables */
        .table-container {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        th {
            background: rgba(0, 0, 0, 0.02);
            color: var(--text-muted);
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            margin-right: 2px;
        }

        .btn-confirm-order {
            background: #28a745;
            /* Green for confirm */
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-confirm-order:disabled {
            background: #6c757d;
            /* Grey when disabled */
            cursor: not-allowed;
        }

        .btn-view {
            background: #10B981;
        }

        .btn-primary {
            background: var(--primary);
        }

        .btn-edit {
            background: #F59E0B;
        }

        .btn-delete {
            background: #EF4444;
        }

        .btn-download {
            background: #6366f1;
        }

        .btn-views {
            background: #e98b11;
        }

        .btn-prod {
            background: #3B82F6;
        }

        /* Mobile Cards */
        @media (max-width: 992px) {
            .table-container {
                border: none;
                background: transparent;
                box-shadow: none;
            }

            thead {
                display: none;
            }

            tbody tr {
                display: block;
                background: var(--card-bg);
                border-radius: 12px;
                margin-bottom: 15px;
                border: 1px solid var(--border-color);
                padding: 15px;
            }

            tbody td {
                display: flex;
                justify-content: space-between;
                text-align: right;
                padding: 8px 0;
                border: none;
                border-bottom: 1px solid #f0f0f0;
            }

            tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--text-muted);
                margin-right: 15px;
            }

            tbody td:last-child {
                border-bottom: none;
                display: flex;
                justify-content: flex-end;
                gap: 5px;
            }
        }

        /* COLORFUL MODAL CSS */
        .modal-header-colorful {
            background: var(--primary-gradient);
            color: white;
            border-radius: 6px 6px 0 0;
            padding: 1.5rem;
        }

        .modal-header-colorful .close {
            color: white;
            opacity: 0.8;
            text-shadow: none;
            font-size: 1.5rem;
        }

        .form-section-title {
            color: var(--primary);
            font-weight: 700;
            margin: 20px 0 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        .form-section-title i {
            background: var(--primary-light);
            padding: 6px;
            border-radius: 4px;
        }

        .form-section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border-color);
        }

        .input-group-text {
            background: #F9FAFB;
            border-color: var(--border-color);
            color: var(--text-muted);
            border-right: none;
        }

        .form-control {
            border-color: var(--border-color);
            color: var(--text-main);
        }

        .input-group .form-control {
            border-left: none;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: none;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
        }

        .preview-card {
            background: #fff;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            position: sticky;
            top: 10px;
        }

        .preview-header {
            background: #f9fafb;
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            font-weight: 700;
            display: flex;
            justify-content: space-between;
        }

        .preview-body {
            padding: 20px;
        }

        .preview-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .preview-value {
            font-weight: 600;
            text-align: right;
        }
    </style>
</head>

<body>

    <!-- Overlay for Mobile Menu -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Include (Ensure it has class 'sidebar') -->
    <?php include 'include/sidebar.php'; ?>

    <!-- Main Content Area -->
    <main class="main-content">

        <!-- Mobile Header (Hamburger) -->
        <div class="mobile-header">
            <button class="mobile-toggle" id="mobileMenuBtn">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h2 style="margin:0; font-size: 18px; font-weight:700;">CPO Bills</h2>
            <div style="width: 32px;"></div>
        </div>

        <h1>CPO Bills</h1>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="search-box">
                <i class="fa-solid fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search Client, ID, Subject...">
            </div>
        </div>

        <!-- Data Table -->
        <div class="table-container">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>CPO ID</th>
                        <th>CPO Name</th>
                        <th>Total Amount</th>
                        <th>Paid Amount</th>
                        <th>Payment Status</th>
                    </tr>
                </thead>
                <tbody id="quotationTableBody">
                    <?php if (!empty($final_data)): $i = 1; ?>
                        <?php foreach ($final_data as $row):
                            if ($row['cpo_name'] != '-') {
                        ?>
                                <tr>
                                    <td>#<?= $i++; ?></td>

                                    <!-- Lead / CPO ID -->
                                    <td><?= $row['qcpo_id'] ?? '-' ?></td>

                                    <!-- CPO Name (if you map it later) -->
                                    <td><?= $row['cpo_name'] ?? '-' ?></td>

                                    <!-- Total Payment -->
                                    <td>
                                        ₹<?= number_format($row['total_grand'] ?? 0, 2) ?>
                                    </td>

                                    <!-- Total Payment -->
                                    <td>
                                        ₹<?= number_format($row['paid_amount'] ?? 0, 2) ?>
                                    </td>

                                    <!-- Payment Status -->
                                    <td>
                                        <?php if ($row['half_payment'] == 'Y'): ?>
                                            <span class="badge badge-success">Paid</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                        <?php }
                        endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">No data found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div id="paginationInfo" style="font-size:14px;color:#6c757d;"></div>

                <ul class="pagination mb-0" id="paginationControls">
                    <!-- JS will generate buttons -->
                </ul>
            </div>
        </div>

        <!-- Orders Section (Initially hidden) -->
        <div id="ordersSection" style="display:none;">
            <!-- Toolbar for Orders if needed -->
            <div class="toolbar">
                <div class="search-box">
                    <i class="fa-solid fa-search"></i>
                    <input type="text" id="orderSearchInput" placeholder="Search Order ID, Client...">
                </div>
            </div>

            <div class="table-container">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th width="80">ID</th>
                            <th>Order No</th>
                            <th>Quotation No</th>
                            <th>Order Date</th>
                            <th>Client</th>
                            <th>Grand Total</th>
                            <th>Payment Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="orderTableBody">
                        <!-- Order data will be loaded here by JavaScript -->
                    </tbody>
                </table>

            </div>
        </div>

    </main>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog modal-md modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Payment Details</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="showFormBtn" style="display:none;">Add Payment</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="showTableBtn">Payment History</button>
                    </div>
                    <div id="payment-form">
                        <form id="paymentForm" enctype="multipart/form-data">
                            <input type="hidden" name="id" id="formId">
                            <!-- NEW Hidden fields for CPO Email logic -->
                            <input type="hidden" name="lead_id" id="hidden_lead_id">
                            <input type="hidden" name="lead_email" id="hidden_lead_email">
                            <div class="form-group mb-3">
                                <label>Mode of Payment</label>
                                <select name="payment_mode" class="form-control" required>
                                    <option value="">Select</option>
                                    <option value="Cash">Cash</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Bank Transfer">Bank Transfer</option>
                                    <option value="Cheque">Cheque</option>
                                </select>
                            </div>
                            <div class="form-group mb-3">
                                <label>Total Payment Amount</label>
                                <input type="number" name="total_payment_amount" class="form-control" required readonly id="total_payment_amount">
                            </div>
                            <div class="form-group mb-3">
                                <label>Collected Amount</label>
                                <input type="number" name="collected_amount" class="form-control" required id="collected_amount" step="0.01">
                                <small class="text-danger mt-1 d-block">Pending Amount: ₹<span id="pending_amount"></span></small>
                            </div>
                            <div class="form-group mb-3">
                                <label>Payment ID / Reference No</label>
                                <input type="text" name="payment_ref" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Upload Proof</label>
                                <input type="file" name="payment_proof" class="form-control-file" accept="image/*,.pdf">
                            </div>
                            <div class="text-right">
                                <button type="submit" class="btn btn-success btn-block">Save Payment</button>
                            </div>
                        </form>
                    </div>

                    <!-- PAYMENT TABLE (default hidden) -->
                    <div id="payment-table" style="display:none;">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="showFormBtnTable" style="float:right"> Add Payment</button>
                        <br><br>

                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Mode</th>
                                    <th>Amount</th>
                                    <th>Reference</th>
                                    <th>Proof</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody id="payment-table-data">

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById("searchInput").addEventListener("keyup", function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll("#quotationTableBody tr");

            rows.forEach(function(row) {
                let text = row.innerText.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });

        function getchargepoints(quo_id) {

            fetch('api/quotation_products.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'charger_details',
                        quotation_id: quo_id
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("HTTP error " + response.status);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log("RAW RESPONSE:", text);
                    const data = JSON.parse(text);

                    if (data.status === true) {
                        showQuotationDetails(data);
                    } else {
                        alert('No charger details found');
                    }
                })
                .catch(error => {
                    console.error("FETCH ERROR:", error);
                    alert('Something went wrong while fetching charger details');
                });
        }

        function showQuotationDetails(data) {

            // Top chargepoints
            let allChargepoints = '';

            if (data.created_chargepoints.length) {
                allChargepoints = data.created_chargepoints.map(cp =>
                    `${cp.chargepoint} 
            <small class="text-muted">
            (${cp.unique_id} | ${cp.board_id})
            </small>`
                ).join('<br>');
            } else {
                allChargepoints = '<em>None</em>';
            }

            let html = `
    <h5 class="mb-3"><strong>Quotation ID:</strong> ${data.quotation_id}</h5>

    <div class="row text-center mb-3">
        <div class="col"><strong>Total Required</strong><br>${data.required_quantity}</div>
        <div class="col"><strong>Already Created</strong><br>${data.already_created}</div>
        <div class="col"><strong>Created Now</strong><br>${data.created_now}</div>
        <div class="col"><strong>Pending</strong><br>${data.pending_quantity}</div>
    </div>

    <div class="mb-3">
        <strong>All Created Chargepoints:</strong><br>
        ${allChargepoints}
    </div>

    <hr>

    <table class="table table-bordered table-sm">
        <thead class="thead-light">
            <tr>
                <th>Product ID</th>
                <th>Required</th>
                <th>Already Created</th>
                <th>Created Now</th>
                <th>Pending</th>
                <th>Status</th>
                <th>Chargepoints</th>
            </tr>
        </thead>
        <tbody>
    `;

            data.products.forEach(p => {

                let badge = 'secondary';
                if (p.status === 'already_completed') badge = 'success';
                else if (p.status === 'pending_no_stock') badge = 'danger';

                // product chargepoints
                let chargepoints = '';

                if (p.created_chargepoints.length) {
                    chargepoints = p.created_chargepoints.map(cp =>
                        `<div>
                    <b>${cp.chargepoint}</b><br>
                    <small>Unique ID: ${cp.unique_id}</small><br>
                    <small>Board ID: ${cp.board_id}</small>
                </div>`
                    ).join('<hr style="margin:5px 0;">');
                } else {
                    chargepoints = '-';
                }

                html += `
        <tr>
            <td>${p.product_id}</td>
            <td>${p.required_quantity}</td>
            <td>${p.already_created}</td>
            <td>${p.created_now}</td>
            <td>${p.pending_quantity}</td>
            <td>
                <span class="badge badge-${badge}">
                    ${p.status.replaceAll('_',' ')}
                </span>
            </td>
            <td>${chargepoints}</td>
        </tr>
        `;
            });

            html += `
        </tbody>
    </table>
    `;

            document.getElementById('quotationDetails').innerHTML = html;
            $('#viewChargerModal').modal('show');
        }

        const quotations = <?php echo json_encode($quotations_data); ?>;

        function viewOrderDetails(orderId) {
            const order = quotations.find(o => o.quotation_id == orderId);

            if (!order) {
                alert('Order not found!');
                return;
            }

            // Fill modal fields
            document.getElementById('vdQuotationNo').innerText = order.quotation_no + ' (' + order.version_code + ')';
            document.getElementById('vdClient').innerText = order.client_name;
            document.getElementById('vdAddress').innerText = order.client_address;
            document.getElementById('vdCPO').innerText = order.cpo_id;
            document.getElementById('vdSubtotal').innerText = '₹' + Number(order.subtotal).toFixed(2);
            document.getElementById('vdGST').innerText = '₹' + Number(order.gst_value).toFixed(2);
            document.getElementById('vdGrandTotal').innerText = '₹' + Number(order.grand_total).toFixed(2);
            document.getElementById('vdPayment').innerText = order.payment_status === 'Y' ? 'Paid' : 'Pending';

            // Show modal
            $('#viewDetailsModal').modal('show');
        }

        // Modified confirmOrder function to create an Order
        function confirmOrder(sno, quotationId, quotationNo) {
            console.log(sno, quotationId, quotationNo)
            if (confirm(`Are you sure you want to confirm Quotation #${quotationNo}? This will create an Order and lock the quotation for editing.`)) {

                fetch('api/quotation_products.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        action: 'update-order',
                        quotation_id: quotationId,
                        quotationNo: quotationNo
                    })
                }).then(res => res.json()).then(data => {
                    window.location.reload();
                });
            }
        }

        // NEW: Revise Quotation Function
        function reviseQuotation(sno, quotationId, quotationNo, version_code, half_payment) {
            if (!confirm(`Are you sure you want to revise Quotation #${quotationNo}? This will create a new version and mark the current one as superseded.`)) {
                return;
            }
            // remove "V" and convert to number
            let num = parseInt(version_code.replace('V', ''));
            // next version
            let nextVersion = 'V' + (num + 1);
            fetch('api/quotation_products.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'version-order',
                    quotation_id: quotationId,
                    quotationNo: quotationNo,
                    version_code: nextVersion
                })
            }).then(res => res.json()).then(data => {
                editQuotation(quotationId, half_payment)

                // window.location.reload();
            });

        }

        document.getElementById('showTableBtn').addEventListener('click', () => {
            document.getElementById('payment-form').style.display = 'none';
            document.getElementById('payment-table').style.display = 'block';
            const id = document.getElementById('formId').value;
            fetchPaymentHistory(id);
        });

        document.getElementById('showFormBtnTable').addEventListener('click', () => {
            document.getElementById('payment-form').style.display = 'block';
            document.getElementById('payment-table').style.display = 'none';
            document.getElementById('showTableBtn').style.display = 'block';
        });

        // Payment History Fetcher
        function fetchPaymentHistory(id) {
            // document.getElementById('paymentForm').reset();
            document.getElementById('showTableBtn').style.display = 'none';
            fetch('api/save-payment.php?id=' + encodeURIComponent(id) + '&action=payment-history')
                .then(res => res.json())
                .then(data => {
                    let html = '';
                    if (data.success && data.payments.length > 0) {
                        data.payments.forEach((row, index) => {
                            html += `<tr>
                                <td>${index + 1}</td>
                                <td>${row.payment_mode}</td>
                                <td>₹${parseFloat(row.amount).toFixed(2)}</td>
                                <td>${row.payment_reference ?? '-'}</td>
                                <td><a href="uploads/payments/${row.proof_file}" target="_blank" class="text-primary">View</a></td>
                                <td>${formatDate(row.created_at)}</td>
                            </tr>`;
                        });
                    } else {
                        html = `<tr><td colspan="6" class="text-center text-muted">No payments found</td></tr>`;
                    }
                    document.getElementById('payment-table-data').innerHTML = html;
                })
                .catch(err => console.error(err));
        }

        // Save Payment
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const id = document.getElementById('formId').value;
            formData.append('action', 'save-payments');

            const pending = parseFloat(document.getElementById('pending_amount').innerText) || 0;
            const collected = parseFloat(document.getElementById('collected_amount').value) || 0;

            if (collected > pending) {
                alert("Collected amount cannot be greater than pending amount.");
                return;
            }

            fetch('api/save-payment.php?id=' + id, {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Payment saved successfully. Now trigger the CPO Email.

                        const leadId = document.getElementById('hidden_lead_id').value;
                        const leadEmail = document.getElementById('hidden_lead_email').value.trim();
                        const quotationId = document.getElementById('formId').value;

                        if (leadId && leadEmail && leadEmail.includes('@')) {
                            // 2. Call send_cpo_email.php. It will generate the token internally.
                            fetch('api/send_cpo_email.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: `email=${encodeURIComponent(leadEmail)}&lead_id=${leadId}&quotation_id=${quotationId}`
                                })
                                .then(r => r.json())
                                .then(emailData => {
                                    // ---- LOGGING TO CONSOLE ----
                                    console.log('--------------------------------');
                                    console.log('CPO EMAIL DEBUG LOG:');
                                    console.log('Sent to Email:', leadEmail);
                                    console.log('API Response:', emailData);
                                    if (emailData.success) {
                                        console.log('Generated Token:', emailData.token);
                                        console.log('Generated Link:', emailData.link);
                                        alert(data.message + ' and CPO email sent successfully!');
                                    } else {
                                        console.warn('CPO email failed:', emailData.message);
                                        alert(data.message + ', but email sending failed: ' + emailData.message);
                                    }
                                    console.log('--------------------------------');
                                    location.reload();
                                })
                                .catch(err => {
                                    console.error('CPO email trigger failed:', err);
                                    alert(data.message + ' (Email trigger failed)');
                                    location.reload();
                                });
                        } else {
                            // No lead email or ID found, just finish
                            alert(data.message || 'Payment recorded successfully');
                            location.reload();
                        }
                    } else {
                        alert(data.message || 'Payment failed');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Network error while saving payment');
                });
        });

        $('#paymentModal').on('hidden.bs.modal', function() {
            // Reset buttons
            document.getElementById('showTableBtn').style.display = 'block';
            document.getElementById('showFormBtn').style.display = 'none';

            // Reset views
            document.getElementById('payment-form').style.display = 'block';
            document.getElementById('payment-table').style.display = 'none';

            // Optional cleanup
            document.getElementById('payment-table-data').innerHTML = '';
            document.getElementById('paymentForm').reset();
        });

        // Open Payment Modal
        function orderConformed(id, grand_total, lead_id, lead_email) {
            $('#paymentModal').modal('show');
            document.getElementById('formId').value = id;
            document.getElementById('total_payment_amount').value = grand_total;
            // Set hidden fields for email trigger
            document.getElementById('hidden_lead_id').value = lead_id;
            document.getElementById('hidden_lead_email').value = lead_email;
            document.getElementById('payment-table-data').innerHTML = '';

            document.getElementById('payment-form').style.display = 'block';
            document.getElementById('payment-table').style.display = 'none';
            document.getElementById('showTableBtn').style.display = 'block';
            document.getElementById('showFormBtn').style.display = 'none';

            fetch('api/save-payment.php?id=' + encodeURIComponent(id) + '&action=pending-payment')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const total = parseFloat(data.total_amount) || 0;
                        const pending = total === 0 ? parseFloat(grand_total) : parseFloat(data.pending_amount) || 0;
                        document.getElementById('pending_amount').innerText = pending.toFixed(2);
                        if (pending <= 0) {
                            document.getElementById('showTableBtn').click();
                            document.getElementById('showFormBtn').style.display = 'none';
                        } else {
                            document.getElementById('showFormBtn').click();
                        }
                    }
                });
        }

        // Edit Quotation (Main Info)


        function editQuotation(id, half_payment) {
            const quotation = quotations.find(q => q.id == id);
            if (!quotation) return;

            const isLocked = half_payment === 'Y';
            document.querySelectorAll("#editForm input, #editForm select, #editForm textarea, #editForm button[type=submit]").forEach(el => el.disabled = isLocked);

            document.getElementById('editId').value = quotation.id;
            document.getElementById('editQuotationNo').value = quotation.quotation_no;
            document.getElementById('editDate').value = quotation.date;
            document.getElementById('editValidTill').value = quotation.valid_till;
            document.getElementById('editClientName').value = quotation.client_name;
            document.getElementById('editClientAddress').value = quotation.client_address || '';
            document.getElementById('editSalutation').value = quotation.salutation || '';
            document.getElementById('editSubject').value = quotation.subject;
            document.getElementById('editIntroduction').value = quotation.introduction || '';
            document.getElementById('editAdditionalNotes').value = quotation.additional_notes || '';

            updateQuotationPreview(quotation);
            setupPreviewUpdates();

            $('#editModal').modal('show');
        }

        function updateQuotationPreview(quotation) {
            document.getElementById('previewClientName').textContent = quotation.client_name || '-';
            document.getElementById('previewDate').textContent = formatDate(quotation.date) || '-';
            document.getElementById('previewValidTill').textContent = formatDate(quotation.valid_till) || '-';
            document.getElementById('quotationDisplayTitle').textContent = `#${quotation.quotation_no}`;
            const statusEl = document.getElementById('quotationStatus');
            if (quotation.order_status === 'Y') {
                statusEl.textContent = 'Confirmed';
                statusEl.className = 'badge badge-success';
            } else {
                statusEl.textContent = 'Active';
                statusEl.className = 'badge badge-info';
            }
        }

        function setupPreviewUpdates() {
            ['editQuotationNo', 'editClientName', 'editDate', 'editValidTill'].forEach(id => {
                document.getElementById(id).addEventListener('input', function() {
                    if (id === 'editQuotationNo') document.getElementById('quotationDisplayTitle').textContent = '#' + this.value;
                    if (id === 'editClientName') document.getElementById('previewClientName').textContent = this.value;
                    if (id === 'editDate') document.getElementById('previewDate').textContent = formatDate(this.value);
                    if (id === 'editValidTill') document.getElementById('previewValidTill').textContent = formatDate(this.value);
                });
            });
        }

        function formatDate(dateString) {
            if (!dateString) return '-';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-GB');
        }

        function previewQuotation() {
            const id = document.getElementById('editId').value;
            window.open(`invoice.php?quotation_id=${id}`, '_blank');
        }

        function deleteQuotation(id) {
            document.getElementById('deleteId').value = id;
            $('#deleteModal').modal('show');
        }

        function downloadQuotation(quoId, orderStatus, quotationNo) {

            if (orderStatus === 'S') {
                alert(`Download PDF for ${quotationNo} is not allowed because this quotation is superseded.`);
                return;
            }

            // continue normal download
            window.location.href = `invoice.php?quotation_id=${quoId}`;
        }

        // Product Logic
        let currentQuotationId = null;
        let currentProducts = [];
        const TotalProducts = JSON.parse('<?= json_encode($products, JSON_UNESCAPED_UNICODE) ?>');

        function editQuotationProducts() {
            const id = document.getElementById('editId').value;
            $('#editModal').modal('hide');
            setTimeout(() => editProducts(id), 500);
        }

        function editProducts(id) {
            currentQuotationId = id;
            const q = quotations.find(x => x.id == id);
            if (q) document.getElementById('quotationTitle').textContent = `#${q.quotation_no}`;
            $('#productsModal').modal('show');
            loadQuotationProducts();
        }

        function loadQuotationProducts() {
            document.getElementById('productsLoading').style.display = 'block';
            document.getElementById('productsList').style.display = 'none';
            document.getElementById('noProducts').style.display = 'none';

            fetch(`api/quotation_products.php?action=fetch&quotation_id=${currentQuotationId}`)
                .then(res => res.json())
                .then(data => {
                    document.getElementById('productsLoading').style.display = 'none';
                    if (data.success && data.products.length > 0) {
                        currentProducts = data.products;
                        displayProducts(data.products);
                        updateSummary(data.summary);
                        document.getElementById('productsList').style.display = 'block';
                    } else {
                        document.getElementById('noProducts').style.display = 'block';
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('productsLoading').style.display = 'none';
                });
        }

        function displayProducts(products) {
            const tbody = document.getElementById('productsTableBody');
            tbody.innerHTML = '';
            products.forEach(p => {
                tbody.innerHTML += `
                    <tr>
                        <td>${p.product_name}</td>
                        <td>₹${parseFloat(p.unit_price).toFixed(2)}</td>
                        <td>${p.quantity}</td>
                        <td>${parseFloat(p.discount_percent||0).toFixed(1)}%</td>
                        <td>${parseFloat(p.gst_percent||18).toFixed(1)}%</td>
                        <td class="text-right font-weight-bold">₹${parseFloat(p.total_price).toFixed(2)}</td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editSingleProduct(${p.id})"><i class="fa fa-pen"></i></button>
                            <button class="btn btn-sm btn-danger" onclick="deleteSingleProduct(${p.id})"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>`;
            });
        }

        function updateSummary(summary) {
            document.getElementById('summarySubtotal').textContent = `₹${parseFloat(summary.subtotal).toFixed(2)}`;
            document.getElementById('summaryDiscount').textContent = `- ₹${parseFloat(summary.total_discount).toFixed(2)}`;
            document.getElementById('summaryGST').textContent = `₹${parseFloat(summary.gst_value).toFixed(2)}`;
            document.getElementById('summaryTotal').textContent = `₹${parseFloat(summary.grand_total).toFixed(2)}`;
        }

        function addNewProduct() {
            document.getElementById('editProductId').value = '';
            document.getElementById('editQuotationId').value = currentQuotationId;
            document.getElementById('editProductName').value = '';
            document.getElementById('editUnitPrice').value = '';
            document.getElementById('editQuantity').value = '1';
            document.getElementById('editDiscountPercent').value = '0';
            document.getElementById('editGstPercent').value = '18';
            document.getElementById('productEditTitle').textContent = 'Add New Product';
            calculateProductTotals();
            $('#productEditModal').modal('show');
        }

        function editSingleProduct(pid) {
            const p = currentProducts.find(x => x.id == pid);
            if (!p) return;
            document.getElementById('editProductId').value = p.id;
            document.getElementById('editQuotationId').value = currentQuotationId;
            document.getElementById('editProductName').value = p.product_id + "~~" + p.product_name;
            document.getElementById('editUnitPrice').value = parseFloat(p.unit_price).toFixed(2);
            document.getElementById('editQuantity').value = p.quantity;
            document.getElementById('editDiscountPercent').value = parseFloat(p.discount_percent || 0).toFixed(2);
            document.getElementById('editGstPercent').value = parseFloat(p.gst_percent || 18).toFixed(2);
            document.getElementById('productEditTitle').textContent = 'Edit Product';
            calculateProductTotals();
            $('#productEditModal').modal('show');
        }

        function deleteSingleProduct(pid) {
            if (!confirm("Delete this product?")) return;
            fetch('api/quotation_products.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `action=delete&product_id=${pid}&quotation_id=${currentQuotationId}`
            }).then(res => res.json()).then(data => {
                if (data.success) loadQuotationProducts();
            });
        }

        function calculateProductTotals() {
            const price = parseFloat(document.getElementById('editUnitPrice').value) || 0;
            const qty = parseFloat(document.getElementById('editQuantity').value) || 0;
            const discPer = parseFloat(document.getElementById('editDiscountPercent').value) || 0;
            const base = price * qty;
            const discAmt = base * (discPer / 100);
            const total = base - discAmt;
            document.getElementById('calcTotalPrice').textContent = `₹${total.toFixed(2)}`;
        }

        document.querySelectorAll('#editUnitPrice, #editQuantity, #editDiscountPercent, #editGstPercent').forEach(el => {
            el.addEventListener('input', calculateProductTotals);
        });

        document.getElementById('editProductName').addEventListener('change', function() {
            const val = this.value;
            if (!val) return;
            const pid = val.split('~~')[0];
            const product = TotalProducts.find(p => p.productid === pid);
            if (product) {
                document.getElementById('editUnitPrice').value = parseFloat(product.mrp_price).toFixed(2);
                document.getElementById('editQuantity').value = product.minimum_quantity || 1;
                document.getElementById('editGstPercent').value = product.gst || 18;
                let disc = 0;
                if (product.mrp_price > product.price) disc = ((product.mrp_price - product.price) / product.mrp_price) * 100;
                document.getElementById('editDiscountPercent').value = disc.toFixed(2);
                calculateProductTotals();
            }
        });

        document.getElementById('productEditForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const action = document.getElementById('editProductId').value ? 'update' : 'add';
            formData.append('action', action);
            fetch('api/quotation_products.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        $('#productEditModal').modal('hide');
                        loadQuotationProducts();
                    } else {
                        alert(data.message);
                    }
                });
        });

        function saveAllProducts() {
            $('#productsModal').modal('hide');
            window.location.reload();
        }
        // Pagination Settings
        const rowsPerPage = 10;
        let currentPage = 1;

        function setupPagination() {

            const rows = document.querySelectorAll("#quotationTableBody tr");
            const totalRows = rows.length;
            const totalPages = Math.ceil(totalRows / rowsPerPage);

            function showPage(page) {

                currentPage = page;

                const start = (page - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                rows.forEach((row, index) => {
                    row.style.display = (index >= start && index < end) ? "" : "none";
                });

                updatePaginationControls(totalPages);
                updatePaginationInfo(totalRows, start, end);
            }

            function updatePaginationControls(totalPages) {

                const pagination = document.getElementById("paginationControls");
                pagination.innerHTML = "";

                // Previous Button
                pagination.innerHTML += `
        <li class="page-item ${currentPage === 1 ? "disabled" : ""}">
            <a class="page-link" href="#" onclick="changePage(${currentPage - 1})">Prev</a>
        </li>
        `;

                for (let i = 1; i <= totalPages; i++) {

                    pagination.innerHTML += `
            <li class="page-item ${i === currentPage ? "active" : ""}">
                <a class="page-link" href="#" onclick="changePage(${i})">${i}</a>
            </li>
            `;
                }

                // Next Button
                pagination.innerHTML += `
        <li class="page-item ${currentPage === totalPages ? "disabled" : ""}">
            <a class="page-link" href="#" onclick="changePage(${currentPage + 1})">Next</a>
        </li>
        `;
            }

            function updatePaginationInfo(totalRows, start, end) {

                const info = document.getElementById("paginationInfo");

                const showingEnd = end > totalRows ? totalRows : end;

                info.innerText =
                    `Showing ${start + 1} to ${showingEnd} of ${totalRows} entries`;
            }

            window.changePage = function(page) {

                const totalPages = Math.ceil(rows.length / rowsPerPage);

                if (page < 1 || page > totalPages) return;

                showPage(page);
            };

            showPage(1);
        }

        // Initialize Pagination
        document.addEventListener("DOMContentLoaded", setupPagination);
    </script>
</body>

</html>