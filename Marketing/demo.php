<?php
// ==========================================
// 1. PHP API LOGIC (Must be at the very top)
// ==========================================
require_once 'include/session_config.php';
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

// Check if this is an API request
if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    // MONTHLY DATA
    if ($_POST['action'] === 'get_monthly_quotes') {
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') AS month,
                    COUNT(*) as total,
                    '' as parentID
                FROM quotations
                GROUP BY month
                ORDER BY month ASC";

        $result = $con->query($sql);
        echo json_encode($result->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    // DAILY DATA
    if ($_POST['action'] === 'get_daily_quotes') {
        $month = $_POST['month'];
        $sql = "SELECT 
                    DATE(created_at) as day,
                    COUNT(*) as total
                FROM quotations
                WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
                GROUP BY day
                ORDER BY day ASC";

        $stmt = $con->prepare($sql);
        $stmt->bind_param("s", $month);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }

    // DETAILS DATA
    if ($_POST['action'] === 'get_quote_details') {
        $day = $_POST['day'];
        $sql = "SELECT * FROM quotations WHERE DATE(created_at) = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param("s", $day);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Graph</title>

    <!-- FontAwesome (For Icons) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- DevExtreme CSS -->
    <link rel="stylesheet" href="https://cdn3.devexpress.com/jslib/23.2.5/css/dx.light.css">

    <style>
        .demo-container {
            margin-top: 20px;
            padding: 20px;
        }

        #chart {
            height: 400px;
            width: 100%;
        }

        .btn-icon {
            cursor: pointer;
            padding: 5px 10px;
            text-decoration: none;
        }
    </style>
</head>

<body>

    <!-- UI Controls -->
    <div class="widget-container">
        <div class="dx-fieldset">
            <div class="dx-field" style="text-align:right;">
                <label class="label-tp" style="font-weight:bold; margin-right: 10px;">Chart Type:</label>

                <a class="btn btn-sm btn-clean btn-icon barAction" title="Bar Chart" onclick="checkChart('bar')">
                    <i class="fas fa-chart-bar" style="color:#0abb87; font-size: 24px;"></i>
                </a>
                <a class="btn btn-sm btn-clean btn-icon lineAction" title="Line Chart" onclick="checkChart('line')">
                    <i class="fas fa-chart-line" style="color:#7474C1; font-size: 24px;"></i>
                </a>
                <a class="btn btn-sm btn-clean btn-icon pieAction" title="Pie Chart" onclick="checkChart('pie')">
                    <i class="fas fa-chart-pie" style="color: #E2ACD7; font-size: 24px;"></i>
                </a>
                <a class="btn btn-sm btn-clean btn-icon doughnutAction" title="Doughnut Chart" onclick="checkChart('doughnut')">
                    <i class="fa-solid fa-circle-dot" style="color: #E8BA5A; font-size: 24px;"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Chart Area -->
    <div class="dx-viewport demo-container">
        <div id="chart"></div>
        <div class="button-container">
            <div id="backButton"></div>
        </div>
    </div>

    <!-- Load jQuery FIRST -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Load DevExtreme SECOND (ONLY ONCE) -->
    <script src="https://cdn3.devexpress.com/jslib/23.2.5/js/dx.all.js"></script>

    <!-- App Logic -->
    <script>
        // Use empty string to target this exact same file for the POST request
        const API_URL = "";

        let currentChartType = "bar";
        let currentData = [];
        let currentTitle = "Monthly Quotations";
        let isDailyView = false;
        let currentMonth = "";

        $(() => {
            // Initialize the Back Button
            $('#backButton').dxButton({
                text: 'Back to Monthly View',
                icon: 'chevronleft',
                visible: false,
                onClick() {
                    loadMonthlyChart();
                }
            });

            // Load initial data on page load
            loadMonthlyChart();
        });

        // Triggered by the HTML icons
        function checkChart(type) {
            currentChartType = type;
            if (currentData.length > 0) {
                renderChart(currentData, currentTitle, currentChartType);
            }
        }

        // Handles destroying old chart instances and creating the new one
        function renderChart(data, title, chartType) {
            currentData = data;
            currentTitle = title;
            let chartContainer = $("#chart");
            let argField = isDailyView ? "day" : "month";

            // Safely dispose of previous charts
            if (chartContainer.data("dxChart")) chartContainer.dxChart("dispose");
            if (chartContainer.data("dxPieChart")) chartContainer.dxPieChart("dispose");

            if (chartType === "bar" || chartType === "line") {
                chartContainer.dxChart({
                    dataSource: data,
                    title: title,
                    series: {
                        argumentField: argField,
                        valueField: "total",
                        type: chartType,
                        label: {
                            visible: true,
                            format: "fixedPoint"
                        },
                        color: "#6babac"
                    },
                    legend: {
                        visible: false
                    },
                    tooltip: {
                        enabled: true
                    },
                    onPointClick: handlePointClick
                });
            } else if (chartType === "pie" || chartType === "doughnut") {
                chartContainer.dxPieChart({
                    dataSource: data,
                    title: title,
                    series: {
                        argumentField: argField,
                        valueField: "total",
                        type: chartType,
                        label: {
                            visible: true,
                            connector: {
                                visible: true
                            }
                        }
                    },
                    legend: {
                        horizontalAlignment: "right",
                        verticalAlignment: "top"
                    },
                    tooltip: {
                        enabled: true
                    },
                    onPointClick: handlePointClick
                });
            }
        }

        // Logic for Drill-down
        function handlePointClick(e) {
            if (!isDailyView) {
                // Drill down from Month -> Day
                currentMonth = e.target.argument;
                loadDailyChart(currentMonth);
            } else {
                // Drill down from Day -> Table Details
                loadQuotationDetails(e.target.argument);
            }
        }

        // --- API FETCH FUNCTIONS ---

        function loadMonthlyChart() {
            isDailyView = false;
            fetch(API_URL, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "action=get_monthly_quotes"
                })
                .then(res => res.json())
                .then(data => {
                    let formattedData = data.map(d => ({
                        month: d.month,
                        total: Number(d.total)
                    }));
                    $("#backButton").dxButton("instance").option("visible", false);
                    renderChart(formattedData, "Monthly Quotations", currentChartType);
                })
                .catch(err => console.error("Error loading monthly data:", err));
        }

        function loadDailyChart(month) {
            isDailyView = true;
            fetch(API_URL, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `action=get_daily_quotes&month=${encodeURIComponent(month)}`
                })
                .then(res => res.json())
                .then(data => {
                    let formattedData = data.map(d => ({
                        day: d.day,
                        total: Number(d.total)
                    }));
                    $("#backButton").dxButton("instance").option("visible", true);
                    renderChart(formattedData, `Daily Quotations (${month})`, currentChartType);
                })
                .catch(err => console.error("Error loading daily data:", err));
        }

        function loadQuotationDetails(day) {
            fetch(API_URL, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `action=get_quote_details&day=${encodeURIComponent(day)}`
                })
                .then(res => res.json())
                .then(data => {
                    let rows = data.map((q, i) => `
                    <tr>
                        <td>${i + 1}</td>
                        <td>${q.client_name}</td>
                        <td>${q.quotation_no}</td>
                        <td>${q.created_at}</td>
                    </tr>
                `).join('');

                    // Ensure these elements exist in your real project's modal/popup
                    if (document.getElementById("statusLeadsTableHead")) {
                        document.getElementById("statusLeadsTableHead").innerHTML = `<tr><th>#</th><th>Client</th><th>Quotation</th><th>Date</th></tr>`;
                        document.getElementById("statusLeadsTableBody").innerHTML = rows;
                    }

                    if (typeof toggleStatusModal === "function") {
                        toggleStatusModal(true);
                    } else {
                        console.log("Modal function not found, but data fetched successfully:", data);
                    }
                })
                .catch(err => console.error("Error loading details:", err));
        }
    </script>
</body>

</html>