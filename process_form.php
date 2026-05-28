<?php
// Prevent unauthorized access and ensure JSON response
header('Content-Type: application/json');

// 1. YOUR EMAIL SETTINGS
$to = "info@illusionxstudios.com"; // Where you want to receive the emails
$from = "no-reply@illusionxstudios.com"; // Must be an email address hosted on your Hostinger account to prevent spam blocking
$subject = "New Project Brief - Illusion X Studios";

// 2. SANITIZE AND COLLECT POST DATA
$name = htmlspecialchars($_POST['name'] ?? '');
$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$email = str_replace(array("\r", "\n"), '', $email); // Prevent header injection
$company = htmlspecialchars($_POST['company'] ?? 'Not provided');
$service = htmlspecialchars($_POST['service'] ?? 'Not selected');
$budget = htmlspecialchars($_POST['budget'] ?? 'Not selected');
$message = htmlspecialchars($_POST['message'] ?? '');

// Basic validation check
if (empty($name) || empty($email) || empty($message)) {
    echo json_encode(["success" => false, "message" => "Required fields are missing."]);
    exit;
}

// 3. BUILD THE EMAIL BODY
$bodyText = "You have a new contact form submission:\n\n";
$bodyText .= "Name: $name\n";
$bodyText .= "Email: $email\n";
$bodyText .= "Company: $company\n";
$bodyText .= "Service: $service\n";
$bodyText .= "Budget: $budget\n";
$bodyText .= "Project Brief:\n$message\n";

// 4. SETUP HEADERS
$headers = "From: $from\r\n";
$headers .= "Reply-To: $email\r\n";

// 5. HANDLE ATTACHMENT (IF UPLOADED)
if (isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK) {
    $file = $_FILES['file'];
    
    // Read file contents and encode it for email transit
    $handle = fopen($file['tmp_name'], "r");
    $content = fread($handle, $file['size']);
    fclose($handle);
    $encoded_content = chunk_split(base64_encode($content));

    $boundary = md5(time());

    // Switch headers to handle mixed content (text + file)
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    // Text Section
    $multipart_body = "--$boundary\r\n";
    $multipart_body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $multipart_body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $multipart_body .= $bodyText . "\r\n\r\n";

    // File Section
    $multipart_body .= "--$boundary\r\n";
    $multipart_body .= "Content-Type: {$file['type']}; name=\"{$file['name']}\"\r\n";
    $multipart_body .= "Content-Disposition: attachment; filename=\"{$file['name']}\"\r\n";
    $multipart_body .= "Content-Transfer-Encoding: base64\r\n\r\n";
    $multipart_body .= $encoded_content . "\r\n\r\n";
    $multipart_body .= "--$boundary--";

    $mail_sent = mail($to, $subject, $multipart_body, $headers);
} else {
    // No attachment uploaded, send standard text email
    $mail_sent = mail($to, $subject, $bodyText, $headers);
}

// 6. RETURN RESPONSE TO JAVASCRIPT
if ($mail_sent) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Mail server rejected the request."]);
}
?>
