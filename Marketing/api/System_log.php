<?php
// API Endpoints
const FRANCHISE_URL = "http://tuckermotors.com/api-Franchise.php?api_key=TUCKER_SECURE_123";
const REVENUE_URL   = "http://tuckermotors.com/api-partner.php?api_key=TUCKER_SECURE_123";
const CONTACT_URL   = "http://tuckermotors.com/api-contact.php?api_key=TUCKER_SECURE_123";

// Function to get API Response
function fetchAPI($url)
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

// Fetch API responses
$franchiseData = fetchAPI(FRANCHISE_URL);
$revenueData   = fetchAPI(REVENUE_URL);
$contactData   = fetchAPI(CONTACT_URL);

// Database Credentials
$db_host = "122.165.204.242";
$db_user = "root";
$db_pass = "firstcall@123";
$db_name = "tucker_website";

// ============================================
//  HANDLE AJAX REQUESTS (LOGGING & FETCHING)
// ============================================

// 1. Fetch Logs for Table
if (isset($_GET['fetch_logs'])) {
    header('Content-Type: application/json');
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    if ($conn->connect_error) {
        echo json_encode(['status' => 'error', 'message' => 'DB Connect Error']);
        exit;
    }

    $sql = "SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 500";
    $result = $conn->query($sql);

    $logs = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }

    echo json_encode(['status' => 'success', 'data' => $logs]);
    $conn->close();
    exit;
}

// 2. Write New Log (VIEW / DELETE actions)
if (isset($_POST['log_action'])) {
    header('Content-Type: application/json');

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        echo json_encode(['status' => 'error']);
        exit;
    }

    $action_type = $_POST['action_type'];  // 'VIEW' or 'DELETE'
    $details     = $_POST['details'];
    $ip_address  = $_SERVER['REMOTE_ADDR'];

    // Bind params require VARIABLES
    $user_id   = 1;
    $username  = "admin";
    $action    = $action_type;
    $detail    = $details;
    $ip        = $ip_address;

    $stmt = $conn->prepare("INSERT INTO system_logs (user_id, username, action_type, details, ip_address) 
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $user_id, $username, $action, $detail, $ip);

    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }

    $stmt->close();
    $conn->close();
    exit;
}
