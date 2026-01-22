
<?php

session_start();

include "db.php";



/* =========================

   SECURITY: Learner only

========================= */

if (!isset($_SESSION['uid']) || ($_SESSION['role'] ?? '') !== 'learner') {

    header("Location: login.php");

    exit;

}

$learnerId = (int)$_SESSION['uid'];



/* =========================

   BASIC INFO & HELPERS

========================= */

$successMsg = "";

$errorMsg = "";



$meQ = mysqli_query($conn, "SELECT first_name, last_name FROM users WHERE id=$learnerId LIMIT 1");

$me = mysqli_fetch_assoc($meQ);

$learnerName = $me ? ($me['first_name'] . " " . $me['last_name']) : "Learner";



function clean_text($conn, $txt, $maxLen = 500) {

    $txt = trim($txt ?? "");

    return mysqli_real_escape_string($conn, substr($txt, 0, $maxLen));

}



/* =========================================================

   1) ACTION: REQUEST A NEW SKILL

========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_skill'])) {

    $mentorSkillId = (int)$_POST['mentor_skill_id'];

    $skillNameS = clean_text($conn, $_POST['skill_name'], 100);

    $descS = clean_text($conn, $_POST['learner_note'], 500);



    // Check if already requested

    $check = mysqli_query($conn, "SELECT id FROM skill_requests WHERE learner_id=$learnerId AND mentor_skill_id=$mentorSkillId");

    if (mysqli_num_rows($check) == 0) {

        // progress_percent 0 diye initialize kora hoyeche

        $q = "INSERT INTO skill_requests (learner_id, mentor_skill_id, skill_name, description, status, progress_percent) 

              VALUES ($learnerId, $mentorSkillId, '$skillNameS', '$descS', 'Pending', 0)";

        if (mysqli_query($conn, $q)) $successMsg = "Application sent to mentor!";

    } else {

        $errorMsg = "You have already applied for this course.";

    }

}



/* =========================================================

   2) ACTION: MESSAGING

========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_msg'])) {

    $requestId = (int)$_POST['request_id'];

    $mentorId = (int)$_POST['receiver_id'];

    $msgS = clean_text($conn, $_POST['message'], 500);

    mysqli_query($conn, "INSERT INTO messages (request_id, sender_id, receiver_id, message) VALUES ($requestId, $learnerId, $mentorId, '$msgS')");

    $successMsg = "Message sent to mentor.";

}



/* =========================================================

   3) DATA FETCHING

========================================================= */



$sql = "SELECT 

            sr.id, 

            sr.skill_name, 

            sr.status, 

            sr.progress_percent, 

            sr.is_completed,

            u.first_name AS m_first, 

            u.last_name AS m_last,

            ms.mentor_id

        FROM skill_requests sr

        JOIN mentor_skills ms ON sr.mentor_skill_id = ms.id

        JOIN users u ON ms.mentor_id = u.id

        WHERE sr.learner_id = $learnerId

        ORDER BY sr.id DESC";



$myRequests = mysqli_query($conn, $sql);



// Progress Stats

$activeCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM skill_requests WHERE learner_id=$learnerId AND status='Accepted'"))['c'];

$pendingCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM skill_requests WHERE learner_id=$learnerId AND status='Pending'"))['c'];

$masteredCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM skill_requests WHERE learner_id=$learnerId AND progress_percent >= 100"))['c'];


