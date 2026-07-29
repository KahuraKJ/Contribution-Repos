<?php
session_start();
include 'header.php';
include 'connect.php'; 

/**
 * 1. ACCESS CONTROL & SECURITY
 */
if (!isset($_SESSION['id_no']) || !$con) {
    header("location: index.php"); 
    exit;
}

$current_member_id = $_SESSION['id_no']; 
$full_name = "Member"; 

// Fetch Member's Full Name
$stmt_name = $con->prepare("SELECT full_name FROM personal WHERE id_no = ?");
$stmt_name->bind_param("s", $current_member_id);
$stmt_name->execute();
$name_data = $stmt_name->get_result()->fetch_assoc();
if ($name_data) { $full_name = $name_data['full_name']; }
$stmt_name->close();

/**
 * 2. REFERRAL TOKEN GENERATION
 * Ensures every user has a unique random string for their link
 */
$token = "";
$stmt_tok = $con->prepare("SELECT token FROM referral_tokens WHERE referrer_id_no = ?");
$stmt_tok->bind_param("s", $current_member_id);
$stmt_tok->execute();
$res_tok = $stmt_tok->get_result();

if ($row_tok = $res_tok->fetch_assoc()) {
    $token = $row_tok['token'];
} else {
    // Generate 16-character secure token if none exists
    $token = bin2hex(random_bytes(8)); 
    $stmt_ins = $con->prepare("INSERT INTO referral_tokens (referrer_id_no, token) VALUES (?, ?)");
    $stmt_ins->bind_param("ss", $current_member_id, $token);
    $stmt_ins->execute();
    $stmt_ins->close();
}
$stmt_tok->close();

// Construction of the public referral link
$referral_link = "https://digitalboda.co.ke/portal/registration.html?ref=" . $token;

/**
 * 3. AGGREGATE STATS & REFERRAL HISTORY
 */
$total_referrals = 0;
$total_earnings = 0.00;
$referred_members_data = [];

