<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$id_no = $_GET['id_no'] ?? null;
if (!$id_no) {
    echo "<p style='color:red;'>Error: ID number missing. Please provide an ID to view notifications.</p>";
    exit;
}

// Database connection parameters

$host = "localhost";
$db = "digita51_portal";
$user = "digita51_enock";
$pass = "digita51_enock";

// Connect to database
$con = new mysqli($host, $user, $pass, $db);
if ($con->connect_error) {
    // Log error and display a user-friendly message
    error_log("Database connection failed: " . $conn->connect_error);
    die("<p style='color:red;'>Connection failed. Please try again later.</p>");
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="userdashboard.css">
    <style>
        /* General Body Styles (kept mostly the same, but reviewed) */
body {
    background-color: #f8f9fa; /* Light grey background */
    font-family: 'Poppins', sans-serif; /* Professional font */
    margin: 0;
    background-image: url('background.jpeg'); /* Your background image */
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center;
    padding: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.content-wrapper {
    flex-grow: 1;
    padding-top: 30px; /* More space from header */
    padding-bottom: 50px; /* More space before footer */
}

h2 {
    color: white;
    font-weight: 600; /* Slightly bolder for headings */
    text-shadow: 1px 1px 3px rgba(0,0,0,0.1); /* Subtle text shadow for pop */
}

h2 i {
    color: #F25A2C; /* Your accent color */
}

/* Notification Card Styles */
.notification-card {
    background-color: white;
    border-radius: 12px; /* Softer rounded corners */
    box-shadow: 0 4px 12px rgba(0,0,0,0.08); /* More pronounced, softer shadow */
    transition: all 0.3s ease-in-out; /* Smoother hover effect */
    padding: 25px; /* More generous padding inside */
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 120px; /* Ensure a minimum height for consistency */
    border: 1px solid #e0e0e0; /* Subtle border */
    position: relative; /* Needed for the unread indicator */
}

.notification-card:hover {
    transform: translateY(-3px); /* Lift more on hover */
    box-shadow: 0 8px 20px rgba(0,0,0,0.12); /* Stronger shadow on hover */
}

/* Unread indicator (new addition) */
.notification-card.unread::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 6px; /* Width of the indicator bar */
    height: 100%;
    background-color: #F25A2C; /* Accent color */
    border-top-left-radius: 12px;
    border-bottom-left-radius: 12px;
}

/* Adjust padding for cards with unread indicator */
.notification-card.unread {
    padding-left: 31px; /* Original padding + indicator width */
}


.card-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px; /* Space between header and message */
}

.card-title {
    color: #343a40; /* Darker text for readability */
    font-weight: 600; /* Bolder title */
    font-size: 1.15rem; /* Slightly larger title */
    margin-bottom: 0; /* Remove default margin from h5 */
}

.card-text {
    color: #5a6268; /* Slightly darker grey for body text */
    font-size: 0.95rem; /* Slightly larger body text */
    line-height: 1.5; /* Better readability */
    margin-bottom: 15px; /* Space before timestamp */
    flex-grow: 1; /* Allow text to take available space */
}

.card-text small {
    display: block; /* Ensure small tag takes full width */
    color: #888; /* Lighter grey for timestamp */
    font-size: 0.85rem; /* Smaller timestamp */
}

.card-text small i {
    color: #F25A2C; /* Accent color for clock icon */
    margin-right: 5px; /* Space between icon and text */
}

/* Status Badge */
.badge-status {
    font-size: 0.8rem; /* Slightly smaller badge font */
    padding: 5px 10px; /* More padding */
    background-color: #F25A2C;
    color: white;
    border-radius: 20px; /* More pill-shaped */
    font-weight: 500;
    letter-spacing: 0.5px; /* Slight letter spacing */
    box-shadow: 0 2px 4px rgba(242, 90, 44, 0.2); /* Subtle shadow for badge */
}

/* Filter Bar Styles (kept mostly the same, but reviewed) */
.filter-bar {
    background-color: white;
    padding: 25px; /* More padding */
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05); /* Softer shadow */
    margin-bottom: 30px; /* More space below filter bar */
    border: 1px solid #e0e0e0; /* Subtle border */
}

.filter-bar .form-label {
    font-weight: 500;
    color: #343a40;
}

.filter-bar .form-control {
    border-radius: 8px; /* Slightly more rounded inputs */
    padding: 10px 15px;
    border: 1px solid #ced4da;
}

