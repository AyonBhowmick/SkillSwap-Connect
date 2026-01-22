<?php
session_start();
include "db.php";

/* =========================
   SECURITY: Mentor only
========================= */
if (!isset($_SESSION['uid']) || ($_SESSION['role'] ?? '') !== 'mentor') {
    header("Location: login.php");
    exit;
}
$mentorId = (int)$_SESSION['uid'];

/* =========================
   BASIC INFO & HELPERS
========================= */
$successMsg = "";
$errorMsg = "";

$meQ = mysqli_query($conn, "SELECT first_name, last_name FROM users WHERE id=$mentorId LIMIT 1");
$me = ($meQ && mysqli_num_rows($meQ) == 1) ? mysqli_fetch_assoc($meQ) : null;
$mentorName = $me ? ($me['first_name'] . " " . $me['last_name']) : "Mentor";

function clean_text($conn, $txt, $maxLen = 500) {
    $txt = trim($txt ?? "");
    if (strlen($txt) > $maxLen) $txt = substr($txt, 0, $maxLen);
    return mysqli_real_escape_string($conn, $txt);
}

/* =========================================================
   1) ACTION: UPDATE PROGRESS (STAYED INTACT)
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $requestId = (int)$_POST['request_id'];
    $prog = (int)$_POST['progress_percent'];
    if($prog > 100) $prog = 100;
    if($prog < 0) $prog = 0;

    $upd = mysqli_query($conn, "UPDATE skill_requests SET progress_percent=$prog WHERE id=$requestId");
    if ($upd) $successMsg = "Progress updated to $prog%.";
}

/* =========================================================
   2) ACTION: ADD / EDIT / DELETE SKILL (STAYED INTACT)
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_skill'])) {
    $skillNameS = clean_text($conn, $_POST['skill_name'], 100);
    $levelS = clean_text($conn, $_POST['skill_level'], 50);
    $descS = clean_text($conn, $_POST['description'], 500);
    $ins = mysqli_query($conn, "INSERT INTO mentor_skills (mentor_id, skill_name, skill_level, description) VALUES ($mentorId, '$skillNameS', '$levelS', '$descS')");
    if ($ins) $successMsg = "Skill added successfully.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_skill'])) {
    $skillId = (int)$_POST['skill_id'];
    $skillNameS = clean_text($conn, $_POST['skill_name'], 100);
    $levelS = clean_text($conn, $_POST['skill_level'], 50);
    $descS = clean_text($conn, $_POST['description'], 500);
    $up = mysqli_query($conn, "UPDATE mentor_skills SET skill_name='$skillNameS', skill_level='$levelS', description='$descS' WHERE id=$skillId AND mentor_id=$mentorId");
    if ($up) $successMsg = "Skill updated successfully.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_skill'])) {
    $skillId = (int)$_POST['skill_id'];
    mysqli_query($conn, "DELETE FROM mentor_skills WHERE id=$skillId AND mentor_id=$mentorId");
    $successMsg = "Skill deleted.";
}

/* =========================================================
   3) ACTION: ENROLLMENT CONTROL (Accept/Reject)
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_action'])) {
    $requestId = (int)$_POST['request_id'];
    $action = $_POST['request_action'];
    $newStatus = ($action === 'accept') ? 'Accepted' : 'Rejected';
    
    $check = mysqli_query($conn, "SELECT sr.id FROM skill_requests sr JOIN mentor_skills ms ON ms.id = sr.mentor_skill_id WHERE sr.id = $requestId AND ms.mentor_id = $mentorId");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE skill_requests SET status='$newStatus' WHERE id=$requestId");
        $successMsg = "Request marked as $newStatus.";
    }
}

/* =========================================================
   4) ACTION: MESSAGING (REPLY)
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message'])) {
    $requestId = (int)$_POST['request_id'];
    $receiverId = (int)$_POST['receiver_id'];
    $msgS = clean_text($conn, $_POST['message'], 500);
    mysqli_query($conn, "INSERT INTO messages (request_id, sender_id, receiver_id, message) VALUES ($requestId, $mentorId, $receiverId, '$msgS')");
    $successMsg = "Reply sent.";
}

/* =========================================================
   5) DATA FETCHING (PRESERVED ALL YOUR QUERIES)
========================================================= */
$skillCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM mentor_skills WHERE mentor_id=$mentorId"))['c'];
$pendingCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM skill_requests sr JOIN mentor_skills ms ON ms.id = sr.mentor_skill_id WHERE ms.mentor_id=$mentorId AND sr.status='Pending'"))['c'];
$totalLearners = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT sr.learner_id) as c FROM skill_requests sr JOIN mentor_skills ms ON ms.id = sr.mentor_skill_id WHERE ms.mentor_id=$mentorId AND sr.status='Accepted'"))['c'];

