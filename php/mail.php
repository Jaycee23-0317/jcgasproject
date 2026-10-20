<?php

/**
 * JC Gas - PHPMailer Integration
 * Reusable mail helper using the bundled phpMailer library.
 *
 * - Sends OTP verification emails (from jcgasproject@gmail.com -> user's Gmail)
 * - Also supports the legacy contact-form POST handler
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Required files - fixed absolute paths (works no matter the working directory)
require __DIR__ . '/../phpMailer/src/Exception.php';
require __DIR__ . '/../phpMailer/src/PHPMailer.php';
require __DIR__ . '/../phpMailer/src/SMTP.php';

/**
 * Create a configured PHPMailer instance using JC Gas Gmail SMTP.
 * @return PHPMailer
 */
function getMailer()
{
    $mail = new PHPMailer(true);

    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'jcgasproject@gmail.com';
    $mail->Password   = 'knkmiifciaplgfbz'; // Gmail App Password
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';
    $mail->SMTPDebug  = 0; // Set to 1/2 for troubleshooting

    return $mail;
}

/**
 * Send the OTP verification email to the user's Gmail address.
 *
 * @param string $toEmail  Recipient Gmail address
 * @param string $otp      6-digit code
 * @param string $username Optional username for a personalized greeting
 * @return bool True on success, false on failure (error in $GLOBALS['mail_error'])
 */
function sendOTPEmail($toEmail, $otp, $username = '')
{
    try {
        $mail = getMailer();

        // Recipients
        $mail->setFrom('jcgasproject@gmail.com', 'JC Gas');
        $mail->addAddress($toEmail);
        $mail->addReplyTo('jcgasproject@gmail.com', 'JC Gas');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'JC Gas - Your OTP Verification Code';

        $greeting = $username !== '' ? htmlspecialchars($username) : 'there';
        $mail->Body    = "
            <div style='font-family:Arial,sans-serif;max-width:520px;margin:auto;border:1px solid #e3e3e3;border-radius:8px;overflow:hidden'>
                <div style='background:#280cff;color:#fff;padding:18px 24px;font-size:20px;font-weight:bold'>
                    🔥 JC Gas &mdash; Verify Your Gmail
                </div>
                <div style='padding:24px'>
                    <p>Hi {$greeting},</p>
                    <p>Your JC Gas account verification code is:</p>
                    <p style='text-align:center;font-size:32px;font-weight:bold;letter-spacing:8px;color:#280cff;background:#f1f0ff;padding:14px;border-radius:6px'>{$otp}</p>
                    <p>This code expires in <strong>10 minutes</strong>. Do not share it with anyone.</p>
                    <p style='color:#888;font-size:13px;margin-top:24px'>If you did not request this, you can safely ignore this email.</p>
                </div>
            </div>
        ";
        $mail->AltBody = "Hi {$greeting},\n\nYour JC Gas verification code is: {$otp}\n\nThis code expires in 10 minutes.\n\n- JC Gas Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        $GLOBALS['mail_error'] = $e->getMessage();
        return false;
    }
}

