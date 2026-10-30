<?php
session_start();
include 'connection.php';

$message = '';
$message_type = ''; // 'success' or 'error'

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if(empty($email)) {
        $message = "Please enter your email address";
        $message_type = 'error';
    } else {
        // Check if email exists in database
        $check_sql = "SELECT id, fullname FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0) {
            $user = $check_result->fetch_assoc();
            $user_id = $user['id'];
            $fullname = $user['fullname'];
            
            // Generate a unique reset token
            $reset_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', time() + 3600); // Token valid for 1 hour
            
            // Save token to database
            $token_sql = "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)";
            $token_stmt = $conn->prepare($token_sql);
            $token_stmt->bind_param("iss", $user_id, $reset_token, $expires_at);
            
            if($token_stmt->execute()) {
                // Create reset link for development
                $reset_link = "http://localhost/assg/reset_password.php?token=" . $reset_token;
                
                $message = "Password reset link created!<br><br>
                           <div style='text-align: center;'>
                               <a href='$reset_link' style='color: white; font-weight: bold; text-decoration: none; 
                                  background: #11676a; padding: 12px 24px; border-radius: 8px; display: inline-block;
                                  box-shadow: 0 4px 12px rgba(17, 103, 106, 0.3);'>
                               🔗 Click to Reset Password
                               </a>
                           </div>
                           <br>
                           <small style='color: #666;'>In production, this link would be sent via email.</small>";
                $message_type = 'success';
                
            } else {
                $message = "Error generating reset link. Please try again.";
                $message_type = 'error';
            }
        } else {
            // Don't reveal whether email exists or not (security best practice)
            $message = "If an account with that email exists, a password reset link has been sent. Please check your email.";
            $message_type = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - UTB Events</title>
    <link rel="stylesheet" href="includes/styles.css">
    <style>
        .message {
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
            text-align: center;
        }
        .message.success {
            background: #e8f5e8;
            color: #2e7d32;
            border-color: #2e7d32;
        }
        .message.error {
            background: #ffebee;
            color: #c62828;
            border-color: #c62828;
        }
        .reset-button {
            color: white;
            font-weight: bold;
            text-decoration: none;
            background: #11676a;
            padding: 12px 24px;
            border-radius: 8px;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(17, 103, 106, 0.3);
            transition: all 0.3s ease;
            margin: 10px 0;
        }
        .reset-button:hover {
            background: #0f5a55;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(17, 103, 106, 0.4);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- UTB Logo Section -->
        <div class="utb-logo">
            <img src="sidebar/utb logo.png" alt="UTB Logo" class="logo-image">
            <div class="logo-text">
                <div class="logo-main">UTB EVENTS</div>
                <div class="logo-subtitle">
                    <div class="motto">BERIMAN • BERINOVASI</div>
                    <div class="established">Since 1986 • TEKNOLOGI BRUNEI</div>
                </div>
            </div>
        </div>

        <!-- Message Display -->
        <?php if(!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Forgot Password Form -->
        <div class="login-form">
            <h2 style="text-align: center; color: #11676a; margin-bottom: 20px;">Reset Your Password</h2>
            <p style="text-align: center; color: #666; margin-bottom: 20px;">
                Enter your email address to receive a password reset link.
            </p>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                           placeholder="Enter your registered email" required>
                </div>

                <button type="submit" class="login-btn" name="submit">Send Reset Link</button>
            </form>
            
            <div style="text-align: center; margin-top: 20px; color: #666;">
                Remember your password? <a href="index.php" style="color: #11676a; text-decoration: none; font-weight: bold;">Login here</a>
            </div>
        </div>
    </div>
</body>
</html>