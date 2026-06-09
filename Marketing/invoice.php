<?php
// Include session configuration
require_once 'include/session_config.php';

// Check if user is logged in and has access, redirect if not
requireLoginAndAccess('invoice.php');

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

// Function to format currency
function formatCurrency($amount)
{
    return '₹' . number_format((float)$amount, 2, '.', ',');
}

// Function to format dates nicely
function formatDatePretty($dateStr)
{
    if (!$dateStr) return '';
    $d = date_create($dateStr);
    if (!$d) return htmlspecialchars($dateStr);
    $out = date_format($d, 'j M Y');
    return str_replace('Sep', 'Sept', $out);
}

$quotation_data = null;
$bank_details = null;
$products = [];
$summary_db = null;
$error_message = '';

// --- CALCULATION VARIABLES ---
$calc_gross = 0;       // Subtotal before discount
$calc_discount_val = 0; // Total Discount amount
$calc_net = 0;         // Net Taxable Value
$calc_gst_total = 0;   // Total GST amount
$calc_grand_total = 0; // Final Payable
$global_discount_percent = 0;

// GST Breakdown Array: [ '18' => ['amount' => 100, 'items' => [1, 2]] ]
$gst_breakdown = [];

if (isset($_GET['quotation_id']) && is_numeric($_GET['quotation_id'])) {
    $quotation_id = (int)$_GET['quotation_id'];

    // 1. Fetch quotation details
    $sql_quotation = "SELECT * FROM quotations WHERE id = ?";
    $stmt_quotation = $conn->prepare($sql_quotation);
    if ($stmt_quotation) {
        $stmt_quotation->bind_param("i", $quotation_id);
        $stmt_quotation->execute();
        $result_quotation = $stmt_quotation->get_result();
        $quotation_data = $result_quotation->fetch_assoc();
        $stmt_quotation->close();
    } else {
        $error_message .= "Error preparing quotation statement: " . $conn->error . "<br>";
    }

    // 2. Fetch Lead details (only if client_id exists)
    $lead_details = null;

    if (!empty($quotation_data['client_id'])) {
        $stmt = $conn->prepare("SELECT company_name, gst_number FROM leads WHERE id = ?");
        $stmt->bind_param("i", $quotation_data['client_id']);
        $stmt->execute();
        $lead_details = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    // 2. Fetch bank details
    $sql_bank = "SELECT * FROM bank_details WHERE quotation_id = ?";
    $stmt_bank = $conn->prepare($sql_bank);
    if ($stmt_bank) {
        $stmt_bank->bind_param("i", $quotation_id);
        $stmt_bank->execute();
        $result_bank = $stmt_bank->get_result();
        $bank_details = $result_bank->fetch_assoc();
        $stmt_bank->close();
    }

    // 3. Fetch products and perform calculations
    $sql_products = "SELECT *, 
                        (unit_price * quantity) as subtotal,
                        ((unit_price * quantity) * (1 - COALESCE(discount_percent, 0) / 100)) as net_amount,
                        (((unit_price * quantity) * (1 - COALESCE(discount_percent, 0) / 100)) * COALESCE(gst_percent, 18) / 100) as gst_amount
                     FROM productss WHERE quotation_id = ?";
    $stmt_products = $conn->prepare($sql_products);
    if ($stmt_products) {
        $stmt_products->bind_param("i", $quotation_id);
        $stmt_products->execute();
        $result_products = $stmt_products->get_result();

        $item_counter = 1; // To track S.No for GST breakdown
        while ($row = $result_products->fetch_assoc()) {
            $products[] = $row;

            // Accumulate global totals
            $calc_gross += $row['subtotal'];
            $calc_net   += $row['net_amount'];
            $calc_gst_total += $row['gst_amount'];

            // --- GST BREAKDOWN LOGIC ---
            $gst_rate = (float)($row['gst_percent'] ?? 18); // Default to 18% if null

            // Initialize array key if not exists
            if (!isset($gst_breakdown[$gst_rate])) {
                $gst_breakdown[$gst_rate] = [
                    'amount' => 0,
                    'items' => []
                ];
            }

            // Add amount and item number
            $gst_breakdown[$gst_rate]['amount'] += $row['gst_amount'];
            $gst_breakdown[$gst_rate]['items'][] = $item_counter;

            $item_counter++;
        }
        $stmt_products->close();
    }

    // Sort GST breakdown (lowest percentage first, e.g., 5% then 18%)
    ksort($gst_breakdown);

    // 4. Calculate Final Totals
    $calc_discount_val = $calc_gross - $calc_net;
    $calc_grand_total  = $calc_net + $calc_gst_total;

    // Calculate effective discount percentage for display
    if ($calc_gross > 0) {
        $global_discount_percent = ($calc_discount_val / $calc_gross) * 100;
    }

    // 5. Fallback for Legacy Summary
    $sql_summary = "SELECT * FROM summary WHERE quotation_id = ?";
    $stmt_summary = $conn->prepare($sql_summary);
    if ($stmt_summary) {
        $stmt_summary->bind_param("i", $quotation_id);
        $stmt_summary->execute();
        $summary_db = $stmt_summary->get_result()->fetch_assoc();
        $stmt_summary->close();
    }

    if (empty($products) && $summary_db) {
        $calc_grand_total = $summary_db['grand_total'];
    }

    if (!$quotation_data && !$error_message) {
        $error_message = "No quotation found with the provided ID.";
    }
} else {
    $error_message = "Invalid quotation ID provided.";
}

$conn->close();

// Helper for number_to_words_indian
function number_to_words_indian_recursive($num)
{
    $digits_1 = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
    $digits_2 = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];
    $num = (int)$num;
    if ($num < 20) return $digits_1[$num];
    return trim($digits_2[floor($num / 10)] . " " . $digits_1[$num % 10]);
}

