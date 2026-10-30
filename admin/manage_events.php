<?php
session_start();
if(!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') != 'admin'){
    header('Location: ../index.php'); exit();
}
include __DIR__ . '/../connection.php';

// ensure events table exists (simple schema)
$conn->query("CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    event_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Ensure there is an `image` column to store uploaded event image path (backfill safe default '')
$colCheck = $conn->query("SHOW COLUMNS FROM events LIKE 'image'");
if(!$colCheck || $colCheck->num_rows === 0){
    @$conn->query("ALTER TABLE events ADD COLUMN image VARCHAR(255) DEFAULT ''");
}

// Handle admin deletion of a comment (POST)
if($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'delete_comment' && !empty($_POST['comment_id'])){
    $cid = intval($_POST['comment_id']);
    if($cid > 0){
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? LIMIT 1");
        if($stmt){ $stmt->bind_param('i', $cid); $stmt->execute(); }
    }
    // Redirect back to manage_events, returning to edit view if provided
    $ret = 'manage_events.php';
    if(!empty($_POST['return_to_event']) && is_numeric($_POST['return_to_event'])) $ret .= '?edit=' . intval($_POST['return_to_event']);
    header('Location: ' . $ret); exit();
}

$message = '';

// Consolidated POST handler: add, update are handled here to avoid duplicated upload logic
if($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])){
    // ADD
    if($_POST['action'] === 'add'){
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $date = trim($_POST['event_date'] ?? null);
        if($title !== ''){
            $stmt = $conn->prepare("INSERT INTO events (title, description, event_date) VALUES (?, ?, ?)");
            if($stmt){
                $stmt->bind_param('sss', $title, $desc, $date);
                if($stmt->execute()){
                    $lastId = $conn->insert_id;
                    $message = 'Event added.';
                    // handle optional image upload
                    if($lastId && !empty($_FILES['event_image']['tmp_name'])){
                        $uploadDir = __DIR__ . '/../includes/events/';
                        if(!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                        $origName = basename($_FILES['event_image']['name']);
                        $safe = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $origName);
                        $newName = time() . '_' . $safe;
                        $dest = $uploadDir . $newName;
                        if(@move_uploaded_file($_FILES['event_image']['tmp_name'], $dest)){
                            $relPath = 'includes/events/' . $newName;
                            // store image path using prepared statement
                            $uStmt = $conn->prepare("UPDATE events SET image = ? WHERE id = ? LIMIT 1");
                            if($uStmt){
                                $uStmt->bind_param('si', $relPath, $lastId);
                                $uStmt->execute();
                                $uStmt->close();
                            }
                            $message .= ' Image uploaded.';
                        } else {
                            $message .= ' Image upload failed (check permissions).';
                        }
                    }
                } else {
                    $message = 'DB error: ' . $stmt->error;
                }
                $stmt->close();
            } else {
                $message = 'DB prepare failed: ' . $conn->error;
            }
        } else {
            $message = 'Title is required.';
        }
    }

    // UPDATE
    if($_POST['action'] === 'update' && isset($_POST['id'])){
        $id = intval($_POST['id']);
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $date = trim($_POST['event_date'] ?? null);
        if($title !== ''){
            $uStmt = $conn->prepare("UPDATE events SET title = ?, description = ?, event_date = ? WHERE id = ? LIMIT 1");
            if($uStmt){
                $uStmt->bind_param('sssi', $title, $desc, $date, $id);
                if($uStmt->execute()){
                    $message = 'Event updated.';
                    // optional image replace
                    if($id && !empty($_FILES['event_image']['tmp_name'])){
                        $uploadDir = __DIR__ . '/../includes/events/';
                        if(!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);
                        $origName = basename($_FILES['event_image']['name']);
                        $safe = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $origName);
                        $newName = time() . '_' . $safe;
                        $dest = $uploadDir . $newName;
                        if(@move_uploaded_file($_FILES['event_image']['tmp_name'], $dest)){
                            $relPath = 'includes/events/' . $newName;
                            $imgUpdate = $conn->prepare("UPDATE events SET image = ? WHERE id = ? LIMIT 1");
                            if($imgUpdate){
                                $imgUpdate->bind_param('si', $relPath, $id);
                                $imgUpdate->execute();
                                $imgUpdate->close();
                            }
                            $message .= ' Image uploaded.';
                        } else {
                            $message .= ' Image upload failed (check permissions).';
                        }
                    }
                } else {
                    $message = 'DB error: ' . $uStmt->error;
                }
                $uStmt->close();
            } else {
                $message = 'DB prepare failed: ' . $conn->error;
            }
        } else {
            $message = 'Title is required.';
        }
    }
}

// Handle delete
if(isset($_GET['delete']) && is_numeric($_GET['delete'])){
    $id = intval($_GET['delete']);
    $del = $conn->prepare("DELETE FROM events WHERE id = ? LIMIT 1");
    if($del){
        $del->bind_param('i', $id);
        $del->execute();
        $del->close();
    }
    header('Location: manage_events.php'); exit();
}

