<?php
// File: api/send_cpo_email.php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; 

$mail = new PHPMailer(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. POST required.']);
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$lead_id = isset($_POST['lead_id']) ? trim($_POST['lead_id']) : '';
$quotation_id = isset($_POST['quotation_id']) ? trim($_POST['quotation_id']) : '';

if (empty($email) || empty($quotation_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

// 1. Database Connection
$servername = "15.207.37.132";
$username = "cloud";
$password = "TUCKER_ser_sql";
$dbname = "marketing_new";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// 2. Fetch CMS_ID and Quotation details from the view
// Assuming quotation_id in your POST matches quotation_no in the view
$stmt_cms = $conn->prepare("SELECT cms_id FROM quotation_summary_view WHERE quotation_no = ? LIMIT 1");
$stmt_cms->bind_param("s", $quotation_id);
$stmt_cms->execute();
$cms_res = $stmt_cms->get_result()->fetch_assoc();
$cms_id = $cms_res['cms_id'] ?? 0;

// 3. Fetch Line Items
$stmt_items = $conn->prepare("SELECT product_name, unit_price, quantity, total_price FROM productss WHERE quotation_id = ?");
$stmt_items->bind_param("s", $quotation_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

$product_rows_html = "";
$subtotal = 0;
while ($item = $items_result->fetch_assoc()) {
    $subtotal += $item['total_price'];
    $product_rows_html .= "
        <tr style='border-bottom: 1px solid #f2f2f2;'>
            <td style='padding: 12px 0; font-size: 13px; color: #111;'>
                <div style='font-weight:bold;'>{$item['product_name']}</div>
            </td>
            <td align='center' style='padding: 12px 0; font-size: 13px;'>{$item['quantity']}</td>
            <td align='right' style='padding: 12px 0; font-size: 13px;'>₹ " . number_format($item['unit_price'], 2) . "</td>
            <td align='right' style='padding: 12px 0; font-size: 13px; font-weight:bold;'>₹ " . number_format($item['total_price'], 2) . "</td>
        </tr>";
}

// 4. Fetch Payment Details
$stmt_pay = $conn->prepare("SELECT payment_mode FROM payments WHERE related_id = ? ORDER BY id DESC LIMIT 1");
$stmt_pay->bind_param("s", $quotation_id);
$stmt_pay->execute();
$pay_res = $stmt_pay->get_result()->fetch_assoc();
$pay_mode = $pay_res['payment_mode'] ?? 'N/A';

$paid_query = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE related_id = '$quotation_id'");
$amount_received = (float)$paid_query->fetch_row()[0];
$balance_due = max(0, $subtotal - $amount_received);

// 5. Logic for Activation Link based on CMS_ID
$show_activation_box = true;
$cpo_link = "";

// Check if link already exists for this lead
$check = $conn->prepare("SELECT is_used, cpo_link FROM cpo_activation_links WHERE lead_id = ?");
$check->bind_param("i", $lead_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $link_data = $res->fetch_assoc();
    if ($link_data['is_used'] == '1') {
        $show_activation_box = false; 
    } else {
        $cpo_link = $link_data['cpo_link'];
    }
}

// If no link exists and we need one, generate it based on CMS_ID
if ($show_activation_box && empty($cpo_link)) {
    $token = bin2hex(random_bytes(32));
    
    // CONDITION: IF CMS_ID is 7, send Partner link, else send CPO link
    if ($cms_id == 7) {
        $cpo_link = "https://star.tuckermotors.com/Home_app/partner/create_partner.php?token=" . $token;
        $title = "Partner Registration";
        $desc = "Your Tucker EV Partner account is ready. Please complete your profile to access the management dashboard.";
    } else {
        $cpo_link = "https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=" . $token;
        $title = "Create your CPO ID";
        $desc = "Your Tucker EV CPO partner account is ready for activation. Create your unique ID to configure your branded charging platform.";
    }

    $ins = $conn->prepare("INSERT INTO cpo_activation_links (token, quotation_id, lead_id, created_at, cpo_link, is_used) VALUES (?, ?, ?, NOW(), ?, '0')");
    $ins->bind_param("siis", $token, $quotation_id, $lead_id, $cpo_link);
    $ins->execute();
} else {
    // Determine title/desc for existing links
    $title = ($cms_id == 7) ? "Partner Registration" : "Create your CPO ID";
    $desc = ($cms_id == 7) ? "Complete your partner profile." : "Activate your unique CPO ID.";
}
$conn->close();

// 6. Build the UI/HTML Section
$activation_html = "";
if ($show_activation_box) {
    $activation_html = "
    <div style='background-color: #0b0b0b; color: white; border-radius: 12px; padding: 35px; margin-top: 30px; position: relative; overflow: hidden;'>
        <p style='color:#666; font-size:10px; text-transform:uppercase; margin:0; letter-spacing:1px;'>Action Required</p>
        <h2 style='margin:10px 0; font-size:22px; color:#ffffff;'>$title</h2>
        <p style='color:#bbb; font-size:13px; line-height:1.6;'>$desc</p>
        <a href='$cpo_link' style='background-color:#E31E24; color:#ffffff; padding:14px 28px; text-decoration:none; border-radius:4px; font-weight:bold; display:inline-block; margin-top:15px; font-size:13px;'> Complete Setup →</a>
        <div style='position:absolute; bottom:-30px; right:10px; font-size:100px; font-weight:bold; color:rgba(255,255,255,0.03); font-family: sans-serif;'>EV</div>
    </div>";
}

// 7. Email Template Construction
$primary_red = "#E31E24";
$logo_url = "https://tuckermotors.com/images/logo.png";

$message = "
<!DOCTYPE html>
<html>
<head><meta charset='utf-8'></head>
<body style='font-family: Arial, sans-serif; background-color: #f7f7f7; margin: 0; padding: 0;'>
    <div style='width: 100%; max-width: 600px; margin: 25px auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #eeeeee;'>
        <div style='background-color: #0b0b0b; padding: 25px 35px;'>
            <table width='100%'><tr>
                <td><img src='$logo_url' height='30' alt='Tucker EV'></td>
                <td align='right'><span style='border:1px solid #333; padding:4px 12px; border-radius:20px; font-size:10px; color:#888; text-transform:uppercase;'>OFFICIAL RECEIPT</span></td>
            </tr></table>
        </div>

        <div style='background-color: #FFF9F9; border-left: 4px solid $primary_red; padding: 18px 35px; border-bottom: 1px solid #f2f2f2;'>
            <b style='color:$primary_red; font-size:15px;'>✔ Payment Confirmed</b>
        </div>

        <div style='padding: 35px;'>
            <h1 style='margin:0 0 15px 0; font-size:26px; color: #111;'>Thank you for your order.</h1>
            <p style='color:#666; font-size:14px;'>We have received your payment for Quotation #$quotation_id.</p>

            <table width='100%' style='border-collapse:collapse; margin-top:25px;'>
                <tr style='font-size:10px; color:#999; text-transform:uppercase; border-bottom:1px solid #eee;'>
                    <th align='left' style='padding-bottom:10px;'>Items</th>
                    <th align='center' style='padding-bottom:10px;'>Qty</th>
                    <th align='right' style='padding-bottom:10px;'>Total</th>
                </tr>
                $product_rows_html
            </table>

            <div style='background-color: #F9FAFB; border-radius: 8px; padding: 20px; margin-top: 25px;'>
                <table width='100%' style='font-size:13px;'>
                    <tr><td>Subtotal</td><td align='right'>₹ " . number_format($subtotal, 2) . "</td></tr>
                    <tr><td style='color:#10B981;'>Paid</td><td align='right' style='color:#10B981;'>- ₹ " . number_format($amount_received, 2) . "</td></tr>
                    <tr><td style='font-size:16px; padding-top:10px;'><b>Balance Due</b></td><td align='right' style='font-size:18px; color:$primary_red; padding-top:10px;'><b>₹ " . number_format($balance_due, 2) . "</b></td></tr>
                </table>
            </div>

            $activation_html

        </div>
    </div>
</body>
</html>";

// 8. SMTP Configuration and Send
try {
    $mail->isSMTP();
    $mail->Host       = 'vps.hul-brupack.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'support@tuckermotors.com';
    $mail->Password   = 'SUP@rt1000'; 
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';

    $mail->setFrom('support@tuckermotors.com', 'Tucker Motors');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = "Payment Confirmation - Order #$quotation_id";
    $mail->Body    = $message;

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Email sent successfully with link logic for CMS ID ' . $cms_id]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
}