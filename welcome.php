<?php
session_start();
if(!isset($_SESSION['user']) || ($_SESSION['role'] != 'student' && $_SESSION['role'] != 'admin')) {
    header("Location: index.php");
    exit();
}
include 'connection.php';
?>
<?php
// Fetch featured events within next 7 days (defensive about column names)
$featured = [];
if(isset($conn) && $conn instanceof mysqli){
    // detect date column in events table
    $dbRow = $conn->query("SELECT DATABASE() AS db");
    $dbName = $dbRow ? $dbRow->fetch_object()->db : '';
    $dateCol = null;
    if($dbName){
        // mysqli doesn't have quote(); use real_escape_string and manual quoting
        $dbNameEsc = $conn->real_escape_string($dbName);
        $colQ = $conn->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema='".$dbNameEsc."' AND table_name='events' AND COLUMN_NAME IN ('event_date','start_date','date','start') LIMIT 1");
        if($colQ && $colQ->num_rows>0){
            $dateCol = $colQ->fetch_assoc()['COLUMN_NAME'];
        }
    }

    if($dateCol){
        $today = date('Y-m-d');
        $end = date('Y-m-d', strtotime('+7 days'));
        $sql = "SELECT * FROM events WHERE {$dateCol} BETWEEN '{$today}' AND '{$end}' ORDER BY {$dateCol} ASC LIMIT 6";
        $res = $conn->query($sql);
        if($res && $res!==false){
            while($r = $res->fetch_assoc()) $featured[] = $r;
        }
    } else {
        // fallback: pick most recent events
        $res = $conn->query("SELECT * FROM events ORDER BY id DESC LIMIT 6");
        if($res) while($r = $res->fetch_assoc()) $featured[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover UTB Events</title>
    <link rel="stylesheet" href="includes/site.css">
</head>
<body>
    <div class="app">
    <?php include __DIR__ . '/sidebar/sidebar.php'; ?>
        <main class="content-wrap">
            <header class="top">
                <div class="breadcrumb">Home &gt; Featured Events</div>
                <div class="title">DISCOVER UTB EVENTS</div>
            </header>

            <div class="panel">
                <div class="upcoming-title">FEATURED EVENTS</div>

                <div class="cards">
                    <?php if(count($featured)===0): ?>
                        <p style="text-align:center;width:100%">No featured events in the next 7 days.</p>
                    <?php else: ?>
                        <?php foreach($featured as $e):
                            $title = $e['title'] ?? $e['name'] ?? ($e['event_name'] ?? 'Untitled Event');
                            $desc = $e['description'] ?? $e['details'] ?? '';
                            $img = $e['image_url'] ?? $e['image'] ?? $e['img'] ?? '';
                            $id = $e['id'] ?? '';
                        ?>
                        <div class="card">
                            <?php if(!empty($id)): ?><a href="events.php?event_id=<?=urlencode($id)?>"><?php endif; ?>
                                <div class="card-title"><?=htmlspecialchars($title)?></div>
                                <div class="card-media">
                                    <?php if(!empty($img)): ?><img src="<?=htmlspecialchars($img)?>" alt="<?=htmlspecialchars($title)?>"><?php else: ?><img src="includes/events/inno-faith.jpg" alt="Event"><?php endif; ?>
                                </div>
                                <div class="card-desc"><?=htmlspecialchars($desc)?></div>
                            <?php if(!empty($id)): ?></a><?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="actions">
                    <a class="btn" href="events.php?filter=upcoming">View Upcoming Events</a>
                </div>
            </div>
        </main>
    </div>
    <?php include __DIR__ . '/contact.php'; ?>
</body>
</html>