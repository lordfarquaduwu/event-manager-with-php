<?php
session_start();
include ("connection.php");

if(isset($_POST['submit'])){
    $email = $_POST['user'];
    $password = $_POST['pass'];

    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Check if password is hashed or plain text
            $login_success = false;
            
            if(password_verify($password, $user['password'])) {
                // Password is hashed and correct
                $login_success = true;
            } elseif ($user['password'] === $password) {
                // Password is plain text and correct - upgrade to hashed
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET password = ? WHERE email = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ss", $hashed_password, $email);
                $update_stmt->execute();
                $login_success = true;
            }
            
            if($login_success) {
                $_SESSION['user'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                if($user['role'] == 'admin') {
                    header("Location: admin/admin_dashboard.php");
                } else {
                    header("Location: welcome.php");
                }
                exit();
            } else {
                echo '<script>
                window.location.href = "index.php";
                alert("Login failed. Invalid email or password.");
                </script>';
            }
        }
        else {
            echo '<script>
            window.location.href = "index.php";
            alert("Login failed. Invalid email or password.");
            </script>';
        }
        $stmt->close();
    } else {
        echo "Database error";
    }
}
?>