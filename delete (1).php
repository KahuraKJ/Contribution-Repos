<?php
// Include the database connection file.
include 'connect.php';

// Check if a 'deleteid' is present in the URL.
if (isset($_GET['deleteid'])) {
    // Sanitize the ID to prevent SQL injection.
    $id = mysqli_real_escape_string($con, $_GET['deleteid']);

    // Start a database transaction to ensure all queries are executed or none are.
    mysqli_begin_transaction($con);

    try {
        // Step 1: Get the 'id_no' from the personal table. This is the key we'll use to delete from all other tables.
        $id_no_query = "SELECT id_no FROM personal WHERE id = '$id'";
        $id_no_result = mysqli_query($con, $id_no_query);

        // Check if the personal record exists and get the id_no.
        if (mysqli_num_rows($id_no_result) > 0) {
            $row = mysqli_fetch_assoc($id_no_result);
            $id_no = $row['id_no'];

            // Step 2: Delete from the login table.
            $delete_login = "DELETE FROM login WHERE id_no = '$id_no'";
            if (!mysqli_query($con, $delete_login)) {
                throw new Exception(mysqli_error($con));
            }

            // Step 3: Delete from the contact_address table.
            $delete_contact = "DELETE FROM contact_address WHERE id_no = '$id_no'";
            if (!mysqli_query($con, $delete_contact)) {
                throw new Exception(mysqli_error($con));
            }

            // Step 4: Delete from the mode_of_travel table.
            $delete_travel = "DELETE FROM mode_of_travel WHERE id_no = '$id_no'";
            if (!mysqli_query($con, $delete_travel)) {
                throw new Exception(mysqli_error($con));
            }

            // Step 5: Delete from the residential table.
            $delete_residential = "DELETE FROM residential WHERE id_no = '$id_no'";
            if (!mysqli_query($con, $delete_residential)) {
                throw new Exception(mysqli_error($con));
            }

            // Step 6: Finally, delete the record from the personal table itself using the primary key 'id'.
            $delete_personal = "DELETE FROM personal WHERE id = '$id'";
            if (!mysqli_query($con, $delete_personal)) {
                throw new Exception(mysqli_error($con));
            }

            // If all queries were successful, commit the transaction.
            mysqli_commit($con);

            // Redirect back with a success message.
            header("Location: adminDisplay.php?msg=Deletion successful");
            exit();

        } else {
            // If the record doesn't exist, just redirect back.
            header("Location: adminDisplay.php?msg=Record not found");
            exit();
        }

    } catch (Exception $e) {
        // If an error occurred, roll back the transaction to undo all changes.
        mysqli_rollback($con);

        // Display the error message from the exception.
        die("Error during deletion: " . $e->getMessage());
    }

} else {
    // If no ID was passed, redirect back to the admin view page.
    header("Location: adminDisplay.php?msg=No ID specified");
    exit();
}
?>
