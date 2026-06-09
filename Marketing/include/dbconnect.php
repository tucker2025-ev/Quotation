<?php
$cms_referred = "tucker";
$cms_color = "#3d56d8";

error_reporting(0);
date_default_timezone_set("Asia/Kolkata");
ini_set('max_execution_time', 450);
set_time_limit(300);


$livehost_name = "13.233.175.29";
$livehost_user = "cloud";
$livehost_pass = "TUCKER_ser_sql";
$livehost_db = "bigtot_cms";

$liveconnect = mysqli_connect($livehost_name, $livehost_user, $livehost_pass, $livehost_db) or die($liveconnect);

$host_name = "15.207.37.132";
$host_user = "cloud";
$host_pass = "TUCKER_ser_sql";
$host_db = "bigtot_cms";

$base_url = "http://cms.tuckerio.bigtot.in/";

$connect = mysqli_connect($host_name, $host_user, $host_pass, $host_db) or die($connect);


// Connection B: Target database (Server 2)
$target_server   = "15.207.37.132";
$target_user     = "cloud";
$target_password = "TUCKER_ser_sql";
$target_db       = "order_portal";

$target_conn = new mysqli($target_server, $target_user, $target_password, $target_db);
if ($target_conn->connect_error) {
    die("Target Connection failed: " . $target_conn->connect_error);
}


$host = "15.207.37.132";
$user = "cloud";
$pass = "TUCKER_ser_sql";
$db   = "marketing_new";

try {
    $con = new mysqli($host, $user, $pass, $db);
    $con->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

$root_dir = "https://" . $_SERVER['HTTP_HOST'] . "/";
define('ROOT_DIR', $root_dir);

if (!function_exists('writeLog')) {
    function writeLog($message, $logDir = 'logs/', $maxSizeMB = 10)
    {
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $date = date('Y-m-d');
        $baseFilename = "$logDir/log-$date";
        $ext = '.log';

        $index = 0;
        do {
            $filename = $baseFilename . ($index === 0 ? '' : "-$index") . $ext;
            $index++;
        } while (file_exists($filename) && filesize($filename) > ($maxSizeMB * 1024 * 1024));

        $logMessage = "[" . date('Y-m-d H:i:s') . "] $message" . PHP_EOL;
        file_put_contents($filename, $logMessage, FILE_APPEND);
    }
}
