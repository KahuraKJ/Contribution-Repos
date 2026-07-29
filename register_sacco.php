<?php
session_start();

// Include DB connection
include 'connect.php'; 

if (!isset($con) || $con->connect_error) {
    die("Connection failed: " . ($con->connect_error ?? "Database object not set."));
}

// 1. Check login session
if (!isset($_SESSION['id_no'])) {
    header('Location: login.php');
    exit();
}

$id_no = $_SESSION['id_no'];

$all_saccos = [];
$message = '';
$error = '';

/* -------------------------------
   2. Fetch ALL SACCO list for dropdown
-------------------------------- */
$sql_fetch_saccos = "SELECT sacco_number, sacco_name FROM sacco ORDER BY sacco_name";
$result_saccos = $con->query($sql_fetch_saccos);

if ($result_saccos) {
    while ($row = $result_saccos->fetch_assoc()) {
        $all_saccos[] = $row;
    }
    $result_saccos->free();
} else {
    $error = "Error fetching SACCO list: " . $con->error;
}

/* -------------------------------
   3. Auto-Fetch User Info
-------------------------------- */
$auto_full_name = "";
$auto_phone = "";
$auto_member_no = "";

$sql_user = "
    SELECT 
        p.full_name,
        c.contact_number,
        l.user_code
    FROM login l
    LEFT JOIN personal p ON p.id_no = l.id_no
    LEFT JOIN contact_address c ON c.id_no = l.id_no
    WHERE l.id_no = ?
";

$stmt_user = $con->prepare($sql_user);
if ($stmt_user) {
    $stmt_user->bind_param("s", $id_no);
    $stmt_user->execute();
    $result = $stmt_user->get_result();

    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $auto_full_name = $user_data['full_name'] ?? '';
        $auto_phone = $user_data['contact_number'] ?? '';
        $auto_member_no = $user_data['user_code'] ?? '';
    } else {
        $error = "Could not fetch your profile details!";
    }

    $stmt_user->close();
} else {
    $error = "SQL Error fetching user details: " . $con->error;
}

/* -------------------------------
   4. Form Submission (Add New SACCO Member)
-------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $sacco_id = trim($_POST['sacco_id'] ?? '');
    $sacco_no = trim($_POST['sacco_no'] ?? '');
    $member_no = trim($_POST['member_no'] ?? '');

    if (!is_numeric($sacco_id) || $sacco_id <= 0) {
        $error = "Invalid SACCO selection.";
    } elseif (empty($full_name) || empty($phone) || empty($sacco_no) || empty($member_no)) {
        $error = "All fields are required.";
    } else {

        // INSERT new SACCO member
        $sql_insert = "
            INSERT INTO sacco_members (full_name, phone, sacco_id, sacco_no, member_no)
            VALUES (?, ?, ?, ?, ?)
        ";

        $stmt_insert = $con->prepare($sql_insert);
        if ($stmt_insert) {

            $stmt_insert->bind_param("ssiss", 
                $full_name, 
                $phone, 
                $sacco_id, 
                $sacco_no, 
                $member_no
            );

            if ($stmt_insert->execute()) {
                $_SESSION['success_message'] = "🎉 SACCO Registration Successful!";
                header("Location: sacco&arbolitos.php");
                exit();
            } else {
                $error = "Error adding SACCO member: " . $stmt_insert->error;
            }

            $stmt_insert->close();

        } else {
            $error = "SQL Prepare failed: " . $con->error;
        }
    }
}

$con->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register SACCO Member</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f4f9; }
        .container { max-width: 600px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        h1 { color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        input[readonly] { background: #eee; }
        button { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 1em; }
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

    <div class="container">
        <h1>🏦 Register New SACCO Member</h1>

        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name"
                       value="<?php echo htmlspecialchars($auto_full_name); ?>"
                       readonly required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone"
                       value="<?php echo htmlspecialchars($auto_phone); ?>"
                       readonly required>
            </div>

            <div class="form-group">
                <label for="sacco_id">Select SACCO</label>
                <select id="sacco_id" name="sacco_id" required>
                    <option value="" disabled selected>--- Select SACCO ---</option>
                    <?php foreach ($all_saccos as $sacco): ?>
                        <option value="<?php echo $sacco['sacco_number']; ?>">
                            <?php echo htmlspecialchars($sacco['sacco_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="sacco_no">SACCO Number</label>
                <input type="text" id="sacco_no" name="sacco_no" required>
            </div>

            <div class="form-group">
                <label for="member_no">Member Number</label>
                <input type="text" id="member_no" name="member_no"
                       value="<?php echo htmlspecialchars($auto_member_no); ?>"
                       readonly required>
            </div>

            <button type="submit">Register Member</button>
        </form>
    </div>

</body>
</html>
