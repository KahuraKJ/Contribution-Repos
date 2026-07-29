<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection parameters (NOTE: In production, these should be loaded from secure environment variables)
$host = "localhost";
$db = "digita51_portal";
$user = "digita51_enock";
$pass = "digita51_enock";

// --- Validation and Redirection ---
if (!isset($_SESSION['id_no'])) {
    header('Location: index.php'); // Redirect to login if not logged in
    exit;
}

// ONLY get the ID from the secure session data.
$id_no = $_SESSION['id_no'];
$id_no_from_request = $id_no;


// --- Database Connection ---
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    // Die with a generic message for security
    die("Connection failed. Please try again later. (Error ID: DB_CONNECT)");
}

$sql_fetch_saccos = "SELECT sacco_number, sacco_name FROM sacco ORDER BY sacco_name";
$result_saccos = $conn->query($sql_fetch_saccos);

if ($result_saccos) {
    while ($row = $result_saccos->fetch_assoc()) {
        $all_saccos[] = $row;
    }
    $result_saccos->free();
} else {
    $error = "Error fetching SACCO list: " . $conn->error;
}

// --- Static Data Definitions ---

$groups = [];
// Added leader_phone to the query
$sql_groups = "SELECT id, group_name, group_number, leader_name, leader_phone FROM arbolitos_groups ORDER BY group_name ASC";
$result_groups = $conn->query($sql_groups);

if ($result_groups) {
    while ($row = $result_groups->fetch_assoc()) {
        $groups[] = $row;
    }
}


$saccos = [];
// Querying the sacco table with correct column names
$sql_saccos = "SELECT id, sacco_name, sacco_number, chairman, chairman_phone_number FROM sacco ORDER BY sacco_name ASC";
$result_saccos = $conn->query($sql_saccos);

if ($result_saccos) {
    while ($row = $result_saccos->fetch_assoc()) {
        $saccos[] = $row;
    }
}
function getTransportUpdateValues(array $postData): array {
    $mode = $postData['mode_of_transport'] ?? 'foot';

    // Default values
    $values = [
        'mode_of_transport' => $mode,
        'fuel_type' => null,
        'make_type_electric' => null,
        'model_type_electric' => null,
        'make_type_petrol' => null,
        'motorcycleModel' => null,
        'make_van' => null,
        'van_model' => null,
        'van_class_type' => null,
        'capacity' => null,
        'plate_number' => null,
        'chassis_number' => null,
        'engine_number' => null,
        'driving_license_number' => null,
        'license_issue_date' => null,
        'license_expiry_date' => null,
        'policy_number' => null,
        'policy_issue_date' => null,
        'policy_expiry_date' => null,
        'license_status' => $postData['license_status'] ?? 'Valid',
        'policy_status' => $postData['policy_status'] ?? 'Valid',
        'insurance_company' => null,
    ];

    if ($mode === 'Motorbike' || $mode === 'vehicle') {
        // Shared fields for Motorbike and Vehicle
        $values['plate_number'] = !empty($postData['plate_number']) ? trim(htmlspecialchars($postData['plate_number'])) : null;
        $values['chassis_number'] = !empty($postData['chassis_number']) ? trim(htmlspecialchars($postData['chassis_number'])) : null;
        $values['engine_number'] = !empty($postData['engine_number']) ? trim(htmlspecialchars($postData['engine_number'])) : null;
        $values['driving_license_number'] = !empty($postData['driving_license_number']) ? trim(htmlspecialchars($postData['driving_license_number'])) : null;
        $values['license_issue_date'] = !empty($postData['license_issue_date']) ? date('Y-m-d', strtotime($postData['license_issue_date'])) : null;
        $values['license_expiry_date'] = !empty($postData['license_expiry_date']) ? date('Y-m-d', strtotime($postData['license_expiry_date'])) : null;
        $values['policy_number'] = !empty($postData['policy_number']) ? trim(htmlspecialchars($postData['policy_number'])) : null;
        $values['policy_issue_date'] = !empty($postData['policy_issue_date']) ? date('Y-m-d', strtotime($postData['policy_issue_date'])) : null;
        $values['policy_expiry_date'] = !empty($postData['policy_expiry_date']) ? date('Y-m-d', strtotime($postData['policy_expiry_date'])) : null;
        $values['insurance_company'] = !empty($postData['insurance_company']) ? trim(htmlspecialchars($postData['insurance_company'])) : null;
    }

    if ($mode === 'Motorbike') {
        $values['fuel_type'] = !empty($postData['fuel_type']) ? trim(htmlspecialchars($postData['fuel_type'])) : null;
        if ($values['fuel_type'] === 'Electric') {
            $values['make_type_electric'] = !empty($postData['make_type_electric']) ? trim(htmlspecialchars($postData['make_type_electric'])) : null;
            $values['model_type_electric'] = !empty($postData['model_type_electric']) ? trim(htmlspecialchars($postData['model_type_electric'])) : null;
        } elseif ($values['fuel_type'] === 'Petrol') {
            $values['make_type_petrol'] = !empty($postData['make_type_petrol']) ? trim(htmlspecialchars($postData['make_type_petrol'])) : null;
            $values['motorcycleModel'] = !empty($postData['motorcycleModel']) ? trim(htmlspecialchars($postData['motorcycleModel'])) : null;
        }
    } elseif ($mode === 'vehicle') {
        $values['make_van'] = !empty($postData['make_van']) ? trim(htmlspecialchars($postData['make_van'])) : null;
        $values['van_model'] = !empty($postData['van_model']) ? trim(htmlspecialchars($postData['van_model'])) : null;
        $values['van_class_type'] = !empty($postData['van_class_type']) ? trim(htmlspecialchars($postData['van_class_type'])) : null;
        $values['capacity'] = !empty($postData['capacity']) ? trim(htmlspecialchars($postData['capacity'])) : null;
    }

    return $values;
}



// Initialize variables for messages
$success = "";
$error = "";
$data = [];


// --- Step 1: Fetch user data ---
if ($id_no_from_request) {
    // JOIN all relevant tables to fetch complete user profile
    $select_stmt = $conn->prepare("
        SELECT
            p.*,
            c.contact_number,
            c.email_address,
            c.emergency_contact,
            r.home_county,
            r.home_sub_county,
            r.home_ward,
            r.home_estate,
            m.*,
            l.user_code,
            a.group_id AS fetched_group_id,
            a.full_name AS arbolitos_full_name,
            s.sacco_id AS fetched_sacco_id,
            s.full_name AS sacco_full_name
        FROM personal p
        LEFT JOIN contact_address c ON p.id_no = c.id_no
        LEFT JOIN residential r ON p.id_no = r.id_no
        LEFT JOIN mode_of_travel m ON p.id_no = m.id_no
        LEFT JOIN login l ON p.id_no = l.id_no
        LEFT JOIN arbolitos_members a ON l.user_code = a.member_no
        LEFT JOIN sacco_members s ON l.user_code = s.member_no

        WHERE p.id_no = ?
    ");

    if ($select_stmt) {
        $select_stmt->bind_param("s", $id_no_from_request);
        $select_stmt->execute();
        $query_result = $select_stmt->get_result();

        if ($query_result->num_rows > 0) {
            $data = $query_result->fetch_assoc();

        

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
                // Merge POST data to keep user inputs on error/re-display
                $data = array_merge($data, $_POST);
            }
        } else {
            $error .= "<p style='color:red;'>User not found for ID: " . htmlspecialchars($id_no_from_request) . "</p>";
        }
        $select_stmt->close();
    } else {
        $error .= "Prepare statement error during data fetch: " . htmlspecialchars($conn->error) . "<br>";
    }
}