$events = [];
$res = $conn->query("SELECT * FROM events ORDER BY id DESC LIMIT 200");
if($res){ while($r = $res->fetch_assoc()) $events[] = $r; }

// If edit requested, load event
$editEvent = null;
if(isset($_GET['edit']) && is_numeric($_GET['edit'])){
    $eid = intval($_GET['edit']);
    $r = $conn->query("SELECT * FROM events WHERE id={$eid} LIMIT 1");
    if($r && $r->num_rows) $editEvent = $r->fetch_assoc();
}

// If editing an event, load its feedback/comments for admin view
$editComments = [];
if($editEvent){
    $eid = intval($editEvent['id']);
    $cstmt = $conn->prepare("SELECT c.*, u.fullname FROM comments c LEFT JOIN users u ON u.id=c.user_id WHERE c.event_id = ? ORDER BY c.created_at DESC");
    if($cstmt){
        $cstmt->bind_param('i', $eid);
        $cstmt->execute();
        $cres = $cstmt->get_result();
        if($cres){ while($row = $cres->fetch_assoc()) $editComments[] = $row; }
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Events - Admin</title>
    <link rel="stylesheet" href="../includes/site.css">
    <link rel="stylesheet" href="../includes/admin.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/../sidebar/sidebar.php'; ?>
    <main class="content-wrap">
        <div class="panel">
            <h1>Event Manager</h1>
            
            <?php if($message): ?>
                <div class="message <?= strpos($message, 'Error') !== false ? 'error' : 'success' ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <div class="event-form-container">
                <form method="post" enctype="multipart/form-data">
                    <?php if($editEvent): ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($editEvent['id']) ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="add">
                    <?php endif; ?>
                    
                    <!-- First Row: Title, Date, Image -->
                    <div class="form-row">
                        <div class="form-group" style="flex: 2;">
                            <label>Title</label>
                            <input name="title" required value="<?= htmlspecialchars($editEvent['title'] ?? '') ?>" placeholder="Event title">
                        </div>
                        
                        <div class="form-group">
                            <label>Date (YYYY-MM-DD)</label>
                            <input name="event_date" value="<?= htmlspecialchars($editEvent['event_date'] ?? '') ?>" placeholder="2025-10-21">
                        </div>
                        
                        <div class="form-group">
                            <label>Event Image</label>
                            <input type="file" name="event_image" accept="image/*">
                        </div>
                    </div>
                    
                    <!-- Second Row: Description -->
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" placeholder="Event description"><?= htmlspecialchars($editEvent['description'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- Third Row: Button -->
                    <div class="button-group">
                        <button class="btn-add" type="submit">
                            <?= $editEvent ? 'Update Event' : 'Add Event' ?>
                        </button>
                        
                        <?php if($editEvent): ?>
                            <a class="btn-cancel" href="manage_events.php">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <?php if($editEvent): ?>
                <h2 style="margin-top:18px">Feedback for "<?= htmlspecialchars($editEvent['title']) ?>"</h2>
                <?php if(empty($editComments)): ?>
                    <p style="color:#666">No feedback for this event yet.</p>
                <?php else: ?>
                    <table style="margin-top:8px;width:100%">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>When</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($editComments as $ec): ?>
                                <tr>
                                    <td><?= htmlspecialchars($ec['fullname'] ?? $ec['email'] ?? 'Anonymous') ?></td>
                                    <td style="color:#f5b301"><?php $rr = intval($ec['rating']); for($i=1;$i<=5;$i++): ?><?php if($i <= $rr): ?><span>★</span><?php else: ?><span style="color:#ddd">☆</span><?php endif; ?><?php endfor; ?></td>
                                    <td style="max-width:480px;white-space:pre-wrap"><?= nl2br(htmlspecialchars($ec['comment'])) ?></td>
                                    <td><?= htmlspecialchars($ec['created_at']) ?></td>
                                    <td>
                                        <form method="post" style="display:inline" onsubmit="return confirm('Delete this feedback?');">
                                            <input type="hidden" name="action" value="delete_comment">
                                            <input type="hidden" name="comment_id" value="<?= intval($ec['id']) ?>">
                                            <input type="hidden" name="return_to_event" value="<?= intval($editEvent['id']) ?>">
                                            <button type="submit" class="btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>

            <h2>Existing Events</h2>
            <?php if(empty($events)): ?>
                <p>No events yet.</p>
            <?php else: ?>
                <table>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach($events as $ev): ?>
                        <tr>
                            <td><?= htmlspecialchars($ev['id']) ?></td>
                            <td><?= htmlspecialchars($ev['title']) ?></td>
                            <td><?= htmlspecialchars($ev['event_date']) ?></td>
                            <td>
                                <a class="btn-edit" href="?edit=<?= urlencode($ev['id']) ?>">Edit</a>
                                <a class="btn-delete" href="?delete=<?= urlencode($ev['id']) ?>" onclick="return confirm('Delete this event?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </main>
</div>
</body>
</html>
<?php include __DIR__ . '/../contact.php'; ?>