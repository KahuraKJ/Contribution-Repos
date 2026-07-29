<?php
session_start ();
include 'header.php';
include 'connect.php';

// Enable error reporting (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['id_no'])) {
    header('Location: index.php');
    exit;
}

$id_no = $_SESSION['id_no'];

// Initialize data array and status flag
$data = null;
$source_table = null;

// --------------------------------------------------------------------------------
// 1. ATTEMPT FETCH FROM beneficiaries2 (Primary table)
// --------------------------------------------------------------------------------
$sql2 = "SELECT * FROM beneficiaries2 WHERE id_no = ?";
$stmt2 = $con->prepare($sql2);
$stmt2->bind_param("s", $id_no);
$stmt2->execute();
$result2 = $stmt2->get_result();

if ($result2->num_rows > 0) {
    $data = $result2->fetch_assoc();
    $source_table = 'beneficiaries2';
}
$stmt2->close();


// --------------------------------------------------------------------------------
// 2. FALLBACK FETCH FROM beneficiaries (Legacy table)
// --------------------------------------------------------------------------------
if ($data === null) {
    // Only select common fields, as the structure is likely different and lacks JSON
    $sql1 = "SELECT father, mother, spouse FROM beneficiaries WHERE id_no = ?"; 
    $stmt1 = $con->prepare($sql1);
    $stmt1->bind_param("s", $id_no);
    $stmt1->execute();
    $result1 = $stmt1->get_result();

    if ($result1->num_rows > 0) {
        // Fetch and merge the simpler data into the $data array
        $legacy_data = $result1->fetch_assoc();
        
        // Populate only the common fields found in the old table.
        // Default statuses to 'N/A (Legacy)' if not present in the old table.
        $data = array_merge([
            'father' => null, 'mother' => null, 'spouse' => null, 
            'father_status' => 'N/A (Legacy)', 'mother_status' => 'N/A (Legacy)', 
            'spouse_status' => 'N/A (Legacy)', 
            'father_in_law' => null, 'mother_in_law' => null, // These are probably null in legacy
            'father_in_law_status' => 'N/A (Legacy)', 'mother_in_law_status' => 'N/A (Legacy)',
            'children' => '[]', 'wives' => '[]'
        ], $legacy_data); 

        $source_table = 'beneficiaries';
    }
    $stmt1->close();
}

// --------------------------------------------------------------------------------
// 3. CHECK RESULT AND DECODE (Handle all cases)
// --------------------------------------------------------------------------------
if ($data === null) {
    // No data found in either table
    $con->close();
    die("<div style='font-family: Arial; text-align: center; padding: 20px;'><h3>❌ No beneficiary data found for ID: " . htmlspecialchars($id_no) . " in any table.</h3></div>");
}

// Decode JSON fields (only present/useful if the source was beneficiaries2)
$children = json_decode($data['children'] ?? '[]', true);
$wives = json_decode($data['wives'] ?? '[]', true);

$con->close();

/**
 * Helper function to display status with color, but only if the name is present.
 * * @param string|null $name The name of the relative.
 * @param string $status The status (e.g., 'Alive', 'Deceased', 'N/A (Legacy)').
 * @return string HTML span tag with colored status or 'N/A'.
 */
