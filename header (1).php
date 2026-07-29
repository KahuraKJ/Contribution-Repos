<?php

error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors
ini_set('log_errors', 1);     // Log errors
ini_set('error_log', '/path/to/your/php-error.log');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id_no'])) {
    header("Location: index.php");
    exit;
}
$id_no = $_SESSION['id_no'];

$conn = new mysqli("localhost", "digita51_enock", "digita51_enock", "digita51_portal");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 2. Set Timezone (Crucial for the "Online" status to work in Kenya)
date_default_timezone_set('Africa/Nairobi');
$conn->query("SET time_zone = '+03:00'");

// 3. Update last_activity in login_history
// We use $conn here because that's our active connection
if(isset($_SESSION['last_log_id'])) {
    $log_id = $_SESSION['last_log_id'];
    
$update_activity = $conn->prepare("UPDATE login_history SET last_activity = NOW() WHERE id = ?");
    $update_activity->bind_param("i", $log_id); // "i" for integer ID
    $update_activity->execute();
    $update_activity->close();
} else {
    $update_fallback = $conn->prepare("UPDATE login_history SET last_activity = NOW() WHERE id_no = ? ORDER BY login_time DESC LIMIT 1");
    $update_fallback->bind_param("s", $id_no);
    $update_fallback->execute();
    $update_fallback->close();
}
    
$stmt = $conn->prepare("SELECT full_name FROM personal WHERE id_no = ?");
$stmt->bind_param("s", $id_no);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();
$stmt->close();

if (!$profile) {
    echo "User not found.";
    exit;
}
$user_code = null;
$stmt_user_code = $conn->prepare("SELECT user_code FROM login WHERE id_no = ?");
$stmt_user_code->bind_param("s", $id_no);
$stmt_user_code->execute();
$result_user_code = $stmt_user_code->get_result();
if ($row = $result_user_code->fetch_assoc()) {
    $user_code = $row['user_code'];
}
$stmt_user_code->close();

// Step 2: Use the fetched user_code to check if the user is a leader.
$is_leader = false;
if ($user_code) {
    $leader_stmt = $conn->prepare("SELECT id FROM arbolitos_groups WHERE leader_id = ?");
    $leader_stmt->bind_param("s", $user_code);
    $leader_stmt->execute();
    $leader_result = $leader_stmt->get_result();
    if ($leader_result->num_rows > 0) {
        $is_leader = true;
    }
    $leader_stmt->close();
}
$sql = "
    SELECT COUNT(*) AS unread_count
    FROM post p
    WHERE p.status = 'unread'
    AND p.id NOT IN (
        SELECT post_id FROM post_reads WHERE id_no = ?
    )
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id_no);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$hasUnread = $row['unread_count'] > 0;
$unreadCount = $row['unread_count'];



// NEW: Fetch profile photo path
$profile_photo_path = null;
$photo_stmt = $conn->prepare("SELECT image_path FROM products WHERE id_no = ? LIMIT 1");
$photo_stmt->bind_param("s", $id_no);
$photo_stmt->execute();
$photo_result = $photo_stmt->get_result();
if ($photo_row = $photo_result->fetch_assoc()) {
    $profile_photo_path = $photo_row['image_path'];
}
$photo_stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        /* Layout Wrapper */
        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #0f0c32;
            color: #f25a2c;
            flex-shrink: 0;
            padding: 10px;
            transition: transform 0.3s ease-in-out;
        }

        .sidebar h2 {
            color: #f25a2c;
            margin-top: 0;
        }

        .sidebar a {
            display: block;
            padding: 10px;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 8px;
        }

        .sidebar a.active, .sidebar a:hover {
            background-color: #f25a2c;
        }

        /* Header */
        .header {
            background-color: white;
            color: #0f0c32;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 10px;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header .menu-toggle {
            display: none;
            cursor: pointer;
        }

        .header img {
            height: 50px;
        }

        .header h3 {
            font-size: 20px;
            margin: 0;
        }

        .notification i {
            font-size: 10px;
            color: #0f0c32;
        }

        .notification {
            position: relative;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            max-height: 400px;
            overflow-y: auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            border-radius: 8px;
        }

        /* Main content */
        .content {
            flex: 1;
            padding: 20px;
        }

        /* Profile picture styling */
        .header-profile-pic {
            width: 50px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                height: 100%;
                left: 0;
                top: 0;
                transform: translateX(-100%);
                z-index: 999;
            }

            .sidebar.open {
                transform: translateX(0);
            }

            .header .menu-toggle {
                display: block;
            }

            .layout {
                flex-direction: column;
            }

            .content {
                margin-top: 60px;
            }
        }
        .badge-count {
    position: absolute;
    top: -6px;
    right: -10px;
    background-color: #08024a;
    color: white;
    font-size: 10px;
    font-weight: bold;
    min-width: 18px;
    height: 18px;
    border-radius: 50%;
    text-align: center;
    line-height: 18px;
    border: 2px solid white;
    z-index: 10;
}

/* Shake animation for unread notifications */
@keyframes shake {
    0%, 100% { transform: translate(0, 0); }
    20%, 60% { transform: translate(-2px, 0); }
    40%, 80% { transform: translate(2px, 0); }
}

