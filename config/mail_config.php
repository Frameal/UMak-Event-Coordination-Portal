<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// These paths are correct based on your file structure
require '../libs/PHPMailer/Exception.php';
require '../libs/PHPMailer/PHPMailer.php';
require '../libs/PHPMailer/SMTP.php';

function sendEmail($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true;
        
        // --- CHANGE THESE TWO LINES ONLY ---
        $mail->Username   = ''; // Your full Gmail address
        $mail->Password   = '';         // The 16-digit App Password (remove spaces if needed)
        // -----------------------------------

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('your_actual_gmail@gmail.com', 'UMak ECP'); // Gmail forces the Sender to be YOU
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Formal HTML Email Template
        $template = '
        <div style="font-family: Century Gothic, sans-serif; max-width: 600px; margin: 0 auto; background-color: #f4f5f7; padding: 20px;">
            <div style="background-color: #022d6d; padding: 20px; text-align: center; color: white;">
                <h2 style="margin: 0;">University of Makati</h2>
                <p style="margin: 5px 0 0; font-size: 14px;">Event Coordination Portal</p>
            </div>
            <div style="background-color: white; padding: 30px; border-radius: 4px; margin-top: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                ' . $body . '
            </div>
            <div style="text-align: center; color: #888; font-size: 12px; margin-top: 20px;">
                &copy; 2025 University of Makati. All rights reserved.
            </div>
        </div>';

        $mail->Body = $template;
        $mail->send();
        return true;
    } catch (Exception $e) {
        // For debugging: uncomment the line below to see the exact error in your browser Network tab
        // error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}

?>
