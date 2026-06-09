<?php
// Include session configuration
require_once 'include/session_config.php';

// Check if user is logged in and has access
requireLoginAndAccess('Quotation.php');

// Database connection
$servername = "15.207.37.132";
$username = "cloud";
$password = "TUCKER_ser_sql";
$dbname = "marketing_new";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch distinct product item names for dropdown
$products = [];
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://cgrmart.com/api/get-productview',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
));
$response = curl_exec($curl);
curl_close($curl);
$result = json_decode($response, true);

if (!$result || !$result['success']) {
    $products = [];
} else {
    $products = $result['data'];
}

// Pre-render product options HTML
$product_options_html = '';
foreach ($products as $product) {
    $id = htmlspecialchars($product['productid'], ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars($product['productname'], ENT_QUOTES, 'UTF-8');
    $product_options_html .= "<option value=\"$id~~$name\">$name</option>";
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'edit':
                $id = $_POST['id'];
                $quotation_no = $_POST['quotation_no'];
                $date = $_POST['date'];
                $valid_till = $_POST['valid_till'];
                $client_name = $_POST['client_name'];
                $client_address = $_POST['client_address'];
                $salutation = $_POST['salutation'];
                $subject = $_POST['subject'];
                $introduction = $_POST['introduction'];
                $additional_notes = $_POST['additional_notes'];

                $sql = "UPDATE quotations SET quotation_no=?, date=?, valid_till=?, client_name=?, client_address=?, salutation=?, subject=?, introduction=?, additional_notes=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssssi", $quotation_no, $date, $valid_till, $client_name, $client_address, $salutation, $subject, $introduction, $additional_notes, $id);
                $stmt->execute();
                $stmt->close();
                break;

            case 'delete':
                $id = $_POST['id'];
                $sql = "DELETE FROM quotations WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
                break;
        }
    }
}

