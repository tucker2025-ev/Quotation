<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern Sidebar Navigation - Lead Management</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- ================================================================= // -->
    <!-- SECTION 2: CSS STYLES -->
    <!-- ================================================================= // -->
    <style>
        :root {
            --sidebar-bg: #FFFFFF;
            --sidebar-width-expanded: 250px;
            --sidebar-width-collapsed: 88px;
            --main-bg: #F7F7FA;
            --text-primary: #1A202C;
            --text-secondary: #718096;
            --text-active: #2D3748;
            --border-color: #E2E8F0;
            --active-indicator: linear-gradient(135deg, #FF7E86, #8E54E9);
            --notification-bg: #4A55E1;
            --theme-toggle-bg: #F7FAFC;
            --success-color: #34D399;
            --danger-color: #F87171;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--main-bg);
            margin: 0;
            padding: 0;
            color: var(--text-primary);
            transition: background-color 0.3s ease;
        }
        .page-container {
            display: flex;
            min-height: 100vh;
        }

        /* === SIDEBAR STYLES === */
        .sidebar {
            width: var(--sidebar-width-expanded);
            background-color: var(--sidebar-bg);
            padding: 24px 16px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            border-right: 1px solid var(--border-color);
            transition: width 0.3s ease-in-out;
            position: fixed;
            height: 100%;
            left: 0;
            top: 0;
            z-index: 100;
        }

        .main-content {
            flex-grow: 1;
            padding: 30px;
            margin-left: var(--sidebar-width-expanded);
            transition: margin-left 0.3s ease-in-out;
        }

        .sidebar.collapsed {
            width: var(--sidebar-width-collapsed);
        }

        .sidebar.collapsed+.main-content {
            margin-left: var(--sidebar-width-collapsed);
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 0 8px;
        }

        .logo-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text-primary);
        }

        .logo-img {
            height: 30px;
            flex-shrink: 0;
        }

        .logo-name {
            font-size: 20px;
            font-weight: 700;
            white-space: nowrap;
            opacity: 1;
            transition: opacity 0.2s ease, width 0.2s ease;
        }

        .sidebar-toggle-btn {
            background-color: #fff;
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: transform 0.3s ease;
        }

        .sidebar-toggle-btn svg {
            width: 16px;
            height: 16px;
            color: var(--text-secondary);
        }

        .sidebar-body {
            flex-grow: 1;
            overflow-y: auto;
            overflow-x: hidden;
            padding: 10px 0;
        }

        .sidebar-body::-webkit-scrollbar { display: none; }
        .sidebar-body { -ms-overflow-style: none; scrollbar-width: none; }
        .nav-list { list-style: none; padding: 0; margin: 0; }
        .nav-item { margin-bottom: 4px; position: relative; }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 12px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 15px;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .nav-link:hover {
            background-color: var(--main-bg);
            color: var(--text-primary);
        }

        .nav-link .nav-icon {
            width: 24px;
            height: 24px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .nav-link .nav-icon svg { width: 20px; height: 20px; }
        .nav-text { opacity: 1; transition: opacity 0.2s ease; }

        .nav-link.active {
            color: var(--text-active);
            font-weight: 600;
            background-color: #F0F2FF;
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 24px;
            background: var(--active-indicator);
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
        }

        .sidebar-footer { padding-top: 10px; margin-top: auto; }
        .separator { height: 1px; background-color: var(--border-color); margin: 16px 8px; }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px;
            border-radius: 8px;
            background-color: transparent;
            transition: background-color 0.2s ease;
        }

        .user-profile:hover { background-color: var(--main-bg); }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
        .user-details { display: flex; align-items: center; justify-content: space-between; width: 100%; overflow: hidden; }
        .user-name { font-weight: 600; font-size: 14px; white-space: nowrap; }
        .logout-icon { color: var(--text-secondary); flex-shrink: 0; }

        .theme-toggle { display: flex; align-items: center; background-color: var(--theme-toggle-bg); border-radius: 8px; padding: 4px; margin-top: 20px; }
        .theme-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 8px; border-radius: 6px; border: none; background-color: transparent; color: var(--text-secondary); font-weight: 600; cursor: pointer; transition: all 0.2s ease; }
        .theme-btn.active { background: var(--active-indicator); color: white; box-shadow: 0 4px 10px -2px rgba(142, 84, 233, 0.4); }
        .theme-btn span { white-space: nowrap; }

        /* === COLLAPSED STATE STYLES === */
        .sidebar.collapsed .logo-name,
        .sidebar.collapsed .nav-text,
        .sidebar.collapsed .user-details,
        .sidebar.collapsed .theme-btn span {
            opacity: 0;
            visibility: hidden;
            width: 0;
            transition: opacity 0.1s, visibility 0.1s, width 0.1s;
        }

        .sidebar.collapsed .sidebar-toggle-btn { transform: rotate(180deg); }
        .sidebar.collapsed .nav-list { display: flex; flex-direction: column; align-items: center; }
        .sidebar.collapsed .nav-link { justify-content: center; padding: 12px; width: 48px; }
        .sidebar.collapsed .user-profile { justify-content: center; }
        .sidebar.collapsed .theme-btn { gap: 0; }

        .sidebar.collapsed .nav-link .nav-text {
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 12px;
            background: var(--active-indicator);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            z-index: 10;
        }

        .sidebar.collapsed .nav-item:hover .nav-link .nav-text {
            opacity: 1;
            visibility: visible;
            transition: opacity 0.2s 0.1s, visibility 0.2s 0.1s;
        }

        /* === LEAD TABLE & TOOLBAR STYLES === */
        .main-content h1 { font-size: 28px; font-weight: 700; margin-bottom: 24px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px; }
        .search-wrapper { position: relative; }
        .search-wrapper svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 20px; height: 20px; color: var(--text-secondary); }
        .search-wrapper input { padding: 10px 10px 10px 40px; border: 1px solid var(--border-color); border-radius: 8px; background-color: var(--sidebar-bg); color: var(--text-primary); font-family: 'Inter', sans-serif; font-size: 14px; width: 280px; }
        .search-wrapper input::placeholder { color: var(--text-secondary); }
        .add-item-btn { background: var(--active-indicator); color: white; border: none; border-radius: 8px; padding: 10px 16px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 10px -2px rgba(142, 84, 233, 0.4); transition: all 0.2s ease; }
        .add-item-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 12px -2px rgba(142, 84, 233, 0.5); }
        .lead-table-container { background-color: var(--sidebar-bg); border-radius: 16px; padding: 24px; border: 1px solid var(--border-color); overflow-x: auto; }
        .lead-table { width: 100%; border-collapse: collapse; min-width: 800px; }
        .lead-table th, .lead-table td { padding: 16px; text-align: left; vertical-align: middle; }
        .lead-table thead { border-bottom: 1px solid var(--border-color); }
        .lead-table th { font-size: 13px; font-weight: 600; color: var(--text-secondary); text-transform: uppercase; }
        .lead-table tbody tr { border-bottom: 1px solid var(--border-color); }
        .lead-table tbody tr:last-child { border-bottom: none; }
        .lead-info { display: flex; align-items: center; gap: 12px; }
        .lead-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; flex-shrink: 0; background-color: var(--main-bg); }
        .lead-details .lead-name { font-weight: 600; color: var(--text-primary); display: block; }
        .lead-details .lead-company { font-size: 13px; color: var(--text-secondary); }
        .contact-details .contact-email { font-weight: 500; color: var(--text-primary); display: block; }
        .contact-details .contact-phone { font-size: 13px; color: var(--text-secondary); }
        .status { padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; display: inline-block; text-transform: capitalize; }
        /* These status classes will need to be mapped to the actual lead_status names/ids */
        .status.new { background-color: #EBF4FF; color: #3B82F6; }
        .status.discussion { background-color: #FEF3C7; color: #D97706; }
        .status.quoted { background-color: #F3E8FF; color: #9333EA; }
        .status.won { background-color: #E6F7F0; color: #0E9F6E; }
        .status.lost { background-color: #FDE8E8; color: var(--danger-color); }
        .action-buttons { display: flex; gap: 8px; }
        .action-btn { width: 32px; height: 32px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--sidebar-bg); cursor: pointer; display: flex; align-items: center; justify-content: center; color: var(--text-secondary); }
        .action-btn:hover { background-color: var(--main-bg); color: var(--text-primary); }
        .pagination { display: flex; justify-content: space-between; align-items: center; padding-top: 24px; font-size: 14px; color: var(--text-secondary); flex-wrap: wrap; gap: 16px; }
        .pagination-buttons { display: flex; gap: 4px; }
        .pagination-btn { height: 36px; min-width: 36px; padding: 0 8px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--sidebar-bg); cursor: pointer; color: var(--text-secondary); font-weight: 500; }
        .pagination-btn:disabled { cursor: not-allowed; opacity: 0.5; }
        .pagination-btn.active, .pagination-btn:not(:disabled):hover { border-color: #A37CF0; background: linear-gradient(0deg, rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), var(--active-indicator); color: #6B42B4; }

        /* === MODAL STYLES === */
        /* Modal Background */
    /* Modal Background */
   /* Modal Background */
.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(3px);
    justify-content: center;
    align-items: center;
    padding: 20px;
}

/* Modal Content Box */
.modal-content {
    background: #fff;
    border-radius: 12px;
    max-width: 550px;
    width: 100%;
    padding: 25px 30px;
    box-shadow: 0px 4px 25px rgba(0, 0, 0, 0.1);
    animation: fadeIn 0.3s ease-in-out;
}

/* Modal Header */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 15px;
    margin-bottom: 20px;
}

.modal-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
}

.modal-header button {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6b7280;
    cursor: pointer;
}

/* Form Fields */
.modal-content form label {
    display: block;
    font-weight: 500;
    margin-bottom: 6px;
    color: #374151;
}

.modal-content input,
.modal-content select,
.modal-content textarea {
    width: 100%;
    padding: 10px;
    margin-bottom: 18px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    background-color: #f9fafb;
    transition: border-color 0.2s ease;
}

.modal-content input:focus,
.modal-content select:focus,
.modal-content textarea:focus {
    border-color: #6366f1;
    outline: none;
}

/* Modal Footer */
.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 15px;
}

