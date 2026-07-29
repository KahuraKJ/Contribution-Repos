<?php
include 'adminNavbar.php';
// Initialize an array to hold all the grouped contribution data
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

// Create a new database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    $status_message = "Connection failed: " . $conn->connect_error;
    $is_error = true;
} else {
    try {
        // First, fetch all members to check against their contributions
        // Use COALESCE to provide a default value for leader_name if it's NULL
        $sql_all_members = "
            SELECT 
                ag.group_name,
                COALESCE(ag.leader_name, 'No Leader Assigned') as leader_name,
                am.full_name as member_name,
                ag.id AS group_id,
                am.member_no,
                am.id AS member_id
            FROM arbolitos_members am
            INNER JOIN arbolitos_groups ag ON am.group_id = ag.id
            ORDER BY ag.group_name, am.full_name
        ";
        $result_all_members = $conn->query($sql_all_members);

        if ($result_all_members->num_rows > 0) {
            while ($member_row = $result_all_members->fetch_assoc()) {
                $leader_name = $member_row['leader_name'];
                $group_name = $member_row['group_name'];
                $member_name = $member_row['member_name'];

                // Initialize group data if it doesn't exist
                if (!isset($groups_contributions[$leader_name])) {
                    $groups_contributions[$leader_name] = [
                        'group_name' => $group_name,
                        'members' => [],
                        'group_total_contributed' => 0,
                        'group_total_expected' => 0,
                    ];
                }

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

        // Now, fetch contributions for the current month
        // Use COALESCE here as well to ensure consistency
        $sql_contributions = "
            SELECT
                COALESCE(ag.leader_name, 'No Leader Assigned') as leader_name,
                am.full_name as member_name,
                c.amount
            FROM contributions c
            INNER JOIN login lt ON c.national_id = lt.id_no
            INNER JOIN arbolitos_members am ON lt.user_code = am.member_no
            INNER JOIN arbolitos_groups ag ON am.group_id = ag.id
            WHERE c.contribution_type = 'Advocacy(MCC)'
              AND YEAR(c.transaction_time) = ?
              AND MONTH(c.transaction_time) = ?
            ORDER BY ag.group_name, am.full_name
        ";

        $stmt = $conn->prepare($sql_contributions);
        $stmt->bind_param("ss", $current_year, $current_month);
        $stmt->execute();
        $result_contributions = $stmt->get_result();

        if ($result_contributions->num_rows > 0) {
            while ($row = $result_contributions->fetch_assoc()) {
                $leader_name = $row['leader_name'];
                $member_name = $row['member_name'];
                $amount = $row['amount'];

                // Update the member's total and pending amount
                if (isset($groups_contributions[$leader_name]['members'][$member_name])) {
                    $groups_contributions[$leader_name]['members'][$member_name]['total_contributed'] += $amount;
                    $groups_contributions[$leader_name]['members'][$member_name]['pending_amount'] -= $amount;
                    
                    // Also update the group's total contributed amount
                    $groups_contributions[$leader_name]['group_total_contributed'] += $amount;
                }
            }
        }
        $stmt->close();
        
        // After processing all contributions, update the status for each member
        foreach ($groups_contributions as $leader_name => &$group_data) {
            foreach ($group_data['members'] as $member_name => &$member_data) {
                if ($member_data['total_contributed'] >= $expected_monthly_contribution) {
                    $member_data['status'] = 'Completed';
                }
            }
            // Calculate group pending amount after processing all members in the group
            $group_data['group_total_pending'] = $group_data['group_total_expected'] - $group_data['group_total_contributed'];
        }

    } catch (mysqli_sql_exception $exception) {
        $status_message = "Error fetching data: " . $exception->getMessage();
        $is_error = true;
    }
}
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advocacy Contributions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .collapse-content {
            display: none;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
    </style>
</head>
<body class="bg-gray-100 p-6 sm:p-10 md:p-12">

    <div class="bg-white p-6 sm:p-8 md:p-12 rounded-xl shadow-lg w-full max-w-4xl mx-auto">
        
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 text-center sm:text-left w-full sm:w-auto">
                Advocacy Contributions for <?php echo date('F Y'); ?>
            </h1>
        </div>

        <div class="mb-6">
            <input type="text" id="searchInput" placeholder="Search by group leader, group name, or member name..."
                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-150 ease-in-out">
        </div>
        
        <?php if (!empty($status_message)): ?>
            <div class="p-4 rounded-lg <?php echo $is_error ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'; ?> mb-6">
                <?php echo htmlspecialchars($status_message); ?>
            </div>
        <?php endif; ?>

        <div id="contributionsContainer" class="space-y-8">
            <?php if (empty($groups_contributions)): ?>
                <div class="text-center text-gray-500 italic p-8">No members or contributions found for this month.</div>
            <?php else: ?>
                <?php foreach ($groups_contributions as $leader_name => $group_data): ?>
                    <div class="group-card bg-gray-50 rounded-xl shadow-sm border border-gray-200 p-6"
                         data-leader="<?php echo htmlspecialchars(strtolower($leader_name)); ?>"
                         data-group="<?php echo htmlspecialchars(strtolower($group_data['group_name'])); ?>">
                        
                        <div class="cursor-pointer group-header" onclick="toggleCollapse(this)">
                            <h2 class="text-xl font-bold text-blue-700 mb-2" data-search-term>
                                Group Leader: <?php echo htmlspecialchars($leader_name); ?>
                            </h2>
                            <p class="text-gray-600 mb-4 flex justify-between items-center" data-search-term>
                                <span>Group Name: <span class="font-medium"><?php echo htmlspecialchars($group_data['group_name']); ?></span></span>
                                <span>Total Contributed: <span class="font-medium">Ksh <?php echo number_format(htmlspecialchars($group_data['group_total_contributed']), 2); ?></span></span>
                                <span>Total Pending: <span class="font-medium">Ksh <?php echo number_format(max(0, htmlspecialchars($group_data['group_total_pending'])), 2); ?></span></span>
                            </p>
                        </div>

                        <div class="collapse-content space-y-4">
                            <?php foreach ($group_data['members'] as $member_name => $member_data): ?>
                                <div class="member-card bg-white rounded-lg shadow-sm border border-gray-200 p-4" data-member="<?php echo htmlspecialchars(strtolower($member_name)); ?>">
                                    <h3 class="text-lg font-semibold text-gray-800 mb-2" data-search-term><?php echo htmlspecialchars($member_name); ?></h3>
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between items-center text-gray-600">
                                            <span>Expected: <span class="font-medium">Ksh <?php echo number_format($expected_monthly_contribution, 2); ?></span></span>
                                            <span>Contributed: <span class="font-medium">Ksh <?php echo number_format(htmlspecialchars($member_data['total_contributed']), 2); ?></span></span>
                                            <span>Pending: <span class="font-medium">Ksh <?php echo number_format(max(0, htmlspecialchars($member_data['pending_amount'])), 2); ?></span></span>
                                        </div>
                                        <div class="mt-2">
                                            <span class="font-medium">Status: </span>
                                            <span class="font-bold <?php echo ($member_data['status'] === 'Completed') ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo htmlspecialchars($member_data['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleCollapse(element) {
            const content = element.nextElementSibling;
            if (content.style.display === "block" || content.style.display === "") {
                content.style.display = "none";
            } else {
                content.style.display = "block";
            }
        }

        document.getElementById('searchInput').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase();
            let groupCards = document.querySelectorAll('.group-card');
            
            groupCards.forEach(function(groupCard) {
                let leaderName = groupCard.getAttribute('data-leader');
                let groupName = groupCard.getAttribute('data-group');
                let hasVisibleMember = false;
                
                let memberCards = groupCard.querySelectorAll('.member-card');
                
                // Show/hide members based on the filter
                memberCards.forEach(function(memberCard) {
                    let memberName = memberCard.getAttribute('data-member');
                    
                    if (memberName.includes(filter) || leaderName.includes(filter) || groupName.includes(filter)) {
                        memberCard.style.display = 'block';
                        hasVisibleMember = true;
                    } else {
                        memberCard.style.display = 'none';
                    }
                });

                // Show/hide the whole group card
                if (hasVisibleMember || leaderName.includes(filter) || groupName.includes(filter)) {
                    groupCard.style.display = 'block';
                } else {
                    groupCard.style.display = 'none';
                }

                // If the group card is visible and the search filter matches a member,
                // ensure the collapse content is expanded.
                if (groupCard.style.display === 'block' && (leaderName.includes(filter) || groupName.includes(filter) || hasVisibleMember)) {
                    let collapseContent = groupCard.querySelector('.collapse-content');
                    if (collapseContent) {
                         collapseContent.style.display = 'block';
                    }
                }
            });
        });
    </script>
</body>
</html>