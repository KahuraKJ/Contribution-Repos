<?php
// Start a session to store user information.
session_start();

// Database connection file.
include 'connect.php';

// Initialize a variable to hold the error message.
$errorMessage = '';

// Check if the form was submitted using the POST method.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize user inputs to prevent SQL injection.
    $id_no = mysqli_real_escape_string($con, $_POST['id_no']);
    $user_code = mysqli_real_escape_string($con, $_POST['user_code']);

    // Construct the SQL query. We join the 'login' table with the 'personal' table
    // to retrieve both the login credentials and the user's status.
    $sql = "SELECT p.status FROM login l
            JOIN personal p ON l.id_no = p.id_no
            WHERE l.id_no = '$id_no' AND l.user_code = '$user_code'";

    // Execute the query.
    $result = mysqli_query($con, $sql);

    // Check if a user with the provided credentials was found.
    if (mysqli_num_rows($result) > 0) {
        // Fetch the result row.
        $row = mysqli_fetch_assoc($result);
        $status = $row['status'];

        // Check the user's account status.
        if ($status == 'Active') {
            // User is active, so set session variables and redirect to a dashboard.
            $_SESSION['id_no'] = $id_no;
            // Redirect to the user dashboard, passing the correct ID number.
            header("Location: userdashboard.php?id_no=" . urlencode($id_no));
            exit();
        } else if ($status == 'On Hold') {
            // User is on hold, set the error message.
            $errorMessage = 'Your account is on hold. Please contact the administrator.';
        } else if ($status == 'Deactivated') {
            // User is deactivated, set the error message.
            $errorMessage = 'Your account has been deactivated. Please contact the administrator.';
        } else {
            // Catch any unexpected status, set a generic login failure message.
            $errorMessage = 'An unexpected error occurred. Please try again.';
        }
    } else {
        // No matching user found, set a generic error message.
        $errorMessage = 'Invalid ID NO or Association Member NO.';
    }

    // Close the database connection.
    mysqli_close($con);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Member Login</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-image: url('boda5.jpeg');
            background-size: cover;
            background-position: center;
        }

        .login-box {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 16px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
        }

        .login-box h2 {
            text-align: center;
            margin-bottom: 10px;
            color: #202155;
        }

        .login-box p {
            text-align: center;
            font-size: 14px;
            color: #555;
            margin-bottom: 20px;
        }

        .form-group {
            position: relative;
            margin-bottom: 28px;
        }

        input {
            width: 100%;
            padding: 12px 8px 8px;
            font-size: 16px;
            border: none;
            border-bottom: 2px solid #ccc;
            background-color: transparent;
            outline: none;
        }

        input:focus {
            border-bottom-color: #F25A2C;
        }

        label {
            position: absolute;
            top: 12px;
            left: 8px;
            color: #888;
            font-size: 14px;
            pointer-events: none;
            transition: all 0.2s ease;
            background: white;
            padding: 0 4px;
        }

        input:focus + label,
        input:not(:placeholder-shown) + label {
            top: -10px;
            left: 6px;
            font-size: 12px;
            color: #F25A2C;
        }

        button {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            background-color: #F25A2C;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #202155;
        }

        .error-message {
            color: #d8000c;
            background-color: #ffd2d2;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <?php if (!empty($errorMessage)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <h2>LOGIN</h2>
        <p>Login into your account</p>

        <form method="POST">
            <div class="form-group">
                <input type="text" name="id_no" id="id_no" required placeholder=" " />
                <label for="username">ID NO</label>
            </div>
            <div class="form-group">
                <input type="password" name="user_code" id="user_code" required placeholder=" " />
                <label for="user_code">Association Member NO: (Password)</label>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