.modal-footer button {
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-weight: 500;
    cursor: pointer;
    font-size: 14px;
}

.modal-footer #cancelBtn {
    background: #f3f4f6;
    color: #374151;
}

.modal-footer button[type="submit"] {
    background: linear-gradient(90deg, #ec4899, #8b5cf6);
    color: white;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}


    </style>
</head>
<body>
    <!-- ================================================================= // -->
    <!-- SECTION 3: HTML STRUCTURE -->
    <!-- ================================================================= // -->
    <div class="page-container">
        <nav class="sidebar">
            <div class="sidebar-header">
                <a href="#" class="logo-wrapper">
                    <!-- <img src="your-logo.png" alt="Logo" class="logo-img"> -->
                    <span class="logo-name">LeadSys</span>
                </a>
                <button class="sidebar-toggle-btn" id="sidebar-toggle">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                </button>
            </div>
            <div class="sidebar-body">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="#" class="nav-link">
                            <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg></span>
                            <span class="nav-text">Overview</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="file.php" class="nav-link active">
                            <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg></span>
                            <span class="nav-text">Lead</span>
                        </a>
                    </li>
                    <?php 
                    // Check if current user should see Products page
                    // Only "amsath" can see Products page, hide for "vivek" and "hari"
                    $currentUsername = getCurrentUsername();
                    if ($currentUsername === 'amsath'): 
                    ?>
                    <li class="nav-item">
                        <a href="product.php" class="nav-link">
                            <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg></span>
                            <span class="nav-text">Products</span>
                        </a>
                    </li>
                    <?php endif; ?>
                      <li class="nav-item">
                        <a href="qut.php" class="nav-link">
                            <span class="nav-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg></span>
                            <span class="nav-text">Quotations</span>
                        </a>
                    </li>
                </ul>
            </div>
            <div class="sidebar-footer">
                <div class="separator"></div>
                <div class="user-profile">
                    <img src="https://i.pravatar.cc/40" alt="User" class="user-avatar">
                    <div class="user-details">
                        <span class="user-name">Amsath</span>
                        <a href="https://station.cms.tuckermotors.com/admin_cms/index.php" class="logout-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 4H3v16h7M17 16l4-4-4-4M21 12H7" /></svg>
                        </a>
                    </div>
                </div>
            </div>
        </nav>