.shake {
    animation: shake 0.5s ease-in-out infinite;
}
.nav-dropdown-item{
    
position: relative;
        display: block; 
    }
    
    .nav-item-indicator {
        margin-left: auto;
        font-size: 10px;
        transition: transform 0.3s ease;
    }
    .nav-dropdown-item:hover > .nav-item > .nav-item-indicator {
        transform: rotate(90deg);
    }

    /* Sub-menu (the dropdown content) */
    .nav-sub-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        background-color: #0f0c32;
        padding: 5px 0;
        
        /* Animation properties */
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
    }

    /* Key change: On hover of the parent, show the sub-menu */
    .nav-dropdown-item:hover .nav-sub-menu {
        max-height: 300px; /* Large enough to show all links */
        opacity: 1;
    }

    /* Sub-menu link items */
    .nav-sub-menu li a {
        display: block;
        padding: 8px 15px 8px 50px;
        color: #fff;
        text-decoration: none;
        font-size: 15px;
        transition: background-color 0.3s ease, padding-left 0.3s ease;
    }

    .nav-sub-menu li a:hover {
        background-color: var(--primary-light);
        padding-left: 55px;
    }
    

    </style>
</head>
<body>

<div class="header">
    <div class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </div>

    <div style="display: flex; align-items: center; gap: 15px;">
        <!--<img src="Digital-logo.png" alt="Logo" />--><img src="https://admin.digitalboda.co.ke/Digital-Boda-Logo.png" alt="Logo" style="height: 50px;">
        <h3>USER DASHBOARD</h3>
    </div>

    <div style="display: flex; align-items: center; gap: 20px;">
    <!--    <div class="notification" onclick="toggleDropdown()" style="cursor: pointer;">-->
           <a href="announcements.php" 
   id="notificationLink" 
   class="text-body mx-3 position-relative" 
   style="text-decoration: none; display: inline-block; position: relative;">
    <i class="fas fa-envelope fa-2x <?= $hasUnread ? 'shake' : '' ?>" 
       id="notificationIcon" 
       style="color: <?= $hasUnread ? '#f25a2c' : '#08024a' ?>;">
    </i>

    <?php if ($hasUnread): ?>
    <span id="notificationBadge" 
          class="badge-count">
          <?= $unreadCount ?>
    </span>
    <?php endif; ?>
</a>



        <!--</div>-->

        <div style="font-weight: 300; display: flex; align-items: center; gap: 10px;">
            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
                <?php if ($profile_photo_path && file_exists($profile_photo_path)): ?>
                    <img src="<?= htmlspecialchars($profile_photo_path) ?>" alt="Profile Photo" class="header-profile-pic">
                <?php else: ?>
                    <i class="fas fa-user-circle" style="font-size: 30px; color: #08072b;"></i>
                <?php endif; ?>
                <strong style="font-size: 10px; margin-top: 3px;"><?= htmlspecialchars($profile['full_name']) ?></strong>
            </div>
            
            <span style="background: #28a745; padding: 3px 8px; border-radius: 8px; font-size: 8px; color: white;">✔ Active</span>
        </div>
    </div>
</div>

<div class="layout">
    <div class="sidebar" id="sidebar">
        <a href="userdashboard.php"><i class="fas fa-home"></i> Home</a>
        <a href="displayProfile.php"><i class="fas fa-user"></i> Personal Profile</a>
        <a href="transport.php"><i class="fas fa-motorcycle"></i> Transport Details</a>
        <div class="nav-dropdown-item">
        <a href="camp.php"><i class="fa-solid fa-wallet"></i> Contribution Overview</a>
       <ul class="nav-sub-menu">
        <li><a href="jiokoe.php"><i class="fa-solid fa-coins"></i> Jiokoe Digital Welfare</a></li>
        <li><a href="arbolitosOverView.php"><i class="fas fa-users"></i> Group Contributions</a></li>
         </ul>
        </div>
        <div class="nav-dropdown-item">
        <a href="arbolitosMembers.php"><i class="fa-solid fa-people-group"></i><span> Arbolitos Membership</span></a>
       <ul class="nav-sub-menu">
           <li><a href="saccoMembers.php"><i class="fas fa-shield-alt"></i><span> Sacco Membership</span></a></li>
        </ul>
        </div>
        <a href="displayBeneficiaries.php"><i class="fa-solid fa-heart"></i><span> Beneficiary Details</span></a>
        <a href="referral_link.php"><i class="fas fa-share-alt"></i> Refer & Earn</a>
        
        <a href="announcements.php"><i class="fas fa-envelope"></i> Notifications</a> <a href="passwordChange.php"><i class="fa fa-key" aria-hidden="true"></i> Password Reset</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
<script>
document.getElementById("notificationLink").addEventListener("click", function() {
    const icon = document.getElementById("notificationIcon");
    const badge = document.getElementById("notificationBadge");

    // Stop shaking and reset color
    icon.classList.remove("shake");
    icon.style.color = "#0f0c32";

    // Hide badge after clicking
    if (badge) badge.style.display = "none";

    // Mark all unread posts as read in the database
    fetch(`mark_read.php`)
        .then(res => console.log("Marked as read for member"));
});
</script>

<script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
    }

    function toggleDropdown() {
        const dropdown = document.getElementById('notification-dropdown');
        dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    // Close sidebar when clicking outside (mobile)
    document.addEventListener("click", function(event) {
        const sidebar = document.getElementById("sidebar");
        const toggle = document.querySelector(".menu-toggle");

        if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
            sidebar.classList.remove("open");
        }
    });
</script>

</body>
</html>