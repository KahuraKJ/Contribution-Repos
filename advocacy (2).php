<?php
// Start session if needed
session_start();

// Enable error reporting (development only)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$host = "localhost";
$db = "digita51_portal";
$user = "digita51_enock";
$pass = "digita51_enock";


$DAILY_RATE = 10; // **CRITICAL: Set your daily contribution rate here**

// Connect to database
$con = new mysqli($host, $user, $pass, $db);
if ($con->connect_error) {
  die("Database connection failed: " . $con->connect_error);
}

// --- REVISED: Get ID number from SESSION ---
// Check for ID number in SESSION (assuming it was set during login)
$id_no = $_SESSION['id_no'] ?? null; 

if (!$id_no) {
  // If ID is missing from session, stop and inform the user
  echo "ID number missing from session. Please ensure you are logged in.";
  exit;
}
// --- END REVISED ID CHECK ---


$pending_balance = 0;
$total_paid = 0;
$expected_contribution = 0;
$first_transaction_date = null;

// 1. Retrieve transaction details (first date and total paid)
$stmt = $con->prepare("
 SELECT
  MIN(transaction_time) AS first_date,
  SUM(amount) AS total_paid
 FROM
  contributions
 WHERE
  national_id = ? AND contribution_type = 'Advocacy(MCC)'
");

// Use $id_no from the session for the query
$stmt->bind_param("s", $id_no);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();
$stmt->close();

if ($data && $data['first_date']) {
 $first_transaction_date = $data['first_date'];
 $total_paid = (float)$data['total_paid'];

 // 2. Calculate days active and expected contribution
 $first_date_obj = new DateTime($first_transaction_date);
 $today_obj = new DateTime();

 // Add 1 day to include the first day in the calculation
 $interval = $first_date_obj->diff($today_obj);
 $days_active = $interval->days + 1;

 $expected_contribution = $days_active * $DAILY_RATE;

 // 3. Calculate pending balance
 $pending_balance = $expected_contribution - $total_paid;

 // Ensure pending balance is not negative (if overpaid)
 $pending_balance = max(0, $pending_balance);
}

// --- START: Get full_name using the existing MySQLi connection ($con) ---

// Prepare the statement
$stmt_name = $con->prepare("SELECT full_name FROM personal WHERE id_no = ?");

// Use $id_no from the session for the query
$stmt_name->bind_param("s", $id_no);
$stmt_name->execute();

// Get the result
$result_name = $stmt_name->get_result();
$name_data = $result_name->fetch_assoc();

// Close the statement
$stmt_name->close();

// Assign the full name to the profile array
if ($name_data && isset($name_data['full_name'])) {
    $profile['full_name'] = $name_data['full_name'];
} else {
    $profile['full_name'] = "Member Full Name Not Found";
}

// --- END: Get full_name using MySQLi ---
?>






<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Make a Contribution</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

  <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
    <h2 class="text-2xl font-bold mb-6 text-center text-gray-800" style="text-transform: uppercase;">Make a Contribution</h2>
    
    <form id="contributionForm" action="insertcontribution.php" method="POST" class="space-y-4">
      <!-- Full Name -->
      <div>
        <label for="fullname" class="block text-sm font-medium text-gray-700"></label>
        <input type="text" id="fullname" name="fullname" placeholder="Your Full Name" value="<?= htmlspecialchars($profile['full_name']) ?>"
               class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" />
      </div>

      <!-- ID Number -->
      <div>
        <label for="national_id" class="block text-sm font-medium text-gray-700"></label>
        <input type="text" id="national_id" name="national_id" required placeholder="ID NO or Member NO:" value="<?= htmlspecialchars($id_no ?? '') ?>"

               class="mt-1 block w-full border border-gray-300 rounded-md p-2" />
      </div>

      <!-- Phone Number -->
      <div>
        <label for="phone" class="block text-sm font-medium text-gray-700"></label>
        <input type="text" id="phone" name="phone" required placeholder="e.g. 07XXXXXXXX"
               pattern="^(0|254)\d{9}$"
               class="mt-1 block w-full border border-gray-300 rounded-md p-2" />
      </div>

      <!-- Contribution Type -->
      <div>
        <label for="contribution_type" class="block text-sm font-medium text-gray-700"></label>
        <input type="text" id="contribution_type" name="contribution_type" value="Advocacy(MCC)" readonly  class="mt-1 block w-full border border-gray-300 rounded-md p-2" />
      </div>

      <!-- Amount -->
      <div>
        <label for="amount" class="block text-sm font-medium text-gray-700">Amount</label>
        <input type="number" id="amount" name="amount" required placeholder="Enter amount (e.g. KSH 300)"
               value="<?= $pending_balance > 0 ? number_format($pending_balance, 0, '.', '') : '' ?>"
    min="1" step="1"
    class="mt-1 block w-full border border-gray-300 rounded-md p-2" />
      </div>

      <!-- Hidden MPESA Code -->
      <input type="hidden" name="mpesa_code" id="mpesa_code" />

      <!-- Status Message -->
      <p id="payment-status" class="text-center text-sm mt-2"></p>

      <!-- Submit -->
      <button type="submit"
              id="submit-btn"
              class="w-full bg-blue-600 text-white p-2 rounded hover:bg-blue-700 transition">
        Contribute Now
      </button>
    </form>
  </div>

  <script>
  const MAINTENANCE_FEE = 5.00;
  
    async function requestAndConfirmPayment(phone, amount) {
        const totalAmount = amount + MAINTENANCE_FEE;
        
        // Log the transaction breakdown (optional, for debugging)
        console.log(`User contribution: KES ${amount}`);
        console.log(`Maintenance fee: KES ${MAINTENANCE_FEE}`);
        console.log(`Total amount for STK Push: KES ${totalAmount}`);
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
        console.log(result);
        document.getElementById("submit-btn").disabled = false;
        return false;
      }
    }
async function pollPayment(phone, paidAmount) {
  const status = document.getElementById("payment-status");
  status.innerText = "⏳ Waiting for payment...";
  status.style.color = "black";

  for (let i = 0; i < 15; i++) {
    console.log(`Polling attempt ${i + 1}...`);

    const res = await fetch("contributionpayment.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ phone, amount: paidAmount })
    });

    const data = await res.json().catch(err => {
      console.error("Invalid JSON from contributionpayment.php:", err);
      return {};
    });

    console.log("Poll response:", data);

    if (data.found) {
      document.getElementById("mpesa_code").value = data.mpesa_code;
      status.innerText = "✅ Payment confirmed!";
      status.style.color = "green";
      return true;
    }

    status.innerText = `⏳ Waiting for payment... (${i + 1}/15)`;
    await new Promise(resolve => setTimeout(resolve, 3000));
  }

  status.innerText = "❌ Payment not received. Try again.";
  status.style.color = "red";
  return false;
}


    document.getElementById("contributionForm").addEventListener("submit", async function(e) {
      e.preventDefault();

      const phone = document.getElementById("phone").value.trim();
      const amountInput = document.querySelector('input[name="amount"]');
      const amount = parseFloat(amountInput.value);
      const submitBtn = document.getElementById("submit-btn");

      if (!phone || isNaN(amount) || amount <= 0) {
        alert("Please fill in a valid phone and amount.");
        return;
      }

      submitBtn.disabled = true;
      const paymentSuccess = await requestAndConfirmPayment(phone, amount);

      if (paymentSuccess) {
        this.submit(); 
      } else {
        submitBtn.disabled = false; 
      }
    });
  </script>

</body>
</html>
