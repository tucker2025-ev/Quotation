<?php
// Include session configuration
require_once 'include/session_config.php';
require_once 'api/System_log.php';
// Check if user is logged in and has access, redirect if not
requireLoginAndAccess('lead.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>General Inquiries</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="https://station.cms.tuckermotors.com/asset/images/favicon.ico">

    <style>
        .btn-chat {
            background: #0d6efd;
            margin-right: 5px;
            color: #fff;
        }


        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }

        .pagination-container button {
            padding: 6px 14px;
            border: none;
            background: #2c7be5;
            color: white;
            border-radius: 6px;
            cursor: pointer;
        }

        /* =========================
   LEAD ACTIVITY LOG UI
========================= */
        /* ===== CHAT MODAL ===== */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1300;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: #ffffff;
            width: 600px;
            max-width: 95%;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
            box-shadow: var(--shadow-lg);
        }

        /* Chat footer */
        .chat-footer {
            display: flex;
            gap: 10px;
            padding: 15px;
            border-top: 1px solid #e5e7eb;
        }

        .chat-footer textarea {
            flex: 1;
            resize: none;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #d1d5db;
        }

        .chat-footer button {
            background: #0d6efd;
            color: #fff;
            border: none;
            padding: 0 18px;
            border-radius: 6px;
            cursor: pointer;
        }

        .chat-body {
            padding: 20px;
            background: #f9fafb;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
            height: 500px;
            ;
        }

        .log-item {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px 16px;
        }

        .log-message {
            font-size: 14px;
            color: #111827;
            line-height: 1.5;
            margin-bottom: 6px;
        }

        .log-time {
            font-size: 12px;
            color: #6b7280;
        }

        .log-empty {
            text-align: center;
            color: #6b7280;
            font-size: 13px;
            padding: 30px 0;
        }


        .modal-header {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .mobile-header {
            display: none;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 15px;
        }

        .menu-btn {
            background: var(--bg-card);
            border: 1px solid var(--border);
            color: var(--text-main);
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
        }

        /* ===== CSS VARIABLES ===== */
        :root {
            --sidebar-bg: #FFFFFF;
            --sidebar-width-expanded: 250px;
            --text-primary: #1A202C;
            --text-secondary: #718096;
            --text-active: #2D3748;
            --border-color: #E2E8F0;
            --main-bg: #F7F7FA;
            --card-bg: #FFFFFF;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.1);
            --primary-color: #667eea;
            --success-color: #48bb78;
            --danger-color: #f56565;
            --warning-color: #ed8936;
            --info-color: #3b82f6;
        }

        /* ===== BASE STYLES ===== */
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--main-bg);
            color: var(--text-primary);
            font-size: 14px;
            overflow-x: hidden;
        }

        /* ===== SIDEBAR & OVERLAY ===== */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: var(--sidebar-width-expanded);
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            transition: transform 0.3s ease;
            z-index: 1100;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(2px);
            z-index: 1050;
        }

        /* ===== MAIN CONTENT ===== */
        .main-content {
            margin-left: var(--sidebar-width-expanded);
            padding: 32px;
            width: calc(100% - var(--sidebar-width-expanded));
            min-height: 100vh;
            transition: all 0.3s ease;
        }

        .main-content h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0 0 24px 0;
        }

        /* ===== TOOLBAR ===== */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            gap: 16px;
        }

        .search-wrapper {
            flex: 1;
            max-width: 400px;
        }

        .search-wrapper input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            background-color: var(--card-bg);
            outline: none;
        }

        /* ===== TABLE STYLES ===== */
        .lead-table-container {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .lead-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .lead-table th {
            background-color: var(--main-bg);
            padding: 16px;
            text-align: left;
            font-weight: 600;
            color: var(--text-secondary);
            border-bottom: 1px solid var(--border-color);
        }

        .lead-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
            vertical-align: middle;
            /* WRAP TEXT LOGIC */
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        /* Helper Classes */
        .badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-gray {
            background: #EDF2F7;
            color: #4A5568;
        }

        .badge-orange {
            background: #FFEDD5;
            color: #C05621;
        }

        .btn {
            padding: 6px 10px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-view {
            background-color: var(--info-color);
            color: white;
            margin-right: 5px;
        }

        .delete-btn {
            background-color: var(--danger-color);
            color: white;
        }

        /* ===== MODAL STYLES ===== */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            z-index: 1200;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .modal-box {
            background: white;
            width: 600px;
            max-width: 100%;
            border-radius: 16px;
            display: flex;
            flex-direction: column;
            max-height: 90vh;
            box-shadow: var(--shadow-lg);
        }

        .modal-header {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-body {
            padding: 24px;
            overflow-y: auto;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .full-width {
            grid-column: span 2;
        }

        .detail-item p {
            background: var(--main-bg);
            padding: 10px;
            border-radius: 6px;
            margin: 4px 0 0 0;
            word-break: break-word;
        }

        /* ===== RESPONSIVE MEDIA QUERIES ===== */

        /* 1. TABLET VIEW (Under 1024px) */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .sidebar-overlay.open {
                display: block;
            }

            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 0;
            }

            .mobile-header {
                display: flex;
            }

            .toolbar,
            .lead-table-container {
                margin: 0 20px;
            }
        }

        /* 2. MOBILE CARD VIEW (Under 768px) */
        @media (max-width: 768px) {
            .toolbar {
                flex-direction: column;
                align-items: stretch;
                margin-bottom: 24px;
            }

            /* Transform Table to Cards */
            .lead-table thead {
                display: none;
            }

            .lead-table,
            .lead-table tbody,
            .lead-table tr,
            .lead-table td {
                display: block;
                width: 100%;
            }

            .lead-table-container {
                background: transparent;
                box-shadow: none;
            }

            .lead-table tbody tr {
                background-color: var(--card-bg);
                margin-bottom: 16px;
                border-radius: 12px;
                box-shadow: var(--shadow);
                border: 1px solid var(--border-color);
                padding: 10px;
            }

            .lead-table td {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                text-align: right;
                padding: 10px 0;
                border-bottom: 1px solid #f1f5f9;
            }

            .lead-table td:last-child {
                border-bottom: none;
                justify-content: center;
                padding-top: 15px;
            }

            /* Add Labels via Data Attribute */
            .lead-table td::before {
                content: attr(data-label);
                font-weight: 700;
                color: var(--text-secondary);
                font-size: 11px;
                text-transform: uppercase;
                text-align: left;
                padding-right: 15px;
                min-width: 100px;
            }
        }

        /* 3. SMALL PHONE VIEW (Under 420px) */
        @media (max-width: 420px) {
            .lead-table td {
                flex-direction: column;
                align-items: flex-start;
                text-align: left;
                gap: 5px;
            }

            .lead-table td::before {
                margin-bottom: 2px;
                color: var(--primary-color);
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: span 1;
            }

            .modal-footer-centered {
                flex-direction: column;
            }

            .modal-btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            body.sidebar-collapsed .main-content {
                margin-left: 0;
            }

            .mobile-header {
                display: flex;
            }

            /* On Tablets, allow scrolling if not switching to cards yet */
            .lead-table-container {
                overflow-x: auto;
            }
        }

        .lead-table-container {
            width: 100%;
            overflow-x: auto;
        }
    </style>
</head>

<body>

    <?php require_once 'include/sidebar.php'; ?>

    <main class="main-content">

        <!-- MOBILE HEADER (Visible only on mobile/tablet) -->
        <div class="mobile-header">
            <button id="mobileMenuBtn" class="menu-btn">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h2 style="margin:0; font-size: 16px; font-weight:600;">General Inquiries</h2>
            <div style="width: 32px;"></div>
        </div>

        <!-- Toolbar -->
        <div class="toolbar">
            <h1 style="margin:0; font-size:28px;" class="hide-on-mobile">General Inquiries</h1>
            <div class="search-wrapper">
                <input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search by Name, Email, or Phone...">
            </div>
        </div>

        <!-- Lead Table -->
        <div class="lead-table-container">
            <table class="lead-table">
                <thead id="table-head">
                </thead>
                <tbody id="table-body">
                </tbody>
            </table>
            <div class="pagination-container" style="display:flex; justify-content:center; gap:10px; margin-top:20px;">
                <button onclick="prevPage()" class="btn">Prev</button>
                <span id="pageInfo" style="padding:6px 10px;"></span>
                <button onclick="nextPage()" class="btn">Next</button>
            </div>
        </div>
    </main>

    <!-- Inquiries Chat Modal -->
    <div class="modal" id="chatModal">
        <div class="modal-content chat-modal">
            <div class="modal-header" style="background:#0d6efd;">
                <h3 style="color:#fdfcfb; margin:0;">Inquiries Activity</h3>
                <div style="cursor:pointer;" onclick="closeModal('chatModal')">
                    <i class="fa-solid fa-xmark" style="color:#fff;"></i>
                </div>
            </div>


            <div id="chatMessages" class="chat-body"></div>

            <div class="chat-footer">
                <textarea id="chatInput" placeholder="Type a message..." rows="2"></textarea>
                <button id="sendMsg">Send</button>
            </div>
        </div>
    </div>


    <!-- VIEW DETAILS MODAL -->
    <div id="viewModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3 style="margin:0;">Details</h3>
                <div style="cursor:pointer;" onclick="closeModal('viewModal')">
                    <i class="fa-solid fa-xmark"></i>
                </div>
            </div>
            <div class="modal-body" id="viewDetailsContent"></div>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div id="deleteModal" class="modal-overlay">
        <div class="modal-box" style="width:400px; text-align:center;">
            <div class="modal-body">
                <div style="font-size:30px; color:var(--danger-color); margin-bottom:15px;"><i class="fa-regular fa-trash-can"></i></div>
                <h3 style="margin:0 0 10px 0;">Delete Record?</h3>
                <p style="color:var(--text-secondary); margin-bottom:20px;">This action cannot be undone.</p>
                <textarea id="deleteReason" style="width:100%; padding:10px; border:1px solid var(--border-color); border-radius:8px; margin-bottom:15px;" rows="3" placeholder="Reason..."></textarea>
                <div style="display:flex; gap:10px;">
                    <button class="btn" style="flex:1; background:white; border:1px solid var(--border-color);" onclick="closeModal('deleteModal')">Cancel</button>
                    <button class="btn btn-confirm-delete" style="flex:1; background:var(--danger-color); color:white;" onclick="confirmDelete()">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let deleteTargetId = null;
        const contactData = <?php echo $contactData; ?>;

        let currentPage = 1;
        let rowsPerPage = 10;
        let filteredData = [];

        const $ = (id) => document.getElementById(id);

        // function toggleSidebar() {
        //     const sidebar = document.querySelector('.sidebar');
        //     const overlay = document.getElementById('sidebarOverlay');
        //     sidebar.classList.toggle('open');
        //     if(sidebar.classList.contains('open')) {
        //         overlay.style.display = 'block';
        //     } else {
        //         overlay.style.display = 'none';
        //     }
        // }

        // =====================================================
        // LOAD MODEL
        // =====================================================
        function loadModel() {
            const head = $('table-head');
            const body = $('table-body');

            // Hide specific desktop title on mobile via JS if needed
            if (window.innerWidth <= 1024) {
                document.querySelectorAll('.hide-on-mobile').forEach(el => el.style.display = 'none');
            }

            head.innerHTML = `<tr>
                <th style="width:60px;">ID</th>
                <th style="width:200px;">Name</th>
                <th style="width:200px;">Contact Info</th>
                <th style="width:180px;">Product</th>
                <th style="width:150px;">Date</th>
                <th style="width:80px; text-align:center;">Action</th>
            </tr>`;

            const rows = contactData?.data.records || [];
            if (rows.length > 0) {
                // renderRows(rows);
                filteredData = contactData?.data.records || [];
                currentPage = 1;
                renderTable();
            } else {
                body.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:30px;">No records found.</td></tr>`;
            }
        }

        function renderTable() {

            const start = (currentPage - 1) * rowsPerPage;
            const end = start + rowsPerPage;

            const pageData = filteredData.slice(start, end);

            renderRows(pageData);

            const totalPages = Math.ceil(filteredData.length / rowsPerPage);

            document.getElementById("pageInfo").innerText =
                `Page ${currentPage} of ${totalPages}`;
        }

        function nextPage() {

            const totalPages = Math.ceil(filteredData.length / rowsPerPage);

            if (currentPage < totalPages) {
                currentPage++;
                renderTable();
            }
        }

        function prevPage() {

            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        }
        // =====================================================
        // RENDER ROWS (With data-label for Mobile Cards)
        // =====================================================
        function renderRows(data) {
            const body = $('table-body');

            body.innerHTML = data.map((row, index) => {
                const dateStr = (row.submission_date || "").substring(0, 10);

                // Added 'data-label' attributes for mobile card view
                return `
                <tr>
                    <td data-label="ID">
<span class="badge badge-gray">
#${((currentPage - 1) * rowsPerPage) + index + 1}
</span>
</td>
                    <td data-label="Name"><strong>${row.full_name || 'Unknown'}</strong></td>
                    <td data-label="Contact">
                        <div style="font-size:13px; color:var(--text-active);">${row.email}</div>
                        <div style="font-size:12px; color:var(--text-secondary);">${row.phone}</div>
                    </td>
                    <td data-label="Product"><span class="badge badge-orange">${row.selected_product || 'N/A'}</span></td>
                    <td data-label="Date"><span style="color:var(--text-secondary); font-size:12px;">${dateStr}</span></td>
                    <td data-label="Action">
                        <div style="display:flex; justify-content:center;">
                         <button class="btn btn-chat" data-id="${row.id}" title="Chat"> <i class="fa-solid fa-comments"></i></button>
                            <button class="btn btn-view" onclick="openView('${row.id}')"><i class="fa-solid fa-eye"></i></button>
                            <button class="btn delete-btn" onclick="promptDelete('${row.id}')"><i class="fas fa-trash-alt"></i></button>
                        </div>
                    </td>
                </tr>`;
            }).join('');
        }

        // =====================================================
        // VIEW LOGIC
        // =====================================================
        function openView(id) {
            const item = contactData.data.records.find(x => x.id == id);
            if (!item) return;

            const initials = (item.full_name || 'U').substring(0, 2).toUpperCase();

            // Generate details grid
            const details = `
                <div class="detail-item"><label style="font-size:11px; color:var(--text-secondary); font-weight:700; text-transform:uppercase;">Email</label><p>${item.email}</p></div>
                <div class="detail-item"><label style="font-size:11px; color:var(--text-secondary); font-weight:700; text-transform:uppercase;">Phone</label><p>${item.phone}</p></div>
                <div class="detail-item"><label style="font-size:11px; color:var(--text-secondary); font-weight:700; text-transform:uppercase;">Product</label><p>${item.selected_product}</p></div>
                <div class="detail-item"><label style="font-size:11px; color:var(--text-secondary); font-weight:700; text-transform:uppercase;">Date</label><p>${item.submission_date}</p></div>
                <div class="detail-item full-width"><label style="font-size:11px; color:var(--text-secondary); font-weight:700; text-transform:uppercase;">Message</label><p>${item.message || 'No message provided.'}</p></div>
            `;

            $('viewDetailsContent').innerHTML = `
                <div style="display:flex; align-items:center; gap:15px; margin-bottom:20px; border-bottom:1px solid #eee; padding-bottom:15px;">
                    <div style="width:50px; height:50px; background:var(--primary-color); color:white; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700;">${initials}</div>
                    <div>
                        <h4 style="margin:0;">${item.full_name}</h4>
                        <span style="font-size:12px; color:gray;">ID: #${item.id}</span>
                    </div>
                </div>
                <div class="detail-grid">${details}</div>`;

            $('viewModal').style.display = 'flex';
        }

        // =====================================================
        // UTILS
        // =====================================================
        function closeModal(id) {
            $(id).style.display = 'none';
        }

        function promptDelete(id) {
            deleteTargetId = id;
            $('deleteModal').style.display = 'flex';
        }

        function confirmDelete() {
            // Reusing your existing fetch logic
            const btn = document.querySelector('.btn-confirm-delete');
            const reasonInput = document.getElementById('deleteReason');
            const reason = reasonInput.value.trim();

            // Validation
            if (reason === "") {
                alert("Please enter a reason for deletion.");
                reasonInput.focus();
                return;
            }

            if (reason.length < 5) {
                alert("Reason must be at least 5 characters.");
                reasonInput.focus();
                return;
            }

            btn.innerText = "Deleting...";
            btn.disabled = true;

            const fd = new FormData();
            fd.append("action", "delete");
            fd.append("id", deleteTargetId);
            fd.append("reason", reason);

            fetch("https://tuckermotors.com/api-contact.php?api_key=TUCKER_SECURE_123", {
                    method: "POST",
                    body: fd
                })
                .then(res => res.json())
                .then(res => {
                    btn.innerText = "Delete";
                    btn.disabled = false;

                    // Remove from local data and re-render
                    contactData.data.records = contactData.data.records.filter(r => r.id != deleteTargetId);
                    renderRows(contactData.data.records);
                    closeModal("deleteModal");
                })
                .catch(() => {
                    btn.innerText = "Delete";
                    btn.disabled = false;
                    alert("Network Error");
                });
        }

        function filterTable() {

            const filter = $('searchInput').value.toLowerCase();

            filteredData = contactData.data.records.filter(i =>
                (i.full_name && i.full_name.toLowerCase().includes(filter)) ||
                (i.email && i.email.toLowerCase().includes(filter)) ||
                (i.phone && i.phone.includes(filter))
            );

            currentPage = 1;
            renderTable();
        }

        window.onload = loadModel;
        window.onclick = e => {
            if (e.target.classList.contains('modal-overlay')) closeModal(e.target.id);
        };
    </script>


    <script>
        let leadId = null;

        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-chat');
            if (!btn) return;

            leadId = btn.dataset.id;
            console.log('Chat clicked for lead:', leadId);

            document.getElementById('chatModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';

            loadMessages();
        });

        function loadMessages() {
            fetch('api/inquiries_chat_handler.php?lead_id=' + leadId)
                .then(res => res.text())
                .then(html => {
                    const chatBox = document.getElementById('chatMessages');
                    chatBox.innerHTML = html;
                    chatBox.scrollTop = chatBox.scrollHeight;
                });
        }

        document.getElementById('sendMsg').addEventListener('click', sendMessage);

        document.getElementById('chatInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const msg = input.value.trim();
            if (!msg) return;

            fetch('api/inquiries_chat_handler.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `lead_id=${leadId}&message=${encodeURIComponent(msg)}`
            }).then(() => {
                input.value = '';
                loadMessages();
            });
        }
    </script>
</body>

</html>