function number_to_words_indian($num)
{
    $num = (float)$num;
    $no = floor($num);
    $point = round(($num - $no) * 100);
    $words_arr = [];
    if ($no == 0) {
        $words_arr[] = "Zero";
    } else {
        if ($no >= 10000000) {
            $words_arr[] = number_to_words_indian_recursive(floor($no / 10000000)) . " Crore";
            $no %= 10000000;
        }
        if ($no >= 100000) {
            $words_arr[] = number_to_words_indian_recursive(floor($no / 100000)) . " Lakh";
            $no %= 100000;
        }
        if ($no >= 1000) {
            $words_arr[] = number_to_words_indian_recursive(floor($no / 1000)) . " Thousand";
            $no %= 1000;
        }
        if ($no >= 100) {
            $words_arr[] = number_to_words_indian_recursive(floor($no / 100)) . " Hundred";
            $no %= 100;
        }
        if ($no > 0) {
            $words_arr[] = number_to_words_indian_recursive($no);
        }
    }
    $result = ucwords(implode(" ", array_filter($words_arr)));
    if ($point > 0) {
        $result .= " and " . ucwords(number_to_words_indian_recursive($point)) . " Paise";
    }
    return $result . " Only.";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation Invoice</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://use.typekit.net/doa1vzk.css">
    <link rel="icon" type="image/x-icon" href="https://station.cms.tuckermotors.com/asset/images/favicon.ico">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "aptos-narrow", sans-serif;
            background-color: #f0f2f5;
            color: #333333;
            font-size: 14px;
            padding: 40px;
        }

        .quotation-container {
            width: 210mm;
            margin: 0 auto 40px auto;
            background: #ffffff;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .page-1,
        .page-2 {
            display: flex;
            flex-direction: column;
            min-height: 297mm;
            position: relative;
        }

        main.quotation {
            flex-grow: 1;
            padding: 0 30px 40px 30px;
        }

        header.new-header-container,
        footer.quotation-footer {
            flex-shrink: 0;
        }

        /* Header */
        .new-header-container {
            display: flex;
            align-items: flex-start;
            width: 100%;
            color: #ffffff;
            margin-bottom: 20px;
        }

        .logo-area {
            background-color: #1d2635;
            flex-shrink: 0;
            width: 35%;
            display: flex;
            align-items: center;
            padding: 10px 20px;
            border-bottom-right-radius: 80px;
            position: relative;
            z-index: 5;
            height: 100px;
        }

        .logo-area img {
            max-height: 85px !important;
            width: auto;
        }

        .info-area {
            background-color: #e83e4d;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            border-bottom-left-radius: 80px;
            margin-left: -80px;
            padding-left: 90px;
            padding-right: 20px;
            position: relative;
            z-index: 5;
            height: 75px;
            padding-top: 5px;
            padding-bottom: 5px;
            letter-spacing: 1px;
        }

        .info-area .company-title {
            font-size: 17px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .info-area .company-subtitle,
        .info-area .company-description {
            font-size: 11px;
            line-height: 1.3;
            font-weight: 600;
        }

        /* Content */
        .quotation-details {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .from-section {
            flex: 1;
            margin-right: 30px;
        }

        .quotation-from-label {
            color: #e83e4d;
            font-weight: 700;
            font-size: 12px;
            margin-bottom: 8px;
        }

        .company-name {
            font-weight: bold;
            color: #1a1a2e;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .company-address {
            color: #555;
            font-size: 13px;
            line-height: 1.4;
            margin-bottom: 5px;
        }

        .quotation-box {
            min-width: 300px;
        }

        .quotation-title {
            font-size: 25px;
            font-weight: bold;
            color: #1a1a2e;
            text-transform: uppercase;
            margin-bottom: 15px;
            text-align: right;
        }

        .quotation-meta {
            font-size: 14px;
            color: #555;
            text-align: right;
        }

        .quotation-meta p {
            margin: 5px 0;
        }

        .subject-section {
            font-size: 14px;
            margin-top: 5px;
            margin-bottom: 10px;
        }

        /* Table */
        .product-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #e9ecef;
        }

        .product-table th {
            background: #e83e4d;
            color: #ffffff;
            padding: 15px 12px;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 14px;
            border: none;
        }

        .product-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
            vertical-align: middle;
            color: #3f3f3fff;
            font-weight: 700;
        }

        .product-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        /* Summary */
        .amount-summary-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
        }

        .amount-section {
            flex: 1;
        }

        .summary-container {
            flex: 0 0 320px;
        }

        .amount-in-words {
            font-weight: bold;
            color: #1a1a2e;
            font-size: 14px;
            padding-top: 20px;
        }

        .amount-in-words strong {
            color: #e83e4d;
        }

        .summary-box {
            width: 320px;
            border: 1px solid #f7f7f7;
            border-radius: 4px;
            overflow: hidden;
            font-size: 14px;
            font-weight: 600;
            color: #222938;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            border-bottom: 1px solid #e9ecef;
            font-weight: 700;
            font-size: 14px;
            align-items: center;
        }

        .summary-row.grand-total {
            background: #e83e4d;
            color: #ffffff;
            font-size: 14px;
            font-weight: bold;
            padding: 15px;
        }

        /* Other Sections */
        .section {
            margin-bottom: 20px;
        }

        .section-title {
            color: #e83e4d;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .section-content {
            color: #555;
            font-size: 14px;
            line-height: 1.5;
            font-weight: 600;
        }

        .section-content ul {
            padding-left: 20px;
        }

        .bank-details p {
            margin: 5px 0;
            font-size: 14px;
        }

        p {
            margin-bottom: 0rem;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 0;
            opacity: 0.1;
            pointer-events: none;
            width: 500px;
            height: 500px;
            background: url('https://tuckermotors.com/assets/logo-marketing.png') no-repeat center center;
            background-size: contain;
        }

        /* Footer */
        .quotation-footer {
            z-index: 10;
            width: 100%;
        }

        .footer-copyright {
            text-align: center;
            padding: 10px 30px;
            font-size: 10px;
            color: #555;
            background-color: #fff;
        }

        .footer-details {
            background-color: #e83e4d;
            color: #ffffff;
            padding: 15px 30px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
        }

        .footer-line {
            align-items: center;
            flex-wrap: wrap;
        }

        .footer-line:not(:last-child) {
            margin-bottom: 8px;
        }

        /* Action Icons */
        .action-icons {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 15px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.95);
            padding: 10px 15px;
            border-radius: 25px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .action-icon {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
            font-size: 16px;
            color: #ffffff;
        }

        .icon-back {
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }

        .icon-edit {
            background: linear-gradient(135deg, #28a745, #20c997);
        }

        .icon-download {
            background: linear-gradient(135deg, #e83e4d, #dc3545);
        }

        .action-buttons {
            text-align: center;
            padding: 20px;
        }

        .btn {
            padding: 10px 20px;
            margin: 0 10px;
            border-radius: 5px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            color: #ffffff;
        }

        .btn-print {
            background: #e83e4d;
        }

        .btn-back {
            background: #6c757d;
        }

        .page-1 {
            page-break-after: always;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                -webkit-print-color-adjust: exact !important;
                color-adjust: exact !important;
            }

            @page {
                size: A4 portrait;
                margin: 0;
            }

            .quotation-container {
                box-shadow: none !important;
                margin: 0 !important;
                width: 100% !important;
            }

            .print-hidden {
                display: none !important;
            }

            .page-1,
            .page-2 {
                min-height: 297mm;
                page-break-after: always;
            }

            .page-2 {
                page-break-after: avoid;
            }

            main.quotation {
                padding: 0 30px;
                padding-bottom: 80px !important;
            }

            .quotation-footer {
                position: absolute;
                bottom: 0;
                left: 0;
                width: 100%;
            }

            .watermark {
                width: 600px;
                height: 600px;
            }

            .new-header-container,
            .logo-area,
            .info-area,
            .product-table th,
            .summary-row.grand-total,
            .footer-details {
                -webkit-print-color-adjust: exact !important;
            }

            .logo-area {
                background-color: #1d2635 !important;
            }

            .info-area,
            .product-table th,
            .summary-row.grand-total,
            .footer-details {
                background-color: #e83e4d !important;
            }

            .section-title,
            .quotation-from-label,
            .amount-in-words strong {
                color: #e83e4d !important;
            }

            .pt {
                padding-top: 50px;
            }

            .quotation-footer {
                position: absolute;
                bottom: 0;
                top: 1290px !important;
                left: 0;
                width: 100%;
            }

            .logo-area img {
                padding-left: 30px;
                padding-bottom: 10px;
            }
        }

        /* Allow text wrapping in Select2 selected value */
        .select2-container .select2-selection--single {
            height: auto !important;
            /* allow height to grow */
        }

        .select2-container .select2-selection__rendered {
            white-space: normal !important;
            /* enable wrap */
            word-wrap: break-word !important;
            overflow-wrap: anywhere !important;
            line-height: 1.4;
            padding-right: 30px;
            /* keep space for arrow */
        }
    </style>
</head>

<body>
    <div class="action-icons print-hidden">
        <div class="action-icon icon-back" onclick="goBack()" title="Go Back"><i class="fas fa-arrow-left"></i></div>
        <!-- <div class="action-icon icon-edit" onclick="editQuotation()" title="Edit Quotation"><i class="fas fa-edit"></i></div> -->
        <div class="action-icon icon-download" onclick="printQuotation()" title="Print / Save PDF"><i class="fas fa-print"></i></div>
    </div>

    <div class="quotation-container">
        <?php if ($error_message) : ?>
            <div class="alert alert-danger m-4"><?php echo $error_message; ?></div>
        <?php else : ?>
            <div class="page-1">
                <div class="watermark"></div>
                <header class="new-header-container">
                    <div class="logo-area">
                        <img src="https://tuckermotors.com/assets/invoice.png" alt="Tucker EV Chargers">
                    </div>
                    <div class="info-area">
                        <div class="company-title">TUCKER MOTORS PRIVATE LIMITED</div>
                        <div class="company-subtitle">Design & Manufacturing Excellence | ISO 9001:2015 Certified | NABL Accredited</div>
                        <div class="company-description">Manufacturer Of Electric Vehicle Dc Fast Charger and Ac Slow Charger</div>
                    </div>
                </header>

                <main class="quotation">
                    <div class="quotation-details">
                        <div class="from-section">
                            <div class="quotation-from-label">Quotation To:</div>
                            <!-- <div class="company-name"><?php echo htmlspecialchars($quotation_data['client_name'] ?? 'N/A'); ?></div> -->
                            <div class="company-name"><?php echo htmlspecialchars($quotation_data['salutation'] ?? 'Dear Sir/Madam,') ?></div>
                            <?php if (!empty($lead_details['company_name'])): ?>
                                <div class="company-name">
                                    Company : <?php echo htmlspecialchars($lead_details['company_name']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($lead_details['gst_number'])): ?>
                                <div class="company-address">
                                    GST : <?php echo htmlspecialchars($lead_details['gst_number']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="company-address" style="width:180px; word-wrap:break-word;"><?php echo nl2br(htmlspecialchars($quotation_data['client_address'] ?? '')); ?></div>
                        </div>
                        <div class="quotation-box">
                            <div class="quotation-title">Quotation</div>
                            <div class="quotation-meta">
                                <p><strong>Quotation No:</strong> <?php echo htmlspecialchars($quotation_data['quotation_no'] ?? 'N/A'); ?></p>
                                <p><strong>Quotation Date:</strong> <?php echo formatDatePretty($quotation_data['date']); ?></p>
                                <p><strong>Quotation By:</strong> <?= [3 => 'Srihari', 4 => 'Kannan', 5 => 'Amsathvani'][$quotation_data['parent_id']] ?? 'Unknown'; ?></p>
                                <p><strong>Valid Till:</strong> <?php echo formatDatePretty($quotation_data['valid_till']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="subject-section">
                        <p><strong><?php echo htmlspecialchars($quotation_data['salutation'] ?? 'Dear Sir/Madam,') ?></strong></p>
                        <p><strong>Subject:</strong> <?php echo htmlspecialchars($quotation_data['subject'] ?? ''); ?></p>
                        <p style="margin-top:5px;"><?php echo htmlspecialchars($quotation_data['introduction'] ?? ''); ?></p>
                    </div>

                    <!-- PRODUCT TABLE: GST Column Removed -->
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th>S.No.</th>
                                <th width="50%">Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Discount</th>
                                <!-- <th>GST</th>  <-- REMOVED -->
                                <th>Total</th> <!-- Renamed: This is (Price*Qty - Discount) -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($products)) : $sno = 1;
                                foreach ($products as $p) : ?>
                                    <tr>
                                        <td><?php echo str_pad($sno++, 2, '0', STR_PAD_LEFT); ?></td>
                                        <td><?php echo htmlspecialchars($p['product_name']); ?></td>
                                        <td><?php echo formatCurrency($p['unit_price']); ?></td>
                                        <td><?php echo htmlspecialchars($p['quantity']); ?></td>
                                        <td><?php echo (floatval($p['discount_percent'] ?? 0) > 0) ? htmlspecialchars($p['discount_percent']) . '%' : '-'; ?></td>
                                        <!-- GST Column removed from body -->
                                        <td><?php echo formatCurrency($p['net_amount']); ?></td>
                                    </tr>
                                <?php endforeach;
                            else : ?>
                                <tr>
                                    <td colspan="6" class="text-center p-4">No products found for this quotation.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- SUMMARY SECTION WITH DYNAMIC GST BREAKDOWN -->
                    <div class="amount-summary-container">
                        <div class="amount-section">
                            <div class="amount-in-words">
                                <strong>Amount (in words):</strong> <br>
                                <span style="font-size: 14px; font-weight: 600; line-height: 1.6;"><?php echo number_to_words_indian($calc_grand_total); ?></span>
                            </div>
                        </div>
                        <div class="summary-container">
                            <div class="summary-box">
                                <!-- Subtotal (Gross) -->
                                <div class="summary-row">
                                    <span>Subtotal (Before Discount)</span>
                                    <span><?php echo formatCurrency($calc_gross); ?></span>
                                </div>

                                <!-- Discount Row -->
                                <?php if ($calc_discount_val > 0): ?>
                                    <div class="summary-row" style="color: #dc3545;">
                                        <!-- (<?php echo number_format($global_discount_percent, 2); ?>%) -->
                                        <span>Total Discount </span>
                                        <span>– <?php echo formatCurrency($calc_discount_val); ?></span>
                                    </div>
                                <?php endif; ?>

                                <!-- Net Taxable -->
                                <div class="summary-row">
                                    <span>Net Taxable Value</span>
                                    <span><?php echo formatCurrency($calc_net); ?></span>
                                </div>

                                <!-- Dynamic GST Breakdown Rows with Items List -->
                                <?php foreach ($gst_breakdown as $rate => $data): ?>
                                    <div class="summary-row">
                                        <span>
                                            GST @ <?php echo $rate; ?>%
                                            <?php if (!empty($data['items'])): ?>
                                                <span style="font-weight:normal; font-size:11px; color:#666;">
                                                    (Items: <?php echo implode(', ', $data['items']); ?>)
                                                </span>
                                            <?php endif; ?>
                                        </span>
                                        <span><?php echo formatCurrency($data['amount']); ?></span>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Grand Total -->
                                <div class="summary-row grand-total">
                                    <span>Grand Total (Payable Amount)</span>
                                    <span><?php echo formatCurrency($calc_grand_total); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- END SUMMARY SECTION -->

                </main>

                <footer class="quotation-footer">
                    <div class="footer-copyright">© <?php echo date('Y'); ?> Tucker Motors Private Limited. All rights reserved. Unauthorized use, reproduction, or distribution of this document is prohibited.</div>
                    <div class="footer-details">
                        <div class="footer-line">
                            <span>info@tuckermotors.com |</span>
                            <span>+91 82200 57752 , 82200 57754 | </span>
                            <span>www.tuckermotors.com</span>
                        </div>
                        <div class="footer-line">
                            <span>GST NO: 33AAHCT1842P1Z0 |</span>
                            <span>CIN NO: U34300TN2019PTC127580 |</span>
                            <span>Corporate ADDR: 159, C1/1, Kamarajar Salai,Madurai - 625 009</span>
                        </div>
                    </div>
                </footer>
            </div>

            <div class="page-2">
                <div class="watermark"></div>
                <header class="new-header-container">
                    <div class="logo-area"><img src="https://tuckermotors.com/images/logo.png" alt="Tucker EV Chargers"></div>
                    <div class="info-area">
                        <div class="company-title">TUCKER MOTORS PRIVATE LIMITED</div>
                        <div class="company-subtitle">Design & Manufacturing Excellence | ISO 9001:2015 Certified | NABL Accredited</div>
                        <div class="company-description">Manufacturer Of Electric Vehicle Dc Fast Charger and Ac Slow Charger</div>
                    </div>
                </header>

                <main class="quotation">
                    <div class="pt">
                        <div class="section">
                            <div class="section-title">Terms and Conditions:</div>
                            <div class="section-content">
                                <?php
                                if (!empty($quotation_data['terms_conditions'])) {
                                    echo nl2br(htmlspecialchars($quotation_data['terms_conditions']));
                                } else {
                                ?>
                                    <ul>
                                        <li>Prices quoted are exclusive of GST unless otherwise specified. Quotation valid for 30 days.</li>
                                        <li>70% advance & 30% before dispatch. Dispatch after full payment only.</li>
                                        <li>Supply includes EV charger as per quotation. Installation and accessories excluded unless specified.</li>
                                        <li>Delivery: DC Fast Charger – 3–4 weeks | AC Charger – 1–2 weeks from order confirmation and advance payment.</li>
                                        <li><?php echo htmlspecialchars($quotation_data['year'] ?? '1'); ?> - year warranty against manufacturing defects only.</li>
                                        <li>AMC & Support: Annual Maintenance Contract (AMC) available after the warranty period.</li>
                                        <li>Orders once confirmed cannot be cancelled or returned.</li>
                                        <li>Liability limited to invoice value.</li>
                                        <li>Governed by Indian law. Jurisdiction: Madurai, Tamil Nadu.</li>
                                    </ul>
                                <?php } ?>
                            </div>
                        </div>
                        <h5 style="color: #e83e4d;font-weight: bold;margin-bottom: 8px;text-transform: uppercase;"> Scope Responsibility </h5>
                        <div class="section">
                            <div class="section-title">Customer Scope:</div>
                            <div class="section-content">
                                <ul>
                                    <li>Major civil & electrical works, transformer and power cable from distribution panel to charger.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="section">
                            <div class="section-title">Tucker Motors Scope:</div>
                            <div class="section-content">
                                <ul>
                                    <li>Supply of equipment, transportation (as per quoted amount), and basic staff training on preventive care.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="section">
                            <div class="section-title">Note:</div>
                            <div class="section-content">
                                <?php echo nl2br(htmlspecialchars($quotation_data['additional_notes'] ?? 'The white labelling cost of ₹50,000 plus 18% GST, it has been waived off on mutual understanding to foster a good relationship and long-term collaboration. Any label or sticker can be added to the front of the panel, which will be left blank for this purpose if required, we can also carry out the finishing on behalf of the customer at actual cost.')); ?>
                            </div>

                        </div>
                        <div class="section bank-details">
                            <div class="section-title">Bank Details</div>
                            <div class="section-content"><?php if ($bank_details) : ?><p><strong>Bank Name:</strong> <?php echo htmlspecialchars($bank_details['bank_name']); ?></p>
                                    <p><strong>Account Number:</strong> <?php echo htmlspecialchars($bank_details['account_number']); ?></p>
                                    <p><strong>IFSC Code:</strong> <?php echo htmlspecialchars($bank_details['ifsc_code']); ?></p>
                                    <p><strong>Branch Name:</strong> <?php echo htmlspecialchars($bank_details['branch_name']); ?></p><?php else : ?><p>Bank details not available.</p><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </main>

                <footer class="quotation-footer">
                    <div class="footer-copyright">© <?php echo date('Y'); ?> Tucker Motors Private Limited. All rights reserved. Unauthorized use, reproduction, or distribution of this document is prohibited.</div>
                    <div class="footer-details">
                        <div class="footer-line">
                            <span>info@tuckermotors.com |</span>
                            <span>+91 82200 57752, 82200 57754 | </span>
                            <span>www.tuckermotors.com</span>
                        </div>
                        <div class="footer-line">
                            <span>GST NO: 33AAHCT1842P1Z0 |</span>
                            <span>CIN NO: U34300TN2019PTC127580 |</span>
                            <span>Corporate ADDR: 159, C1/1, Kamarajar Salai,Madurai - 625 009</span>
                        </div>
                    </div>
                </footer>
            </div>
        <?php endif; ?>
    </div>

    <div class="action-buttons print-hidden">
        <button onclick="printQuotation()" class="btn btn-print"><i class="fas fa-print"></i> Print Quotation</button>
        <a href="Add_Quatation.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Go Back to Form</a>
    </div>

    <script>
        function goBack() {
            window.location.href = "Quotation.php";
        }

        function printQuotation() {
            window.print();
        }

        function editQuotation() {
            const quotationId = new URLSearchParams(window.location.search).get('quotation_id');
            if (quotationId) {
                window.location.href = `#!`;
            } else {
                alert('No quotation ID found.');
            }
        }
    </script>
</body>

</html>