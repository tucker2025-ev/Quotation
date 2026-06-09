<?php
header('Content-Type: application/json');

if (!isset($_GET['product_id']) || empty($_GET['product_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Product ID missing'
    ]);
    exit;
}

$productId = urlencode($_GET['product_id']);

$apiUrl = "https://cgrmart.com/api/get-products?product_id={$productId}";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
]);

$response = curl_exec($ch);

if ($response === false) {
    echo json_encode([
        'success' => false,
        'message' => curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

curl_close($ch);
echo $response;
exit;