function display_status($name, $status) {
    // Return N/A if the name field is empty (no relative recorded)
    if (empty($name)) {
        return "N/A"; 
    }
    
    $status = htmlspecialchars($status);
    $class = '';

    if ($status === 'Deceased') {
        $class = 'status-deceased';
    } elseif (str_contains($status, 'Legacy')) {
        $class = 'status-legacy';
    } else {
        // This covers 'Alive' and other valid statuses
        $class = 'status-alive';
    }

    return "<span class='$class'>$status</span>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beneficiaries for <?= htmlspecialchars($id_no) ?></title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background-color: #f4f7f6; 
           
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            padding: 30px;
        }
        h2 { 
            color: #1a73e8; 
            text-align: center;
            border-bottom: 3px solid #e0e0e0;
            padding-bottom: 15px;
            margin-bottom: 30px;
            font-weight: 600;
        }
        .section { 
            margin-bottom: 40px; 
            padding: 20px;
            border-radius: 8px;
            background-color: #fcfcfc;
            border: 1px solid #eee;
        }
        .section h3 {
            color: #34495e; 
            margin-top: 0;
            border-left: 4px solid #1a73e8;
            padding-left: 10px;
            margin-bottom: 20px;
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
        }
        th, td { 
            padding: 12px 15px; 
            text-align: left; 
            border-bottom: 1px solid #e0e0e0;
        }
        th { 
            background-color: #f8f8f8;
            color: #555;
            font-weight: 500;
            width: 30%; 
        }
        tr:last-child td {
            border-bottom: none;
        }
        .dynamic-table th {
            width: auto;
        }
        .status-alive {
            color: #008000; 
            font-weight: 600;
        }
        .status-deceased {
            color: #c0392b; 
            font-weight: 600;
        }
        .status-legacy {
            color: #f39c12; /* Orange for warning/partial data */
            font-weight: 600;
        }
        .wife-card {
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .wife-card h4 {
            color: #2980b9;
            margin-top: 0;
            margin-bottom: 10px;
            font-weight: 500;
        }
        .wife-card p {
            margin: 5px 0 0 0;
            font-size: 0.95em;
            color: #555;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 style="text-transform: uppercase;">Beneficiary Details Report ✨</h2>

    <div class="section">
        <h3>Primary Relatives</h3>
        <table>
            <tr>
                <th>Father</th>
                <?php $father = $data['father'] ?? null; ?>
                <td><?= htmlspecialchars($father ?? 'N/A') ?> 
                    (<?= display_status($father, $data['father_status'] ?? 'N/A') ?>)
                </td>
            </tr>
            <tr>
                <th>Mother</th>
                <?php $mother = $data['mother'] ?? null; ?>
                <td><?= htmlspecialchars($mother ?? 'N/A') ?> 
                    (<?= display_status($mother, $data['mother_status'] ?? 'N/A') ?>)
                </td>
            </tr>
            <tr>
                <th>Spouse</th>
                <?php $spouse = $data['spouse'] ?? null; ?>
                <td><?= htmlspecialchars($spouse ?? 'N/A') ?> 
                    (<?= display_status($spouse, $data['spouse_status'] ?? 'N/A') ?>)
                </td>
            </tr>
            <tr>
                <th>Father-in-law</th>
                <?php $father_in_law = $data['father_in_law'] ?? null; ?>
                <td><?= htmlspecialchars($father_in_law ?? 'N/A') ?> 
                    (<?= display_status($father_in_law, $data['father_in_law_status'] ?? 'N/A') ?>)
                </td>
            </tr>
            <tr>
                <th>Mother-in-law</th>
                <?php $mother_in_law = $data['mother_in_law'] ?? null; ?>
                <td><?= htmlspecialchars($mother_in_law ?? 'N/A') ?> 
                    (<?= display_status($mother_in_law, $data['mother_in_law_status'] ?? 'N/A') ?>)
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Children</h3>
        <?php if ($source_table === 'beneficiaries2' && !empty($children)): ?>
            <table class="dynamic-table">
                <tr><th>Name</th><th>Status</th></tr>
                <?php foreach ($children as $child): ?>
                    <tr>
                        <td><?= htmlspecialchars($child['name'] ?? 'N/A') ?></td>
                        <td><?= display_status($child['name'] ?? null, $child['status'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>
                <?php if ($source_table === 'beneficiaries'): ?>
                    Children data not available for legacy records.
                <?php else: ?>
                    No children recorded.
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h3>Other Wives</h3>
        <?php if ($source_table === 'beneficiaries2' && !empty($wives)): ?>
            <?php foreach ($wives as $wife): ?>
                <div class="wife-card">
                    <h4>
                        <?= htmlspecialchars($wife['name'] ?? 'N/A Wife') ?> 
                        (<?= display_status($wife['name'] ?? null, $wife['status'] ?? 'N/A') ?>)
                    </h4>
                    <?php if (!empty($wife['children'])): ?>
                        <p><strong>Children:</strong> <?= htmlspecialchars(implode(", ", $wife['children'])) ?></p>
                    <?php else: ?>
                        <p>No children listed for this wife.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>
                <?php if ($source_table === 'beneficiaries'): ?>
                    Wives data not available for legacy records.
                <?php else: ?>
                    No wives recorded.
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>

</div>

</body>
</html>