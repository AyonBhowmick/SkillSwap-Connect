<?php
session_start();
include "db.php";

$error = "";

/* ========= LEARNER SIGNUP ========= */
if (isset($_POST['signup_learner'])) {
    $first   = trim($_POST['learnerFirstName'] ?? "");
    $last    = trim($_POST['learnerLastName'] ?? "");
    $gender  = trim($_POST['learnerGender'] ?? "");
    $email   = trim($_POST['learnerEmail'] ?? "");
    $phone   = trim($_POST['learnerPhone'] ?? "");
    $pass    = $_POST['learnerPassword'] ?? "";
    $cpass   = $_POST['learnerConfirmPassword'] ?? "";
    $want    = trim($_POST['learnerWantSkill'] ?? "");
    $teach   = trim($_POST['learnerTeachSkill'] ?? "");
    $time    = trim($_POST['learnerTime'] ?? "flexible");
    $terms   = isset($_POST['learnerTerms']);

    // BASIC VALIDATION - ALL FIELDS KEPT
    if ($first === "" || $last === "" || $gender === "" || $email === "" || $pass === "" || $cpass === "" || $want === "") {
        $error = "Please fill all required fields!";
    } 
    // First name validation
    elseif (strlen($first) < 2 || strlen($first) > 50) {
        $error = "First name must be 2-50 characters!";
    }
    // Last name validation
    elseif (strlen($last) < 2 || strlen($last) > 50) {
        $error = "Last name must be 2-50 characters!";
    }
    // Email validation
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address!";
    }
    // Phone validation (optional)
    elseif ($phone != "" && !preg_match('/^[0-9]{10,15}$/', $phone)) {
        $error = "Phone must be 10-15 digits!";
    }
    // Password validation
    elseif (strlen($pass) < 8) {
        $error = "Password must be at least 8 characters!";
    }
    elseif ($pass !== $cpass) {
        $error = "Passwords do not match!";
    }
    // Skill validation
    elseif (strlen($want) < 2 || strlen($want) > 100) {
        $error = "Skill must be 2-100 characters!";
    }
    // Optional teach skill validation
    elseif ($teach != "" && (strlen($teach) < 2 || strlen($teach) > 100)) {
        $error = "Teach skill must be 2-100 characters!";
    }
    elseif (!$terms) {
        $error = "You must agree to Terms & Conditions!";
    } 
    else {
        // Escape all inputs
        $firstS  = mysqli_real_escape_string($conn, $first);
        $lastS   = mysqli_real_escape_string($conn, $last);
        $genderS = mysqli_real_escape_string($conn, $gender);
        $emailS  = mysqli_real_escape_string($conn, $email);
        $phoneS  = mysqli_real_escape_string($conn, $phone);
        $wantS   = mysqli_real_escape_string($conn, $want);
        $teachS  = mysqli_real_escape_string($conn, $teach);
        $timeS   = mysqli_real_escape_string($conn, $time);
        $passS   = mysqli_real_escape_string($conn, $pass);

        // Check email exists
        $chk = mysqli_query($conn, "SELECT id FROM users WHERE email='$emailS' LIMIT 1");
        if ($chk && mysqli_num_rows($chk) > 0) {
            $error = "This email is already registered!";
        } else {
            // Insert user
            $insU = mysqli_query($conn, "
                INSERT INTO users (role, first_name, last_name, email, phone, gender, password)
                VALUES ('learner','$firstS','$lastS','$emailS','$phoneS','$genderS','$passS')
            ");

            if (!$insU) {
                $error = "Error: " . mysqli_error($conn);
            } else {
                $uid = mysqli_insert_id($conn);

                // Insert learner profile
                $insP = mysqli_query($conn, "
                    INSERT INTO learner_profiles (user_id, want_skill, teach_skill, preferred_time)
                    VALUES ($uid,'$wantS','$teachS','$timeS')
                ");

                if (!$insP) {
                    $error = "Error: " . mysqli_error($conn);
                } else {
                    $_SESSION['uid']  = $uid;
                    $_SESSION['role'] = 'learner';
                    header("Location: learner.php");
                    exit;
                }
            }
        }
    }
}

/* ========= MENTOR SIGNUP ========= */

if (isset($_POST['signup_mentor'])) {
    $first   = trim($_POST['mentorFirstName'] ?? "");
    $last    = trim($_POST['mentorLastName'] ?? "");
    $gender  = trim($_POST['mentorGender'] ?? "");
    $email   = trim($_POST['mentorEmail'] ?? "");
    $phone   = trim($_POST['mentorPhone'] ?? "");
    $pass    = $_POST['mentorPassword'] ?? "";
    $cpass   = $_POST['mentorConfirmPassword'] ?? ""; // FIXED LINE
    $title   = trim($_POST['mentorTitle'] ?? "");
    $exp     = trim($_POST['mentorExperience'] ?? "");
    $bio     = trim($_POST['mentorBio'] ?? "");
    $skill   = trim($_POST['mentorSkill'] ?? "");
    $method  = trim($_POST['mentorMethod'] ?? "free");
    $rate    = trim($_POST['mentorRate'] ?? "");
    $terms   = isset($_POST['mentorTerms']);

    // BASIC VALIDATION - ALL FIELDS KEPT
    if ($first === "" || $last === "" || $gender === "" || $email === "" || $phone === "" || $pass === "" || $cpass === "" || $skill === "") {
        $error = "Please fill all required fields!";
    }
    // First name validation
    elseif (strlen($first) < 2 || strlen($first) > 50) {
        $error = "First name must be 2-50 characters!";
    }
    // Last name validation
    elseif (strlen($last) < 2 || strlen($last) > 50) {
        $error = "Last name must be 2-50 characters!";
    }
    // Email validation
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address!";
    }
    // Phone validation
    elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $error = "Phone must be 10-15 digits!";
    }
    // Password validation
    elseif (strlen($pass) < 8) {
        $error = "Password must be at least 8 characters!";
    }
    elseif ($pass !== $cpass) {
        $error = "Passwords do not match!";
    }
    // Title validation (optional)
    elseif ($title != "" && (strlen($title) < 2 || strlen($title) > 100)) {
        $error = "Title must be 2-100 characters!";
    }
    // Bio validation (optional)
    elseif (strlen($bio) > 500) {
        $error = "Bio must be less than 500 characters!";
    }
    // Skill validation
    elseif (strlen($skill) < 2 || strlen($skill) > 100) {
        $error = "Skill must be 2-100 characters!";
    }
    // Rate validation
    elseif (($method === "paid" || $method === "both") && ($rate === "" || $rate <= 0)) {
        $error = "Hourly rate is required for Paid/Both method!";
    }
    elseif (!$terms) {
        $error = "You must agree to Terms & Conditions!";
    } 
    else {
        // Escape all inputs
        $firstS  = mysqli_real_escape_string($conn, $first);
        $lastS   = mysqli_real_escape_string($conn, $last);
        $genderS = mysqli_real_escape_string($conn, $gender);
        $emailS  = mysqli_real_escape_string($conn, $email);
        $phoneS  = mysqli_real_escape_string($conn, $phone);
        $titleS  = mysqli_real_escape_string($conn, $title);
        $expS    = mysqli_real_escape_string($conn, $exp);
        $bioS    = mysqli_real_escape_string($conn, $bio);
        $skillS  = mysqli_real_escape_string($conn, $skill);
        $methodS = mysqli_real_escape_string($conn, $method);
        $rateS   = mysqli_real_escape_string($conn, $rate);
        $passS   = mysqli_real_escape_string($conn, $pass);

        // Check email exists
        $chk = mysqli_query($conn, "SELECT id FROM users WHERE email='$emailS' LIMIT 1");
        if ($chk && mysqli_num_rows($chk) > 0) {
            $error = "This email is already registered!";
        } else {
            // Insert user
            $insU = mysqli_query($conn, "
                INSERT INTO users (role, first_name, last_name, email, phone, gender, password)
                VALUES ('mentor','$firstS','$lastS','$emailS','$phoneS','$genderS','$passS')
            ");

            if (!$insU) {
                $error = "Error: " . mysqli_error($conn);
            } else {
                $uid = mysqli_insert_id($conn);

                // Insert mentor profile
                $insP = mysqli_query($conn, "
                    INSERT INTO mentor_profiles (user_id, title, experience, bio, skill, method, rate)
                    VALUES ($uid,'$titleS','$expS','$bioS','$skillS','$methodS','$rateS')
                ");

                if (!$insP) {
                    $error = "Error: " . mysqli_error($conn);
                } else {
                    $_SESSION['uid']  = $uid;
                    $_SESSION['role'] = 'mentor';
                    header("Location: mentor.php");
                    exit;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SkillSwap - Sign Up</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial; background:#f5f5f5; }
        
        .header {
            background:white;
            padding:15px 25px;
            border-bottom:1px solid #ddd;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }
        .logo { font-size:22px; font-weight:bold; color:#4a6cf7; }
        .back-link { color:#4a6cf7; text-decoration:none; }
        
        .container {
            width:700px;
            margin:30px auto;
            background:white;
            padding:25px;
            border-radius:5px;
            border:1px solid #ddd;
        }
        
        .title { text-align:center; margin-bottom:20px; }
        
        .error {
            background:#ffe5e5;
            color:red;
            padding:10px;
            margin-bottom:15px;
            text-align:center;
            border-radius:3px;
        }
        
        .role-buttons {
            display:flex;
            margin-bottom:20px;
        }
        .role-btn {
            flex:1;
            padding:10px;
            border:1px solid #ccc;
            background:#eee;
            cursor:pointer;
        }
        .role-btn.active {
            background:#4a6cf7;
            color:white;
        }
        
        .form-section {
            display:none;
        }
        .form-section.active {
            display:block;
        }
        
        .row {
            display:flex;
            gap:15px;
            margin-bottom:15px;
        }
        .group {
            flex:1;
        }
        
        label {
            display:block;
            margin-bottom:5px;
            color:#333;
        }
        input, select, textarea {
            width:100%;
            padding:8px;
            border:1px solid #ccc;
            border-radius:3px;
        }
        input.error-field {
            border-color:red;
        }
        input.valid-field {
            border-color:green;
        }
        
        .radio-group {
            margin:10px 0;
        }
        .radio-group label {
            display:inline-block;
            margin-right:15px;
        }
        
        .checkbox {
            margin:15px 0;
        }
        .checkbox label {
            display:inline-block;
            margin-left:5px;
        }
        
        .btn-row {
            display:flex;
            gap:10px;
            margin-top:20px;
        }
        .submit-btn {
            flex:1;
            padding:10px;
            background:#4a6cf7;
            color:white;
            border:none;
            border-radius:3px;
            cursor:pointer;
        }
        .reset-btn {
            flex:1;
            padding:10px;
            background:#6c757d;
            color:white;
            border:none;
            border-radius:3px;
            cursor:pointer;
        }
        
        .note {
            text-align:center;
            margin-top:15px;
            color:#666;
        }
        .note a {
            color:#4a6cf7;
        }
        
        .hint {
            font-size:12px;
            color:#666;
            margin-top:5px;
        }
    </style>
</head>
<body>

<div class="header">
    <div class="logo">SkillSwap</div>
    <a href="index.php" class="back-link">← Home</a>
</div>

<div class="container">
    <div class="title">
        <h2>Create Account</h2>
        <p>Select your role</p>
    </div>
    
    <?php if($error != ""): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="role-buttons">
        <button class="role-btn active" onclick="showLearner()">Learner</button>
        <button class="role-btn" onclick="showMentor()">Mentor</button>
    </div>
    
    <!-- LEARNER FORM -->
    <div class="form-section active" id="learnerForm">
        <form method="post" onsubmit="return validateLearner()">
            <div class="row">
                <div class="group">
                    <label>First Name *</label>
                    <input type="text" name="learnerFirstName" id="learnerFirstName" oninput="validateFirstName()">
                    <div class="hint"></div>
                </div>
                <div class="group">
                    <label>Last Name *</label>
                    <input type="text" name="learnerLastName" id="learnerLastName" oninput="validateLastName()">
                    <div class="hint"></div>
                </div>
            </div>
            
            <label>Gender *</label>
            <div class="radio-group">
                <label><input type="radio" name="learnerGender" value="male" required> Male</label>
                <label><input type="radio" name="learnerGender" value="female"> Female</label>
                <label><input type="radio" name="learnerGender" value="other"> Other</label>
            </div>
            
            <div class="row">
                <div class="group">
                    <label>Email *</label>
                    <input type="email" name="learnerEmail" id="learnerEmail" oninput="validateEmail()">
                </div>
                <div class="group">
                    <label>Phone</label>
                    <input type="tel" name="learnerPhone" id="learnerPhone" oninput="validatePhone()">
                    <div class="hint"></div>
                </div>
            </div>
            
            <div class="row">
                <div class="group">
                    <label>Password *</label>
                    <input type="password" name="learnerPassword" id="learnerPassword" oninput="validatePassword()">
                    <div class="hint">Min 8 characters</div>
                </div>
                <div class="group">
                    <label>Confirm Password *</label>
                    <input type="password" name="learnerConfirmPassword" id="learnerConfirmPassword" oninput="validateConfirmPassword()">
                </div>
            </div>
            
            <label>Skills I Want to Learn *</label>
            <input type="text" name="learnerWantSkill" id="learnerWantSkill" oninput="validateWantSkill()">
            <!-- <div class="hint">2-100 characters</div> -->
            
            <label>Skills I Can Teach</label>
            <input type="text" name="learnerTeachSkill" id="learnerTeachSkill" oninput="validateTeachSkill()">
            <!-- <div class="hint">Optional: 2-100 characters</div> -->
            
            <label>Preferred Learning Time:</label>
            <div class="radio-group">
                <label><input type="radio" name="learnerTime" value="flexible" checked> Flexible</label>
                <label><input type="radio" name="learnerTime" value="weekends"> Weekends</label>
                <label><input type="radio" name="learnerTime" value="evenings"> Evenings</label>
            </div>
            
            <div class="checkbox">
                <input type="checkbox" name="learnerTerms" id="learnerTerms" required>
                <label>I agree to Terms & Conditions</label>
            </div>
            
            <div class="btn-row">
                <button type="submit" name="signup_learner" class="submit-btn">Create Learner Account</button>
                <button type="reset" class="reset-btn">Reset</button>
            </div>
        </form>
    </div>
    
    <!-- MENTOR FORM -->
    <div class="form-section" id="mentorForm">
        <form method="post" onsubmit="return validateMentor()">
            <div class="row">
                <div class="group">
                    <label>First Name *</label>
                    <input type="text" name="mentorFirstName" id="mentorFirstName" oninput="validateMentorFirstName()">
                    <!-- <div class="hint"></div> -->
                </div>
                <div class="group">
                    <label>Last Name *</label>
                    <input type="text" name="mentorLastName" id="mentorLastName" oninput="validateMentorLastName()">
                    <!-- <div class="hint"></div> -->
                </div>
            </div>
            
            <label>Gender *</label>
            <div class="radio-group">
                <label><input type="radio" name="mentorGender" value="male" required> Male</label>
                <label><input type="radio" name="mentorGender" value="female"> Female</label>
                <label><input type="radio" name="mentorGender" value="other"> Other</label>
            </div>
            
            <div class="row">
                <div class="group">
                    <label>Email *</label>
                    <input type="email" name="mentorEmail" id="mentorEmail" oninput="validateMentorEmail()">
                </div>
                <div class="group">
                    <label>Phone *</label>
                    <input type="tel" name="mentorPhone" id="mentorPhone" oninput="validateMentorPhone()">
                    <!-- <div class="hint">10-15 digits</div> -->
                </div>
            </div>
            
            <div class="row">
                <div class="group">
                    <label>Password *</label>
                    <input type="password" name="mentorPassword" id="mentorPassword" oninput="validateMentorPassword()">
                    <div class="hint">Min 8 characters</div>
                </div>
                <div class="group">
                    <label>Confirm Password *</label>
                    <input type="password" name="mentorConfirmPassword" id="mentorConfirmPassword" oninput="validateMentorConfirmPassword()">
                </div>
            </div>
            
            <label>Professional Title</label>
            <input type="text" name="mentorTitle" id="mentorTitle" oninput="validateMentorTitle()">
            <!-- <div class="hint">Optional: 2-100 characters</div> -->
            
            <label>Experience (Years)</label>
            <select name="mentorExperience">
                <option value="">Select</option>
                <option value="1-2">1-2 years</option>
                <option value="3-5">3-5 years</option>
                <option value="5+">5+ years</option>
            </select>
            
            <label>Short Bio</label>
            <textarea name="mentorBio" rows="3" id="mentorBio" oninput="validateMentorBio()"></textarea>
            <div class="hint">Max 500 characters</div>
            
            <label>Skills I Can Teach *</label>
            <input type="text" name="mentorSkill" id="mentorSkill" oninput="validateMentorSkill()">
            <div class="hint">2-100 characters</div>
            
            <label>Teaching Method:</label>
            <div class="radio-group">
                <label><input type="radio" name="mentorMethod" value="free" checked onclick="toggleRate()"> Free</label>
                <label><input type="radio" name="mentorMethod" value="paid" onclick="toggleRate()"> Paid</label>
                <label><input type="radio" name="mentorMethod" value="both" onclick="toggleRate()"> Both</label>
            </div>
            
            <div id="rateGroup" style="display:none;">
                <label>Hourly Rate ($) *</label>
                <input type="number" name="mentorRate" id="mentorRate" min="0" step="0.01" oninput="validateMentorRate()">
                <div class="hint">Required for Paid/Both methods</div>
            </div>
            
            <div class="checkbox">
                <input type="checkbox" name="mentorTerms" id="mentorTerms" required>
                <label>I agree to Terms & Conditions</label>
            </div>
            
            <div class="btn-row">
                <button type="submit" name="signup_mentor" class="submit-btn">Apply as Mentor</button>
                <button type="reset" class="reset-btn" onclick="hideRate()">Reset</button>
            </div>
        </form>
    </div>
    
    <div class="note">
        Already have account? <a href="login.php">Login here</a>
    </div>
</div>

<script>
// Show forms
function showLearner() {
    document.getElementById('learnerForm').classList.add('active');
    document.getElementById('mentorForm').classList.remove('active');
    document.querySelectorAll('.role-btn')[0].classList.add('active');
    document.querySelectorAll('.role-btn')[1].classList.remove('active');
}

function showMentor() {
    document.getElementById('mentorForm').classList.add('active');
    document.getElementById('learnerForm').classList.remove('active');
    document.querySelectorAll('.role-btn')[1].classList.add('active');
    document.querySelectorAll('.role-btn')[0].classList.remove('active');
}

// Toggle rate field
function toggleRate() {
    var method = document.querySelector('input[name="mentorMethod"]:checked').value;
    var rateGroup = document.getElementById('rateGroup');
    
    if (method === 'paid' || method === 'both') {
        rateGroup.style.display = 'block';
    } else {
        rateGroup.style.display = 'none';
    }
}

function hideRate() {
    document.getElementById('rateGroup').style.display = 'none';
}

// VALIDATION FUNCTIONS - LEARNER
function validateFirstName() {
    var field = document.getElementById('learnerFirstName');
    var value = field.value.trim();
    
    if (value.length >= 2 && value.length <= 50) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateLastName() {
    var field = document.getElementById('learnerLastName');
    var value = field.value.trim();
    
    if (value.length >= 2 && value.length <= 50) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateEmail() {
    var field = document.getElementById('learnerEmail');
    var value = field.value.trim();
    var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (pattern.test(value)) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validatePhone() {
    var field = document.getElementById('learnerPhone');
    var value = field.value.trim();
    
    if (value === '' || /^[0-9]{10,15}$/.test(value)) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validatePassword() {
    var field = document.getElementById('learnerPassword');
    var value = field.value;
    
    if (value.length >= 8) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateConfirmPassword() {
    var field = document.getElementById('learnerConfirmPassword');
    var pass = document.getElementById('learnerPassword').value;
    var value = field.value;
    
    if (value === pass && pass.length >= 8) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateWantSkill() {
    var field = document.getElementById('learnerWantSkill');
    var value = field.value.trim();
    
    if (value.length >= 2 && value.length <= 100) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateTeachSkill() {
    var field = document.getElementById('learnerTeachSkill');
    var value = field.value.trim();
    
    if (value === '' || (value.length >= 2 && value.length <= 100)) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

// VALIDATION FUNCTIONS - MENTOR
function validateMentorFirstName() {
    var field = document.getElementById('mentorFirstName');
    var value = field.value.trim();
    
    if (value.length >= 2 && value.length <= 50) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateMentorLastName() {
    var field = document.getElementById('mentorLastName');
    var value = field.value.trim();
    
    if (value.length >= 2 && value.length <= 50) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateMentorEmail() {
    var field = document.getElementById('mentorEmail');
    var value = field.value.trim();
    var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (pattern.test(value)) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateMentorPhone() {
    var field = document.getElementById('mentorPhone');
    var value = field.value.trim();
    
    if (/^[0-9]{10,15}$/.test(value)) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateMentorPassword() {
    var field = document.getElementById('mentorPassword');
    var value = field.value;
    
    if (value.length >= 8) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateMentorConfirmPassword() {
    var field = document.getElementById('mentorConfirmPassword');
    var pass = document.getElementById('mentorPassword').value;
    var value = field.value;
    
    if (value === pass && pass.length >= 8) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateMentorTitle() {
    var field = document.getElementById('mentorTitle');
    var value = field.value.trim();
    
    if (value === '' || (value.length >= 2 && value.length <= 100)) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateMentorBio() {
    var field = document.getElementById('mentorBio');
    var value = field.value;
    
    if (value.length <= 500) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateMentorSkill() {
    var field = document.getElementById('mentorSkill');
    var value = field.value.trim();
    
    if (value.length >= 2 && value.length <= 100) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

function validateMentorRate() {
    var field = document.getElementById('mentorRate');
    var value = parseFloat(field.value);
    
    if (value > 0) {
        field.classList.remove('error-field');
        field.classList.add('valid-field');
        return true;
    } else {
        field.classList.remove('valid-field');
        field.classList.add('error-field');
        return false;
    }
}

// FORM SUBMISSION VALIDATION
function validateLearner() {
    var isValid = 
        validateFirstName() &&
        validateLastName() &&
        validateEmail() &&
        validatePhone() &&
        validatePassword() &&
        validateConfirmPassword() &&
        validateWantSkill() &&
        validateTeachSkill();
    
    if (!isValid) {
        alert('Please fix all errors before submitting!');
        return false;
    }
    return true;
}

function validateMentor() {
    var method = document.querySelector('input[name="mentorMethod"]:checked').value;
    var isValid = 
        validateMentorFirstName() &&
        validateMentorLastName() &&
        validateMentorEmail() &&
        validateMentorPhone() &&
        validateMentorPassword() &&
        validateMentorConfirmPassword() &&
        validateMentorSkill();
    
    // Check rate if paid/both
    if ((method === 'paid' || method === 'both') && !validateMentorRate()) {
        alert('Please enter a valid hourly rate!');
        return false;
    }
    
    if (!isValid) {
        alert('Please fix all errors before submitting!');
        return false;
    }
    return true;
}
</script>

</body>
</html>