if ($_SESSION["master_id"] == 2) {
    $sql = "SELECT q.*,q.id as quo_id, s.*, um.user_name,le.id as lead_id,le.email,le.phone_number FROM quotations q INNER JOIN summary s ON s.quotation_id = q.id LEFT JOIN bigtot_cms.user_management um ON q.parent_id = um.user_id LEFT JOIN leads le ON q.client_id = le.id where le.source_id != '8' and q.order_status != 'S' ORDER BY q.created_at DESC";
} else {
    $sql = "SELECT q.*,q.id as quo_id, s.*,le.id as lead_id,le.email,le.phone_number FROM quotations q INNER JOIN summary s ON s.quotation_id = q.id LEFT JOIN leads le ON q.client_id = le.id WHERE q.parent_id = " . $_SESSION["user_id"] . " and le.source_id != '8' and q.order_status != 'S' ORDER BY q.created_at DESC";
}
$result = $conn->query($sql);
$quotations_data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['half_payment'] = 'N';
        $quotation_id = $row['id'];
        $pay_sql = "SELECT 1 FROM payments WHERE related_id = " . $quotation_id . " LIMIT 1";
        $pay_result = $conn->query($pay_sql);
        if ($pay_result && $pay_result->num_rows > 0) {
            $row['half_payment'] = 'Y';
        }
        $quotations_data[] = $row;
    }
}
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
            <h2 style="margin:0; font-size: 18px; font-weight:700;">Quotations</h2>
            <div style="width: 32px;"></div>
        </div>

        <h1>Quotations</h1>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="search-box">
                <i class="fa-solid fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search Client, ID, Subject...">
            </div>
            <a href="Add_Quatation.php" class="btn-add">
                <i class="fa-solid fa-plus"></i> New Quotation
            </a>
        </div>

        <!-- Data Table -->
        <div class="table-container">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th width="80">ID</th>
                        <th>Quotation No</th>
                        <th>Date</th>
                        <th>Client</th>
                        <!-- <th>Subject</th> -->
                        <th>Status</th>
                        <?php if ($_SESSION["master_id"] == 2) {
                            echo "<th>Sub Head</th>";
                        } ?>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="quotationTableBody">
                    <?php if (!empty($quotations_data)):  $i = 1; ?>
                        <?php foreach ($quotations_data as $row): ?>
                            <tr style="<?php echo ($row['parent_id'] == 5) ? 'background-color: #fcf7f7;' : ''; ?>">
                                <td data-label="ID" style="color:var(--text-muted);">#<?= $i++; ?></td>
                                <td data-label="Quotation No"><strong style="color:var(--primary);"><?= htmlspecialchars($row['quotation_no']) ?></strong></td>
                                <td data-label="Date"><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                <td data-label="Client">
                                    <div style="line-height:1.4;">
                                        <strong style="display:block; font-size:14px; color:#222;">
                                            <?= htmlspecialchars($row['client_name']) ?>
                                        </strong>
                                        <span style="display:block; font-size:12px; color:#666;">
                                            <?= htmlspecialchars($row['email']) ?>
                                        </span>
                                        <span style="display:block; font-size:12px; color:#888;">
                                            <?= htmlspecialchars($row['phone_number']) ?>
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Subject" style="color:var(--text-muted); font-size: 0.9em;display: none;">
                                    <?= htmlspecialchars(mb_strimwidth($row['subject'], 0, 30, "...")) ?>
                                </td>
                                <td>
                                    <?php if ($row['order_status'] == 'N') { ?>
                                        <span class="badge badge-info">Active</span>
                                    <?php } else if ($row['order_status'] == 'Y') { ?>
                                        <span class="badge badge-success">Ordered</span>
                                    <?php } else if ($row['order_status'] == 'S') { ?>
                                        <span class="badge badge-secondary">Superseded</span>
                                    <?php  }else if ($row['order_status'] == 'D') { ?>
                                        <span class="badge badge-danger">Delivered</span>
                                    <?php  }  ?>
                                </td>
                                <?php if ($_SESSION["master_id"] == 2) {
                                    echo "<td data-label='Sub Head'>{$row['user_name']}</td>";
                                } ?>
                                <td data-label="Actions">
                                    <div class="action-btns">
                                        <?php $isSuperseded = ($row['order_status'] === 'S') || ($row['order_status'] === 'Y'); ?>
                                        <?php if ($row['order_status'] == 'N') { ?>
                                            <button type="button" class="btn-icon btn-confirm-order" onclick="confirmOrder(`<?= $i - 1 ?>`,`<?= (int)$row['quo_id']; ?>`,`<?= $row['quotation_no']; ?>`)" title="Confirm Order & Proceed to Payment"><i class="fa-solid fa-check"></i></button>
                                            <button type="button" class="btn-icon btn-secondary"
                                                onclick="reviseQuotation(`<?= $i - 1 ?>`,`<?= (int)$row['quo_id']; ?>`,`<?= $row['quotation_no']; ?>`,`<?= $row['version_code']; ?>`,'<?= $row['half_payment'] ?>')"
                                                title="Revise Quotation (creates new version)">
                                                <i class="fa-solid fa-code-branch"></i>
                                            </button>
                                        <?php } else if ($row['order_status'] == 'Y') { ?>
                                            <button type="button" class="btn-icon btn-view" onclick="paymentorderConformed(<?= (int)$row['quo_id']; ?>,<?= (float)$row['grand_total']; ?>,<?= (int)$row['lead_id']; ?>,'<?= htmlspecialchars($row['email'], ENT_QUOTES); ?>')" title="Confirm Order & Proceed to Payment"><i class="fa-solid fa-dollar-sign"></i></button>

                                            <button type="button" class="btn-icon btn-primary"
                                                onclick="viewOrderDetails(`<?= (int)$row['quo_id']; ?>`)"
                                                title="View Order Details">
                                                <i class="fa-solid fa-receipt"></i>
                                            </button>

                                        <?php } ?>
                                         <?php if ($row['order_status'] != 'D') { ?>
                                        <button
                                            type="button"
                                            class="btn-icon btn-delete"
                                            onclick="deleteQuotation(<?= $row['quo_id']; ?>)"
                                            title="Delete Quotation"
                                            <?= ($isSuperseded || $row['payment_status'] === 'Y' || $row['half_payment'] === 'Y') ? 'disabled' : ''; ?>>
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
  <?php } ?>
                                        <button
                                            type="button"
                                            class="btn-icon btn-download"
                                            onclick="downloadQuotation(<?= $row['quo_id']; ?>,'<?= $row['order_status']; ?>','<?= $row['quotation_no']; ?>')"
                                            title="Download PDF" <?= ($row['order_status'] === 'S') ? 'disabled' : ''; ?>>
                                            <i class="fa-solid fa-file-pdf"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center; padding: 30px; color:var(--text-muted);">No quotations found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <!-- Pagination -->
            <div class="pagination-container" style="margin-top:15px; text-align:center;">
                <button id="prevPage" class="btn btn-sm btn-light">Prev</button>
                <span id="pageNumbers" style="margin:0 10px;"></span>
                <button id="nextPage" class="btn btn-sm btn-light">Next</button>
            </div>
        </div>
    </main>

    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1" data-backdrop="static"
        data-keyboard="false">
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
                                    <th>Partial Invoice</th>
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

    <!-- Colorful Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static"
        data-keyboard="false">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header-colorful">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fa-solid fa-pen-to-square mr-2"></i> Edit Quotation
                    </h5>
                    <!-- Cancel Btn / Close Icon -->
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form id="editForm" method="POST" style="display: flex; flex-direction: column; height: 100%;">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editId">
                    <input type="hidden" name="quotationid" id="quotationid">

                    <div class="modal-body bg-light">
                        <div class="row">
                            <div class="col-lg-8 pr-lg-4">
                                <div class="form-section-title"><i class="fa fa-info-circle"></i> Basic Information</div>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Quotation Number *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-hashtag"></i></span></div>
                                            <input type="text" class="form-control" id="editQuotationNo" name="quotation_no" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Client Name *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-user"></i></span></div>
                                            <input type="text" class="form-control" id="editClientName" name="client_name" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Client Address</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-map-marker-alt"></i></span></div>
                                        <textarea class="form-control" id="editClientAddress" name="client_address" rows="2"></textarea>
                                    </div>
                                </div>

                                <div class="form-section-title"><i class="fa fa-calendar-alt"></i> Important Dates</div>
                                <div class="row">
                                    <div class="col-md-6 form-group">
                                        <label>Date Issued *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-calendar-day"></i></span></div>
                                            <input type="date" class="form-control" id="editDate" name="date" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 form-group">
                                        <label>Valid Until *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-clock"></i></span></div>
                                            <input type="date" class="form-control" id="editValidTill" name="valid_till" required>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section-title"><i class="fa fa-file-alt"></i> Content</div>
                                <div class="row">
                                    <div class="col-md-4 form-group">
                                        <label>Salutation</label>
                                        <input type="text" class="form-control" id="editSalutation" name="salutation">
                                    </div>
                                    <div class="col-md-8 form-group">
                                        <label>Subject *</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text"><i class="fa fa-heading"></i></span></div>
                                            <input type="text" class="form-control" id="editSubject" name="subject" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label>Introduction</label>
                                    <textarea class="form-control" id="editIntroduction" name="introduction" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Additional Notes</label>
                                    <textarea class="form-control" id="editAdditionalNotes" name="additional_notes" rows="2"></textarea>
                                </div>
                            </div>
                            <div class="col-lg-4 mt-4 mt-lg-0">
                                <div class="preview-card">
                                    <div class="preview-header">
                                        <span><i class="fa fa-eye mr-2 text-primary"></i> Live Preview</span>
                                        <span class="badge badge-success" id="quotationStatus">Active</span>
                                    </div>
                                    <div class="preview-body">
                                        <h5 class="font-weight-bold text-center mb-4 text-primary" id="quotationDisplayTitle">#QT-000</h5>
                                        <div class="preview-item"><span class="preview-label">Client</span><span class="preview-value" id="previewClientName">-</span></div>
                                        <div class="preview-item"><span class="preview-label">Date</span><span class="preview-value" id="previewDate">-</span></div>
                                        <div class="preview-item"><span class="preview-label">Valid</span><span class="preview-value" id="previewValidTill">-</span></div>
                                        <hr class="my-4">
                                        <div class="d-flex flex-column gap-2">
                                            <button type="button" class="btn btn-outline-primary btn-block mb-2" onclick="previewQuotation()"><i class="fa fa-file-pdf mr-2"></i> PDF View</button>
                                            <button type="button" class="btn btn-primary btn-block" onclick="editQuotationProducts()"><i class="fa fa-box-open mr-2"></i> Edit Products</button>
                                            <button type="button" class="btn btn-primary btn-block" onclick="SubmitQuotation(this.form)"><i class="fa fa-box-open mr-2"></i> Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white">
                        <!-- Cancel Button working -->
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success font-weight-bold"><i class="fa fa-save mr-2"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Products Modal -->
    <div class="modal fade" id="productsModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static"
        data-keyboard="false">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Edit Products - <span id="quotationTitle"></span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-0">
                    <div id="productsLoading" class="text-center p-5">
                        <div class="spinner-border text-primary"></div>
                    </div>
                    <div id="productsList" style="display:none;">
                        <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold">Item List</h6>
                            <button class="btn btn-success btn-sm" onclick="addNewProduct()"><i class="fa fa-plus"></i> Add Product</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Disc%</th>
                                        <th>GST%</th>
                                        <th>Total</th>
                                        <th>Act</th>
                                    </tr>
                                </thead>
                                <tbody id="productsTableBody"></tbody>
                            </table>
                        </div>
                        <div class="row p-3 m-0 border-top bg-light">
                            <div class="col-md-7"></div>
                            <div class="col-md-5">
                                <div class="d-flex justify-content-between"><span>Subtotal:</span><span id="summarySubtotal">₹0.00</span></div>
                                <div class="d-flex justify-content-between"><span>Discount:</span><span id="summaryDiscount" class="text-danger">-₹0.00</span></div>
                                <div class="d-flex justify-content-between"><span>GST:</span><span id="summaryGST">₹0.00</span></div>
                                <hr class="my-2">
                                <div class="d-flex justify-content-between h5"><span>Grand Total:</span><span id="summaryTotal" class="text-primary">₹0.00</span></div>
                            </div>
                        </div>
                    </div>
                    <div id="noProducts" class="text-center p-5" style="display:none;">
                        <i class="fa-solid fa-box-open fa-3x text-muted mb-3"></i>
                        <h5>No Products</h5>
                        <button class="btn btn-primary mt-2" onclick="addNewProduct()">Add First Product</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="saveAllProducts()">Save & Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Single Product Modal -->
    <div class="modal fade" id="productEditModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060;" data-backdrop="static"
        data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><span id="productEditTitle">Edit Product</span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form id="productEditForm">
                    <input type="hidden" id="editProductId" name="product_id">
                    <input type="hidden" id="editQuotationId" name="quotation_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Product Name</label>
                            <select class="form-control" id="editProductName" name="product_name" required>
                                <option value="">Select...</option><?php echo $product_options_html; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-6 form-group"><label>Price</label><input type="number" class="form-control" id="editUnitPrice" name="unit_price" step="0.01" required></div>
                            <div class="col-6 form-group"><label>Quantity</label><input type="number" class="form-control" id="editQuantity" name="quantity" min="1" required></div>
                        </div>
                        <div class="row">
                            <div class="col-6 form-group"><label>Discount %</label><input type="number" class="form-control" id="editDiscountPercent" name="discount_percent" step="0.01"></div>
                            <div class="col-6 form-group"><label>GST %</label><input type="number" class="form-control" id="editGstPercent" name="gst_percent" step="0.01"></div>
                        </div>
                        <div class="card bg-light border-0 p-3">
                            <div class="d-flex justify-content-between h5 m-0"><span>Total:</span><strong id="calcTotalPrice" class="text-primary">₹0.00</strong></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-info">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" data-backdrop="static"
        data-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button class="close text-white" data-dismiss="modal">&times;</button>
                </div>
                <form id="deleteForm" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteId">
                    <div class="modal-body">Are you sure you want to delete this quotation?</div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" data-backdrop="static"
        data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Quotation Details</h5>
                    <button class="close text-white" data-dismiss="modal">&times;</button>
                </div>

                <div class="modal-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>Quotation No</th>
                            <td id="vdQuotationNo"></td>
                        </tr>
                        <tr>
                            <th>Client Name</th>
                            <td id="vdClient"></td>
                        </tr>
                        <tr>
                            <th>Client Address</th>
                            <td id="vdAddress"></td>
                        </tr>
                        <tr>
                            <th>CPO ID</th>
                            <td id="vdCPO"></td>
                        </tr>
                        <tr>
                            <th>Subtotal</th>
                            <td id="vdSubtotal"></td>
                        </tr>
                        <tr>
                            <th>GST</th>
                            <td id="vdGST"></td>
                        </tr>
                        <tr>
                            <th>Grand Total</th>
                            <td id="vdGrandTotal"></td>
                        </tr>
                        <tr>
                            <th>Payment Status</th>
                            <td id="vdPayment"></td>
                        </tr>
                    </table>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">Close</button>
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

            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                if (text.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });

        document.getElementById("searchInput").addEventListener("keyup", function() {
            let filter = this.value.toLowerCase();
            let rows = document.querySelectorAll("#quotationTableBody tr");
            rows.forEach(function(row) {
                let rowText = row.textContent.toLowerCase();
                if (rowText.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        });


        document.addEventListener('DOMContentLoaded', () => {
            const reviseData = sessionStorage.getItem('reviseQuotation');

            if (!reviseData) return;

            const {
                quotationId,
                half_payment,
                quotation_no
            } = JSON.parse(reviseData);

            // Wait until quotations array exists
            if (!Array.isArray(quotations) || quotations.length === 0) {
                console.warn('Quotations not ready yet');
                return;
            }

            editQuotation(quotationId, half_payment);
            sessionStorage.removeItem('reviseQuotation');
        });

        // Modified confirmOrder function to create an Order
        function confirmOrder(sno, quotationId, quotationNo) {
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
                if (!data.success) {
                    alert('Failed to revise quotation');
                    return;
                }

                const quotation_no = data.quotation_no;
                const version_code = data.version_code;
                const newQuotationId = data.quotation_id;

                // ✅ STORE SESSION VALUES
                sessionStorage.setItem('reviseQuotation', JSON.stringify({
                    quotationId: newQuotationId,
                    quotation_no: quotation_no,
                    half_payment: half_payment
                }));

                window.location.reload();
            });

        }

        function SubmitQuotation(form) {
            const editId = document.getElementById('editId').value;
            const quotationId = document.getElementById('quotationid').value;

            if (!editId || !quotationId) {
                alert("Quotation ID is missing. Cannot update.");
                return;
            }

            const formData = new FormData(form);
            formData.set('action', 'update-quotation'); // overwrite if already present
            formData.set('id', editId);
            formData.set('quotationid', quotationId);

            fetch('api/quotation_products.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        console.log("Quotation updated successfully");
                        // Optional: Close modal and refresh table
                        $('#editModal').modal('hide');
                        location.reload(); // or call a function to refresh your table
                    } else {
                        alert(data.message || "Failed to update quotation");
                    }
                })
                .catch(err => console.error(err));
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
                                <td><button type="button" class="btn-icon btn-download" onclick="downloadQuotation(${formatDate(row.quotation_id)})" title="Download PDF"><i class="fa-solid fa-file-pdf"></i></button></td>
                            </tr>`;
                        });
                    } else {
                        html = `<tr><td colspan="6" class="text-center text-muted">No payments found</td></tr>`;
                    }
                    document.getElementById('payment-table-data').innerHTML = html;
                })
                .catch(err => console.error(err));
        }


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
                                    // -----------------------------
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
                    }
                    // if (data.success) {
                    // alert(data.message);
                    // window.location.reload();
                    // }
                    else {
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
        function paymentorderConformed(id, grand_total, lead_id, lead_email) {
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
        const quotations = <?php echo json_encode($quotations_data); ?>;

        function editQuotation(id, half_payment) {
            const quotation = quotations.find(q => q.quo_id == id);
            if (!quotation) return;

            const isLocked = half_payment === 'Y';
            document.querySelectorAll("#editForm input, #editForm select, #editForm textarea, #editForm button[type=submit]").forEach(el => el.disabled = isLocked);

            document.getElementById('editId').value = quotation.quo_id;
            document.getElementById('quotationid').value = quotation.quo_id;
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

        function downloadQuotation(id) {
            window.location.href = `invoice.php?quotation_id=${id}`;
        }

        // Product Logic
        let currentQuotationId = null;
        let currentProducts = [];
        const TotalProducts = <?php echo json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

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
        let currentPage = 1;
        let rowsPerPage = 10;

        const tableBody = document.getElementById("quotationTableBody");
        const rows = Array.from(tableBody.querySelectorAll("tr"));

        function displayTable() {

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            rows.forEach((row, index) => {
                if (index >= start && index < end) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });

            updatePagination();
        }

        function updatePagination() {

            const pageCount = Math.ceil(rows.length / rowsPerPage);
            const pageNumbers = document.getElementById("pageNumbers");

            pageNumbers.innerHTML = "";

            for (let i = 1; i <= pageCount; i++) {

                const btn = document.createElement("button");

                btn.innerText = i;
                btn.className = "btn btn-sm " + (i === currentPage ? "btn-primary" : "btn-light");

                btn.addEventListener("click", () => {
                    currentPage = i;
                    displayTable();
                });

                pageNumbers.appendChild(btn);
            }
        }

        document.getElementById("prevPage").addEventListener("click", () => {
            if (currentPage > 1) {
                currentPage--;
                displayTable();
            }
        });

        document.getElementById("nextPage").addEventListener("click", () => {
            const pageCount = Math.ceil(rows.length / rowsPerPage);

            if (currentPage < pageCount) {
                currentPage++;
                displayTable();
            }
        });

        displayTable();
        document.getElementById("searchInput").addEventListener("keyup", function() {

            let filter = this.value.toLowerCase();

            rows.forEach(function(row) {

                let rowText = row.textContent.toLowerCase();

                if (rowText.includes(filter)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }

            });

        });
    </script>
</body>

</html>