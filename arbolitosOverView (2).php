<?php
session_start();
// Assuming 'header.php' exists and handles initial setup/includes.
include 'header.php';

// Initialize
$groups_contributions = [];
$group_donations = [];
$status_message = '';
$is_error = false;

// Config
// Removed $current_year and $current_month as the new logic is based on lifetime daily accrual.
$DAILY_RATE = 10; // Daily contribution rate in Ksh

// DB credentials
$servername = "localhost";
$username = "digita51_enock";
$password = "digita51_enock";
$dbname = "digita51_portal";

// Check session
if (!isset($_SESSION['id_no'])) {
    header('Location: index.php');
    exit;
}

$leader_id_no = $_SESSION['id_no'];

// Connect DB (using $con for the connection object)
$con = new mysqli($servername, $username, $password, $dbname);
if ($con->connect_error) {
    $status_message = "Connection failed: " . $con->connect_error;
    $is_error = true;
} else {
    try {
        // Fetch user’s group info and corresponding national_id (ID_NO) from login table
        $sql_get_group = "
            SELECT 
                ag.group_name,
                COALESCE(ag.leader_name, 'No Leader Assigned') AS leader_name,
                am.group_id AS group_number
            FROM login lt
            INNER JOIN arbolitos_members am ON lt.user_code = am.member_no
            LEFT JOIN arbolitos_groups ag ON am.group_id = ag.group_number
            WHERE lt.id_no = ?;
        ";

        $stmt_group = $con->prepare($sql_get_group);
        $stmt_group->bind_param("s", $leader_id_no);
        $stmt_group->execute();
        $result_group = $stmt_group->get_result();
        $leader_group_info = $result_group->fetch_assoc();
        $stmt_group->close();

        if ($leader_group_info && $leader_group_info['group_number']) {
            $group_id = $leader_group_info['group_number'];
            $leader_name = $leader_group_info['leader_name'];
            $group_name = $leader_group_info['group_name'];
            
            $groups_contributions[$leader_name] = [
                'group_name' => $group_name,
                'members' => [],
                'group_total_contributed' => 0, // Lifetime Total
                'group_total_expected' => 0,    // Lifetime Expected
            ];

            // Fetch group members along with their login ID_NO (national_id)
            $sql_all_members = "
                SELECT am.full_name AS member_name, lt.id_no AS member_id_no, am.member_no
                FROM arbolitos_members am
                INNER JOIN login lt ON am.member_no = lt.user_code
                WHERE am.group_id = ?
                ORDER BY am.full_name
            ";
            $stmt_all_members = $con->prepare($sql_all_members);
            $stmt_all_members->bind_param("i", $group_id);
            $stmt_all_members->execute();
            $result_all_members = $stmt_all_members->get_result();
            
            // Loop through each member to calculate their daily contribution status (MCC logic)
            while ($member_row = $result_all_members->fetch_assoc()) {
                $member_name = $member_row['member_name'];
                $member_id_no = $member_row['member_id_no'];
                $member = []; // Temp array to hold member data

                // 1. Fetch total lifetime contributions and first transaction date for 'Advocacy(MCC)'
                $sql_advocacy_check = "
                    SELECT
                        MIN(transaction_time) AS first_date,
                        SUM(amount) AS total_paid
                    FROM contributions
                    WHERE national_id = ? AND contribution_type = 'Advocacy(MCC)'
                ";
                
                if ($stmt_advocacy = $con->prepare($sql_advocacy_check)) {
                    $stmt_advocacy->bind_param("s", $member_id_no);
                    $stmt_advocacy->execute();
                    $data = $stmt_advocacy->get_result()->fetch_assoc();
                    $stmt_advocacy->close();

                    $first_transaction_date = $data['first_date'] ?? null;
                    $total_paid = (float)($data['total_paid'] ?? 0);
                    
                    $expected_amount = 0;
                    $member['days_in_arrears'] = 0;
                    $member['days_ahead'] = 0;

                    if (!empty($first_transaction_date)) {
                        try {
                            $first_date_obj = new DateTime($first_transaction_date);
                            $today_obj = new DateTime();

                            // Calculate expected amount (Days Active)
                            $interval_active = $first_date_obj->diff($today_obj);
                            // Days active including the first day and today
                            $days_active = $interval_active->days + 1; 
                            $expected_amount = $days_active * $DAILY_RATE;
                            
                            // 2. Determine MCC status
                            if ($total_paid >= $expected_amount) {
                                $member['mcc_status'] = 'Subscribed';
                                $member['surplus'] = $total_paid - $expected_amount;
                                $member['debt'] = 0;
                                // Calculate Days Ahead
                                $member['days_ahead'] = (int)floor($member['surplus'] / $DAILY_RATE); 
                            } else {
                                $member['mcc_status'] = 'Unsubscribed';
                                $member['debt'] = $expected_amount - $total_paid;
                                $member['surplus'] = 0;
                                // Calculation for debt in days: round up for full coverage
                                $member['days_in_arrears'] = (int)ceil($member['debt'] / $DAILY_RATE); 
                            }
                            
                        } catch (Exception $e) {
                            $member['mcc_status'] = 'error';
                            error_log("Date calculation error: " . $e->getMessage());
                        }
                    } else {
                        // Inactive / No Advocacy contributions ever made
                        $member['mcc_status'] = 'Inactive';
                        $member['debt'] = 0;
                        $member['surplus'] = 0;
                    }
                    
                    $member['total_contribution'] = $total_paid;
                    $member['expected_amount'] = $expected_amount;
                    
                    // Add to group totals
                    $groups_contributions[$leader_name]['group_total_expected'] += $member['expected_amount'];
                    $groups_contributions[$leader_name]['group_total_contributed'] += $member['total_contribution'];
                    
                    // 3. APPLY COLOR AND DISPLAY LOGIC BASED ON STATUS
                    $status_display_color = '';
                    $days_display_value = '';
                    $status_text = '';
                    
                    switch ($member['mcc_status']) {
                        case 'Subscribed':
                            $status_text = 'Subscribed';
                            $status_display_color = 'bg-green-100 text-green-700'; // Green
                            $days_display_value = $member['days_ahead'] . ' days ahead';
                            break;
                        case 'Unsubscribed':
                            $status_text = 'Unsubscribed';
                            $status_display_color = 'bg-red-100 text-red-700'; // Red
                            $days_display_value = $member['days_in_arrears'] . ' days arrears';
                            break;
                        case 'Inactive':
                            $status_text = 'Inactive';
                            $status_display_color = 'bg-yellow-100 text-yellow-800'; // Yellow
                            $days_display_value = ''; // No days displayed
                            break;
                        default:
                            $status_text = 'Error/Unknown';
                            $status_display_color = 'bg-red-100 text-red-700';
                            $days_display_value = '';
                    }

                    // Store the processed member data for the display loop
                    $groups_contributions[$leader_name]['members'][$member_name] = [
                        'total_contributed' => $member['total_contribution'],
                        'expected_contribution' => $member['expected_amount'],
                        'debt' => $member['debt'],
                        'surplus' => $member['surplus'],
                        'status' => $status_text,
                        'status_color' => $status_display_color,
                        'days_display' => $days_display_value,
                    ];
                    
                }
            } // End of member loop
            $stmt_all_members->close();

            // Finalize group total pending using lifetime values
            $groups_contributions[$leader_name]['group_total_pending'] = max(0, $groups_contributions[$leader_name]['group_total_expected'] - $groups_contributions[$leader_name]['group_total_contributed']);


            // --- Fetch Group Donations (This logic remains the same) ---

            $sql_donations = "
                SELECT 
                    d.donation_title,
                    d.fullname,
                    d.amount
                FROM donations d
                INNER JOIN login lt ON d.national_id = lt.id_no
                INNER JOIN arbolitos_members am ON lt.user_code = am.member_no
                WHERE am.group_id = ?
                ORDER BY d.donation_title, d.fullname
            ";
            $stmt_don = $con->prepare($sql_donations);
            $stmt_don->bind_param("i", $group_id);
            $stmt_don->execute();
            $result_don = $stmt_don->get_result();

            while ($don_row = $result_don->fetch_assoc()) {
                $title = $don_row['donation_title'] ?: 'Untitled Donation';
                $group_donations[$title][] = [
                    'fullname' => $don_row['fullname'],
                    'amount' => $don_row['amount']
                ];
            }
            $stmt_don->close();
        } else {
            $status_message = "No group found for this leader.";
        }
    } catch (mysqli_sql_exception $exception) {
        $status_message = "Error fetching data: " . $exception->getMessage();
        $is_error = true;
    }
}
$con->close(); 
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Group's Contributions</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-6xl mx-auto my-8">

    <div class="flex flex-col sm:flex-row justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800" style="text-transform: uppercase;">My Group's Contributions</h1>
        <span class="text-lg text-gray-600">Status based on daily rate (Ksh <?php echo $DAILY_RATE; ?>/day)</span>
    </div>

    <?php if (!empty($status_message)): ?>
        <div class="p-4 rounded-lg <?php echo $is_error ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700'; ?> mb-6">
            <?php echo htmlspecialchars($status_message); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($groups_contributions)): ?>
        <div class="text-center text-gray-500 italic p-8">No group or members found.</div>
    <?php else: ?>
        <?php foreach ($groups_contributions as $leader_name => $group_data): ?>
            <div class="bg-blue-600 text-white p-6 rounded-xl mb-6">
                <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($group_data['group_name']); ?></h2>
                <p>Group Leader: <?php echo htmlspecialchars($leader_name); ?></p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-4 text-center">
                    <div class="bg-blue-700 p-4 rounded-lg">
                        <p class="text-sm opacity-80">Total Expected (Lifetime)</p>
                        <p class="text-xl font-bold">Ksh <?php echo number_format($group_data['group_total_expected'], 2); ?></p>
                    </div>
                    <div class="bg-green-500 p-4 rounded-lg">
                        <p class="text-sm opacity-80">Total Contributed (Lifetime)</p>
                        <p class="text-xl font-bold">Ksh <?php echo number_format($group_data['group_total_contributed'], 2); ?></p>
                    </div>
                    <div class="bg-red-500 p-4 rounded-lg">
                        <p class="text-sm opacity-80">Group Total Arrears</p>
                        <p class="text-xl font-bold">Ksh <?php echo number_format($group_data['group_total_pending'], 2); ?></p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <div class="bg-gray-50 p-6 rounded-xl shadow-md">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Member MCC Status (Daily Accrual)</h3>
                    <?php foreach ($group_data['members'] as $member_name => $member_data): ?>
                        <div class="bg-white border border-gray-200 p-4 rounded-lg flex justify-between items-center mb-3">
                            <div>
                                <h4 class="text-lg font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($member_name); ?>
                                </h4>
                                <p class="text-sm text-gray-500">
                                    Expected: <span class="font-medium">Ksh <?php echo number_format($member_data['expected_contribution'], 2); ?></span>
                                    | Paid: <span class="font-medium text-green-600">Ksh <?php echo number_format($member_data['total_contributed'], 2); ?></span>
                                    
                                    <?php if ($member_data['days_display']): ?>
                                        <br>
                                        <span class="font-bold text-sm">
                                            <?php echo htmlspecialchars($member_data['days_display']); ?>
                                        </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <span class="px-3 py-1 rounded-full text-sm font-semibold 
                                <?php echo htmlspecialchars($member_data['status_color']); ?>">
                                <?php echo htmlspecialchars($member_data['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="bg-gray-50 p-6 rounded-xl shadow-md">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">Group Donations</h3>
                    <?php if (!empty($group_donations)): ?>
                        <div class="space-y-4">
                            <?php foreach ($group_donations as $title => $donors): ?>
                                <?php 
                                    $total_amount = array_sum(array_column($donors, 'amount'));
                                    $donor_count = count($donors);
                                ?>
                                <div class="border border-gray-200 bg-white rounded-lg shadow-sm">
                                    <button onclick="toggleDonation('<?php echo md5($title); ?>')" 
                                        class="w-full flex justify-between items-center px-6 py-4 text-left hover:bg-gray-50 transition">
                                        <div>
                                            <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($title); ?></h4>
                                            <p class="text-sm text-gray-500"><?php echo $donor_count; ?> donor(s)</p>
                                        </div>
                                        <span class="font-bold text-blue-600">Ksh <?php echo number_format($total_amount, 2); ?></span>
                                    </button>
                                    <div id="donation-<?php echo md5($title); ?>" class="hidden border-t border-gray-200 bg-gray-50 px-6 py-4">
                                        <?php foreach ($donors as $donor): ?>
                                            <div class="flex justify-between py-2 border-b last:border-0 text-gray-700">
                                                <span><?php echo htmlspecialchars($donor['fullname']); ?></span>
                                                <span>Ksh <?php echo number_format($donor['amount'], 2); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-gray-500 italic">No donations recorded for this group yet.</p>
                    <?php endif; ?>
                </div>

            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function toggleDonation(id) {
    const section = document.getElementById('donation-' + id);
    section.classList.toggle('hidden');
}
</script>
</body>
</html>