<?php

include 'connect.php';

if (!isset($_SESSION['id_no'])) {
    header('Location: index.php');
    exit;
}

$id_no = $_SESSION['id_no'];

// Initialize fullname with a default value.
$fullname = "User";

// --- Fetch user's full name from the database ---
$sql_name = "SELECT fullname FROM contributions WHERE national_id = ? LIMIT 1";
$stmt_name = $con->prepare($sql_name);
if ($stmt_name) {
    $stmt_name->bind_param("s", $id_no);
    $stmt_name->execute();
    $result_name = $stmt_name->get_result();
    if ($row_name = $result_name->fetch_assoc()) {
        $fullname = $row_name['fullname'];
    }
    $stmt_name->close();
}

// Placeholder for a logo URL. A real application should fetch this from a more reliable source.
$logo_url = "https://digitalboda.co.ke/portal/Digital-Boda-Logo.png";
$logo_base64 = "";

// Attempt to fetch the logo image and encode it as base64 for embedding
$image_data = @file_get_contents($logo_url);
if ($image_data !== false) {
    $mime_type = 'image/png';
    $logo_base64 = 'data:' . $mime_type . ';base64,' . base64_encode($image_data);
} else {
    error_log("Failed to fetch logo image from: " . $logo_url);
}

// --- Dynamic Data Calculation from DB ---

// Define the daily rate for Advocacy contributions
$DAILY_RATE = 10;

// Get total Advocacy contributions and the date of the first contribution
$sql_summary = "SELECT SUM(amount) AS total_paid, MIN(transaction_time) AS first_date FROM contributions WHERE national_id = ? AND contribution_type = 'Advocacy(MCC)'";
$stmt_summary = $con->prepare($sql_summary);
$totalAdvocacyPaid = 0;
$firstContributionDate = null;
if ($stmt_summary) {
    $stmt_summary->bind_param("s", $id_no);
    $stmt_summary->execute();
    $result_summary = $stmt_summary->get_result();
    $summary_data = $result_summary->fetch_assoc();
    $totalAdvocacyPaid = $summary_data['total_paid'] ?? 0;
    $firstContributionDate = $summary_data['first_date'];
    $stmt_summary->close();
}

// Get all contributions and donations for points calculation
$sql_contributions_points = "SELECT transaction_time, amount FROM contributions WHERE national_id = ?";
$stmt_contrib_points = $con->prepare($sql_contributions_points);
$stmt_contrib_points->bind_param("s", $id_no);
$stmt_contrib_points->execute();
$contributions = $stmt_contrib_points->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_contrib_points->close();

$sql_donations_points = "SELECT transaction_time, amount FROM donations WHERE national_id = ?";
$stmt_don_points = $con->prepare($sql_donations_points);
$stmt_don_points->bind_param("s", $id_no);
$stmt_don_points->execute();
$donations = $stmt_don_points->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_don_points->close();

// === Points Calculation Logic ===
$totalPoints = 0;
$pointsHistory = [];

function calculateContributionPoints($amount) {
    $points = 0;
    if ($amount > 10) {
        $remaining = $amount - 10;
        $points = floor($remaining / 10);
    }
    return $points;
}

function calculateDonationPoints($amount) {
    return floor($amount / 50);
}

// Process contributions
foreach ($contributions as $c) {
    $points = calculateContributionPoints($c['amount']);
    $totalPoints += $points;
    $pointsHistory[] = [
        'date' => date('Y-m-d', strtotime($c['transaction_time'])),
        'amount' => $c['amount'],
        'points' => $points,
        'type' => 'Contribution'
    ];
}

// Process donations
foreach ($donations as $d) {
    $points = calculateDonationPoints($d['amount']);
    $totalPoints += $points;
    $pointsHistory[] = [
        'date' => date('Y-m-d', strtotime($d['transaction_time'])),
        'amount' => $d['amount'],
        'points' => $points,
        'type' => 'Uplift/Donation'
    ];
}

