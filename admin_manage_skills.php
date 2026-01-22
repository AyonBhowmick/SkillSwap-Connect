<?php
session_start();
include "db.php";

// 1. Admin Security
if (!isset($_SESSION['uid']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

$successMsg = "";
$errorMsg = "";

// 2. Action: Delete Skill
if (isset($_GET['delete_id'])) {
    $delId = (int)$_GET['delete_id'];
    $delQuery = "DELETE FROM mentor_skills WHERE id = $delId";
    if (mysqli_query($conn, $delQuery)) {
        $successMsg = "Skill deleted successfully!";
    } else {
        $errorMsg = "Error deleting skill.";
    }
}

// 3. Action: Approve/Reject
if (isset($_GET['action'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] == 'approve') {
        mysqli_query($conn, "UPDATE mentor_skills SET is_approved=1 WHERE id=$id");
        $successMsg = "Skill approved successfully!";
    } elseif ($_GET['action'] == 'reject') {
        mysqli_query($conn, "UPDATE mentor_skills SET is_approved=0 WHERE id=$id");
        $successMsg = "Skill rejected!";
    }
}

// 4. Data Fetching (ms.level error handle korar jonno query)
$sql = "SELECT ms.*, u.first_name, u.last_name 
        FROM mentor_skills ms 
        INNER JOIN users u ON ms.mentor_id = u.id 
        ORDER BY ms.id DESC";
$allSkills = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Skills | Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --sidebar-bg: #1e293b; --main-bg: #f8fafc; }
        body { background-color: var(--main-bg); font-family: 'Inter', sans-serif; display: flex; }
        
        .sidebar { width: 260px; height: 100vh; background: var(--sidebar-bg); color: white; position: fixed; padding: 20px; z-index: 100; }
        .sidebar h4 { color: #6366f1; font-weight: 800; margin-bottom: 30px; }
        .sidebar a { color: #94a3b8; text-decoration: none; padding: 12px 15px; display: block; border-radius: 8px; margin-bottom: 8px; transition: 0.2s; }
        .sidebar a:hover, .sidebar a.active { background: #334155; color: white; }
        
        .main-content { margin-left: 260px; padding: 40px; width: 100%; }
        .skill-card { background: white; border-radius: 16px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
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
    <a href="logout.php" class="text-danger mt-5"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Skill Management</h2>
        <span class="badge bg-primary px-3 py-2">
            Total: <?php echo ($allSkills) ? mysqli_num_rows($allSkills) : 0; ?>
        </span>
    </div>

    <?php if($successMsg): ?> <div class="alert alert-success border-0 shadow-sm"><?php echo $successMsg; ?></div> <?php endif; ?>
    <?php if($errorMsg): ?> <div class="alert alert-danger border-0 shadow-sm"><?php echo $errorMsg; ?></div> <?php endif; ?>

    <div class="card skill-card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Skill Name</th>
                        <th>Mentor</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($allSkills): ?>
                        <?php while($row = mysqli_fetch_assoc($allSkills)): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($row['skill_name']); ?></strong><br>
                                <small class="text-muted">Level: <?php echo htmlspecialchars($row['level'] ?? 'N/A'); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                            <td>
                                <?php if(isset($row['is_approved']) && $row['is_approved']): ?>
                                    <span class="badge bg-success text-white">Verified</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group">
                                    <a href="?action=approve&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-success" title="Approve"><i class="fas fa-check"></i></a>
                                    <a href="?action=reject&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-warning" title="Reject"><i class="fas fa-times"></i></a>
                                    <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this skill?')" title="Delete"><i class="fas fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No skills found. Run the SQL query to fix table columns.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>