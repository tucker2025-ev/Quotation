<?php
// Include session configuration
require_once 'include/session_config.php';

// Check if user is logged in and has access, redirect if not
requireLoginAndAccess('Add_Quatation.php');

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

// AJAX endpoint: return latest details for a given product item
if (isset($_GET['action']) && $_GET['action'] === 'product_details') {
    header('Content-Type: application/json');
    $item_name = $_GET['item_name'] ?? '';
    $item_name = trim($item_name);
    if ($item_name === '') {
        echo json_encode(['error' => 'Missing item name']);
        exit();
    }
    $sql = "SELECT base_price, gst, max_discount FROM products WHERE item_name = ? ORDER BY updated_at DESC, id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['error' => 'Query prepare failed']);
        exit();
    }
    $stmt->bind_param('s', $item_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'base_price' => (float)$row['base_price'],
            'gst' => (float)$row['gst'],
            'max_discount' => (float)$row['max_discount']
        ]);
    } else {
        echo json_encode(['error' => 'Product not found']);
    }
    $stmt->close();
    exit();
}

// AJAX endpoint: return client details including address
if (isset($_GET['action']) && $_GET['action'] === 'client_details') {
    header('Content-Type: application/json');
    $client_name = $_GET['client_name'] ?? '';
    $client_name = trim($client_name);
    if ($client_name === '') {
        echo json_encode(['error' => 'Missing client name']);
        exit();
    }
    $sql = "SELECT id, full_name, address, address2, city, state, pincode,parent_id,salutation FROM leads WHERE full_name = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['error' => 'Query prepare failed']);
        exit();
    }
    $stmt->bind_param('s', $client_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            'id' => (int)$row['id'],
            'full_name' => $row['full_name'],
            'address' => $row['address'] ?? '',
            'address2' => $row['address2'] ?? '',
            'city' => $row['city'] ?? '',
            'state' => $row['state'] ?? '',
            'pincode' => $row['pincode'] ?? '',
            'salutation' => $row['salutation'] ?? ''

        ]);
    } else {
        echo json_encode(['error' => 'Client not found']);
    }
    $stmt->close();
    exit();
}

// Helper: Generate next quotation number
function getNextQuotationNo($conn)
{
    $year = date('Y');
    $prefix = "QUO-$year-";

    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(quotation_no, '-', 3),'-', -1) AS UNSIGNED)) AS max_no FROM quotations WHERE quotation_no LIKE ?";

    $stmt = $conn->prepare($sql);
    $like = $prefix . '%';
    $stmt->bind_param('s', $like);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();
    $next = ($row['max_no'] ?? 0) + 1;

    return $prefix . sprintf('%02d', $next);
}
$display_quotation_no = getNextQuotationNo($conn);

// Fetch leads
$leads = [];
$leads_query = "SELECT id, full_name, address,parent_id,customer_type_id,email,phone_number FROM leads where source_id != '8' ORDER BY full_name ASC";
if ($result = $conn->query($leads_query)) {
    while ($row = $result->fetch_assoc()) {
        $leads[] = $row;
    }
    $result->free();
}

// Fetch products via CURL
$products = [];
$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => "https://cgrmart.com/api/get-productview",
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

