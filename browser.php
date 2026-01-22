<?php
session_start(); 
include "db.php";

// -----------------------------
// Check if user is logged in
// -----------------------------
$isLoggedIn = isset($_SESSION['uid']);
$userRole = $_SESSION['role'] ?? '';
$userId = (int)($_SESSION['uid'] ?? 0);

// Messages
$successMsg = "";
$errorMsg = "";

// -----------------------------
// HANDLE ALL POST REQUESTS
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ADD REQUEST LOGIC
    if (isset($_POST['request_to_learn'])) {
        if (!$isLoggedIn) {
            $errorMsg = "Please login first to request a skill.";
        } elseif ($userRole !== 'learner') {
            $errorMsg = "Only Learners can request skills.";
        } else {
            $skillId = (int)($_POST['skill_id'] ?? 0);
            $desc = trim($_POST['description'] ?? "");
            if (strlen($desc) > 300) $desc = substr($desc, 0, 300);
            $descSafe = mysqli_real_escape_string($conn, $desc);

            if ($skillId > 0) {
                $skillQ = "SELECT ms.id, ms.skill_name FROM mentor_skills ms JOIN users u ON u.id = ms.mentor_id WHERE ms.id = $skillId AND u.role = 'mentor' AND u.is_active = 1 LIMIT 1";
                $skillRes = mysqli_query($conn, $skillQ);

                if ($skillRes && mysqli_num_rows($skillRes) == 1) {
                    $skillRow = mysqli_fetch_assoc($skillRes);
                    $skillNameSafe = mysqli_real_escape_string($conn, $skillRow['skill_name']);

                    $dupQ = "SELECT id FROM skill_requests WHERE learner_id = $userId AND mentor_skill_id = $skillId AND status = 'Pending' LIMIT 1";
                    if (mysqli_num_rows(mysqli_query($conn, $dupQ)) > 0) {
                        $errorMsg = "You already have a Pending request for this skill.";
                    } else {
                        $insQ = "INSERT INTO skill_requests (learner_id, mentor_skill_id, skill_name, description, status) VALUES ($userId, $skillId, '$skillNameSafe', '$descSafe', 'Pending')";
                        if (mysqli_query($conn, $insQ)) {
                             header("Location: browser.php?msg=success"); // Redirect to prevent resubmit
                             exit();
                        }
                        else $errorMsg = "Request failed.";
                    }
                }
            }
        }
    }

    // 2. CANCEL REQUEST LOGIC
    if (isset($_POST['cancel_request'])) {
        $skillId = (int)($_POST['skill_id'] ?? 0);
        $delQ = "DELETE FROM skill_requests WHERE learner_id = $userId AND mentor_skill_id = $skillId AND status = 'Pending'";
        if (mysqli_query($conn, $delQ)) {
            header("Location: browser.php?msg=cancelled");
            exit();
        } else {
            $errorMsg = "Could not cancel request.";
        }
    }

    // 3. POST CUSTOM DEMAND LOGIC (Fixed Placement)
    if (isset($_POST['post_demand'])) {
        if ($isLoggedIn && $userRole === 'learner') {
            $skillName = mysqli_real_escape_string($conn, trim($_POST['demand_skill']));
            $skillDesc = mysqli_real_escape_string($conn, trim($_POST['demand_desc']));
            
            if (!empty($skillName)) {
                $ins = mysqli_query($conn, "INSERT INTO learner_demands (learner_id, skill_name, description) VALUES ($userId, '$skillName', '$skillDesc')");
                if ($ins) {
                    header("Location: browser.php?msg=demand_posted");
                    exit();
                } else {
                    $errorMsg = "Failed to post demand.";
                }
            }
        }
    }
}

// Success message handling from Redirects
if(isset($_GET['msg'])){
    if($_GET['msg'] == 'success') $successMsg = "Request sent successfully!";
    if($_GET['msg'] == 'cancelled') $successMsg = "Request cancelled successfully.";
    if($_GET['msg'] == 'demand_posted') $successMsg = "Your demand has been posted! Mentors will see this.";
}

// -----------------------------
// Data Fetching
// -----------------------------
$search = trim($_GET['search'] ?? "");
$searchSafe = mysqli_real_escape_string($conn, substr($search, 0, 100));

$sql = "SELECT ms.id, ms.skill_name, ms.skill_level, ms.description, u.first_name, u.last_name 
        FROM mentor_skills ms JOIN users u ON u.id = ms.mentor_id 
        WHERE u.role = 'mentor' AND u.is_active = 1 ";

if ($search !== "") {
    $sql .= " AND (ms.skill_name LIKE '%$searchSafe%' OR ms.skill_level LIKE '%$searchSafe%' OR ms.description LIKE '%$searchSafe%' OR u.first_name LIKE '%$searchSafe%' OR u.last_name LIKE '%$searchSafe%') ";
}
$sql .= " ORDER BY ms.id DESC LIMIT 50";
$q = mysqli_query($conn, $sql);

