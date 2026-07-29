<?php
session_start();
include 'header.php';
include 'connect.php'; // Assumes this defines $con (MySQLi connection)

// Function remains the same
function toSentenceCase($str) {
    if (empty($str)) {
        return '';
    }
    return ucwords(strtolower((string)$str));
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

// --- SACCO LOGIC START ---

// Step 2: Get sacco_id and sacco_no from sacco_members
$stmt = $con->prepare("SELECT sacco_id, sacco_no FROM sacco_members WHERE member_no = ? LIMIT 1");
$stmt->bind_param("s", $user_code);
$stmt->execute();
$result = $stmt->get_result();
$sacco_member = $result->fetch_assoc();
$stmt->close();

if (!$sacco_member) {
    die("Member not found in a SACCO.");
}
$sacco_id = $sacco_member['sacco_id'];
$sacco_member_no = $sacco_member['sacco_no'] ?? ''; 

// Step 3: Get sacco_name, Chairman info, and Leader's member_no from sacco table
$stmt = $con->prepare("SELECT sacco_name, chairman, chairman_phone_number, member_no AS leader_member_no FROM sacco WHERE sacco_number = ? LIMIT 1");
$stmt->bind_param("i", $sacco_id);
$stmt->execute();
$result = $stmt->get_result();
$sacco = $result->fetch_assoc();
$stmt->close();

$sacco_name = $sacco['sacco_name'] ?? 'Unknown SACCO';
$leader_member_no = $sacco['leader_member_no'] ?? ''; 
$chairman_name = $sacco['chairman'] ?? 'N/A';
$chairman_phone = $sacco['chairman_phone_number'] ?? 'N/A';

// Step 4: Fetch all members in the same SACCO
// *** CRITICAL FIX: Selecting full_name and phone directly from sacco_members (sm) ***
$sql = "SELECT
            sm.member_no,
            sm.sacco_no,
            sm.full_name,
            sm.phone,
            l.id_no /* Kept for troubleshooting the login link */
        FROM sacco_members sm
        LEFT JOIN login l ON sm.member_no = l.user_code
        WHERE sm.sacco_id = ?
        ORDER BY sm.full_name ASC";

$stmt = $con->prepare($sql);
$stmt->bind_param("i", $sacco_id);
$stmt->execute();
$result = $stmt->get_result();
$members = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// --- SACCO LOGIC END ---
?>

<!DOCTYPE html>
<html>
<head>
    <title>SACCO Members</title>
    <style>
        /* CSS remains the same */
        body { font-family: 'Poppins', Arial, sans-serif; background-color: #eef2f7; }
        .main-form-container { 
            max-width: 900px; 
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
            margin-bottom: 15px; 
        }
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
        }  text-align: left; 
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
        .sacco-details {
            margin-bottom: 25px;
            padding: 10px;
            background-color: #fff9f5;
            border: 1px solid #f25a2c;
            border-radius: 8px;
        }
        /* Highlight missing data for visibility */
        .missing-data {
            color: #cc0000;
            font-style: italic;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="main-form-container">

    <h2 style="text-transform: uppercase;">SACCO Members - <?php echo htmlspecialchars($sacco_name); ?> (ID #<?php echo htmlspecialchars($sacco_id); ?>)</h2>
    <p>Your Member No: <b><?php echo htmlspecialchars($sacco_member_no ?? ''); ?></b></p> 

    <div class="sacco-details">
        <p>
            <span style="font-weight: bold; color: #f25a2c;">Chairman:</span> 
            <?php echo htmlspecialchars(toSentenceCase($chairman_name)); ?>
        </p>
        <p>
            <span style="font-weight: bold; color: #f25a2c;">Chairman Contact:</span> 
            <?php echo htmlspecialchars($chairman_phone); ?>
        </p>
    </div>
    <hr>
    
    <table class="member-list-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Sacco NO:</th>
                <th>Full Name</th>
                <th>Contact Phone</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($members)): ?>
                <?php $counter = 1; ?>
                <?php foreach ($members as $m): 
                    $is_leader = ($m['member_no'] == $leader_member_no);
                    $row_class = $is_leader ? 'leader-row' : '';
                    $display_name = htmlspecialchars(toSentenceCase($m['full_name'] ?? ''));
                    if (empty($display_name)) $display_name = '<span class="missing-data">NAME MISSING</span>';

                    $display_phone = htmlspecialchars($m['phone'] ?? '');
                    // Assuming a phone number of '0' is also invalid/missing based on your sample data
                    if (empty($display_phone) || $display_phone === '0') $display_phone = '<span class="missing-data">PHONE MISSING</span>';

                    $display_id_no = htmlspecialchars($m['id_no'] ?? '');
                    if (empty($display_id_no)) $display_id_no = '<span class="missing-data">LOGIN FAILED</span>';
                ?>
                    <tr class="<?php echo $row_class; ?>">
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($m['sacco_no'] ?? ''); ?></td>
                        <td>
                            <?php echo $display_name; ?>
                            <?php if ($is_leader): ?>
                                <span class="leader-badge">SACCO LEADER</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $display_phone; ?></td> 
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center;">No members found in this SACCO.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>