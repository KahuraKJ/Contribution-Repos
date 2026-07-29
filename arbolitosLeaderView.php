<?php
// Ensure the header is included before any output is sent
include 'header.php';

// Initialize an array to hold the single group's contribution data
$groups_contributions = [];
$status_message = '';
$is_error = false;

// Get the current year and month for filtering
$current_year = date('Y');
$current_month = date('m');
$expected_monthly_contribution = 300;

// IMPORTANT: Replace these with your actual database credentials
$servername = "localhost";
$username = "digita51_enock";
$password = "digita51_enock";
$dbname = "digita51_portal";

// Assuming the leader's ID is passed via a GET parameter or stored in a session.
// For security, you should use a session variable in a real application.
// For this example, let's use a GET parameter.
$leader_id_no = $_GET['id_no'] ?? null;
if (!$leader_id_no) {
    $status_message = "Leader ID not provided.";
    $is_error = true;
}

// Create a new database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    $status_message = "Connection failed: " . $conn->connect_error;
    $is_error = true;
} else {
    try {
        if ($leader_id_no) {
            // Updated SQL query to join login and arbolitos_groups tables
            // This links the URL parameter 'id_no' (from login table) to the leader_id in arbolitos_groups
            $sql_get_group = "
                SELECT 
                    ag.id, 
                    ag.group_name, 
                    COALESCE(ag.leader_name, 'No Leader Assigned') as leader_name,
                    ag.group_number
                FROM login lt
                LEFT JOIN arbolitos_groups ag ON lt.user_code = ag.leader_id
                WHERE lt.id_no = ?;
            ";

            $stmt_group = $conn->prepare($sql_get_group);
            $stmt_group->bind_param("s", $leader_id_no);
            $stmt_group->execute();
            $result_group = $stmt_group->get_result();
            $leader_group_info = $result_group->fetch_assoc();
            $stmt_group->close();

            // Check if a group was found for the leader
            if ($leader_group_info && $leader_group_info['group_number']) {
                $group_id = $leader_group_info['group_number'];
                $leader_name = $leader_group_info['leader_name'];
                $group_name = $leader_group_info['group_name'];

                // Initialize the data structure for this specific group
                $groups_contributions[$leader_name] = [
                    'group_name' => $group_name,
                    'members' => [],
                    'group_total_contributed' => 0,
                    'group_total_expected' => 0,
                ];

                // Arrays to hold data for the chart
                $member_names_for_chart = [];
                $member_contributions_for_chart = [];
                $member_colors_for_chart = [];

                // Now, fetch all members belonging to this specific group
                $sql_all_members = "
                    SELECT full_name as member_name, member_no
                    FROM arbolitos_members
                    WHERE group_id = ?
                    ORDER BY full_name
                ";
                $stmt_all_members = $conn->prepare($sql_all_members);
                $stmt_all_members->bind_param("i", $group_id);
                $stmt_all_members->execute();
                $result_all_members = $stmt_all_members->get_result();

                if ($result_all_members->num_rows > 0) {
                    while ($member_row = $result_all_members->fetch_assoc()) {
                        $member_name = $member_row['member_name'];

                        // Add the member and update the expected group total
                        $groups_contributions[$leader_name]['members'][$member_name] = [
                            'contributions' => [],
                            'total_contributed' => 0,
                            'pending_amount' => $expected_monthly_contribution,
                            'status' => 'Pending',
                        ];
                        $groups_contributions[$leader_name]['group_total_expected'] += $expected_monthly_contribution;
                    }
                }
                $stmt_all_members->close();

                // Fetch contributions for the members of this specific group for the current month
                $sql_contributions = "
                    SELECT
                        am.full_name as member_name,
                        c.amount
                    FROM contributions c
                    INNER JOIN login lt ON c.national_id = lt.id_no
                    INNER JOIN arbolitos_members am ON lt.user_code = am.member_no
                    WHERE am.group_id = ?
                    AND c.contribution_type = 'Advocacy(MCC)'
                    AND YEAR(c.transaction_time) = ?
                    AND MONTH(c.transaction_time) = ?
                    ORDER BY am.full_name
                ";

                $stmt = $conn->prepare($sql_contributions);
                $stmt->bind_param("iss", $group_id, $current_year, $current_month);
                $stmt->execute();
                $result_contributions = $stmt->get_result();

                if ($result_contributions->num_rows > 0) {
                    while ($row = $result_contributions->fetch_assoc()) {
                        $member_name = $row['member_name'];
                        $amount = $row['amount'];

                        // Update the member's total and pending amount
                        if (isset($groups_contributions[$leader_name]['members'][$member_name])) {
                            $groups_contributions[$leader_name]['members'][$member_name]['total_contributed'] += $amount;
                            $groups_contributions[$leader_name]['members'][$member_name]['pending_amount'] -= $amount;
                            $groups_contributions[$leader_name]['group_total_contributed'] += $amount;
                        }
                    }
                }
                $stmt->close();

                // After processing all contributions, update the status and prepare data for the chart
                foreach ($groups_contributions as $leader_name => &$group_data) {
                    foreach ($group_data['members'] as $member_name => &$member_data) {
                        if ($member_data['total_contributed'] >= $expected_monthly_contribution) {
                            $member_data['status'] = 'Completed';
                            $member_colors_for_chart[] = 'rgb(34, 197, 94)'; // Tailwind green-500
                        } else {
                            $member_colors_for_chart[] = 'rgb(239, 68, 68)'; // Tailwind red-500
                        }
                        $member_names_for_chart[] = $member_name;
                        $member_contributions_for_chart[] = $member_data['total_contributed'];
                    }
                    // Calculate group pending amount
                    $group_data['group_total_pending'] = $group_data['group_total_expected'] - $group_data['group_total_contributed'];
                }
            } else {
                $status_message = "No group found for the provided leader ID.";
            }
        }

    } catch (mysqli_sql_exception $exception) {
        $status_message = "Error fetching data: " . $exception->getMessage();
        $is_error = true;
    }
}
$conn->close();

