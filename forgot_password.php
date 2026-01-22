<?php
session_start();
include "db.php";

$successMsg = "";
$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $newPass = trim($_POST['new_password'] ?? '');
    $confirmPass = trim($_POST['confirm_password'] ?? '');

    if ($email === '' || $newPass === '' || $confirmPass === '') {
        $errorMsg = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Invalid email format.";
    } elseif (strlen($newPass) < 6) {
        $errorMsg = "Password must be at least 6 characters.";
    } elseif ($newPass !== $confirmPass) {
        $errorMsg = "Passwords do not match.";
    } else {
        $emailSafe = mysqli_real_escape_string($conn, $email);

        $check = mysqli_query($conn, "
            SELECT id FROM users 
            WHERE email='$emailSafe' AND is_active=1 
            LIMIT 1
        ");

        if ($check && mysqli_num_rows($check) === 1) {
            $user = mysqli_fetch_assoc($check);
            $uid = (int)$user['id'];

            // BASIC password update (same style as your project)
            $passSafe = mysqli_real_escape_string($conn, $newPass);

            $up = mysqli_query($conn, "
                UPDATE users 
                SET password='$passSafe' 
                WHERE id=$uid
            ");

            if ($up) {
                $successMsg = "Password updated successfully. You can now login.";
            } else {
                $errorMsg = "Password update failed. Try again.";
            }
        } else {
            $errorMsg = "Email not found or account inactive.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Forgot Password - SkillSwap</title>
<style>
    body{background:#f5f5f5;font-family:Arial;}
    .box{
        width:360px;
        margin:80px auto;
        background:white;
        padding:20px;
        border-radius:8px;
        border:1px solid #ddd;
        box-shadow:0 2px 5px rgba(0,0,0,0.1);
    }
    h2{text-align:center;color:#4a6cf7;margin-bottom:15px;}
    input{
        width:100%;
        padding:10px;
        margin:8px 0;
        border:1px solid #ccc;
        border-radius:4px;
    }
    input:focus{outline:none;border-color:#4a6cf7;}
    button{
        width:100%;
        background:#4a6cf7;
        color:white;
        border:none;
        padding:10px;
        border-radius:4px;
        font-weight:bold;
        cursor:pointer;
    }
    button:hover{background:#3a5ce5;}
    .msg{
        padding:10px;
        border-radius:6px;
        margin-bottom:10px;
        font-size:14px;
    }
    .success{background:#e6f4ea;color:#1b5e20;}
    .error{background:#f8d7da;color:#721c24;}
    .back{
        text-align:center;
        margin-top:10px;
    }
</style>
</head>
<body>

<div class="box">
    <h2>Forgot Password</h2>

    <?php if($successMsg): ?>
        <div class="msg success"><?php echo htmlspecialchars($successMsg); ?></div>
    <?php endif; ?>

    <?php if($errorMsg): ?>
        <div class="msg error"><?php echo htmlspecialchars($errorMsg); ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Registered Email" required>
        <input type="password" name="new_password" placeholder="New Password" required>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required>
        <button type="submit">Reset Password</button>
    </form>

    <div class="back">
        <a href="login.php">← Back to Login</a>
    </div>
</div>

</body>
</html>
