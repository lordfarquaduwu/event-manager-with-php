<?php
session_start();
include 'connection.php';

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $dob = $_POST['dob'];
    
    // Simple validation
    if(empty($fullname) || empty($email) || empty($password)) {
        $error = "All fields are required";
    }
    elseif($password !== $confirm_password) {
        $error = "Passwords do not match";
    }
    elseif(strlen($password) < 6) {
        $error = "Password must be at least 6 characters";
    }
    else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0) {
            $error = "Email already registered. Please login instead.";
        } else {
            // Hash the password before storing
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user with hashed password
            $insert_sql = "INSERT INTO users (fullname, email, password, dob, role) VALUES (?, ?, ?, ?, 'student')";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssss", $fullname, $email, $hashed_password, $dob);
            
            if($insert_stmt->execute()) {
                $success = "Registration successful! You can now login.";
                // Clear form
                $fullname = $email = $dob = '';
            } else {
                $error = "Registration failed: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - UTB Events</title>
    <link rel="stylesheet" href="includes/styles.css">
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

        <!-- Error/Success Messages -->
        <?php if(!empty($error)): ?>
            <div style="background: #ffebee; color: #c62828; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #c62828;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div style="background: #e8f5e8; color: #2e7d32; padding: 12px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #2e7d32;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Sign Up Form -->
        <div class="login-form">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input type="text" id="fullname" name="fullname" 
                           value="<?php echo isset($fullname) ? htmlspecialchars($fullname) : ''; ?>" 
                           placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" 
                           placeholder="Enter your email address" required>
                </div>

                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob" 
                           value="<?php echo isset($dob) ? htmlspecialchars($dob) : ''; ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Enter your password (min. 6 characters)" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm your password" required>
                </div>

                <button type="submit" class="login-btn" name="submit">Create Account</button>
            </form>
            
            <div style="text-align: center; margin-top: 20px; color: #666;">
                Already have an account? <a href="index.php" style="color: #11676a; text-decoration: none; font-weight: bold;">Login here</a>
            </div>
        </div>
    </div>
</body>
</html>