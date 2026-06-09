<?php
// Include session configuration
require_once 'include/session_config.php';
requireLoginAndAccess('lead1.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

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

try {
    $con_live = new mysqli("13.233.175.29", "cloud", "TUCKER_ser_sql", "bigtot_cms");
    $con_live->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

/**
 * Mapping Customer Type to cms_id
 */
function getCmsId($customerTypeId)
{
    $map = [1 => 8, 2 => 8, 3 => 8, 4 => 8, 5 => 7, 6 => 3, 7 => 5];
    return isset($map[$customerTypeId]) ? $map[$customerTypeId] : 8;
}

// -------------------- INSERT OPERATION --------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'insert') {
    $fullName       = trim($_POST['fullName']);
    $salutation     = trim($_POST['salutation']);
    $email          = trim($_POST['email']);
    $phone          = trim($_POST['phoneNumber']);
    $address        = trim($_POST['address']);
    $address2       = trim($_POST['address2'] ?? '');
    $city           = trim($_POST['city'] ?? '');
    $state          = trim($_POST['state'] ?? '');
    $pincode        = (int) ($_POST['pincode'] ?? 0);
    $customerTypeId = (int) $_POST['customerType'];
    $companyName    = trim($_POST['companyName'] ?? '');
    $gstNumber      = trim($_POST['gstNumber'] ?? '');
    $leadSourceId   = (int) $_POST['leadSource'];
    $notes          = trim($_POST['notes'] ?? '');
    $cms_id         = getCmsId($customerTypeId);

    // Generate CPO ID
    $result = $con_live->query("SELECT cpo_id FROM fca_cpo ORDER BY sno DESC LIMIT 1");
    $lastRow = $result->fetch_assoc();
    $nextNumber = 1;
    if ($lastRow && !empty($lastRow['cpo_id'])) {
        preg_match('/AA(\d+)$/', $lastRow['cpo_id'], $matches);
        $nextNumber = (int)($matches[1] ?? 0) + 1;
    }
    $stateCode = strtoupper(substr($state, 0, 2));
    $numberFormatted = str_pad($nextNumber, 2, '0', STR_PAD_LEFT);
    $cpo_id = "T{$stateCode}-AA{$numberFormatted}";

    $sql = "INSERT INTO leads 
            (parent_id, salutation, full_name, email, phone_number, address, address2, city, state, pincode, customer_type_id, company_name, gst_number, status_id, source_id, notes, cms_id, cpo_id) 
            VALUES (?,?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?,?,?)";

    try {
        $stmt = $con->prepare($sql);
        $stmt->bind_param(
            "issssssssiississs",
            $_SESSION["user_id"],
            $salutation,
            $fullName,
            $email,
            $phone,
            $address,
            $address2,
            $city,
            $state,
            $pincode,
            $customerTypeId,
            $companyName,
            $gstNumber,
            $leadSourceId,
            $notes,
            $cms_id,
            $cpo_id
        );
        $stmt->execute();
        echo "success";
    } catch (Exception $e) {
        echo "error: " . $e->getMessage();
    }
    $con->close();
    exit;
}

// -------------------- UPDATE OPERATION --------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'update') {
    $leadId         = (int) $_POST['leadId'];
    $fullName       = trim($_POST['fullName']);
    $salutation     = trim($_POST['salutation']);
    $email          = trim($_POST['email']);
    $phone          = trim($_POST['phoneNumber']);
    $address        = trim($_POST['address']);
    $address2       = trim($_POST['address2'] ?? '');
    $city           = trim($_POST['city'] ?? '');
    $state          = trim($_POST['state'] ?? '');
    $pincode        = (int) ($_POST['pincode'] ?? 0);
    $customerTypeId = (int) $_POST['customerType'];
    $companyName    = trim($_POST['companyName'] ?? '');
    $gstNumber      = trim($_POST['gstNumber'] ?? '');
    $leadSourceId   = (int) $_POST['leadSource'];
    $notes          = trim($_POST['notes'] ?? '');
    $cms_id         = getCmsId($customerTypeId);

    $sql = "UPDATE leads SET 
            salutation = ?, full_name = ?, email = ?, phone_number = ?, 
            address = ?, address2 = ?, city = ?, state = ?, 
            pincode = ?, customer_type_id = ?, company_name = ?, 
            gst_number = ?, source_id = ?, notes = ?, cms_id = ? 
            WHERE id = ?";

    try {
        $stmt = $con->prepare($sql);
        $stmt->bind_param(
            "ssssssssiissisii",
            $salutation,
            $fullName,
            $email,
            $phone,
            $address,
            $address2,
            $city,
            $state,
            $pincode,
            $customerTypeId,
            $companyName,
            $gstNumber,
            $leadSourceId,
            $notes,
            $cms_id,
            $leadId
        );
        $stmt->execute();
        echo "success";
    } catch (Exception $e) {
        echo "error: " . $e->getMessage();
    }
    $con->close();
    exit;
}

