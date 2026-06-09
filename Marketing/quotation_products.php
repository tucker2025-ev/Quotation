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
$dbname = "marketing";

try {
    // Log the request for debugging
    error_log("quotation_products.php called with action: " . ($_GET['action'] ?? $_POST['action'] ?? 'none') . ", quotation_id: " . ($_GET['quotation_id'] ?? $_POST['quotation_id'] ?? 'none'));
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $quotation_id = $_GET['quotation_id'] ?? $_POST['quotation_id'] ?? 0;

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
            $product_id = $_POST['product_id'] ?? 0;
            $product_name = $_POST['product_name'] ?? '';
            $unit_price = (float)($_POST['unit_price'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            $discount_percent = (float)($_POST['discount_percent'] ?? 0);
            $gst_percent = (float)($_POST['gst_percent'] ?? 18);
            
            if (!$product_id || !is_numeric($product_id)) {
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
            
            // Validate against product catalog
            $sql_catalog = "SELECT base_price, gst, max_discount FROM products WHERE item_name = ? ORDER BY updated_at DESC, id DESC LIMIT 1";
            $stmt_catalog = $conn->prepare($sql_catalog);
            if ($stmt_catalog) {
                $stmt_catalog->bind_param('s', $product_name);
                $stmt_catalog->execute();
                $catalog_result = $stmt_catalog->get_result();
                $catalog = $catalog_result->fetch_assoc();
                $stmt_catalog->close();
                
                if ($catalog) {
                    $catalog_max_discount = (float)$catalog['max_discount'];
                    $catalog_gst_cap = (float)$catalog['gst'];
                    
                    if ($discount_percent > $catalog_max_discount) {
                        throw new Exception("Discount {$discount_percent}% exceeds maximum allowed {$catalog_max_discount}% for this product");
                    }
                    
                    if ($gst_percent > $catalog_gst_cap) {
                        throw new Exception("GST {$gst_percent}% exceeds maximum allowed {$catalog_gst_cap}% for this product");
                    }
                }
            }
            
            // Calculate total price
            $base_amount = $unit_price * $quantity;
            $discount_amount = $base_amount * ($discount_percent / 100);
            $after_discount = $base_amount - $discount_amount;
            $gst_amount = $after_discount * ($gst_percent / 100);
            $total_price = $after_discount + $gst_amount;
            
            // Update product
            $sql = "UPDATE productss SET 
                        product_name = ?, 
                        unit_price = ?, 
                        quantity = ?, 
                        discount_percent = ?, 
                        gst_percent = ?, 
                        total_price = ? 
                    WHERE id = ? AND quotation_id = ?";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing update statement: " . $conn->error);
            }
            
            $stmt->bind_param("sdiddiii", $product_name, $unit_price, $quantity, $discount_percent, $gst_percent, $total_price, $product_id, $quotation_id);
            
            if ($stmt->execute()) {
                $stmt->close();
                echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
            } else {
                throw new Exception("Error updating product: " . $stmt->error);
            }
            break;

        case 'add':
            // Add a new product
            $product_name = $_POST['product_name'] ?? '';
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
            
            // Validate against product catalog
            $sql_catalog = "SELECT base_price, gst, max_discount FROM products WHERE item_name = ? ORDER BY updated_at DESC, id DESC LIMIT 1";
            $stmt_catalog = $conn->prepare($sql_catalog);
            if ($stmt_catalog) {
                $stmt_catalog->bind_param('s', $product_name);
                $stmt_catalog->execute();
                $catalog_result = $stmt_catalog->get_result();
                $catalog = $catalog_result->fetch_assoc();
                $stmt_catalog->close();
                
                if ($catalog) {
                    $catalog_max_discount = (float)$catalog['max_discount'];
                    $catalog_gst_cap = (float)$catalog['gst'];
                    $catalog_base_price = (float)$catalog['base_price'];
                    
                    // Use catalog base price if unit price is not provided or is 0
                    if ($unit_price <= 0) {
                        $unit_price = $catalog_base_price;
                    }
                    
                    if ($discount_percent > $catalog_max_discount) {
                        throw new Exception("Discount {$discount_percent}% exceeds maximum allowed {$catalog_max_discount}% for this product");
                    }
                    
                    if ($gst_percent > $catalog_gst_cap) {
                        throw new Exception("GST {$gst_percent}% exceeds maximum allowed {$catalog_gst_cap}% for this product");
                    }
                } else {
                    throw new Exception("Product '{$product_name}' not found in catalog");
                }
            }
            
            // Calculate total price
            $base_amount = $unit_price * $quantity;
            $discount_amount = $base_amount * ($discount_percent / 100);
            $after_discount = $base_amount - $discount_amount;
            $gst_amount = $after_discount * ($gst_percent / 100);
            $total_price = $after_discount + $gst_amount;
            
            // Insert product
            $sql = "INSERT INTO productss (quotation_id, product_name, unit_price, quantity, discount_percent, gst_percent, total_price) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error preparing insert statement: " . $conn->error);
            }
            
            $stmt->bind_param("isidddd", $quotation_id, $product_name, $unit_price, $quantity, $discount_percent, $gst_percent, $total_price);
            
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
?>
