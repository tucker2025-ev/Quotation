<?php
// Include session configuration
require_once 'include/session_config.php';

// Check if user is logged in
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions | Tucker Motors</title>

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="https://station.cms.tuckermotors.com/asset/images/favicon.ico">
    
    <style>
        /* ============================
           1. CSS VARIABLES (Matched to Lead.php)
        ============================ */
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338ca;
            --bg-body: #F3F4F6;
            --bg-card: #FFFFFF;
            --text-main: #111827;
            --text-muted: #6B7280;
            --border: #E5E7EB;
            --sidebar-w: 260px;
            --sidebar-w-collapsed: 80px;
        }

        /* ============================
           2. LAYOUT & TYPOGRAPHY
        ============================ */
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            font-size: 14px;
            transition: background 0.3s;
        }

        .main-content {
            margin-left: var(--sidebar-w);
            padding: 30px;
            min-height: 100vh;
            transition: margin-left 0.3s ease, padding 0.3s;
        }

        h1 {
            margin: 0 0 10px 0;
            font-size: 24px;
            font-weight: 700;
        }
        
        .subtitle {
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        /* ============================
           3. MOBILE HEADER
        ============================ */
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

        /* ============================
           4. TERMS CONTENT CARD
        ============================ */
        .terms-card {
            background: var(--bg-card);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            padding: 40px;
            /* FULL WIDTH SETTINGS: */
            width: 100%; 
            box-sizing: border-box;
        }

        .term-section {
            margin-bottom: 30px;
            border-bottom: 1px dashed var(--border);
            padding-bottom: 20px;
        }
        
        .term-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .term-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .term-number {
            background: var(--primary);
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .term-title {
            font-size: 18px; /* Slightly larger for full width */
            font-weight: 700;
            color: var(--text-main);
        }

        .term-list {
            margin: 0;
            padding-left: 54px; /* Align with text start */
            list-style-type: disc;
            color: #4B5563;
            font-size: 15px; /* Slightly larger text for readability on wide screens */
        }

        .term-list li {
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .nested-list {
            list-style-type: circle;
            margin-top: 5px;
            padding-left: 20px;
        }

        /* ============================
           5. RESPONSIVE
        ============================ */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            .mobile-header {
                display: flex;
            }
        }

        @media (max-width: 768px) {
            .terms-card {
                padding: 20px;
            }
            .term-list {
                padding-left: 20px; /* Reset indentation for small screens */
            }
            .term-number {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }
            .term-title {
                font-size: 16px;
            }
            .term-list {
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar-wrapper">
        <?php require_once 'include/sidebar.php'; ?>
    </div>

    <div class="main-content">
        <!-- Mobile Header -->
        <div class="mobile-header">
            <button id="mobileMenuBtn" class="menu-btn">
                <i class="fa-solid fa-bars"></i>
            </button>
            <h2 style="margin:0; font-size: 16px; font-weight:600;">Terms</h2>
            <div style="width: 32px;"></div>
        </div>

        <h1>Terms & Conditions</h1>
        <p class="subtitle">Standard operating procedures and policies for Tucker Motors.</p>

        <!-- Content Card -->
        <div class="terms-card">
            
            <!-- 1. Price & Validity -->
            <div class="term-section">
                <div class="term-header">
                    <div class="term-number">1</div>
                    <div class="term-title">Price & Validity</div>
                </div>
                <ul class="term-list">
                    <li>Prices quoted are exclusive of GST unless otherwise specified.</li>
                    <li>Quotation is valid for 30 days from the date of issue.</li>
                    <li>Prices are subject to change after validity expiry.</li>
                </ul>
            </div>

            <!-- 2. Scope of Supply -->
            <div class="term-section">
                <div class="term-header">
                    <div class="term-number">2</div>
                    <div class="term-title">Scope of Supply</div>
                </div>
                <ul class="term-list">
                    <li>Supply includes EV charger equipment as mentioned in the quotation.</li>
                    <li>Accessories, civil work, cabling, earthing, networking, and installation are excluded unless explicitly stated.</li>
                    <li>Software features are limited to the version available at the time of delivery.</li>
                </ul>
            </div>

            <!-- 3. Payment Terms -->
            <div class="term-section">
                <div class="term-header">
                    <div class="term-number">3</div>
                    <div class="term-title">Payment Terms</div>
                </div>
                <ul class="term-list">
                    <li>70% advance & 30% before dispatch.</li>
                    <li>Dispatch will be initiated only after receipt of full payment.</li>
                    <li>All payments must be made via approved banking channels.</li>
                </ul>
            </div>

            <!-- 4. Delivery & Dispatch -->
            <div class="term-section">
                <div class="term-header">
                    <div class="term-number">4</div>
                    <div class="term-title">Delivery & Dispatch</div>
                </div>
                <ul class="term-list">
                    <li>Delivery period: 2 - 3 weeks from confirmation of order and advance payment.</li>
                    <li>Freight charges are extra unless mentioned as “inclusive”.</li>
                    <li>Risk transfers to the buyer once the material is handed over to the transporter.</li>
                </ul>
            </div>

            <!-- 5. Installation & Commissioning -->
            <div class="term-section">
                <div class="term-header">
                    <div class="term-number">5</div>
                    <div class="term-title">Installation & Commissioning</div>
                </div>
                <ul class="term-list">
                    <li>Installation and commissioning are not included unless mentioned separately.</li>
                    <li>Site readiness (power availability, earthing, internet, space) must be ensured by the customer.</li>
                    <li>Delays due to site conditions are not the supplier’s responsibility.</li>
                </ul>
            </div>

            <!-- 6. Warranty -->
            <div class="term-section">
                <div class="term-header">
                    <div class="term-number">6</div>
                    <div class="term-title">Warranty</div>
                </div>
                <ul class="term-list">
                    <li>Charger comes with a 1-year warranty from the date of installation or dispatch.</li>
                    <li>Warranty covers manufacturing defects only.</li>
                    <li>Warranty does not cover damages due to:
                        <ul class="nested-list">
                            <li>Power fluctuations</li>
                            <li>Improper installation</li>
                            <li>Physical damage</li>
                            <li>Water ingress</li>
                            <li>Unauthorized modification or misuse</li>
                        </ul>
                    </li>
                </ul>
            </div>

            <!-- 7. AMC & Support -->
            <div class="term-section">
                <div class="term-header">
                    <div class="term-number">7</div>
                    <div class="term-title">AMC & Support</div>
                </div>
                <ul class="term-list">
                    <li>Annual Maintenance Contract (AMC) is available after the warranty period.</li>
                    <li>Software updates and remote support are subject to AMC terms.</li>
                    <li>SIM/data charges are borne by the customer.</li>
                </ul>
            </div>

            <!-- 8. Taxes & Statutory Levies -->
            <div class="term-section">
                <div class="term-header">
                    <div class="term-number">8</div>
                    <div class="term-title">Taxes & Statutory Levies</div>
                </div>
                <ul class="term-list">
                    <li>GST and any applicable government taxes will be charged as per prevailing rates.</li>
                    <li>Any future statutory changes will be applicable to the customer.</li>
                </ul>
            </div>

            <!-- 9. Returns & Cancellation -->
            <div class="term-section">
                <div class="term-header">
                    <div class="term-number">9</div>
                    <div class="term-title">Returns & Cancellation</div>
                </div>
                <ul class="term-list">
                    <li>Orders once confirmed cannot be cancelled.</li>
                    <li>Customized or configured chargers are non-returnable.</li>
                    <li>No refunds once material is dispatched.</li>
                </ul>
            </div>

            <!-- 10. Liability -->
            <div class="term-section">
                <div class="term-header">
                    <div class="term-number">10</div>
                    <div class="term-title">Liability</div>
                </div>
                <ul class="term-list">
                    <li>The supplier is not liable for indirect, incidental, or consequential damages.</li>
                    <li>Maximum liability is limited to the value of the supplied equipment.</li>
                </ul>
            </div>

            <!-- 11. Force Majeure -->
            <div class="term-section">
                <div class="term-header">
                    <div class="term-number">11</div>
                    <div class="term-title">Force Majeure</div>
                </div>
                <ul class="term-list">
                    <li>The supplier shall not be responsible for delays due to events beyond control such as natural disasters, government actions, strikes, or supply chain disruptions.</li>
                </ul>
            </div>

            <!-- 12. Governing Law & Jurisdiction -->
            <div class="term-section">
                <div class="term-header">
                    <div class="term-number">12</div>
                    <div class="term-title">Governing Law & Jurisdiction</div>
                </div>
                <ul class="term-list">
                    <li>This quotation is governed by Indian laws.</li>
                    <li>Jurisdiction shall be [Madurai / Tamil Nadu] only.</li>
                </ul>
            </div>

        </div>
    </div>

    <script>
        // Mobile Sidebar Toggle Logic
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                // This triggers the logic inside your include/sidebar.php 
                // typically looking for a click event to add 'open' class
                const sidebar = document.getElementById('mainSidebar');
                const overlay = document.getElementById('sidebarOverlay');
                if(sidebar && overlay) {
                    sidebar.classList.add('open');
                    overlay.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            });
        }
    </script>
</body>
</html>