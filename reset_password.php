<?php
session_start();
include 'connection.php';

$message = '';
$message_type = ''; // 'success' or 'error'
$valid_token = false;
$user_id = null;
$user_email = null;

// Check if token is provided and valid
if(isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token exists and is not used
    $token_sql = "SELECT pr.user_id, u.email, u.fullname 
                  FROM password_resets pr 
                  JOIN users u ON pr.user_id = u.id 
                  WHERE pr.token = ? AND pr.used = 0";
    $token_stmt = $conn->prepare($token_sql);
    $token_stmt->bind_param("s", $token);
    $token_stmt->execute();
    $token_result = $token_stmt->get_result();
    
    if($token_result->num_rows > 0) {
        $valid_token = true;
        $token_data = $token_result->fetch_assoc();
        $user_id = $token_data['user_id'];
        $user_email = $token_data['email'];
        $user_fullname = $token_data['fullname'];
    } else {
        $message = "Invalid or expired reset link. Please request a new password reset.";
        $message_type = 'error';
    }
}

// Handle password reset form submission
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if(empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all password fields";
        $message_type = 'error';
    } elseif($new_password !== $confirm_password) {
        $message = "Passwords do not match";
        $message_type = 'error';
    } elseif(strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters";
        $message_type = 'error';
    } else {
        // Verify token again
        $verify_sql = "SELECT user_id FROM password_resets WHERE token = ? AND used = 0";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("s", $token);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        
        if($verify_result->num_rows > 0) {
            $token_data = $verify_result->fetch_assoc();
            $user_id = $token_data['user_id'];
            
            // Hash the new password before storing
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update user's password with hashed version
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            
            if($update_stmt->execute()) {
                // Mark token as used
                $mark_used_sql = "UPDATE password_resets SET used = 1 WHERE token = ?";
                $mark_used_stmt = $conn->prepare($mark_used_sql);
                $mark_used_stmt->bind_param("s", $token);
                $mark_used_stmt->execute();
                
                $message = "Password reset successfully! You can now login with your new password.";
                $message_type = 'success';
                $valid_token = false; // Hide form after success
            } else {
                $message = "Error updating password. Please try again.";
                $message_type = 'error';
            }
        } else {
            $message = "Invalid or expired reset token.";
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - UTB Events</title>
    <link rel="stylesheet" href="includes/styles.css">
    <style>
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid;
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

        <!-- Reset Password Form -->
        <div class="login-form">
            <h2 style="text-align: center; color: #11676a; margin-bottom: 20px;">
                <?php echo $valid_token ? 'Set New Password' : 'Password Reset'; ?>
            </h2>
            
            <?php if($valid_token): ?>
                <p style="text-align: center; color: #666; margin-bottom: 20px;">
                    Enter your new password for <strong><?php echo htmlspecialchars($user_email); ?></strong>
                </p>
                
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" 
                               placeholder="Enter new password (min. 6 characters)" required>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" 
                               placeholder="Confirm your new password" required>
                    </div>

                    <button type="submit" class="login-btn" name="reset_password">Reset Password</button>
                </form>
            <?php else: ?>
                <p style="text-align: center; color: #666; margin-bottom: 20px;">
                    <?php echo empty($message) ? 'Invalid reset link.' : ''; ?>
                </p>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 20px; color: #666;">
                <a href="index.php" style="color: #11676a; text-decoration: none; font-weight: bold;">Back to Login</a>
                <br><br>
                <a href="forgot_password.php" style="color: #11676a; text-decoration: none; font-weight: bold;">Request New Reset Link</a>
            </div>
        </div>
    </div>
</body>
</html>