<?php
// Include session configuration
require_once 'include/session_config.php';

// Check if user is logged in and has access to Products page
requireLoginAndAccess('product.php');

// ---------------- BACKEND PROCESSING ----------------
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Database credentials
$host = "15.207.37.132";
$user = "cloud";
$pass = "TUCKER_ser_sql";
$db   = "marketing_new";

$con = null;

try {
    $con = new mysqli($host, $user, $pass, $db);
    $con->set_charset("utf8mb4");
} catch (Exception $e) {
    error_log("Database Connection Failed: " . $e->getMessage());
    die("<h1>Database Connection Failed. Please try again later.</h1>");
}

$action = $_POST['action'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST" && $action) {
    header("Content-Type: application/json");

    try {
        if ($action === 'insert') {
            $sql = "INSERT INTO products (item_name, category, base_price, gst, max_discount, status, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $con->prepare($sql);
            $stmt->bind_param("ssddds", $_POST['item_name'], $_POST['category'], $_POST['base_price'], $_POST['gst'], $_POST['max_discount'], $_POST['status']);
            $stmt->execute();
            echo json_encode(["status" => "success"]);
            exit;
        }
        // Update, Delete, and Fetch logic (as previously defined)
        if ($action === 'fetch_single') {
            $stmt = $con->prepare("SELECT * FROM products WHERE id=?");
            $stmt->bind_param("i", $_POST['id']);
            $stmt->execute();
            $res = $stmt->get_result();
            echo json_encode($res->fetch_assoc() ?: ["status" => "error", "message" => "Not found"]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    } finally {
        if ($con) $con->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Products | Tucker Motors</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="https://station.cms.tuckermotors.com/asset/images/favicon.ico">

    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338ca;
            --bg-body: #F3F4F6;
            --bg-card: #FFFFFF;
            --text-main: #111827;
            --text-muted: #6B7280;
            --border: #E5E7EB;
            --sidebar-w: 260px;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            font-size: 14px;
            overflow-x: hidden;
        }

        /* --- LAYOUT --- */
        .main-content {
            margin-left: var(--sidebar-w);
            padding: 30px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .mobile-header {
            display: none;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            background: var(--bg-card);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 999;
        }

        /* --- TOOLBAR --- */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            gap: 15px;
            flex-wrap: wrap;
        }

        .search-wrap {
            position: relative;
            flex: 1;
            min-width: 250px;
        }

        .search-wrap input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--bg-card);
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
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        /* --- TABLE --- */
        .table-card {
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #F9FAFB;
            padding: 15px 20px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: var(--text-muted);
            letter-spacing: 0.05em;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid var(--border);
        }

        .product-name-col {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-icon {
            width: 38px;
            height: 38px;
            border-radius: 8px;
            background: #EEF2FF;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .status-available {
            background: #D1FAE5;
            color: #065F46;
        }

        .status-unavailable {
            background: #FEE2E2;
            color: #991B1B;
        }

        /* --- MODAL --- */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 20px;
            backdrop-filter: blur(4px);
        }

        .modal-content {
            background: var(--bg-card);
            width: 100%;
            max-width: 550px;
            border-radius: 12px;
            overflow: hidden;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
        }

        .modal-body {
            padding: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-muted);
        }

        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-family: inherit;
        }

        /* ============================================================
           ALL DEVICE RESPONSIVE MEDIA QUERIES
        ============================================================ */

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .mobile-header {
                display: flex;
            }

            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 1001;
            }

            .sidebar.active {
                transform: translateX(0);
            }
        }

        @media (max-width: 768px) {
            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-wrap {
                max-width: 100%;
            }

            .btn-add {
                justify-content: center;
            }

            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
            }

            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            tr {
                border: 1px solid var(--border);
                border-radius: 10px;
                margin-bottom: 15px;
                background: white;
                padding: 5px;
            }

            td {
                border: none;
                border-bottom: 1px solid #f0f0f0;
                position: relative;
                padding-left: 50%;
                text-align: right;
                font-size: 13px;
            }

            td:last-child {
                border-bottom: none;
            }

            td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: 45%;
                text-align: left;
                font-weight: 700;
                color: var(--text-muted);
                font-size: 11px;
            }

            .product-name-col {
                justify-content: flex-end;
            }
        }

        @media (max-width: 480px) {
            .main-content {
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .modal-content {
                height: 100%;
                border-radius: 0;
                max-width: 100%;
            }

            .modal {
                padding: 0;
            }

            h1 {
                font-size: 18px;
            }
        }

        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }

        .pagination-container button {
            background: #a9b8cf;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            padding: 8px 15px;
            border-radius: 8px;
        }

        #pageNumbers {
            display: flex;
            gap: 6px;
        }

        #pageNumbers button {
            padding: 5px 10px;
            border: none;
            background: #e9ecef;
            border-radius: 4px;
            cursor: pointer;
        }

        #pageNumbers button.active {
            background: #0d6efd;
            color: #fff;
        }
    </style>
