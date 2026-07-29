<?php
include 'connect.php';

$id_no = $_POST['id_no'];
$county = $_POST['county'];
$sub_county = $_POST['sub_county'];
$ward = $_POST['ward'];
$estate = $_POST['estate'];
$home_county = $_POST['home_county'];
$home_sub_county = $_POST['home_sub_county'];
$home_ward = $_POST['home_ward'];
$home_estate = $_POST['home_estate'];

// Check if record exists
$stmt = $con->prepare("SELECT id_no FROM residential WHERE id_no = ?");
$stmt->bind_param("s", $id_no);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // Update existing record
    $update = $con->prepare("UPDATE residential SET county=?, sub_county=?, ward=?, estate=?, home_county=?, home_sub_county=?, home_ward=?, home_estate=? WHERE id_no=?");
    $update->bind_param("sssssssss", $county, $sub_county, $ward, $estate, $home_county, $home_sub_county, $home_ward, $home_estate, $id_no);
    $update->execute();
    $message = "✅ Address updated successfully!";
} else {
    // Insert new record
    $insert = $con->prepare("INSERT INTO residential (id_no, county, sub_county, ward, estate, home_county, home_sub_county, home_ward, home_estate)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("sssssssss", $id_no, $county, $sub_county, $ward, $estate, $home_county, $home_sub_county, $home_ward, $home_estate);
    $insert->execute();
    $message = "✅ Address added successfully!";
}

header("Location: userdashboard.php");
exit;
?>
