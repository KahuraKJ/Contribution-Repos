<?php
include 'adminNavbar.php';
include 'connect.php';

// Fetch total amounts per donation title
$query = "SELECT donation_title, SUM(amount) AS total_amount FROM donations GROUP BY donation_title ORDER BY donation_title";
$result = $con->query($query);

$donationTitles = [];
$donationAmounts = [];

while ($row = $result->fetch_assoc()) {
    $donationTitles[] = $row['donation_title'];
    $donationAmounts[] = $row['total_amount'];
}

$con->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Donation Graphs</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h2 { color: #F25A2C; }
        .chart-container {
            width: 90%;
            max-width: 900px;
            margin: 40px auto;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<h2><img src="Digital-logo.png" alt="Logo" style="height:30px; vertical-align:middle; margin-right:10px;">DIGITALBODA Donation Graphs</h2>

<div class="chart-container">
    <canvas id="barChart"></canvas>
</div>

<div class="chart-container">
    <canvas id="pieChart"></canvas>
</div>

<script>
    const labels = <?= json_encode($donationTitles) ?>;
    const data = <?= json_encode($donationAmounts) ?>;

    // Bar Chart
    const barCtx = document.getElementById('barChart').getContext('2d');
    const barChart = new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Donations (KES)',
                data: data,
                backgroundColor: '#F25A2C',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: 'Total Donations per Title',
                    font: { size: 18 }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: value => 'KES ' + value.toLocaleString() }
                }
            }
        }
    });

    // Pie Chart
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    const pieChart = new Chart(pieCtx, {
        type: 'pie',
        data: {
            labels: labels,
            datasets: [{
                label: 'Total Donations',
                data: data,
                backgroundColor: labels.map((_, i) => `hsl(${i * 45}, 70%, 60%)`)
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Donation Distribution by Title',
                    font: { size: 18 }
                }
            }
        }
    });
</script>

</body>
</html>
