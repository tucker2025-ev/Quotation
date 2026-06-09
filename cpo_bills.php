<?php
$livehost_name = "13.233.175.29";
$livehost_user = "cloud";
$livehost_pass = "TUCKER_ser_sql";
$livehost_db = "bigtot_cms";

$liveconnect = mysqli_connect($livehost_name, $livehost_user, $livehost_pass, $livehost_db) or die($liveconnect);

// ✅ MAIN DB
$conn = new mysqli("15.207.37.132", "cloud", "TUCKER_ser_sql", "marketing_new");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$final_data = [];
$cpo_map = [];

/* =========================
   ✅ STEP 1: GET CPO DATA
========================= */
$sql = "SELECT cpo_id, cpo_name FROM fca_cpo ORDER BY sno ASC";
$cpo_result = $liveconnect->query($sql);

while ($row = $cpo_result->fetch_assoc()) {
    $cpo_map[$row['cpo_id']] = $row['cpo_name'];
}

/* =========================
   ✅ STEP 2: MAIN QUERY
========================= */
$sql = "
SELECT 
    GROUP_CONCAT(q.id) AS quotation_ids,
    q.cpo_id,
    SUM(s.grand_total) AS grand_total
FROM quotations q
INNER JOIN summary s ON s.quotation_id = q.id
WHERE q.order_status = 'Y'
GROUP BY q.client_id
ORDER BY q.created_at DESC
";

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {

    $quotation_ids = $row['quotation_ids'];

    /* =========================
       ✅ STEP 3: PAYMENT QUERY
    ========================= */
    $pay_sql = "
        SELECT 
            SUM(amount) AS amount 
        FROM payments 
        WHERE related_id IN ($quotation_ids)
    ";
    
    $pay_result = $conn->query($pay_sql);
    $pay_row = $pay_result->fetch_assoc();

    $paid_amount = $pay_row['amount'] ?? 0;
    $grand_total = $row['grand_total'] ?? 0;

    /* =========================
       ✅ FINAL OUTPUT
    ========================= */
    $final_data[] = [
        'cpo_id'        => $row['cpo_id'],
        'cpo_name'      => $cpo_map[$row['cpo_id']] ?? '-',
        'grand_total'   => $grand_total,
        'paid_amount'   => $paid_amount,
        'pending_amount'=> $grand_total - $paid_amount
    ];
}

/* =========================
   ✅ OUTPUT
========================= */
echo json_encode([
    'success' => true,
    'data' => $final_data
]);

$conn->close();
?>