// Discovery Logic: Shudu shei skills dekhabe jader jonno learner ekhono apply koreni
$suggestedQ = mysqli_query($conn, "
    SELECT ms.*, u.first_name, u.last_name 
    FROM mentor_skills ms 
    JOIN users u ON u.id = ms.mentor_id 
    WHERE ms.id NOT IN (
        SELECT mentor_skill_id 
        FROM skill_requests 
        WHERE learner_id = $learnerId
    )
    ORDER BY RAND()
    LIMIT 3
");

?>



<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <title>Learner Dashboard - SkillSwap</title>

    <style>

        *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI', sans-serif;}

        body{background:#f0f2f5; color:#333;}

        .navbar{background:#4a6cf7; padding:15px 50px; display:flex; justify-content:space-between; color:white;}

        .navbar a{color:white; text-decoration:none; font-weight:600; margin-left:20px;}

        .container{max-width:1200px; margin:30px auto; padding:0 20px;}

        .alert{padding:15px; border-radius:8px; margin-bottom:20px; font-weight: 500;}

        .alert-success{background:#d4edda; color:#155724; border:1px solid #c3e6cb;}

        .alert-error{background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;}

        .header-stats{display:flex; gap:20px; margin-bottom:30px;}

        .stat-card{flex:1; background:white; padding:20px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05); text-align:center;}

        .stat-card h2{color:#4a6cf7; font-size:32px;}

        .grid{display:grid; grid-template-columns: 2fr 1fr; gap:30px;}

        .box{background:white; padding:25px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05); margin-bottom:20px;}

        

        /* Progress Bar Styling */

        .progress-container{background:#eee; border-radius:10px; height:12px; margin:10px 0; overflow:hidden; position: relative;}

        .progress-fill{background: linear-gradient(90deg, #4a6cf7, #6e8efb); height:100%; border-radius:10px; transition: width 0.8s ease-in-out;}

        

        .course-item{border-bottom:1px solid #eee; padding:15px 0; display:flex; justify-content:space-between; align-items:center;}

        .status-badge{padding:5px 12px; border-radius:20px; font-size:12px; font-weight:bold;}

        .status-accepted{background:#d4edda; color:#155724;}

        .status-pending{background:#fff3cd; color:#856404;}

        .btn{padding:10px 20px; border-radius:8px; border:none; cursor:pointer; font-weight:600; transition:0.3s;}

        .btn-primary{background:#4a6cf7; color:white;}

        .btn-outline{border:1px solid #4a6cf7; color:#4a6cf7; background:none;}

        .mentor-card{border:1px solid #eee; padding:15px; border-radius:8px; margin-top:10px;}

        input, textarea{width:100%; padding:10px; margin:10px 0; border:1px solid #ddd; border-radius:6px;}

        .certificate-link{color:#4a6cf7; font-size:13px; text-decoration:none; display:block; margin-top:5px; font-weight: bold;}

    </style>

</head>

<body>



<div class="navbar">

    <div style="font-size:24px; font-weight:bold;">SkillSwap</div>

    <div>

        <a href="browser.php">Explore Mentors</a>

        <a href="learner.php">My Learning</a>

        <a href="logout.php">Logout</a>

    </div>

</div>



<div class="container">

    <?php if($successMsg): ?><div class="alert alert-success"><?php echo $successMsg; ?></div><?php endif; ?>

    <?php if($errorMsg): ?><div class="alert alert-error"><?php echo $errorMsg; ?></div><?php endif; ?>



    <div style="margin-bottom:20px;">

        <h1>Hello, <?php echo htmlspecialchars($learnerName); ?> 👋</h1>

    </div>



    <div class="header-stats">

        <div class="stat-card"><h2><?php echo $activeCount; ?></h2><p>Courses in Progress</p></div>

        <div class="stat-card"><h2><?php echo $pendingCount; ?></h2><p>Pending Applications</p></div>

        <div class="stat-card"><h2><?php echo $masteredCount; ?></h2><p>Skills Mastered</p></div>

    </div>



    <div class="grid">

        <div class="box">

            <h3>My Learning Path</h3>

            <?php if(mysqli_num_rows($myRequests) > 0): ?>

                <?php while($r = mysqli_fetch_assoc($myRequests)): ?>

                <div class="course-item">

                    <div style="flex:1;">

                        <h4 style="margin-bottom:5px;"><?php echo htmlspecialchars($r['skill_name']); ?></h4>

                        <small>Mentor: <strong><?php echo htmlspecialchars(($r['m_first'] ?? 'Unknown')." ".($r['m_last'] ?? '')); ?></strong></small>

                        

                        <?php if($r['status'] == 'Accepted'): ?>

                            <?php $prog = (int)$r['progress_percent']; ?>

                            <div class="progress-container">

                                <div class="progress-fill" style="width: <?php echo $prog; ?>%;"></div>

                            </div>

                            <small>Progress: <strong><?php echo $prog; ?>%</strong> completed</small>

                            

                            <?php if($prog >= 100): ?>

                                <a href="certificate.php?id=<?php echo $r['id']; ?>" class="certificate-link">🎓 Download Certificate</a>

                            <?php else: ?>

                                <span class="certificate-link" style="color:#999;">🔒 Certificate unlocks at 100%</span>

                            <?php endif; ?>

                        <?php endif; ?>

                    </div>

                    

                    <div style="text-align:right;">

                        <span class="status-badge <?php echo ($r['status']=='Accepted') ? 'status-accepted' : 'status-pending'; ?>">

                            <?php echo htmlspecialchars($r['status']); ?>

                        </span>

                        

                        <?php if($r['status'] == 'Accepted'): ?>

                            <button onclick="toggleChat(<?php echo $r['id']; ?>)" class="btn btn-outline" style="margin-top:10px; display:block;">Chat with Mentor</button>

                        <?php endif; ?>

                    </div>

                </div>



                <div id="chat-<?php echo $r['id']; ?>" class="box" style="display:none; margin-top:10px; background:#f9f9f9;">

                    <h5>Conversation with <?php echo htmlspecialchars($r['m_first'] ?? 'Mentor'); ?></h5>

                    <div style="height:150px; overflow-y:auto; border:1px solid #ddd; background:white; padding:10px; margin:10px 0;" id="msg-area-<?php echo $r['id']; ?>">

                        <?php

                            $rid = (int)$r['id'];

                            $msgs = mysqli_query($conn, "SELECT m.*, u.first_name FROM messages m JOIN users u ON u.id=m.sender_id WHERE m.request_id=$rid ORDER BY m.id ASC");

                            while($m = mysqli_fetch_assoc($msgs)){

                                $align = ($m['sender_id'] == $learnerId) ? 'text-align:right;' : '';

                                $color = ($m['sender_id'] == $learnerId) ? '#e3f2fd' : '#f5f5f5';

                                echo "<div style='$align margin-bottom:5px;'><span style='background:$color; padding:5px 10px; border-radius:10px; display:inline-block;'>".htmlspecialchars($m['message'])."</span></div>";

                            }

                        ?>

                    </div>

                    <form method="POST">

                        <input type="hidden" name="request_id" value="<?php echo $r['id']; ?>">

                        <input type="hidden" name="receiver_id" value="<?php echo $r['mentor_id']; ?>">

                        <textarea name="message" placeholder="Type message..." rows="2" required></textarea>

                        <button type="submit" name="send_msg" class="btn btn-primary">Send</button>

                    </form>

                </div>

                <?php endwhile; ?>

            <?php else: ?>

                <p style="color:#666;">No enrollments found.</p>

            <?php endif; ?>

        </div>

<div class="box">
    <h3>Recommended for You</h3>
    <p style="font-size:12px; color:#666; margin-bottom:15px;">Explore mentors you haven't joined yet:</p>
    
    <?php if(mysqli_num_rows($suggestedQ) > 0): ?>
        <?php while($s = mysqli_fetch_assoc($suggestedQ)): ?>
            <div class="mentor-card">
                <strong><?php echo htmlspecialchars($s['skill_name']); ?></strong>
                <div style="font-size:13px; color:#666;">Mentor: <?php echo htmlspecialchars($s['first_name']." ".$s['last_name']); ?></div>
                <form method="POST">
                    <input type="hidden" name="mentor_skill_id" value="<?php echo $s['id']; ?>">
                    <input type="hidden" name="skill_name" value="<?php echo $s['skill_name']; ?>">
                    <input type="hidden" name="learner_note" value="Hi, I want to learn this!">
                    <button type="submit" name="request_skill" class="btn btn-outline" style="width:100%; margin-top:5px;">Join Mentor</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="font-size:13px; color:#999;">You have applied to all available mentors!</p>
    <?php endif; ?>
</div>

    
        </div>

    </div>

</div>



<script>

function toggleChat(id) {

    var chat = document.getElementById('chat-' + id);

    chat.style.display = (chat.style.display === "none") ? "block" : "none";

    if(chat.style.display === "block") {

        var msgArea = document.getElementById('msg-area-' + id);

        msgArea.scrollTop = msgArea.scrollHeight;

    }

}

</script>



</body>

</html> 