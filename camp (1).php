<?php
session_start ();
include 'connect.php'; // Database connection

if (!isset($_SESSION['id_no'])) {
    header('Location: index.php');
    exit;
}

$id_no = $_SESSION['id_no'];
// Fetch the active campaign, if any
$query = "SELECT * FROM campaigns";
$result = mysqli_query($con, $query);
$activeCampaign = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Contributions</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  
  <!-- Optional custom styles -->
  <link rel="stylesheet" href="userdashboard.css">
</head>
<body class="bg-light">
  <?php include 'header.php'; ?>
    <!-- Main Content -->
    <div class="flex-grow-1 p-4">
      <h1 class="mb-4" style="text-transform: uppercase;">Contribution Panel</h1>

      <div class="container-fluid">
        <div class="row g-4">
          
          <!-- General Contributions: Monthly/Weekly -->
          <div class="col-md-<?= $activeCampaign ? '6' : '12' ?>">
            <?php include 'advocacy.php'; ?>
          </div>

          <!-- Campaign Contributions -->
          <?php if ($activeCampaign): ?>
            <div class="col-md-6">
              <?php include 'userdonation.php'; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Full Contribution Overview -->
        <div class="mt-5">
          <?php include 'contribution.php'; ?>
        </div>
      </div>
    </div>
  

  <!-- Optional JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
