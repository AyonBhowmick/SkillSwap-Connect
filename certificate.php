<?php
session_start();
include "db.php";

if (!isset($_SESSION['uid'])) {
    header("Location: login.php");
    exit;
}

$requestId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$userId = (int)$_SESSION['uid'];

// Query to get certificate details
$sql = "SELECT 
            sr.skill_name, 
            sr.progress_percent,
            u_learner.first_name AS l_first, u_learner.last_name AS l_last,
            u_mentor.first_name AS m_first, u_mentor.last_name AS m_last
        FROM skill_requests sr
        JOIN users u_learner ON sr.learner_id = u_learner.id
        JOIN mentor_skills ms ON sr.mentor_skill_id = ms.id
        JOIN users u_mentor ON ms.mentor_id = u_mentor.id
        WHERE sr.id = $requestId AND sr.learner_id = $userId AND sr.progress_percent >= 100";

$res = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($res);

if (!$data) {
    die("Certificate not available yet. Please complete 100% of the course.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Completion</title>
    <style>
        body { font-family: 'Georgia', serif; background: #f0f0f0; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .certificate-container { 
            width: 800px; padding: 50px; background: white; border: 15px solid #4a6cf7; 
            box-shadow: 0 0 20px rgba(0,0,0,0.2); position: relative; text-align: center;
        }
        .certificate-container::after {
            content: ""; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            border: 2px solid #4a6cf7; margin: 5px; pointer-events: none;
        }
        .logo { font-size: 30px; font-weight: bold; color: #4a6cf7; margin-bottom: 20px; }
        h1 { font-size: 50px; margin: 10px 0; color: #333; }
        h2 { font-weight: normal; font-style: italic; margin: 20px 0; }
        .learner-name { font-size: 35px; color: #4a6cf7; border-bottom: 2px solid #ccc; display: inline-block; padding: 0 30px; }
        .course-name { font-size: 24px; font-weight: bold; margin: 20px 0; }
        .footer { margin-top: 50px; display: flex; justify-content: space-between; padding: 0 50px; }
        .sig-box { border-top: 1px solid #333; width: 200px; padding-top: 5px; }
        .btn-print { 
            position: fixed; top: 20px; right: 20px; padding: 10px 20px; 
            background: #4a6cf7; color: white; border: none; border-radius: 5px; cursor: pointer;
        }
        @media print { .btn-print { display: none; } body { background: white; } }
    </style>
</head>
<body>

<button class="btn-print" onclick="window.print()">Download / Print PDF</button>

<div class="certificate-container">
    <div class="logo">SkillSwap</div>
    <h2>CERTIFICATE OF COMPLETION</h2>
    <p>This is to certify that</p>
    <div class="learner-name"><?php echo htmlspecialchars($data['l_first'] . " " . $data['l_last']); ?></div>
    <p>has successfully completed the course</p>
    <div class="course-name">"<?php echo htmlspecialchars($data['skill_name']); ?>"</div>
    <p>on this day of <strong><?php echo date('F d, Y'); ?></strong></p>

    

    <div class="footer">
        <div class="sig-box">
            <strong>SkillSwap Academy</strong><br><small>Official Platform</small>
        </div>
        <div class="sig-box">
            <strong><?php echo htmlspecialchars($data['m_first'] . " " . $data['m_last']); ?></strong><br><small>Course Mentor</small>
        </div>
    </div>
</div>

</body>
</html>