</head>

<body>
    <!-- Mobile Header (LOGO REMOVED) -->
    <div class="mobile-header">
        <button id="mobileMenuBtn" style="background:none; border:none; font-size:20px; color:var(--text-main); cursor:pointer;">
            <i class="fa-solid fa-bars"></i>
        </button>
        <span style="font-weight:700; font-size: 16px;">Products</span>
        <div style="width:20px;"></div> <!-- Spacer to keep text centered -->
    </div>

    <!-- Include Sidebar -->
    <?php require_once 'include/sidebar.php'; ?>

    <div class="main-content">
        <h1>Products & Services</h1>

        <div class="toolbar">
            <div class="search-wrap">
                <i class="fa-solid fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by name, category or price...">
            </div>
            <button class="btn-add" id="addNewProductBtn" style="display: none;">
                <i class="fa-solid fa-plus"></i> Add New Product
            </button>
        </div>

        <div class="table-card">
            <table id="productTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product Details</th>
                        <th>Product Id</th>
                        <th>Base Price</th>
                        <th>GST %</th>
                        <th>Final Max Disc.</th>
                        <th>Final Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    <!-- Data populated by JavaScript -->
                </tbody>
            </table>
            <div class="pagination-container">
                <button id="prevPage">Previous</button>
                <span id="pageNumbers"></span>
                <button id="nextPage">Next</button>
            </div>
        </div>
    </div>

    <!-- Modal Form -->
    <div class="modal" id="itemModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle" style="margin:0;">Add Product</h3>
                <button id="closeModalBtn" style="background:none; border:none; font-size:24px; cursor:pointer;">&times;</button>
            </div>
            <form id="modalForm">
                <div class="modal-body">
                    <input type="hidden" id="itemId">

                    <div class="form-group">
                        <label>Product Name</label>
                        <input type="text" id="itemName" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <select id="itemCategory" class="form-control">
                            <option value="3.3kW AC Charger">3.3kW AC Charger</option>
                            <option value="Type 2 AC Charger">Type 2 AC Charger</option>
                            <option value="DC Fast Charger">DC Fast Charger</option>
                            <option value="Service">Service</option>
                        </select>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Base Price (₹)</label>
                            <input type="number" id="itemPrice" class="form-control" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>GST (%)</label>
                            <input type="number" id="itemGst" class="form-control" step="0.01" value="18">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Max Disc (%)</label>
                            <input type="number" id="itemMaxDiscount" class="form-control" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select id="itemStatus" class="form-control">
                                <option value="1">Available</option>
                                <option value="0">Unavailable</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="padding:20px; border-top:1px solid var(--border); text-align:right;">
                    <button type="button" id="cancelModalBtn" style="padding:10px 20px; border:none; background:none; cursor:pointer;">Cancel</button>
                    <button type="submit" class="btn-add" style="display:inline-flex;">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tableBody = document.getElementById('tableBody');
            const itemModal = document.getElementById('itemModal');
            const modalForm = document.getElementById('modalForm');


            const fetchItems = () => {
                fetch('api/get_products.php')
                    .then(res => res.json())
                    .then(response => {
                        if (!response.success) return;
                        tableBody.innerHTML = '';
                        response.data.forEach((item, index) => {
                            const statusText = item.status === "1" ? "In Stock" : "Out Of Stock";
                            const statusClass = item.status === "1" ? "status-available" : "status-unavailable";
                            const mrp = Number(item.mrp_price);
                            const price = Number(item.price);
                            const discount = mrp > 0 ? (((mrp - price) / mrp) * 100).toFixed(1) : 0;

                            tableBody.innerHTML += `
                        <tr>
                            <td data-label="#">${index + 1}</td>
                            <td data-label="Product" class="product-name-col">
                                <div class="product-icon"><img src="${item.imageurl}" alt="Product" width="50" height="50"/></div>
                                <div style="text-align:left">
                                    <div style="font-weight:600">${item.productname}</div>
                                    <div style="font-size:10px;color:gray">${item.product_usage}</div>
                                </div>
                            </td>
                            <td data-label="Base Price">₹${mrp.toLocaleString()}</td>
                            <td data-label="GST %">${item.gst}%</td>
                            <td data-label="Max Disc.">${discount}%</td>
                            <td data-label="Final Price"><strong>₹${price.toLocaleString()}</strong></td>
                            <td data-label="Status">
                                <span class="status-badge ${statusClass}">${statusText}</span>
                            </td>
                        </tr>`;
                        });
                    });
            };

            // Modal Controls
            document.getElementById('addNewProductBtn').onclick = () => {
                modalForm.reset();
                document.getElementById('itemId').value = '';
                document.getElementById('modalTitle').innerText = 'Add New Product';
                itemModal.style.display = 'flex';
            };

            document.getElementById('closeModalBtn').onclick = () => itemModal.style.display = 'none';
            document.getElementById('cancelModalBtn').onclick = () => itemModal.style.display = 'none';

            // Search Filter
            document.getElementById('searchInput').addEventListener('keyup', function() {
                const val = this.value.toLowerCase();
                document.querySelectorAll('#tableBody tr').forEach(row => {
                    row.style.display = row.innerText.toLowerCase().includes(val) ? '' : 'none';
                });
            });

            fetchItems();
        });
        let productsData = [];
        let currentPage = 1;
        const rowsPerPage = 8;

        document.addEventListener('DOMContentLoaded', () => {

            const tableBody = document.getElementById('tableBody');
            const pageNumbers = document.getElementById('pageNumbers');

            function renderTable() {

                tableBody.innerHTML = "";

                const start = (currentPage - 1) * rowsPerPage;
                const end = start + rowsPerPage;

                const pageData = productsData.slice(start, end);

                pageData.forEach((item, index) => {

                    const statusText = item.status === "1" ? "In Stock" : "Out Of Stock";
                    const statusClass = item.status === "1" ? "status-available" : "status-unavailable";

                    const mrp = Number(item.mrp_price);
                    const price = Number(item.price);
                    const discount = mrp > 0 ? (((mrp - price) / mrp) * 100).toFixed(1) : 0;

                    tableBody.innerHTML += `
<tr>
<td>${start + index + 1}</td>

<td class="product-name-col">
<div class="product-icon">
<img src="${item.imageurl}" width="50" height="50"/>
</div>

<div style="text-align:left">
<div style="font-weight:600">${item.productname}</div>
<div style="font-size:10px;color:gray">${item.product_usage}</div>
</div>
</td>
<td>${item.productid}</td>
<td>₹${mrp.toLocaleString()}</td>
<td>${item.gst}%</td>
<td>${discount}%</td>
<td><strong>₹${price.toLocaleString()}</strong></td>

<td>
<span class="status-badge ${statusClass}">
${statusText}
</span>
</td>

</tr>`;
                });

                createPagination();

            }

            function createPagination() {

                const totalPages = Math.ceil(productsData.length / rowsPerPage);

                pageNumbers.innerHTML = "";

                for (let i = 1; i <= totalPages; i++) {

                    const btn = document.createElement("button");
                    btn.innerText = i;

                    if (i === currentPage) {
                        btn.classList.add("active");
                    }

                    btn.onclick = () => {
                        currentPage = i;
                        renderTable();
                    };

                    pageNumbers.appendChild(btn);
                }

            }

            document.getElementById("prevPage").onclick = () => {
                if (currentPage > 1) {
                    currentPage--;
                    renderTable();
                }
            }

            document.getElementById("nextPage").onclick = () => {
                const totalPages = Math.ceil(productsData.length / rowsPerPage);

                if (currentPage < totalPages) {
                    currentPage++;
                    renderTable();
                }
            }

            // Fetch API
            fetch('api/get_products.php')
                .then(res => res.json())
                .then(response => {

                    if (!response.success) return;

                    productsData = response.data;

                    renderTable();

                });


            // Search
            document.getElementById('searchInput').addEventListener('keyup', function() {

                const val = this.value.toLowerCase();

                const filtered = productsData.filter(item =>
                    item.productname.toLowerCase().includes(val) ||
                    item.product_usage.toLowerCase().includes(val)
                );

                currentPage = 1;
                productsData = filtered;

                renderTable();

            });

        });
    </script>
</body>

</html>