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

$email        = isset($_POST['email'])        ? trim($_POST['email'])        : '';
$lead_id      = isset($_POST['lead_id'])      ? trim($_POST['lead_id'])      : '';
$quotation_id = isset($_POST['quotation_id']) ? trim($_POST['quotation_id']) : '';

if (empty($email) || empty($quotation_id) || empty($lead_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters.']);
    exit;
}

// ── 1. Database Connection ────────────────────────────────────────────────────
$servername = "15.207.37.132";
$username   = "cloud";
$password   = "TUCKER_ser_sql";
$dbname     = "marketing_new";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

// ── 2. Fetch Line Items ───────────────────────────────────────────────────────
$stmt_items = $conn->prepare(
    "SELECT product_name, unit_price, quantity, total_price FROM productss WHERE quotation_id = ?"
);
$stmt_items->bind_param("i", $quotation_id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

$product_rows_html = "";
$subtotal          = 0;
while ($item = $items_result->fetch_assoc()) {
    $subtotal += $item['total_price'];
    $product_rows_html .= "
        <tr style='border-bottom: 1px solid #f2f2f2;'>
            <td style='padding: 12px 0; font-size: 13px; color: #111;'>
                <div style='font-weight:bold;'>{$item['product_name']}</div>
            </td>
            <td align='center' style='padding: 12px 0; font-size: 13px;'>{$item['quantity']}</td>
            <td align='right'  style='padding: 12px 0; font-size: 13px;'>₹ " . number_format($item['unit_price'],  2) . "</td>
            <td align='right'  style='padding: 12px 0; font-size: 13px; font-weight:bold;'>₹ " . number_format($item['total_price'], 2) . "</td>
         </tr>";
}

// ── 3. Fetch Payment Details ──────────────────────────────────────────────────
$stmt_pay = $conn->prepare(
    "SELECT payment_mode FROM payments WHERE related_id = ? ORDER BY id DESC LIMIT 1"
);
$stmt_pay->bind_param("i", $quotation_id);
$stmt_pay->execute();
$pay_res  = $stmt_pay->get_result()->fetch_assoc();
$pay_mode = $pay_res['payment_mode'] ?? 'N/A';

$paid_query      = $conn->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE related_id = $quotation_id");
$amount_received = (float) $paid_query->fetch_row()[0];
$balance_due     = max(0, $subtotal - $amount_received);

// ── 4. Fetch CMS_ID ───────────────────────────────────────────────────────────
$cms_id   = 0;
$stmt_cms = $conn->prepare("SELECT cms_id FROM quotation_summary_view WHERE lead_id = ?");
$stmt_cms->bind_param("s", $lead_id);
$stmt_cms->execute();
$cms_res = $stmt_cms->get_result();
if ($cms_row = $cms_res->fetch_assoc()) {
    $cms_id = (int) $cms_row['cms_id'];
}

// ── 5. Determine the Correct CPO / FCC Link ──────────────────────────────────
$show_activation_box = true;
$cpo_link            = "";
$existing_token      = "";

// Check for an existing record
$check = $conn->prepare(
    "SELECT is_used, cpo_link, token FROM cpo_activation_links WHERE lead_id = ?"
);
$check->bind_param("s", $lead_id);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    $link_data = $res->fetch_assoc();

    if ($link_data['is_used'] == '1') {
        // Already activated – hide activation box
        $show_activation_box = false;
    } else {
        $existing_token = $link_data['token'];
        
        if ($cms_id == 7) {
            // FCC flow: use fcc.php with token
            if (strpos($link_data['cpo_link'], 'fcg.php') !== false && !empty($link_data['token'])) {
                // Reuse existing valid FCC token
                $cpo_link = "https://star.tuckermotors.com/Home_app/partner/fcg.php?token=" . $link_data['token'] . "&lead_id=" . $lead_id;
            } else {
                // Generate new token for FCC
                $new_token = bin2hex(random_bytes(32));
                $cpo_link = "https://star.tuckermotors.com/Home_app/partner/fcg.php?token=" . $new_token . "&lead_id=" . $lead_id;
                
                $upd = $conn->prepare(
                    "UPDATE cpo_activation_links SET token = ?, cpo_link = ? WHERE lead_id = ?"
                );
                $upd->bind_param("sss", $new_token, $cpo_link, $lead_id);
                $upd->execute();
                $existing_token = $new_token;
            }
        } else {
            // Standard CPO flow: use create_cpo.php with token
            if (strpos($link_data['cpo_link'], 'create_cpo.php') !== false && !empty($link_data['token'])) {
                // Reuse existing valid CPO token
                $cpo_link = "https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=" . $link_data['token'] . "&lead_id=" . $lead_id;
            } else {
                // Generate new token for CPO
                $new_token = bin2hex(random_bytes(32));
                $cpo_link = "https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=" . $new_token . "&lead_id=" . $lead_id;
                
                $upd = $conn->prepare(
                    "UPDATE cpo_activation_links SET token = ?, cpo_link = ? WHERE lead_id = ?"
                );
                $upd->bind_param("sss", $new_token, $cpo_link, $lead_id);
                $upd->execute();
                $existing_token = $new_token;
            }
        }
    }
}

// No existing record at all → INSERT
if ($show_activation_box && empty($cpo_link)) {
    $token = bin2hex(random_bytes(32));
    
    if ($cms_id == 7) {
        $cpo_link = "https://star.tuckermotors.com/Home_app/partner/fcg.php?token=" . $token . "&lead_id=" . $lead_id;
    } else {
        $cpo_link = "https://star.tuckermotors.com/TuckerApp/cpo/create_cpo.php?token=" . $token . "&lead_id=" . $lead_id;
    }

    $ins = $conn->prepare(
        "INSERT INTO cpo_activation_links (token, quotation_id, lead_id, created_at, cpo_link, is_used)
         VALUES (?, ?, ?, NOW(), ?, '0')"
    );
    $ins->bind_param("siis", $token, $quotation_id, $lead_id, $cpo_link);
    $ins->execute();
    $existing_token = $token;
}

$conn->close();

// ── 6. Build Activation HTML Block ───────────────────────────────────────────
$activation_html = "";
if ($show_activation_box) {
    $button_label = ($cms_id == 7) ? "Partner Portal →" : "CPO Link →";
    $activation_html = "
    <div style='background-color: #0b0b0b; color: white; border-radius: 12px; padding: 35px;
                margin-top: 30px; position: relative; overflow: hidden;'>
        <p style='color:#666; font-size:10px; text-transform:uppercase; margin:0; letter-spacing:1px;'>Partner Activation</p>
        <h2 style='margin:10px 0; font-size:22px; color:#ffffff;'>Create your CPO ID</h2>
        <p style='color:#bbb; font-size:13px; line-height:1.6;'>
            Your Tucker EV partner account is ready for activation.
            Click the link below to configure your account.
        </p>
        <a href='$cpo_link'
           style='background-color:#E31E24; color:#ffffff; padding:14px 28px; text-decoration:none;
                  border-radius:4px; font-weight:bold; display:inline-block; margin-top:15px; font-size:13px;'>
           $button_label
        </a>
       
    </div>";
}

// ── 7. Build Full Email Template ──────────────────────────────────────────────
$primary_red = "#E31E24";
$logo_url    = "https://tuckermotors.com/images/logo.png";

$message = "
<!DOCTYPE html>
<html>
<head><meta charset='utf-8'></head>
<body style='font-family: Arial, sans-serif; background-color: #f7f7f7; margin: 0; padding: 0;'>
    <div style='width: 100%; max-width: 600px; margin: 25px auto; background: #ffffff;
                border-radius: 8px; overflow: hidden; border: 1px solid #eeeeee;'>

        <!-- Header -->
        <div style='background-color: #0b0b0b; padding: 25px 35px;'>
            <table width='100%'><tr>
                <td><img src='$logo_url' height='30' alt='Tucker EV Charger'></td>
                <td align='right'>
                    <span style='border:1px solid #333; padding:4px 12px; border-radius:20px;
                                 font-size:10px; color:#888; text-transform:uppercase; letter-spacing:1px;'>
                        PARTNER NOTICE
                    </span>
                </td>
            </tr></table>
        </div>

        

        <!-- Body -->
        <div style='padding: 35px;'>
            <p style='color:#999; font-size:11px; text-transform:uppercase; margin:0; letter-spacing:1px;'>Hello, Partner</p>
            <h1 style='margin:10px 0; font-size:28px;  color: #111;'>Payment confirmed.</h1>
            <p style='color:#666; font-size:14px; line-height:1.6;'>
                Thank you for partnering with Tucker EV Charger. We have successfully received your payment.
            </p>

            <!-- Order Items Table -->
            <p style='font-size:11px; font-weight:bold; color:#999; text-transform:uppercase; margin-top:35px;'>Order Items</p>
            <table width='100%' style='border-collapse:collapse; margin-top:10px;'>
                <tr style='font-size:10px; color:#999; text-transform:uppercase; border-bottom:1px solid #eeeeee;'>
                    <th align='left'   style='padding-bottom:10px;'>Product</th>
                    <th align='center' style='padding-bottom:10px;'>Qty</th>
                    <th align='right'  style='padding-bottom:10px;'>Unit Price</th>
                    <th align='right'  style='padding-bottom:10px;'>Amount</th>
                </tr>
                $product_rows_html
            </table>

            <!-- Summary Box -->
            <div style='background-color: #F9FAFB; border-radius: 8px; padding: 25px; margin-top: 30px;'>
                <table width='100%' style='font-size:14px; border-spacing:0 8px;'>
                    <tr>
                        <td style='color:#888;'>Order ID:</td>
                        <td align='right'><b>#ORD-WL-$lead_id</b></td>
                    </tr>
                    <tr>
                        <td style='color:#888;'>Date:</td>
                        <td align='right'>" . date('d M Y') . "</td>
                    </tr>
                    <tr>
                        <td style='color:#888;'>Payment Method:</td>
                        <td align='right'>$pay_mode</td>
                    </tr>
                    <tr>
                        <td colspan='2'><hr style='border:none; border-top:1px solid #e5e5e5; margin:10px 0;'></td>
                    </tr>
                    <tr>
                        <td><b>Subtotal:</b></td>
                        <td align='right'><b>₹ " . number_format($subtotal, 2) . "</b></td>
                    </tr>
                    <tr>
                        <td style='color:#10B981;'>✔ Amount Received:</td>
                        <td align='right' style='color:#10B981; font-weight:bold;'>₹ " . number_format($amount_received, 2) . "</td>
                    </tr>
                    <tr>
                        <td style='color:$primary_red; font-size:17px;  padding-top:10px;'>Balance Due:</td>
                        <td align='right' style='color:$primary_red; font-size:22px; font-weight:bold;
                                                  padding-top:10px; '>
                            ₹ " . number_format($balance_due, 2) . "
                        </td>
                    </tr>
                </table>
            </div>

            $activation_html

            <p style='text-align:center; font-size:11px; color:#999; margin-top:40px;'>
                For order queries, reach us at
                <a href='mailto:support@tuckermotors.com' style='color:$primary_red; text-decoration:none;'>
                    support@tuckermotors.com
                </a>
            </p>
        </div>

        <!-- Footer -->
        <div style='background-color:#f9f9f9; padding:20px; text-align:center;
                    border-top:1px solid #eee; font-size:11px; color:#999;'>
            Tucker Motors Private Limited • Privacy Policy • Terms
        </div>
    </div>
</body>
</html>";

// ── 8. Send Email ─────────────────────────────────────────────────────────────
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
    $mail->Subject = "Payment Receipt: Order #ORD-WL-$lead_id Confirmed";
    $mail->Body    = $message;

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Email sent. Token link assigned.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
}
?>