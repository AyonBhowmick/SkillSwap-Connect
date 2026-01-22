<?php
session_start();
include "db.php";

$error = "";

// if already logged in
if (isset($_SESSION['uid']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin')  { header("Location: admin.php"); exit; }
    if ($_SESSION['role'] === 'mentor') { header("Location: mentor.php"); exit; }
    if ($_SESSION['role'] === 'learner'){ header("Location: learner.php"); exit; }
}

if (isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? "");
    $pass  = trim($_POST['password'] ?? "");

    // BASIC VALIDATION
    if ($email === "" || $pass === "") {
        $error = "Enter email and password!";
    } 
    // Email format validation
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format!";
    }
    // Password length validation
    elseif (strlen($pass) < 6) {
        $error = "Password must be at least 6 characters!";
    }
    else {
        $e = mysqli_real_escape_string($conn, $email);
        $p = mysqli_real_escape_string($conn, $pass);

        // plain password match
        $q = mysqli_query($conn, "SELECT * FROM users WHERE email='$e' AND password='$p' AND is_active=1 LIMIT 1");

        if (!$q) {
            $error = "Database error!";
        } else {
            $user = mysqli_fetch_assoc($q);

            if (!$user) {
                $error = "Invalid email or password!";
            } else {
                $_SESSION['uid']  = $user['id'];
                $_SESSION['role'] = $user['role'];

                if ($user['role'] === 'admin') {
                    header("Location: admin.php"); exit;
                      } elseif ($user['role'] === 'mentor') {
    header("Location: mentor.php"); exit;
} else {
    header("Location: learner.php"); exit;
}
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SkillSwap - Login</title>
    <style>
        *{
        margin:0;
        padding:0;
        box-sizing:border-box;
        font-family:Arial;}
        body
        {
            background:#f5f5f5;
        }
        .box
        {
            max-width:420px;
            margin:60px auto;
            background:#fff;
            border:1px solid #ddd;
            border-radius:10px;
            padding:25px;
        }
        h2
        {
            text-align:center;
            margin-bottom:10px;
        }
        label
        {
            display:block;
            margin:10px 0 6px;
            color:#444;
            font-size:14px;
        }
        input
        {
            width:100%;
            padding:10px;
            border:1px solid #ccc;
            border-radius:6px;
        }
        input.error {
            border-color:red;
        }
        input.valid {
            border-color:green;
        }
        .btn
        {width:100%;
        margin-top:15px;
        padding:11px;
        border:none;
        border-radius:6px;
        background:#4a6cf7;
        color:#fff;
        cursor:pointer;
    }
        .btn:hover
        {
            background:#3a5ce5;
        }
        .btn:disabled {
            background:#ccc;
            cursor:not-allowed;
        }
        .error
        {
            background:#ffe5e5;
            border:1px solid #ffb3b3;
            color:#b00000;
            padding:10px;
            border-radius:6px;
            margin-bottom:12px;
            text-align:center;
        }
        .note
        {
            text-align:center;
            margin-top:12px;
            color:#666;
        }
        .note a
        {
            color:#4a6cf7;
            text-decoration:none;
            font-weight:bold;
        }
        .hint {
            font-size:12px;
            color:#666;
            margin-top:5px;
        }
    </style>
</head>
<body>

<div class="box">
    <h2>Login</h2>

    <?php if($error!=""){ ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php } ?>

    <form method="post" id="loginForm" onsubmit="return validateForm()">
        <label>Email</label>
        <input type="email" name="email" id="email" oninput="validateEmail()" required>
        <div class="hint" id="emailHint">Enter valid email</div>

        <label>Password</label>
        <input type="password" name="password" id="password" oninput="validatePassword()" required>
        <div class="hint" id="passwordHint">Min 6 characters</div>

        <button class="btn" type="submit" name="login" id="loginBtn">Login</button>
        
    </form>
<p style="margin-top:12px;text-align:center;">
    <a href="forgot_password.php" 
       style="color:#4a6cf7;font-weight:bold;text-decoration:none;">
       Forgot Password?
    </a>
</p>


    <div class="note">
        New user? <a href="signup.php">Create account</a>
    </div>

    <div class="note" style="margin-top:8px;">
        Public browse? <a href="browser.php">Browse Skills</a>
    </div>
</div>

<script>
// Real-time validation
function validateEmail() {
    var email = document.getElementById('email').value;
    var emailField = document.getElementById('email');
    var hint = document.getElementById('emailHint');
    var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (email === '') {
        emailField.classList.remove('valid', 'error');
        hint.textContent = 'Enter valid email';
        hint.style.color = '#666';
        return false;
    }
    
    if (pattern.test(email)) {
        emailField.classList.remove('error');
        emailField.classList.add('valid');
        hint.textContent = '✓ Valid email';
        hint.style.color = 'green';
        return true;
    } else {
        emailField.classList.remove('valid');
        emailField.classList.add('error');
        hint.textContent = '✗ Invalid email format';
        hint.style.color = 'red';
        return false;
    }
}

function validatePassword() {
    var password = document.getElementById('password').value;
    var passwordField = document.getElementById('password');
    var hint = document.getElementById('passwordHint');
    
    if (password === '') {
        passwordField.classList.remove('valid', 'error');
        hint.textContent = 'Min 6 characters';
        hint.style.color = '#666';
        return false;
    }
    
    if (password.length >= 6) {
        passwordField.classList.remove('error');
        passwordField.classList.add('valid');
        hint.textContent = '✓ Password OK';
        hint.style.color = 'green';
        return true;
    } else {
        passwordField.classList.remove('valid');
        passwordField.classList.add('error');
        hint.textContent = '✗ Min 6 characters required';
        hint.style.color = 'red';
        return false;
    }
}

// Form validation before submit
function validateForm() {
    var emailValid = validateEmail();
    var passwordValid = validatePassword();
    
    if (!emailValid || !passwordValid) {
        alert('Please fix errors before submitting!');
        return false;
    }
    return true;
}

// Disable button initially
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('loginBtn').disabled = false;
});
</script>

</body>
</html>