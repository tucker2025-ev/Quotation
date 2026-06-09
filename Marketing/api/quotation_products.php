<?php
// Include session configuration
require_once '../include/session_config.php';

// Check if user is logged in and has access
// Temporarily disable strict access check for debugging
// requireLoginAndAccess('quotation_products.php');

// Basic session check
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_mobile']) || empty($_SESSION['user_mobile'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Set content type to JSON
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "15.207.37.132";
$username = "cloud";
$password = "TUCKER_ser_sql";
$dbname = "marketing_new";

try {
    // Log the request for debugging
    error_log("quotation_products.php called with action: " . ($_GET['action'] ?? $_POST['action'] ?? 'none') . ", quotation_id: " . ($_GET['quotation_id'] ?? $_POST['quotation_id'] ?? $_POST['quotationid'] ?? 'none'));

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $quotation_id = $_GET['quotation_id'] ?? $_POST['quotation_id'] ?? $_POST['quotationid'] ?? 0;

    error_log("Parsed action: $action, quotation_id: $quotation_id");

    if (!$quotation_id || !is_numeric($quotation_id)) {
        throw new Exception("Invalid quotation ID: $quotation_id");
    }

    // Check if quotation exists
    $check_sql = "SELECT id, quotation_no FROM quotations WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt) {
        $check_stmt->bind_param("i", $quotation_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows === 0) {
            throw new Exception("Quotation with ID $quotation_id not found");
        }
        $quotation_info = $check_result->fetch_assoc();
        error_log("Found quotation: " . $quotation_info['quotation_no']);
        $check_stmt->close();
    } else {
        error_log("Could not prepare quotation check statement: " . $conn->error);
    }

    switch ($action) {
        case 'fetch':
            error_log("Fetching products for quotation_id: $quotation_id");

            // Fetch all products for a quotation
            $sql = "SELECT p.*, 
                           (p.unit_price * p.quantity) as subtotal,
                           ((p.unit_price * p.quantity) * (COALESCE(p.discount_percent, 0) / 100)) as discount_amount,
                           ((p.unit_price * p.quantity) * (1 - COALESCE(p.discount_percent, 0) / 100)) as net_amount,
                           (((p.unit_price * p.quantity) * (1 - COALESCE(p.discount_percent, 0) / 100)) * COALESCE(p.gst_percent, 18) / 100) as gst_amount
                    FROM productss p 
                    WHERE p.quotation_id = ? 
                    ORDER BY p.id ASC";

            error_log("SQL query: $sql");

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }

            $stmt->bind_param("i", $quotation_id);
            $stmt->execute();
            $result = $stmt->get_result();

            error_log("Query executed, rows found: " . $result->num_rows);

            $products = [];
            $summary = [
                'subtotal' => 0,
                'total_discount' => 0,
                'net_taxable_value' => 0,
                'gst_value' => 0,
                'grand_total' => 0
            ];

            while ($row = $result->fetch_assoc()) {
                // Calculate totals
                $subtotal = $row['subtotal'];
                $discount_amount = $row['discount_amount'];
                $net_amount = $row['net_amount'];
                $gst_amount = $row['gst_amount'];
                $total_price = $net_amount + $gst_amount;

                // Add calculated fields to row
                $row['total_price'] = $total_price;

                // Update summary
                $summary['subtotal'] += $subtotal;
                $summary['total_discount'] += $discount_amount;
                $summary['net_taxable_value'] += $net_amount;
                $summary['gst_value'] += $gst_amount;
                $summary['grand_total'] += $total_price;

                $products[] = $row;
            }

            $stmt->close();

            // Also fetch quotation details
            $sql_quotation = "SELECT quotation_no, client_name FROM quotations WHERE id = ?";
            $stmt_quotation = $conn->prepare($sql_quotation);
            if ($stmt_quotation) {
                $stmt_quotation->bind_param("i", $quotation_id);
                $stmt_quotation->execute();
                $result_quotation = $stmt_quotation->get_result();
                $quotation_info = $result_quotation->fetch_assoc();
                $stmt_quotation->close();
            } else {
                $quotation_info = ['quotation_no' => 'Unknown', 'client_name' => 'Unknown'];
            }

            echo json_encode([
                'success' => true,
                'products' => $products,
                'summary' => $summary,
                'quotation_info' => $quotation_info
            ]);
            break;

        case 'update':
            // Update a product
            $product_sno = $_POST['product_id'] ?? 0;
            $product_names = $_POST['product_name'] ?? '';

            list($product_id, $product_name) = explode('~~', $product_names);

            $unit_price = (float)($_POST['unit_price'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            $discount_percent = (float)($_POST['discount_percent'] ?? 0);
            $gst_percent = (float)($_POST['gst_percent'] ?? 18);

            if (!$product_id) {
                throw new Exception("Invalid product ID");
            }

            if (empty($product_name)) {
                throw new Exception("Product name is required");
            }

            if ($unit_price <= 0) {
                throw new Exception("Unit price must be greater than 0");
            }

            if ($quantity <= 0) {
                throw new Exception("Quantity must be greater than 0");
            }

            // Calculate total price
            $base_amount = $unit_price * $quantity;
            $discount_amount = $base_amount * ($discount_percent / 100);
            $after_discount = $base_amount - $discount_amount;
            $gst_amount = $after_discount * ($gst_percent / 100);
            $total_price = $after_discount + $gst_amount;

            // Update product
            $sql = "UPDATE productss SET product_name = ?, unit_price = ?, quantity = ?, discount_percent = ?, gst_percent = ?,total_price = ?,product_id = ? WHERE id = ? AND quotation_id = ?";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing update statement: " . $conn->error);
            }

            $stmt->bind_param("sdiddisii", $product_name, $unit_price, $quantity, $discount_percent, $gst_percent, $total_price, $product_id, $product_sno, $quotation_id);

            if ($stmt->execute()) {
                $stmt->close();
                echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
            } else {
                throw new Exception("Error updating product: " . $stmt->error);
            }
            break;

        case 'add':
            // Add a new product
            $product_names = $_POST['product_name'] ?? '';

            list($product_id, $product_name) = explode('~~', $product_names);
            $unit_price = (float)($_POST['unit_price'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            $discount_percent = (float)($_POST['discount_percent'] ?? 0);
            $gst_percent = (float)($_POST['gst_percent'] ?? 18);

            if (empty($product_name)) {
                throw new Exception("Product name is required");
            }

            if ($unit_price <= 0) {
                throw new Exception("Unit price must be greater than 0");
            }

            if ($quantity <= 0) {
                throw new Exception("Quantity must be greater than 0");
            }

            // Calculate total price
            $base_amount = $unit_price * $quantity;
            $discount_amount = $base_amount * ($discount_percent / 100);
            $after_discount = $base_amount - $discount_amount;
            $gst_amount = $after_discount * ($gst_percent / 100);
            $total_price = $after_discount + $gst_amount;

            // Insert product
            $sql = "INSERT INTO productss (quotation_id, product_id,product_name, unit_price, quantity, discount_percent, gst_percent, total_price) 
                    VALUES (?, ?, ?,?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing insert statement: " . $conn->error);
            }

            $stmt->bind_param("issidddd", $quotation_id, $product_id, $product_name, $unit_price, $quantity, $discount_percent, $gst_percent, $total_price);

            if ($stmt->execute()) {
                $new_product_id = $conn->insert_id;
                $stmt->close();
                echo json_encode(['success' => true, 'message' => 'Product added successfully', 'product_id' => $new_product_id]);
            } else {
                throw new Exception("Error adding product: " . $stmt->error);
            }
            break;

        case 'delete':
            // Delete a product
            $product_id = $_POST['product_id'] ?? 0;

            if (!$product_id || !is_numeric($product_id)) {
                throw new Exception("Invalid product ID");
            }

            $sql = "DELETE FROM productss WHERE id = ? AND quotation_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing delete statement: " . $conn->error);
            }

            $stmt->bind_param("ii", $product_id, $quotation_id);

            if ($stmt->execute()) {
                $stmt->close();
                echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                throw new Exception("Error deleting product: " . $stmt->error);
            }
            break;

        case 'update-order':

            $quotationNo = $_POST['quotationNo'] ?? '';
            $quotation_id = (int)($_POST['quotation_id'] ?? 0);

            if (!$quotation_id || empty($quotationNo)) {
                throw new Exception("Invalid quotation data");
            }

            $sql = "UPDATE quotations 
            SET order_status = 'Y' 
            WHERE id = ? AND quotation_no = ?";

            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param("is", $quotation_id, $quotationNo);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Order confirmed'
                ]);
            } else {
                throw new Exception("Order update failed");
            }

            $stmt->close();
            break;
        case 'version-order':

            $id = (int)$_POST['quotation_id'];
            $no = $_POST['quotationNo'];            // QUO-2026-17
            $version_code = $_POST['version_code']; // V2

            /* 1️⃣ Mark OLD quotation as Superseded */
            $sql = "UPDATE quotations 
            SET order_status = 'S' 
            WHERE id = ? AND quotation_no = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $id, $no);
            $stmt->execute();
            $stmt->close();


            /* 2️⃣ Insert NEW quotation (NEW VERSION) */
            $sql = "INSERT INTO quotations (
        parent_id,
        client_id,
        order_date,
        quotation_no,
        date,
        valid_till,
        client_name,
        client_address,
        salutation,
        subject,
        introduction,
        additional_notes,
        order_status,
        version_code,
        cms_id,
        cpo_id
    )
    SELECT
        parent_id,
        client_id,
        NOW(),
        CONCAT(
            SUBSTRING_INDEX(quotation_no, '-V', 1),
            '-',
            ?
        ),
        date,
        valid_till,
        client_name,
        client_address,
        salutation,
        subject,
        introduction,
        additional_notes,
        'N',
        ?,
        cms_id,
        cpo_id
    FROM quotations
    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $version_code, $version_code, $id);
            $stmt->execute();
            $stmt->close();

            /* 🔑 Get NEW quotation ID */
            $newQuotationId = $conn->insert_id;

            /* 3️⃣ Insert NEW summary */
            $sql = "
        INSERT INTO summary (
            quotation_id,
            subtotal,
            net_value,
            total_discount,
            gst_value,
            grand_total,
            payment_status
        )
        SELECT
            ?,
            subtotal,
            net_value,
            total_discount,
            gst_value,
            grand_total,
            'N'
        FROM summary
        WHERE quotation_id = ?
    ";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $newQuotationId, $id);
            $stmt->execute();
            $stmt->close();


            /* 3️⃣ Insert NEW summary */
            $sql = "
