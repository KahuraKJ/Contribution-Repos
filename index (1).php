<?php
session_start();
include 'connect.php';
$con->query("SET time_zone = '+03:00'");

// Set Timezone to ensure "Online" calculation is accurate for your region
date_default_timezone_set('Africa/Nairobi');

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_no = mysqli_real_escape_string($con, $_POST['id_no']);
    $user_code = mysqli_real_escape_string($con, $_POST['user_code']);

    $sql = "SELECT p.status, p.full_name FROM login l
            JOIN personal p ON l.id_no = p.id_no
            WHERE l.id_no = '$id_no' AND l.user_code = '$user_code'";

    $result = mysqli_query($con, $sql);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $status = $row['status'];
        $full_name = $row['full_name'];

        if ($status == 'Active') {
            $_SESSION['id_no'] = $id_no;
            $_SESSION['user_code'] = $user_code;
            $_SESSION['full_name'] = $full_name;

            // TRACKING DATA
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];

            // Insert login record. 
            $log_sql = "INSERT INTO login_history (user_code, id_no, full_name, ip_address, user_agent, login_time, last_activity) 
                        VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
            
            if ($log_stmt = mysqli_prepare($con, $log_sql)) {
                mysqli_stmt_bind_param($log_stmt, "sssss", $user_code, $id_no, $full_name, $ip_address, $user_agent);
                mysqli_stmt_execute($log_stmt);
                
                // 1. GET THE ID FIRST
                $_SESSION['last_log_id'] = mysqli_insert_id($con); 
                
                // 2. CLOSE THE STATEMENT ONLY ONCE
                mysqli_stmt_close($log_stmt);
            }

            header("Location: userdashboard.php");
            exit();
            
        } else if ($status == 'Suspension') {
            $errorMessage = 'Your account is Suspended. Please contact the administrator.';
        } else if ($status == 'Deactivated') {
            $errorMessage = 'Your account has been deactivated. Please contact the administrator.';
        } else if ($status == 'Death') {
            $errorMessage = 'Your account does not exist. Please contact the administrator.';
        } else {
            $errorMessage = 'An unexpected error occurred. Please try again.';
        }
    } else {
        $errorMessage = 'Invalid ID NO or Association Member NO.';
    }
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
        .logo {
      display: block;
      margin: 0 auto 20px; /* Centers the logo and adds space below it */
      width: 200px;        /* Adjust this value to change the size */
      height: auto;       /* Maintains the aspect ratio */
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
         <img src="https://digitalboda.co.ke/portal/Digital-Boda-Logo.png" alt="Digital Boda Logo" class="logo">
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
