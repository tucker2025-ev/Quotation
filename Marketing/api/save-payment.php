<?php
header('Content-Type: application/json');

// DB CONFIG
$db_host = "15.207.37.132";
$db_user = "cloud";
$db_pass = "TUCKER_ser_sql";
$db_name = "marketing_new";

// MAIN CONNECTION
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}
$fileName = null;
if (isset($_POST['action']) && $_POST['action'] === 'save-payments') {

    // INPUTS
    $related_id = intval($_GET['id'] ?? 0);
    $mode   = trim($_POST['payment_mode'] ?? '');
    $total_payment_amount = floatval($_POST['total_payment_amount'] ?? 0);
    $ref    = trim($_POST['payment_ref'] ?? '');
    $collected_amount    = trim($_POST['collected_amount'] ?? '');

    // VALIDATION
    if ($related_id <= 0 || $mode === '' || $total_payment_amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing or invalid fields']);
        exit;
    }

    if (isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === UPLOAD_ERR_OK) {

    /* FILE VALIDATION */
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $fileType = $finfo->file($_FILES['payment_proof']['tmp_name']);

    if (!in_array($fileType, $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type']);
        exit;
    }

    // FILE SIZE LIMIT (5MB)
    if ($_FILES['payment_proof']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large']);
        exit;
    }

    /* UPLOAD */
    $uploadDir = __DIR__ . "/../uploads/payments/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $fileExt  = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('pay_', true) . "." . $fileExt;
    $filePath = $uploadDir . $fileName;

    if (!move_uploaded_file($_FILES['payment_proof']['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'message' => 'File upload failed']);
        exit;
    }

    }

    // START TRANSACTION
    $conn->begin_transaction();

    try {
        /* ================= INSERT PAYMENT ================= */
        $stmt = $conn->prepare("INSERT INTO payments (related_id, payment_mode, total_amount, amount, payment_reference, proof_file) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param(
            "isdsss",
            $related_id,
            $mode,
            $total_payment_amount,
            $collected_amount,
            $ref,
            $fileName
        );
        $stmt->execute();

        $check = $conn->prepare("SELECT cp_status FROM quotations WHERE id = ?");
        $check->bind_param("i", $related_id);
        $check->execute();
        $check->bind_result($cp_status);
        $check->fetch();
        $check->close();

        if ($cp_status === 'N') {

            $sql_quotation = "SELECT COALESCE(CONCAT('ORD-',LPAD(MAX(SUBSTRING_INDEX(order_no, '-', -1)) + 1, 3, '0')),'ORD-001') AS next_order_no FROM quotations WHERE order_no IS NOT NULL AND order_no <> ''";
            $stmt_quotation = $conn->prepare($sql_quotation);
            $stmt_quotation->execute();
            $row = $stmt_quotation->get_result()->fetch_assoc();

            $nextOrderNo = $row['next_order_no'];

            file_get_contents(
                "https://star.tuckermotors.com/TuckerApp/create_cp.php?quotation_id=" . $related_id
            );

            $update = $conn->prepare("UPDATE quotations SET cp_status = 'Y', order_no = '$nextOrderNo',order_date = Now() WHERE id = ? AND cp_status != 'Y'");
            $update->bind_param("i", $related_id);
            $update->execute();
        }

        /* ================= CHECK PAYMENT COMPLETION ================= */
        $check = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS paid_amount,MAX(total_amount) AS total_amount FROM payments WHERE related_id = ?");
        $check->bind_param("i", $related_id);
        $check->execute();
        $result = $check->get_result()->fetch_assoc();

        $paidAmount  = (float)$result['paid_amount'];
        $totalAmount = (float)$result['total_amount'];

        /* ================= UPDATE SUMMARY IF PAID ================= */
        if ($paidAmount >= $totalAmount && $totalAmount > 0) {
            $update = $conn->prepare("UPDATE summary SET payment_status = 'Y' WHERE quotation_id = ?");
            $update->bind_param("i", $related_id);
            $update->execute();
        }

        /* ================= COMMIT ================= */
        $conn->commit();

        echo json_encode(['success' => true, 'message' => 'Payment saved successfully']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Transaction failed']);
    }

    // CLOSE
    $conn->close();
} elseif (isset($_GET['action']) && $_GET['action'] === 'payment-history') {

    $id = intval($_GET['id'] ?? 0);

    $stmt = $conn->prepare(
        "SELECT * FROM payments WHERE related_id = ? ORDER BY id DESC"
    );
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'payments' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ]);
} elseif (isset($_GET['action']) && $_GET['action'] === 'pending-payment') {

    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID']);
        exit;
    }

    $stmt = $conn->prepare("SELECT COALESCE(MAX(total_amount), 0) AS total_amount,(COALESCE(MAX(total_amount), 0) - COALESCE(SUM(amount), 0)) AS pending_amount FROM payments WHERE related_id = ?");

    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        'success' => true,
        'total_amount'   => (float)$result['total_amount'],
        'pending_amount' => max(0, (float)$result['pending_amount'])
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