// Encode data for use in JavaScript
$chart_member_names = json_encode($member_names_for_chart);
$chart_member_contributions = json_encode($member_contributions_for_chart);
$chart_member_colors = json_encode($member_colors_for_chart);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Group's Advocacy Contributions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .text-shadow {
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="">

    <div class="bg-white p-6 sm:p-8 md:p-12 rounded-xl shadow-lg w-full max-w-4xl mx-auto">
        
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-3xl font-bold text-gray-800 text-center sm:text-left w-full sm:w-auto">
                My Group's Contributions
            </h1>
            <span class="text-xl font-semibold text-gray-600 text-center sm:text-right">
                Month of <?php echo date('F Y'); ?>
            </span>
        </div>
        
        <?php if (!empty($status_message)): ?>
            <div class="p-4 rounded-lg <?php echo $is_error ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'; ?> mb-6">
                <?php echo htmlspecialchars($status_message); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($groups_contributions)): ?>
            <div class="text-center text-gray-500 italic p-8">No group or members found for this leader.</div>
        <?php else: ?>
            <?php foreach ($groups_contributions as $leader_name => $group_data): ?>
                
                <div class="bg-blue-600 text-white p-6 rounded-xl shadow-md mb-8">
                    <h2 class="text-2xl font-bold text-shadow mb-2"><?php echo htmlspecialchars($group_data['group_name']); ?></h2>
                    <p class="text-lg font-medium mb-4">Group Leader: <?php echo htmlspecialchars($leader_name); ?></p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                        <div class="bg-blue-700 p-4 rounded-lg">
                            <span class="block text-sm opacity-80">Total Expected</span>
                            <span class="block text-xl font-bold">Ksh <?php echo number_format($group_data['group_total_expected'], 2); ?></span>
                        </div>
                        <div class="bg-green-500 p-4 rounded-lg">
                            <span class="block text-sm opacity-80">Total Contributed</span>
                            <span class="block text-xl font-bold">Ksh <?php echo number_format(htmlspecialchars($group_data['group_total_contributed']), 2); ?></span>
                        </div>
                        <div class="bg-red-500 p-4 rounded-lg">
                            <span class="block text-sm opacity-80">Total Pending</span>
                            <span class="block text-xl font-bold">Ksh <?php echo number_format(max(0, htmlspecialchars($group_data['group_total_pending'])), 2); ?></span>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-6 rounded-xl shadow-md mb-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Contributions Overview</h3>
                    <canvas id="contributionsChart"></canvas>
                </div>

                <div class="space-y-4">
                    <h3 class="text-xl font-semibold text-gray-700">Member Status</h3>
                    <?php if (empty($group_data['members'])): ?>
                        <div class="text-center text-gray-500 italic p-8 bg-white rounded-lg shadow-sm">No members found in this group.</div>
                    <?php else: ?>
                        <?php foreach ($group_data['members'] as $member_name => $member_data): ?>
                            <div class="member-card bg-white rounded-lg shadow-sm border border-gray-200 p-4 flex flex-col sm:flex-row justify-between items-center">
                                <div class="flex-grow">
                                    <h4 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($member_name); ?></h4>
                                    <p class="text-sm text-gray-500">
                                        Contributed: <span class="font-medium">Ksh <?php echo number_format(htmlspecialchars($member_data['total_contributed']), 2); ?></span>
                                    </p>
                                </div>
                                <div class="mt-2 sm:mt-0">
                                    <span class="font-bold text-sm px-3 py-1 rounded-full 
                                        <?php echo ($member_data['status'] === 'Completed') ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <?php echo htmlspecialchars($member_data['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
        // Use PHP variables to pass data to JavaScript
        const memberNames = <?php echo $chart_member_names; ?>;
        const memberContributions = <?php echo $chart_member_contributions; ?>;
        const memberColors = <?php echo $chart_member_colors; ?>;

        // Configuration for the bar chart
        const chartData = {
            labels: memberNames,
            datasets: [{
                label: 'Contributions (Ksh)',
                data: memberContributions,
                backgroundColor: memberColors,
                borderColor: memberColors.map(color => color.replace('rgb', 'rgba').replace(')', ', 0.5)')),
                borderWidth: 1,
                borderRadius: 5,
            }, {
                label: 'Expected (Ksh)',
                data: Array(memberNames.length).fill(<?php echo $expected_monthly_contribution; ?>),
                type: 'line',
                borderColor: 'rgb(59, 130, 246)', // Tailwind blue-500
                borderWidth: 2,
                pointRadius: 0,
                fill: false,
                tension: 0.1,
            }]
        };

        const chartConfig = {
            type: 'bar',
            data: chartData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Amount (Ksh)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += 'Ksh ' + new Intl.NumberFormat('en-US', { minimumFractionDigits: 2 }).format(context.parsed.y);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        };

        // Render the chart
        window.onload = function() {
            const ctx = document.getElementById('contributionsChart').getContext('2d');
            new Chart(ctx, chartConfig);
        };
    </script>
</body>
</html>