<?php
include 'connect.php';


$target = 0.00;
$campaign_title = "";
$campaign_id = 0;

// Check if a campaign ID is provided in the URL query string.
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Sanitize the input to prevent SQL injection.
    $campaign_id = (int)$_GET['id'];

    // Prepare the SQL query to fetch the campaign's target amount and title.
    // This is a prepared statement for security.
    $sql = "SELECT title, target FROM campaigns WHERE campaignId = ? LIMIT 1";

    if ($stmt = $con->prepare($sql)) {
        // Bind the campaign ID parameter.
        $stmt->bind_param("i", $campaign_id);

        // Execute the query.
        $stmt->execute();

        // Bind the result variables.
        $stmt->bind_result($fetched_title, $fetched_amount);

        // Fetch the result.
        if ($stmt->fetch()) {
            $campaign_title = htmlspecialchars($fetched_title);
            $target = htmlspecialchars($fetched_amount);
        }

        // Close the statement.
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Contribute to <?php echo $campaign_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .modal {
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 500px;
            position: relative;
        }
        .close-button {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 20px;
            cursor: pointer;
        }
        .close-button:hover {
            color: black;
        }
        .modal-form .form-group {
            margin-bottom: 15px;
        }
        .modal-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .modal-form input[type="text"],
        .modal-form input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .modal-form button {
            width: 100%;
            padding: 12px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            margin-top: 20px;
            transition: background-color 0.2s ease-in-out;
        }
        .modal-form button:hover {
            background-color: #218838;
        }
        /* Style for readonly inputs */
        input[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen p-8">

<div id="contributionModal" class="modal">
    <div class="modal-content">
        <!-- Close button to return to the campaigns page. -->
        <span class="close-button" onclick="window.location.href='https://portal.digitalboda.co.ke/'">&times;</span>
        <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">
            Contribute to <span id="modalCampaignTitle" class="text-blue-600"><?php echo $campaign_title; ?></span>
        </h2>

        <form id="contributionForm" class="modal-form" action="insertdonation.php" method="post">
            <div class="form-group">
                <label for="fullname">Full Name:</label>
                <input type="text" id="fullname" name="fullname" placeholder="Your Full Name" required>
            </div>
            <div class="form-group">
                <label for="national_id">ID Number:</label>
                <input type="text" id="national_id" name="national_id" placeholder="National ID" required>
                <div id="error-message" style="color: red; display: none;">Please enter a valid National ID  (at least 7 digits).</div>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number (M-Pesa):</label>
                <input type="text" id="phone" name="phone" placeholder="e.g., 07XXXXXXXX" pattern="^(0|254)\d{9}$" required>
                
            </div>
            <div class="form-group">
                <label for="amount">Amount (KSh):</label>
                <!-- The PHP code populates the value and makes the input readonly -->
                <input type="number" id="amount" name="amount" min="1" step="0.01" value="<?php echo $target; ?>"required>
            </div>
            <!-- Hidden fields to pass data to the next script -->
            <input type="hidden" name="donation_title" id="hiddenDonationTitle" value="<?php echo $campaign_title; ?>">
            <input type="hidden" id="hiddenCampaignId" name="campaignId" value="<?php echo $campaign_id; ?>">
            <input type="hidden" id="mpesa_code" name="mpesa_code" />

            <button type="submit" id="submit-btn">Pay Now (M-Pesa)</button>
        </form>
    </div>
</div>

<script>
    const campaignId = document.getElementById('hiddenCampaignId').value;
    const campaignTitle = document.getElementById('hiddenDonationTitle').value;
    
    // Define the maintenance/transaction fee
    const MAINTENANCE_FEE = 5.00;

    async function requestAndConfirmPayment(phone, amount) {
        // Calculate the total amount to be transacted through M-Pesa
        const totalAmount = amount + MAINTENANCE_FEE;
        
        console.log(`User contribution: KES ${amount}`);
        console.log(`Maintenance fee: KES ${MAINTENANCE_FEE}`);
        console.log(`Total amount for STK Push: KES ${totalAmount}`);
        
        const response = await fetch('donationstk.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            // Send the total amount to the STK Push script
            body: JSON.stringify({ phone, amount: totalAmount }) 
        });

        const result = await response.json();

        if (result.ResponseCode === "0" || result.success) {
            // Replaced alert with a custom message box for better UX
            showMessage(`✅ STK Push sent. Check your phone to pay KES ${totalAmount.toFixed(2)}.`, "success");
            
            // Pass the total amount to the polling function
            return await pollPayment(phone, totalAmount); 
        } else {
            showMessage("❌ STK Push failed: " + (result.message || "Unknown error"), "error");
            console.log(result);
            return false;
        }
    }

    async function pollPayment(phone, paidAmount) {
        // Clear any previous status messages
        let statusContainer = document.getElementById("status-message-container");
        if (statusContainer) {
            statusContainer.innerHTML = '';
        } else {
            statusContainer = document.createElement("div");
            statusContainer.id = "status-message-container";
            document.getElementById("contributionForm").appendChild(statusContainer);
        }
        
        const status = document.createElement("p");
        status.className = "text-center my-4 font-bold";
        statusContainer.appendChild(status);

        for (let i = 0; i < 15; i++) {
            const res = await fetch("donationpayment.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                // Use the total amount for polling
                body: JSON.stringify({ phone, amount:paidAmount }) 
            });
            
            const data = await res.json().catch(() => ({}));

            if (data.found) {
                document.getElementById("mpesa_code").value = data.mpesa_code;
                document.getElementById("submit-btn").disabled = false;
                status.innerText = "✅ Payment confirmed! Submitting donation...";
                status.style.color = "green";
                return true;
            }

            status.innerText = `⏳ Waiting for KES ${paidAmount.toFixed(2)} payment... (${i + 1}/15)`;
            await new Promise(resolve => setTimeout(resolve, 3000));
        }

        status.innerText = "❌ Payment not received. Try again.";
        status.style.color = "red";
        return false;
    }

    // Custom message function (minor enhancement added 'info' type)
    function showMessage(msg, type) {
        const modalContent = document.querySelector('.modal-content');
        let messageBox = document.getElementById('messageBox');
        if (!messageBox) {
            messageBox = document.createElement('div');
            messageBox.id = 'messageBox';
            messageBox.style.cssText = `
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                z-index: 1001;
                text-align: center;
                transition: opacity 0.3s ease-in-out;
            `;
            modalContent.appendChild(messageBox);
        }

        messageBox.innerText = msg;
        if (type === "success") {
            messageBox.style.backgroundColor = "#d4edda";
            messageBox.style.color = "#155724";
            messageBox.style.border = "1px solid #c3e6cb";
        } else if (type === "error") {
            messageBox.style.backgroundColor = "#f8d7da";
            messageBox.style.color = "#721c24";
            messageBox.style.border = "1px solid #f5c6cb";
        } else { // 'info' type
            messageBox.style.backgroundColor = "#cce5ff";
            messageBox.style.color = "#004085";
            messageBox.style.border = "1px solid #b8daff";
        }

        messageBox.style.opacity = '1';
        setTimeout(() => {
            // Only hide the automated messages, keep the polling status visible
            if (type !== 'info') { 
                messageBox.style.opacity = '0';
            }
        }, 3000);
    }

    document.getElementById("contributionForm").addEventListener("submit", async function(e) {
        e.preventDefault();
        
        const nationalId = document.getElementById('national_id');
        const errorMessage = document.getElementById('error-message');
        const cleanedIdValue = nationalId.value.replace(/\D/g, '');

        if (cleanedIdValue.length < 7) {
            errorMessage.style.display = 'block';
            return; // Stop execution if ID is invalid
        } else {
            errorMessage.style.display = 'none';
        }

        const phone = document.getElementById("phone").value.trim();
        // Get the base amount entered by the user (or pre-filled from target)
        const baseAmount = parseFloat(document.getElementById("amount").value); 

        if (!phone || isNaN(baseAmount) || baseAmount <= 0) {
            showMessage("Please fill in a valid phone and amount.", "error");
            return;
        }

        document.getElementById("submit-btn").disabled = true;

        // Call the function with the base amount
        const paymentSuccess = await requestAndConfirmPayment(phone, baseAmount);
        
        if (paymentSuccess) {
            // After successful payment confirmation, submit the form.
            // The 'amount' field in the form still holds the base amount, 
            // which is correct for recording the donation amount.
            this.submit();
        } else {
            document.getElementById("submit-btn").disabled = false;
        }
    });
</script>

</body>
</html>