// -------------------- DELETE & FETCH --------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'delete') {
    $leadId = (int) $_POST['leadId'];
    $stmt = $con->prepare("DELETE FROM leads WHERE id = ?");
    $stmt->bind_param("i", $leadId);
    echo ($stmt->execute()) ? "success" : "error";
    $con->close();
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'fetch_single') {
    $leadId = (int) $_POST['leadId'];
    $stmt = $con->prepare("SELECT * FROM leads WHERE id = ?");
    $stmt->bind_param("i", $leadId);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_assoc());
    $con->close();
    exit;
}

// -------------------- DROP DOWNS & LISTING --------------------
function fetchDropdown($con, $table, $nameColumn)
{
    $data = [];
    $result = $con->query("SELECT id, {$nameColumn} AS name FROM {$table} ORDER BY id ASC");
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}
$customerTypes = fetchDropdown($con, "customer_types", "type_name");
$leadSources   = fetchDropdown($con, "lead_sources", "source_name");

$leads = [];
$userId = $_SESSION["user_id"];
$masterId = $_SESSION["master_id"];
$where = ($masterId == 2) ? "1" : "l.parent_id = $userId";
$sql = "SELECT l.*, c.type_name AS customer_type, src.source_name AS source, um.user_name
        FROM leads l
        LEFT JOIN customer_types c ON l.customer_type_id = c.id
        LEFT JOIN lead_sources src ON l.source_id = src.id
        LEFT JOIN bigtot_cms.user_management um ON l.parent_id = um.user_id
        WHERE $where AND l.source_id != '8'
        ORDER BY l.id DESC";
