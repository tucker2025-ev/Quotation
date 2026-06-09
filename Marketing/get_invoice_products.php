<?php

header('Content-Type: application/json');

// DB Connection
$conn = new mysqli("15.207.37.132", "cloud", "TUCKER_ser_sql", "marketing_new");

if ($conn->connect_error) {
    die(json_encode(["status" => false, "message" => "DB Connection failed"]));
}

// Validate input
$quotation_id = $_GET['quotation_id'] ?? 0;

if (!$quotation_id) {
    echo json_encode(["status" => false, "message" => "quotation_id required"]);
    exit;
}

// ✅ 1. Check quotation status
$sql_quotation = "SELECT order_status FROM quotations WHERE id = ? AND order_status = 'Y'";
$stmt_quotation = $conn->prepare($sql_quotation);
$stmt_quotation->bind_param("i", $quotation_id);
$stmt_quotation->execute();
$result_quotation = $stmt_quotation->get_result();

if ($result_quotation->num_rows > 0) {

    // Initialize
    $products = [];
    $gst_breakdown = [];
    $calc_gross = 0;
    $calc_net = 0;
    $calc_gst_total = 0;

    // ✅ 2. Fetch products
    $sql_products = "SELECT *, 
    ROUND(unit_price * quantity, 2) as subtotal,
    ROUND((unit_price * quantity) * (1 - COALESCE(discount_percent, 0) / 100), 2) as net_amount,
    ROUND(((unit_price * quantity) * (1 - COALESCE(discount_percent, 0) / 100)) * COALESCE(gst_percent, 18) / 100, 2) as gst_amount
FROM productss 
WHERE quotation_id = ?";

    $stmt_products = $conn->prepare($sql_products);
    $stmt_products->bind_param("i", $quotation_id);
    $stmt_products->execute();
    $result_products = $stmt_products->get_result();

    $item_counter = 1;

    while ($row = $result_products->fetch_assoc()) {
        $products[] = $row;

        $calc_gross += $row['subtotal'];
        $calc_net += $row['net_amount'];
        $calc_gst_total += $row['gst_amount'];

        $gst_rate = (float)($row['gst_percent'] ?? 18);

        if (!isset($gst_breakdown[$gst_rate])) {
            $gst_breakdown[$gst_rate] = [
                "amount" => 0,
                "items" => []
            ];
        }

        $gst_breakdown[$gst_rate]["amount"] += $row['gst_amount'];
        $gst_breakdown[$gst_rate]["items"][] = $item_counter;

        $item_counter++;
    }

    ksort($gst_breakdown);

    // ✅ 3. Totals
    $calc_discount_val = $calc_gross - $calc_net;
    $calc_grand_total = $calc_net + $calc_gst_total;

    $global_discount_percent = ($calc_gross > 0)
        ? ($calc_discount_val / $calc_gross) * 100
        : 0;

    // ✅ 4. Summary fallback
    $sql_summary = "SELECT * FROM summary WHERE quotation_id = ?";
    $stmt_summary = $conn->prepare($sql_summary);
    $stmt_summary->bind_param("i", $quotation_id);
    $stmt_summary->execute();
    $summary_db = $stmt_summary->get_result()->fetch_assoc();

    if (empty($products) && $summary_db) {
        $calc_grand_total = $summary_db['grand_total'];
    }

    // ✅ Final Response
    echo json_encode([
        "status" => true,
        "products" => $products,
        "gross" => number_format($calc_gross, 2, '.', ''),
        "net" => number_format($calc_net, 2, '.', ''),
        "gst_total" => number_format($calc_gst_total, 2, '.', ''),
        "grand_total" => number_format($calc_grand_total, 2, '.', ''),
        "discount_percent" => round($global_discount_percent, 2),
        "gst_breakdown" => $gst_breakdown
    ]);
} else {
    echo json_encode([
        "status" => false,
        "message" => "Order not confirmed.Please Check the payment"
    ]);
}