$totalSkills = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM mentor_skills ms JOIN users u ON u.id = ms.mentor_id WHERE u.role = 'mentor' AND u.is_active = 1"))['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Browse Skills - SkillSwap</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box;font-family:Arial;}
        body{background:#f5f5f5;color:#333;}
        .header{background:white;padding:15px 20px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center;}
        .logo{font-size:24px;font-weight:bold;color:#4a6cf7;text-decoration:none;}
        .nav{display:flex;gap:15px;align-items:center;}
        .nav a{color:#4a6cf7;text-decoration:none;font-weight:bold;}
        .container{width:90%;max-width:1200px;margin:20px auto;}
        .page-title{background:#4a6cf7;color:white;padding:20px;border-radius:8px;margin-bottom:20px;text-align:center;}
        .search-box{background:white;padding:20px;border-radius:8px;border:1px solid #ddd;margin-bottom:20px;display:flex;gap:10px;}
        .search-box input{flex:1;padding:10px;border:1px solid #ccc;border-radius:4px;font-size:16px;}
        .btn{background:#4a6cf7;color:white;border:none;padding:10px 20px;border-radius:4px;cursor:pointer;font-weight:bold;text-decoration:none;display:inline-block;width:100%;text-align:center;}
        .btn-cancel{background:#dc3545;margin-top:5px; font-size: 12px; padding: 5px;}
        .skills-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:20px;}
        .skill-card{background:white;border:1px solid #ddd;border-radius:8px;padding:15px;box-shadow:0 2px 5px rgba(0,0,0,0.1);}
        .skill-header{display:flex;justify-content:space-between;align-items:center;}
        .skill-name{font-size:18px;font-weight:bold;}
        .skill-level{background:#e7f4e4;color:#2e7d32;padding:4px 10px;border-radius:12px;font-size:12px;}
        .skill-desc{color:#777;font-size:14px;margin:10px 0;min-height:60px;}
        .msg{padding:12px;border-radius:8px;margin-bottom:15px;}
        .success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
        .error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;}
        .req-text{width:100%;padding:10px;margin-bottom:10px;border-radius:4px;border:1px solid #ccc;}
    </style>
</head>
<body>

<div class="header">
    <a href="index.php" class="logo">SkillSwap</a>
    <div class="nav">
        <?php if($isLoggedIn): ?>
            <a href="<?php echo $userRole; ?>.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="signup.php">Sign Up</a>
        <?php endif; ?>
        <a href="index.php">Home</a>
    </div>
</div>

<div class="container">
    <div class="page-title">
        <h1>Browse Available Skills</h1>
        <p>Current Total Skills: <?php echo $totalSkills; ?></p>
    </div>

    <?php if($successMsg): ?><div class="msg success"><?php echo $successMsg; ?></div><?php endif; ?>
    <?php if($errorMsg): ?><div class="msg error"><?php echo $errorMsg; ?></div><?php endif; ?>

    <div class="search-box">
        <form method="GET" style="display:contents;">
            <input type="text" name="search" placeholder="Search skills..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn" style="width:auto;">Search</button>
        </form>
    </div>

    <?php if($isLoggedIn && $userRole === 'learner'): ?>
    <div style="background: white; padding: 20px; border-radius: 8px; border: 2px dashed #4a6cf7; margin-bottom: 25px;">
        <h3 style="color: #4a6cf7; margin-bottom: 10px;">Missing a skill? Request one!</h3>
        <p style="font-size: 14px; color: #666; margin-bottom: 15px;">Post your demand and mentors will see what the market needs!</p>
        <form method="POST" style="display: flex; flex-direction: column; gap: 5px;">
            <input type="text" name="demand_skill" placeholder="Skill Name..." class="req-text" required>
            <textarea name="demand_desc" placeholder="Details..." class="req-text" style="height: 60px;"></textarea>
            <button type="submit" name="post_demand" class="btn" style="background: #28a745; width: 180px;">Post Demand</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="skills-grid">
        <?php while($row = mysqli_fetch_assoc($q)): ?>
            <div class="skill-card">
                <div class="skill-header">
                    <div class="skill-name"><?php echo htmlspecialchars($row['skill_name']); ?></div>
                    <div class="skill-level"><?php echo htmlspecialchars($row['skill_level']); ?></div>
                </div>
                <div style="font-size:13px; color:#666; margin-top:5px;">Mentor: <?php echo htmlspecialchars($row['first_name'].' '.$row['last_name']); ?></div>
                <div class="skill-desc"><?php echo htmlspecialchars($row['description'] ?: 'No description provided.'); ?></div>
                
                <div style="margin-top:15px;">
                    <?php if($isLoggedIn && $userRole === 'learner'): 
                        $sid = $row['id'];
                        $check = mysqli_query($conn, "SELECT status FROM skill_requests WHERE learner_id = $userId AND mentor_skill_id = $sid LIMIT 1");
                        $res = mysqli_fetch_assoc($check);
                        
                        if(!$res): ?>
                            <form method="POST">
                                <input type="hidden" name="skill_id" value="<?php echo $sid; ?>">
                                <textarea class="req-text" name="description" placeholder="Short message..."></textarea>
                                <button type="submit" name="request_to_learn" class="btn">Request to Learn</button>
                            </form>
                        <?php elseif($res['status'] == 'Pending'): ?>
                            <div style="text-align:center;">
                                <span style="color:#f39c12; font-weight:bold; font-size:14px;">Status: Pending</span>
                                <form method="POST">
                                    <input type="hidden" name="skill_id" value="<?php echo $sid; ?>">
                                    <button type="submit" name="cancel_request" class="btn btn-cancel" onclick="return confirm('Cancel?')">Cancel Request</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <button class="btn" style="background:#28a745;" disabled>Already Enrolled</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>
</body>
</html>