function sendOrderStatusEmail($recipientEmail, $customerName, $orderGroupID, $transactionID, $newStatus)
{
    // 1. Customize the email look and headings based on the active tracking status text string
    $statusColor = '#ffc107'; // Default yellow warning for pending states
    $statusDescription = "Your order status has been updated.";

    if ($newStatus === 'Pending Review') {
        $statusColor = '#ffc107'; // Bootstrap Primary Blue
        $statusDescription = "Your order has been successfully placed and is now pending review. Our team will review your order and confirm it shortly.";
    } elseif ($newStatus === 'Order Placed') {
        $statusColor = '#0d6efd'; // Bootstrap Primary Blue
        $statusDescription = "Great news! Your order has been officially accepted and is now being prepared by our team.";
    } elseif ($newStatus === 'Out for Delivery') {
        $statusColor = '#fd7e14'; // Orange
        $statusDescription = "Get ready! Your items have been prepared and are now out for active delivery to your address.";
    } elseif ($newStatus === 'Completed') {
        $statusColor = '#198754'; // Success Green
        $statusDescription = "Transaction finalized! Your order has been successfully delivered and completed. Thank you for ordering with us!";
    } elseif ($newStatus === 'Cancelled') {
        $statusColor = '#dc3545'; // Danger Red
        $statusDescription = "We regret to inform you that your order has been cancelled.";
    }

    // 2. Build a modern, clean HTML structural template layout block
    $subject = "Order #{$orderGroupID} Update: {$newStatus}";

    $messageBody = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 8px;'>
        <div style='text-align: center; margin-bottom: 20px;'>
            <h2 style='color: #333;'>Order Tracking Notification</h2>
        </div>
        <p>Hello <strong>" . htmlspecialchars($customerName) . "</strong>,</p>
        <p>{$statusDescription}</p>
        
        <div style='background-color: #f8f9fa; border-left: 4px solid {$statusColor}; padding: 15px; margin: 20px 0; border-radius: 4px;'>
            <p style='margin: 0; font-size: 14px; color: #666;'>Order Group Reference:</p>
            <p style='margin: 5px 0 15px 0; font-size: 18px; font-weight: bold; color: #333;'>#{$orderGroupID}</p>

            <p style='margin: 0; font-size: 14px; color: #666;'>Transaction ID:</p>
            <p style='margin: 5px 0 15px 0; font-size: 18px; font-weight: bold; color: #333;'>#{$transactionID}</p>
            
            <p style='margin: 0; font-size: 14px; color: #666;'>Current Status:</p>
            <span style='display: inline-block; background-color: {$statusColor}; color: " . ($newStatus === 'Order Placed' || $newStatus === 'Cancelled' || $newStatus === 'Completed' ? '#ffffff' : '#333333') . "; padding: 6px 12px; font-weight: bold; border-radius: 4px; margin-top: 5px; font-size: 14px;'>
                " . htmlspecialchars($newStatus) . "
            </span>
        </div>
        
        <p style='font-size: 12px; color: #999; margin-top: 30px; text-align: center; border-top: 1px solid #eee; padding-top: 15px;'>
            This is an automated operational notification update from our system. Please do not reply directly to this mail.
        </p>
    </div>
    ";

    try {
        // FIX: Pull pre-configured server settings from your custom helper function instance!
        $mail = getMailer();

        // Set properties cleanly
        $mail->setFrom('jcgasproject@gmail.com', 'JC Gas');
        $mail->addAddress($recipientEmail);
        $mail->addReplyTo('jcgasproject@gmail.com', 'JC Gas');
        $mail->isHTML(true);

        $mail->Subject = $subject;
        $mail->Body    = $messageBody;
        $mail->AltBody = "Hello " . htmlspecialchars($customerName) . ",\n\nYour Order #{$orderGroupID} status has been updated to: {$newStatus}.\n\n- JC Gas Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        $GLOBALS['mail_error'] = $e->getMessage();
        return false;
    }
}


// ------------------------------------------------------------------
// Legacy contact-form POST handler (kept from the original mail.php)
// ------------------------------------------------------------------
// if (isset($_POST["send"])) {
//     try {
//         $mail = getMailer();

//         // Recipients
//         $mail->setFrom($_POST["email"], $_POST["name"]);          // Sender email and name
//         $mail->addAddress('jcgasproject@gmail.com');              // Recipient
//         $mail->addReplyTo($_POST["email"], $_POST["name"]);       // Reply to sender

//         // Content
//         $mail->isHTML(true);
//         $mail->Subject = $_POST["subject"];
//         $mail->Body    = $_POST["message"];

//         $mail->send();
//         echo "
//         <script>
//             alert('Message was sent successfully!');
//             document.location.href = 'index.php';
//         </script>
//         ";
//     } catch (Exception $e) {
//         echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
//     }
// }