INSERT INTO productss (
    quotation_id,
    product_id,
    product_name,
    unit_price,
    quantity,
    discount_percent,
    gst_percent,
    total_price
)
SELECT
    ?,
    product_id,
    product_name,
    unit_price,
    quantity,
    discount_percent,
    gst_percent,
    total_price
FROM productss
WHERE quotation_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $newQuotationId, $id);
            $stmt->execute();
            $stmt->close();


            /* 3️⃣ Insert Bank Details */
            $sql = "INSERT INTO bank_details (quotation_id,bank_name,account_number,ifsc_code,branch_name) SELECT ?,bank_name,account_number,ifsc_code,branch_name FROM bank_details WHERE quotation_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $newQuotationId, $id);
            $stmt->execute();
            $stmt->close();

            echo json_encode([
                'success' => true,
                'quotation_no' => $no . '-' . $version_code,
                'version_code' => $version_code,
                'quotation_id' => $newQuotationId

            ]);
            break;

        case 'update-quotation':

            $editId = $_POST['id'] ?? 0;   // FIXED
            $quotationid = $_POST['quotationid'] ?? 0;
            if (!$editId) {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
                exit;
            }

            $quotation_no     = $_POST['quotation_no'];
            $date             = $_POST['date'];
            $valid_till       = $_POST['valid_till'];
            $client_name      = $_POST['client_name'];
            $client_address   = $_POST['client_address'];
            $salutation       = $_POST['salutation'];
            $subject          = $_POST['subject'];
            $introduction     = $_POST['introduction'];
            $additional_notes = $_POST['additional_notes'];

            $sql = "UPDATE quotations 
            SET quotation_no=?, date=?, valid_till=?, client_name=?, client_address=?, salutation=?, subject=?, introduction=?, additional_notes=?
            WHERE id=?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssssssi",
                $quotation_no,
                $date,
                $valid_till,
                $client_name,
                $client_address,
                $salutation,
                $subject,
                $introduction,
                $additional_notes,
                $editId
            );

            $stmt->execute();

            echo json_encode(['success' => true]);
            break;

        case 'charger_details':

            $quotation_id = $_POST['quotation_id'] ?? 0;

            $data_op = file_get_contents(
                "https://star.tuckermotors.com/TuckerApp/get_quotation_data.php?quotation_id=" . $quotation_id
            );
            // $data_op = file_get_contents(
            //     "https://star.tuckermotors.com/TuckerApp/create_cp.php?quotation_id=3"
            // );

            $decoded = json_decode($data_op, true); // 👈 convert to array

            echo json_encode($decoded); // 👈 send clean JSON
            break;

        default:
            throw new Exception("Invalid action");
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
