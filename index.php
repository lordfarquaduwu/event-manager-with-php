<?php
include 'connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTB Events - Student Portal</title>
    <link rel="stylesheet" type="text/css" href="includes/styles.css">
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

        <!-- Login Form -->
        <div class="login-form">
            <form name="form" action="login.php" onsubmit="return isvalid()" method="POST">
                <div class="form-group">
                    <label for="user">Email</label>
                    <input type="email" id="user" name="user" placeholder="Enter your email address">
                </div>
                
                <div class="form-group">
                    <label for="pass">Password</label>
                    <input type="password" id="pass" name="pass" placeholder="Enter your password">
                </div>

                <button type="submit" class="login-btn" name="submit">Login to UTB Events</button>
            </form>
            
            <!-- SIGN UP LINK ADDED HERE -->
            <div style="text-align: center; margin-top: 20px; color: #666;">
                Don't have an account? <a href="signup.php" style="color: #11676a; text-decoration: none; font-weight: bold;">Sign up here</a>
            </div>
            <div style="text-align: center; margin-top: 10px; color: #666;">
    <a href="forgot_password.php" style="color: #11676a; text-decoration: none; font-weight: bold;">Forgot Password?</a>
</div>
            
        </div>
    </div>

    <script src="includes/site.js"></script>
</body>
</html>