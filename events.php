<?php
session_start();
if(!isset($_SESSION['user'])){
    header('Location: index.php'); exit();
}
include 'connection.php';
// Ensure comments/feedback table exists
$conn->query("CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    rating TINYINT DEFAULT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle AJAX/simple POST feedback, registration/unregistration requests before rendering the page
// Feedback handler: expects action=leave_feedback, event_id, rating (1-5), comment
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'leave_feedback'){
    if(session_status() !== PHP_SESSION_ACTIVE) session_start();
    header('Content-Type: application/json');
    $resp = ['success' => false];
    if(empty($_SESSION['user'])){
        echo json_encode(['success'=>false,'message'=>'Not authenticated']); exit();
    }
    $event_id = intval($_POST['event_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if($event_id <= 0 || $rating < 1 || $rating > 5){
        echo json_encode(['success'=>false,'message'=>'Invalid input']); exit();
    }

    // verify user registered for this event
    $user_email = $_SESSION['user'];
    $chk = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND (email = ? OR user_id = (SELECT id FROM users WHERE email=? LIMIT 1)) LIMIT 1");
    if($chk){
        $chk->bind_param('iss', $event_id, $user_email, $user_email);
        $chk->execute();
        $cres = $chk->get_result();
        if(!$cres || $cres->num_rows === 0){
            echo json_encode(['success'=>false,'message'=>'You must be registered to leave feedback']); exit();
        }
    }

    // get optional user id
    $uid = null;
    $uRes = $conn->query("SELECT id FROM users WHERE email='" . $conn->real_escape_string($user_email) . "' LIMIT 1");
    if($uRes && $uRes->num_rows) $uid = intval($uRes->fetch_assoc()['id']);

    $stmt = $conn->prepare("INSERT INTO comments (event_id, user_id, email, rating, comment) VALUES (?, ?, ?, ?, ?)");
    if($stmt){
        $emailEsc = $conn->real_escape_string($user_email);
        $stmt->bind_param('iisis', $event_id, $uid, $emailEsc, $rating, $comment);
        if($stmt->execute()){
            echo json_encode(['success'=>true,'message'=>'Feedback saved']); exit();
        } else {
            echo json_encode(['success'=>false,'message'=>'DB error: ' . $conn->error]); exit();
        }
    } else {
        echo json_encode(['success'=>false,'message'=>'DB prepare failed']); exit();
    }
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && in_array($_POST['action'], ['register','unregister'])){
    // ensure session started (do not call session_start() unconditionally to avoid warnings)
    if(session_status() !== PHP_SESSION_ACTIVE) session_start();
    // debug log helper (temporary) - append-only
    $debug_log = __DIR__ . '/includes/registration_debug.log';
    $dl = function($msg) use ($debug_log){
        @file_put_contents($debug_log, date('c') . ' | ' . $msg . "\n", FILE_APPEND);
    };
    $dl('--- REGISTER POST START ---');
    $dl('POST: ' . json_encode($_POST));
    $dl('SESSION: ' . json_encode(isset($_SESSION) ? $_SESSION : null));
    if(empty($_SESSION['user'])){
        header('Content-Type: application/json');
        $dl('Not authenticated');
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit();
    }

    $action = $_POST['action'];
    $event_id = intval($_POST['event_id'] ?? 0);
    $user_email = $_SESSION['user'];

    // If user wants to unregister, perform deletion and exit
    if($action === 'unregister'){
        $dl('UNREGISTER attempt for event ' . $event_id . ' user ' . $user_email);
        $del = $conn->prepare("DELETE FROM event_registrations WHERE event_id = ? AND email = ? LIMIT 1");
        if(!$del){
            header('Content-Type: application/json');
            $dl('UNREGISTER prepare failed: ' . $conn->error);
            echo json_encode(['success' => false, 'message' => 'DB error: '. $conn->error]);
            exit();
        }
        $del->bind_param('is', $event_id, $user_email);
        $del->execute();
        $affected = $del->affected_rows;
        header('Content-Type: application/json');
        if($affected > 0){
            $dl('UNREGISTER OK for event ' . $event_id . ' user ' . $user_email);
            echo json_encode(['success' => true, 'registered' => false]);
        } else {
            $dl('UNREGISTER no rows affected for event ' . $event_id . ' user ' . $user_email);
            echo json_encode(['success' => false, 'message' => 'Not registered']);
        }
        exit();
    }


    // fetch user biodata from users table
    $user_stmt = $conn->prepare("SELECT id, fullname, email, dob FROM users WHERE email = ? LIMIT 1");
    if(!$user_stmt){
        header('Content-Type: application/json');
        $dl('USER_STMT prepare failed: ' . $conn->error);
        echo json_encode(['success' => false, 'message' => 'DB error: '. $conn->error]);
        exit();
    }
    $user_stmt->bind_param('s', $user_email);
    $user_stmt->execute();
    $user_res = $user_stmt->get_result();
    $user = $user_res && $user_res->num_rows ? $user_res->fetch_assoc() : null;

    // Check if the event has already passed
    $event_check = $conn->prepare("SELECT event_date FROM events WHERE id = ? AND event_date > CURDATE() LIMIT 1");
    if($event_check){
        $event_check->bind_param('i', $event_id);
        $event_check->execute();
        $event_res = $event_check->get_result();
        if(!$event_res || $event_res->num_rows === 0){
            header('Content-Type: application/json');
            $dl('Event ' . $event_id . ' has already passed or does not exist');
            echo json_encode(['success' => false, 'message' => 'Cannot register for past events']);
            exit();
        }
    }

    // check if already registered
    $chk_stmt = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND email = ? LIMIT 1");
    if($chk_stmt){
        $chk_stmt->bind_param('is', $event_id, $user_email);
        $chk_stmt->execute();
        $chk_res = $chk_stmt->get_result();
        if($chk_res && $chk_res->num_rows > 0){
            header('Content-Type: application/json');
            $dl('Already registered for event ' . $event_id . ' by ' . $user_email);
            echo json_encode(['success' => true, 'registered' => true, 'message' => 'Already registered']);
            exit();
        }
    }

    // insert registration using available biodata
    $insert_stmt = $conn->prepare("INSERT INTO event_registrations (event_id, user_id, fullname, email, dob) VALUES (?, ?, ?, ?, ?)");
    if(!$insert_stmt){
        header('Content-Type: application/json');
        $dl('INSERT prepare failed: ' . $conn->error);
        echo json_encode(['success' => false, 'message' => 'DB error: '. $conn->error]);
        exit();
    }
    $uid = $user['id'] ?? null;
    $fullname = $user['fullname'] ?? null;
    $email = $user['email'] ?? $user_email;
    $dob = $user['dob'] ?? null;
    $insert_stmt->bind_param('issss', $event_id, $uid, $fullname, $email, $dob);
    $ok = $insert_stmt->execute();
    header('Content-Type: application/json');
    if($ok){
        $dl('INSERT OK for event ' . $event_id . ' user ' . $email);
        echo json_encode(['success' => true, 'registered' => true]);
    } else {
        $dl('INSERT failed: ' . $conn->error);
        echo json_encode(['success' => false, 'message' => 'Insert failed: '. $conn->error]);
    }
    exit();
}
// Fetch events if possible and support filters
$events = [];
$sql_error = '';
$dateCol = null;
if(isset($conn) && $conn instanceof mysqli){
    // detect date column
    $dbRow = $conn->query("SELECT DATABASE() AS db");
    $dbName = $dbRow ? $dbRow->fetch_object()->db : '';
    if($dbName){
        $dbNameEsc = $conn->real_escape_string($dbName);
        $colQ = $conn->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema='".$dbNameEsc."' AND table_name='events' AND COLUMN_NAME IN ('event_date','start_date','date','start') LIMIT 1");
        if($colQ && $colQ->num_rows>0) $dateCol = $colQ->fetch_assoc()['COLUMN_NAME'];
    }
    // collect available column names for safer SQL building (search/sort)
    $availableCols = [];
    $colListQ = $conn->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema='".$dbNameEsc."' AND table_name='events'");
    if($colListQ){
        while($c = $colListQ->fetch_assoc()) $availableCols[] = $c['COLUMN_NAME'];
    }
    // determine best title-like column (for A→Z sort)
    $titleCol = null;
    foreach(['title','name','event_name'] as $tc){ if(in_array($tc, $availableCols)){ $titleCol = $tc; break; } }

    // details view
    $filter = $_GET['filter'] ?? 'all';
    if(isset($_GET['event_id'])){
        $id = $conn->real_escape_string($_GET['event_id']);
        $res = $conn->query("SELECT * FROM events WHERE id='{$id}' LIMIT 1");
        if($res && $res!==false){
            $event_detail = $res->fetch_assoc();
        } else $sql_error = $conn->error;
    } else {
        // $filter already set above
        $today = date('Y-m-d');

        // Search and sort inputs
        $search = $conn->real_escape_string($_GET['q'] ?? '');
        $sort = $_GET['sort'] ?? 'newest'; // default

        // Build where clauses
        $where = [];
        if($filter === 'upcoming' && $dateCol) $where[] = "{$dateCol} >= '{$today}'";
        if($filter === 'running' && $dateCol) $where[] = "{$dateCol} = '{$today}'";
        if($filter === 'past' && $dateCol) $where[] = "{$dateCol} < '{$today}'";
        if($search !== ''){
            // search only on columns that exist
            $sEsc = $search;
            $searchCols = [];
            foreach(['title','name','event_name','description','details'] as $col){ if(in_array($col,$availableCols)) $searchCols[] = "{$col} LIKE '%{$sEsc}%'"; }
            if(count($searchCols)>0) $where[] = '(' . implode(' OR ', $searchCols) . ')';
        }

        // Determine order by
        if($sort === 'az'){
            // alphabetical by available title-like column
            if($titleCol) $orderBy = "{$titleCol} ASC";
            else $orderBy = "id DESC"; // fallback
        } elseif($sort === 'za'){
            if($titleCol) $orderBy = "{$titleCol} DESC";
            else $orderBy = "id DESC";
        } elseif($sort === 'oldest' && $dateCol){
            $orderBy = "{$dateCol} ASC";
        } else {
            // newest
            if($dateCol) $orderBy = "{$dateCol} DESC";
            else $orderBy = "id DESC";
        }

        $sql = "SELECT * FROM events";
        if(count($where)>0) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= " ORDER BY {$orderBy} LIMIT 100";

        $res = $conn->query($sql);
        if($res === false) $sql_error = $conn->error;
        else { while($r = $res->fetch_assoc()) $events[] = $r; }
    }
} else {
    $sql_error = 'No valid $conn mysqli connection';
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>All Events - UTB Events</title>
    <link rel="stylesheet" href="includes/site.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/sidebar/sidebar.php'; ?>
    <main class="content">
        <?php
            // Decide breadcrumb and title
            if(!empty($event_detail)){
                $breadcrumb = "Home &gt; Events &gt; " . (htmlspecialchars($event_detail['title'] ?? $event_detail['name'] ?? 'Event'));
                $pageTitle = htmlspecialchars($event_detail['title'] ?? $event_detail['name'] ?? 'Event Details');
            } else {
                if($filter === 'upcoming'){
                    $breadcrumb = 'Home &gt; Featured Events &gt; Upcoming Events';
                    $pageTitle = 'Upcoming Events';
                } elseif($filter === 'running'){
                    $breadcrumb = 'Home &gt; Featured Events &gt; Running Events';
                    $pageTitle = 'Running Events';
                } elseif($filter === 'past'){
                    $breadcrumb = 'Home &gt; Events &gt; Past Events';
                    $pageTitle = 'Past Events';
                } else {
                    $breadcrumb = 'Home &gt; Events';
                    $pageTitle = 'All Events';
                }
            }
        ?>
        <div class="breadcrumb"><?= $breadcrumb ?></div>
        <h1><?= $pageTitle ?></h1>

        <?php if(empty($event_detail)): ?>
        <form method="get" style="margin:14px 0;display:flex;gap:10px;align-items:center">
            <input type="hidden" name="filter" value="<?=htmlspecialchars($filter)?>">
            <input name="q" placeholder="Search events..." value="<?=htmlspecialchars($_GET['q'] ?? '')?>" style="padding:8px;border-radius:6px;border:1px solid #ddd;flex:1">
            <select name="sort" style="padding:8px;border-radius:6px;border:1px solid #ddd">
                <option value="newest" <?= (($_GET['sort'] ?? 'newest')==='newest')? 'selected':''?>>Newest</option>
                <option value="oldest" <?= (($_GET['sort'] ?? '')==='oldest')? 'selected':''?>>Oldest</option>
                <option value="az" <?= (($_GET['sort'] ?? '')==='az')? 'selected':''?>>A → Z</option>
                <option value="za" <?= (($_GET['sort'] ?? '')==='za')? 'selected':''?>>Z → A</option>
            </select>
            <button type="submit" class="btn">Apply</button>
        </form>
        <?php endif; ?>

        <div class="panel">
            <?php if(!empty($sql_error)): ?>
                <p style="color:crimson;font-weight:700">Database error: <?=htmlspecialchars($sql_error) ?></p>
            <?php endif; ?>

            <?php if(!empty($event_detail)): ?>
                <?php
                    $title = $event_detail['title'] ?? $event_detail['name'] ?? $event_detail['event_name'] ?? 'Event';
                    $desc = $event_detail['description'] ?? $event_detail['details'] ?? '';
                    $img = $event_detail['image_url'] ?? $event_detail['image'] ?? $event_detail['img'] ?? '';
                    $date = $event_detail['event_date'] ?? $event_detail['date'] ?? $event_detail['start_date'] ?? '';
                    $venue = $event_detail['venue'] ?? $event_detail['location'] ?? '';
                ?>
                <div class="event-detail">
                    <div class="detail-image">
                        <?php if(!empty($img)): ?>
                            <img src="<?=htmlspecialchars($img)?>" alt="<?=htmlspecialchars($title)?>">
                        <?php else: ?>
                            <img src="includes/events/placeholder.png" alt="placeholder">
                        <?php endif; ?>
                    </div>
                    <div class="detail-body">
                        <h2><?=htmlspecialchars($title)?></h2>
                        <?php if(!empty($date)): ?><div class="event-meta"><strong>Date:</strong> <?=htmlspecialchars($date)?></div><?php endif; ?>
                        <?php if(!empty($venue)): ?><div class="event-meta"><strong>Venue:</strong> <?=htmlspecialchars($venue)?></div><?php endif; ?>
                        <div style="margin-top:16px;line-height:1.6"><?=nl2br(htmlspecialchars($desc))?></div>
                        <div style="margin-top:18px">
                            <?php
                                // Determine if current user already registered for this event
                                $is_registered = false;
                                if(!empty($_SESSION['user']) && !empty($event_detail['id'])){
                                    $chk = $conn->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND email = ? LIMIT 1");
                                    if($chk){
                                        $chk->bind_param('is', $event_detail['id'], $_SESSION['user']);
                                        $chk->execute();
                                        $cres = $chk->get_result();
                                        if($cres && $cres->num_rows>0) $is_registered = true;
                                    }
                                }
                            ?>
                            <?php $evIdAttr = htmlspecialchars($event_detail['id'] ?? ''); ?>
                            <button id="registerBtn" class="btn" data-event-id="<?= $evIdAttr ?>" data-registered="<?= $is_registered ? '1' : '0' ?>" aria-pressed="<?= $is_registered ? 'true' : 'false' ?>" title="Toggle registration for this event">
                                <?php if($is_registered): ?>
                                    &#10003; Registered <small style="font-weight:600">(click to unregister)</small>
                                <?php else: ?>
                                    Register
                                <?php endif; ?>
                            </button>
                            <a href="events.php?filter=<?=urlencode($filter)?>" class="btn" style="margin-left:8px;background:#ccc;color:#000">Back to list</a>
                        </div>
                        <!-- Feedback / Ratings -->
                        <?php
                            // Load comments for this event and compute average
                            $comments = [];
                            $avgRating = null;
                            if(!empty($event_detail['id'])){
                                $cid = intval($event_detail['id']);
                                $cstmt = $conn->prepare("SELECT c.*, u.fullname FROM comments c LEFT JOIN users u ON u.id = c.user_id WHERE c.event_id = ? ORDER BY c.created_at DESC");
                                if($cstmt){
                                    $cstmt->bind_param('i', $cid);
                                    $cstmt->execute();
                                    $cres = $cstmt->get_result();
                                    if($cres){ while($row = $cres->fetch_assoc()) $comments[] = $row; }
                                }
                                // average
                                $avgQ = $conn->query("SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM comments WHERE event_id=" . $cid);
                                if($avgQ && $avgQ->num_rows){ $ar = $avgQ->fetch_assoc(); $avgRating = $ar['avg_rating'] ? round($ar['avg_rating'],1) : null; $commentsCount = intval($ar['cnt']); }
                                else $commentsCount = 0;
                            }
                        ?>

                        <div style="margin-top:18px;padding:12px;border-top:1px dashed #e6e6e6">
                            <h3 style="margin:0 0 8px 0">Event Feedback <?php if($avgRating) echo ' — <small style="font-weight:700;color:#11676a">' . htmlspecialchars($avgRating) . ' / 5 (' . $commentsCount . ')</small>'; ?></h3>

                            <?php if(!empty($comments)): ?>
                                <div style="margin-top:10px">
                                    <?php foreach($comments as $com): ?>
                                        <div style="border:1px solid #f0f0f0;padding:10px;border-radius:6px;margin-bottom:8px">
                                            <div style="font-weight:700;color:#11676a"><?=htmlspecialchars($com['fullname'] ?? $com['email'] ?? 'Anonymous')?></div>
                                            <div style="color:#444;margin:6px 0">
                                                <?php for($i=1;$i<=5;$i++): ?>
                                                    <?php if($i <= intval($com['rating'])): ?>★<?php else: ?>☆<?php endif; ?>
                                                <?php endfor; ?>
                                            </div>
                                            <div style="color:#333;white-space:pre-wrap"><?=nl2br(htmlspecialchars($com['comment']))?></div>
                                            <div style="font-size:12px;color:#888;margin-top:6px"><?=htmlspecialchars($com['created_at'])?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p style="color:#666">No feedback yet. Be the first to leave a rating.</p>
                            <?php endif; ?>

                            <?php if($is_registered): ?>
                                <form id="feedbackForm" style="margin-top:12px">
                                    <input type="hidden" name="action" value="leave_feedback">
                                    <input type="hidden" name="event_id" value="<?=htmlspecialchars($event_detail['id'])?>">
                                    <div style="margin-bottom:8px">Your rating:
                                        <label style="margin-left:8px"><input type="radio" name="rating" value="5"> 5</label>
                                        <label style="margin-left:6px"><input type="radio" name="rating" value="4"> 4</label>
                                        <label style="margin-left:6px"><input type="radio" name="rating" value="3"> 3</label>
                                        <label style="margin-left:6px"><input type="radio" name="rating" value="2"> 2</label>
                                        <label style="margin-left:6px"><input type="radio" name="rating" value="1"> 1</label>
                                    </div>
                                    <div style="margin-bottom:8px">
                                        <textarea name="comment" placeholder="Write your feedback..." style="width:100%;min-height:80px;padding:8px;border:1px solid #ddd;border-radius:6px"></textarea>
                                    </div>
                                    <div><button class="btn" type="submit">Send Feedback</button></div>
                                </form>
                            <?php else: ?>
                                <p style="color:#666;margin-top:8px">Only participants can leave feedback. Please register to be able to rate and comment.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <?php if(count($events)===0 && empty($sql_error)): ?>
                    <p>No events found. If your database doesn't have an `events` table or it's empty, this placeholder will show. You can create a table named `events` with fields: id, title, description, image_url, event_date.</p>
                <?php else: ?>
                    <div class="grid">
                        <?php foreach($events as $e):
                                // determine title/description safely
                                $title = $e['title'] ?? $e['name'] ?? ($e['event_name'] ?? 'Untitled Event');
                                $desc = $e['description'] ?? $e['details'] ?? '';

                                // optional image fields (check a few common names)
                                $img = '';
                                if(!empty($e['image_url'])) $img = $e['image_url'];
                                elseif(!empty($e['image'])) $img = $e['image'];
                                elseif(!empty($e['img'])) $img = $e['img'];

                                // optional date fields
                                $date = $e['event_date'] ?? $e['date'] ?? $e['start_date'] ?? '';
                                $id = $e['id'] ?? '';
                        ?>
                            <div class="card">
                                <?php if(!empty($id)): ?><a href="events.php?event_id=<?=urlencode($id)?>&filter=<?=urlencode($filter)?>"><?php endif; ?>
                                    <div class="card-title"><?=htmlspecialchars($title)?></div>
                                    <div class="card-media"><?php if(!empty($img)): ?><img src="<?=htmlspecialchars($img)?>" alt="<?=htmlspecialchars($title)?>"><?php else: ?><img src="includes/events/placeholder.png" alt="placeholder"><?php endif; ?></div>
                                    <div class="card-desc"><?=htmlspecialchars($desc)?></div>
                                    <?php if(!empty($date)): ?><small style="color:#6b8f8d"><?=htmlspecialchars($date)?></small><?php endif; ?>
                                <?php if(!empty($id)): ?></a><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
            <?php include __DIR__ . '/contact.php'; ?>
        </body>
        </html>
<!-- site.js contains register and feedback handlers -->