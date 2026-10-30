<?php
session_start();
if(!isset($_SESSION['user']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
} // just for admin

include '../connection.php';
// helper: pick the best image path for an event (adjust path for admin folder)
function resolve_event_image(array $e){
    $img = '';
    if(!empty($e['image'])) $img = $e['image'];
    elseif(!empty($e['image_url'])) $img = $e['image_url'];
    elseif(!empty($e['img'])) $img = $e['img'];
    if($img && strpos($img, 'includes/') === 0){
        $img = '../' . $img;
    }
    return $img;
}

// load recent events
$events = [];
$res = $conn->query("SELECT * FROM events ORDER BY id DESC LIMIT 50");
if($res){ while($r = $res->fetch_assoc()) $events[] = $r; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UTB Events</title>
    <link rel="stylesheet" href="../includes/site.css">
</head>
<body>
    <div class="app">
    <?php include __DIR__ . '/../sidebar/sidebar.php'; ?>
        <main class="content-wrap">
            <div class="panel">
                <h1>Admin Dashboard</h1>
                <p>Welcome, Admin, <?php echo htmlspecialchars($_SESSION['user']); ?>!</p>

                <h2>Event Manager</h2>
                <p>Below are recent events. Click Edit to modify an event.</p>
                <?php if(empty($events)): ?>
                    <p>No events found. Use <a href="manage_events.php">Event Manager</a> to add one.</p>
                <?php else: ?>
                    <div class="grid">
                        <?php foreach($events as $e):
                            $title = $e['title'] ?? 'Untitled';
                            $img = resolve_event_image($e);
                        ?>
                        <div class="card">
                            <div class="card-media">
                                <?php if($img): ?>
                                    <img src="<?=htmlspecialchars($img)?>" alt="<?=htmlspecialchars($title)?>">
                                <?php else: ?>
                                    <img src="../includes/events/placeholder.png" alt="placeholder">
                                <?php endif; ?>
                            </div>
                            <h3><?=htmlspecialchars($title)?></h3>
                            <div style="margin-top:6px"><a class="btn" href="manage_events.php?edit=<?=urlencode($e['id'])?>">Edit</a></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- LOGOUT BUTTON REMOVED - Using sidebar only -->
            </div>
        </main>
    </div>
    <?php include __DIR__ . '/../contact.php'; ?>
</body>
</html>