// Revenue Logic
$incomeQ = mysqli_query($conn, "SELECT (COUNT(sr.id) * mp.rate) as total FROM skill_requests sr JOIN mentor_skills ms ON ms.id = sr.mentor_skill_id JOIN mentor_profiles mp ON mp.user_id = ms.mentor_id WHERE ms.mentor_id=$mentorId AND sr.status='Accepted' GROUP BY mp.rate");
$incomeRow = mysqli_fetch_assoc($incomeQ);
$totalIncome = number_format((float)($incomeRow['total'] ?? 0), 2);

// Demand Data (Fixed: Fetching once here)
$analysisQ = mysqli_query($conn, "SELECT skill_name, COUNT(*) as request_count FROM learner_demands GROUP BY skill_name ORDER BY request_count DESC LIMIT 5");

$mySkillsQ = mysqli_query($conn, "SELECT * FROM mentor_skills WHERE mentor_id=$mentorId ORDER BY id DESC");
$reqQ = mysqli_query($conn, "SELECT sr.*, u.first_name, u.last_name FROM skill_requests sr JOIN mentor_skills ms ON ms.id = sr.mentor_skill_id JOIN users u ON u.id = sr.learner_id WHERE ms.mentor_id = $mentorId ORDER BY sr.id DESC");

$editSkill = null;
if (isset($_GET['edit_skill_id'])) {
    $editId = (int)$_GET['edit_skill_id'];
    $e = mysqli_query($conn, "SELECT * FROM mentor_skills WHERE id=$editId AND mentor_id=$mentorId LIMIT 1");
    if ($e && mysqli_num_rows($e) == 1) $editSkill = mysqli_fetch_assoc($e);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mentor Dashboard - SkillSwap</title>
    <style>
        /* Existing Styles Preserved */
        *{margin:0;padding:0;box-sizing:border-box;font-family:Arial, sans-serif;}
        body{background:#f5f5f5;color:#333; padding-bottom: 50px;}
        .header{background:white;padding:15px 20px;border-bottom:1px solid #ddd;display:flex;justify-content:space-between;align-items:center;}
        .logo{font-size:24px;font-weight:bold;color:#4a6cf7;text-decoration:none;}
        .nav a{color:#4a6cf7;text-decoration:none;font-weight:bold;margin-left:15px;}
        .container{width:95%;max-width:1200px;margin:20px auto;}
        .page-title{background:#4a6cf7;color:white;padding:20px;border-radius:8px;margin-bottom:20px;text-align:center;}
        .stats{display:flex;gap:15px;margin-bottom:20px;flex-wrap:wrap;}
        .stat-box{flex:1;min-width:200px;background:white;border:1px solid #ddd;border-radius:8px;padding:20px;text-align:center;box-shadow:0 2px 5px rgba(0,0,0,0.05);}
        .stat-num{font-size:28px;font-weight:bold;color:#4a6cf7;}
        .stat-lbl{color:#666;font-size:12px;margin-top:5px;text-transform:uppercase;letter-spacing:1px;}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
        .box{background:white;border:1px solid #ddd;border-radius:8px;padding:20px;box-shadow:0 2px 5px rgba(0,0,0,0.05); margin-bottom: 20px;}
        .box h3{margin-bottom:15px; border-bottom: 1px solid #eee; padding-bottom: 10px;}
        table{width:100%;border-collapse:collapse;margin-top:10px;}
        th, td{border:1px solid #eee;padding:12px;text-align:left;font-size:14px;}
        th{background:#f9f9f9;}
        .btn{padding:8px 14px;border-radius:4px;cursor:pointer;font-weight:bold;text-decoration:none;border:none;display:inline-block;font-size:13px;}
        .btn-blue{background:#4a6cf7;color:white;}
        .btn-green{background:#28a745;color:white;}
        .btn-red{background:#dc3545;color:white;}
        input, select, textarea{width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;margin-top:8px;}
        .msg{padding:15px;border-radius:8px;margin-bottom:15px; font-weight: bold;}
        .success{background:#d4edda;color:#155724;border:1px solid #c3e6cb;}
        .msg-box{background:#f9f9f9; padding:10px; border-radius:5px; margin-top:10px; font-size:13px;}
        .chat-area{max-height:150px; overflow-y:auto; background:white; padding:10px; border-radius:4px; border:1px solid #eee; margin-bottom:10px;}
        @media (max-width: 900px){.grid{grid-template-columns:1fr;}}
    </style>
</head>
<body>

<div class="header">
    <a href="index.php" class="logo">SkillSwap</a>
    <div class="nav">
        <a href="browser.php">Browse</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <div class="page-title">
        <h1>Mentor Console</h1>
        <p>Welcome, <?php echo $mentorName; ?>. Control your courses and learners.</p>
    </div>

    <?php if($successMsg): ?><div class="msg success"><?php echo $successMsg; ?></div><?php endif; ?>

    <div class="stats">
        <div class="stat-box"><div class="stat-num"><?php echo $skillCount; ?></div><div class="stat-lbl">My Courses</div></div>
        <div class="stat-box"><div class="stat-num"><?php echo $pendingCount; ?></div><div class="stat-lbl">Waitlist</div></div>
        <div class="stat-box" style="border-top: 4px solid #28a745;"><div class="stat-num"><?php echo $totalLearners; ?></div><div class="stat-lbl">Active Learners</div></div>
        <div class="stat-box" style="border-top: 4px solid #ffc107;"><div class="stat-num">$<?php echo $totalIncome; ?></div><div class="stat-lbl">Net Revenue</div></div>
    </div>

    <div class="grid">
        <div class="box">
            <h3><?php echo $editSkill ? "Update Course" : "Create New Course"; ?></h3>
            <form method="POST">
                <?php if($editSkill): ?><input type="hidden" name="skill_id" value="<?php echo $editSkill['id']; ?>"><?php endif; ?>
                <label>Course Title</label>
                <input type="text" name="skill_name" value="<?php echo $editSkill ? htmlspecialchars($editSkill['skill_name']) : ''; ?>" required>
                
                <label style="display:block; margin-top:10px;">Difficulty Level</label>
                <select name="skill_level">
                    <option <?php if($editSkill && $editSkill['skill_level']=='Beginner') echo 'selected'; ?>>Beginner</option>
                    <option <?php if($editSkill && $editSkill['skill_level']=='Intermediate') echo 'selected'; ?>>Intermediate</option>
                    <option <?php if($editSkill && $editSkill['skill_level']=='Advanced') echo 'selected'; ?>>Advanced</option>
                </select>

                <label style="display:block; margin-top:10px;">Syllabus / Description</label>
                <textarea name="description" rows="3"><?php echo $editSkill ? htmlspecialchars($editSkill['description']) : ''; ?></textarea>
                
                <button type="submit" name="<?php echo $editSkill ? 'edit_skill' : 'add_skill'; ?>" class="btn btn-blue" style="width:100%; margin-top:15px;">
                    <?php echo $editSkill ? "Save Changes" : "Launch Course"; ?>
                </button>
            </form>
        </div>

        <div class="box">
            <h3>📊 Market Demand Analysis</h3>
            <p style="font-size:12px; color:#666; margin-bottom:10px;">Highest requested skills from learners:</p>
            <table>
                <tr><th>Skill Name</th><th>Total Requests</th></tr>
                <?php while($row = mysqli_fetch_assoc($analysisQ)): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($row['skill_name']); ?></strong></td>
                    <td><span style="background: #4a6cf7; color:white; padding: 2px 8px; border-radius: 10px; font-size: 11px;"><?php echo $row['request_count']; ?> Learners</span></td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <div class="box">
        <h3>Learner Management & Chat</h3>
        <?php if(mysqli_num_rows($reqQ) > 0): ?>
        <table>
            <tr><th>Learner Name</th><th>Course</th><th>Status</th><th>Control & Conversations</th></tr>
            <?php while($r = mysqli_fetch_assoc($reqQ)): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['first_name']." ".$r['last_name']); ?></td>
                <td><?php echo htmlspecialchars($r['skill_name']); ?></td>
                <td><strong><?php echo $r['status']; ?></strong></td>
                <td style="width: 50%;">
                    <?php if($r['status'] == 'Pending'): ?>
                        <form method="POST">
                            <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                            <button type="submit" name="request_action" value="accept" class="btn btn-green">Enroll Learner</button>
                            <button type="submit" name="request_action" value="reject" class="btn btn-red">Decline</button>
                        </form>
                    <?php else: ?>
                        <div style="background:#eef2ff; padding:8px; border-radius:5px; margin-bottom:10px;">
                            <form method="POST" style="display:flex; align-items:center; gap:10px;">
                                <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                <span style="font-size:11px;">Progress %:</span>
                                <input type="number" name="progress_percent" value="<?php echo $r['progress_percent']; ?>" min="0" max="100" style="width:50px; margin:0; padding:2px;">
                                <button type="submit" name="update_progress" class="btn btn-blue" style="padding:2px 8px; font-size:11px;">Update</button>
                            </form>
                        </div>

                        <div class="msg-box">
                            <div class="chat-area">
                                <?php
                                $rid = (int)$r['id'];
                                $msgs = mysqli_query($conn, "SELECT m.*, u.first_name FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.request_id=$rid ORDER BY m.id ASC");
                                while($m = mysqli_fetch_assoc($msgs)){
                                    $isMe = ($m['sender_id'] == $mentorId);
                                    $style = $isMe ? "text-align:right; color:#4a6cf7;" : "text-align:left; color:#333;";
                                    echo "<div style='$style font-size:12px; margin-bottom:5px;'><strong>".($isMe ? "Me" : htmlspecialchars($m['first_name'])).":</strong> ".htmlspecialchars($m['message'])."</div>";
                                }
                                ?>
                            </div>
                            <form method="POST" style="display:flex; gap:5px;">
                                <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">
                                <input type="hidden" name="receiver_id" value="<?php echo $r['learner_id']; ?>">
                                <input type="text" name="message" placeholder="Type..." required style="flex:1; margin:0; padding:5px; font-size:12px;">
                                <button type="submit" name="reply_message" class="btn btn-blue" style="padding:5px 10px;">Reply</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
        <p>No active learners or requests.</p>
        <?php endif; ?>
    </div>

    <div class="box">
        <h3>My Courses</h3>
        <table>
            <tr><th>Course Name</th><th>Level</th><th>Action</th></tr>
            <?php mysqli_data_seek($mySkillsQ, 0); ?>
            <?php while($s = mysqli_fetch_assoc($mySkillsQ)): ?>
            <tr>
                <td><?php echo htmlspecialchars($s['skill_name']); ?></td>
                <td><?php echo $s['skill_level']; ?></td>
                <td>
                    <a href="mentor.php?edit_skill_id=<?php echo $s['id']; ?>" class="btn btn-blue">Edit</a>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="skill_id" value="<?php echo $s['id']; ?>">
                        <button type="submit" name="delete_skill" class="btn btn-red" onclick="return confirm('Delete course?');">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
</div>
</body>
</html>