// --- Step 2: Handle updates ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id_no_to_update = htmlspecialchars($_POST['id_no'] ?? '');

    // Assigning the select menu values to your new variable names
    $group_id = isset($_POST['group_id']) ? $_POST['group_id'] : null; // This maps to group_number
    $sacco_id = isset($_POST['sacco_id']) ? $_POST['sacco_id'] : null; // This maps to sacco_number
    
    
    // Basic validation and data sanitization
    $full_name = trim(htmlspecialchars($_POST['full_name'] ?? ''));
    $kra_pin = trim(htmlspecialchars($_POST['kra_pin'] ?? ''));
    $date_of_birth = trim(htmlspecialchars($_POST['date_of_birth'] ?? ''));
    $education_level = trim(htmlspecialchars($_POST['education_level'] ?? ''));
    $gender = trim(htmlspecialchars($_POST['gender'] ?? ''));
    $marital_status = trim(htmlspecialchars($_POST['marital_status'] ?? ''));
    $next_kin = trim(htmlspecialchars($_POST['next_kin'] ?? ''));
    $relationship = trim(htmlspecialchars($_POST['relationship'] ?? ''));
    $next_kin_phone = trim(htmlspecialchars($_POST['next_kin_phone'] ?? ''));
    $contact_number = trim(htmlspecialchars($_POST['contact_number'] ?? ''));
    $email_address = trim(htmlspecialchars($_POST['email_address'] ?? ''));
    $emergency_contact = trim(htmlspecialchars($_POST['emergency_contact'] ?? ''));
    $nationality = trim(htmlspecialchars($_POST['nationality'] ?? ''));
    $county = trim(htmlspecialchars($_POST['home_county'] ?? ''));
    $sub_county = trim(htmlspecialchars($_POST['home_sub_county'] ?? ''));
    $ward = trim(htmlspecialchars($_POST['home_ward'] ?? ''));
    $estate = trim(htmlspecialchars($_POST['home_estate'] ?? ''));
    $arbolitos_group_name = trim(htmlspecialchars($_POST['arbolitos_group'] ?? ''));
    $sacco_name_post = trim(htmlspecialchars($_POST['sacco_name'] ?? ''));

    $member_no = $data['user_code'] ?? $id_no_to_update;

    $transport_data = getTransportUpdateValues($_POST);

    if (empty($id_no_to_update) || empty($full_name) || empty($kra_pin) || empty($date_of_birth) || empty($contact_number) || empty($email_address)) {
        $error .= "Essential personal and contact details are required.<br>";
    }

    if ($group_id === null || $group_id === 0) {
        $error .= "Please select a valid Arbolitos Group. (Failed to extract Group ID)<br>";
    }

    if ($sacco_id === null || $sacco_id === 0) {
        $error .= "Please select a valid SACCO. (Failed to extract Sacco ID)<br>";
    }

    if (empty($error)) {
        $conn->begin_transaction();
        $update_successful = true;

        // 1. Update personal
        $stmt_personal = $conn->prepare("UPDATE personal SET full_name=?, kra_pin=?, date_of_birth=?, education_level=?, gender=?, marital_status=?, next_kin=?, relationship=?, next_kin_phone=?, nationality=? WHERE id_no=?");
        if ($stmt_personal) {
            $stmt_personal->bind_param("sssssssssss", $full_name, $kra_pin, $date_of_birth, $education_level, $gender, $marital_status, $next_kin, $relationship, $next_kin_phone, $nationality, $id_no_to_update);
            if (!$stmt_personal->execute()) {
                $error .= "Error updating personal: " . htmlspecialchars($stmt_personal->error) . "<br>";
                $update_successful = false;
            }
            $stmt_personal->close();
        } else {
             $error .= "Prepare statement error for personal UPDATE: " . htmlspecialchars($conn->error) . "<br>";
             $update_successful = false;
        }


        // 2. Update contact
        if ($update_successful) {
            $stmt_contact = $conn->prepare("UPDATE contact_address SET contact_number=?, email_address=?, emergency_contact=? WHERE id_no=?");
            if ($stmt_contact) {
                $stmt_contact->bind_param("ssss", $contact_number, $email_address, $emergency_contact, $id_no_to_update);
                if (!$stmt_contact->execute()) {
                    $error .= "Error updating contact_address: " . htmlspecialchars($stmt_contact->error) . "<br>";
                    $update_successful = false;
                }
                $stmt_contact->close();
            } else {
                 $error .= "Prepare statement error for contact UPDATE: " . htmlspecialchars($conn->error) . "<br>";
                 $update_successful = false;
            }
        }

        // 3. Update residential
        if ($update_successful) {
            $stmt_residential = $conn->prepare("UPDATE residential SET home_county=?, home_sub_county=?, home_ward=?, home_estate=? WHERE id_no=?");
            if ($stmt_residential) {
                $stmt_residential->bind_param("sssss", $county, $sub_county, $ward, $estate, $id_no_to_update);
                if (!$stmt_residential->execute()) {
                    $error .= "Error updating residential: " . htmlspecialchars($stmt_residential->error) . "<br>";
                    $update_successful = false;
                }
                $stmt_residential->close();
            } else {
                 $error .= "Prepare statement error for residential UPDATE: " . htmlspecialchars($conn->error) . "<br>";
                 $update_successful = false;
            }
        }

        // 4. Update/Insert arbolitos_members
        // --- 4. Update/Insert arbolitos_members ---
if ($update_successful && $group_id !== null) {
    // Note: Changed "ssis" to "ssss" because group_id is now the group_number
    $stmt_update_arbolitos = $conn->prepare("UPDATE arbolitos_members SET full_name=?, phone=?, group_id=? WHERE member_no=?");
    if ($stmt_update_arbolitos) {
        $stmt_update_arbolitos->bind_param("ssss", $full_name, $contact_number, $group_id, $member_no);
        $stmt_update_arbolitos->execute();

        if ($stmt_update_arbolitos->affected_rows === 0) {
            $stmt_insert_arbolitos = $conn->prepare("INSERT INTO arbolitos_members (full_name, member_no, phone, group_id) VALUES (?, ?, ?, ?)");
            $stmt_insert_arbolitos->bind_param("ssss", $full_name, $member_no, $contact_number, $group_id);
            $stmt_insert_arbolitos->execute();
            $stmt_insert_arbolitos->close();
        }
        $stmt_update_arbolitos->close();
    }
}

// --- 5. Update/Insert sacco_members ---
if ($update_successful && $sacco_id !== null) {
    // Note: Changed "ssis" to "ssss" because sacco_id is now the sacco_number
    $stmt_update_sacco = $conn->prepare("UPDATE sacco_members SET full_name=?, phone=?, sacco_id=? WHERE member_no=?");
    if ($stmt_update_sacco) {
        $stmt_update_sacco->bind_param("ssss", $full_name, $contact_number, $sacco_id, $member_no);
        $stmt_update_sacco->execute();

        if ($stmt_update_sacco->affected_rows === 0) {
            $stmt_insert_sacco = $conn->prepare("INSERT INTO sacco_members (full_name, member_no, phone, sacco_id) VALUES (?, ?, ?, ?)");
            $stmt_insert_sacco->bind_param("ssss", $full_name, $member_no, $contact_number, $sacco_id);
            $stmt_insert_sacco->execute();
            $stmt_insert_sacco->close();
        }
        $stmt_update_sacco->close();
    }
}
        // 6. Update transport
        if ($update_successful) {
            $stmt_travel = $conn->prepare("
                UPDATE mode_of_travel SET
                    mode_of_transport=?, fuel_type=?, make_type_electric=?, model_type_electric=?, make_type_petrol=?, motorcycleModel=?, make_van=?, van_model=?, van_class_type=?, capacity=?, plate_number=?, chassis_number=?, engine_number=?, driving_license_number=?, license_issue_date=?, license_expiry_date=?, policy_number=?, policy_issue_date=?, policy_expiry_date=?, license_status=?, policy_status=?,insurance_company=?
                WHERE id_no=?
            ");
            if ($stmt_travel) {
                // Binding 22 variables + 1 WHERE condition = 23 parameters
                $stmt_travel->bind_param("sssssssssssssssssssssss",
                    $transport_data['mode_of_transport'],
                    $transport_data['fuel_type'],
                    $transport_data['make_type_electric'],
                    $transport_data['model_type_electric'],
                    $transport_data['make_type_petrol'],
                    $transport_data['motorcycleModel'],
                    $transport_data['make_van'],
                    $transport_data['van_model'],
                    $transport_data['van_class_type'],
                    $transport_data['capacity'],
                    $transport_data['plate_number'],
                    $transport_data['chassis_number'],
                    $transport_data['engine_number'],
                    $transport_data['driving_license_number'],
                    $transport_data['license_issue_date'],
                    $transport_data['license_expiry_date'],
                    $transport_data['policy_number'],
                    $transport_data['policy_issue_date'],
                    $transport_data['policy_expiry_date'],
                    $transport_data['license_status'],
                    $transport_data['policy_status'],
                    $transport_data['insurance_company'],
                    $id_no_to_update
                );
                if (!$stmt_travel->execute()) {
                    $error .= "Error updating mode_of_travel: " . htmlspecialchars($stmt_travel->error) . "<br>";
                    $update_successful = false;
                }
                $stmt_travel->close();
            } else {
                 $error .= "Prepare statement error for travel UPDATE: " . htmlspecialchars($conn->error) . "<br>";
                 $update_successful = false;
            }
        }

        // 7. Update profile flag
        if ($update_successful) {
            $updateFlag = $conn->prepare("UPDATE personal SET profile_updated='YES' WHERE id_no=?");
            if ($updateFlag) {
                $updateFlag->bind_param("s", $id_no_to_update);
                $updateFlag->execute();
                $updateFlag->close();
            }
        }

        // 8. Commit or rollback
        if ($update_successful && empty($error)) {
            $conn->commit();
            // Success: Redirect to avoid form resubmission
            header("Location: userdashboard.php");
            exit;
        } else {
            $conn->rollback();
            // Error: Update $data with POST values so form fields retain input on reload
            $data = array_merge($data, $_POST);
            $error = "Failed to update user information. " . $error;
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Member Registration</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
            padding: 2rem;
            margin: 0;
        }

        .container {
            max-width: 900px;
            margin: auto;
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        h2, h3 {
            text-align: center;
            color: #333;
            margin-bottom: 1.5rem;
        }

        .section {
            margin-bottom: 2rem;
            border: 1px solid #e0e0e0;
            padding: 1.5rem;
            border-radius: 8px;
            background-color: #fcfcfc;
        }

        .section-label {
            font-size: 1.2rem;
            font-weight: bold;
            color: #444;
            margin-bottom: 1rem;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 0.5rem;
        }

        label {
            display: block;
            margin-top: 1rem;
            font-weight: bold;
            color: #555;
        }

        input, select {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 0.3rem;
            box-sizing: border-box;
            font-size: 1rem;
        }

        input:focus, select:focus {
            border-color: #0056b3;
            outline: none;
            box-shadow: 0 0 5px rgba(0, 86, 179, 0.3);
        }

        .form-row {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .form-row > div {
            flex: 1;
            min-width: 250px;
        }

        button[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background 0.3s ease;
            display: block;
            margin: 2rem auto 0;
            width: fit-content;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-bottom: 1rem;
            font-weight: bold;
        }

        .success-message {
            color: green;
            text-align: center;
            margin-bottom: 1rem;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            .form-row > div {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Update Membership Information</h2>
    <form method="POST">
        <input type="hidden" name="id_no" value="<?= htmlspecialchars($data['id_no'] ?? '') ?>">

        <?php if (!empty($success)): ?>
            <p class="success-message"><?= $success ?></p>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <p class="error-message"><h3>Update Errors:</h3><?= $error ?></p>
        <?php endif; ?>

        <div class="section">
            <div class="section-label">Personal Information</div>
            <div class="form-row">
                <div>
                    <label for="full_name">Full Name:</label>
                    <input type="text" name="full_name" id="full_name" value="<?= htmlspecialchars($data['full_name'] ?? '') ?>" placeholder = "e.g John" required>
                </div>
                <div>
                    <label for="kra_pin">KRA PIN:</label>
                    <input type="text" name="kra_pin" id="kra_pin" value="<?= htmlspecialchars($data['kra_pin'] ?? '') ?>" maxlength="11"
                           pattern="^[A-Za-z0-9]{11}$" title="KRA PIN must be 11 alphanumeric characters" placeholder ="Axxxxxxxxxx" required>
                </div>
                <div>
                    <label for="date_of_birth">Date Of Birth:</label>
                    <input type="date" name="date_of_birth" id="date_of_birth" value="<?= htmlspecialchars($data['date_of_birth'] ?? '') ?>" placeholder="1990-01-01" required>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="gender">Gender:</label>
                    <select name="gender" id="gender" required>
                        <option value="">--Select--</option>
                        <option value="Male" <?= ($data['gender'] ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($data['gender'] ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div>
                    <label for="education_level">Education Level:</label>
                    <select id="education_level" name="education_level" required >
                        <option value="">Select Level of Education</option>
                        <option value="None" <?= ($data['education_level'] ?? '') == 'None' ? 'selected' : '' ?>>None</option>
                        <option value="Primary" <?= ($data['education_level'] ?? '') == 'Primary' ? 'selected' : '' ?>>Primary</option>
                        <option value="Secondary" <?= ($data['education_level'] ?? '') == 'Secondary' ? 'selected' : '' ?>>Secondary</option>
                        <option value="Certificate" <?= ($data['education_level'] ?? '') == 'Certificate' ? 'selected' : '' ?>>Certificate</option>
                        <option value="Diploma" <?= ($data['education_level'] ?? '') == 'Diploma' ? 'selected' : '' ?>>Diploma</option>
                        <option value="Degree" <?= ($data['education_level'] ?? '') == 'Degree' ? 'selected' : '' ?>>Degree</option>
                        <option value="Masters" <?= ($data['education_level'] ?? '') == 'Masters' ? 'selected' : '' ?>>Masters</option>
                        <option value="PhD" <?= ($data['education_level'] ?? '') == 'PhD' ? 'selected' : '' ?>>PhD</option>
                        <option value="Undergraduate" <?= ($data['education_level'] ?? '') == 'Undergraduate' ? 'selected' : '' ?>>Undergraduate</option>
                        <option value="Postgraduate" <?= ($data['education_level'] ?? '') == 'Postgraduate' ? 'selected' : '' ?>>Postgraduate</option>
                        <option value="Other" <?= ($data['education_level'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label for="marital_status">Marital Status:</label>
                    <select id="marital_status" name="marital_status" required >
                        <option value="">Select Marital Status</option>
                        <option value="Single" <?= ($data['marital_status'] ?? '') == 'Single' ? 'selected' : '' ?> >Single</option>
                        <option value="Married" <?= ($data['marital_status'] ?? '') == 'Married' ? 'selected' : '' ?> >Married</option>
                        <option value="Divorced" <?= ($data['marital_status'] ?? '') == 'Divorced' ? 'selected' : '' ?>>Divorced</option>
                        <option value="Widowed" <?= ($data['marital_status'] ?? '') == 'Widowed' ? 'selected' : '' ?> >Widowed</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="next_kin">Next Of Kin:</label>
                    <input type="text" name="next_kin" id="next_kin" value="<?= htmlspecialchars($data['next_kin'] ?? '') ?>" placeholedr="John" required>
                </div>
                <div>
                    <label for="relationship">Relationship:</label>
                    <select id="relationship" name="relationship" required>
                        <option value="">Select Relationship</option>
                        <option value="Parent" <?= ($data['relationship'] ?? '') == 'Parent' ? 'selected' : '' ?>>Parent</option>
                        <option value="Sibling" <?= ($data['relationship'] ?? '') == 'Sibling' ? 'selected' : '' ?>>Sibling</option>
                        <option value="Spouse" <?= ($data['relationship'] ?? '') == 'Spouse' ? 'selected' : '' ?>>Spouse</option>
                        <option value="Child" <?= ($data['relationship'] ?? '') == 'Child' ? 'selected' : '' ?>>Child</option>
                        <option value="Relative" <?= ($data['relationship'] ?? '') == 'Relative' ? 'selected' : '' ?>>Relative</option>
                        <option value="Friend" <?= ($data['relationship'] ?? '') == 'Friend' ? 'selected' : '' ?>>Friend</option>
                        <option value="Guardian" <?= ($data['relationship'] ?? '') == 'Guardian' ? 'selected' : '' ?>>Guardian</option>
                        <option value="Other" <?= ($data['relationship'] ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label for="next_kin_phone">Next of Kin Phone:</label>
                    <input type="tel" name="next_kin_phone" id="next_kin_phone" value="<?= htmlspecialchars($data['next_kin_phone'] ?? '') ?>" maxlength="10"
                           pattern="^0[17]\d{8}$" title="Phone number must start with 01 or 07 and be 10 digits" placeholder="07xxxxxxxx" required>
                </div>
            </div>
        </div>

        <div class="section">
            <div class="section-label">Contact & Address Details</div>
            <div class="form-row">
                <div>
                    <label for="contact_number">Contact Number (Member):</label>
                    <input type="text" name="contact_number" id="contact_number" value="<?= htmlspecialchars($data['contact_number'] ?? '') ?>" maxlength="10"
                           pattern="^0[17]\d{8}$" title="Phone number must start with 01 or 07 and be 10 digits" required>
                </div>
                <div>
                    <label for="email_address">Email Address:</label>
                    <input type="email" name="email_address" id="email_address" value="<?= htmlspecialchars($data['email_address'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="emergency_contact">Emergency Contact:</label>
                    <input type="tel" name="emergency_contact" id="emergency_contact" value="<?= htmlspecialchars($data['emergency_contact'] ?? '') ?>" maxlength="10"
                           pattern="^0[17]\d{8}$" title="Phone number must start with 01 or 07 and be 10 digits" required>
                </div>
            </div>
            <div class="form-row">
                <div>
                    <label for="nationality">Nationality:</label>
                    <input type="text" name="nationality" id="nationality" value="<?= htmlspecialchars($data['nationality'] ?? '') ?>" required>
                </div>
                <div>
                <label for="county">Home County:</label>
                <select id="countySelect" name="home_county" required>
                <option value="" disabled selected>-- Choose Your County --</option>
                </select>
            </div>
                <!-- Sub-county Input -->
            <div>
            <label for="sub_county">Home Sub-county:</label>
            <select id="subCountySelect" name="home_sub_county" disabled required>
                <option value="" disabled selected>-- Select Your Sub-county --</option>
            </select>
            </div>
             <div>
            <label for="ward">Home Ward:</label>
            <select id="ward" name="home_ward" disabled required>
                <option value="" disabled selected>-- Select Your Ward --</option>
            </select></div>


            <div>
      
                <label for="estate">Home Estate:</label>
                <input type="text" class="form-control" id="estate" name="home_estate" placeholder="" required>    
            </div>
  
            <div class="form-group">
    <label for="group_select">Arbolitos Name</label>
    <select name="group_id" id="group_select" required onchange="updateLeaderInfo()">
        <option value="">-- Select Arbolitos --</option>
        <?php foreach ($groups as $group): ?>
            <option value="<?= htmlspecialchars($group['group_number']) ?>" 
                    data-leader="<?= htmlspecialchars($group['leader_name']) ?>" 
                    data-phone="<?= htmlspecialchars($group['leader_phone']) ?>"
                    <?= (isset($data['fetched_group_id']) && $data['fetched_group_id'] == $group['group_number']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($group['group_name']) ?> (Group #<?= htmlspecialchars($group['group_number']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>
            </div>

            <div class="form-row">

                <div>

                    <label for="leader_name">Arbolitos Leader Name</label>

                           <input type="text" id="leader_name" name="leader_name" readonly placeholder="Will auto-populate">

                </div>

                <div class="form-group">

                    <label for="leader_phone_number">Leader Phone Number</label>

                    <input type="text" id="leader_phone" name="leader_phone" readonly placeholder="Will auto-populate">

                </div>

<div class="form-group">
    <label for="sacco_select">SACCO Name</label>
    <select name="sacco_id" id="sacco_select" required onchange="updateSaccoInfo()">
        <option value="">-- Select a SACCO --</option>
        <?php foreach ($saccos as $sacco): ?>
            <option value="<?= htmlspecialchars($sacco['sacco_number']) ?>" 
                    data-chairman="<?= htmlspecialchars($sacco['chairman']) ?>" 
                    data-phone="<?= htmlspecialchars($sacco['chairman_phone_number']) ?>"
                    <?= (isset($data['fetched_sacco_id']) && $data['fetched_sacco_id'] == $sacco['sacco_number']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($sacco['sacco_name']) ?> (No. <?= htmlspecialchars($sacco['sacco_number']) ?>)
            </option>
        <?php endforeach; ?>
    </select>
</div>
    <div class="form-group">

        <label for="chairman">Chairman</label>

        <input type="text" id="chairman_name" name="chairman_name" readonly placeholder="Chairman will appear here">

    </div>
   <div class="form-group">
    <label for="chairman_phone_number">Chairman Phone Number</label>
    <input type="text" id="chairman_phone" name="chairman_phone" readonly placeholder="Phone number will appear here">

</div>
</div>
</div>
        <div class="section">
            <div class="section-label">Transport Information</div>
            <div class="form-row">
                <div>
                    <label for="mode-of-travel">Mode of Operation:</label>
                    <select name="mode_of_transport" id="mode-of-travel" onchange="toggleTravelFields()" required>
                        <option value="">--Select Mode of Operation--</option>
                        <option value="foot" <?= ($data['mode_of_transport'] ?? '') == 'foot' ? 'selected' : '' ?>>Walking Courier</option>
                        <option value="cyclist" <?= ($data['mode_of_transport'] ?? '') == 'cyclist' ? 'selected' : '' ?>>Cyclist</option>
                        <option value="Motorbike" <?= ($data['mode_of_transport'] ?? '') == 'Motorbike' ? 'selected' : '' ?>>Motorcycle</option>
                        <option value="vehicle" <?= ($data['mode_of_transport'] ?? '') == 'vehicle' ? 'selected' : '' ?>>Delivery Van</option>
                    </select>
                </div>
            </div>

            <div id="motorbike-fields" style="display: none;">
                <div class="form-row">
                    <div>
                        <label for="fuel_type">Fuel Type:</label>
                        <select id="fuel_type" name="fuel_type" onchange="toggleMakeDropdown()">
                            <option value="">Select Fuel Type</option>
                            <option value="Electric" <?= ($data['fuel_type'] ?? '') == 'Electric' ? 'selected' : '' ?>>Electric</option>
                            <option value="Petrol" <?= ($data['fuel_type'] ?? '') == 'Petrol' ? 'selected' : '' ?>>Petrol</option>
                        </select>
                    </div>
                    
                </div>

                <div class="form-row">
                    <div id="electric-make-group" style="display: none;">
                        <label for="make_type_electric">Make (Electric):</label>
                        <select id="make_type_electric" name="make_type_electric">
                            <option value="">-- Select Make(Electric) --</option>
                            <option value="Ecobodaa" <?= ($data['make_type_electric'] ?? '') == 'Ecobodaa' ? 'selected' : '' ?>>Ecobodaa</option>
                            <option value="Ampersand" <?= ($data['make_type_electric'] ?? '') == 'Ampersand' ? 'selected' : '' ?>>Ampersand</option>
                            <option value="Roam" <?= ($data['make_type_electric'] ?? '') == 'Roam' ? 'selected' : '' ?>>Roam</option>
                            <option value="Spartan" <?= ($data['make_type_electric'] ?? '') == 'Spartan' ? 'selected' : '' ?>>Spartan</option>
                            <option value="Arc Ride" <?= ($data['make_type_electric'] ?? '') == 'Arc Ride' ? 'selected' : '' ?>>Arc Ride</option>
                            <option value="Kiri EV" <?= ($data['make_type_electric'] ?? '') == 'Kiri EV' ? 'selected' : '' ?>>Kiri EV</option>
                            <option value="Stima Boda" <?= ($data['make_type_electric'] ?? '') == 'Stima Boda' ? 'selected' : '' ?>>Stima Boda</option>
                            <option value="Opibus" <?= ($data['make_type_electric'] ?? '') == 'Opibus' ? 'selected' : '' ?>>Opibus</option>
                            <option value="Mazi Mobility" <?= ($data['make_type_electric'] ?? '') == 'Mazi Mobility' ? 'selected' : '' ?>>Mazi Mobility</option>
                            <option value="Ebee" <?= ($data['make_type_electric'] ?? '') == 'Ebee' ? 'selected' : '' ?>>Ebee</option>
                            <option value="Bodawerk" <?= ($data['make_type_electric'] ?? '') == 'Bodawerk' ? 'selected' : '' ?>>Bodawerk</option>
                            <option value="Ecomobilus" <?= ($data['make_type_electric'] ?? '') == 'Ecomobilus' ? 'selected' : '' ?>>Ecomobilus</option>
                            <option value="Fika Mobility" <?= ($data['make_type_electric'] ?? '') == 'Fika Mobility' ? 'selected' : '' ?>>Fika Mobility</option>
                            <option value="Spiro" <?= ($data['make_type_electric'] ?? '') == 'Spiro' ? 'selected' : '' ?>>Spiro</option>
                        </select>
                    </div>
                    <div id="electric-model-group" style="display: none;">
                        <label for="model_type_electric">Model (Electric):</label>
                        <select id="model_type_electric" name="model_type_electric">
                            <option value="">-- Select Model(Electric) --</option>
                            <option value="Eco1" <?= ($data['model_type_electric'] ?? '') == 'Eco1' ? 'selected' : '' ?>>Eco1 (Ecobodaa)</option>
                            <option value="Ampersand V1" <?= ($data['model_type_electric'] ?? '') == 'Ampersand V1' ? 'selected' : '' ?>>Ampersand V1</option>
                            <option value="Ampersand V2" <?= ($data['model_type_electric'] ?? '') == 'Ampersand V2' ? 'selected' : '' ?>>Ampersand V2</option>
                            <option value="Roam Air" <?= ($data['model_type_electric'] ?? '') == 'Roam Air' ? 'selected' : '' ?>>Roam Air</option>
                            <option value="Roam Air Beta" <?= ($data['model_type_electric'] ?? '') == 'Roam Air Beta' ? 'selected' : '' ?>>Roam Air Beta</option>
                            <option value="Spartan One" <?= ($data['model_type_electric'] ?? '') == 'Spartan One' ? 'selected' : '' ?>>Spartan One</option>
                            <option value="Spartan X" <?= ($data['model_type_electric'] ?? '') == 'Spartan X' ? 'selected' : '' ?>>Spartan X</option>
                            <option value="Arc Ride Alpha" <?= ($data['model_type_electric'] ?? '') == 'Arc Ride Alpha' ? 'selected' : '' ?>>Arc Ride Alpha</option>
                            <option value="Kiri EV Jumba" <?= ($data['model_type_electric'] ?? '') == 'Kiri EV Jumba' ? 'selected' : '' ?>>Kiri EV Jumba</option>
                            <option value="Stima One" <?= ($data['model_type_electric'] ?? '') == 'Stima One' ? 'selected' : '' ?>>Stima One</option>
                            <option value="Opibus Electric Bike" <?= ($data['model_type_electric'] ?? '') == 'Opibus Electric Bike' ? 'selected' : '' ?>>Opibus Electric Bike</option>
                            <option value="Mazi Max" <?= ($data['model_type_electric'] ?? '') == 'Mazi Max' ? 'selected' : '' ?>>Mazi Max</option>
                            <option value="Ebee E1" <?= ($data['model_type_electric'] ?? '') == 'Ebee E1' ? 'selected' : '' ?>>Ebee E1</option>
                            <option value="Bodawerk eBike" <?= ($data['model_type_electric'] ?? '') == 'Bodawerk eBike' ? 'selected' : '' ?>>Bodawerk eBike</option>
                            <option value="Ecomobilus Xpress" <?= ($data['model_type_electric'] ?? '') == 'Ecomobilus Xpress' ? 'selected' : '' ?>>Ecomobilus Xpress</option>
                            <option value="Fika eMoto" <?= ($data['model_type_electric'] ?? '') == 'Fika eMoto' ? 'selected' : '' ?>>Fika eMoto</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div id="petrol-make-group" style="display: none;">
                        <label for="make_type_petrol">Make (Petrol):</label>
                        <select id="make_type_petrol" name="make_type_petrol">
                            <option value="">-- Select Make(Petrol) --</option>
                            <option value="Bajaj" <?= ($data['make_type_petrol'] ?? '') == 'Bajaj' ? 'selected' : '' ?>>Bajaj</option>
                            <option value="TVS" <?= ($data['make_type_petrol'] ?? '') == 'TVS' ? 'selected' : '' ?>>TVS</option>
                            <option value="Boxer" <?= ($data['make_type_petrol'] ?? '') == 'Boxer' ? 'selected' : '' ?>>Boxer</option>
                            <option value="Hero MotoCorp" <?= ($data['make_type_petrol'] ?? '') == 'Hero MotoCorp' ? 'selected' : '' ?>>Hero MotoCorp</option>
                            <option value="Honda" <?= ($data['make_type_petrol'] ?? '') == 'Honda' ? 'selected' : '' ?>>Honda</option>
                            <option value="Yamaha" <?= ($data['make_type_petrol'] ?? '') == 'Yamaha' ? 'selected' : '' ?>>Yamaha</option>
                        </select>
                    </div>
                    <div id="motorcycle-model-group" style="display: none;">
                        <label for="motorcycleModel">Motorcycle Model:</label>
                       <select id = "motorcycleModel" name="motorcycleModel">
                           <option value=" ">--- Select Model(Petrol) ---</option>
                           <option value=" "<?= ($data['motorcycleModel']?? '')== ' ' ? 'selected' : '' ?>> </option>
                       </select>
                    </div>
                </div>
            </div>

            <div id="vehicle-fields" style="display: none;">
                <div class="form-row">
                    <div>
                        <label for="make_van">Make (Van):</label>
                        <input type="text" name="make_van" id="make_van" value="<?= htmlspecialchars($data['make_van'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="van_model">Van Model:</label>
                        <input type="text" name="van_model" id="van_model" value="<?= htmlspecialchars($data['van_model'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="van_class_type">Van Class Type:</label>
                        <input type="text" name="van_class_type" id="van_class_type" value="<?= htmlspecialchars($data['van_class_type'] ?? '') ?>">
                    </div>
                </div>
                
            </div>

            <div id="common-vehicle-fields" style="display: none;">
                <div class="form-row">
                    <div style="flex: 1;">
                    <label for="insurance_company">Insurance Company</label>
                <select name="insurance_company" id="insurance_company">
                    <option value="">-- Select Insurance Company --</option>
                    <option value="AAR Insurance (Kenya) Ltd" <?= $data['insurance_company'] == 'AAR Insurance (Kenya) Ltd' ? 'selected' : '' ?>>AAR Insurance (Kenya) Ltd</option>
<option value="Africa Merchant Assurance Co. Ltd" <?= $data['insurance_company'] == 'Africa Merchant Assurance Co. Ltd' ? 'selected' : '' ?>>Africa Merchant Assurance Co. Ltd</option>
<option value="NCBA Insurance Co. Ltd" <?= $data['insurance_company'] == 'NCBA Insurance Co. Ltd' ? 'selected' : '' ?>>NCBA Insurance Co. Ltd</option>
<option value="APA Insurance Ltd" <?= $data['insurance_company'] == 'APA Insurance Ltd' ? 'selected' : '' ?>>APA Insurance Ltd</option>
<option value="Britam General Insurance (Kenya) Ltd" <?= $data['insurance_company'] == 'Britam General Insurance (Kenya) Ltd' ? 'selected' : '' ?>>Britam General Insurance (Kenya) Ltd</option>
<option value="Cannon General Insurance Co. Ltd" <?= $data['insurance_company'] == 'Cannon General Insurance Co. Ltd' ? 'selected' : '' ?>>Cannon General Insurance Co. Ltd</option>
<option value="CIC General Insurance Ltd" <?= $data['insurance_company'] == 'CIC General Insurance Ltd' ? 'selected' : '' ?>>CIC General Insurance Ltd</option>
<option value="Corporate Insurance Co. Ltd" <?= $data['insurance_company'] == 'Corporate Insurance Co. Ltd' ? 'selected' : '' ?>>Corporate Insurance Co. Ltd</option>
<option value="Directline Assurance Co. Ltd" <?= $data['insurance_company'] == 'Directline Assurance Co. Ltd' ? 'selected' : '' ?>>Directline Assurance Co. Ltd</option>
<option value="Definite Assurance Co. Ltd" <?= $data['insurance_company'] == 'Definite Assurance Co. Ltd' ? 'selected' : '' ?>>Definite Assurance Co. Ltd</option>
<option value="Equity General Insurance (Kenya) Ltd" <?= $data['insurance_company'] == 'Equity General Insurance (Kenya) Ltd' ? 'selected' : '' ?>>Equity General Insurance (Kenya) Ltd</option>
<option value="Fidelity Shield Insurance Co. Ltd" <?= $data['insurance_company'] == 'Fidelity Shield Insurance Co. Ltd' ? 'selected' : '' ?>>Fidelity Shield Insurance Co. Ltd</option>
<option value="First Assurance Co. Ltd" <?= $data['insurance_company'] == 'First Assurance Co. Ltd' ? 'selected' : '' ?>>First Assurance Co. Ltd</option>
<option value="GA Insurance Ltd" <?= $data['insurance_company'] == 'GA Insurance Ltd' ? 'selected' : '' ?>>GA Insurance Ltd</option>
<option value="Geminia Insurance Co. Ltd" <?= $data['insurance_company'] == 'Geminia Insurance Co. Ltd' ? 'selected' : '' ?>>Geminia Insurance Co. Ltd</option>
<option value="ICEA Lion General Insurance Co. Ltd" <?= $data['insurance_company'] == 'ICEA Lion General Insurance Co. Ltd' ? 'selected' : '' ?>>ICEA Lion General Insurance Co. Ltd</option>
<option value="Intra Africa Assurance Co. Ltd" <?= $data['insurance_company'] == 'Intra Africa Assurance Co. Ltd' ? 'selected' : '' ?>>Intra Africa Assurance Co. Ltd</option>
<option value="Jubilee Allianz General Insurance Ltd" <?= $data['insurance_company'] == 'Jubilee Allianz General Insurance Ltd' ? 'selected' : '' ?>>Jubilee Allianz General Insurance Ltd</option>
<option value="Jubilee Health Insurance Ltd" <?= $data['insurance_company'] == 'Jubilee Health Insurance Ltd' ? 'selected' : '' ?>>Jubilee Health Insurance Ltd</option>
<option value="Kenindia Assurance Co. Ltd" <?= $data['insurance_company'] == 'Kenindia Assurance Co. Ltd' ? 'selected' : '' ?>>Kenindia Assurance Co. Ltd</option>
<option value="Kenya Orient Insurance Ltd" <?= $data['insurance_company'] == 'Kenya Orient Insurance Ltd' ? 'selected' : '' ?>>Kenya Orient Insurance Ltd</option>
<option value="Madison General Insurance Kenya Ltd" <?= $data['insurance_company'] == 'Madison General Insurance Kenya Ltd' ? 'selected' : '' ?>>Madison General Insurance Kenya Ltd</option>
<option value="Mayfair Insurance Co. Ltd" <?= $data['insurance_company'] == 'Mayfair Insurance Co. Ltd' ? 'selected' : '' ?>>Mayfair Insurance Co. Ltd</option>
<option value="MUA Insurance (Kenya) Ltd" <?= $data['insurance_company'] == 'MUA Insurance (Kenya) Ltd' ? 'selected' : '' ?>>MUA Insurance (Kenya) Ltd</option>
<option value="Occidental Insurance Co. Ltd" <?= $data['insurance_company'] == 'Occidental Insurance Co. Ltd' ? 'selected' : '' ?>>Occidental Insurance Co. Ltd</option>
<option value="Old Mutual General Insurance Kenya Ltd" <?= $data['insurance_company'] == 'Old Mutual General Insurance Kenya Ltd' ? 'selected' : '' ?>>Old Mutual General Insurance Kenya Ltd</option>
<option value="Pacis Insurance Co. Ltd" <?= $data['insurance_company'] == 'Pacis Insurance Co. Ltd' ? 'selected' : '' ?>>Pacis Insurance Co. Ltd</option>
<option value="Pioneer General Insurance Ltd" <?= $data['insurance_company'] == 'Pioneer General Insurance Ltd' ? 'selected' : '' ?>>Pioneer General Insurance Ltd</option>
<option value="Sanlam General Insurance Co. Ltd" <?= $data['insurance_company'] == 'Sanlam General Insurance Co. Ltd' ? 'selected' : '' ?>>Sanlam General Insurance Co. Ltd</option>
<option value="Star Discover Insurance Ltd" <?= $data['insurance_company'] == 'Star Discover Insurance Ltd' ? 'selected' : '' ?>>Star Discover Insurance Ltd</option>
<option value="Takaful Insurance of Africa Ltd" <?= $data['insurance_company'] == 'Takaful Insurance of Africa Ltd' ? 'selected' : '' ?>>Takaful Insurance of Africa Ltd</option>
<option value="Tausi Assurance Co. Ltd" <?= $data['insurance_company'] == 'Tausi Assurance Co. Ltd' ? 'selected' : '' ?>>Tausi Assurance Co. Ltd</option>
<option value="The Heritage Insurance Co. Ltd" <?= $data['insurance_company'] == 'The Heritage Insurance Co. Ltd' ? 'selected' : '' ?>>The Heritage Insurance Co. Ltd</option>
<option value="The Kenyan Alliance Insurance Co. Ltd" <?= $data['insurance_company'] == 'The Kenyan Alliance Insurance Co. Ltd' ? 'selected' : '' ?>>The Kenyan Alliance Insurance Co. Ltd</option>
<option value="The Monarch Insurance Co. Ltd" <?= $data['insurance_company'] == 'The Monarch Insurance Co. Ltd' ? 'selected' : '' ?>>The Monarch Insurance Co. Ltd</option>
<option value="Trident Insurance Co. Ltd" <?= $data['insurance_company'] == 'Trident Insurance Co. Ltd' ? 'selected' : '' ?>>Trident Insurance Co. Ltd</option>

                </select>
                </div>
                </div>

                <div class="form-row">
                    <div>
                        <label for="plate_number">Plate Number:</label>
                        <input type="text" name="plate_number" id="plate_number" value="<?= htmlspecialchars($data['plate_number'] ?? '') ?>">
                    </div><div>
                        <label for="chassis_number">Chassis Number:</label>
                        <input type="text" name="chassis_number" id="chassis_number" value="<?= htmlspecialchars($data['chassis_number'] ?? '') ?>">
                    </div><div>
                        <label for="engine_number">Engine Number:</label>
                        <input type="text" name="engine_number" id="engine_number" value="<?= htmlspecialchars($data['engine_number'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label for="driving_license_number">Driving License Number:</label>
                        <input type="text" name="driving_license_number" id="driving_license_number" value="<?= htmlspecialchars($data['driving_license_number'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="license_issue_date">License Issue Date:</label>
                        <input type="date" name="license_issue_date" id="license_issue_date" value="<?= htmlspecialchars($data['license_issue_date'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="license_expiry_date">License Expiry Date:</label>
                        <input type="date" name="license_expiry_date" id="license_expiry_date" value="<?= htmlspecialchars($data['license_expiry_date'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div>
                        <label for="policy_number">Policy/Certificate Number:</label>
                        <input type="text" name="policy_number" id="policy_number" value="<?= htmlspecialchars($data['policy_number'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="policy_issue_date">Policy Issue Date:</label>
                        <input type="date" name="policy_issue_date" id="policy_issue_date" value="<?= htmlspecialchars($data['policy_issue_date'] ?? '') ?>">
                    </div>
                    <div>
                        <label for="policy_expiry_date">Policy Expiry Date:</label>
                        <input type="date" name="policy_expiry_date" id="policy_expiry_date" value="<?= htmlspecialchars($data['policy_expiry_date'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" name="update">Update Information</button>
    </form>
</div>
<script>
       const countyData = {
    "Baringo": {
        "Baringo Central": ["Kabarnet Town Ward", "Sacho Ward", "Tenges Ward", "Kapropita Ward", "Ewalel Chapel Ward"],
        "Baringo North": ["Kabartonjo Ward", "Bartabwa Ward", "Saimo-Kipsaraman Ward", "Saimo-Soi Ward", "Barwessa Ward"],
        "Baringo South": ["Marigat Ward", "Ilchamus Ward", "Mochongoi Ward", "Mukutani Ward"],
        "Eldama Ravine": ["Eldama Ravine Ward", "Lembus Kwen Ward", "Lembus Ward", "Mogotio Ward", "Emining Ward"],
        "Mogotio": ["Mogotio Ward", "Kisanana Ward", "Sandai Ward", "Emsos Ward"],
        "Tiaty": ["Kolowa Ward", "Tirioko Ward", "Ripkwo/Churo/Barsombe Ward", "Loyamorok Ward", "Nukinyang Ward", "Silale Ward", "Loiyangalani Ward"]
    },
    "Bomet": {
        "Bomet Central": ["Mutarakwa Ward", "Kipreres Ward", "Ndani Ward", "Singorwet Ward", "Chesoen Ward"],
        "Bomet East": ["Merigi Ward", "Kembu Ward", "Longisa Ward", "Kipsonoi Ward", "Chemaner Ward"],
        "Chepalungu": ["Chepalungu Ward", "Kong'asis Ward", "Siongiroi Ward", "Nyangores Ward", "Taboino Ward"],
        "Konoin": ["Boito Ward", "Embomos Ward", "Kimulot Ward", "Chepchabas Ward", "Tungunoi Ward"],
        "Sotik": ["Ndanai/Abosi Ward", "Manaret/Kipchoge Ward", "Kaplong Ward", "Mutito/Ainamoi Ward", "Chebilat Ward"]
    },
    "Bungoma": {
        "Bumula": ["Bumula Ward", "Khasoko Ward", "Kimaeti Ward", "South Bukusu Ward", "Siboti Ward"],
        "Kabuchai": ["Kabuchai Ward", "Chwele/Kabuchai Ward", "Mukuyuni Ward", "Cheptais Ward", "Kibingei Ward"],
        "Kanduyi": ["Bukembe West Ward", "East Sang'alo Ward", "Kanduyi Ward", "Marakaru/Tuuti Ward", "Milima Ward", "Sinoko Ward", "Tuuti/Marakaru Ward", "Khalaba Ward", "Musikoma Ward", "Township Ward"],
        "Kimilil": ["Kimilil Ward", "Kamukuywa Ward", "Kimilili Ward", "Maeni Ward"],
        "Mt. Elgon": ["Chepyuk Ward", "Kaptama Ward", "Kapsokwony Ward", "Koitilil Ward", "Cheptais Ward", "Chemoge Ward"],
        "Sirisia": ["Sirisia Ward", "Lwandanyi Ward", "Malakisi/South Khasoko Ward"],
        "Tongaren": ["Tongaren Ward", "Ndalu Ward", "Mbakalo Ward", "Mbakalo/Kamukuywa Ward", "Naitiri/Kabuyefwe Ward"],
        "Webuye East": ["Marakaru Ward", "Mihuu Ward", "Ndivisi Ward"],
        "Webuye West": ["Sitikho Ward", "Misikhu Ward", "Bokoli Ward"]
    },
    "Busia": {
        "Budalangi": ["Ruambwa Ward", "Bunyala Central Ward", "Bunyala North Ward", "Bunyala South Ward"],
        "Butula": ["Marachi East Ward", "Marachi West Ward", "Kingandole Ward", "Mayenje Ward", "Butula Ward", "Nanguba Ward"],
        "Funyula": ["Funyula Ward", "Namboboto Ward", "Wakhungu Ward", "Obaro Ward"],
        "Nambale": ["Nambale Township Ward", "Burumba Ward", "Bukhayo North/Walatsi Ward", "Bukhayo Central Ward", "Mwira Ward"],
        "Teso North": ["Ang'urai East Ward", "Ang'urai South Ward", "Ang'urai North Ward", "" /* Add other wards */],
        "Teso South": ["Amukura East Ward", "Amukura West Ward", "Chakol South Ward", "Chakol North Ward", "Aloete Ward"]
    },
    "Elgeyo-Marakwet": {
        "Keiyo North": ["Iten/Tambach Ward", "Kamariny Ward", "Samweno Ward", "Chepkorio Ward"],
        "Keiyo South": ["Metkei Ward", "Kaptarakwa Ward", "Soy South Ward", "Kabiemit Ward"],
        "Marakwet East": ["Embobut/Embulot Ward", "Sengwer Ward", "Kapyego Ward", "Sambirir Ward"],
        "Marakwet West": ["Kapsowar Ward", "Arror Ward", "Cherang'any/Chebororwa Ward", "Lelan Ward", "Moiben/Kuserwo Ward"]
    },
    "Embu": {
        "Manyatta": ["Nginda Ward", "Mbeti North Ward", "Mbeti South Ward", "Kithimu Ward", "Manyatta Ward"],
        "Mbeere North": ["Evurore Ward", "Nthawa Ward", "Muminji Ward", "Gitare Ward", "Mavuria Ward"],
        "Mbeere South": ["Mwea Ward", "Makima Ward", "Mbeti South Ward", "Kiritiri Ward", "Kiambere Ward"],
        "Runyenjes": ["Runyenjes Central Ward", "Gaturi South Ward", "Kagaari South Ward", "Kagaari North Ward"]
    },
    "Garissa": {
        "Dadaab": ["Dadaab Ward", "Liboi Ward", "Damajale Ward", "Abakaile Ward"],
        "Fafi": ["Bura Ward", "Dekaharia Ward", "Jarajila Ward", "Fafi Ward", "Nanighi Ward"],
        "Garissa": ["Township Ward", "Biashara Ward", "Galbet Ward", "Waberi Ward"],
        "Hulugho": ["Hulugho Ward", "Sangailu Ward", "Ijara Ward"],
        "Ijara": ["Ijara Ward", "Sangailu Ward", "Korakora Ward", "Hulugho Ward"],
        "Lagdera": ["Benane Ward", "Modogashe Ward", "Sabena Ward", "Lagdera Ward"]
    },
    "Homa Bay": {
        "Homabay Town": ["Homa Bay Town Central Ward", "Homa Bay Town East Ward", "Homa Bay Town West Ward"],
        "Kabondo Kasipul": ["Kabondo East Ward", "Kabondo West Ward", "Kodera Ward", "Kasipul West Ward"],
        "Karachwonyo": ["North Karachwonyo Ward", "Central Karachwonyo Ward", "Kendu Bay Town Ward", "Kanyadoto Ward", "Kibiri Ward",
  "Wang'chieng' Ward"],
        "Kasipul": ["East Kasipul Ward", "West Kasipul Ward", "South Kasipul Ward", "Wang'chieng Ward"],
        "Mbita": ["Mfangano Island Ward", "Rusinga Island Ward", "Gembe Ward", "Mbita Ward", "Gwassi North Ward", "Gwassi South Ward"],
        "Ndhiwa": ["Kanyikela Ward", "Kanyamwa Kosewe Ward", "Kwabwai Ward", "Ndhiwa Ward", "Kanyamwa Kologi Ward"],
        "Rangwe": ["Rangwe Ward", "Kanyaluo Ward", "Gem Ward", "Kamuga Ward"],
        "Suba": ["Gwassi North Ward", "Gwassi South Ward", "Ruma Kanyamwa Ward", "Lambwe Ward", "" /* Add other wards */]
    },
    "Isiolo": {
        "Isiolo": ["Bulapesa Ward", "Chari Ward", "Wabera Ward", "Burat Ward"],
        "Garbatulla": ["Garbatulla Ward", "Kinna Ward", "Modogashe Ward"],
        "Merti": ["Cherab Ward", "Gafarsa Ward", "Oldonyiro Ward"]
    },
    "Kajiado": {
        "Isinya": ["Isinya Ward", "Kaputiei North Ward", "Oloosirkon/Sholinke Ward", "Lenkek Ward"],
        "Kajiado Central": ["Kajiado Township Ward", "Imaroro Ward", "Poka/Mashuuru Ward", "Dalalekutuk Ward"],
        "Kajiado North": ["Ngong Ward", "Olkeri Ward", "Nkaimurunya Ward", "Oloolua Ward", "Kajiado West Ward"],
        "Loitokitok": ["Rombo Ward", "Kimana Ward", "Illasit Ward", "Loitokitok Ward", "Entonet/Lenkisin Ward"],
        "Mashuuru": ["Mashuuru Ward", "Ildamat Ward", "Meto Ward", "Kitengela Ward"]
    },
    "Kakamega": {
        "Butere": ["Marama North Ward", "Marama West Ward", "Marama Central Ward", "Marama South Ward"],
        "Ikolomani": ["Shisiru Ward", "Idakho North Ward", "Idakho South Ward", "Lirhanda Ward"],
        "Khwisero": ["Khwisero Ward", "Luanda/Luandeti Ward", "Koyonzo Ward", "Chegulo Ward"],
        "Likuyani": ["Likuyani Ward", "Likuyani/Matunda Ward", "Sikho Ward", "Soy Ward"],
        "Lugari": ["Lugari Ward", "Lumakanda Ward", "Likuyani Ward", "Chekalini Ward", "Likuyani/Matunda Ward"],
        "Lurambi": ["Lurambi Ward", "Butsotso East Ward", "Butsotso Central Ward", "Butsotso West Ward", "Murhanda Ward", "" /* Add other wards */],
        "Malava": ["East Kabras Ward", "West Kabras Ward", "North Kabras Ward", "South Kabras Ward", "Chemuche Ward", "" /* Add other wards */],
        "Matungu": ["Koyonzo Ward", "Khalaba Ward", "Eswani Ward", "Kholera Ward"],
        "Mumias East": ["East Wanga Ward", "Malia Ward", "Echesa Ward"],
        "Mumias West": ["Mumias Central Ward", "Mumias North Ward", "Mumias South Ward", "Musanda Ward"],
        "Navakholo": ["Navakholo Ward", "Bunyala East Ward", "Bunyala North Ward", "Bunyala West Ward"],
        "Shinyalu": ["Shinyalu Ward", "Isukha East Ward", "Isukha North Ward", "Isukha South Ward", "Ilesi Ward"]
    },
    "Kericho": {
        "Ainamoi": ["Ainamoi Ward", "Kapsoit Ward", "Kipchebor Ward", "Tebesonik Ward", "Chemogos Ward"],
        "Belgut": ["Belgut Ward", "Kabianga Ward", "Waldai Ward", "Chepkembeli Ward"],
        "Bureti": ["Litein Ward", "Kimulot Ward", "Kipreres Ward", "Kamelil Ward", "Tebesonik Ward", "Roret Ward", "Cheboin Ward"],
        "Kipkelion East": ["Londiani Ward", "Kedowa/Saniak Ward", "Kipkelion Ward", "Tendwet Ward"],
        "Kipkelion West": ["Chilchila Ward", "Kunyak Ward", "Kiptere Ward", "Chepseon Ward"],
        "Soin Sigowet": ["Soin Ward", "Sigowet Ward", "Kaplelartet Ward", "Soliat Ward"]
    },
    "Kiambu": {
        "Gatundu North": ["Mang'u Ward", "Chania Ward", "Gatukuyu Ward", "Ndarugo Ward"],
        "Gatundu South": ["Kiamwangi Ward", "Kiamworia Ward", "Ituru Ward", "Muiru Ward"],
        "Githunguri": ["Githunguri Ward", "Ngewa Ward", "Komothai Ward", "Githiga Ward",
  "Ikinu Ward"],
        "Juja": ["Juja Ward", "Kalimoni Ward", "Witeithie Ward", "Murera Ward"],
        "Kabete": ["Gitaru Ward", "Kabete Ward", "Kinoo Ward", "Uthiru/Muguga Ward", "Ngecha Tigoni Ward"],
        "Kiambaa": ["Karuri Ward", "Ndenderu Ward", "Muchatha Ward", "Cianda Ward", "Kihara Ward"],
        "Kiambu Town": ["Township Ward", "Ngecha Ward", "Ndumberi Ward", "Ting'ang'a Ward"],
        "Kikuyu": ["Kikuyu Ward", "Karai Ward", "Nachu Ward", "Sigona Ward", "Limuru East Ward"],
        "Limuru": ["Limuru East Ward", "Limuru Central Ward", "Ngecha Ward", "Biashara Ward"],
        "Ruiru": ["Biashara Ward", "Kahawa Sukari Ward", "Kahawa Wendani Ward", "Githurai Ward", "Gitothua Ward", "Gatongora Ward", "Ruiru Central Ward"],
        "Thika Town": ["Township Ward", "Parklands Ward", "Kamenu Ward", "Hospital Ward", "Ngewa Ward", "Witeithie Ward"],
        "Lari": ["Lari Ward", "Kijabe Ward", "Kamburu Ward", "Nyanduma Ward", "Kijabe Ward"]
    },
    "Kilifi": {
        "Ganze": ["Bamba Ward", "Ganze Ward", "Jaribuni Ward", "Mwahera Ward"],
        "Kaloleni": ["Kaloleni Ward", "Mariakani Ward", "Mavueni Ward", "Kayafungo Ward"],
        "Kilifi North": ["Tezo Ward", "Matsangoni Ward", "Kibarani Ward", "Watamu Ward", "Dabaso Ward"],
        "Kilifi South": ["Shimo La Tewa Ward", "Chasimba Ward", "Mtepeni Ward", "Junju Ward"],
        "Magarini": ["Gongoni Ward", "Marafa Ward", "Magarini Ward", "Sabaki Ward", "Garashi Ward"],
        "Malindi": ["Shella Ward", "Malindi Town Ward", "Ganda Ward", "Kakuyuni Ward", "Jilore Ward", "Langobaya Ward"],
        "Rabai": ["Rabai/Mwakirunge Ward", "Kambe/Ribe Ward", "Jibana Ward"]
    },
    "Kirinyaga": {
        "Kirinyaga Central": ["Kanyekini Ward", "Kerugoya/Kutus Ward", "Mutira Ward", "Nyangati Ward"],
        "Kirinyaga East": ["Gichugu Ward", "Kabare Ward", "Kanyenya-ini Ward", "Ngariama Ward"],
        "Kirinyaga West": ["Mwea Ward", "Thiba Ward", "Wamumu Ward", "Wang'uru Ward"],
        "Mwea East": ["Kangai Ward", "Mwea Ward", "Wang'uru Ward", "Thiba Ward"],
        "Mwea West": ["Mutithi Ward", "Murinduko Ward", "Nyangati Ward", "Thigirigi Ward"]
    },
    "Kisii": {
        "Bomachoge Borabu": ["Bobaracho Ward", "Bomachoge Ward", "Kiabonyoru Ward", "Misesi Ward"],
        "Bomachoge Chache": ["Bogetenga Ward", "Bokeira Ward", "Tendere Ward", "Kenyenya Ward"],
        "Bobasi": ["Bobasi Ward", "Sameta Ward", "Basii Ward", "Nyacheki Ward", "" /* Add other wards */],
        "Bokimira": ["Bokimira Ward", "Gesicho Ward", "Masaba South Ward"],
        "Etago": ["Etago Ward", "Kegogi Ward", "Nyacheki Ward"],
        "Kitutu Chache North": ["Mosocho Ward", "Marani Ward", "Mwamonari Ward", "Kitutu Central Ward"],
        "Kitutu Chache South": ["Kisii Central Ward", "Nyansira Ward", "Nyamataro Ward", "Jogoo Ward", "Sensi Ward"],
        "Nyaribari Chache": ["Bobaracho Ward", "Kiogoro Ward", "Mosocho Ward", "Central Ward", "" /* Add other wards */],
        "Nyaribari Masaba": ["Kiogoro Ward", "Mwarembo Ward", "Gesusu Ward", "Ibacho Ward"],
        "Sameta": ["Sameta Ward", "Ichuni Ward", "Nyakoe Ward"],
        "South Mugirango": ["Bogetenga Ward", "Kenyenya Ward", "Moticho Ward", "Omobera Ward", "Boikanga Ward"]
    },
    "Kisumu": {
        "Kisumu Central": ["Kondele Ward", "Kaloleni Ward", "Shanzu Ward", "Nyalenda 'A' Ward", "Nyalenda 'B' Ward", "Railways Ward"],
        "Kisumu East": ["Kolwa East Ward", "Kolwa Central Ward", "Manyatta B Ward","Nyalenda A Ward",
  "Railways Ward","Kabonyo/Kajulu Ward"],
        "Kisumu West": ["Kisumu North Ward", "South West Kisumu Ward", "Central Kisumu Ward","North West Kisumu Ward",
  "West Kisumu Ward"],
        "Muhoroni": ["Chemelil/Tamu Ward", "Koru Ward", "Miwani Ward", "Ombeyi Ward"],
        "Nyakach": ["South Nyakach Ward", "Central Nyakach Ward", "North Nyakach Ward", "West Nyakach Ward", "East Nyakach Ward"],
        "Nyando": ["Awasi/Onjiko Ward", "Ahero Ward", "Kano/Kolwa Ward", "Kabonyo/Kajulu Ward"],
        "Seme": ["East Seme Ward", "West Seme Ward", "North Seme Ward", "Central Seme Ward"]
    },
    "Kitui": {
        "Ikutha": ["Ikutha Ward", "Kanziko Ward", "Mutomo/Kibwezi Ward", "" /* Add other wards */],
        "Katulani": ["Katulani Ward", "Kisasi Ward", "Kanyangi Ward", "Kyangwithya East Ward"],
        "Kisasi": ["Kisasi Ward", "Kanyangi Ward", "Kyangwithya West Ward", "Miambani Ward"],
        "Kitui Central": ["Kitui Central Ward", "Mulango Ward", "Kyangwithya East Ward", "Kyangwithya West Ward"],
        "Kitui East": ["Mutito/Mbitini Ward", "Kitui East Ward", "Endau/Malalani Ward", "Kanyangi Ward", "Voo/Kyamu Ward"],
        "Kitui Rural": ["Mutitu Ward", "Kitui South Ward", "Kanyangi Ward", "" /* Add other wards */],
        "Kitui South": ["Mutomo/Kibwezi Ward", "Mutha Ward", "Ikanga/Kyatune Ward", "Kyuu Ward"],
        "Kitui West": ["Mutongoni Ward", "Wamunyu Ward", "Kauwi Ward", "Kyangwithya West Ward"],
        "Lower Yatta": ["Yatta/Kwa Vonza Ward", "Kanziku Ward", "" /* Add other wards */],
        "Matiyani": ["Matiyani Ward", "Kyangwithya West Ward", "Museve Ward"],
        "Migwani": ["Migwani Ward", "Ngaaie Ward", "Thitani Ward", "Kamuloko Ward"],
        "Mutitu": ["Mutitu Ward", "Kwa Mutonga/Kithumula Ward", "Waita Ward"],
        "Mutomo": ["Mutomo/Kibwezi Ward", "Mutha Ward", "Ikanga/Kyatune Ward", "Kyuu Ward"],
        "Muumonikyusu": ["Muumonikyusu Ward", "Nzambani Ward", "Waita Ward"],
        "Mwingi Central": ["Kyome/Thaana Ward", "Mwingi Central Ward", "Nguni Ward", "Nuuni Ward"],
        "Mwingi North": ["Tseikuru Ward", "Mwingi North Ward", "Kyuso Ward", "" /* Add other wards */],
        "Mwingi West": ["Migwani Ward", "Thitani Ward", "Kalyambeu Ward", "Ngaie Ward"],
        "Nzambani": ["Nzambani Ward", "Kitui East Ward", "Mui Ward"],
        "Tseikuru": ["Tseikuru Ward", "Mwingi North Ward", "Ngomeni Ward"]
    },
    "Kwale": {
        "Kinango": ["Mwavumbo Ward", "Kinango Ward", "Puma Ward", "Ndavaya Ward"],
        "Lungalunga": ["Pongwe/Kikoneni Ward", "Mwereni Ward", "Dzombo Ward", "Lungalunga Ward"],
        "Msambweni": ["Msambweni Ward", "Gombato/Bongwe Ward", "Ramisi Ward", "Kinondo Ward"],
        "Matuga": ["Tiwi Ward", "Waa/Ng'ombeni Ward", "Kubo South Ward", "Kubo North Ward"]
    },
    "Laikipia": {
        "Laikipia Central": ["Nanyuki Ward", "Thingithu Ward", "Umande Ward", "Marmanet Ward"],
        "Laikipia East": ["Ngobit Ward", "Tigithi Ward", "Mukogodo East Ward", "Mukogodo West Ward"],
        "Laikipia North": ["Sosian Ward", "Sebi Ward", "Ilmotiok Ward", "Segera Ward"],
        "Laikipia West": ["Rumuruti Ward", "Ol-Moran Ward", "Maji Mazuri Ward", "Salama Ward"],
        "Nyahururu": ["Nyahururu Ward", "Ol Joro Orok Ward", "Rumuruti Ward"]
    },
    "Lamu": {
        "Lamu East": ["Faza Ward", "Pate Ward", "Kizingitini Ward"],
        "Lamu West": ["Shella Ward", "Mkomani Ward", "Hindi Ward", "Witu Ward", "Bahari Ward"]
    },
    "Machakos": {
        "Kathiani": ["Kathiani Ward", "Lower Kaewa/Mavoko Ward", "Kaewa/Mavoko Ward", "" /* Add other wards */],
        "Machakos Town": ["Kalama Ward", "Mumbuni North Ward", "Mumbuni South Ward", "Machakos Central Ward", "Mutituni Ward", "Kola Ward"],
        "Masinga": ["Masinga Central Ward", "Masinga North Ward", "Kithyoko Ward", "Kivaa Ward"],
        "Matungulu": ["Matungulu North Ward", "Matungulu East Ward", "Matungulu West Ward", "Tala Ward"],
        "Mavoko": ["Athi River Ward", "Kinanie Ward", "Syokimau/Mlolongo Ward", "Mlolongo Ward", "" /* Add other wards */],
        "Mwala": ["Mwala Ward", "Wamunyu Ward", "Makutano/Mitaboni Ward", "Masii Ward"],
        "Yatta": ["Yatta/Kithimani Ward", "Katangi Ward", "Nolwe Ward", "Kivandini Ward"]
    },
    "Makueni": {
        "Kaiti": ["Ukia Ward", "Kee Ward", "Ivingoni/Nzambani Ward", "" /* Add other wards */],
        "Kibwezi East": ["Makindu Ward", "Kibwezi West Ward", "Nguumo Ward", "" /* Add other wards */],
        "Kibwezi West": ["Chyulu Ward", "Nzaui/Kilili/Kalamba Ward", "Masongaleni Ward", "" /* Add other wards */],
        "Kilome": ["Ukia Ward", "Kiambu Ward", "Ivingoni/Nzambani Ward"],
        "Makueni": ["Wote Ward", "Wamunyu Ward", "Kako/Waita Ward", "" /* Add other wards */],
        "Mbooni": ["Mbooni Ward", "Tulimani Ward", "Kisau/Kiteta Ward", "Kithungo/Kitundu Ward"]
    },
    "Mandera": {
        "Banissa": ["Banissa Ward", "Guba Ward", "Sarman Ward", "Derkhale Ward"],
        "Lafey": ["Lafey Ward", "Fino Ward", "Warankara Ward", "Arohle Ward"],
        "Mandera East": ["Mandera North Ward", "Mandera West Ward", "Mandera East Ward", "Township Ward", "" /* Add other wards */],
        "Mandera North": ["Rhamu Ward", "Ashabito Ward", "Guticha Ward", "Garbitulla Ward"],
        "Mandera South": ["Shimbir Fatuma Ward", "Lafey Ward", "Elwak South Ward", "" /* Add other wards */],
        "Mandera West": ["Takaba North Ward", "Takaba South Ward", "Dandu Ward", "" /* Add other wards */]
    },
    "Marsabit": {
        "Laisamis": ["Laisamis Ward", "Kargi/Korr/Ngurnit Ward", "Logologo Ward", "" /* Add other wards */],
        "Moyale": ["Moyale Town Ward", "Golbo Ward", "Butiye Ward", "Sololo Ward"],
        "North Horr": ["North Horr Ward", "Maikona Ward", "Turbi Ward", "Dukana Ward"],
        "Saku": ["Saku Ward", "Sagante/Jaldesa Ward", "Karare Ward"]
    },
    "Meru": {
        "Buuri": ["Buuri West Ward", "Buuri East Ward", "Kiguchwa Ward", "Ruiri/Rwarera Ward", "Ntima East Ward", "" /* Add other wards */],
        "Igembe Central": ["Igembe Central Ward", "Akachiu Ward", "Kiegoi/Antuambui Ward", "Athiru Gaiti Ward"],
        "Igembe North": ["Igembe North Ward", "Laare Ward", "Amwathi Ward", "Antuambui Ward"],
        "Igembe South": ["Maua Ward", "Kiegoi/Antuambui Ward", "Athiru Gaiti Ward", "Akachiu Ward"],
        "Imenti Central": ["Abogeta East Ward", "Abogeta West Ward", "Kigumo Ward", "Mitunguu Ward"],
        "Imenti North": ["Municipality Ward", "Imenti North Ward", "Abothuguchi Central Ward", "Abothuguchi West Ward"],
        "Imenti South": ["Imenti South Ward", "Nkuene Ward", "Mwangathia Ward", "Kanyakine Ward", "" /* Add other wards */],
        "Tigania East": ["Kiguchwa Ward", "Mikinduri Ward", "Muthara Ward", "Karama Ward"],
        "Tigania West": ["Athwana Ward", "Akithi Ward", "Kianjai Ward", "Mbeu Ward"]
    },
    "Migori": {
        "Awendo": ["Awendo Ward", "North Sakwa Ward", "South Sakwa Ward", "God Jope Ward"],
        "Kuria East": ["Nyabasi East Ward", "Nyabasi West Ward", "Ntimaru East Ward", "Ntimaru West Ward"],
        "Kuria West": ["Masaba Ward", "Tagare Ward", "Makerero Ward", "Gokeharaka/Getenga Ward"],
        "Nyatike": ["Nyatike East Ward", "Nyatike West Ward", "Kanyamkago Ward", "" /* Add other wards */],
        "Rongo": ["Rongo Ward", "North Kamagambo Ward", "South Kamagambo Ward", "East Kamagambo Ward"],
        "Suna East": ["Kakrao Ward", "East Suna Ward", "Komolo Rume Ward", "" /* Add other wards */],
        "Suna West": ["Wasweta II Ward", "Ragana/Ombo Ward", "Kaler Ward", "" /* Add other wards */],
        "Uriri": ["Uriri Ward", "North Kanyamkago Ward", "South Kanyamkago Ward", "Central Kanyamkago Ward"]
    },
    "Mombasa": {
        "Changamwe": ["Chaani Ward", "Kipevu Ward", "Miritini Ward", "Port Reitz Ward", "Changamwe Ward"],
        "Jomvu": ["Jomvu Kuu Ward", "Mikindani Ward", "Miritini Ward"],
        "Kisauni": ["Bamburi Ward", "Mtopanga Ward", "Mwandoni Ward", "Shanzu Ward", "Kadongo Ward", "" /* Add other wards */],
        "Likoni": ["Bofu Ward", "Likoni Ward", "Mtongwe Ward", "Shika Adabu Ward", "Timbwani Ward"],
        "Mvita": ["Majengo Ward", "Mji Wa Kale/Old Town Ward", "Shimanzi/Ganjoni Ward", "Tononoka Ward", "Mvita Ward"],
        "Nyali": ["Frere Town Ward", "Kadzonzo Ward", "Kisauni Ward", "Kongowea Ward", "Nyali Ward"]
    },
    "Murang'a": {
        "Gatanga": ["Gatanga Ward", "Gatara Ward", "Kibugi Ward", "Mangu Ward", "Mugumo-ini Ward"],
        "Kandara": ["Kandara Ward", "Githunguri Ward", "Ng'araria Ward", "Kihumbu-ini Ward", "Muruka Ward"],
        "Kangema": ["Kanyenya-ini Ward", "Muguru Ward", "Rwathia Ward"],
        "Kigumo": ["Kigumo Ward", "Kahumbu Ward", "Kangari Ward", "Kanyenya-ini Ward", "" /* Add other wards */],
        "Kiharu": ["Kiharu Ward", "Mugoiri Ward", "Wangu Ward", "Kahuro Ward", "Munyoro Ward"],
        "Maragua": ["Maragua Ridge Ward", "Mikalai Ward", "Ichagaki Ward", "Kambiti Ward"],
        "Mathioya": ["Gatara Ward", "Kiriti Ward", "Kiru Ward", "Njumbi Ward"],
        "Murang'a South": ["Makuyu Ward", "Kambiti Ward", "Kandara Ward", "" /* Add other wards */]
    },
    "Nairobi": {
        "Dagoretti North": ["Kileleshwa Ward", "Kawangware Ward", "Kilimani Ward", "Lavington Ward", "Gatina Ward"],
        "Dagoretti South": ["Mutuini Ward", "Ngand'o Ward", "Waithaka Ward", "Riruta Ward"],
        "Embakasi Central": ["Kariobangi South Ward", "Dandora Phase I Ward", "Dandora Phase II Ward", "Dandora Phase III Ward", "Dandora Phase IV Ward"],
        "Embakasi East": ["Embakasi Ward", "Lower Savannah Ward", "Upper Savannah Ward", "Umoja I Ward", "Umoja II Ward"],
        "Embakasi North": ["Kariobangi North Ward", "Dandora Area I Ward", "Dandora Area II Ward", "Dandora Area III Ward", "Dandora Area IV Ward"],
        "Embakasi South": ["Imara Daima Ward", "Kware Ward", "Mihango Ward", "Pipeline Ward"],
        "Embakasi West": ["Umoja Ward", "Mihango Ward", "Kware Ward", "Embakasi West Ward"],
        "Kamukunji": ["Eastleigh North Ward", "Eastleigh South Ward", "Pumwani Ward", "Airbase Ward", "Biashara Ward"],
        "Kasarani": ["Kasarani Ward", "Mwiki Ward", "Clay City Ward", "Roysambu Ward", "Ruaraka Ward"],
        "Kibra": ["Laini Saba Ward", "Lindi Ward", "Makina Ward", "Sarang'ombe Ward", "Woodley/Kenyatta Golf Course Ward"],
        "Lang'ata": ["Karen Ward", "Mugumo-ini Ward", "South C Ward", "Nairobi West Ward", "Lang'ata Ward"],
        "Makadara": ["Makongeni Ward", "Kaloleni/Mbotela Ward", "Hamza Ward", "Maringo/Hamza Ward", "Viwandani Ward"],
        "Mathare": ["Huruma Ward", "Mabatini Ward", "Mathare North Ward", "Ngei Ward", "" /* Add other wards */],
        "Roysambu": ["Roysambu Ward", "Githurai Ward", "Kahawa West Ward", "Kahawa North Ward", "Zimmerman Ward"],
        "Ruaraka": ["Baba Dogo Ward", "Korogocho Ward", "Mathare North Ward", "Dandora Ward", "" /* Add other wards */],
        "Starehe": ["Central Ward", "Ngara Ward", "Pangani Ward", "Landimawe Ward", "Ziwani/Kariokor Ward"],
        "Westlands": ["Kitisuru Ward", "Parklands/Highridge Ward", "Karura Ward", "Kangemi Ward", "Mountain View Ward", "Waiyaki Way Ward"]
    },
    "Nakuru": {
        "Bahati": ["Bahati Ward", "Dundori Ward", "Kabatini Ward", "Kiamaina Ward"],
        "Gilgil": ["Gilgil Ward", "Elementaita Ward", "Malewa West Ward", "Murindat Ward"],
        "Kuresoi North": ["Kipkelion Ward", "Kuresoi North Ward", "Amalo Ward", "Keringet Ward"],
        "Kuresoi South": ["Keringet Ward", "Amalo Ward", "Tinet Ward", "Kuresoi South Ward"],
        "Naivasha": ["Biashara Ward", "Hells Gate Ward", "Lakeview Ward", "Maimahiu Ward", "Naivasha East Ward", "Naivasha West Ward", "Viwandani Ward"],
        "Nakuru Town East": ["Biashara Ward", "Flamingo Ward", "Kivumbini Ward", "Lake View Ward", "Shabab Ward"],
        "Nakuru Town West": ["Barut Ward", "London Ward", "Shauri Yako Ward", "Kapkures Ward"],
        "Njoro": ["Njoro Ward", "Mauche Ward", "Lare Ward", "Kihingo Ward"],
        "Rongai": ["Rongai Ward", "Solai Ward", "Visoi Ward", "Kampi Ya Moto Ward"],
        "Subukia": ["Subukia Ward", "Waseges Ward", "Kabazi Ward"]
    },
    "Nandi": {
        "Aldai": ["Kaptumo/Kaboi Ward", "Koyochim Ward", "Terik Ward", "Ndonyiro Ward"],
        "Chesumei": ["Chemundu/Sangalo Ward", "Kapsabet Ward", "Kosirai Ward", "Kiptuya Ward"],
        "Emgwen": ["Kapsabet/Kamwega Ward", "Kilibwoni Ward", "Kapkanga Ward", "Kigumo Ward"],
        "Mosop": ["Kurgung/Surungai Ward", "Chemundu/Sangalo Ward", "Kabiyet Ward", "Kurgung Ward", "Sarupio Ward"],
        "Nandi Hills": ["Nandi Hills Ward", "Chepkumia Ward", "Kapsimotwo Ward", "Tindiret Ward"],
        "Tindiret": ["Tindiret Ward", "Meteitei Ward", "Chemase Ward", "Kapsimotwo Ward"]
    },
    "Narok": {
        "Kilgoris": ["Kilgoris Central Ward", "Keyian Ward", "Angata Barikoi Ward", "Shankoe Ward"],
        "Narok East": ["Ololulunga Ward", "Maji Moto Ward", "Naikarra Ward", "" /* Add other wards */],
        "Narok North": ["Narok Town Ward", "Olopito Ward", "Nkareta Ward", "Olorropil Ward"],
        "Narok South": ["Loita Ward", "Maji Moto Ward", "Sogoo Ward", "Melelo Ward"],
        "Narok West": ["Loita Ward", "Siana Ward", "Lemek Ward", "Nkareta Ward", "Olesholey Ward"],
        "Emurua Dikirr": ["Ilkerin Ward", "Ololulunga Ward", "Angata Barikoi Ward"]
    },
    "Nyamira": {
        "Borabu": ["Rangenyo Ward", "Nyansiongo Ward", "Borabu Ward", "Manga Ward"],
        "Masaba North": ["Masaba North Ward", "Kegogi Ward", "Bokeira Ward", "Mugirango West Ward"],
        " Missing Nyamira North " : ["Township Ward", "Bogichora Ward", "Bokeira Ward", "Nyankuru Ward"], // Placeholder
        "Nyamira South": ["Bokimonge Ward", "Bonyamatuta Ward", "Kebirigo Ward", "Nyamira Township Ward"],
        "Rigoma": ["Rigoma Ward", "Gesiaga Ward", "Nyabite Ward", "Bosamaro Ward"],
        "West Mugirango": ["Nyabite Ward", "Getare Ward", "Rigoma Ward", "Ekerenyo Ward"]
    },
    "Nyandarua": {
        "Kinangop": ["Githabai Ward", "Kinangop Central Ward", "Kinangop North Ward", "Kinangop South Ward", "Njabini/Kiburu Ward"],
        "Kipipiri": ["Geta Ward", "Kipipiri Ward", "Wanjohi Ward", "Githioro Ward"],
        "Ndaragwa": ["Ndaragwa Central Ward", "Leshau/Pondo Ward", "Shamata Ward", "Dundori Ward"],
        "Ol Kalou": ["Ol Kalou Town Ward", "Githunguri Ward", "Karau Ward", "Rurii Ward", "Kaimbaga Ward"],
        "Ol Joro Orok": ["Ol Joro Orok Ward", "Gatimu Ward", "Murungaru Ward", "Passenga Ward"]
    },
    "Nyeri": {
        "Kieni East": ["Mweiga Ward", "Naromoru/Kiamariga Ward", "Mwiyogo/Endarasha Ward", "Amboni/Kanyurira Ward"],
        "Kieni West": ["Mukurweini Central Ward", "Mukurweini West Ward", "Gikondi Ward", "Muhito Ward"],
        "Mathira East": ["Kirimukuyu Ward", "Ruguru Ward", "Mutira Ward", "Konyu Ward"],
        "Mathira West": ["Magutu Ward", "Mukurweini Central Ward", "Ngorano Ward", "" /* Add other wards */],
        "Mukurweini": ["Gikondi Ward", "Muhito Ward", "Mukurweini Central Ward", "Mukurweini West Ward"],
        "Nyeri Town": ["Rware Ward", "Kamakwa/Mukaro Ward", "Gatitu/Muruguru Ward", "" /* Add other wards */],
        "Othaya": ["Mahiga Ward", "Iyego Ward", "Chinga Ward", "Othaya Ward"],
        "Tetu": ["Aguthi/Gaaki Ward", "Kiganjo/Mathira Ward", "Karundu/Mugunda Ward", "Muthuaini Ward"]
    },
    "Samburu": {
        "Samburu East": ["Wamba East Ward", "Wamba West Ward", "Wamba North Ward", "Lodokejek Ward"],
        "Samburu North": ["Baragoi Ward", "Nachola Ward", "Ndoto Ward", "Nyiro Ward"],
        "Samburu West": ["Lodokejek Ward", "Maralal Old Town Ward", "Baawa Ward", "Loosuk Ward", "Poron Ward"]
    },
    "Siaya": {
        "Alego Usonga": ["Siaya Township Ward", "Alego Usonga Ward", "Usonga Ward", "Karemo Ward", "" /* Add other wards */],
        "Bondo": ["Bondo Town Ward", "Usigu Ward", "Nyatike Ward", "South Sakwa Ward"],
        "Gem": ["North Gem Ward", "Central Gem Ward", "South Gem Ward", "Yala Township Ward"],
        "Rarieda": ["East Asembo Ward", "West Asembo Ward", "Asembo Central Ward", "South East Rarieda Ward"],
        "Ugenya": ["East Ugenya Ward", "North Ugenya Ward", "West Ugenya Ward", "Ugenya Ward"],
        "Ugunja": ["Ugunja Ward", "Sidindi Ward", "Ambira/Murambo/Ukhoh Ward"]
    },
    "Taita-Taveta": {
        "Mwatate": ["Mwatate Ward", "Bura Ward", "Wumingu/Bura Ward", "Ronge Ward"],
        "Taveta": ["Taveta Ward", "Mata Ward", "Chala Ward", "Mboghoni Ward"],
        "Voi": ["Voi Ward", "Sagalla Ward", "Mbololo Ward", "Kasigau Ward", "Marungu Ward"],
        "Wundanyi": ["Wundanyi/Mbale Ward", "Wumingu/Kishushe Ward", "Werugha Ward", "Mgange/Mwanda Ward"]
    },
    "Tana River": {
        "Bura": ["Chewele/Mikinduni Ward", "Bangale Ward", "Sala Ward", "Madogo Ward"],
        "Galole": ["Garsen South Ward", "Garsen Central Ward", "Chewele Ward", "Mikinduni Ward"],
        "Garsen": ["Garsen Central Ward", "Garsen South Ward", "Kipini East Ward", "Kipini West Ward"]
    },
    "Tharaka-Nithi": {
        "Chuka Igambang'ombe": ["Karingani Ward", "Magumoni Ward", "Muthambi Ward", "Chuka Ward"],
        "Maara": ["Chogoria Ward", "Mitheru Ward", "Mwimbi Ward", "Gatunga Ward"],
        "Tharaka Nithi": ["Tharaka Ward", "Mutino Ward", "Chiakariga Ward", "Marimanti Ward"]
    },
    "Trans-Nzoia": {
        "Cherangany": ["Motosiet Ward", "Sinyerere Ward", "Kaplamai Ward", "Sitatunga Ward"],
        "Endebess": ["Endebess Ward", "Kwanza Ward", "Kinyoro Ward", "Saboti Ward"],
        "Kiminini": ["Waitaluk Ward", "Kiminini Ward", "Saboti Ward", "" /* Add other wards */],
        "Kwanza": ["Kwanza Ward", "Kiminini Ward", "Endebess Ward", "Chepsiro/Kiptoror Ward"],
        "Saboti": ["Saboti Ward", "Matisi Ward", "Tuwan Ward", "Kinyoro Ward"]
    },
    "Turkana": {
        "Turkana Central": ["Lodwar Town Ward", "Loima Ward", "Turkwel Ward", "Kerio Delta Ward"],
        "Turkana East": ["Kapedo/Napei Ward", "Kapedo/Napei Ward", "Lokori/Kochodin Ward", "" /* Add other wards */],
        "Turkana North": ["Lokitaung Ward", "Lokichoggio Ward", "Kibish Ward", "" /* Add other wards */],
        "Turkana South": ["Kalokol Ward", "Katilu Ward", "Lokichar Ward", "" /* Add other wards */],
        "Turkana West": ["Turkana West Ward", "Loima Ward", "Kakuma Ward", "Lopur Ward"],
        "Loima": ["Loima Ward", "Kotaruk/Lobei Ward", "Turkwel Ward"]
    },
    "Uasin Gishu": {
        "Ainabkoi": ["Ainabkoi/Olare Ward", "Kaptagat Ward", "Kapsoya Ward", "Plateau Ward"],
        "Kapseret": ["Kapseret/Simat Ward", "Cheptiret/Kipchamo Ward", "Racecourse Ward", "Langas Ward"],
        "Kesses": ["Kesses Ward", "Tarakwa Ward", "Cheptiret/Kipchamo Ward", "" /* Add other wards */],
        "Moiben": ["Moiben Ward", "Tembely/Chemase Ward", "Sergoit Ward", "Chebarus Ward"],
        "Soy": ["Soy Ward", "Ziwa Ward", "Kiplombe Ward", "Kapkures Ward"],
        "Turbo": ["Turbo Ward", "Kamagut Ward", "Tapsagoi Ward", "Kipsomba Ward"]
    },
    "Vihiga": {
        "Emuhaya": ["Central Bunyore Ward", "West Bunyore Ward", "East Bunyore Ward", "Itumbi/Central Bunyore Ward"],
        "Luanda": ["Luanda Town Ward", "Emabungo Ward", "Wamuluma Ward", "Mwibona Ward"],
        "Sabatia": ["Chavakali Ward", "Izava/Lyaduywa Ward", "North Maragoli Ward", "Sabatia Ward", "" /* Add other wards */],
        "Vihiga": ["Central Maragoli Ward", "North Maragoli Ward", "South Maragoli Ward", "" /* Add other wards */],
        "Hamisi": ["Hamisi Ward", "Jepkoyai Ward", "Gamoi Ward", "Shirugu/Mugai Ward"]
    },
    "Wajir": {
        "Eldas": ["Eldas Ward", "Boru Ward", "" /* Add other wards */],
        "Habaswein": ["Habaswein Ward", "Abakore Ward", "Lagbogol Ward", "" /* Add other wards */],
        "Lafey": ["Lafey Ward", "Fino Ward", "Warankara Ward", "" /* Add other wards */],
        "Mdira": ["Mdira Ward", "Sarman Ward", "" /* Add other wards */], // Note: Mdira might be a location, not a full subcounty. Data verification needed.
        "Tarbaj": ["Tarbaj Ward", "Sarman Ward", "" /* Add other wards */],
        "Wajir East": ["Wajir East Ward", "Township Ward", "Hospital Ward", "" /* Add other wards */],
        "Wajir North": ["Bute Ward", "Korondile Ward", "Danaba Ward", "" /* Add other wards */],
        "Wajir South": ["Lagbogol Ward", "Benane Ward", "Diff Ward", "" /* Add other wards */],
        "Wajir West": ["Arbajahan Ward", "Ganyure Ward", "" /* Add other wards */]
    },
    "West Pokot": {
        "Kapenguria": ["Kapenguria Ward", "Mnagei Ward", "Riwo Ward", "" /* Add other wards */],
        "Kacheliba": ["Kacheliba Ward", "Suam Ward", "Kasei Ward", "" /* Add other wards */],
        "Pokot South": ["Sook Ward", "Wei Wei Ward", "" /* Add other wards */],
        "Sigor": ["Sigor Ward", "Cheptulel Ward", "Sekerr Ward", "" /* Add other wards */]
    }
};


        document.addEventListener('DOMContentLoaded', function () {
            const countySelect = document.getElementById('countySelect');
            const subCountySelect = document.getElementById('subCountySelect');
            const ward = document.getElementById('ward');

            function populateCounties() {
                for (const county in countyData) {
                    const option = document.createElement('option');
                    option.value = county;
                    option.textContent = county;
                    countySelect.appendChild(option);
                }
            }

            function populateSubCounties(selectedCounty) {
                subCountySelect.innerHTML = '<option value="" disabled selected>-- Select Your Sub-County --</option>';
                ward.innerHTML = '<option value="" disabled selected>-- Select Your Ward --</option>';
                ward.disabled = true;

                if (selectedCounty && countyData[selectedCounty]) {
                    const subCounties = Object.keys(countyData[selectedCounty]);
                    subCounties.forEach(sub => {
                        const option = document.createElement('option');
                        option.value = sub;
                        option.textContent = sub;
                        subCountySelect.appendChild(option);
                    });
                    subCountySelect.disabled = false;
                } else {
                    subCountySelect.disabled = true;
                }
            }

            function populateEstates(selectedCounty, selectedSubCounty) {
                ward.innerHTML = '<option value="" disabled selected>-- Select Your Ward --</option>';

                if (
                    selectedCounty &&
                    selectedSubCounty &&
                    countyData[selectedCounty] &&
                    countyData[selectedCounty][selectedSubCounty]
                ) {
                    countyData[selectedCounty][selectedSubCounty].forEach(estate => {
                        const option = document.createElement('option');
                        option.value = estate;
                        option.textContent = estate;
                        ward.appendChild(option);
                    });
                    ward.disabled = false;
                } else {
                ward.disabled = true;
                }
            }

            countySelect.addEventListener('change', function () {
                const selectedCounty = this.value;
                populateSubCounties(selectedCounty);
            });

            subCountySelect.addEventListener('change', function () {
                const selectedCounty = countySelect.value;
                const selectedSubCounty = this.value;
                populateEstates(selectedCounty, selectedSubCounty);
            });

            populateCounties();
        });
    </script>
<script>
    const arbolitosData = {
        <?php
        foreach ($arbolitosData as $groupName => $details) {
            echo "'" . htmlspecialchars($groupName) . "': {
                leader_name: '" . htmlspecialchars($details['leader_name']) . "',
                leader_phone: '" . htmlspecialchars($details['leader_phone']) . "'
            },";
        }
        ?>
    };

    function populateLeaderDetails() {
        const groupSelect = document.getElementById('arbolitos_group');
        const leaderNameInput = document.getElementById('leader_name');
        const leaderPhoneInput = document.getElementById('leader_phone_number');
        const selectedGroup = groupSelect.value;
        
        if (selectedGroup && arbolitosData[selectedGroup]) {
            leaderNameInput.value = arbolitosData[selectedGroup].leader_name;
            leaderPhoneInput.value = arbolitosData[selectedGroup].leader_phone;
        } else {
            leaderNameInput.value = '';
            leaderPhoneInput.value = '';
        }
    }
    
    function toggleTravelFields() {
        const modeOfTravel = document.getElementById('mode-of-travel').value;
        const motorbikeFields = document.getElementById('motorbike-fields');
        const vehicleFields = document.getElementById('vehicle-fields');
        const commonVehicleFields = document.getElementById('common-vehicle-fields');
        const fuelTypeSelect = document.getElementById('fuel_type');
        
        motorbikeFields.style.display = 'none';
        vehicleFields.style.display = 'none';
        commonVehicleFields.style.display = 'none';

        if (modeOfTravel === 'Motorbike') {
            motorbikeFields.style.display = 'block';
            commonVehicleFields.style.display = 'block';
            toggleMakeDropdown();
        } else if (modeOfTravel === 'vehicle') {
            vehicleFields.style.display = 'block';
            commonVehicleFields.style.display = 'block';
        }
    }

    function toggleMakeDropdown() {
        const fuelType = document.getElementById('fuel_type').value;
        const electricMakeGroup = document.getElementById('electric-make-group');
        const electricModelGroup = document.getElementById('electric-model-group');
        const petrolMakeGroup = document.getElementById('petrol-make-group');
        const motorcycleModelGroup = document.getElementById('motorcycle-model-group');
        
        // Hide all make/model groups initially
        electricMakeGroup.style.display = 'none';
        electricModelGroup.style.display = 'none';
        petrolMakeGroup.style.display = 'none';
        motorcycleModelGroup.style.display = 'none';

        if (fuelType === 'Electric') {
            electricMakeGroup.style.display = 'block';
            electricModelGroup.style.display = 'block';
        } else if (fuelType === 'Petrol') {
            petrolMakeGroup.style.display = 'block';
            motorcycleModelGroup.style.display = 'block';
        }
    }

    // Call the functions on page load to set initial state based on pre-filled data
    document.addEventListener('DOMContentLoaded', () => {
        populateLeaderDetails();
        toggleTravelFields();
    });
</script>
<script>
  function updateSaccoInfo() {
    const select = document.getElementById('sacco_select');
    const selectedOption = select.options[select.selectedIndex];
    
    const chairmanInput = document.getElementById('chairman_name');
    const phoneInput = document.getElementById('chairman_phone');

    if (selectedOption.value !== "") {
        // Pull values from data attributes assigned in PHP
        chairmanInput.value = selectedOption.getAttribute('data-chairman');
        phoneInput.value = selectedOption.getAttribute('data-phone');
    } else {
        // Clear fields if the "Select" placeholder is chosen
        chairmanInput.value = "";
        phoneInput.value = "";
    }
}
</script>


<script>
  function updateLeaderInfo() {
    const select = document.getElementById('group_select');
    const selectedOption = select.options[select.selectedIndex];
    
    const leaderInput = document.getElementById('leader_name');
    const phoneInput = document.getElementById('leader_phone');

    if (selectedOption.value !== "") {
        // Pull values from data attributes
        leaderInput.value = selectedOption.getAttribute('data-leader');
        phoneInput.value = selectedOption.getAttribute('data-phone');
    } else {
        // Clear fields if no group is selected
        leaderInput.value = "";
        phoneInput.value = "";
    }
}
        // Populate the dropdown when the page loads.
        document.addEventListener('DOMContentLoaded', populateGroupDropdown);

        // This event listener is already attached via the onchange attribute in the HTML.
        // document.getElementById('arbolitos_group').addEventListener('change', populateLeaderDetails);


    function toggleTravelFields() {
        const modeOfTravel = document.getElementById('mode-of-travel').value;
        const motorbikeFields = document.getElementById('motorbike-fields');
        const vehicleFields = document.getElementById('vehicle-fields');
        const commonVehicleFields = document.getElementById('common-vehicle-fields');

        // Hide all fields initially
        motorbikeFields.style.display = 'none';
        vehicleFields.style.display = 'none';
        commonVehicleFields.style.display = 'none';

        // Set inputs within hidden fields as not required
        setRequired(motorbikeFields, false);
        setRequired(vehicleFields, false);
        setRequired(commonVehicleFields, false);

        if (modeOfTravel === 'Motorbike') {
            motorbikeFields.style.display = 'block';
            commonVehicleFields.style.display = 'block';
            setRequired(motorbikeFields, true);
            setRequired(commonVehicleFields, true);
            toggleMakeDropdown(); // Ensure make dropdown is correctly shown/hidden for motorbike
        } else if (modeOfTravel === 'vehicle') {
            vehicleFields.style.display = 'block';
            commonVehicleFields.style.display = 'block';
            setRequired(vehicleFields, true);
            setRequired(commonVehicleFields, true);
        }
    }

    function toggleMakeDropdown() {
        const fuelType = document.getElementById('fuel_type').value;
        const electricMakeGroup = document.getElementById('electric-make-group');
        const electricModelGroup = document.getElementById('electric-model-group');
        const petrolMakeGroup = document.getElementById('petrol-make-group');
        const motorcycleModelGroup = document.getElementById('motorcycle-model-group');

        // Hide all make/model fields initially
        electricMakeGroup.style.display = 'none';
        electricModelGroup.style.display = 'none';
        petrolMakeGroup.style.display = 'none';
        motorcycleModelGroup.style.display = 'none';

        // Set inputs within hidden make/model fields as not required
        setRequired(electricMakeGroup, false);
        setRequired(electricModelGroup, false);
        setRequired(petrolMakeGroup, false);
        setRequired(motorcycleModelGroup, false);


        if (fuelType === 'Electric') {
            electricMakeGroup.style.display = 'block';
            electricModelGroup.style.display = 'block';
            setRequired(electricMakeGroup, true);
            setRequired(electricModelGroup, true);
        } else if (fuelType === 'Petrol') {
            petrolMakeGroup.style.display = 'block';
            motorcycleModelGroup.style.display = 'block';
            setRequired(petrolMakeGroup, true);
            setRequired(motorcycleModelGroup, true);
        }
    }

    // Helper function to set or unset 'required' attribute for inputs within a container
    function setRequired(container, isRequired) {
        const inputs = container.querySelectorAll('input, select');
        inputs.forEach(input => {
            if (isRequired) {
                input.setAttribute('required', 'required');
            } else {
                input.removeAttribute('required');
            }
        });
    }

    // Call initial toggles on page load to set correct states based on fetched data
    window.onload = function() {
        toggleTravelFields();
        toggleMakeDropdown();
    };
</script>

</body>
</html>