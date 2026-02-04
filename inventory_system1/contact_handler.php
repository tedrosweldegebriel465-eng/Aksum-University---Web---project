<?php
/**
 * Contact Form Handler
 * Processes contact form submissions from homepage
 */
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validate required fields
    if (empty($name)) {
        $response['message'] = 'Please enter your full name.';
        echo json_encode($response);
        exit;
    }
    
    if (empty($email)) {
        $response['message'] = 'Please enter your email address.';
        echo json_encode($response);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
        echo json_encode($response);
        exit;
    }
    
    if (empty($subject)) {
        $response['message'] = 'Please enter a subject.';
        echo json_encode($response);
        exit;
    }
    
    if (empty($message)) {
        $response['message'] = 'Please enter your message.';
        echo json_encode($response);
        exit;
    }
    
    // Additional validation
    if (strlen($name) < 2) {
        $response['message'] = 'Name must be at least 2 characters long.';
        echo json_encode($response);
        exit;
    }
    
    if (strlen($subject) < 5) {
        $response['message'] = 'Subject must be at least 5 characters long.';
        echo json_encode($response);
        exit;
    }
    
    if (strlen($message) < 10) {
        $response['message'] = 'Message must be at least 10 characters long.';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Create contact_messages table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            subject VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            status ENUM('new', 'read', 'replied') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $conn->query($createTable);
        
        // Insert the contact message
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $stmt->bind_param("ssssss", $name, $email, $subject, $message, $ip_address, $user_agent);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Thank you for your message! We have received your inquiry and will get back to you within 24 hours.';
            
            // Optional: Send email notification to admin (if email is configured)
            // You can uncomment and configure this section if you have email setup
            /*
            $admin_email = 'support@inventoryPro.com';
            $email_subject = 'New Contact Form Message: ' . $subject;
            $email_body = "New message from: $name ($email)\n\n";
            $email_body .= "Subject: $subject\n\n";
            $email_body .= "Message:\n$message\n\n";
            $email_body .= "IP Address: $ip_address\n";
            $email_body .= "Submitted: " . date('Y-m-d H:i:s');
            
            $headers = "From: $email\r\n";
            $headers .= "Reply-To: $email\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            mail($admin_email, $email_subject, $email_body, $headers);
            */
            
        } else {
            $response['message'] = 'Sorry, there was an error sending your message. Please try again later.';
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Sorry, there was a technical error. Please try again later.';
        error_log("Contact form error: " . $e->getMessage());
    }
    
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>