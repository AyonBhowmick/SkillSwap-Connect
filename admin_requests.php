<?php
session_start();
include "db.php";

// 1. Admin Security Check
if (!isset($_SESSION['uid']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$uid = (int)$_SESSION['uid'];
$msg = "";

// 2. Handle Actions (Approve, Reject, Delete, Assign)
if (isset($_GET['action'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        mysqli_query($conn, "UPDATE skill_requests SET status='Accepted' WHERE id=$id");
        $msg = "Request approved!";
    } elseif ($action == 'reject') {
        mysqli_query($conn, "UPDATE skill_requests SET status='Rejected' WHERE id=$id");
        $msg = "Request rejected!";
    } elseif ($action == 'delete') {
        mysqli_query($conn, "DELETE FROM skill_requests WHERE id=$id");
        $msg = "Request deleted!";
    } elseif ($action == 'assign') {
        $mentor_id = (int)$_GET['mentor_id'];
        mysqli_query($conn, "UPDATE skill_requests SET mentor_skill_id=$mentor_id WHERE id=$id");
        $msg = "Mentor assigned successfully!";
    }
}

// 3. Fetch Data for Requests
$q = mysqli_query($conn, "
    SELECT r.*, 
           l.first_name as learner_first, l.last_name as learner_last,
           m.first_name as mentor_first, m.last_name as mentor_last,
           ms.skill_name as mentor_skill
    FROM skill_requests r
    JOIN users l ON l.id = r.learner_id
    LEFT JOIN mentor_skills ms ON ms.id = r.mentor_skill_id
    LEFT JOIN users m ON m.id = ms.mentor_id
    ORDER BY r.id DESC
");
$requests = mysqli_fetch_all($q, MYSQLI_ASSOC);

// 4. Fetch Available Mentors for Assignment
$qMentors = mysqli_query($conn, "
    SELECT ms.id, ms.skill_name, u.first_name, u.last_name 
    FROM mentor_skills ms 
    JOIN users u ON u.id = ms.mentor_id 
    WHERE u.is_active=1
");
$mentors = mysqli_fetch_all($qMentors, MYSQLI_ASSOC);

// 5. Statistics
$qStats = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(status='Pending') as pending,
        SUM(status='Accepted') as accepted,
        SUM(status='Rejected') as rejected
    FROM skill_requests
");
$stats = mysqli_fetch_assoc($qStats);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests | Admin Control</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --sidebar-bg: #1e293b; --main-bg: #f8fafc; --accent: #4f46e5; }
        body { background-color: var(--main-bg); font-family: 'Inter', sans-serif; }
        
        /* Sidebar - Exact match with admin.php */
        .sidebar { width: 260px; height: 100vh; background: var(--sidebar-bg); color: white; position: fixed; padding: 20px; transition: 0.3s; }
        .sidebar h4 { font-weight: 800; letter-spacing: -1px; margin-bottom: 30px; color: #6366f1; }
        .sidebar a { color: #94a3b8; text-decoration: none; padding: 12px 15px; display: block; border-radius: 8px; margin-bottom: 8px; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: white; }
        .sidebar i { margin-right: 10px; width: 20px; }

        .main-content { margin-left: 260px; padding: 40px; }
        .request-card { background: white; border-radius: 16px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .stat-card { border: none; border-radius: 12px; transition: 0.3s; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
        .badge-status { padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 11px; }
        .assign-form-box { background: #f1f5f9; padding: 15px; border-radius: 8px; display: none; margin-top: 10px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h4 class="text-primary mb-4">SkillSwap Admin</h4>
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
    <a href="logout.php" class="text-danger mt-5"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
<div class="main-content">
    <h2 class="fw-bold mb-4">Skill Swap Requests</h2>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stat-card p-3 bg-white border-start border-primary border-4">
                <small class="text-muted text-uppercase fw-bold">Total Requests</small>
                <h3 class="fw-bold m-0"><?php echo $stats['total'] ?? 0; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3 bg-white border-start border-warning border-4">
                <small class="text-muted text-uppercase fw-bold">Pending</small>
                <h3 class="fw-bold m-0"><?php echo $stats['pending'] ?? 0; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3 bg-white border-start border-success border-4">
                <small class="text-muted text-uppercase fw-bold">Accepted</small>
                <h3 class="fw-bold m-0"><?php echo $stats['accepted'] ?? 0; ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card p-3 bg-white border-start border-danger border-4">
                <small class="text-muted text-uppercase fw-bold">Rejected</small>
                <h3 class="fw-bold m-0"><?php echo $stats['rejected'] ?? 0; ?></h3>
            </div>
        </div>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-success border-0 shadow-sm mb-4">
            <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <div class="card request-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Learner</th>
                        <th>Skill Required</th>
                        <th>Assigned Mentor</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($requests as $req): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($req['learner_first'] . ' ' . $req['learner_last']); ?></div>
                            <small class="text-muted"><?php echo date('d M, Y', strtotime($req['requested_at'])); ?></small>
                        </td>
                        <td>
                            <div class="fw-bold text-primary"><?php echo htmlspecialchars($req['skill_name']); ?></div>
                            <?php if(!empty($req['description'])): ?>
                                <small class="text-muted d-block" style="max-width: 200px;"><?php echo htmlspecialchars(substr($req['description'], 0, 50)); ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if(!empty($req['mentor_first'])): ?>
                                <div><i class="fas fa-user-check text-success me-1"></i> <?php echo htmlspecialchars($req['mentor_first'] . ' ' . $req['mentor_last']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($req['mentor_skill']); ?></small>
                            <?php else: ?>
                                <span class="text-muted"><i class="fas fa-user-clock me-1"></i> Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                $statusClass = [
                                    'Pending' => 'bg-warning text-dark',
                                    'Accepted' => 'bg-success text-white',
                                    'Rejected' => 'bg-danger text-white'
                                ][$req['status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge-status badge <?php echo $statusClass; ?>">
                                <?php echo strtoupper($req['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <?php if($req['status'] == 'Pending'): ?>
                                    <a href="?action=approve&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-success"><i class="fas fa-check"></i></a>
                                    <a href="?action=reject&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-outline-warning"><i class="fas fa-times"></i></a>
                                <?php endif; ?>

                                <?php if(empty($req['mentor_skill_id'])): ?>
                                    <button class="btn btn-sm btn-primary" onclick="toggleAssign(<?php echo $req['id']; ?>)">
                                        <i class="fas fa-user-plus"></i> Assign
                                    </button>
                                <?php endif; ?>

                                <a href="?action=delete&id=<?php echo $req['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this request?')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            </div>

                            <div id="assign_box_<?php echo $req['id']; ?>" class="assign-form-box mt-3 shadow-sm border">
                                <form method="GET" class="d-flex gap-2">
                                    <input type="hidden" name="action" value="assign">
                                    <input type="hidden" name="id" value="<?php echo $req['id']; ?>">
                                    <select name="mentor_id" class="form-select form-select-sm" required>
                                        <option value="">Select Mentor...</option>
                                        <?php foreach($mentors as $mentor): ?>
                                            <option value="<?php echo $mentor['id']; ?>">
                                                <?php echo htmlspecialchars($mentor['skill_name'] . ' - ' . $mentor['first_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-dark">Done</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function toggleAssign(id) {
        let el = document.getElementById('assign_box_' + id);
        el.style.display = (el.style.display === 'block') ? 'none' : 'block';
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>