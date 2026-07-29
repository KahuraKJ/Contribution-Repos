<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$db = "digita51_portal";
$user = "digita51_enock";
$pass = "digita51_enock";

$DAILY_RATE = 10;

$con = new mysqli($host, $user, $pass, $db);
if ($con->connect_error) {
    die("Database connection failed: " . $con->connect_error);
}

$id_no = $_SESSION['id_no'] ?? null;
if (!$id_no) {
    echo "ID number missing from session. Please ensure you are logged in.";
    exit;
}

$profile = [];
$stmt_profile = $con->prepare("SELECT full_name FROM personal WHERE id_no = ?");
if ($stmt_profile) {
    $stmt_profile->bind_param("s", $id_no);
    $stmt_profile->execute();
    $result_profile = $stmt_profile->get_result();
    $profile = $result_profile->fetch_assoc() ?? [];
    $stmt_profile->close();
}

$pending_balance = 0;
$total_paid = 0;
$expected_contribution = 0;
$first_transaction_date = null;

$stmt = $con->prepare("
SELECT
 MIN(transaction_time) AS first_date,
 SUM(amount) AS total_paid
FROM
 contributions
WHERE
 national_id = ? AND contribution_type = 'Advocacy(MCC)'
");

$stmt->bind_param("s", $id_no);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if ($data && $data['first_date']) {
    $first_transaction_date = $data['first_date'];
    $total_paid = (float)$data['total_paid'];

    $first_date_obj = new DateTime($first_transaction_date);
    $today_obj = new DateTime();

    $interval = $first_date_obj->diff($today_obj);
    $days_active = $interval->days + 1;

    $expected_contribution = $days_active * $DAILY_RATE;
    $pending_balance = $expected_contribution - $total_paid;

    $pending_balance = max(0, $pending_balance);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1">
 <title>Make a Contribution</title>
 <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex justify-center p-4">

<div class="w-full max-w-lg bg-white p-6 md:p-8 rounded-xl shadow-lg border border-gray-200">

    <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">Advocacy Contribution</h2>

    <div class="mb-6 p-4 rounded-lg 
        <?= $pending_balance > 0
            ? 'bg-red-50 text-red-700 border border-red-300'
            : 'bg-green-50 text-green-700 border border-green-300' ?>">

        <p class="font-semibold text-lg">
            <?php if ($pending_balance > 0): ?>
                🚨 Pending Balance: KSh <?= number_format($pending_balance, 2) ?>
            <?php else: ?>
                ✅ You are up-to-date!
            <?php endif; ?>
        </p>

        <?php if ($pending_balance > 0): ?>
        <p class="text-sm mt-1">
            Your payment of <strong>KSh <?= number_format($pending_balance, 0) ?></strong> will clear your debt.
        </p>
        <?php endif; ?>
    </div>

    <form id="contributionForm" action="insertcontributionlogin.php" method="POST" class="space-y-4">

        <div>
            <label class="block text-sm font-medium text-gray-700">Full Name</label>
            <input type="text" name="fullname"
                value="<?= htmlspecialchars($profile['full_name'] ?? '') ?>"
                readonly
                class="mt-1 block w-full border border-gray-300 rounded-md p-2 bg-gray-50">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">ID / Member Number</label>
            <input type="text" name="national_id"
                value="<?= htmlspecialchars($id_no) ?>"
                readonly
                class="mt-1 block w-full border border-gray-300 rounded-md p-2 bg-gray-50">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Phone Number</label>
            <input type="text" name="phone"  required
                pattern="^(0|254)\d{9}$"
                class="mt-1 block w-full border border-gray-300 rounded-md p-2">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Contribution Type</label>
            <input type="text" name="contribution_type"
                value="Advocacy(MCC)"
                readonly
                class="mt-1 block w-full border border-gray-300 rounded-md p-2 bg-gray-50">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Amount to Pay</label>
            <input type="number" name="amount"
                required
                min="1"
                step="1"
                value="<?= $pending_balance > 0 ? number_format($pending_balance, 0, '.', '') : '100' ?>"
                class="mt-1 block w-full border border-gray-300 rounded-md p-2">
        </div>

        <input type="hidden" name="mpesa_code" id="mpesa_code">
        <input type="hidden" name="maintenance_fee" value="5.00">

        <p id="payment-status" class="text-center text-sm mt-2"></p>

        <button type="submit" id="submit-btn"
            class="w-full bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 transition">
            Contribute Now
        </button>
    </form>
</div>

<script>
const MAINTENANCE_FEE = 5.00;

async function requestAndConfirmPayment(phone, amount) {
    const totalAmount = amount + MAINTENANCE_FEE;

    const response = await fetch('contributionstk.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ phone, amount: totalAmount })
    });

    const result = await response.json();

    if (result.ResponseCode === "0" || result.success) {
        alert("✅ STK Push sent. Check your phone to pay.");
        return await pollPayment(phone, totalAmount);
    } else {
        alert("❌ STK Push failed: " + (result.message || "Unknown error"));
        document.getElementById("submit-btn").disabled = false;
        return false;
    }
}

async function pollPayment(phone, paidAmount) {
    const status = document.getElementById("payment-status");
    status.innerText = "⏳ Waiting for payment...";
    status.style.color = "black";

    for (let i = 0; i < 15; i++) {
        const res = await fetch("contributionpayment.php", {
            method: "POST",
            headers: {"Content-Type": "application/json"},
            body: JSON.stringify({ phone, amount: paidAmount })
        });

        const data = await res.json().catch(() => ({}));

        if (data.found) {
            document.getElementById("mpesa_code").value = data.mpesa_code;
            status.innerText = "✅ Payment confirmed!";
            status.style.color = "green";
            return true;
        }

        status.innerText = `⏳ Waiting for payment... (${i + 1}/15)`;
        await new Promise(r => setTimeout(r, 3000));
    }

    status.innerText = "❌ Payment not received. Try again.";
    status.style.color = "red";
    return false;
}

document.getElementById("contributionForm").addEventListener("submit", async function(e) {
    e.preventDefault();

    const phone = document.querySelector('input[name="phone"]').value.trim();
    const amount = parseFloat(document.querySelector('input[name="amount"]').value);
    const submitBtn = document.getElementById("submit-btn");

    if (!phone || isNaN(amount) || amount <= 0) {
        alert("Please fill in a valid phone and amount greater than 0.");
        return;
    }

    submitBtn.disabled = true;

    const paymentSuccess = await requestAndConfirmPayment(phone, amount);

    if (paymentSuccess) {
        window.location.href = "userdashboard.php";
        return;
    } else {
        submitBtn.disabled = false;
    }
});
</script>

</body>
</html>
