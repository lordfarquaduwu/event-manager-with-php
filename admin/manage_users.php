<?php
session_start();
if(!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') != 'admin'){
    header('Location: ../index.php'); 
    exit();
}
include __DIR__ . '/../connection.php';

$message = '';

// Handle role change only
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_role'){
    $user_id = intval($_POST['user_id']);
    $new_role = $conn->real_escape_string($_POST['role']);
    
    // Prevent admin from removing their own admin rights
    if($user_id && $new_role && ($_SESSION['user'] != $user_id || $new_role === 'admin')){
        $sql = "UPDATE users SET role='{$new_role}' WHERE id={$user_id} LIMIT 1";
        if($conn->query($sql)){
            $message = 'User role updated successfully.';
        } else {
            $message = 'Error updating role: ' . $conn->error;
        }
    } else {
        $message = 'Cannot change your own admin role.';
    }
}

// Handle user deletion (admin only)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user'){
    $user_id = intval($_POST['user_id']);
    $message = '';
    // Prevent deleting current admin
    $protect = false;
    if($user_id){
        $check = $conn->prepare("SELECT email FROM users WHERE id = ? LIMIT 1");
        if($check){
            $check->bind_param('i', $user_id);
            $check->execute();
            $cres = $check->get_result();
            if($cres && $cres->num_rows){
                $row = $cres->fetch_assoc();
                if(($row['email'] ?? '') === ($_SESSION['user'] ?? '')) $protect = true;
            }
            $check->close();
        }
    }

    if($user_id && !$protect){
        $del = $conn->prepare("DELETE FROM users WHERE id = ? LIMIT 1");
        if($del){
            $del->bind_param('i', $user_id);
            if($del->execute()){
                $message = 'User deleted successfully.';
            } else {
                $message = 'Error deleting user: ' . $del->error;
            }
            $del->close();
        } else {
            $message = 'DB error: ' . $conn->error;
        }
    } else {
        $message = 'Cannot delete the currently logged-in admin.';
    }
}

// Fetch all users
$users = [];
$res = $conn->query(
    "SELECT 
        u.id,
        u.fullname,
        u.email,
        u.role,
        u.dob,
        u.created_at,
        COUNT(r.id) as event_count,
        GROUP_CONCAT(DISTINCT CONCAT_WS('::', IFNULL(e.id,0), IFNULL(e.title,''), IFNULL(e.event_date,'')) SEPARATOR '||') AS registrations
    FROM users u
    LEFT JOIN event_registrations r ON u.email = r.email OR u.id = r.user_id
    LEFT JOIN events e ON r.event_id = e.id
    GROUP BY u.id, u.fullname, u.email, u.role, u.dob, u.created_at
    ORDER BY u.created_at DESC"
);

if($res) { 
    while($r = $res->fetch_assoc()) {
        // Ensure registrations is always a string
        if(!isset($r['registrations'])) $r['registrations'] = '';
        $users[] = $r;
    }
}

// Get user statistics
$stats = [
    'total_users' => count($users),
    'total_students' => 0,
    'total_admins' => 0,
    'active_users' => 0
];

foreach($users as $user) {
    if($user['role'] === 'student') $stats['total_students']++;
    if($user['role'] === 'admin') $stats['total_admins']++;
    if($user['event_count'] > 0) $stats['active_users']++;
}
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Users - Admin</title>
    <link rel="stylesheet" href="../includes/site.css">
    <link rel="stylesheet" href="../includes/admin.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/../sidebar/sidebar.php'; ?>
    <main class="content-wrap">
        <div class="panel">
            <h1>User Management</h1>
            
            <?php if($message): ?>
                <div class="message <?= strpos($message, 'Error') !== false ? 'error' : 'success' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <!-- User Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_users'] ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_students'] ?></div>
                    <div class="stat-label">Students</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_admins'] ?></div>
                    <div class="stat-label">Admins</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['active_users'] ?></div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>

            <!-- Users Table -->
            <h2>All Users</h2>
            <?php if(empty($users)): ?>
                <div style="text-align: center; padding: 40px; color: #666;">
                    No users found in the system.
                </div>
            <?php else: ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Events</th>
                            <th>Joined</th>
                            <th>Change Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($user['fullname']) ?></strong>
                                <?php if($user['email'] === $_SESSION['user']): ?>
                                    <br><small style="color: #dc3545;">(You)</small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span class="role-badge role-<?= $user['role'] ?>">
                                    <?= strtoupper($user['role']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="event-count"><?= $user['event_count'] ?> events</span>
                                <?php if(!empty($user['registrations'])): ?>
                                    <?php
                                        $items = explode('||', $user['registrations']);
                                        $preview = [];
                                        foreach($items as $it){
                                            if(trim($it)==='') continue;
                                            $parts = explode('::', $it);
                                            $eid = intval($parts[0] ?? 0);
                                            $etitle = $parts[1] ?? '';
                                            if($etitle !== '') $preview[] = $etitle;
                                        }
                                    ?>
                                    <?php if(count($preview) > 0): ?>
                                        <div style="margin-top:6px;font-size:0.9rem;color:#444">
                                            <?= htmlspecialchars(implode(', ', array_slice($preview,0,2))) ?><?php if(count($preview) > 2) echo '…'; ?>
                                            <br>
                                            <a href="#" onclick="toggleRegs(<?= $user['id'] ?>);return false;">View registrations</a>
                                        </div>
                                        <div id="regs-<?= $user['id'] ?>" style="display:none;margin-top:6px;">
                                            <ul style="margin:6px 0 0 18px;padding:0;color:#333;">
                                            <?php foreach($items as $it): if(trim($it)==='') continue; $p = explode('::',$it); $eid=intval($p[0]); $et=htmlspecialchars($p[1] ?? ''); $ed=htmlspecialchars($p[2] ?? ''); ?>
                                                <li><a href="<?= htmlspecialchars('../events.php?event_id=' . $eid) ?>" target="_blank"><?= $et ?></a> <?= $ed ? '<small style="color:#666">('.$ed.')</small>' : '' ?></li>
                                            <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= date('M j, Y', strtotime($user['created_at'])) ?>
                            </td>
                            <td>
                                <div style="display:flex;gap:8px;align-items:center">
                                <form method="POST" onsubmit="return confirm('Change role for <?= htmlspecialchars($user['fullname']) ?>?')">
                                    <input type="hidden" name="action" value="change_role">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <select name="role" onchange="this.form.submit()" class="role-select" <?= $user['email'] === $_SESSION['user'] ? 'disabled' : '' ?>>
                                        <option value="student" <?= $user['role'] === 'student' ? 'selected' : '' ?>>Student</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                </form>

                                <form method="POST" onsubmit="return confirm('Delete user <?= htmlspecialchars($user['fullname']) ?>?');">
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn-delete" <?= $user['email'] === $_SESSION['user'] ? 'disabled' : '' ?>>Delete</button>
                                </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>
            <?php include __DIR__ . '/../contact.php'; ?>
                <!-- site.js provides toggleRegs -->
        </body>
        </html>