.filter-bar .form-control:focus {
    border-color: #F25A2C;
    box-shadow: 0 0 0 0.25rem rgba(242, 90, 44, 0.25);
}

.btn-primary {
    background-color: #F25A2C;
    border-color: #F25A2C;
    font-weight: 500;
    border-radius: 8px; /* Matching input border-radius */
    padding: 10px 15px;
    transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
}

.btn-primary:hover {
    background-color: #d94e25;
    border-color: #d94e25;
    box-shadow: 0 2px 6px rgba(242, 90, 44, 0.3);
}

.btn-outline-secondary {
    border-color: #ced4da;
    color: #6c757d;
    font-weight: 500;
    border-radius: 8px;
    padding: 10px 15px;
    transition: all 0.2s ease;
}

.btn-outline-secondary:hover {
    background-color: #f0f2f5;
    border-color: #adb5bd;
    color: #495057;
}

/* Container spacing */
.container {
    margin-top: 30px;
    margin-bottom: 50px;
}

/* Responsive Adjustments for Cards */
@media (max-width: 767.98px) {
    .col-md-6 {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
@media (min-width: 768px) and (max-width: 991.98px) {
    .col-lg-4 { /* For example, if you want 2 columns on medium screens */
        flex: 0 0 50%;
        max-width: 50%;
    }
}
    </style>
</head>
<body>
    <?php include 'header.php'; // Include header first ?>

    <div class="container mt-4 mb-5 content-wrapper">
        <h2 class="mb-4 text-center"><i class="fas fa-bell me-2"></i>Notifications</h2>

        <div class="filter-bar row g-3 align-items-end mx-auto" style="max-width: 900px;">
            <div class="col-md-4">
                <label for="startDate" class="form-label">Start Date</label>
                <input type="date" id="startDate" class="form-control">
            </div>
            <div class="col-md-4">
                <label for="endDate" class="form-label">End Date</label>
                <input type="date" id="endDate" class="form-control">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button id="filterBtn" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
                <button id="resetBtn" class="btn btn-outline-secondary"><i class="fas fa-sync-alt"></i></button>
            </div>
        </div>

        <div class="row mt-4 justify-content-center" id="notifications">
            <?php
            

            $sql = "SELECT title, message, created_at FROM post WHERE status='unread' ORDER BY created_at DESC LIMIT 100";

            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                echo "<div class='alert alert-danger'>Error preparing statement: " . htmlspecialchars($conn->error) . "</div>";
                error_log("Error preparing statement for notifications: " . $conn->error);
            } else {
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $formattedDate = date("F j, Y, g:i a", strtotime($row['created_at']));
                        $rawDate = $row['created_at'];

                        echo '
                        <div class="col-md-6 col-lg-4 mb-4 notification-wrapper">
                            <div class="notification-card p-3" data-date="' . htmlspecialchars($rawDate) . '">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h5 class="card-title mb-0">' . htmlspecialchars($row['title']) . '</h5>
                                     <span class="badge-status">Unread</span>
                                </div>
                                <p class="card-text text-muted">' . htmlspecialchars($row['message']) . '</p>
                                <small class="text-muted"><i class="far fa-clock me-1"></i>' . htmlspecialchars($formattedDate) . '</small>
                            </div>
                        </div>';
                    }
                } else {
                    echo "<div class='alert alert-info text-center mx-auto' style='max-width: 600px;'>No unread notifications available for you.</div>";
                }
                $stmt->close();
            }

            $conn->close();
            ?>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('filterBtn').addEventListener('click', function () {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const notificationWrappers = document.querySelectorAll('.notification-wrapper'); // Select the parent wrapper

            notificationWrappers.forEach(wrapper => {
                const card = wrapper.querySelector('.notification-card');
                if (card) { // Ensure card exists within wrapper
                    const cardDate = card.getAttribute('data-date');
                    const show = (!startDate || cardDate >= startDate) && (!endDate || cardDate <= endDate);
                    wrapper.style.display = show ? 'block' : 'none'; // Toggle visibility of the wrapper
                }
            });
        });

        document.getElementById('resetBtn').addEventListener('click', () => {
            document.getElementById('startDate').value = '';
            document.getElementById('endDate').value = '';
            document.querySelectorAll('.notification-wrapper').forEach(wrapper => {
                wrapper.style.display = 'block';
            });
        });
    </script>
</body>
</html>