// Sort the points history by date
usort($pointsHistory, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

// === Daily Contribution Expected Calculation ===
$totalDailyExpected = 0;
$daysCovered = 0;
$daysPending = 0;

if ($firstContributionDate !== null) {
    $start = new DateTime(date('Y-m-d', strtotime($firstContributionDate)));
    $end = new DateTime(date('Y-m-d'));
    $days = $start->diff($end)->days + 1;

    $totalDailyExpected = $days * $DAILY_RATE;
    $daysCovered = floor($totalAdvocacyPaid / $DAILY_RATE);
    $daysPending = $days - $daysCovered;
}

$dailyPendingAmount = $totalDailyExpected - $totalAdvocacyPaid;

// === Status Formatter ===
function formatStatus($expected, $paid, $diff) {
    if ($diff < 0) {
        return "Overpaid by <strong>KES " . number_format(abs($diff), 2) . "</strong>";
    } elseif ($diff > 0) {
        return "Pending: <strong>KES " . number_format($diff, 2) . "</strong>";
    } else {
        return "Paid in full: <strong>KES " . number_format($paid, 2) . "</strong>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contribution Overview</title>
    <!-- Assuming userdashboard.css exists, keeping it here -->
    <link rel="stylesheet" href="userdashboard.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap">
    <!-- Include jsPDF and html2canvas for PDF generation -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <style>
        /* General styles for the dashboard section */
        .dashboard-section {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Styles for tables and their container */
        .table-responsive {
            overflow-x: auto;
            margin-top: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #ddd;
        }

        thead tr {
            background: #F25A2C;
            color: white;
            text-transform: uppercase;
        }

        tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tbody tr:hover {
            background-color: #f1f1f1;
        }

        .text-center {
            text-align: center;
        }

        /* Styles for the summary cards */
        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin: 1rem 0;
        }
        .col-md-4, .col-md-6 {
            flex: 1;
            min-width: 250px;
        }
        .card {
            background-color: #fff;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: 100%;
        }
        .card-title {
            color: #F25A2C;
            font-size: 1rem;
            margin-bottom: 1rem;
        }
        
        .list-group {
            max-height: 200px;
            overflow-y: auto;
        }
        .list-group-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border: none;
        }
        .badge {
            font-weight: bold;
        }

        /* Download button style */
        .download-button-container {
            text-align: right;
            margin-bottom: 1rem;
            padding: 0 2rem;
        }
        .download-button {
            background-color: #337ab7;
            color: white;
            border: none;
            padding: 8px 15px;
            font-size: 1rem;
            cursor: pointer;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: background-color 0.3s ease;
        }
        .download-button:hover {
            background-color: #286090;
        }

        /* Styles for the chart */
        #AdvocacyChart {
            margin-top: 2rem;
        }

        /* Media queries for responsiveness */
        @media (max-width: 768px) {
            .row {
                flex-direction: column;
            }
        }
        
        /* Styles for the print header, including logo */
        .print-header {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .print-header .header-content {
            display: flex;
            align-items: center;
        }
        .print-header h2 {
            margin: 0;
            margin-left: 10px;
        }
        .print-header .logo {
            width: 150px;
            height: 90px;
        }

        /* === Print-specific styles === */
        @media print {
            .print-header h2 {
                text-align: left;
                margin-bottom: 0;
            }
        }
    </style>
</head>
<body>

<div class="download-button-container">
    <button class="download-button" onclick="downloadStatement()">Download Statement</button>
</div>

<section class="dashboard-section" id="contribution">
    <div class="print-content" id="downloadableContent">
        <div class="print-header">
            <div class="header-content">
                <img src="<?= htmlspecialchars($logo_base64); ?>" alt="Digital Boda Logo" class="logo">
                <h2 style="text-transform: uppercase;"><?php echo htmlspecialchars($fullname); ?> Contribution</h2>
            </div>
        </div>

        <div style="margin-bottom: 20px; display: flex; gap: 10px;">
            <input type="text" id="tableSearch" placeholder="Search by MPESA Code or Date (YYYY-MM-DD)..." 
                   style="padding: 10px; width: 100%; max-width: 400px; border: 1px solid #ddd; border-radius: 5px;">
        </div>

        <div class="table-responsive">
            <table border="1" cellspacing="0" cellpadding="10" style="width: 100%; border-collapse: collapse;" id="contributionTable">
                <thead class="table-dark">
                    <tr style="background: #F25A2C; color: white;">
                        <th>#</th>
                        <th>Full Name</th>
                        <th>National ID</th>
                        <th>Phone</th>
                        <th>Amount</th>
                        <th>Frequency</th>
                        <th>MPESA Code</th>
                        <th>Transaction Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "SELECT * FROM contributions WHERE national_id = ? ORDER BY transaction_time DESC";
                $stmt = $con->prepare($sql);
                if (!$stmt) {
                    die("Failed to prepare statement: " . $con->error);
                }
                $stmt->bind_param("s", $id_no);
                $stmt->execute();
                $result = $stmt->get_result();
                $total_rows = $result->num_rows;

                if ($total_rows > 0): 
                    $i = 1;
                    while($row = $result->fetch_assoc()): ?>
                        <tr class="record-row <?= ($i > 5) ? 'hidden-row' : '' ?>" style="<?= ($i > 5) ? 'display: none;' : '' ?>">
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($row['fullname']?? '') ?></td>
                            <td><?= htmlspecialchars($row['national_id']?? '') ?></td>
                            <td><?= htmlspecialchars($row['phone']?? '') ?></td>
                            <td><?= number_format($row['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($row['contribution_type']) ?></td>
                            <td class="mpesa-cell"><?= htmlspecialchars($row['mpesa_code'] ?? '') ?></td>
                            <td class="date-cell"><?= htmlspecialchars($row['transaction_time']?? '') ?></td>
                        </tr>
                    <?php endwhile; ?>

                    <?php if ($total_rows > 5): ?>
                        <tr id="toggleRow">
                            <td colspan="8" class="text-center">
                                <button type="button" class="download-button" style="background:#666;" onclick="toggleRows(this, 'contributionTable')">Show All Records</button>
                            </td>
                        </tr>
                    <?php endif; ?>

                <?php else: ?>
                    <tr><td colspan="8" class="text-center">No contributions yet.</td></tr>
                <?php endif; // This closes the IF result > 0 block ?>
                </tbody>
            </table>
        </div>
    
        <div class="table-responsive">
            <table border="1" cellspacing="0" cellpadding="10" style="width: 100%; border-collapse: collapse;" id="donationTable">
                <thead class="table-dark">
                    <tr style="background: #F25A2C; color: white;">
                        <th>#</th>
                        <th>Full Name</th>
                        <th>National ID</th>
                        <th>Phone</th>
                        <th>Uplift Title</th>
                        <th>Uplift Amount</th>
                        <th>MPESA Code</th>
                        <th>Transaction Time</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $sql = "SELECT * FROM donations WHERE national_id = ? ORDER BY transaction_time DESC";
                $stmt = $con->prepare($sql);
                if (!$stmt) {
                    die("Failed to prepare statement: " . $con->error);
                }
                $stmt->bind_param("s", $id_no);
                $stmt->execute();
                $result = $stmt->get_result();
                ?>
    
                <?php if ($result->num_rows > 0): $i = 1; ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                         <tr class="record-row <?= ($i > 5) ? 'hidden-row' : '' ?>" style="<?= ($i > 5) ? 'display: none;' : '' ?>">
                            <td><?= htmlspecialchars($i++) ?></td>
                            <td><?= htmlspecialchars($row['fullname']) ?></td>
                            <td><?= htmlspecialchars($row['national_id']) ?></td>
                            <td><?= htmlspecialchars($row['phone']) ?></td>
                            <td><?= htmlspecialchars($row['donation_title']) ?></td>
                            <td><?= number_format($row['amount'], 2) ?></td>
                            <td><?= htmlspecialchars($row['mpesa_code']) ?></td>
                            <td><?= htmlspecialchars($row['transaction_time']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                     <?php if ($total_rows > 5): ?>
                        <tr id="toggleRow">
                            <td colspan="8" class="text-center">
                                <button type="button" class="download-button" style="background:#666;" onclick="toggleRows(this, 'contributionTable')">Show All Records</button>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php else: ?>
                    <tr><td colspan="8" class="text-center">No donations yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    
    <!-- === Bootstrap Display === -->
    <div class="row my-6">
    
      <!-- Advocacy Contributions -->
      <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title">📅 Advocacy(MCC) Contributions</h5>
            <p>Total Paid: <strong>KES <?= number_format($totalAdvocacyPaid, 2) ?></strong></p>
            <p>Expected Daily: <strong>KES <?= number_format($totalDailyExpected, 2) ?></strong></p>
            <p><?= formatStatus($totalDailyExpected, $totalAdvocacyPaid, $dailyPendingAmount) ?></p>
          </div>
        </div>
      </div>
    
      <!-- Points Card -->
      <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title">✨ Total Points Earned</h5>
            <p>Total Points: <strong><?= number_format($totalPoints) ?></strong></p>
    
            
          </div>
        </div>
      </div>
    
      <!-- Days Record -->
      <div class="col-md-4 mb-4">
        <div class="card shadow-sm">
          <div class="card-body">
            <h5 class="card-title">🗓️ Days Record</h5>
            <p>Days Covered: <strong><?= number_format($daysCovered) ?></strong></p>
            <p>Days Pending: <strong><?= number_format($daysPending) ?></strong></p>
          </div>
        </div>
      </div>
    
    </div>
    <?php
    // --- Chart Data Fetching ---
    $sql_chart = "SELECT amount, transaction_time FROM contributions WHERE national_id = ? AND contribution_type = 'Advocacy(MCC)' ORDER BY transaction_time ASC";
    $stmt_chart = $con->prepare($sql_chart);
    $stmt_chart->bind_param("s", $id_no);
    $stmt_chart->execute();
    $result_chart = $stmt_chart->get_result();

    $dailyData = [];
    $firstDate = null;
    $currentCumulativePaid = 0;

    if ($result_chart->num_rows > 0) {
        while ($row = $result_chart->fetch_assoc()) {
            $transactionDate = date('Y-m-d', strtotime($row['transaction_time']));
            $amount = floatval($row['amount']);
            if ($firstDate === null) {
                $firstDate = $transactionDate;
            }
            if (!isset($dailyData[$transactionDate])) {
                $dailyData[$transactionDate] = 0;
            }
            $dailyData[$transactionDate] += $amount;
        }
    }

    $chartLabels = [];
    $cumulativePaidValues = [];
    $expectedValues = [];
    $currentDate = $firstDate;
    
    if ($firstDate === null) {
        $firstDate = date('Y-m-d');
        // If no contributions, set the initial values to zero to avoid errors
        $chartLabels[] = $firstDate;
        $cumulativePaidValues[] = 0;
        $expectedValues[] = 0;
    } else {
        $cumulativePaid = 0;
        $dayCount = 0;
        while (strtotime($currentDate) <= strtotime(date('Y-m-d'))) {
            $chartLabels[] = $currentDate;
            
            if (isset($dailyData[$currentDate])) {
                $cumulativePaid += $dailyData[$currentDate];
            }
            $cumulativePaidValues[] = $cumulativePaid;
            
            $dayCount++;
            $expectedValues[] = $dayCount * $DAILY_RATE;
            
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }
    }
    
    $chartLabelsJson = json_encode($chartLabels);
    $cumulativePaidValuesJson = json_encode($cumulativePaidValues);
    $expectedValuesJson = json_encode($expectedValues);
    ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <canvas id="AdvocacyChart" height="120"></canvas>

</section>

<script>
// Toggle for Points Summary
function togglePoints(btn) {
    const container = document.getElementById('pointsList');
    const hiddenItems = container.querySelectorAll('.hidden-point');
    const isExpanded = btn.innerText === "Show Less";

    hiddenItems.forEach(item => {
        item.style.display = isExpanded ? "none" : "block";
    });

    btn.innerText = isExpanded ? "Show More" : "Show Less";
}

// Update your existing search listener to also filter points
document.getElementById('tableSearch').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    
    // ... your existing table search code ...

    // Filter Points List
    const pointItems = document.querySelectorAll('.point-item');
    const pointsToggleBtn = document.querySelector('[onclick="togglePoints(this)"]');

    pointItems.forEach((item, index) => {
        const dateText = item.getAttribute('data-date').toLowerCase();
        
        if (filter === "") {
            // Reset to default (first 5 shown)
            item.style.display = (index < 5) ? "block" : "none";
            if(pointsToggleBtn) pointsToggleBtn.parentElement.style.display = "block";
        } else {
            // Show if matches search
            const match = dateText.includes(filter);
            item.style.display = match ? "block" : "none";
            if(pointsToggleBtn) pointsToggleBtn.parentElement.style.display = "none";
        }
    });
});

// Toggle Function for "Show More"
function toggleRows(btn, tableId) {
    const table = document.getElementById(tableId);
    const hiddenRows = table.querySelectorAll('.hidden-row');
    const isShowing = btn.innerText === "Show Less";

    hiddenRows.forEach(row => {
        row.style.display = isShowing ? 'none' : 'table-row';
    });

    btn.innerText = isShowing ? "Show All Records" : "Show Less";
}

// Search Functionality
document.getElementById('tableSearch').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const tables = ['contributionTable', 'donationTable'];

    tables.forEach(tableId => {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        const rows = table.querySelectorAll('tbody tr.record-row');

        rows.forEach(row => {
            const mpesa = row.querySelector('.mpesa-cell')?.innerText.toLowerCase() || "";
            const date = row.querySelector('.date-cell')?.innerText.toLowerCase() || "";
            
            if (mpesa.includes(filter) || date.includes(filter)) {
                row.style.display = ""; // Show if matches
            } else {
                row.style.display = "none"; // Hide if no match
            }
        });

        // Reset to 5-row limit if search is cleared
        if (filter === "") {
            rows.forEach((row, index) => {
                row.style.display = index < 5 ? "" : "none";
            });
        }
    });
});

    let originalValues = {};
    const fullname = <?= json_encode($fullname) ?>;
    
    function maskSensitiveData() {
        const sensitiveColumns = {
            'National ID': 2,
            'Phone': 3
        };
        const tables = ['contributionTable', 'donationTable'];
        
        tables.forEach(tableId => {
            const table = document.getElementById(tableId);
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach((row, rowIndex) => {
                    if (row.cells.length > 2) {
                        for (const colName in sensitiveColumns) {
                            const colIndex = sensitiveColumns[colName];
                            const cell = row.cells[colIndex];
                            const originalValue = cell.innerText;
                            const key = `${tableId}-${rowIndex}-${colName}`;
                            
                            originalValues[key] = originalValue;
                            
                            if (originalValue.length > 4) {
                                const maskedValue = '****' + originalValue.slice(-4);
                                cell.innerText = maskedValue;
                            }
                        }
                    }
                });
            }
        });
    }

    function unmaskSensitiveData() {
        const tables = ['contributionTable', 'donationTable'];
        const sensitiveColumns = {
            'National ID': 2,
            'Phone': 3
        };

        tables.forEach(tableId => {
            const table = document.getElementById(tableId);
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach((row, rowIndex) => {
                    if (row.cells.length > 2) {
                        for (const colName in sensitiveColumns) {
                            const key = `${tableId}-${rowIndex}-${colName}`;
                            if (originalValues[key]) {
                                const colIndex = sensitiveColumns[colName];
                                const cell = row.cells[colIndex];
                                cell.innerText = originalValues[key];
                            }
                        }
                    }
                });
            }
        });
        originalValues = {};
    }

    async function downloadStatement() {
        const element = document.getElementById('downloadableContent');
        maskSensitiveData();
        const canvas = await html2canvas(element, { scale: 2, useCORS: false });
        const imgData = canvas.toDataURL('image/png');
        unmaskSensitiveData();

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF('p', 'mm', 'a4');
        const imgWidth = 210;
        const pageHeight = 295;
        const imgHeight = canvas.height * imgWidth / canvas.width;
        let heightLeft = imgHeight;
        let position = 0;

        pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        while (heightLeft >= 0) {
            position = heightLeft - imgHeight;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;
        }

        const filename = `${fullname} contribution.pdf`;
        pdf.save(filename);
    }
    
    // --- Chart Scripts ---
    const chartLabels = <?= $chartLabelsJson; ?>;
    const cumulativePaidValues = <?= $cumulativePaidValuesJson; ?>;
    const expectedValues = <?= $expectedValuesJson; ?>;

    const ctx = document.getElementById('AdvocacyChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [
                {
                    label: 'Total Paid (KES)',
                    data: cumulativePaidValues,
                    backgroundColor: 'rgba(242, 90, 44, 0.5)',
                    borderColor: '#F25A2C',
                    borderWidth: 2,
                    fill: false,
                    tension: 0.2
                },
                {
                    label: 'Expected (KES)',
                    data: expectedValues,
                    backgroundColor: 'rgba(51, 122, 183, 0.5)',
                    borderColor: '#337ab7',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    fill: false,
                    tension: 0.2
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Cumulative Advocacy Contributions vs. Expected'
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                },
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    title: {
                        display: true,
                        text: 'KES'
                    },
                    beginAtZero: true
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    },
                    ticks: {
                        autoSkip: true,
                        maxTicksLimit: 10
                    }
                }
            }
        }
    });
</script>
</body>
</html>
