<?php
$response = ["success" => false];
include '../include/dbconnect.php';

// Connection A: Source database (Server 1)
$source_server   = "15.207.37.132";
$source_user     = "cloud";
$source_password = "TUCKER_ser_sql";
$source_db       = "marketing_new";

$source_conn = new mysqli($source_server, $source_user, $source_password, $source_db);
if ($source_conn->connect_error) {
    die("Source Connection failed: " . $source_conn->connect_error);
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// $zone           = isset($_POST['zone']) ? $_POST['zone'] : '';
// $quantity       = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
$description    = isset($_POST['description']) ? $source_conn->real_escape_string($_POST['description']) : '';
$device_type_raw    = isset($_POST['device_type']) ? $_POST['device_type'] : '';
$device_modal   = isset($_POST['device_modal']) ? $_POST['device_modal'] : '';
$communication  = isset($_POST['communication']) ? $_POST['communication'] : '';
$receive_date = isset($_POST['receive_date']) && !empty($_POST['receive_date']) ? "'" . mysqli_real_escape_string($target_conn, $_POST['receive_date']) . "'" : "NULL";
$dispatch_date = isset($_POST['dispatch_date']) && !empty($_POST['dispatch_date']) ? "'" . mysqli_real_escape_string($target_conn, $_POST['dispatch_date']) . "'" : "NULL";

$zone   = isset($_POST['deviceType']) ? $_POST['deviceType'] : '1';
$dc_power  = $_POST['dc_power'] ?? '20 KW';
$weight    = $_POST['weight'] ?? null;

// Handle CP numbers input
$input = $_POST['cp_numbers'] ?? '';
$cp_list = [];
if (!empty($input)) {
    foreach (explode(',', $input) as $part) {
        $part = trim($part);
        if (strpos($part, '-') !== false) {
            list($start, $end) = explode('-', $part);
            $cp_list = array_merge($cp_list, range((int)$start, (int)$end));
        } else {
            $cp_list[] = (int)$part;
        }
    }
}


$device_type_parts = explode('~~', $device_type_raw);
$device_modal_code  = $device_type_parts[0] ?? $device_type_raw;

//Example: Fetch from source
$sql = "SELECT * FROM quotations where id = $id ORDER BY created_at DESC LIMIT 1";
$result = $source_conn->query($sql);

$sql_1 = "SELECT quantity FROM productss WHERE quotation_id = '$id' ORDER BY quotation_id DESC LIMIT 1";
$result_1 = $source_conn->query($sql_1);

$quantity = ($row = $result_1->fetch_assoc()) ? $row['quantity'] : 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        // Generate sequential order id
        $last_sql = "SELECT order_id FROM orders_list ORDER BY sno DESC LIMIT 1";
        $last_result = mysqli_query($target_conn, $last_sql);

        $next_number = 1; // default for first order
        if ($last_result && mysqli_num_rows($last_result) > 0) {
            $last_row = mysqli_fetch_assoc($last_result);
            $last_order_id = $last_row['order_id']; // e.g. ORD-005

            // Extract number part
            if (preg_match('/ORD-(\d+)/', $last_order_id, $matches)) {
                $next_number = (int)$matches[1] + 1;
            }
        }

        // Format with leading zeros (3 digits)
        $order_id = "ORD-" . str_pad($next_number, 3, '0', STR_PAD_LEFT);

        $cp_numbers_str = implode(', ', $cp_list);

        //Insert into target
        $insert_sql = "INSERT INTO orders_list(order_id, device_type, quantity,power,weight, communication, device_modal,zone, description, receive_date, dispatch_date, entry_date,marketing_status,charge_numbers) 
        VALUES ('$order_id','$device_modal_code', $quantity,'$dc_power','$weight', '$communication','$device_modal','$zone', '$description',$receive_date,$dispatch_date,NOW(),'Y','$cp_numbers_str')";

        if ($target_conn->query($insert_sql)) {
            // Get the auto-increment sno from orders_list
            $order_sno = mysqli_insert_id($target_conn);

            //Insert initial status record for this order
            $tracker_sql = "INSERT INTO status_tracker (orderid, odr_sno, status, entry_date, updated_date) 
                    VALUES ('$order_id', $order_sno, 'N', NOW(), NOW())";

            if (mysqli_query($target_conn, $tracker_sql)) {
                $response["success"]  = true;
                $response["order_id"] = $order_id;


                // Convert CP numbers to "ChargePointX" format
                // $cp_list_for_email = array_map(function ($num, $i) {
                //     return "ChargePoint" . ($i + 1); // 1-based index
                // }, $cp_list, array_keys($cp_list));

                // Send email
                // $to = "madhubala@tuckermotors.com,vivekc@tuckermotors.com,manoj@tuckermotors.com";
                // $subject = "New Support Request From Marketing Team : Create Charge Point";
                // $message = "Hello Support Team,\n\nA new request to create charge points has been submitted.\n\n";
                // $message .= "Order ID: $order_id\n";
                // $message .= "CP Numbers: " . implode(', ', $cp_list_for_email) . "\n\n";
                // $message .= "Please process this request as soon as possible.\n\nRegards,\nTucker Motors Support System";

                // $headers = "From: madhubala@tuckermotors.com\r\nReply-To: madhubala@tuckermotors.com\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";
                // mail($to, $subject, $message, $headers);




            } else {
                $response["message"] = "DB Error inserting status_tracker: " . mysqli_error($connect);
            }


            $update_sql = "UPDATE quotations SET order_status = 'Y' WHERE id = $id";

            if ($source_conn->query($update_sql)) {
                $response["success"] = true;
                $response["message"] = "Ordered created successfully";
            } else {
                echo "Error updating quotation: " . $source_conn->error;
            }
        } else {
            echo "Error inserting: " . $target_conn->error;
        }
    }
} else {
    echo "No data found in source database.";
}

echo json_encode($response);

// Close connections
$source_conn->close();
$target_conn->close();
