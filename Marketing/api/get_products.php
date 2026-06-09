<?php
header('Content-Type: application/json');

$url = "https://cgrmart.com/api/get-productview";
echo file_get_contents($url);