$stmt_list = $con->prepare("
    SELECT 
        r.referred_id_no, 
        r.commission_amount, 
        r.transaction_date, 
        r.status,
        p.full_name 
    FROM referrals r
    LEFT JOIN personal p ON r.referred_id_no = p.id_no
    WHERE r.referrer_id_no = ? 
    ORDER BY r.transaction_date DESC
");
$stmt_list->bind_param("s", $current_member_id);
$stmt_list->execute();
$result_list = $stmt_list->get_result();

while ($row = $result_list->fetch_assoc()) {
    $referred_members_data[] = $row;
    $total_referrals++;
    $total_earnings += (float)$row['commission_amount'];
}
$stmt_list->close();

// Prepare WhatsApp Share URL
$share_message = "Hello! Join Digital Boda today and start earning. Register using my link: " . $referral_link;
$whatsapp_url = "https://api.whatsapp.com/send?text=" . urlencode($share_message);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referral Dashboard | Digital Boda</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #030833;
            --primary-dark: #ff5e00;
            --whatsapp: #25D366;
            --success: #10b981;
            --pending: #f59e0b;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text-dark);
            line-height: 1.5;
        }

        .container {
            max-width: 850px;
            margin: 40px auto;
        }

        /* Hero Card Style */
        .main-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            margin-bottom: 24px;
            border: 1px solid #e2e8f0;
        }

        .card-header {
            padding: 40px 30px;
            background: linear-gradient(135deg, #ff5e00 0%, #ff5e00 100%);
            color: white;
            text-align: center;
        }

        .card-header h1 { margin: 0; font-size: 1.8rem; font-weight: 700; }
        .card-header p { margin: 10px 0 0; opacity: 0.9; font-size: 1rem; }

        .card-content { padding: 40px; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: #f1f5f9;
            padding: 24px;
            border-radius: 16px;
            text-align: center;
            transition: transform 0.2s;
        }

        .stat-card:hover { transform: translateY(-3px); }
        .stat-card span { display: block; font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-card h2 { margin: 8px 0 0; font-size: 1.5rem; color: var(--primary); font-weight: 800; }

        /* Referral Link Box */
        .link-section { margin-bottom: 30px; }
        .section-title { font-size: 0.9rem; font-weight: 600; color: var(--text-muted); margin-bottom: 12px; display: block; }
        
        .copy-group {
            display: flex;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px;
            align-items: center;
        }

        #referralInput {
            flex: 1;
            border: none;
            background: transparent;
            padding: 10px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.85rem;
            color: var(--text-dark);
            outline: none;
        }

        .btn-copy {
            background: var(--primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-copy:hover { background: var(--primary-dark); }

        .btn-whatsapp {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: var(--whatsapp);
            color: white;
            text-decoration: none;
            padding: 16px;
            border-radius: 12px;
            font-weight: 700;
            margin-top: 15px;
            transition: filter 0.2s;
        }

        .btn-whatsapp:hover { filter: brightness(0.95); }

        /* Table Section */
        .history-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .history-card h3 { margin: 0 0 20px; font-size: 1.25rem; }

        .responsive-table { width: 100%; overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; min-width: 500px; }
        th { text-align: left; padding: 12px; border-bottom: 2px solid #f1f5f9; color: var(--text-muted); font-size: 0.85rem; }
        td { padding: 16px 12px; border-bottom: 1px solid #f1f5f9; font-size: 0.9rem; }

        .badge {
            padding: 4px 12px;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-completed { background: #dcfce7; color: #166534; }
        .status-pending { background: #fef3c7; color: #92400e; }

        @media (max-width: 640px) {
            .card-content { padding: 25px; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="main-card">
        <div class="card-header">
            <h1 style="text-transform: uppercase;">Welcome, <?php echo htmlspecialchars($full_name); ?></h1>
            <p>Earn 25% commission for every friend you refer</p>
        </div>

        <div class="card-content">
            <div class="stats-grid">
                <div class="stat-card">
                    <span>Successful Referrals</span>
                    <h2><?php echo $total_referrals; ?></h2>
                </div>
                <div class="stat-card">
                    <span>Total Commission</span>
                    <h2>KSh <?php echo number_format($total_earnings, 2); ?></h2>
                </div>
            </div>

            <div class="link-section">
                <span class="section-title">Your Unique Invitation Link</span>
                <div class="copy-group">
                    <input type="text" id="referralInput" value="<?php echo $referral_link; ?>" readonly>
                    <button id="copyBtn" class="btn-copy">Copy Link</button>
                </div>
                <a href="<?php echo $whatsapp_url; ?>" class="btn-whatsapp" target="_blank">
                    Share via WhatsApp
                </a>
            </div>
        </div>
    </div>

    <div class="history-card">
        <h3>Referral Activity</h3>
        <div class="responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Member Name</th>
                        <th>Joined Date</th>
                        <th>Commission</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($referred_members_data)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                No referrals yet. Share your link to start earning!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($referred_members_data as $row): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['full_name'] ?? 'Incomplete Reg'); ?></strong></td>
                                <td><?php echo date('d M Y', strtotime($row['transaction_date'])); ?></td>
                                <td>KSh <?php echo number_format($row['commission_amount'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo ($row['status'] == 'completed') ? 'status-completed' : 'status-pending'; ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    const copyBtn = document.getElementById('copyBtn');
    const referralInput = document.getElementById('referralInput');

    copyBtn.addEventListener('click', async () => {
        try {
            referralInput.select();
            await navigator.clipboard.writeText(referralInput.value);
            
            // Visual feedback
            const originalText = copyBtn.innerText;
            copyBtn.innerText = "Copied!";
            copyBtn.style.backgroundColor = "var(--success)";
            
            setTimeout(() => {
                copyBtn.innerText = originalText;
                copyBtn.style.backgroundColor = "var(--primary)";
            }, 2000);
        } catch (err) {
            console.error('Failed to copy text: ', err);
        }
    });
</script>

</body>
</html>