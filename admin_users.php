<?php
session_start();
include "db.php";

// 1. Admin Security Check
if (!isset($_SESSION['uid']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$uid = (int)$_SESSION['uid'];

// 2. Handle Actions (Delete, Status Change, Role Change)
$msg = "";
if (isset($_GET['action'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'approve') {
        // Jodi table-e is_active thake tobe thik ache, nahole 'status' column use hobe
        mysqli_query($conn, "UPDATE users SET is_active=1 WHERE id=$id");
        $msg = "User approved successfully!";
    } elseif ($action == 'deactivate') {
        mysqli_query($conn, "UPDATE users SET is_active=0 WHERE id=$id");
        $msg = "User deactivated!";
    } elseif ($action == 'delete') {
        mysqli_query($conn, "DELETE FROM users WHERE id=$id AND role!='admin'");
        $msg = "User deleted successfully!";
    } elseif ($action == 'make_mentor') {
        mysqli_query($conn, "UPDATE users SET role='mentor' WHERE id=$id");
        $msg = "User promoted to Mentor!";
    } elseif ($action == 'make_learner') {
        mysqli_query($conn, "UPDATE users SET role='learner' WHERE id=$id");
        $msg = "User role changed to Learner!";
    }
}

// 3. Fetch All Users
$q = mysqli_query($conn, "SELECT * FROM users WHERE role != 'admin' ORDER BY id DESC");
$users = mysqli_fetch_all($q, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users | Admin Control</title>
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
        .user-card { background: white; border-radius: 16px; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .btn-action { padding: 5px 10px; font-size: 13px; border-radius: 6px; text-decoration: none; margin-right: 5px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h4>SkillSwap Admin</h4>
    <a href="admin.php"><i class="fas fa-chart-line"></i> Dashboard</a>
    <a href="admin_users.php" class="active"><i class="fas fa-users"></i> Manage Users</a>
    <a href="admin_manage_skills.php"><i class="fas fa-book-open"></i> Manage All Skills</a>
    <a href="admin_requests.php"><i class="fas fa-exchange-alt"></i> Skill Requests</a>
    <a href="admin_skills.php"><i class="fas fa-flag"></i> Skills Audit (Reports)</a>
    <hr style="border-color: #334155;">
    <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold">User Management</h2>
            <p class="text-muted">Monitor, Verify, and Control all platform members.</p>
        </div>
        <div class="search-box">
            <input type="text" id="userInput" class="form-control" placeholder="Search by name or email...">
        </div>
    </div>

    <?php if($msg): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
            <i class="fas fa-check-circle me-2"></i> <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card user-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="usersTable">
                <thead class="table-light">
                    <tr>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $user): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></div>
                            <small class="text-muted">ID: #<?php echo $user['id']; ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                <?php echo strtoupper($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if(isset($user['is_active']) && $user['is_active']): ?>
                                <span class="status-badge bg-success text-white">Active</span>
                            <?php else: ?>
                                <span class="status-badge bg-warning text-dark">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if(isset($user['is_active']) && $user['is_active']): ?>
                                <a href="?action=deactivate&id=<?php echo $user['id']; ?>" class="btn-action btn btn-outline-warning">Deactivate</a>
                            <?php else: ?>
                                <a href="?action=approve&id=<?php echo $user['id']; ?>" class="btn-action btn btn-outline-success">Activate</a>
                            <?php endif; ?>

                            <?php if($user['role'] === 'learner'): ?>
                                <a href="?action=make_mentor&id=<?php echo $user['id']; ?>" class="btn-action btn btn-outline-primary">Make Mentor</a>
                            <?php else: ?>
                                <a href="?action=make_learner&id=<?php echo $user['id']; ?>" class="btn-action btn btn-outline-secondary">Make Learner</a>
                            <?php endif; ?>

                            <a href="?action=delete&id=<?php echo $user['id']; ?>" 
                               class="btn-action btn btn-outline-danger" 
                               onclick="return confirm('Permanently delete this user?')">
                               <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Real-time Search Logic
    document.getElementById('userInput').addEventListener('input', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#usersTable tbody tr');
        
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>