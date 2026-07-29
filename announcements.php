<?php
include 'connect.php';

// Fetch all posts, newest first
$sql = "SELECT * FROM post ORDER BY created_at DESC";
$result = $con->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
    <style>
        /* Theme Colors */
        .text-royal-blue { color: #003366 !important; }
        .bg-royal-blue { background-color: #003366 !important; }
        .text-theme-orange { color: #FF8C00 !important; }
        .text-theme-green { color: #28a745 !important; } /* Theme Green */

        /* General Card Styling */
        .card-announcement {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            border-left: 5px solid; /* For status indication */
            border-color: #dee2e6; /* Default border color */
        }
        .card-announcement:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        /* Status: Unread (Orange) */
        .card-unread {
            border-left-color: #FF8C00 !important; /* Orange border for unread */
            background-color: #fff9f5; /* Very light orange background */
            box-shadow: 0 0.25rem 0.75rem rgba(255, 140, 0, 0.2) !important;
        }

        /* Status: LATEST Announcement (Green) */
        .card-latest {
            border-left-color: #28a745 !important; /* Green border for the LATEST post */
            background-color: #e6ffec; /* Very light green background */
            box-shadow: 0 0.25rem 0.75rem rgba(40, 167, 69, 0.3) !important;
        }
        .card-latest .card-title {
             color: #003366 !important; /* Keep title royal blue even for latest */
        }
        .card-latest .text-muted {
            font-weight: bold; /* Make details stand out */
        }
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a2d9d5b3f3.js" crossorigin="anonymous"></script>
</head>
<body class="bg-light">

<div class="container py-5">
    <h2 class="mb-5 text-center text-royal-blue fw-bold" style="text-transform: uppercase;">📢 Announcements</h2>

    <?php if ($result->num_rows > 0): ?>
        <div class="row justify-content-center">
            <div class="col-lg-8">
            <?php 
            $is_first_post = true; // Flag to identify the latest post (since the query is DESC)
            while($row = $result->fetch_assoc()): 
                $is_unread = $row['status'] === 'unread';
                
                // Determine the special status class
                if ($is_first_post) {
                    $card_class = 'card-latest'; // Overrides unread for the newest post
                    $icon_class = 'fas fa-star text-theme-green';
                    $title_class = 'text-royal-blue';
                    $is_first_post = false; // Turn the flag off after the first iteration
                } elseif ($is_unread) {
                    $card_class = 'card-unread';
                    $icon_class = 'fas fa-exclamation-circle text-theme-orange';
                    $title_class = 'text-royal-blue';
                } else {
                    $card_class = 'border-secondary';
                    $icon_class = 'fas fa-bullhorn text-royal-blue';
                    $title_class = 'text-dark';
                }
            ?>
                <div class="card card-announcement mb-4 shadow-sm <?php echo $card_class; ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <i class="<?php echo $icon_class; ?> me-3 mt-1 fs-4" title="<?php echo $is_first_post ? 'Latest Announcement' : 'Announcement'; ?>"></i>

                            <div class="flex-grow-1">
                                <h5 class="card-title fw-bold mb-2 <?php echo $title_class; ?>">
                                    <?php echo htmlspecialchars($row['title']); ?>
                                    <?php if ($card_class === 'card-latest'): ?>
                                        <span class="badge bg-theme-green text-white ms-2">LATEST</span>
                                    <?php endif; ?>
                                </h5>
                                <p class="card-text text-secondary mb-3">
                                    <?php echo nl2br(htmlspecialchars($row['message'])); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center border-top pt-2">
                                    <small class="text-muted">
                                        Posted by: <strong class="text-royal-blue"><?php echo htmlspecialchars($row['posted_by']); ?></strong>
                                    </small>
                                    <small class="text-muted fst-italic">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date("M d, Y h:i A", strtotime($row['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>
        </div>
    <?php else: ?>
        <p class="text-center text-muted py-5"><i class="fas fa-inbox me-2"></i>No announcements yet.</p>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>