<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Kolkata');

$action = $_POST['action'] ?? '';
$id     = $_POST['id'] ?? '';
$reason = $_POST['reason'] ?? '';

if ($_POST['action'] == 'Franchise_delete') {

    $ch = curl_init("https://cgrmart.com/tucker_website/Franchise.php");

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            "action" => $action,
            "id" => $id,
            "reason" => $reason
        ],
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    echo $response;
}

if ($_POST['action'] == 'Revenue_delete') {

    $ch = curl_init("https://cgrmart.com/tucker_website/Franchise.php");

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            "action" => $action,
            "id" => $id,
            "reason" => $reason
        ],
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    echo $response;
}

if ($_POST['action'] == 'Inquiries_delete') {

    $ch = curl_init("https://cgrmart.com/tucker_website/Franchise.php");

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => [
            "action" => $action,
            "id" => $id,
            "reason" => $reason
        ],
        CURLOPT_RETURNTRANSFER => true
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    echo $response;
}