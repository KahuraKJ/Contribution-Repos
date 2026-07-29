<?php


error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors
ini_set('log_errors', 1);     // Log errors
ini_set('error_log', '/path/to/your/php-error.log');

$id_no = $_GET['id_no'] ?? null;

if (!$id_no) {
    echo "No user ID provided.";
    exit;
}

$conn = new mysqli("localhost", "digita51_enock", "digita51_enock", "digita51_portal");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT full_name FROM personal WHERE id_no = ?");
$stmt->bind_param("s", $id_no);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

if (!$profile) {
    echo "User not found.";
    exit;
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Dashboard</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />

  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" />

  <link rel="stylesheet" href="style1.css" />
  <style>
    :root {
      --primary: #0f0c32;
      --primary-light: #F25A2C;
      --dark: #1a1a2e;
    }
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #fff;
      color: var(--primary);
    }
    .header {
      background-color: var(--primary-light);
      padding: 0 30px;
      height: 70px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .menu-toggle {
  display: none;
  color: white;
  font-size: 24px;
  cursor: pointer;
}

@media (max-width: 768px) {
  .menu-toggle {
    display: block;
  }

  .container {
    display: flex;
    flex-direction: column;
  }

  .sidebar {
    position: fixed;
    top: 70px;
    left: 0;
    width: 250px;
    height: 100vh;
    background-color: #0f0c32;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
    z-index: 1001;
  }

  .sidebar.active {
    transform: translateX(0);
  }

  .main-content {
    padding: 20px;
    margin-left: 0 !important;
  }

  .header {
    justify-content: space-between;
  }
}
.nav-menu {
  display: flex;
  flex-direction: column;
}

.nav-item {
  display: flex;
  align-items: center;
  padding: 12px 20px;
  color: white;
  text-decoration: none;
  transition: background-color 0.3s, padding-left 0.3s;
}

.nav-item i {
  margin-right: 10px;
}

/* Hover effect */
.nav-item:hover {
  background-color: #F25A2C;  /* Vibrant orange on hover */
  padding-left: 25px;         /* Slide-in effect */
  color: white;
  border-left: 4px solid #fff;
  cursor: pointer;
}

/* Active state */
.nav-item.active {
  background-color: #F25A2C;
  border-left: 4px solid #fff;
  padding-left: 25px;
}


  </style>
</head>

<body>
<div class="container">
  <div class="sidebar" id="sidebar">
    <div class="logo">
    <h1><img src="Digital-logo.png" alt="Logo" style="height: 60px;" />
    Digital<span>Boda</span></h1>
    </div>
    <div class="nav-menu">
      <div class="menu-heading">Main</div>
      <div class="nav-item active"><i class="fas fa-chart-pie"></i><span>Dashboard</span></div>
      <a href="adminDisplay.php" class="nav-item"><i class="fas fa-users"></i><span>Members</span></a>
      <a href="displayProfile.php?id_no=<?= htmlspecialchars(urlencode($id_no)) ?>" class="nav-item"><i class="fas fa-box"></i><span>Accounts</span></a>
      <a href="adminContribution.php" class="nav-item"><i class="fas fa-shopping-cart"></i><span>Contributions</span></a>

      <div class="menu-heading">Admin</div>
      <a href="#" class="nav-item"><i class="fas fa-cog"></i><span>Settings</span></a>
      <a href="post.html" class="nav-item"><i class="fas fa-bell"></i><span>Notifications</span></a>
      <a href="adminlogout.php" class="nav-item"><i class="fas fa-shield-alt"></i><span>LogOut</span></a>
    </div>
  </div>

  <div class="header">
    <div class="menu-toggle" onclick="toggleSidebar()">
  <i class="fas fa-bars"></i>
</div>
    <div class="search-bar">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Search..." />
    </div>
    <div class="header-actions">
      <div class="notification position-relative" onclick="toggleDropdown()">
        <a href="notification.php?id_no=<?= htmlspecialchars(urlencode($id_no)) ?>"><i class="fas fa-bell text-white"></i></a>
        <div id="notification-dropdown" class="dropdown-menu" style="display: none; position: absolute; right: 0; top: 100%; background: white; max-height: 400px; overflow-y: auto;"></div>
      </div>
      <div class="user-profile">
  <div class="profile-img">
    
  </div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($profile['full_name']) ?></div>
          <div class="user-role">Administrator</div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  
  function fetchNotifications() {
    fetch("fetchNotifications.php")
      .then(res => res.json())
      .then(data => {
        const container = document.getElementById("notification-dropdown");
        container.innerHTML = data.length
          ? data.map(n => `<div>${n.message}</div>`).join('')
          : "<div>No new notifications.</div>";
      });
  }
  
  function toggleSidebar() {
  const sidebar = document.querySelector(".sidebar");
  sidebar.classList.toggle("active");
}
</script>
<script>
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
