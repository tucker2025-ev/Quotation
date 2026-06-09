<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);
$currentUsername = $_SESSION["user_name"] ?? 'Guest';

// Helper for active menu item
function isActive($page, $curr)
{
    return $page === $curr ? 'active' : '';
}
?>

<!-- Mobile Overlay -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<nav class="sidebar" id="mainSidebar">
    <!-- Header -->
    <div class="sidebar-header">
        <a href="Dashboard.php" class="logo-wrapper">
            <div class="logo-icon-wrapper">
                <i class="fa-solid fa-bolt logo-icon"></i>
            </div>
            <span class="logo-text">TUCKER</span>
        </a>
        <!-- Close Button (Visible on Mobile Only) -->
        <button class="sidebar-close-btn" id="sidebarCloseBtn">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Navigation -->
    <div class="sidebar-body">
        <ul class="nav-links">
            <li>
                <a href="Dashboard.php" class="<?= isActive('Dashboard.php', $current_page) ?>">
                    <i class="fa-solid fa-chart-pie"></i><span>Overview</span>
                </a>
            </li>
            <li>
                <a href="lead.php" class="<?= isActive('lead.php', $current_page) ?>">
                    <i class="fa-solid fa-user-group"></i><span>Leads</span>
                </a>
            </li>

            <?php if ($currentUsername === 'Amsathvani'): ?>
                <li>
                    <a href="product.php" class="<?= isActive('product.php', $current_page) ?>">
                        <i class="fa-solid fa-box-open"></i><span>Products</span>
                    </a>
                </li>
            <?php endif; ?>

            <li>
                <a href="Quotation.php" class="<?= isActive('Quotation.php', $current_page) ?>">
                    <i class="fa-solid fa-file-invoice-dollar"></i><span>Quotations</span>
                </a>
            </li>

            <?php if ($currentUsername === 'Amsathvani'): ?>
                <li>
                    <a href="orders.php" class="<?= isActive('orders.php', $current_page) ?>">
                        <i class="fa-solid fa-cart-shopping"></i><span>Orders</span>
                    </a>
                </li>
                <li>
                    <a href="cpo_bill.php" class="<?= isActive('cpo_bill.php', $current_page) ?>">
                        <i class="fa-solid fa-clipboard-list"></i><span>CPO Bills</span>
                    </a>
                </li>

                <li>
                    <a href="Franchise.php" class="<?= isActive('Franchise.php', $current_page) ?>">
                        <i class="fa-solid fa-store"></i><span>Franchise</span>
                    </a>
                </li>
                <li>
                    <a href="Revenue.php" class="<?= isActive('Revenue.php', $current_page) ?>">
                        <i class="fa-solid fa-chart-line"></i><span>Revenue</span>
                    </a>
                </li>
                <li>
                    <a href="Inquiries.php" class="<?= isActive('Inquiries.php', $current_page) ?>">
                        <i class="fa-solid fa-envelope-open-text"></i><span>Inquiries</span>
                    </a>
                </li>
            <?php endif; ?>

            <!-- ADDED: Terms and Conditions -->
            <!-- Placed at the bottom as it is a policy/static page -->
            <!-- <li>
                <a href="Terms_Condition.php" class="<?= isActive('Terms_Condition.php', $current_page) ?>">
                    <i class="fa-solid fa-file-contract"></i><span>Terms & Condition</span>
                </a>
            </li> -->

        </ul>
    </div>

    <!-- Footer -->
    <div class="sidebar-footer">
        <div class="user-profile">
            <!-- Dynamic Avatar based on username -->
            <div class="avatar-circle">
                <?= strtoupper(substr($currentUsername, 0, 1)) ?>
            </div>
            <div class="user-details">
                <span class="user-name"><?= htmlspecialchars($currentUsername) ?></span>
                <span class="user-role"><?= $_SESSION["master_name"] ?></span>
            </div>
            <a href="logout.php" class="logout-btn" title="Logout">
                <i class="fa-solid fa-right-from-bracket"></i>
            </a>
        </div>
    </div>
