<?php
session_start();

// Ensure connect.php initializes $con (mysqli object)
include 'connect.php';

// Check for database connection success
if (!isset($con) || $con->connect_error) {
    die("Connection failed: " . ($con->connect_error ?? "Database object not set."));
}

// --- 2. SESSION CHECK ---
if (!isset($_SESSION['id_no'])) {
    die("Error: User ID is missing from the session. Please log in.");
}
$session_id_no = $_SESSION['id_no'];

$sql = "
    SELECT
        M.id,
        L.user_code AS member_no,
        M.full_name,
        M.phone,
        COALESCE(S.sacco_name, AG.group_name) AS organization_name,
        M.organization_type -- Key to determining membership type
    FROM
        login L
    INNER JOIN
        (
            -- 1. Combine SACCO members
            SELECT sm.id, sm.member_no, sm.full_name, sm.phone, sm.sacco_id AS org_id, 'SACCO' AS organization_type
            FROM sacco_members sm
            
            UNION ALL
            
            -- 2. Combine ARBOLITOS members
            SELECT am.id, am.member_no, am.full_name, am.phone, am.group_id AS org_id, 'ARBOLITOS' AS organization_type
            FROM arbolitos_members am
        ) M ON L.user_code = M.member_no
    LEFT JOIN
        sacco S ON M.organization_type = 'SACCO' AND S.sacco_number = M.org_id
    LEFT JOIN
        arbolitos_groups AG ON M.organization_type = 'ARBOLITOS' AND AG.group_number = M.org_id
    WHERE
        L.id_no = ?
";

// --- 5. PREPARE & EXECUTE STATEMENT ---
$sacco_details = null;
$arbolitos_details = null;
$error_message = null;

$stmt = $con->prepare($sql);

if ($stmt) {
    // 's' means the parameter is bound as a string
    $stmt->bind_param("s", $session_id_no);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Separate the results into their respective organization types
        while($row = $result->fetch_assoc()) {
            if ($row['organization_type'] === 'SACCO') {
                $sacco_details = $row;
            } elseif ($row['organization_type'] === 'ARBOLITOS') {
                $arbolitos_details = $row;
            }
        }
    }

    $stmt->close();
} else {
    // Handle SQL preparation error
    $error_message = "SQL Prepare failed: " . $con->error;
}
$con->close();

// Status Flags for easy checking in HTML
$is_sacco_member = (bool)$sacco_details;
$is_arbolitos_member = (bool)$arbolitos_details;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Membership Status Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; }
        .card-container { display: flex; gap: 20px; justify-content: center; margin-top: 30px; }
        .member-card { 
            background-color: white; 
            border-radius: 8px; 
            box-shadow: 0 4px 8px rgba(0,0,0,0.1); 
            padding: 20px; 
            width: 350px;
            min-height: 200px;
            border-left: 5px solid;
        }
        .sacco { border-left-color: #007bff; }
        .arbolitos { border-left-color: #28a745; }
        .card-title { font-size: 1.5em; margin-bottom: 10px; font-weight: bold; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-missing { color: #dc3545; font-weight: bold; }
        .action-link { 
            display: inline-block; 
            margin-top: 15px; 
            padding: 8px 15px; 
            border-radius: 4px; 
            text-decoration: none; 
            font-weight: bold; 
            text-align: center;
        }
        .update-sacco { background-color: #007bff; color: white; }
        .update-arbolitos { background-color: #28a745; color: white; }
        .detail-row { margin-bottom: 5px; font-size: 0.9em; }
        .detail-label { font-weight: bold; width: 120px; display: inline-block; }
        /* NEW: Style for the CONFIRMED button link */
        .confirm-button {
            background-color: #fa500c; /* Matching SACCO blue */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
            text-decoration: none; /* Remove link underline */
            display: inline-block;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<center>
    <h1>👤 Membership Status Check</h1>
    <p>Logged in as ID NO: <strong><?php echo htmlspecialchars($session_id_no); ?></strong> (Member NO: 
        <strong><?php echo htmlspecialchars($sacco_details['member_no'] ?? $arbolitos_details['member_no'] ?? 'N/A'); ?></strong>)</p>

    <?php if (isset($error_message)): ?>
        <p style="color: red; font-weight: bold;"><?php echo $error_message; ?></p>
    <?php endif; ?>
</center>
    <div class="card-container">

        <div class="member-card sacco">
            <div class="card-title">SACCO Membership</div>
            <hr>
            <?php if ($is_sacco_member): ?>
                <p class="status-ok">✅ **Status: Registered**</p>
                <div class="detail-row"><span class="detail-label">Member Name:</span> <?php echo htmlspecialchars($sacco_details['full_name']); ?></div>
                <div class="detail-row"><span class="detail-label">Sacco:</span> <?php echo htmlspecialchars($sacco_details['organization_name']); ?></div>
                <div class="detail-row"><span class="detail-label">Phone:</span> <?php echo htmlspecialchars($sacco_details['phone']); ?></div>
                <a href="update_sacco.php?id=<?php echo htmlspecialchars($sacco_details['id']); ?>" class="action-link update-sacco">Update SACCO Details</a>
            <?php else: ?>
                <p class="status-missing">❌ **Status: Not Registered**</p>
                <p>You do not currently have a record in the SACCO members database.</p>
                <a href="register_sacco.php" class="action-link update-sacco">Register for SACCO</a>
            <?php endif; ?>
        </div>

        <div class="member-card arbolitos">
            <div class="card-title">Arbolitos Membership</div>
            <hr>
            <?php if ($is_arbolitos_member): ?>
                <p class="status-ok">✅ **Status: Registered**</p>
                <div class="detail-row"><span class="detail-label">Member Name:</span> <?php echo htmlspecialchars($arbolitos_details['full_name']); ?></div>
                <div class="detail-row"><span class="detail-label">Arbolitos:</span> <?php echo htmlspecialchars($arbolitos_details['organization_name']); ?></div>
                <div class="detail-row"><span class="detail-label">Phone:</span> <?php echo htmlspecialchars($arbolitos_details['phone']); ?></div>
                <a href="update_arbolitos.php?id=<?php echo htmlspecialchars($arbolitos_details['id']); ?>" class="action-link update-arbolitos">Update Arbolitos Details</a>
            <?php else: ?>
                <p class="status-missing">❌ **Status: Not Registered**</p>
                <p>You do not currently have a record in the Arbolitos members database.</p>
                <a href="register_arbolitos.php" class="action-link update-arbolitos">Register for Arbolitos</a>
            <?php endif; ?>
        </div>

    </div>
    
    <?php if (!$is_sacco_member && !$is_arbolitos_member): ?>
        <p style="text-align: center; margin-top: 30px; font-weight: bold;">
            ⚠️ No membership records found for this account ID in either SACCO or Arbolitos.
        </p>
    <?php endif; ?>
<center>
    <a href="userdashboard.php" class="confirm-button">Confirmed</a>
</center>
</body>
</html>