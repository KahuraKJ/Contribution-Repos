<?php
if (isset($_POST['submit'])) {
    // Collect and sanitize form data
    $name = htmlspecialchars($_POST['name']);
    $email = htmlspecialchars($_POST['email']);
    $message = htmlspecialchars($_POST['message']);

    // Recipient email address
    $to = 'info@digitalboda.co.ke';

    // Subject of the email
    $subject = "New Support Message from DigitalBoda Website";

    // Email headers
    $headers = "From: " . $name . " <" . $email . ">" . "\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

    // Email body (using HTML for better formatting)
    $email_body = "<html><body>";
    $email_body .= "<h2>New Support Inquiry</h2>";
    $email_body .= "<p><strong>Name:</strong> " . $name . "</p>";
    $email_body .= "<p><strong>Email:</strong> " . $email . "</p>";
    $email_body .= "<p><strong>Message:</strong></p>";
    $email_body .= "<p>" . nl2br($message) . "</p>";
    $email_body .= "</body></html>";

    // Send the email
    if (mail($to, $subject, $email_body, $headers)) {
        // Redirect back to the form with a success message
        header('Location: support.html?status=success');
    } else {
        // Redirect back with an error message
        header('Location: support.html?status=error');
    }
} else {
    // If the form wasn't submitted, redirect to the support page
    header('Location: support.html');
}
exit;
?>