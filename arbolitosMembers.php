<?php
session_start();
include 'header.php';
include 'connect.php'; // Assumes this defines $con (MySQLi connection)

function toSentenceCase($str) {
    if (empty($str)) {
        return '';
    }
    return ucwords(strtolower($str));
}
if (!isset($_SESSION['id_no'])) {
    header('Location: index.php');
    exit;
}

$id_no = $_SESSION['id_no'];

// Step 1: Get user_code from login
$stmt = $con->prepare("SELECT user_code FROM login WHERE id_no = ? LIMIT 1");
$stmt->bind_param("s", $id_no);
$stmt->execute();
$result = $stmt->get_result();
$login = $result->fetch_assoc();
$stmt->close();

if (!$login) {
    die("User not found in login.");
}
$user_code = $login['user_code'];

// Step 2: Get group_id from arbolitos_members
$stmt = $con->prepare("SELECT group_id FROM arbolitos_members WHERE member_no = ? LIMIT 1");
$stmt->bind_param("s", $user_code);
$stmt->execute();
$result = $stmt->get_result();
$member = $result->fetch_assoc();
$stmt->close();

if (!$member) {
    die("Member not found in arbolitos_members.");
}
$group_id = $member['group_id'];

// Step 3: Get group_name AND leader_id
$stmt = $con->prepare("SELECT group_name, leader_id FROM arbolitos_groups WHERE group_number = ? LIMIT 1");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();
$group = $result->fetch_assoc();
$stmt->close();

$group_name = $group['group_name'] ?? 'Unknown Group';
// ASSUMPTION: 'leader_id' in arbolitos_groups stores the 'member_no' (user_code) of the group leader
$leader_member_no = $group['leader_id'] ?? null; 

// Step 4: Fetch all members in the same group
$stmt = $con->prepare("SELECT m.member_no, m.full_name, m.phone
                        FROM arbolitos_members m
                        WHERE m.group_id = ?
                        ORDER BY m.full_name ASC");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();
$members = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Group Members</title>
    <style>
        /* General Styles */
        body { font-family: 'Poppins', Arial, sans-serif;  background-color: #eef2f7; }
        .main-form-container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(32, 33, 85, 0.1); 
        }
        h2 { 
            color: #020329; 
            border-bottom: 3px solid #f25a2c; 
            padding-bottom: 10px; 
            margin-bottom: 25px; 
        }

        /* Table Styles */
        .member-list-table { 
            border-collapse: collapse; 
            width: 100%; 
            margin-top: 20px; 
            font-size: 0.95em;
        }
        .member-list-table th, .member-list-table td { 
            border: 1px solid #ddd; 
            padding: 12px 15px; 
            text-align: left; 
        }
        .member-list-table th { 
            background: #f54b07; 
            color: white; 
            font-weight: 600;
        }
        .member-list-table tr:nth-child(even) { 
            background: #f9f9f9; 
        }
        .member-list-table tr:hover:not(.leader-row) { 
            background-color: #f0f0f0; 
        }

        /* Leader Highlight */
        .leader-row {
            background-color: #ffd8a1; /* Light orange/yellow background */
            font-weight: bold;
            border: 2px solid #f25a2c;
        }
        .leader-badge {
            display: inline-block;
            background-color: #f25a2c;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-left: 10px;
        }
    </style>
</head>
<body>

<div class="main-form-container">

    <h2 style="text-transform: uppercase;">Group Members - <?php echo htmlspecialchars($group_name); ?> (Group #<?php echo htmlspecialchars($group_id); ?>)</h2>

    <table class="member-list-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Member No</th>
                <th>Full Name</th>
                <th>Phone</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($members)): ?>
                <?php $counter = 1; ?>
                <?php foreach ($members as $m): 
                    $is_leader = ($m['member_no'] == $leader_member_no);
                    $row_class = $is_leader ? 'leader-row' : '';
                ?>
                    <tr class="<?php echo $row_class; ?>">
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($m['member_no']); ?></td>
                        <td>
                            <?php echo htmlspecialchars(toSentenceCase($m['full_name'])); ?>
                            <?php if ($is_leader): ?>
                                <span class="leader-badge">GROUP LEADER</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($m['phone']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4" style="text-align: center;">No members found in this group.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>