// Pre-render product options
$product_options_html = '';
foreach ($products as $product) {
    $id = htmlspecialchars($product['productid'], ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars($product['productname'], ENT_QUOTES, 'UTF-8');
    $product_options_html .= "<option value=\"$id~~$name\">$name</option>";
}

function sanitize_input($data)
{
    if ($data === null) return null;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Handle POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $quotation_id = null;
    try {
        $quotation_no = getNextQuotationNo($conn);
        $date = sanitize_input($_POST['date'] ?? '');
        $valid_till = sanitize_input($_POST['valid_till'] ?? '');
        $client_name = sanitize_input($_POST['client_name'] ?? '');
        $client_address = sanitize_input($_POST['client_address'] ?? '');
        $client_address2 = sanitize_input($_POST['client_address2'] ?? '');
        $client_city = sanitize_input($_POST['client_city'] ?? '');
        $client_state = sanitize_input($_POST['client_state'] ?? '');
        $client_pincode = sanitize_input($_POST['client_pincode'] ?? '');
        $salutation = sanitize_input($_POST['salutation'] ?? '');
        $subject = sanitize_input($_POST['subject'] ?? '');
        $introduction = sanitize_input($_POST['introduction'] ?? '');
        $additional_notes = sanitize_input($_POST['additional_notes'] ?? '');
        $warranty_year = sanitize_input($_POST['warranty_year'] ?? '');

        $terms_conditions = $_POST['terms_conditions'];


        $sql = "SELECT id,cpo_id, cms_id FROM leads WHERE full_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $client_name);
        $stmt->execute();

        $row = $stmt->get_result()->fetch_assoc();

        $client_id = $row['id'] ?? null;
        $cpo_id = $row['cpo_id'] ?? null;

        $cms_id = $row['cms_id'] ?? null;

        $stmt->close();

        $formatted_address = trim($client_address);
        if (!empty($client_address2)) $formatted_address .= "\n" . $client_address2;
        if (!empty($client_city) || !empty($client_state)) $formatted_address .= "\n" . trim($client_city . ', ' . $client_state);
        if (!empty($client_pincode)) $formatted_address .= "\n" . $client_pincode;

        $sql_quotations = "INSERT INTO quotations (parent_id,client_id,quotation_no, date, valid_till, client_name, client_address, salutation, subject, introduction, additional_notes,cpo_id,cms_id,year,terms_conditions) VALUES (?,?,?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?)";
        $stmt = $conn->prepare($sql_quotations);
        $stmt->bind_param("sssssssssssssss", $_SESSION["user_id"], $client_id, $quotation_no, $date, $valid_till, $client_name, $formatted_address, $salutation, $subject, $introduction, $additional_notes, $cpo_id, $cms_id, $warranty_year,$terms_conditions);
        $stmt->execute();
        $quotation_id = $conn->insert_id;
        $stmt->close();

        $sql = "UPDATE quotations
        SET terms_conditions = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $terms_conditions, $quotation_id);
$stmt->execute();

        // Bank Details
        $bank_name = sanitize_input($_POST['bank_name'] ?? '');
        $account_number = sanitize_input($_POST['account_number'] ?? '');
        $ifsc_code = sanitize_input($_POST['ifsc_code'] ?? '');
        $branch_name = sanitize_input($_POST['branch_name'] ?? '');

        $sql_bank_details = "INSERT INTO bank_details (quotation_id, bank_name, account_number, ifsc_code, branch_name) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_bank_details);
        $stmt->bind_param("issss", $quotation_id, $bank_name, $account_number, $ifsc_code, $branch_name);
        $stmt->execute();
        $stmt->close();

        // Products
        $product_names = $_POST['product_name'] ?? [];
        $unit_prices = $_POST['unit_price'] ?? [];
        $quantities = $_POST['quantity'] ?? [];
        $discounts = $_POST['discount_percent'] ?? [];
        $gsts = $_POST['gst_percent'] ?? [];

        $sql_products = "INSERT INTO productss (quotation_id, product_id,product_name, unit_price, quantity, discount_percent, gst_percent, total_price) VALUES (?, ?, ?,?, ?, ?, ?, ?)";
        $stmt_products = $conn->prepare($sql_products);

        $server_subtotal = 0;
        $server_total_discount = 0;
        $server_net_taxable_value = 0;
        $server_gst_value = 0;

        for ($i = 0; $i < count($product_names); $i++) {
            list($product_id, $product_name) = explode('~~', $product_names[$i], 2);
            $product_id   = sanitize_input(trim($product_id));
            $product_name = sanitize_input(trim($product_name));
            $unit_price = (float) sanitize_input($unit_prices[$i] ?? 0);
            $quantity = (int) sanitize_input($quantities[$i] ?? 0);
            $discount_percent = (float) sanitize_input($discounts[$i] ?? 0);
            $gst_percent = (float) sanitize_input($gsts[$i] ?? 0);

            $itemBasePrice = $unit_price * $quantity;
            $itemDiscountAmount = $itemBasePrice * ($discount_percent / 100);
            $itemPriceAfterDiscount = $itemBasePrice - $itemDiscountAmount;
            $itemGSTAmount = $itemPriceAfterDiscount * ($gst_percent / 100);
            $calculatedTotalPrice = $itemPriceAfterDiscount + $itemGSTAmount;

            $stmt_products->bind_param("issidddd", $quotation_id, $product_id, $product_name, $unit_price, $quantity, $discount_percent, $gst_percent, $calculatedTotalPrice);
            $stmt_products->execute();

            $server_subtotal += $itemBasePrice;
            $server_total_discount += $itemDiscountAmount;
            $server_net_taxable_value += $itemPriceAfterDiscount;
            $server_gst_value += $itemGSTAmount;
        }
        $stmt_products->close();
        $server_grand_total = $server_net_taxable_value + $server_gst_value;

        // Summary
        $sql_summary = "INSERT INTO summary (quotation_id, subtotal, net_value, total_discount, gst_value, grand_total) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_summary);
        $stmt->bind_param("iddddd", $quotation_id, $server_subtotal, $server_net_taxable_value, $server_total_discount, $server_gst_value, $server_grand_total);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        header("Location: invoice.php?quotation_id=" . $quotation_id);
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error: " . $e->getMessage();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quotation</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="https://station.cms.tuckermotors.com/asset/images/favicon.ico">

    <style>
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px;
            width: 200px;
        }

        /* ============================
           3. MOBILE HEADER
        ============================ */
        .mobile-header {
            display: none;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
        }

        .menu-btn {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        /* ===== CSS VARIABLES ===== */
        :root {
            --sidebar-bg: #FFFFFF;
            --sidebar-width-expanded: 250px;
            --sidebar-width-collapsed: 88px;
            --text-primary: #1A202C;
            --text-secondary: #718096;
            --text-active: #2D3748;
            --border-color: #E2E8F0;
            --active-indicator: linear-gradient(135deg, #FF7E86, #8E54E9);
            --main-bg: #F7F7FA;
            --card-bg: #FFFFFF;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --primary-color: #667eea;
            --secondary-color: #E2E8F0;
        }

        /* ===== BASE ===== */
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--main-bg);
            color: var(--text-primary);
            font-size: 14px;
            transition: background 0.3s;
        }

        /* ===== MAIN LAYOUT ===== */
        .main-content {
            margin-left: var(--sidebar-width-expanded);
            padding: 32px;
            width: calc(100% - var(--sidebar-width-expanded));
            transition: 0.3s;
        }

        body.sidebar-is-collapsed .main-content {
            margin-left: var(--sidebar-width-collapsed);
            width: calc(100% - var(--sidebar-width-collapsed));
        }

        /* Mobile Menu Button */
        .mobile-menu-trigger {
            display: none;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 16px;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            cursor: pointer;
            width: fit-content;
        }

        /* ===== FORM STYLES ===== */
        .form-section {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
        }

        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.95rem;
            background: var(--card-bg);
            color: var(--text-primary);
        }

        /* ===== BANK TABS (ORIGINAL COLORS) ===== */
        .bank-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
            flex-wrap: wrap;
        }

        .bank-tab {
            background: var(--main-bg);
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: 0.2s;
            color: var(--text-primary);
            /* Stretch on mobile for easier tapping */
            flex: 1;
            text-align: center;
            white-space: nowrap;
        }

        .bank-tab.active {
            background: var(--active-indicator);
            color: white;
            border-color: transparent;
        }

        input[readonly] {
            background-color: #f8fafc;
            cursor: default;
        }

        /* ===== PRODUCT TABLE STYLES ===== */
        .select2-container {
            width: 100% !important;
        }

        .heading-row {
            display: flex;
            padding: 10px 12px;
            gap: 10px;
            background: var(--secondary-color);
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .heading-row span {
            font-weight: 700;
            color: var(--text-primary);
            /* text-align: center; */
        }

        .product-row {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .row-left {
            display: flex;
            flex: 1;
            align-items: center;
            gap: 10px;
            width: 100%;
        }

        .mobile-grid-inputs {
            display: flex;
            /* Default for Desktop */
            gap: 10px;
            flex: 3;
        }

        .mobile-input-group {
            flex: 1;
        }

        .item-index-badge {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: var(--secondary-color);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            flex-shrink: 0;
        }

        .row-total {
            width: 120px;
            font-weight: 600;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            gap: 4px;
        }

        .remove-product-row {
            background: #fee2e2;
            color: #ef4444;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .add-item-bar {
            background: var(--text-primary);
            color: white;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
            margin-top: 15px;
        }

        .mobile-label {
            display: none;
        }

        /* Hidden on desktop */

        /* Summary */
        .summary-card {
            background: var(--main-bg);
            border-radius: 10px;
            padding: 20px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .summary-item.total {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--primary-color);
            border-top: 1px solid var(--border-color);
            padding-top: 12px;
            margin-top: 12px;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 16px;
            }

            .mobile-menu-trigger {
                display: flex;
            }

            /* Sidebar Slide Logic */
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 100;
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            /* Hide Desktop Headers */
            .heading-row {
                display: none !important;
            }

            /* Product Card Mobile */
            .product-row {
                flex-direction: column;
                align-items: stretch;
                padding: 15px;
                position: relative;
            }

            .row-left {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }

            .item-index-badge {
                position: absolute;
                top: 10px;
                left: 10px;
                z-index: 2;
            }

            /* Adjust Product Select to clear badge */
            .product-select-wrapper {
                padding-left: 30px;
            }

            /* Mobile Grid for 4 inputs */
            .mobile-grid-inputs {
                display: grid !important;
                grid-template-columns: 1fr 1fr;
                gap: 12px;
                width: 100%;
            }

            .mobile-label {
                display: block;
                font-size: 0.8rem;
                color: var(--text-secondary);
                margin-bottom: 2px;
            }

            /* Total Row */
            .row-total {
                width: 100%;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                border-top: 1px solid var(--border-color);
                margin-top: 10px;
                padding-top: 10px;
            }

            .remove-product-row {
                width: 30px;
                height: 30px;
            }

            /* Bank Details Spacing Fix */
            .form-group.col-6 {
                padding-left: 5px;
                padding-right: 5px;
            }

            .row {
                margin-left: -5px;
                margin-right: -5px;
            }
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            body.sidebar-collapsed .main-content {
                margin-left: 0;
            }

            .mobile-header {
                display: flex;
            }

            .header h1 {
                display: none;
            }

            .header .back_btn {
                margin-left: auto;
            }


            /* On Tablets, allow scrolling if not switching to cards yet */
            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>
    <!-- ===== SIDEBAR ===== -->
    <?php include 'include/sidebar.php'; ?>

    <!-- ===== MAIN CONTENT ===== -->
    <main class="main-content">
        <div class="mobile-header">
            <button id="mobileMenuBtn" class="menu-btn">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h2 style="margin:0; font-size: 16px; font-weight:600;">Create Quotation</h2>
            <div style="width: 32px;"></div>
        </div>


        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap header">
            <h1 class="h3 mb-2 mb-md-0">Create Quotation</h1>
            <a href="Quotation.php" class="btn btn-secondary btn-sm back_btn">Back to List</a>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="" method="POST">
            <!-- Meta Section -->
            <div class="form-section">
                <div class="row">
                    <!-- Left: Basic Info -->
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <h4 class="mb-3">Basic Information</h4>
                        <div class="form-group">
                            <label>Quotation No.</label>
                            <input type="text" class="form-control" name="quotation_no" value="<?php echo $display_quotation_no; ?>" readonly>
                        </div>
                        <div class="row">
                            <div class="col-6 form-group">
                                <label>Date</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-6 form-group">
                                <label>Valid Till</label>
                                <input type="date" class="form-control" id="valid_till" name="valid_till" value="" required>
                            </div>
                        </div>

                        <!-- warranty_year Select -->
                        <label>Warranty Year</label>
                        <div class="form-group">
                            <select class="form-control warranty_year" name="warranty_year" required>
                                <option value="1">1</option>
                                <option value="10">10</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="dealer_only" name="single_label">
                                Dealer Clients
                            </label>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" id="white_label_only" name="single_label">
                                White Label Clients
                            </label>
                        </div>
                        <div class="form-group">
                            <label>Client Name</label>

                            <select class="form-control client-select" name="client_name" id="client_name" required>
                                <option value="">Select Client</option>
                                <?php foreach ($leads as $l): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($l['full_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($l['email']); ?>"
                                        data-mobile="<?php echo htmlspecialchars($l['phone_number']); ?>"
                                        data-customer-type="<?php echo (int)$l['customer_type_id']; ?>">
                                        <?php echo htmlspecialchars($l['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Address</label>
                            <input type="text" class="form-control" id="client_address" name="client_address" placeholder="Line 1" readonly>
                            <input type="text" class="form-control mt-2" id="client_address2" name="client_address2" placeholder="Line 2" readonly>
                        </div>
                        <div class="row">
                            <div class="col-4 pr-1"><input type="text" class="form-control" id="client_city" name="client_city" placeholder="City" readonly></div>
                            <div class="col-4 px-1"><input type="text" class="form-control" id="client_state" name="client_state" placeholder="State" readonly></div>
                            <div class="col-4 pl-1"><input type="text" class="form-control" id="client_pincode" name="client_pincode" placeholder="Pin" readonly></div>
                        </div>
                    </div>

                    <!-- Right: Bank Details -->
                    <div class="col-lg-6">
                        <h4 class="mb-3">Bank Details</h4>
                        <!-- Original Style Tabs with Flex adjustment -->
                        <div class="bank-tabs">
                            <button type="button" class="bank-tab active" data-bank="bob">Bank of Baroda</button>
                            <button type="button" class="bank-tab" data-bank="hdfc">HDFC Bank</button>
                            <button type="button" class="bank-tab" data-bank="custom">Custom</button>
                        </div>

                        <!-- Organized Grid for Mobile View -->
                        <div class="row">
                            <div class="col-12 form-group">
                                <label>Bank Name</label>
                                <input type="text" class="form-control" id="bank_name" name="bank_name" required>
                            </div>
                            <div class="col-12 form-group">
                                <label>Account No</label>
                                <input type="text" class="form-control" id="account_number" name="account_number" required>
                            </div>
                            <div class="col-6 form-group">
                                <label>IFSC Code</label>
                                <input type="text" class="form-control" id="ifsc_code" name="ifsc_code" required>
                            </div>
                            <div class="col-6 form-group">
                                <label>Branch</label>
                                <input type="text" class="form-control" id="branch_name" name="branch_name" required>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Intro Section -->
            <div class="form-section">
                <h4 class="mb-3">Greeting & Subject</h4>
                <div class="form-group"><label>Salutation</label><input type="text" class="form-control" id="salutation" name="salutation" value="Dear Sir/Madam"></div>
                <div class="form-group"><label>Subject</label><input type="text" class="form-control" name="subject" value="Quotation for Electric Vehicle Charging Station"></div>
                <div class="form-group"><label>Introduction</label><textarea class="form-control" name="introduction" rows="2">We hereby give our best quote based on our understanding...</textarea></div>
            </div>

            <!-- Products Section -->
            <div class="form-section">
                <h4 class="mb-3">Products & Services</h4>
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Desktop Heading Row -->
                        <div class="heading-row d-none d-md-flex">
                            <span style="width: 40px;">No</span>
                            <span style="flex: 2; text-align: center;">Product Name</span>
                            <span style="flex: 0.5;">Price</span>
                            <span style="flex: 0.5;">Qty</span>
                            <span style="flex: 0.5;">GST%</span>
                            <span style="flex: 0.5;">Disc%</span>
                            <span style="width: 120px; text-align: right;">Total</span>
                        </div>

                        <div id="product-list">
                            <!-- Product Row 1 -->
                            <div class="product-row">
                                <div class="row-left">
                                    <span class="item-index-badge">1</span>

                                    <!-- Product Select -->
                                    <div class="mobile-input-group product-select-wrapper" style="flex: 2;">
                                        <label class="mobile-label">Product Name</label>
                                        <select class="form-control product-select" name="product_name[]" required>
                                            <option value="">Select a Product/Service...</option>
                                            <?php echo $product_options_html; ?>
                                        </select>
                                    </div>

                                    <!-- Price/Qty/Gst/Disc Group (Grid on Mobile) -->
                                    <div class="mobile-grid-inputs">
                                        <div class="mobile-input-group">
                                            <label class="mobile-label">Price</label>
                                            <input type="number" class="form-control" name="unit_price[]" placeholder="Price" min="0" step="0.01">
                                        </div>
                                        <div class="mobile-input-group">
                                            <label class="mobile-label">Qty</label>
                                            <input type="number" class="form-control" name="quantity[]" value="1" placeholder="1" min="1" step="1">
                                        </div>
                                        <div class="mobile-input-group">
                                            <label class="mobile-label">GST %</label>
                                            <input type="number" class="form-control" name="gst_percent[]" placeholder="GST" min="0" max="100" step="0.01">
                                        </div>
                                        <div class="mobile-input-group">
                                            <label class="mobile-label">Disc %</label>
                                            <input type="number" class="form-control" name="discount_percent[]" value="0" placeholder="Disc" min="0" max="100" step="0.01">
                                        </div>
                                    </div>

                                    <!-- Hidden Fields -->
                                    <input type="hidden" name="min_discount[]" value="0">
                                    <input type="hidden" name="max_discount[]" value="0">
                                </div>

                                <div class="row-total">
                                    <span class="d-md-none text-muted">Total:</span>
                                    <span class="amount">₹0.00</span>
                                    <input type="hidden" name="total_Amount[]" value="0">
                                    <button type="button" class="remove-product-row" title="Remove Item"><i class="fas fa-trash-alt"></i></button>
                                </div>
                            </div>
                        </div>

                        <div class="add-item-bar mt-3">+ Add Item</div>
                    </div>

                    <!-- Summary -->
                    <div class="col-lg-4 mt-4 mt-lg-0">
                        <div class="summary-card">
                            <h5 class="mb-3">Summary</h5>
                            <div class="summary-item"><span>Subtotal (Excl. Tax)</span><span id="subtotal-display">₹0.00</span> <input type="hidden" name="subtotal" id="subtotal-input" value="0.00"></div>
                            <div class="summary-item"><span>Net Taxable Value</span><span id="net-taxable-value-display">₹0.00</span><input type="hidden" name="net_taxable_value" id="net-taxable-value-input" value="0.00"></div>
                            <div class="summary-item"><span>Total Discount</span><span id="total-discount-display">₹0.00</span><input type="hidden" name="total_discount" id="total-discount-input" value="0.00"></div>
                            <div class="summary-item"><span>Total GST</span><span id="gst-value-display">₹0.00</span></div>
                            <div class="summary-item total"><span>Grand Total</span><span id="grand-total-display">₹0.00</span><input type="hidden" name="grand_total" id="grand-total-input" value="0.00"></div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Terms & Condition -->
            <?php
            $default_terms = "Prices quoted are exclusive of GST unless otherwise specified. Quotation valid for 30 days.

70% advance & 30% before dispatch. Dispatch after full payment only.

Supply includes EV charger as per quotation. Installation and accessories excluded unless specified.

Delivery: DC Fast Charger – 3–4 weeks | AC Charger – 1–2 weeks from order confirmation and advance payment.

" . ($quotation['year'] ?? '1') . " year warranty against manufacturing defects only.

AMC & Support: Annual Maintenance Contract (AMC) available after the warranty period.

Orders once confirmed cannot be cancelled or returned.

Liability limited to invoice value.

Governed by Indian law. Jurisdiction: Madurai, Tamil Nadu.";
            ?>

            <div class="form-section">
                <h4 class="mb-3">Terms & Condition</h4>
                <textarea name="terms_conditions" class="form-control" rows="10"><?=
                                                                                    htmlspecialchars(
                                                                                        !empty($quotation['terms_conditions'])
                                                                                            ? $quotation['terms_conditions']
                                                                                            : $default_terms
                                                                                    );
                                                                                    ?></textarea>
            </div>


            <!-- Footer Section -->
            <div class="form-section">
                <h4 class="mb-3">Additional Notes</h4>
                <textarea class="form-control" name="additional_notes" rows="3">The white labelling cost of ₹50,000 plus 18% GST, if has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.</textarea>
            </div>

            <div class="d-flex justify-content-end mb-5">
                <!-- CANCEL BUTTON: Explicit Redirect -->
                <button type="button" class="btn btn-secondary mr-2" onclick="window.location.href='Quotation.php'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background:var(--active-indicator); border:none;">Generate Quotation</button>
            </div>
        </form>
    </main>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        function loadProductDetailsForRow($row) {
            const productId = $row.find('select.product-select').val().split('~~')[0];
            if (!productId) return;
            $.getJSON('api/get_products_details.php', {
                    product_id: productId
                })
                .done(function(res) {
                    if (!res || !res.success || !res.data) return;
                    const product = res.data;

                    $row.find('input[name="unit_price[]"]').val(parseFloat(product.mrp_price) || 0);
                    $row.find('input[name="gst_percent[]"]').val(product.gst).attr('max', product.gst);
                    $row.find('input[name="min_discount[]"]').val(product.min_discount);
                    $row.find('input[name="max_discount[]"]').val(product.max_discount);

                    let discount = 0;
                    if (product.mrp_price && product.price) {
                        discount = ((product.mrp_price - product.price) / product.mrp_price) * 100;
                    }
                    $row.find('input[name="discount_percent[]"]').val(Math.max(0, discount.toFixed(2)));
                    updateSummary();
                });
        }

        function updateSummary() {
            let subtotalBeforeDiscount = 0;
            let totalDiscountAmount = 0;
            let netTaxableValue = 0;
            let totalGSTValue = 0;
            let gstItems = []; // Array to store item numbers with GST

            $('#product-list .product-row').each(function(index) {
                let unitPrice = parseFloat($(this).find('input[name="unit_price[]"]').val()) || 0;
                let quantity = parseInt($(this).find('input[name="quantity[]"]').val()) || 0;
                let discountPercent = parseFloat($(this).find('input[name="discount_percent[]"]')
                    .val()) || 0;
                let gstPercent = parseFloat($(this).find('input[name="gst_percent[]"]').val()) || 0;
                let min_discount = parseFloat($(this).find('input[name="min_discount[]"]').val()) || 0;
                let max_discount = parseFloat($(this).find('input[name="max_discount[]"]').val()) || 0;

                if (discountPercent < min_discount) {
                    // handle error / show message / reset value
                    alert(`Discount must be between ${min_discount}% and ${max_discount}%`);
                    $(this).find('input[name="discount_percent[]"]').val(min_discount);
                    discountPercent = 1
                } else if (discountPercent > max_discount) {
                    $(this).find('input[name="discount_percent[]"]').val(min_discount);
                    alert(`Discount must be between ${min_discount}% and ${max_discount}%`);
                    discountPercent = 1

                }

                let itemBasePrice = unitPrice * quantity;
                subtotalBeforeDiscount += itemBasePrice;

                let itemDiscountAmount = itemBasePrice * (discountPercent / 100);
                totalDiscountAmount += itemDiscountAmount;

                let itemPriceAfterDiscount = itemBasePrice - itemDiscountAmount;
                netTaxableValue += itemPriceAfterDiscount;

                let itemGSTAmount = itemPriceAfterDiscount * (gstPercent / 100);
                totalGSTValue += itemGSTAmount;

                // Track items with GST
                if (gstPercent > 0) {
                    gstItems.push(index + 1); // Item number (1-based)
                }
                let rowTotalDisplay = itemPriceAfterDiscount;
                $(this).find('.row-total .amount').text(formatCurrency(rowTotalDisplay));
                //Update hidden input correctly
                $(this).find('input[name="total_Amount[]"]').val(rowTotalDisplay);

            });

            let grandTotal = netTaxableValue + totalGSTValue;

            $('#subtotal-display').text(formatCurrency(subtotalBeforeDiscount));
            $('#subtotal-input').val(subtotalBeforeDiscount.toFixed(2));

            $('#net-taxable-value-display').text(formatCurrency(netTaxableValue));
            $('#net-taxable-value-input').val(netTaxableValue.toFixed(2));

            $('#total-discount-display').text(formatCurrency(totalDiscountAmount));
            $('#total-discount-input').val(totalDiscountAmount.toFixed(2));

            $('#grand-total-display').text(formatCurrency(grandTotal));
            $('#gst-value-display').text(formatCurrency(totalGSTValue));
            $('#grand-total-input').val(grandTotal.toFixed(2));
        }

        function formatCurrency(amount) {
            return '₹' + parseFloat(amount).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function initProductSearch($context = $(document)) {
            $context.find('.client-select, .product-select').each(function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        placeholder: 'Select options...',
                        allowClear: true,
                        width: '100%'
                    });
                }
            });
        }

        function renumberRows() {
            $('#product-list .product-row').each(function(index) {
                $(this).find('.item-index-badge').text(index + 1);
            });
        }

        $(document).ready(function() {
            const leads_data = <?= json_encode($leads, JSON_UNESCAPED_UNICODE); ?>;
            // console.log(leads_data);
            // ===== SELECT2 INIT =====
            if ($.fn.select2) initProductSearch();



            if ($.fn.select2) {
                initProductSearch();
            }

            if ($.fn.select2) {
                initProductSearch();
            }

            // ===== CHECKBOX FILTER =====
            let filterTypes = null;

            $('#client_name').select2({
                placeholder: 'Select Client',
                allowClear: true,
                width: '100%',

                matcher: function(params, data) {
                    if ($.trim(params.term) === '') {
                        return data;
                    }

                    if (typeof data.text === 'undefined') {
                        return null;
                    }

                    const term = params.term.toLowerCase();

                    const name = data.text.toLowerCase();
                    const email = $(data.element).attr('data-email')?.toLowerCase() || '';
                    const mobile = $(data.element).attr('data-mobile')?.toLowerCase() || '';

                    if (
                        name.includes(term) ||
                        email.includes(term) ||
                        mobile.includes(term)
                    ) {
                        return data;
                    }

                    return null;
                },

                templateResult: function(data) {
                    if (!data.id) return data.text;

                    const email = $(data.element).attr('data-email') || '';
                    const mobile = $(data.element).attr('data-mobile') || '';
                    const index = $(data.element).index();

                    return $(`
            <div style="display:flex; justify-content:space-between; font-size:13px;">
                <div style="width:40px;">${index}</div>
                <div style="flex:1;"><b>${data.text}</b></div>
                <div style="flex:1;">${email}</div>
                <div style="flex:1;">${mobile}</div>
            </div>
        `);
                },

                templateSelection: function(data) {
                    if (!data.id) return data.text;

                    const email = $(data.element).attr('data-email') || '';
                    const mobile = $(data.element).attr('data-mobile') || '';

                    return `${data.text} | ${email} | ${mobile}`;
                }
            });
            // Dealer checkbox
            $('#dealer_only').on('change', function() {
                if (this.checked) {
                    $('#white_label_only').prop('checked', false);
                    filterTypes = ["4"];
                } else {
                    filterTypes = null;
                }
                refreshClientDropdown();
            });

            // White Label checkbox
            $('#white_label_only').on('change', function() {
                if (this.checked) {
                    $('#dealer_only').prop('checked', false);
                    filterTypes = ["5", "6", "7"];
                } else {
                    filterTypes = null;
                }
                refreshClientDropdown();
            });

            function refreshClientDropdown() {
                $('#client_name').val(null).trigger('change');
                $('#client_name').select2('close').select2('open');
            }
            // ===== BANK DETAILS LOGIC =====
            const bankPresets = {
                bob: {
                    bank_name: 'Bank of Baroda',
                    account_number: '97400500000298',
                    ifsc_code: 'BARB0DBMRAI',
                    branch_name: 'Kamarajar Salai'
                },
                hdfc: {
                    bank_name: 'HDFC Bank',
                    account_number: '601305021603',
                    ifsc_code: 'ICIC0006013',
                    branch_name: 'MADURAI SUBRAMANIPURAM BRANCH'
                },
                custom: {
                    bank_name: '',
                    account_number: '',
                    ifsc_code: '',
                    branch_name: ''
                }
            };

            function applyBankPreset(key) {
                const preset = bankPresets[key];
                if (!preset) return;

                $('#bank_name').val(preset.bank_name);
                $('#account_number').val(preset.account_number);
                $('#ifsc_code').val(preset.ifsc_code);
                $('#branch_name').val(preset.branch_name);

                // Make readonly if not custom, to prevent errors
                const isReadOnly = (key !== 'custom');
                $('#bank_name, #account_number, #ifsc_code, #branch_name').prop('readonly', isReadOnly);
            }

            $(document).on('click', '.bank-tab', function() {
                $('.bank-tab').removeClass('active');
                $(this).addClass('active');
                const key = $(this).data('bank');
                applyBankPreset(key);
            });
            // Init
            applyBankPreset('bob');

            // ===== CALCULATIONS & HELPERS =====

            function loadClientDetails(clientName) {
                if (!clientName) {
                    $('#client_address').val('');
                    $('#client_address2').val('');
                    $('#client_city').val('');
                    $('#client_state').val('');
                    $('#client_pincode').val('');
                    $('#salutation').val('');
                    return;
                }
                $.getJSON(window.location.pathname + '?action=client_details&client_name=' + encodeURIComponent(clientName))
                    .done(function(data) {
                        // console.log(data,"data")
                        if (data && !data.error) {
                            $('#client_address').val(data.address || '');
                            $('#client_address2').val(data.address2 || '');
                            $('#client_city').val(data.city || '');
                            $('#client_state').val(data.state || '');
                            $('#client_pincode').val(data.pincode || '');
                            $('#salutation').val(data.salutation + data.full_name + ',');
                        }
                    });
            }


            // Events
            $(document).on('change', '.product-select', function() {
                loadProductDetailsForRow($(this).closest('.product-row'));
            });

            $('#product-list').on('change keyup', 'input', function() {
                updateSummary();
            });

            $('#client_name').on('change', function() {
                loadClientDetails($(this).val());
            });

            $('.add-item-bar').on('click', function() {
                const index = $('#product-list .product-row').length + 1;
                const newRow = `
                <div class="product-row">
                    <div class="row-left">
                        <span class="item-index-badge">${index}</span>
                        <div class="mobile-input-group product-select-wrapper" style="flex: 2;">
                            <label class="mobile-label">Product Name</label>
                            <select class="form-control product-select" name="product_name[]" required>
                                <option value="">Select a Product/Service...</option>
                                <?php echo $product_options_html; ?>
                            </select>
                        </div>
                        <div class="mobile-grid-inputs">
                            <div class="mobile-input-group">
                                <label class="mobile-label">Price</label>
                                <input type="number" class="form-control" name="unit_price[]" placeholder="Price" min="0" step="0.01">
                            </div>
                            <div class="mobile-input-group">
                                <label class="mobile-label">Qty</label>
                                <input type="number" class="form-control" name="quantity[]" value="1" placeholder="1" min="1" step="1">
                            </div>
                            <div class="mobile-input-group">
                                <label class="mobile-label">GST %</label>
                                <input type="number" class="form-control" name="gst_percent[]" placeholder="GST" min="0" max="100" step="0.01">
                            </div>
                            <div class="mobile-input-group">
                                <label class="mobile-label">Disc %</label>
                                <input type="number" class="form-control" name="discount_percent[]" value="0" placeholder="Disc" min="0" max="100" step="0.01">
                            </div>
                        </div>
                        <input type="hidden" name="min_discount[]" value="0">
                        <input type="hidden" name="max_discount[]" value="0">
                    </div>
                    <div class="row-total">
                        <span class="d-md-none text-muted">Total:</span>
                        <span class="amount">₹0.00</span>
                        <input type="hidden" name="total_Amount[]" value="0">
                        <button type="button" class="remove-product-row"><i class="fas fa-trash-alt"></i></button>
                    </div>
                </div>`;
                const $row = $(newRow);
                $('#product-list').append($row);
                initProductSearch($row);
                renumberRows();
            });

            $('#product-list').on('click', '.remove-product-row', function() {
                $(this).closest('.product-row').remove();
                renumberRows();
                updateSummary();
            });

            // Init Dates & Client
            document.getElementById('date').valueAsDate = new Date();
            let d = new Date();
            d.setDate(d.getDate() + 30);
            document.getElementById('valid_till').valueAsDate = d;

            if ($('#client_name').val()) loadClientDetails($('#client_name').val());
        });

        function addAutoProducts(productNames) {
            productNames.forEach(function(productName) {
                let exists = false;

                // Prevent duplicate items
                $('#product-list .product-row').each(function() {
                    const selectedText = $(this).find('select.product-select option:selected').text().trim();
                    if (selectedText === productName) {
                        exists = true;
                    }
                });

                if (!exists) {
                    addNewRowWithProduct(productName);
                }
            });
        }

        function addNewRowWithProduct(productName) {
            const index = $('#product-list .product-row').length + 1;

            const newRow = $(`
        <div class="product-row">
            <div class="row-left">
                <span class="item-index-badge">${index}</span>

                <div class="mobile-input-group product-select-wrapper" style="flex: 2;">
                    <select class="form-control product-select" name="product_name[]" required>
                        <option value="">Select a Product/Service...</option>
                        <?php echo $product_options_html; ?>
                    </select>
                </div>

                <div class="mobile-grid-inputs">
                    <input type="number" class="form-control" name="unit_price[]" placeholder="Price">
                    <input type="number" class="form-control" name="quantity[]" value="1">
                    <input type="number" class="form-control" name="gst_percent[]" placeholder="GST">
                    <input type="number" class="form-control" name="discount_percent[]" value="0">
                </div>

                <input type="hidden" name="min_discount[]" value="0">
                <input type="hidden" name="max_discount[]" value="0">
            </div>

            <div class="row-total">
                <span class="amount">₹0.00</span>
                <input type="hidden" name="total_Amount[]" value="0">
                <button type="button" class="remove-product-row">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
        </div>
    `);

            $('#product-list').append(newRow);

            // Re-init select2
            initProductSearch(newRow);

            // Select product automatically
            const select = newRow.find('select.product-select');

            select.find('option').each(function() {
                if ($(this).text().trim() === productName) {
                    select.val($(this).val()).trigger('change');
                }
            });

            renumberRows();
        }

        $(document).on('change', '.product-select', function() {
            const selectedText = $(this).find('option:selected').text().toLowerCase();

            loadProductDetailsForRow($(this).closest('.product-row'));

            // ✅ Detect Franchise
            if (selectedText.includes('franchise')) {

                addAutoProducts([
                    'Canopy',
                    'DB Panel',
                    'Installation & Commissioning',
                    'Safety Equipment (Camera & Fire Extinguisher)'
                ]);
            }
        });
    </script>
</body>

</html>