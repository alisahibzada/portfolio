<?php
header('Content-Type: application/json');

// Database Configuration (Default XAMPP/WAMP settings)
$host = 'localhost';
$dbname = 'ali_portfolio_db';
$username = 'root';
$password = ''; // Leave empty for default XAMPP, or set your MySQL password

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // 1. Sanitize and validate inputs
    $name = htmlspecialchars(strip_tags(trim($_POST['name'] ?? '')));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars(strip_tags(trim($_POST['subject'] ?? 'Portfolio Inquiry')));
    $message = htmlspecialchars(strip_tags(trim($_POST['message'] ?? '')));

    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode([
            "status" => "error",
            "message" => "Please fill in all required fields."
        ]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "status" => "error",
            "message" => "Please enter a valid email address."
        ]);
        exit;
    }

    try {
        // 2. Establish PDO Connection
        $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
        ];

        $pdo = new PDO($dsn, $username, $password, $options);

        // 3. Insert message using a Prepared Statement
        $sql = "INSERT INTO contact_messages (sender_name, sender_email, subject, message) 
                VALUES (:name, :email, :subject, :message)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name'    => $name,
            ':email'   => $email,
            ':subject' => $subject,
            ':message' => $message
        ]);

        // 4. Optional Email Notification to Sahibzada Ali Shah
        $to = "alisahibzada083@gmail.com";
        $emailSubject = "New Portfolio Message: " . $subject;
        $emailBody = "Name: $name\nEmail: $email\n\nMessage:\n$message";
        $headers = "From: $email\r\nReply-To: $email\r\n";
        @mail($to, $emailSubject, $emailBody, $headers);

        echo json_encode([
            "status" => "success",
            "message" => "Thank you, $name! Your message has been saved to the database."
        ]);

    } catch (PDOException $e) {
        // Return a clean error without exposing sensitive database credentials
        echo json_encode([
            "status" => "error",
            "message" => "Database error: Could not save your message. Please try again later."
        ]);
    }
    exit;
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method."
    ]);
    exit;
}
?>