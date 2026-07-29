<?php
include 'connect.php';

// Handle date filtering
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';
$params = [];
$filterSQL = '';

if ($startDate && $endDate) {
    $filterSQL = "AND DATE(transaction_time) BETWEEN ? AND ?";
    $params = [$startDate, $endDate];
}

// Prepare query per user
$sql = "
    SELECT full_name, id_no, phone , amount, mpesa_code
    FROM registration 
    WHERE 1=1 $filterSQL 
    GROUP BY full_name, id_no, phone
    ORDER BY full_name
";

$stmt = $con->prepare($sql);
if ($filterSQL) {
    $stmt->bind_param('ss', ...$params);
}
$stmt->execute();
$result = $stmt->get_result();





?>
<!DOCTYPE html>
<html>
<head>
    <title>Registration Fee Summary</title>

    <!-- DataTables & Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.flash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

    <style>
        body { font-family: Arial; margin: 30px; }
        h2 { color: #F25A2C; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        form { margin-bottom: 20px; }
        input[type="date"] { padding: 6px; margin-right: 10px; }
        button { padding: 8px 14px; background: #F25A2C; color: white; border: none; cursor: pointer; }
        button:hover { background: #d14a23; }

        .dt-button-custom {
            background-color: #F25A2C !important;
            color: white !important;
            border: none !important;
            padding: 6px 12px;
            border-radius: 4px;
            margin-right: 5px;
            font-weight: bold;
        }

        .dt-button-custom:hover {
            background-color: #d14a23 !important;
        }
        .title-logo {
            vertical-align: middle; 
            margin-right: 10px;    
            height: 30px;          
            width: aut
    </style>
</head>
<body>

<h2><img src="Digital-logo.png" alt="Company Logo" class="title-logo"> Registration Fee Summary</h2>






<form method="GET">
    <label>Start Date: <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"></label>
    <label>End Date: <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"></label>
    <button type="submit">Filter</button>
    <button type="button" onclick="window.location.href='contribution_export.php?start_date=<?= $startDate ?>&end_date=<?= $endDate ?>'">Export CSV</button>
</form>

<table id="memberTable">
    <thead>
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>ID Number</th>
            <th>Phone</th>
            <th>Amount</th>
            <th>Mpesa Code</th>
        </tr>
    </thead>
    <tbody>
        <?php
       function maskPhone($phone) {
    $phone = trim($phone);
    $length = strlen($phone);

    if ($length <= 6) {
        return str_repeat('x', $length); // Fully mask if too short
    }

    $start = substr($phone, 0, 4);
    $end = substr($phone, -3);
    $maskedMiddle = str_repeat('x', $length - 6);

    return $start . $maskedMiddle . $end;
}


function maskID($id) {
    $id = trim($id);
    if (strlen($id) < 2) {
        return str_repeat('x', strlen($id)); // fully mask if too short
    }
    return substr($id, 0, 2) . str_repeat('x', strlen($id) - 2);
}

        $counter = 1;
        $totalAmount = 0;
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $counter++ . "</td>";
            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars(maskID($row['id_no'])) . "</td>";
            echo "<td>" . htmlspecialchars(maskPhone($row['phone'])) . "</td>";
            echo "<td>" . number_format($row['amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['mpesa_code'], 2) . "</td>";
            echo "</tr>";
        
            
            
    $totalAmount += $row['amount'];
            
        }
        ?>
    </tbody>
</table>
<p><strong>Total Amount:</strong> KES <?= number_format($totalAmount, 2) ?></p>


<script>
  $(document).ready(function () {
      
    $('#memberTable').DataTable({
      responsive: true,
      dom: 'Bfrtip',
      buttons: [
        {
          extend: 'csv',
          text: 'Export CSV',
          className: 'dt-button-custom',
          exportOptions: {
            columns: [0, 1, 2, 3, 4]
                }
        },
        {
          extend: 'excel',
          text: 'Export Excel',
          className: 'dt-button-custom',
          exportOptions: {
            columns: [0, 1, 2, 3, 4]
                }
        },
        {
          extend: 'pdf',
          text: 'Export PDF',
          className: 'dt-button-custom',
          exportOptions: {
            columns: [0, 1, 2, 3, 4]
                }
        },
        {
          extend: 'print',
          text: 'Print',
          className: 'dt-button-custom',
          exportOptions: {
            columns: [0, 1, 2, 3, 4]
                }
        }
      ],
      pageLength: 50
    });
  });
  
  
</script>

</body>
</html>