$result = $con->query($sql);
while ($row = $result->fetch_assoc()) {
    $leads[] = $row;
}
$con->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads Management | Tucker Motors</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="https://station.cms.tuckermotors.com/asset/images/favicon.ico">

    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --bg-body: #F3F4F6;
            --bg-card: #FFFFFF;
            --text-main: #111827;
            --text-muted: #6B7280;
            --border: #E5E7EB;
            --sidebar-w: 260px;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            font-size: 14px;
        }

        .main-content {
            margin-left: var(--sidebar-w);
            padding: 30px;
            min-height: 100vh;
            transition: 0.3s;
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 20px;
        }

        .search-wrap {
            position: relative;
            flex: 1;
            max-width: 400px;
        }

        .search-wrap input {
            width: 100%;
            padding: 10px 15px 10px 40px;
            border-radius: 8px;
            border: 1px solid var(--border);
            outline: none;
        }

        .search-wrap i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .btn-add {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .btn-add:hover {
            background: var(--primary-hover);
        }

        /* Table */
        .table-card {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 14px 20px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: #F9FAF7;
            color: var(--text-muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        tr:hover {
            background: #FDFDFD;
        }

        .action-btn {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            border: none;
            color: white;
            cursor: pointer;
            transition: 0.2s;
        }

        .btn-edit {
            background: #F59E0B;
        }

        .btn-del {
            background: #EF4444;
        }

        .btn-chat {
            background: #3B82F6;
        }

        .action-btn:hover {
            opacity: 0.8;
        }

        /* Modern Modal & Form Design */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 3000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: var(--bg-card);
            width: 100%;
            max-width: 750px;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
        }

        .modal-body {
            padding: 24px;
            max-height: 75vh;
            overflow-y: auto;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-row.two-col {
            grid-template-columns: repeat(2, 1fr);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group.full-width {
            grid-column: span 3;
        }

        .form-group label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid var(--border);
            font-family: inherit;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        .modal-footer {
            padding: 16px 24px;
            background: #F9FAFB;
            border-top: 1px solid var(--border);
            text-align: right;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .btn-cancel {
            background: white;
            border: 1px solid var(--border);
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        /* Chat Modal */
        .chat-modal {
            max-width: 500px;
            height: 80vh;
            display: flex;
            flex-direction: column;
        }

        .chat-body {
            flex: 1;
            padding: 15px;
            overflow-y: auto;
            background: #F3F4F6;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .chat-footer {
            padding: 15px;
            background: white;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 10px;
        }

        .chat-footer textarea {
            flex: 1;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid var(--border);
            resize: none;
            height: 45px;
        }
    </style>
</head>

<body>

    <?php require_once 'include/sidebar.php'; ?>

    <div class="main-content">
        <div class="toolbar">
            <div>
                <h1 style="margin:0; font-size: 24px;">Lead Management</h1>
                <p style="margin:5px 0 0; color:var(--text-muted);">Manage and track your business inquiries</p>
            </div>
            <div class="search-wrap">
                <i class="fa-solid fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search leads by name, email or phone...">
            </div>
            <button class="btn-add" id="addLeadBtn"><i class="fa-solid fa-plus"></i> Add Lead</button>
        </div>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>SNo</th>
                        <th>Lead Details</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Type & Source</th>
                        <?php if ($masterId == 2) echo "<th>Assigned To</th>"; ?>
                        <th style="text-align:center">Actions</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <?php $sno = 1;
                    foreach ($leads as $lead): ?>
                        <tr>
                            <td style="color:var(--text-muted)"><?= $sno++; ?></td>
                            <td>
                                <div style="font-weight:600; color:var(--text-main);"><?= htmlspecialchars($lead['full_name']); ?></div>
                                <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($lead['company_name'] ?: 'No Company'); ?></div>
                            </td>
                            <td>
                                <div style="font-size:13px;"><?= htmlspecialchars($lead['email']); ?></div>
                                <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($lead['phone_number']); ?></div>
                            </td>
                            <td>
                                <div style="font-size:13px;"><?= htmlspecialchars($lead['city']); ?></div>
                                <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($lead['state']); ?></div>
                            </td>
                            <td>
                                <span style="display:inline-block; background:#EEF2FF; color:#4338CA; padding:2px 8px; border-radius:12px; font-size:11px; font-weight:600; margin-bottom:4px;">
                                    <?= htmlspecialchars($lead['customer_type']); ?>
                                </span><br>
                                <span style="font-size:11px; color:var(--text-muted);"><i class="fa-solid fa-link"></i> <?= htmlspecialchars($lead['source']); ?></span>
                            </td>
                            <?php if ($masterId == 2) echo "<td><span style='font-size:13px; font-weight:500;'>" . htmlspecialchars($lead['user_name']) . "</span></td>"; ?>
                            <td style="text-align:center; white-space:nowrap;">
                                <button class="action-btn btn-chat chat-trigger" data-id="<?= $lead['id']; ?>" title="Log Activity"><i class="fa-solid fa-comments"></i></button>
                                <button class="action-btn btn-edit edit-btn" data-id="<?= $lead['id']; ?>" title="Edit"><i class="fa-solid fa-pen-to-square"></i></button>
                                <button class="action-btn btn-del delete-btn" data-id="<?= $lead['id']; ?>" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- LEAD ADD/EDIT MODAL -->
    <div class="modal" id="leadModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Lead</h3>
                <button class="close-modal" style="background:none; border:none; font-size:24px; cursor:pointer; color:var(--text-muted)">&times;</button>
            </div>
            <form id="modalForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="insert">
                    <input type="hidden" name="leadId" id="leadId">

                    <!-- Section: Basic Info -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Customer Type *</label>
                            <select id="customerType" name="customerType" class="form-control" required>
                                <option value="">Select Type</option>
                                <?php foreach ($customerTypes as $type): ?>
                                    <option value="<?= $type['id']; ?>"><?= htmlspecialchars($type['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Salutation *</label>
                            <select id="salutation" name="salutation" class="form-control" required>
                                <option value="Mr.">Mr.</option>
                                <option value="Mrs.">Mrs.</option>
                                <option value="Miss">Miss</option>
                                <option value="Dr.">Dr.</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="fullName" id="fullName" class="form-control" placeholder="John Doe" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="phoneNumber" id="phoneNumber" class="form-control" placeholder="9876543210" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" id="email" class="form-control" placeholder="example@mail.com" required>
                        </div>
                        <div class="form-group">
                            <label>Lead Source *</label>
                            <select name="leadSource" id="leadSource" class="form-control" required>
                                <option value="">Select Source</option>
                                <?php foreach ($leadSources as $src): ?>
                                    <option value="<?= $src['id']; ?>"><?= htmlspecialchars($src['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Section: Company -->
                    <div class="form-row two-col">
                        <div class="form-group">
                            <label>Company Name</label>
                            <input type="text" name="companyName" id="companyName" class="form-control" placeholder="Company Pvt Ltd">
                        </div>
                        <div class="form-group">
                            <label>GST Number</label>
                            <input type="text" name="gstNumber" id="gstNumber" class="form-control" placeholder="22AAAAA0000A1Z5">
                        </div>
                    </div>

                    <!-- Section: Address -->
                    <div class="form-row two-col">
                        <div class="form-group">
                            <label>Address Line 1 *</label>
                            <input type="text" name="address" id="address" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Address Line 2</label>
                            <input type="text" name="address2" id="address2" class="form-control">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>City *</label>
                            <input type="text" name="city" id="city" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>State *</label>
                            <input type="text" name="state" id="state" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Pincode *</label>
                            <input type="text" name="pincode" id="pincode" class="form-control" maxlength="6" required>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Additional Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="Enter any specific requirements..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel close-modal">Cancel</button>
                    <button type="submit" class="btn-save">Save Lead Details</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ACTIVITY MODAL -->
    <div class="modal" id="chatModal">
        <div class="modal-content chat-modal">
            <div class="modal-header" style="background:var(--primary); color:white;">
                <h3 style="color:white;"><i class="fa-solid fa-clock-rotate-left"></i> Activity Log</h3>
                <button id="closeChatBtn" style="background:none; border:none; font-size:24px; color:white; cursor:pointer;">&times;</button>
            </div>
            <div id="chatMessages" class="chat-body"></div>
            <div class="chat-footer">
                <textarea id="chatInput" placeholder="Add a note..."></textarea>
                <button id="sendMsgBtn" class="btn-save" style="padding:0 20px;">Send</button>
            </div>
        </div>
    </div>

    <script>
        let currentLeadId = null;

        function toggleModal(id, show) {
            document.getElementById(id).style.display = show ? 'flex' : 'none';
        }

        document.getElementById('addLeadBtn').addEventListener('click', () => {
            document.getElementById('modalForm').reset();
            document.getElementById('modalTitle').innerText = "Add New Lead";
            document.getElementById('formAction').value = "insert";
            toggleModal('leadModal', true);
        });

        document.querySelectorAll('.close-modal').forEach(b => b.onclick = () => toggleModal('leadModal', false));

        // Edit Button
        document.getElementById('tableBody').addEventListener('click', function(e) {
            const btn = e.target.closest('.edit-btn');
            if (!btn) return;
            const fd = new FormData();
            fd.append('action', 'fetch_single');
            fd.append('leadId', btn.dataset.id);
            fetch('', {
                method: 'POST',
                body: fd
            }).then(r => r.json()).then(data => {
                document.getElementById('modalTitle').innerText = "Edit Lead";
                document.getElementById('formAction').value = "update";
                document.getElementById('leadId').value = data.id;
                document.getElementById('fullName').value = data.full_name;
                document.getElementById('email').value = data.email;
                document.getElementById('phoneNumber').value = data.phone_number;
                document.getElementById('customerType').value = data.customer_type_id;
                document.getElementById('salutation').value = data.salutation;
                document.getElementById('leadSource').value = data.source_id;
                document.getElementById('address').value = data.address;
                document.getElementById('address2').value = data.address2 || '';
                document.getElementById('city').value = data.city;
                document.getElementById('state').value = data.state;
                document.getElementById('pincode').value = data.pincode;
                document.getElementById('companyName').value = data.company_name || '';
                document.getElementById('gstNumber').value = data.gst_number || '';
                document.getElementById('notes').value = data.notes || '';
                toggleModal('leadModal', true);
            });
        });

        // Form Submit
        document.getElementById('modalForm').onsubmit = (e) => {
            e.preventDefault();
            const submitBtn = e.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerText = "Processing...";

            fetch('', {
                method: 'POST',
                body: new FormData(e.target)
            }).then(r => r.text()).then(res => {
                if (res.trim().includes('success')) {
                    location.reload();
                } else {
                    alert(res);
                    submitBtn.disabled = false;
                    submitBtn.innerText = "Save Lead Details";
                }
            });
        };

        // Activity Log / Chat
        document.getElementById('tableBody').addEventListener('click', function(e) {
            const btn = e.target.closest('.chat-trigger');
            if (!btn) return;
            currentLeadId = btn.dataset.id;
            toggleModal('chatModal', true);
            loadMessages();
        });

        document.getElementById('closeChatBtn').onclick = () => toggleModal('chatModal', false);

        function loadMessages() {
            if (!currentLeadId) return;
            fetch('api/chat_handler.php?lead_id=' + currentLeadId)
                .then(res => res.text())
                .then(html => {
                    const box = document.getElementById('chatMessages');
                    box.innerHTML = html;
                    box.scrollTop = box.scrollHeight;
                });
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const msg = input.value.trim();
            if (!msg || !currentLeadId) return;

            fetch('api/chat_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `lead_id=${currentLeadId}&message=${encodeURIComponent(msg)}`
            }).then(() => {
                input.value = '';
                loadMessages();
            });
        }

        document.getElementById('sendMsgBtn').onclick = sendMessage;

        // Delete
        document.getElementById('tableBody').addEventListener('click', function(e) {
            const btn = e.target.closest('.delete-btn');
            if (btn && confirm('Are you sure you want to delete this lead?')) {
                const fd = new FormData();
                fd.append('action', 'delete');
                fd.append('leadId', btn.dataset.id);
                fetch('', {
                    method: 'POST',
                    body: fd
                }).then(() => location.reload());
            }
        });

        // Search
        document.getElementById('searchInput').onkeyup = function() {
            let val = this.value.toLowerCase();
            document.querySelectorAll('#tableBody tr').forEach(tr => {
                tr.style.display = tr.innerText.toLowerCase().includes(val) ? '' : 'none';
            });
        };
    </script>
</body>

</html>