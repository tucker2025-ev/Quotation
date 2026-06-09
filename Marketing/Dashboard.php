<?php
// Include session configuration 
require_once 'include/session_config.php';
// Check if user is logged in and has access, redirect if not
requireLoginAndAccess('Dashboard.php');

function formatINR($number)
{
    $number = floor((float)$number);
    if ($number < 1000) return '₹' . $number;
    $number = (string)$number;
    $last3 = substr($number, -3);
    $rest  = substr($number, 0, -3);
    $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
    return '₹' . $rest . ',' . $last3;
}

$host = "15.207.37.132";
$user = "cloud";
$pass = "TUCKER_ser_sql";
$db   = "marketing_new";

try {
    $con = new mysqli($host, $user, $pass, $db);
    $con->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// -------------------- FETCH LEADS BY STATUS --------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'fetch_by_leads') {
    $statusId = (int) $_POST['statusId'];
    if ($_SESSION["master_id"] == 2) {
        $sql = "SELECT l.id, l.full_name, l.email, l.phone_number, l.address,c.type_name AS customer_type,s.status_name AS status,src.source_name AS source,l.company_name, l.gst_number, l.notes FROM leads l LEFT JOIN customer_types c ON l.customer_type_id = c.id LEFT JOIN lead_statuses s ON l.status_id = s.id LEFT JOIN lead_sources src ON l.source_id = src.id WHERE l.status_id = ? ORDER BY l.id DESC";
    } else {
        $sql = "SELECT l.id, l.full_name, l.email, l.phone_number, l.address,c.type_name AS customer_type,s.status_name AS status,src.source_name AS source,l.company_name, l.gst_number, l.notes FROM leads l LEFT JOIN customer_types c ON l.customer_type_id = c.id LEFT JOIN lead_statuses s ON l.status_id = s.id LEFT JOIN lead_sources src ON l.source_id = src.id WHERE l.status_id = ? and l.parent_id = " . $_SESSION["user_id"] . " ORDER BY l.id DESC";
    }
    try {
        $stmt = $con->prepare($sql);
        $stmt->bind_param("i", $statusId);
        $stmt->execute();
        $result = $stmt->get_result();
        $leads = [];
        while ($row = $result->fetch_assoc()) $leads[] = $row;
        echo json_encode($leads);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
    $con->close();
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'fetch_by_quotations') {
    if ($_SESSION["master_id"] == 2) {
        $sql = "SELECT * FROM quotations q INNER JOIN summary s ON s.quotation_id = q.id ORDER BY q.created_at DESC";
    } else {
        $sql = "SELECT * FROM quotations q INNER JOIN summary s ON s.quotation_id = q.id where q.parent_id = " . $_SESSION["user_id"] . " ORDER BY q.created_at DESC";
    }
    try {
        $stmt = $con->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $quotations = [];
        while ($row = $result->fetch_assoc()) $quotations[] = $row;
        echo json_encode($quotations);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
    $con->close();
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'fetch_by_closed') {
    if ($_SESSION["master_id"] == 2) {
        $sql = "SELECT * FROM quotations q INNER JOIN summary s ON s.quotation_id = q.id where s.payment_status = 'Y' ORDER BY q.created_at DESC";
    } else {
        $sql = "SELECT * FROM quotations q INNER JOIN summary s ON s.quotation_id = q.id where s.payment_status = 'Y' and q.parent_id = " . $_SESSION["user_id"] . " ORDER BY q.created_at DESC";
    }
    try {
        $stmt = $con->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        $quotations = [];
        while ($row = $result->fetch_assoc()) $quotations[] = $row;
        echo json_encode($quotations);
    } catch (Exception $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
    $con->close();
    exit;
}

// -------------------- FETCH LEAD STATUS COUNTS --------------------
$statusCounts = [];
try {
    if ($_SESSION["master_id"] == 2) {
        $sql = "SELECT 'Total Enquiries' AS status_name, COUNT(*) AS value FROM leads UNION ALL SELECT 'Quotations Issued', COUNT(*) FROM quotations WHERE order_status IN ('N','Y') UNION ALL SELECT 'Total Quoted Amount', IFNULL(SUM(s.grand_total), 0) FROM summary s JOIN quotations q ON s.quotation_id = q.id WHERE q.order_status IN ('N','Y')UNION ALL SELECT 'Total Ordered Amount', IFNULL(SUM(s.grand_total), 0) FROM summary s JOIN quotations q ON s.quotation_id = q.id WHERE q.order_status IN ('Y') UNION ALL SELECT 'Amount Collected', IFNULL(SUM(p.amount), 0) FROM payments p JOIN quotations q ON p.related_id = q.id WHERE q.order_status IN ('Y') UNION ALL SELECT 'Outstanding Amount', GREATEST(0,(SELECT IFNULL(SUM(s.grand_total), 0) FROM summary s JOIN quotations q ON s.quotation_id = q.id WHERE q.order_status = 'Y') - (SELECT IFNULL(SUM(p.amount), 0) FROM payments p JOIN quotations q ON p.related_id = q.id WHERE q.order_status = 'Y')) UNION ALL SELECT 'Confirmed Orders', COUNT(*) FROM summary s JOIN quotations q ON s.quotation_id = q.id WHERE s.payment_status = 'Y' AND q.order_status IN ('N','Y');";
    } else {
        $sql = "SELECT 'Total Enquiries' AS status_name, CAST(COUNT(*) AS CHAR) AS value FROM leads WHERE parent_id = " . $_SESSION["user_id"] . " UNION ALL SELECT 'Quotations Issued', CAST(COUNT(*) AS CHAR) FROM quotations WHERE parent_id = " . $_SESSION["user_id"] . " UNION ALL SELECT 'Total Quoted Amount', IFNULL(SUM(s.grand_total),0) FROM summary s JOIN quotations q ON q.id = s.quotation_id WHERE q.parent_id = " . $_SESSION["user_id"] . " UNION ALL SELECT 'Amount Collected', IFNULL(SUM(p.amount),0) FROM payments p JOIN quotations q ON q.id = p.related_id WHERE q.parent_id = " . $_SESSION["user_id"] . " UNION ALL SELECT 'Outstanding Amount', GREATEST(0,(SELECT IFNULL(SUM(s.grand_total),0) FROM summary s JOIN quotations q ON q.id = s.quotation_id WHERE q.parent_id = " . $_SESSION["user_id"] . " AND q.order_status = 'Y') -  (SELECT IFNULL(SUM(p.amount),0) FROM payments p JOIN quotations q ON q.id = p.related_id WHERE q.parent_id = " . $_SESSION["user_id"] . " AND q.order_status = 'Y'))UNION ALL SELECT 'Confirmed Orders', COUNT(*) FROM summary s JOIN quotations q ON q.id = s.quotation_id WHERE s.payment_status = 'Y' AND q.parent_id = " . $_SESSION["user_id"] . " and q.order_status in ('N','Y')";
    }

    $result = $con->query($sql);
    $statusIdMap = [
        'Total Enquiries' => 1,
        'Quotations Issued' => 2,
        'Total Quoted Amount' => 3,
        'Total Ordered Amount' => 3,
        'Amount Collected' => 4,
        'Outstanding Amount' => 5,
        'Confirmed Orders' => 6
    ];

    while ($row = $result->fetch_assoc()) {
        $row['status_id'] = $statusIdMap[$row['status_name']] ?? 0;
        $statusCounts[] = $row;
    }
} catch (Exception $e) {
}


// Check if this is an API request For Graph
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'get_monthly_quotes') {
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,COUNT(*) as total, '' as parentID FROM quotations GROUP BY month ORDER BY month ASC";
        $result = $con->query($sql);
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($_POST['action'] === 'get_daily_quotes') {
        $month = $_POST['month'];
        $sql = "SELECT DATE(created_at) as day,COUNT(*) as total FROM quotations WHERE DATE_FORMAT(created_at, '%Y-%m') = ? GROUP BY day ORDER BY day ASC";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("s", $month);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    if ($_POST['action'] === 'get_quote_details') {
        $day = $_POST['day'];
        $sql = "SELECT * FROM quotations WHERE DATE(created_at) = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("s", $day);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }
}

//Every Leads Based DETAILS DATA (ALWAYS RUN)
$sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,COUNT(*) AS total_leads,GROUP_CONCAT(full_name SEPARATOR ', ') AS names FROM leads where source_id not in ('8') GROUP BY month ORDER BY month ASC";
$stmt = $con->prepare($sql);
$stmt->execute();
$lead_result = $stmt->get_result();

$labels = [];
$data   = [];
$names = [];
while ($row = $lead_result->fetch_assoc()) {
    $labels[] = $row['month'];
    $data[]   = (int)$row['total_leads'];
    $names[] = $row['names'];
}

$sql = "SELECT DATE_FORMAT(q.created_at, '%b %Y') AS month,IFNULL(SUM(s.grand_total), 0) AS total_quoted_amount,IFNULL(SUM(CASE WHEN q.order_status = 'Y' THEN s.grand_total END), 0) AS total_ordered_amount, IFNULL(SUM(CASE WHEN q.order_status = 'Y' THEN p.amount END), 0) AS amount_collected FROM quotations q LEFT JOIN summary s ON s.quotation_id = q.id LEFT JOIN payments p ON p.related_id = q.id GROUP BY DATE_FORMAT(q.created_at, '%Y-%m') ORDER BY DATE_FORMAT(q.created_at, '%Y-%m') ASC";
$result = $con->query($sql);

$months = [];
$totalQuoted = [];
$totalOrdered = [];
$amountCollected = [];

while ($row = $result->fetch_assoc()) {
    $months[] = $row['month'];
    $totalQuoted[] = (float)$row['total_quoted_amount'];
    $totalOrdered[] = (float)$row['total_ordered_amount'];
    $amountCollected[] = (float)$row['amount_collected'];
}
$con->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Tucker Motors</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="https://station.cms.tuckermotors.com/asset/images/favicon.ico">

    <!-- DevExtreme CSS -->
    <link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/23.2.5/css/dx.light.css">

    <style>
        :root {
            --primary-bg: #F4F7FE;
            --card-bg: #FFFFFF;
            --text-main: #2B3674;
            --text-muted: #A3AED0;
            --border-color: #E0E5F2;

            --grad-indigo: linear-gradient(135deg, #868CFF 0%, #4318FF 100%);
            --grad-green: linear-gradient(135deg, #05CD99 0%, #04A67B 100%);
            --grad-amber: linear-gradient(135deg, #FFB547 0%, #FF9020 100%);
            --grad-rose: linear-gradient(135deg, #FF6A88 0%, #FF3D64 100%);
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--primary-bg);
            color: var(--text-main);
        }

        /* --- Layout & Sidebar Adjustments --- */
        .main-content {
            margin-left: 260px;
            /* Adjust based on your sidebar width */
            padding: 30px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-main);
            margin-top: 0;
            margin-bottom: 25px;
            letter-spacing: -0.5px;
        }

        .mobile-header {
            display: none;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 25px;
            background: var(--card-bg);
            padding: 15px 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.02);
        }

        .menu-toggle-btn {
            background: var(--primary-bg);
            border: none;
            color: var(--text-main);
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
        }

        /* --- KPI Cards Grid --- */
        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            cursor: pointer;
            border: 1px solid transparent;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-color: var(--border-color);
        }

        .icon-box {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }

        .bg-indigo {
            background: var(--grad-indigo);
        }

        .bg-green {
            background: var(--grad-green);
        }

        .bg-amber {
            background: var(--grad-amber);
        }

        .bg-rose {
            background: var(--grad-rose);
        }

        .stat-info {
            flex: 1;
        }

        .stat-info h3 {
            margin: 0 0 6px 0;
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-info .value {
            font-size: 26px;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1.1;
        }

        /* --- Modern Card Containers for Charts --- */
        .chart-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);
            padding: 24px;
            margin-bottom: 30px;
            border: 1px solid #fff;
            /* Replaced on hover */
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
        }

        .chart-controls {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--primary-bg);
            padding: 6px;
            border-radius: 10px;
        }

        .btn-icon {
            cursor: pointer;
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            background: transparent;
            border: none;
            transition: all 0.2s;
        }

        .btn-icon:hover {
            background: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        /* Override DevExtreme Chart Cursor */
        #chart.pointer-on-bars .dxc-series rect {
            cursor: pointer;
        }

        /* --- Bottom Charts Grid --- */
        .bottom-charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        canvas#myChart {
            display: block;
        }

        #container {
            width: 100%;
            height: 350px;
        }

        /* --- Beautiful Modal UI --- */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(11, 20, 55, 0.6);
            backdrop-filter: blur(4px);
            z-index: 5000;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .modal-box {
            background: var(--card-bg);
            width: 100%;
            max-width: 1000px;
            /* Slightly wider to accommodate S.No comfortably */
            border-radius: 20px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            animation: modalFadeIn 0.3s ease-out;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #FAFCFE;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 20px;
            color: var(--text-main);
        }

        .close-button {
            background: #F4F7FE;
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            font-size: 20px;
            color: var(--text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: 0.2s;
        }

        .close-button:hover {
            background: #FF6A88;
            color: white;
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
        }

        .status-summary {
            background: var(--primary-bg);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
            border: 1px dashed var(--border-color);
        }

        .status-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .status-icon-large {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .status-text h3 {
            margin: 0 0 5px 0;
            font-size: 22px;
            font-weight: 700;
            color: var(--text-main);
        }

        .status-text p {
            margin: 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .status-text p#statusCount {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 5px;
        }

        /* Table Design inside modal */
        .status-leads-container {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            overflow: hidden;
        }

        .status-leads-header {
            background: #FAFCFE;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .status-leads-header h4 {
            margin: 0;
            font-size: 16px;
            color: var(--text-main);
        }

        .status-leads-table-container {
            max-height: 400px;
            overflow-y: auto;
        }

        .status-leads-table {
            width: 100%;
            border-collapse: collapse;
        }

        .status-leads-table th {
            background: #FAFCFE;
            position: sticky;
            top: 0;
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
            padding: 14px 20px;
            text-align: left;
            font-weight: 700;
            border-bottom: 1px solid var(--border-color);
        }

        .status-leads-table td {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-main);
            font-size: 14px;
        }

        .status-leads-table tbody tr:hover {
            background-color: #FAFCFE;
        }

        .no-leads-message {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-muted);
        }

        .no-leads-icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: var(--border-color);
        }

        /* --- Responsive Design --- */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .mobile-header {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .bottom-charts-grid {
                grid-template-columns: 1fr;
            }

            .modal-box {
                width: 95%;
                max-height: 90vh;
            }

            /* Responsive Table Conversion */
            .status-leads-table thead {
                display: none;
            }

            .status-leads-table tr {
                display: block;
                border-bottom: 2px solid var(--primary-bg);
                padding: 10px;
            }

            .status-leads-table td {
                display: flex;
                justify-content: space-between;
                border-bottom: none;
                padding: 8px 5px;
            }

            .status-leads-table td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--text-muted);
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <?php include 'include/sidebar.php'; ?>

    <main class="main-content">
        <!-- Mobile Header (Visible on < 1024px) -->
        <div class="mobile-header">
            <button id="mobileMenuBtn" class="menu-toggle-btn">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h2 style="margin:0; font-size: 18px; color: var(--text-main);">Dashboard</h2>
            <div style="width: 32px;"></div> <!-- Spacer -->
        </div>

        <h1 class="page-title">Overview</h1>

        <!-- KPI Grid -->
        <div class="grid-container">
            <?php foreach ($statusCounts as $status): ?>
                <?php
                $statusName  = $status['status_name'];
                $statusValue = $status['value'] ?? 0;
                $statusLower = strtolower(str_replace(' ', '-', $statusName));

                $iconClass = 'bg-indigo';
                $iconFA = 'fa-chart-pie';
                $description = 'Dashboard summary';
                $cardType = 'leads';

                switch ($statusLower) {
                    case 'total-enquiries':
                        $iconClass = 'bg-indigo';
                        $iconFA = 'fa-solid fa-chart-pie';
                        $description = 'Total Enquiries created';
                        $cardType = 'leads';
                        break;
                    case 'quotations-issued':
                        $iconClass = 'bg-amber';
                        $iconFA = 'fa-solid fa-file-invoice';
                        $description = 'Quotations Issued generated';
                        $cardType = 'quotations';
                        break;
                    case 'total-quoted-amount':
                        $iconClass = 'bg-green';
                        $iconFA = 'fa-solid fa-indian-rupee-sign';
                        $description = 'Overall quotation amount';
                        $cardType = 'total-amount';
                        break;
                    case 'amount-collected':
                        $iconClass = 'bg-green';
                        $iconFA = 'fa-solid fa-indian-rupee-sign';
                        $description = 'Amount settled';
                        $cardType = 'settlement-amount';
                        break;
                    case 'outstanding-amount':
                        $iconClass = 'bg-green';
                        $iconFA = 'fa-solid fa-indian-rupee-sign';
                        $description = 'Pending payment amount';
                        $cardType = 'pending-amount';
                        break;
                    case 'confirmed-orders':
                        $iconClass = 'bg-rose';
                        $iconFA = 'fa-solid fa-check-circle';
                        $description = 'Confirmed orders';
                        $cardType = 'confirmed-orders';
                        break;
                }
                ?>

                <div class="stat-card" data-status-id="<?php echo $status['status_id']; ?>"
                    data-card-type="<?php echo $cardType; ?>"
                    data-status-name="<?php echo htmlspecialchars($status['status_name']); ?>"
                    data-status-count="<?php echo ($cardType === 'confirmed-orders') ? $statusValue : formatINR($statusValue); ?>"
                    data-icon-class="<?php echo $iconClass; ?>"
                    data-icon-fa="<?php echo $iconFA; ?>"
                    data-description="<?php echo $description; ?>">

                    <div class="icon-box <?= $iconClass ?>">
                        <i class="<?= $iconFA ?>"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo htmlspecialchars($statusName); ?></h3>
                        <div class="value">
                            <?php
                            if ($cardType === 'confirmed-orders') {
                                echo (int)$statusValue;
                            } else {
                                echo is_numeric($statusValue) ? formatINR($statusValue) : $statusValue;
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- DevExtreme Chart Area -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title" id="devExtremeTitle">Monthly Quotations Overview</h3>
                <div class="chart-controls">
                    <!-- DevExtreme Back Button Container -->
                    <div id="backButton"></div>

                    <button class="btn-icon barAction" title="Bar Chart" onclick="checkChart('bar')">
                        <i class="fas fa-chart-bar" style="color:#05CD99; font-size: 20px;"></i>
                    </button>
                    <button class="btn-icon lineAction" title="Line Chart" onclick="checkChart('line')">
                        <i class="fas fa-chart-line" style="color:#4318FF; font-size: 20px;"></i>
                    </button>
                    <button class="btn-icon pieAction" title="Pie Chart" onclick="checkChart('pie')">
                        <i class="fas fa-chart-pie" style="color: #FF3D64; font-size: 20px;"></i>
                    </button>
                    <button class="btn-icon doughnutAction" title="Doughnut Chart" onclick="checkChart('doughnut')">
                        <i class="fa-solid fa-circle-dot" style="color: #FF9020; font-size: 20px;"></i>
                    </button>
                </div>
            </div>
            <div id="chart" style="height: 400px; width: 100%;"></div>
        </div>

        <!-- Bottom Charts (Chart.js & Highcharts) -->
        <div class="bottom-charts-grid">
            <div class="chart-card">
                <h3 class="chart-title" style="margin-bottom: 20px;">Monthly Leads</h3>
                <div id="myChartWrapper" style="width:100%; overflow-x:auto;">
                    <canvas id="myChart" height="350"></canvas>
                </div>
                <div id="leadNamesPanel" style="display:none; margin-top:16px; border:1px solid #E0E5F2; border-radius:10px; overflow:hidden;">
                    <div style="background:#F4F7FE; padding:10px 16px; display:flex; justify-content:space-between; align-items:center;">
                        <span id="leadNamesPanelTitle" style="font-size:13px; font-weight:600; color:#2B3674;"></span>
                        <button onclick="document.getElementById('leadNamesPanel').style.display='none'" style="background:none;border:none;cursor:pointer;font-size:16px;color:#A3AED0;line-height:1;">&times;</button>
                    </div>
                    <ul id="leadNamesList" style="margin:0; padding:0; list-style:none; max-height:200px; overflow-y:auto;"></ul>
                </div>
            </div>
            <div class="chart-card">
                <h3 class="chart-title" style="margin-bottom: 20px;">Quotation Payment Details</h3>
                <div id="container"></div>
            </div>
        </div>
    </main>

    <!-- Status Details Modal -->
    <div class="modal" id="statusDetailsModal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="statusModalTitle">Lead Details</h2>
                <button class="close-button" id="closeStatusModalBtn">&times;</button>
            </div>
            <div id="statusDetailsContent" class="modal-body">
                <div class="status-summary">
                    <div class="status-info">
                        <div class="status-icon-large" id="statusIconLarge">
                            <i id="statusIconFA" class="fa-solid fa-chart-pie"></i>
                        </div>
                        <div class="status-text">
                            <h3 id="statusName">Status Name</h3>
                            <p id="statusCount">0</p>
                            <p id="statusDescription">Description here</p>
                        </div>
                    </div>
                </div>

                <div class="status-leads-container" id="leadsTableWrapper">
                    <div class="status-leads-header">
                        <h4 id="header">Leads in this Status</h4>
                    </div>

                    <div class="status-leads-table-container">
                        <table class="status-leads-table">
                            <thead id="statusLeadsTableHead"></thead>
                            <tbody id="statusLeadsTableBody"></tbody>
                        </table>
                    </div>

                    <div id="noLeadsMessage" class="no-leads-message" style="display: none;">
                        <div class="no-leads-icon"><i class="fa-regular fa-folder-open"></i></div>
                        <h4 id="display_data" style="margin:0 0 5px 0; color:var(--text-main); font-size: 18px;">No records found</h4>
                        <p id="display_parah" style="margin:0;">There are no records in this status at the moment.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn3.devexpress.com/jslib/23.2.5/js/dx.all.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>

    <script>
        // Modal Logic & Headers With S.No Included
        const leadTableHead = `<tr><th>S.No</th><th>Name</th><th>Contact</th><th>Company</th><th>Customer Type</th><th>Source</th></tr>`;
        const quotationTableHead = `<tr><th>S.No</th><th>Client</th><th>Quotation Id</th><th>Date / Valid Till</th><th>Address</th></tr>`;

        function toggleStatusModal(show = true) {
            const modal = document.getElementById('statusDetailsModal');
            modal.style.display = show ? 'flex' : 'none';
            document.body.style.overflow = show ? 'hidden' : '';
        }

        function setModalHeader(card, countLabel) {
            document.getElementById('statusModalTitle').textContent = card.dataset.statusName + ' Details';
            document.getElementById('statusName').textContent = card.dataset.statusName;
            document.getElementById('statusCount').textContent = `${card.dataset.statusCount ?? 0} ${countLabel}`;
            document.getElementById('statusDescription').textContent = card.dataset.description;
            document.getElementById('statusIconFA').className = card.dataset.iconFa;
            document.getElementById('statusIconLarge').className = 'status-icon-large ' + card.dataset.iconClass;
        }

        function showLoading() {
            document.getElementById('statusLeadsTableBody').innerHTML = `<tr><td colspan="6" style="text-align:center;padding:40px;">Loading...</td></tr>`;
            document.getElementById('noLeadsMessage').style.display = 'none';
        }

        function displayStatusLeads(card) {
            if (!card) return;
            document.getElementById('header').textContent = 'Leads in this Status';
            setModalHeader(card, 'leads');
            document.getElementById('statusLeadsTableHead').innerHTML = leadTableHead;
            showLoading();

            const formData = new FormData();
            formData.append('action', 'fetch_by_leads');
            formData.append('statusId', card.dataset.statusId);

            fetch("", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(leads => {
                    const body = document.getElementById('statusLeadsTableBody');
                    const empty = document.getElementById('noLeadsMessage');

                    if (!leads || leads.length === 0) {
                        body.innerHTML = '';
                        empty.style.display = 'block';
                        document.getElementById('display_data').textContent = 'No Leads found';
                        document.getElementById('display_parah').textContent = 'There are no leads in this status at the moment.';
                        return;
                    }

                    body.innerHTML = leads.map((l, index) => `
                        <tr>
                            <td data-label="S.No">${index + 1}</td>
                            <td data-label="Name">${l.full_name}</td>
                            <td data-label="Contact">
                                <div>${l.email}</div>
                                <div style="font-size:12px;color:var(--text-muted)">${l.phone_number || 'N/A'}</div>
                            </td>
                            <td data-label="Company">${l.company_name || 'N/A'}</td>
                            <td data-label="Customer Type">${l.customer_type || 'N/A'}</td>
                            <td data-label="Source">${l.source || 'N/A'}</td>
                        </tr>
                    `).join('');
                })
                .catch(() => {
                    document.getElementById('statusLeadsTableBody').innerHTML = `<tr><td colspan="6" style="text-align:center;color:red;">Error loading leads</td></tr>`;
                });
            toggleStatusModal(true);
        }

        function displayQuotations(card) {
            if (!card) return;
            document.getElementById('header').textContent = 'Quotations in this Status';
            setModalHeader(card, 'Quotations');
            document.getElementById('statusLeadsTableHead').innerHTML = quotationTableHead;
            showLoading();

            const formData = new FormData();
            formData.append('action', 'fetch_by_quotations');
            formData.append('statusId', card.dataset.statusId);

            fetch("", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    const body = document.getElementById('statusLeadsTableBody');
                    const empty = document.getElementById('noLeadsMessage');

                    if (!data || data.length === 0) {
                        body.innerHTML = '';
                        empty.style.display = 'block';
                        document.getElementById('display_data').textContent = 'No Quotations found';
                        document.getElementById('display_parah').textContent = 'There are no quotations in this status at the moment.';
                        return;
                    }

                    body.innerHTML = data.map((q, index) => `
                        <tr>
                            <td data-label="S.No">${index + 1}</td>
                            <td data-label="Client">${q.client_name}</td>
                            <td data-label="Quotation ID">${q.quotation_no}</td>
                            <td data-label="Date">${q.date || 'N/A'} - ${q.valid_till || 'N/A'}</td>
                            <td data-label="Address">${q.client_address || 'N/A'}</td>
                        </tr>
                    `).join('');
                })
                .catch(() => {
                    document.getElementById('statusLeadsTableBody').innerHTML = `<tr><td colspan="5" style="text-align:center;color:red;">Error loading quotations</td></tr>`;
                });
            toggleStatusModal(true);
        }

        function displayclosed(card) {
            if (!card) return;
            document.getElementById('header').textContent = 'Quotations in this Status';
            setModalHeader(card, 'Quotations');
            document.getElementById('statusLeadsTableHead').innerHTML = quotationTableHead;
            showLoading();

            const formData = new FormData();
            formData.append('action', 'fetch_by_closed');
            formData.append('statusId', card.dataset.statusId);

            fetch("", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    const body = document.getElementById('statusLeadsTableBody');
                    const empty = document.getElementById('noLeadsMessage');

                    if (!data || data.length === 0) {
                        body.innerHTML = '';
                        empty.style.display = 'block';
                        document.getElementById('display_data').textContent = 'No Quotations found';
                        document.getElementById('display_parah').textContent = 'There are no quotations in this status at the moment.';
                        return;
                    }

                    body.innerHTML = data.map((q, index) => `
                        <tr>
                            <td data-label="S.No">${index + 1}</td>
                            <td data-label="Client">${q.client_name}</td>
                            <td data-label="Quotation ID">${q.quotation_no}</td>
                            <td data-label="Date">${q.date || 'N/A'} - ${q.valid_till || 'N/A'}</td>
                            <td data-label="Address">${q.client_address || 'N/A'}</td>
                        </tr>
                    `).join('');
                })
                .catch(() => {
                    document.getElementById('statusLeadsTableBody').innerHTML = `<tr><td colspan="5" style="text-align:center;color:red;">Error loading quotations</td></tr>`;
                });
            toggleStatusModal(true);
        }

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('.stat-card').forEach(card => {
                card.addEventListener('click', () => {
                    const type = card.dataset.cardType;
                    setModalHeader(card, '');
                    document.getElementById('leadsTableWrapper').style.display = 'block';

                    if (type === 'leads') {
                        displayStatusLeads(card);
                    } else if (type === 'quotations') {
                        displayQuotations(card);
                    } else if (type === 'confirmed-orders') {
                        displayclosed(card);
                    } else {
                        setModalHeader(card, 'amount');
                        document.getElementById('leadsTableWrapper').style.display = 'none';
                        toggleStatusModal(true);
                    }
                });
            });

            document.getElementById('closeStatusModalBtn')?.addEventListener('click', () => toggleStatusModal(false));

            window.addEventListener('click', e => {
                if (e.target === document.getElementById('statusDetailsModal')) {
                    toggleStatusModal(false);
                }
            });
        });

        // ================== DEVEXTREME CHART LOGIC ==================
        let currentChartType = "bar";
        let currentData = [];
        let isDailyView = false;
        let currentMonth = "";

        $(() => {
            $('#backButton').dxButton({
                text: 'Back',
                icon: 'chevronleft',
                visible: false,
                onClick() {
                    loadMonthlyChart();
                }
            });
            loadMonthlyChart();
        });

        function checkChart(type) {
            currentChartType = type;
            if (currentData.length > 0) renderChart(currentData, currentChartType);
        }

        function renderChart(data, chartType) {
            currentData = data;
            let chartContainer = $("#chart");
            let argField = isDailyView ? "day" : "month";
            let chartTitle = isDailyView ? `Daily Quotations (${currentMonth})` : "Monthly Quotations Overview";

            // Update the HTML title
            document.getElementById('devExtremeTitle').innerText = chartTitle;

            if (chartContainer.data("dxChart")) chartContainer.dxChart("dispose");
            if (chartContainer.data("dxPieChart")) chartContainer.dxPieChart("dispose");

            if (chartType === "bar" || chartType === "line") {
                chartContainer.dxChart({
                    dataSource: data,
                    series: {
                        argumentField: argField,
                        valueField: "total",
                        type: chartType,
                        label: {
                            visible: true,
                            format: "fixedPoint"
                        },
                        color: "#a1d5e6"
                    },
                    legend: {
                        visible: false
                    },
                    tooltip: {
                        enabled: true
                    },
                    onPointClick: handlePointClick
                });
            } else if (chartType === "pie" || chartType === "doughnut") {
                chartContainer.dxPieChart({
                    dataSource: data,
                    series: {
                        argumentField: argField,
                        valueField: "total",
                        type: chartType,
                        label: {
                            visible: true,
                            connector: {
                                visible: true
                            }
                        }
                    },
                    legend: {
                        horizontalAlignment: "right",
                        verticalAlignment: "top"
                    },
                    tooltip: {
                        enabled: true
                    },
                    onPointClick: handlePointClick
                });
            }
        }

        function handlePointClick(e) {
            if (!isDailyView) {
                currentMonth = e.target.argument;
                loadDailyChart(currentMonth);
            } else {
                loadQuotationDetails(e.target.argument);
            }
        }

        function loadMonthlyChart() {
            isDailyView = false;
            fetch("", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "action=get_monthly_quotes"
                })
                .then(res => res.json())
                .then(data => {
                    let formattedData = data.map(d => ({
                        month: d.month,
                        total: Number(d.total)
                    }));
                    $("#backButton").dxButton("instance").option("visible", false);
                    renderChart(formattedData, currentChartType);
                });
        }

        function loadDailyChart(month) {
            isDailyView = true;
            fetch("", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `action=get_daily_quotes&month=${encodeURIComponent(month)}`
                })
                .then(res => res.json())
                .then(data => {
                    let formattedData = data.map(d => ({
                        day: d.day,
                        total: Number(d.total)
                    }));
                    $("#backButton").dxButton("instance").option("visible", true);
                    renderChart(formattedData, currentChartType);
                });
        }

        function loadQuotationDetails(day) {
            fetch("", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `action=get_quote_details&day=${encodeURIComponent(day)}`
                })
                .then(res => res.json())
                .then(data => {
                    let rows = data.map((q, i) => `
                        <tr>
                            <td data-label="S.No">${i + 1}</td>
                            <td data-label="Client">${q.client_name}</td>
                            <td data-label="Quotation No">${q.quotation_no}</td>
                            <td data-label="Date">${q.created_at}</td>
                        </tr>
                    `).join('');

                    document.getElementById('statusModalTitle').textContent = "Quotation Details for " + day;
                    document.getElementById("statusLeadsTableHead").innerHTML = `<tr><th>S.No</th><th>Client</th><th>Quotation No</th><th>Date</th></tr>`;
                    document.getElementById("statusLeadsTableBody").innerHTML = rows || `<tr><td colspan="4" style="text-align:center;">No records found.</td></tr>`;

                    document.getElementById('leadsTableWrapper').style.display = 'block';
                    document.querySelector('.status-summary').style.display = 'none'; // Hide summary box for this specific view
                    toggleStatusModal(true);
                });
        }

        // ================== BOTTOM CHARTS LOGIC ==================
        const labels = <?= json_encode($labels); ?>;
        const dataValues = <?= json_encode($data); ?>;
        const namesData = <?= json_encode($names); ?>;

        var myChartCanvas = document.getElementById('myChart');
        var myChartWrapper = document.getElementById('myChartWrapper');

        if (!dataValues || dataValues.length === 0) {
            myChartCanvas.style.display = 'none';
            var noDataDiv = document.createElement('div');
            noDataDiv.style.cssText = 'display:flex;flex-direction:column;align-items:center;justify-content:center;height:200px;color:#A3AED0;';
            noDataDiv.innerHTML = '<i class="fas fa-chart-bar" style="font-size:40px;margin-bottom:12px;opacity:0.3;"></i><p style="margin:0;font-size:14px;font-weight:500;">No lead data available</p><p style="margin:4px 0 0;font-size:12px;">Data will appear once leads are added</p>';
            myChartWrapper.appendChild(noDataDiv);
        } else {
            var maxVal = Math.max.apply(null, dataValues);
            var yMax = maxVal + Math.max(Math.ceil(maxVal * 0.3), 3);
            var barWidth = 80;
            var canvasWidth = Math.max(myChartWrapper.offsetWidth || 400, dataValues.length * barWidth);
            myChartCanvas.width  = canvasWidth;
            myChartCanvas.height = 350;

            new Chart(myChartCanvas, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Monthly Leads',
                        data: dataValues,
                        backgroundColor: '#a4a7ee',
                        borderRadius: 6
                    }]
                },
                options: {
                    responsive: false,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 30, right: 10, left: 10, bottom: 10 } },
                    scales: {
                        y: {
                            min: 0,
                            max: yMax,
                            ticks: { stepSize: 1, precision: 0 }
                        }
                    },
                    onClick: function(evt, elements) {
                        if (!elements || elements.length === 0) return;
                        var idx = elements[0].index;
                        var month = labels[idx];
                        var nameList = namesData[idx] ? namesData[idx].split(', ') : [];
                        var panel = document.getElementById('leadNamesPanel');
                        var list  = document.getElementById('leadNamesList');
                        document.getElementById('leadNamesPanelTitle').textContent = month + ' — ' + nameList.length + ' lead' + (nameList.length !== 1 ? 's' : '');
                        list.innerHTML = nameList.length === 0
                            ? '<li style="padding:10px 16px;color:#A3AED0;font-size:13px;">No names available</li>'
                            : nameList.map(function(name, i) {
                                return '<li style="padding:8px 16px;border-bottom:1px solid #F4F7FE;font-size:13px;color:#2B3674;display:flex;gap:10px;align-items:center;">'
                                    + '<span style="width:22px;height:22px;border-radius:50%;background:#a4a7ee;display:inline-flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0;">' + (i + 1) + '</span>'
                                    + '<span>' + name + '</span></li>';
                            }).join('');
                        panel.style.display = 'block';
                        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    },
                    plugins: {
                        datalabels: {
                            anchor: 'end',
                            align: 'top',
                            color: '#2B3674',
                            font: { weight: '600', size: 12 },
                            formatter: function(value) { return value; }
                        },
                        tooltip: {
                            callbacks: {
                                title: function(ctx) { return ctx[0].label; },
                                label: function(ctx) { return ' ' + ctx.parsed.y + ' lead(s) — click bar for names'; }
                            }
                        }
                    }
                },
                plugins: [ChartDataLabels]
            });
        }

        Highcharts.chart('container', {
            chart: {
                type: 'column',
                backgroundColor: 'transparent'
            },
            title: {
                text: null
            }, // Handled by HTML header
            xAxis: {
                categories: <?= json_encode($months) ?>
            },
            yAxis: {
                min: 0,
                title: {
                    text: 'Amount (₹)'
                }
            },
            tooltip: {
                valuePrefix: '₹ '
            },
            plotOptions: {
                column: {
                    borderRadius: 4
                }
            },
            credits: {
                enabled: false
            },
            colors: ['#b2f0b2', '#97e2ce', '#f0c584'],
            series: [{
                    name: 'Total Quoted Amount',
                    data: <?= json_encode($totalQuoted) ?>
                },
                {
                    name: 'Total Ordered Amount',
                    data: <?= json_encode($totalOrdered) ?>
                },
                {
                    name: 'Amount Collected',
                    data: <?= json_encode($amountCollected) ?>
                }
            ]
        });
    </script>
</body>

</html>