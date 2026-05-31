<?php
/**
 * register.php — Ghana 2027 International Conference
 * Receives the registration form (POST) and emails the details.
 *
 * DEPLOY (cPanel): upload next to index.html in public_html/.
 *   1) Set $ORGANIZER to the inbox that should receive registrations.
 *   2) Set $FROM to a real mailbox/alias on YOUR domain (cPanel > Email Accounts).
 *
 * RELIABLE DELIVERY (recommended): cPanel's php mail() often lands in spam.
 * For production, send via SMTP with PHPMailer instead of mail():
 *   composer require phpmailer/phpmailer
 *   $m = new PHPMailer\PHPMailer\PHPMailer(true);
 *   $m->isSMTP(); $m->Host='mail.yourdomain.com'; $m->SMTPAuth=true;
 *   $m->Username='no-reply@yourdomain.com'; $m->Password='********';
 *   $m->SMTPSecure='tls'; $m->Port=587; ...
 */

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$ORGANIZER = 'register@ghana2027.org';   // <-- CHANGE: where registrations land
$FROM      = 'no-reply@ghana2027.org';   // <-- CHANGE: a mailbox/alias on your domain

function clean($k) {
    return isset($_POST[$k]) ? trim(filter_var($_POST[$k], FILTER_SANITIZE_FULL_SPECIAL_CHARS)) : '';
}

$first   = clean('firstName');
$last    = clean('lastName');
$email   = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone   = clean('phone');
$country = clean('country');
$org     = clean('organisation');
$cat     = clean('category');
$grc     = clean('grc') ? 'Yes' : 'No';
$needs   = clean('needs');

if (!$first || !$last || !$cat || !$country || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Missing or invalid fields']);
    exit;
}

$ref = 'GH27-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));

$body  = "New registration — Ghana 2027 International Conference\n\n";
$body .= "Reference:        $ref\n";
$body .= "Name:             $first $last\n";
$body .= "Email:            $email\n";
$body .= "Phone:            $phone\n";
$body .= "Country:          $country\n";
$body .= "Organisation:     $org\n";
$body .= "Category:         $cat\n";
$body .= "GRC participation: $grc\n";
$body .= "Access/Dietary:   $needs\n";
$body .= "Submitted:        " . date('Y-m-d H:i:s') . "\n";

$headers  = "From: Ghana 2027 <$FROM>\r\n";
$headers .= "Reply-To: $first $last <$email>\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$sent = mail($ORGANIZER, "[Registration $ref] $first $last — $cat", $body, $headers);

// Auto-acknowledgement to the registrant
$ack  = "Dear $first,\n\n";
$ack .= "Thank you for registering for the Ghana 2027 International Conference\n";
$ack .= "(21-28 February 2027, Accra).\n\n";
$ack .= "Your reference number is $ref.\n";
$ack .= "Our team will contact you with payment instructions and next steps.\n\n";
$ack .= "With warm regards,\nGhana 2027 Conference Team";
@mail($email, "Ghana 2027 - Registration received ($ref)", $ack,
      "From: Ghana 2027 <$FROM>\r\nContent-Type: text/plain; charset=UTF-8\r\n");

if ($sent) {
    echo json_encode(['ok' => true, 'ref' => $ref]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Mail send failed', 'ref' => $ref]);
}
