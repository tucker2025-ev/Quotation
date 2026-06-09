<?php
header("Content-Type: application/json");

// 🔽 Read JSON body
$input = json_decode(file_get_contents("php://input"), true);
$cpo_filter = $input['cpo_id'] ?? null;

$liveconnect = mysqli_connect("13.233.175.29", "cloud", "TUCKER_ser_sql", "bigtot_cms") or die("DB Error");

// ✅ MAIN DB
$conn = new mysqli("15.207.37.132", "cloud", "TUCKER_ser_sql", "marketing_new");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$final_data = [];
$cpo_map = [];
$payment_history = [];

$total_grand_sum = 0;
$total_paid_sum = 0;
$total_pending_sum = 0;

/* =========================
   ✅ STEP 1: GET CPO DATA
========================= */
$sql = "SELECT cpo_id, cpo_name FROM fca_cpo where cpo_id = '" . $conn->real_escape_string($cpo_filter) . "'";
$cpo_result = $liveconnect->query($sql);

while ($row = $cpo_result->fetch_assoc()) {
    $cpo_map[$row['cpo_id']] = $row['cpo_name'];
}

/* =========================
   ✅ STEP 2: MAIN QUERY (with filter)
========================= */
// 🔥 Dynamic WHERE condition
$where = "q.order_status = 'Y'";
if (!empty($cpo_filter)) {
    $where .= " AND q.cpo_id = '" . $conn->real_escape_string($cpo_filter) . "'";
} else {
    echo json_encode([
        'success' => false,
        'data' => "CPO Id is empty"
    ]);
    return;
}

$sql = "SELECT GROUP_CONCAT(q.id) AS quotation_ids,q.cpo_id,SUM(s.grand_total) AS grand_total FROM quotations q INNER JOIN summary s ON s.quotation_id = q.id WHERE $where GROUP BY q.client_id ORDER BY q.created_at DESC";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {

    $quotation_ids = $row['quotation_ids'];

    /* ========================= STEP 3: PAYMENT QUERY ========================= */
    $pay_sql = "SELECT amount, total_amount, created_at, payment_mode,payment_reference FROM payments WHERE related_id IN ($quotation_ids)";

    $pay_result = $conn->query($pay_sql);

    $paid_amount = 0;
    $payment_history = [];

    while ($pay_row = $pay_result->fetch_assoc()) {

        // ✅ Sum of all payments
        $paid_amount += $pay_row['amount'] ?? 0;

        // ✅ Store history
        $payment_history[] = [
            'amount'        => $pay_row['amount'],
            'payment_mode'  => $pay_row['payment_mode'],
            'date'          => $pay_row['created_at'],
            'payment_reference'          => $pay_row['payment_reference']

        ];
    }

    $grand_total = $row['grand_total'] ?? 0;
    $pending = $grand_total - $paid_amount;

    $total_grand_sum += $grand_total;
    $total_paid_sum += $paid_amount;
    $total_pending_sum += $pending;

    $final_data[] = [
        'cpo_id'        => $row['cpo_id'],
        'cpo_name'      => $cpo_map[$row['cpo_id']] ?? '-',
        'grand_total'   => $grand_total,
        'paid_amount'   => $paid_amount,
        'pending_amount' => $grand_total - $paid_amount,
        'payment_history' => $payment_history
    ];
}

/* =========================  ✅ INVENTORY QUERY FIX  ========================= */
$inventry_result = $conn->query("SELECT cpo_id, grand_total, subtotal FROM inventory_summary WHERE cpo_id = '$cpo_filter'");

while ($row = $inventry_result->fetch_assoc()) {

    $inpay_result = $conn->query("SELECT amount, total_amount, created_at, payment_mode, payment_reference FROM inventry_payments WHERE cpo_id = '$cpo_filter'");

    $paid_amount = 0;
    $inpayment_history = [];

    if ($inpay_result && $inpay_result->num_rows > 0) {
        while ($pay_row = $inpay_result->fetch_assoc()) {
            $paid_amount += $pay_row['amount'] ?? 0;

            $inpayment_history[] = [
                'amount' => $pay_row['amount'],
                'payment_mode' => $pay_row['payment_mode'],
                'date' => $pay_row['created_at'],
                'payment_reference' => $pay_row['payment_reference']
            ];
        }
    }


    $grand_total = $row['grand_total'] ?? 0;
    $pending = $grand_total - $paid_amount;
    $total_grand_sum += $grand_total;
    $total_paid_sum += $paid_amount;
    $total_pending_sum += $pending;

    $final_data[] = [
        'cpo_id' => $row['cpo_id'],
        'cpo_name' => $cpo_map[$row['cpo_id']] ?? '-',
        'grand_total' => $grand_total,
        'paid_amount' => $paid_amount,
        'pending_amount' => $grand_total - $paid_amount,
        'payment_history' => $inpayment_history
    ];
}

/* =========================
   ✅ OUTPUT
========================= */
echo json_encode([
    'success' => true,
    'sumgrand_total' => $total_grand_sum,
    'total_paid_amount' => $total_paid_sum,
    'pending_amount' => $total_pending_sum,
    'data' => $final_data
]);

$conn->close();
