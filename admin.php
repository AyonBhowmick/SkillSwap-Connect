<?php
session_start();
include "db.php";

// 1. Super Admin Security Check
if (!isset($_SESSION['uid']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$successMsg = "";

// 2. Feature: Post Global Announcement
if (isset($_POST['post_notice'])) {
    $notice = mysqli_real_escape_string($conn, $_POST['announcement']);
    // Check if settings table exists and update
    $check = mysqli_query($conn, "SELECT * FROM settings WHERE name='global_notice'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE settings SET value='$notice' WHERE name='global_notice'");
    } else {
        mysqli_query($conn, "INSERT INTO settings (name, value) VALUES ('global_notice', '$notice')");
    }
    $successMsg = "Notice updated for all users!";
}

// 3. Stats Calculation (Fixed variable names to match HTML)
$totalUsers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$totalMentors = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='mentor'"))['count'];
$totalSkills = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM mentor_skills"))['count'];
$totalRequests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM skill_requests"))['count'];
$pendingRequests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM skill_requests WHERE status='Pending'"))['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Control Center | SkillSwap</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --sidebar-bg: #1e293b; --main-bg: #f8fafc; --accent: #4f46e5; }
        body { background-color: var(--main-bg); font-family: 'Inter', sans-serif; }
        
        /* Sidebar Design - Fixed */
        .sidebar { width: 260px; height: 100vh; background: var(--sidebar-bg); color: white; position: fixed; padding: 20px; transition: 0.3s; z-index: 100; }
        .sidebar h4 { font-weight: 800; letter-spacing: -1px; margin-bottom: 30px; color: #6366f1; }
        .sidebar a { color: #94a3b8; text-decoration: none; padding: 12px 15px; display: block; border-radius: 8px; margin-bottom: 8px; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: white; }
        .sidebar i { margin-right: 10px; width: 20px; }

        /* Main Content Area */
        .main-content { margin-left: 260px; padding: 40px; }
        .stat-card { border: none; border-radius: 16px; transition: 0.3s; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); background: white; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        
        .announcement-box { background: white; border-radius: 16px; padding: 25px; border-left: 5px solid var(--accent); }
        .btn-primary { background: var(--accent); border: none; padding: 10px 25px; border-radius: 8px; }
        .badge-date { background: white; color: #1e293b; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

<div class="sidebar">
    <h4>SkillSwap Admin</h4>
    <a href="admin.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin.php') ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i> Dashboard
    </a>
    <a href="admin_users.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin_users.php') ? 'active' : ''; ?>">
        <i class="fas fa-users"></i> Manage Users
    </a>
    <a href="admin_manage_skills.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin_manage_skills.php') ? 'active' : ''; ?>">
        <i class="fas fa-book-open"></i> Manage All Skills
    </a>
    <a href="admin_requests.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'admin_requests.php') ? 'active' : ''; ?>">
        <i class="fas fa-exchange-alt"></i> Skill Requests
    </a>
    <hr style="border-color: #334155;">
    <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">Platform Overview</h2>
            <p class="text-muted">Welcome back! Here is what's happening today.</p>
        </div>
        <div class="text-end">
            <span class="badge badge-date p-2 shadow-sm">
                <i class="far fa-calendar-alt me-1"></i> <?php echo date('Y-m-d'); ?>
            </span>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card stat-card p-3">
                <small class="text-muted text-uppercase fw-bold">Total Users</small>
                <h2 class="fw-bold text-dark"><?php echo $totalUsers; ?></h2>
                <div class="text-success small"><i class="fas fa-user-friends"></i> Mentors: <?php echo $totalMentors; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3">
                <small class="text-muted text-uppercase fw-bold">Live Skills</small>
                <h2 class="fw-bold text-primary"><?php echo $totalSkills; ?></h2>
                <div class="text-muted small">Active on platform</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3">
                <small class="text-muted text-uppercase fw-bold">Total Swaps</small>
                <h2 class="fw-bold text-success"><?php echo $totalRequests; ?></h2>
                <div class="text-muted small">Lifetime requests</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3 bg-danger text-white">
                <small class="text-uppercase fw-bold text-white-50">Action Needed</small>
                <h2 class="fw-bold text-white"><?php echo $pendingRequests; ?></h2>
                <div class="small">Pending requests</div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="announcement-box shadow-sm mb-4">
                <h5 class="fw-bold mb-3"><i class="fas fa-bullhorn text-accent"></i> Push Global Announcement</h5>
                <?php if($successMsg): ?> 
                    <div class="alert alert-success py-2 border-0 shadow-sm mb-3">
                        <i class="fas fa-check-circle me-2"></i> <?php echo $successMsg; ?>
                    </div> 
                <?php endif; ?>
                <form method="POST">
                    <textarea name="announcement" class="form-control mb-3 border-0 bg-light" rows="4" placeholder="Update notice..." required></textarea>
                    <button type="submit" name="post_notice" class="btn btn-primary shadow-sm">
                        <i class="fas fa-paper-plane me-2"></i> Update Notice Board
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card p-4 shadow-sm border-0 h-100 bg-white">
                <h5 class="fw-bold mb-4">Quick Actions</h5>
                <a href="admin_users.php" class="btn btn-outline-primary w-100 mb-3 text-start">
                    <i class="fas fa-user-shield me-2"></i> User Access Control
                </a>
                <a href="admin_manage_skills.php" class="btn btn-outline-dark w-100 mb-3 text-start">
                    <i class="fas fa-book-open me-2"></i> Manage Skills
                </a>
                <div class="mt-4 p-3 bg-light rounded-3 text-center border">
                    <small class="text-muted d-block mb-1">System Health</small>
                    <div class="fw-bold text-success"><i class="fas fa-heartbeat me-1"></i> 99.9% Online</div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>