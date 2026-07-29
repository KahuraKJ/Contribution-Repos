<?php
@include 'connect.php';

// Verify database connection
if (!isset($con) || !$con) {
    die("<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative text-center mx-auto my-4' role='alert'>
            <strong class='font-bold'>Connection Error:</strong>
            <span class='block sm:inline'>Could not connect to the database. Please check your 'connect.php' file.</span>
        </div>");
}

$member_data = null;
$error_message = null;
$photo_path = null;
$db_image_path = null; // New variable to store the path from the database

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['id_no']) && !empty($_POST['id_no'])) {
        $id_no = $_POST['id_no'];

        // SQL query to fetch member details AND the image_path from the products (pr) table
        $sql = "SELECT
                    l.user_code,
                    p.full_name,
                    p.id_no,
                    p.date_of_birth,
                    p.gender,
                    p.blood_group,
                    p.created_at,
                    c.contact_number,
                    m.plate_number,
                    m.policy_expiry_date,
                    r.estate,
                    pr.image_path  -- <<< Added image path from products table
                FROM personal p
                LEFT JOIN contact_address c ON p.id_no = c.id_no
                LEFT JOIN mode_of_travel m ON p.id_no = m.id_no
                LEFT JOIN residential r ON p.id_no = r.id_no
                LEFT JOIN login l ON p.id_no = l.id_no
                LEFT JOIN products pr ON p.id_no = pr.id_no  -- <<< Assuming the products table holds the image_path
                WHERE p.id_no = ?
                LIMIT 1";

        try {
            $stmt = $con->prepare($sql);
            $stmt->bind_param("s", $id_no);
            $stmt->execute();
            $result = $stmt->get_result();
            $member_data = $result->fetch_assoc();
            $stmt->close();

            if (!$member_data) {
                $error_message = "Member with ID: " . htmlspecialchars($id_no) . " not found.";
            } else {
                // --- PHOTO LOGIC START: Prioritize DB photo, then uploaded photo ---
                
                // 1. Check for database image path
                $db_image_path = $member_data['image_path'] ?? null;
                
                if (!empty($db_image_path)) {
                    // Use the database photo URL, constructing the full path as requested
                    $photo_path = 'https://portal.digitalboda.co.ke/' . htmlspecialchars(ltrim($db_image_path, '/'));
                }

                // 2. Check for uploaded photo as a fallback
                if (empty($photo_path) && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $photo_tmp_name = $_FILES['photo']['tmp_name'];
                    $photo_data = file_get_contents($photo_tmp_name);
                    // Convert uploaded image to a base64 data URI
                    $photo_path = 'data:image/' . pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION) . ';base64,' . base64_encode($photo_data);
                }

                // Final check: if no photo is available from either source, show an error
                if (empty($photo_path)) {
                    $error_message = "No photo found for this member (neither in the database nor uploaded).";
                    $member_data = null; // Prevent card display if no photo
                }
                // --- PHOTO LOGIC END ---
            }

        } catch (mysqli_sql_exception $e) {
            $error_message = "Database query failed: " . $e->getMessage();
        }

        if (isset($con) && $con->ping()) {
             // Close connection only after the query execution
            $con->close();
        }
    } else {
        $error_message = "Please provide a valid member ID number.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DigitalBoda ID Card Generator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
        body { font-family: 'Inter', sans-serif; }
        
        /* Define a consistent size for the ID card sections */
        .card-section {
            width: 420px; /* Standard ID card width (approx 85.6mm) */
            height: 300px; /* Standard ID card height (approx 53.98mm) */
            margin: 1.5rem auto;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            background-color: white;
            box-sizing: border-box;
        }
        
        .print-hidden {
            @media print { display: none !important; }
        }

        /* CRITICAL FIX: Force background colors/gradients and images to print */
        .print-color-adjust {
            -webkit-print-color-adjust: exact !important; 
            color-adjust: exact !important;
        }

        /* Styles specifically for printing to display both on one sheet */
        @media print {
            body {
                background-color: white !important;
                margin: 0;
                padding: 0;
                display: block;
            }
            /* Hide everything that's not the card content */
            body > *:not(.print-card-wrapper) {
                display: none !important;
            }
            .print-card-wrapper {
                display: block;
                width: fit-content;
                margin: 0 auto;
            }
            .card-section {
                margin: 10px auto;
                box-shadow: none;
                border: 1px solid #000;
                break-inside: avoid;
            }
            
            /* >>>>>>>>>>>>>>> BACK-TO-BACK PRINTING FIX <<<<<<<<<<<<<<< */
            #card-front {
                /* Force a page break after the front side is printed */
                page-break-after: always;
            }
            /* >>>>>>>>>>>>>>> BACK-TO-BACK PRINTING FIX <<<<<<<<<<<<<<< */
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col items-center justify-center min-h-screen p-4">

<h1 class="text-3xl font-bold text-gray-800 mb-2 print-hidden">DigitalBoda ID Card Generator</h1>
<p class="text-sm text-gray-600 mb-4 print-hidden">Enter member details and **optionally** upload a photo to generate their ID card.</p>

<div class="bg-white p-6 rounded-lg shadow-md w-full max-w-sm mb-8 print-hidden">
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label for="id_no" class="block text-sm font-medium text-gray-700">Member ID Number</label>
            <input type="text" name="id_no" id="id_no" value="<?= isset($_POST['id_no']) ? htmlspecialchars($_POST['id_no']) : ''; ?>" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
        </div>
        <div>
            <label for="photo" class="block text-sm font-medium text-gray-700">Upload Member Photo (Optional fallback)</label>
            <input type="file" name="photo" id="photo" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-violet-50 file:text-violet-700 hover:file:bg-violet-100">
        </div>
        <button type="submit" class="w-full py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Generate ID Card
        </button>
    </form>
</div>

<?php if ($member_data && $photo_path): ?>

<div class="print-card-wrapper">
    <div id="card-front" class="card-section text-gray-800">
        <div class="bg-gradient-to-br from-blue-900 to-indigo-800 text-white p-4 print-color-adjust">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <img src="https://digitalboda.co.ke/portal/Digital-logo.png" class="w-10 h-10 rounded-full shadow-md print-color-adjust" alt="DigitalBoda Logo">
                    <h2 class="text-xl font-extrabold tracking-wide">DigitalBoda</h2>
                </div>
                <span class="text-xs bg-orange-500 px-2 py-1 rounded font-semibold shadow print-color-adjust">MEMBER</span>
            </div>
        </div>

        <div class="p-6 flex flex-col h-[calc(100%-60px)] z-10">
            <div class="flex items-center flex-grow">
                <div class="flex-shrink-0 mr-4">
                    <img src="<?= htmlspecialchars($photo_path); ?>" 
                         class="w-[100px] h-[100px] object-cover border-2 border-blue-700 shadow-md" 
                         alt="Member Photo">
                </div>

                <div class="border-l border-gray-300 h-[80px] mx-4"></div>

                <div class="flex-grow text-xs leading-tight">
                    <p><span class="font-semibold">Name:</span> <?= htmlspecialchars($member_data['full_name']); ?></p>
                    <p><span class="font-semibold">ID No:</span> <?= htmlspecialchars($member_data['id_no']); ?></p>
                    <p><span class="font-semibold">User Code:</span> <?= htmlspecialchars($member_data['user_code']); ?></p>
                    <p><span class="font-semibold">Blood Group:</span> <?= htmlspecialchars($member_data['blood_group']); ?></p>
                    <p><span class="font-semibold">Plate No:</span> <?= htmlspecialchars($member_data['plate_number']); ?></p>
                </div>
            </div>

            <div class="mt-auto text-[9px] text-center opacity-90 pt-2 border-t border-gray-200 text-gray-700">
                <p>www.digitalboda.com</p>
                <p>Valid until: <span class="font-bold text-orange-600 print-color-adjust"><?= date('d M Y', strtotime($member_data['policy_expiry_date'] ?? '+1 year')); ?></span></p>
            </div>
        </div>
    </div>

    <div id="card-back" class="card-section text-gray-800">
        <div class="p-6 flex flex-col h-full">
            <h2 class="text-sm font-bold text-blue-900 mb-3 border-b border-gray-300 pb-1">Member Information</h2>
            
            <div class="text-[10px] grid grid-cols-2 gap-y-2 gap-x-4 leading-tight flex-grow">
                
                <div><span class="font-semibold">Contact:</span> <?= htmlspecialchars($member_data['contact_number']); ?></div>
                <div class="col-span-2"><span class="font-semibold">Address:</span> <?= htmlspecialchars($member_data['estate']); ?></div>
                <div><span class="font-semibold">Date of Birth:</span> <?= htmlspecialchars(date('d M Y', strtotime($member_data['date_of_birth']))); ?></div>
                
                <div class="col-span-1 flex flex-col justify-center items-center p-1 bg-gray-50 border border-gray-200 rounded print-color-adjust">
                    <span class="font-semibold text-xs mb-1 text-center text-blue-800">Scan for Verification</span>
                    <?php
                    // Data to encode (Verification URL + ID number)
                    $qr_data = 'https://digitalboda.co.ke/member_lookup.php?id=' . urlencode($member_data['id_no']);
                    // NEW QR CODE API: GoQR.me
                    $qr_code_url = 'https://api.qrserver.com/v1/create-qr-code/?size=60x60&data=' . urlencode($qr_data);
                    ?>
                    <img src="<?= htmlspecialchars($qr_code_url); ?>" 
                        alt="Verification QR Code" 
                        class="w-12 h-12 print-color-adjust">
                </div>      
                
                <div><span class="font-semibold">Created:</span> <?= htmlspecialchars(date('d M Y', strtotime($member_data['created_at']))); ?></div>
                
            </div>
            <div class="mt-auto flex justify-between items-end text-[10px] pt-1 border-t border-gray-200">
                <img src="https://digitalboda.co.ke/portal/sign1.png" alt="Signature/Logo" class="w-50 h-20 border border-gray-400 rounded-sm shadow-sm print-color-adjust">
                <div class="text-right">
                    <p class="font-bold text-orange-600 print-color-adjust">Official ID - Do Not Duplicate</p>
                    <p class="italic">https://digitalboda.co.ke</p>
                    <div class="mt-1 font-bold text-red-600 text-left bg-red-50 border border-red-200 p-1 rounded print-color-adjust">
                        <p>If found, contact:</p>
                        <p>+254 710 353974</p>
                        <p>admin@digitalboda.co.ke</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="flex justify-center items-center space-x-4 mt-8 print-hidden">
    <button onclick="printIDCard()" class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg shadow-lg transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
        Print ID
    </button>
    <button onclick="sendIDCard()" class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg shadow-lg transition-all duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50">
        Send to Member
    </button>
</div>

<div id="status-message" class="mt-4 text-sm text-gray-600 text-center"></div>

<?php elseif ($_SERVER["REQUEST_METHOD"] == "POST" && $error_message): ?>
<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 text-center rounded-lg shadow-md mx-auto max-w-sm mt-8">
    <p><?= htmlspecialchars($error_message); ?></p>
</div>
<?php endif; ?>

<script>
    function printIDCard() {
        // This command opens the print dialog, applying the @media print styles.
        window.print();
    }

    async function sendIDCard() {
        const statusMessage = document.getElementById('status-message');
        const memberID = "<?= htmlspecialchars($member_data['id_no'] ?? ''); ?>";

        if (!memberID) {
            statusMessage.textContent = 'Member ID not available for sending.';
            statusMessage.className = 'mt-4 text-sm text-red-600 font-semibold text-center';
            return;
        }

        statusMessage.textContent = 'Generating and sending ID card...';
        statusMessage.className = 'mt-4 text-sm text-gray-600 text-center';

        try {
            // Capture the front and back sections separately.
            // Note: If you want to include print styles (like background colors) in the image, 
            // ensure the html2canvas options are set to include them, or ensure your CSS uses
            // print-friendly techniques.
            const frontCanvas = await html2canvas(document.getElementById('card-front'), { scale: 2 });
            const backCanvas = await html2canvas(document.getElementById('card-back'), { scale: 2 });

            const frontImage = frontCanvas.toDataURL('image/jpeg');
            const backImage = backCanvas.toDataURL('image/jpeg');

            const response = await fetch('send_id_card.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id_no: memberID,
                    front_image: frontImage,
                    back_image: backImage
                })
            });

            const result = await response.json();

            if (response.ok) {
                statusMessage.textContent = result.message;
                statusMessage.className = 'mt-4 text-sm text-green-600 font-semibold text-center';
            } else {
                statusMessage.textContent = result.message || 'An error occurred. Please try again.';
                statusMessage.className = 'mt-4 text-sm text-red-600 font-semibold text-center';
            }
        } catch (error) {
            console.error('Error:', error);
            statusMessage.textContent = 'Failed to generate or send the ID card.';
            statusMessage.className = 'mt-4 text-sm text-red-600 font-semibold text-center';
        }
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalElement = document.getElementById('memberSummaryModal');
    
    // Add event listener to the new button
    document.getElementById('generateIdCardButton').addEventListener('click', function() {
        // 1. Get the Member ID from a span element in the modal
        const memberId = document.getElementById('summaryIdNo').textContent.trim(); 

        if (memberId) {
            // 2. Hide the modal
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
                modal.hide();
            }
            
            // 3. Redirect the user, passing the ID number as a URL parameter
            window.location.href = 'idCard2.php?id_no=' + encodeURIComponent(memberId);
        } else {
            alert('Error: Could not retrieve Member ID.');
        }
    });

});
</script>
</body>
</html>