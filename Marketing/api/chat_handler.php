<?php
include '../include/dbconnect.php';
date_default_timezone_set('Asia/Kolkata');

/* =====================
   SAVE NOTE (POST)
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $lead_id = (int)($_POST['lead_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');

    if ($lead_id > 0 && $message !== '') {
        $stmt = $con->prepare(
            "INSERT INTO lead_messages (lead_id, message, created_at) VALUES (?, ?, NOW())"
        );
        $stmt->bind_param("is", $lead_id, $message);
        $stmt->execute();
        $stmt->close();
    }

    exit;
}

/* =====================
   LOAD ACTIVITY LOG (GET)
===================== */
if (isset($_GET['lead_id'])) {

    $lead_id = (int)$_GET['lead_id'];

    $stmt = $con->prepare(
        "SELECT message, created_at FROM lead_messages WHERE lead_id = ? ORDER BY created_at DESC"
    );
    $stmt->bind_param("i", $lead_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo '<div class="log-empty">No activity found.</div>';
    }

    while ($row = $result->fetch_assoc()) {
?>
        <div class="log-item">
            <div class="log-message">
                <?= nl2br(htmlspecialchars($row['message'])) ?>
            </div>
            <div class="log-time">
                <?= date(
                    'd M Y · h:i A',
                    strtotime($row['created_at'] . ' +5 hours 30 minutes')
                ) ?>

            </div>
        </div>
<?php
    }

    $stmt->close();
}
