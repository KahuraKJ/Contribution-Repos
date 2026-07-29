<?php
include 'connect.php';

// Set rates
$WEEKLY_RATE = 50;
$DAILY_RATE = 300;

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
    SELECT fullname, national_id, phone, contribution_type, SUM(amount) as total_paid, COUNT(*) as count 
    FROM contributions 
    WHERE 1=1 $filterSQL 
    GROUP BY fullname, national_id, phone, contribution_type
    ORDER BY fullname
";

$stmt = $con->prepare($sql);
if ($filterSQL) {
    $stmt->bind_param('ss', ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Organize data per user
$users = [];
while ($row = $result->fetch_assoc()) {
    $key = $row['national_id'];
    if (!isset($users[$key])) {
        $users[$key] = [
            'name' => $row['fullname'],
            'id' => $row['national_id'],
            'phone' => $row['phone'],
            'weekly_paid' => 0,
            'daily_paid' => 0,
            'weekly_count' => 0,
            'daily_count' => 0,
        ];
    }

    if ($row['contribution_type'] === 'JIOKOE') {
        $users[$key]['weekly_paid'] = $row['total_paid'];
        $users[$key]['weekly_count'] = $row['count'];
    } elseif ($row['contribution_type'] === 'Advocacy(MCC)') {
        $users[$key]['daily_paid'] = $row['total_paid'];
        $users[$key]['daily_count'] = $row['count'];
    }
}

// Format status
function formatStatus($expected, $paid) {
    $diff = $paid - $expected;

    if ($expected == 0 && $paid == 0) {
        return "<span style='color: gray;'>No Contributions Yet</span>";
    }

    if ($diff == 0) {
        return "<span style='color: green;'>✔ Up to Date</span>";
    }

    if ($diff > 0) {
        return "<span style='color: blue;'>+KES " . number_format($diff, 2) . " Overpaid</span>";
    }

    return "<span style='color: red;'>-KES " . number_format(abs($diff), 2) . " Pending</span>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>User Contribution Summary</title>
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

    </style>
</head>
<body>

<h2>👥 User Contribution Summary</h2>

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
            <th>Weekly Paid</th>
            <th>Weekly Expected</th>
            <th>Weekly Status</th>
            <th>Daily Paid</th>
            <th>Daily Expected</th>
            <th>Daily Status</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($users) > 0): ?>
    <?php $i = 1; foreach ($users as $u): 
        $weeklyExpected = $u['weekly_count'] * $WEEKLY_RATE;
        $dailyExpected = $u['daily_count'] * $DAILY_RATE;

        // Safety check
        $required = ['name', 'id', 'phone', 'weekly_paid', 'weekly_count', 'daily_paid', 'daily_count'];
        foreach ($required as $key) {
            if (!isset($u[$key])) {
                echo "<tr><td colspan='10' style='color:red;'>Missing data for: $key</td></tr>";
                continue 2; // skip this user
            }
        }
    ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= htmlspecialchars($u['name']) ?></td>
            <td><?= $u['id'] ?></td>
            <td><?= $u['phone'] ?></td>
            <td>KES <?= number_format($u['weekly_paid'], 2) ?></td>
            <td>KES <?= number_format($weeklyExpected, 2) ?></td>
            <td><?= formatStatus($weeklyExpected, $u['weekly_paid']) ?></td>
            <td>KES <?= number_format($u['daily_paid'], 2) ?></td>
            <td>KES <?= number_format($dailyExpected, 2) ?></td>
            <td><?= formatStatus($dailyExpected, $u['daily_paid']) ?></td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="10">No data found.</td></tr>
<?php endif; ?>
</tbody>
</table>
<script>
  $(document).ready(function () {
    $('#memberTable').DataTable({
      responsive: true,
      dom: 'Bfrtip',
      buttons: [
        {
          extend: 'csv',
          text: 'Export CSV',
          className: 'dt-button-custom'
        },
        {
          extend: 'excel',
          text: 'Export Excel',
          className: 'dt-button-custom'
        },
        {
          extend: 'pdf',
          text: 'Export PDF',
          className: 'dt-button-custom'
        },
        {
          extend: 'print',
          text: 'Print',
          className: 'dt-button-custom'
        }
      ],
      pageLength: 50
    });
  });
</script>

</body>
</html>
