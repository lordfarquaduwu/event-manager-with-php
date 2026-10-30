<?php
session_start();
if(!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') != 'admin'){
    header('Location: ../index.php'); 
    exit();
}
include __DIR__ . '/../connection.php';

// Handle admin deletion of a comment (POST)
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_comment' && isset($_POST['comment_id'])){
    $cid = intval($_POST['comment_id']);
    if($cid > 0){
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ? LIMIT 1");
        if($stmt){
            $stmt->bind_param('i', $cid);
            $stmt->execute();
        }
    }
    // Redirect back to avoid form resubmission
    header('Location: admin_reports.php'); exit();
}

// Fetch event registration statistics
$events = [];
$res = $conn->query("
    SELECT 
        e.id,
        e.title,
        e.event_date,
        COUNT(r.id) as registration_count,
        e.created_at
    FROM events e
    LEFT JOIN event_registrations r ON e.id = r.event_id
    GROUP BY e.id, e.title, e.event_date, e.created_at
    ORDER BY registration_count DESC, e.event_date DESC
");

if($res) { 
    while($r = $res->fetch_assoc()) {
        $events[] = $r;
    }
}

// Get overall statistics
$stats = [
    'total_events' => 0,
    'total_registrations' => 0,
    'avg_registrations' => 0,
    'most_popular_event' => null,
    'upcoming_events' => 0
];

$statRes = $conn->query("SELECT COUNT(*) as total FROM events");
if($statRes) $stats['total_events'] = $statRes->fetch_assoc()['total'];

$statRes = $conn->query("SELECT COUNT(*) as total FROM event_registrations");
if($statRes) $stats['total_registrations'] = $statRes->fetch_assoc()['total'];

$statRes = $conn->query("SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE()");
if($statRes) $stats['upcoming_events'] = $statRes->fetch_assoc()['total'];

if(count($events) > 0) {
    $stats['avg_registrations'] = round($stats['total_registrations'] / $stats['total_events'], 1);
    $stats['most_popular_event'] = $events[0]; // First event has highest registrations
}

// Load recent feedback/comments for admin review
$comments = [];
$cres = $conn->query("SELECT c.*, e.title AS event_title, u.fullname FROM comments c LEFT JOIN events e ON e.id = c.event_id LEFT JOIN users u ON u.id = c.user_id ORDER BY c.created_at DESC LIMIT 500");
if($cres){ while($row = $cres->fetch_assoc()) $comments[] = $row; }

// CSV export endpoint: only for admins
if(isset($_GET['export']) && $_GET['export'] === 'csv'){
    if(empty($_SESSION['role']) || $_SESSION['role'] !== 'admin'){
        header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden'); exit();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="event_reports.csv"');
    $out = fopen('php://output', 'w');
    
    // Write header row
    fputcsv($out, ['Event ID', 'Title', 'Date', 'Description', 'Total Registrations', 'Registered Users', 'Comments']);
    
    // Query to get events with their details, registrations, and comments
    $r = $conn->query("
        SELECT 
            e.id,
            e.title,
            e.event_date,
            e.description,
            COUNT(DISTINCT r.id) AS registration_count,
            GROUP_CONCAT(DISTINCT u.fullname ORDER BY u.fullname SEPARATOR ', ') as registered_users,
            GROUP_CONCAT(
                CONCAT(
                    'Rating: ', COALESCE(c.rating, 'N/A'), 
                    ' - ', 
                    COALESCE(c.comment, 'No comment'),
                    ' (by ', COALESCE(cu.fullname, 'Anonymous'), ')'
                ) 
                SEPARATOR ' | '
            ) as event_comments
        FROM events e
        LEFT JOIN event_registrations r ON e.id = r.event_id
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN comments c ON e.id = c.event_id
        LEFT JOIN users cu ON c.user_id = cu.id
        GROUP BY e.id, e.title, e.event_date, e.description
        ORDER BY e.event_date DESC
    ");

    if($r){
        while($row = $r->fetch_assoc()){
            fputcsv($out, [
                $row['id'],
                $row['title'],
                $row['event_date'],
                $row['description'],
                $row['registration_count'],
                $row['registered_users'] ?: 'No registrations',
                $row['event_comments'] ?: 'No comments'
            ]);
        }
    }
    fclose($out);
    exit();
}
?>

<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Event Reports - Admin</title>
    <link rel="stylesheet" href="../includes/site.css">
    <link rel="stylesheet" href="../includes/admin.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/../sidebar/sidebar.php'; ?>
    <main class="content-wrap">
        <div class="panel">
            <h1>Event Registration Reports</h1>
            <div style="margin-top:10px">
                <a class="btn" href="?export=csv">Download CSV</a>
            </div>
            
            <!-- Statistics Overview -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_events'] ?></div>
                    <div class="stat-label">Total Events</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_registrations'] ?></div>
                    <div class="stat-label">Total Registrations</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['avg_registrations'] ?></div>
                    <div class="stat-label">Avg. Registrations per Event</div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['upcoming_events'] ?></div>
                    <div class="stat-label">Upcoming Events</div>
                </div>
            </div>

            <!-- Most Popular Event Highlight -->
            <?php if($stats['most_popular_event']): ?>
            <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                <h3 style="margin: 0 0 10px 0; color: #155724;">🌟 Most Popular Event</h3>
                <p style="margin: 0;">
                    <strong><?= htmlspecialchars($stats['most_popular_event']['title']) ?></strong> 
                    with <span class="registration-count"><?= $stats['most_popular_event']['registration_count'] ?></span> registrations
                    <?php if($stats['most_popular_event']['event_date']): ?>
                        - Scheduled for <?= htmlspecialchars($stats['most_popular_event']['event_date']) ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Events Registration Details -->
            <h2>Event Registration Details</h2>
            <?php if(empty($events)): ?>
                <div class="no-data">
                    No events found. Create some events to see registration reports.
                </div>
            <?php else: ?>
                <table class="events-table">
                    <thead>
                        <tr>
                            <th>Event Title</th>
                            <th>Date</th>
                            <th>Registrations</th>
                            <th>Popularity Rank</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($events as $index => $event): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($event['title']) ?></strong>
                                <?php if(!empty($event['id'])): ?>
                                    <br><a class="muted" href="../events.php?event_id=<?= urlencode($event['id']) ?>" target="_blank">View event</a>
                                <?php endif; ?>
                                <?php if($index === 0 && $event['registration_count'] > 0): ?>
                                    <span class="popular-badge">Most Popular</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= $event['event_date'] ? htmlspecialchars($event['event_date']) : 'No date set' ?>
                                <?php if($event['event_date'] && $event['event_date'] >= date('Y-m-d')): ?>
                                    <br><small style="color: #28a745;">↑ Upcoming</small>
                                <?php elseif($event['event_date'] && $event['event_date'] < date('Y-m-d')): ?>
                                    <br><small style="color: #6c757d;">↓ Past</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="registration-count"><?= $event['registration_count'] ?></span>
                            </td>
                            <td>
                                #<?= $index + 1 ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Event Feedback (comments) -->
            <h2 style="margin-top:30px">Event Feedback</h2>
            <?php if(empty($comments)): ?>
                <div class="no-data">No feedback available.</div>
            <?php else: ?>
                <table class="events-table" style="margin-top:10px">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>User</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>When</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($comments as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['event_title'] ?? 'Unknown') ?></td>
                                <td><?= htmlspecialchars($c['fullname'] ?? $c['email'] ?? 'Anonymous') ?></td>
                                <td class="stars">
                                    <?php $r = intval($c['rating']); for($i=1;$i<=5;$i++): ?>
                                        <?php if($i <= $r): ?><span>★</span><?php else: ?><span style="color:#ddd">☆</span><?php endif; ?>
                                    <?php endfor; ?>
                                </td>
                                <td style="max-width:420px;white-space:pre-wrap"><?= nl2br(htmlspecialchars($c['comment'])) ?></td>
                                <td><?= htmlspecialchars($c['created_at']) ?></td>
                                <td>
                                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this feedback?');">
                                        <input type="hidden" name="action" value="delete_comment">
                                        <input type="hidden" name="comment_id" value="<?= intval($c['id']) ?>">
                                        <button type="submit" class="btn-delete">Delete</button>
                                    </form>
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
</body>
</html>