</nav>

<!-- Styles and Scripts remain the same -->
<style>
    /* ============================
       1. SIDEBAR VARIABLES
    ============================ */
    :root {
        --sidebar-w: 260px;
        --sidebar-bg: #FFFFFF;
        --primary: #4F46E5;
        --text-main: #1F2937;
        --text-muted: #6B7280;
        --border: #E5E7EB;
        --overlay-z: 1999;
        --sidebar-z: 2000;
        /* Must be highest */
    }

    /* ============================
       2. DESKTOP STYLES
    ============================ */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        width: var(--sidebar-w);
        background: var(--sidebar-bg);
        border-right: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        z-index: var(--sidebar-z);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    /* Header */
    .sidebar-header {
        height: 70px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
        border-bottom: 1px solid var(--border);
    }

    .logo-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: var(--text-main);
        font-weight: 800;
        font-size: 20px;
        letter-spacing: 0.5px;
    }

    .logo-icon {
        color: var(--primary);
    }

    .sidebar-close-btn {
        display: none;
        /* Hidden on Desktop */
        background: transparent;
        border: none;
        font-size: 24px;
        color: var(--text-muted);
        cursor: pointer;
    }

    /* Navigation */
    .sidebar-body {
        flex: 1;
        overflow-y: auto;
        padding: 20px 15px;
    }

    .nav-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .nav-links li {
        margin-bottom: 6px;
    }

    .nav-links a {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        border-radius: 8px;
        color: var(--text-muted);
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .nav-links a:hover {
        background: #F3F4F6;
        color: var(--text-main);
    }

    .nav-links a.active {
        background: var(--primary);
        color: #FFF;
        box-shadow: 0 4px 6px -2px rgba(79, 70, 229, 0.3);
    }

    .nav-links a i {
        width: 20px;
        text-align: center;
    }

    /* Footer */
    .sidebar-footer {
        border-top: 1px solid var(--border);
        padding: 15px;
        background: #F9FAFB;
    }

    .user-profile {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .avatar-circle {
        width: 40px;
        height: 40px;
        background: var(--primary);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
    }

    .user-details {
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .user-name {
        font-size: 14px;
        font-weight: 600;
        color: var(--text-main);
    }

    .user-role {
        font-size: 11px;
        color: var(--text-muted);
    }

    .logout-btn {
        color: var(--text-muted);
        cursor: pointer;
        transition: color 0.2s;
    }

    .logout-btn:hover {
        color: #EF4444;
    }

    /* Overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: var(--overlay-z);
        backdrop-filter: blur(2px);
        opacity: 0;
        transition: opacity 0.3s;
    }

    .sidebar-overlay.active {
        display: block;
    }

    /* ============================
       3. MOBILE STYLES (Max-width: 992px)
    ============================ */
    @media (max-width: 992px) {
        .sidebar {
            transform: translateX(-100%);
            /* Hidden by default */
            width: 280px;
            /* Slightly wider for touch */
        }

        .sidebar.open {
            transform: translateX(0);
            /* Slide In */
            box-shadow: 10px 0 25px rgba(0, 0, 0, 0.1);
        }

        .sidebar-close-btn {
            display: block;
        }

        /* Show X button */
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.getElementById('mainSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const closeBtn = document.getElementById('sidebarCloseBtn');
        // This button is in the parent page
        const toggleBtn = document.getElementById('mobileMenuBtn');

        function toggleSidebar(show) {
            if (show) {
                sidebar.classList.add('open');
                overlay.classList.add('active');
                document.body.style.overflow = 'hidden'; // Stop background scroll
            } else {
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        // Parent Page Trigger
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleSidebar(true);
            });
        }

        // Close Triggers
        if (closeBtn) closeBtn.addEventListener('click', () => toggleSidebar(false));
        if (overlay) overlay.addEventListener('click', () => toggleSidebar(false));
    });
</script>