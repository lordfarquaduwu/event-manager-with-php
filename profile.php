<?php
session_start();
if(!isset($_SESSION['user'])){ header('Location: index.php'); exit(); }
include 'connection.php';
$profile = null;
$message = '';
if(isset($conn) && $conn instanceof mysqli){
    // get current db name
    $dbRow = $conn->query("SELECT DATABASE() AS db");
    $dbName = $dbRow ? $dbRow->fetch_object()->db : '';

    // load profile by email (login stores email in session)
    $u = $conn->real_escape_string($_SESSION['user']);
    $res = $conn->query("SELECT * FROM users WHERE email='{$u}' LIMIT 1");
    if($res && $res->num_rows>0) $profile = $res->fetch_assoc();

    // handle profile update POST
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='save_profile'){
        // determine which columns exist in users table
        $userCols = [];
        if($dbName){
            $colsq = $conn->query("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema='".$conn->real_escape_string($dbName)."' AND table_name='users'");
            if($colsq){ while($c = $colsq->fetch_assoc()) $userCols[] = $c['COLUMN_NAME']; }
        }

        $updates = [];
        if(in_array('fullname', $userCols) && isset($_POST['fullname'])){
            $updates[] = "fullname='".$conn->real_escape_string($_POST['fullname'])."'";
        }
        if(in_array('email', $userCols) && isset($_POST['email'])){
            $updates[] = "email='".$conn->real_escape_string($_POST['email'])."'";
        }
        if(in_array('dob', $userCols) && isset($_POST['dob'])){
            $updates[] = "dob='".$conn->real_escape_string($_POST['dob'])."'";
        }

        if(count($updates)>0 && isset($profile['id'])){
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id='".$conn->real_escape_string($profile['id'])."' LIMIT 1";
            if($conn->query($sql)){
                $message = 'Profile updated successfully.';
                // refresh profile
                $res = $conn->query("SELECT * FROM users WHERE id='".$conn->real_escape_string($profile['id'])."' LIMIT 1");
                if($res) $profile = $res->fetch_assoc();
            } else {
                $message = 'Failed to update profile: ' . $conn->error;
            }
        } else {
            $message = 'Nothing to update.';
        }
    }

    // handle profile picture upload
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action']==='upload_pic'){
        if(isset($profile['id'])){
            // ensure profile_pic column exists
            $colQ = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
            if(!$colQ || $colQ->num_rows === 0){
                $conn->query("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL");
            }

            if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK){
                $tmp = $_FILES['profile_pic']['tmp_name'];
                $orig = $_FILES['profile_pic']['name'];
                $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                if(!in_array($ext, ['png','jpg','jpeg','gif'])){
                    $message = 'Invalid image type. Allowed: png, jpg, jpeg, gif.';
                } else {
                    $targetDir = __DIR__ . '/includes/profile_pics';
                    if(!is_dir($targetDir)) @mkdir($targetDir, 0755, true);
                    $filename = $profile['id'] . '_profile.' . $ext;
                    $target = $targetDir . '/' . $filename;
                    if(move_uploaded_file($tmp, $target)){
                        $webPath = 'includes/profile_pics/' . $filename;
                        $uId = $conn->real_escape_string($profile['id']);
                        $conn->query("UPDATE users SET profile_pic='".$conn->real_escape_string($webPath)."' WHERE id='".$uId."' LIMIT 1");
                        $message = 'Profile picture uploaded.';
                        // refresh profile
                        $res = $conn->query("SELECT * FROM users WHERE id='".$uId."' LIMIT 1");
                        if($res) $profile = $res->fetch_assoc();
                    } else {
                        $message = 'Failed to move uploaded file.';
                    }
                }
            } else {
                $message = 'No file uploaded or upload error.';
            }
        } else {
            $message = 'Profile not found.';
        }
    }

    // Fetch events the user has registered for (from event_registrations)
    $registeredEvents = [];
    if(isset($profile['id'])){
        $emailEsc = $conn->real_escape_string($_SESSION['user']);
        $uidEsc = $conn->real_escape_string($profile['id']);
        $sql = "SELECT e.* FROM events e JOIN event_registrations r ON e.id = r.event_id WHERE (r.email = '{$emailEsc}' OR r.user_id = '{$uidEsc}') ORDER BY e.id DESC";
        $rres = $conn->query($sql);
        if($rres){ while($row = $rres->fetch_assoc()) $registeredEvents[] = $row; }
    }

}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Profile - UTB Events</title>
    <link rel="stylesheet" href="includes/site.css">
</head>
<body>
<div class="app">
    <?php include __DIR__ . '/sidebar/sidebar.php'; ?>
    <main class="content">
        <h1>My Profile</h1>
        <?php if(!empty($message)): ?><div style="margin-bottom:12px;color:green;font-weight:700"><?=htmlspecialchars($message)?></div><?php endif; ?>
        <?php if($profile): ?>
            <div style="display:flex;gap:24px;align-items:flex-start">
                <div class="card" style="padding:18px;min-width:320px">
                    <h3 style="margin-top:0">Profile</h3>
                    <?php
                        // show profile picture at top with cache-busting so new uploads appear immediately
                        $picTop = '';
                        $picTopPath = '';
                        if(!empty($profile['profile_pic']) && file_exists(__DIR__ . '/' . $profile['profile_pic'])){
                            $picTopPath = $profile['profile_pic'];
                        } elseif(file_exists(__DIR__ . '/includes/profile_pics/default.png')){
                            $picTopPath = 'includes/profile_pics/default.png';
                        }
                        if($picTopPath){
                            // append file mtime to bust browser cache after upload
                            $full = __DIR__ . '/' . $picTopPath;
                            if(file_exists($full)){
                                $picTop = $picTopPath . '?v=' . filemtime($full);
                            } else {
                                $picTop = $picTopPath;
                            }
                        }
                    ?>
                    <?php if($picTop): ?>
                        <div style="text-align:center;margin-bottom:12px">
                            <img src="<?=htmlspecialchars($picTop)?>" alt="profile" style="width:160px;height:160px;object-fit:cover;border-radius:12px;display:inline-block">
                        </div>
                    <?php endif; ?>
                    <form method="post">
                        <input type="hidden" name="action" value="save_profile">
                        <label>Full name</label>
                        <input name="fullname" value="<?=htmlspecialchars($profile['fullname'] ?? '')?>" style="width:100%;padding:8px;margin:6px 0;border-radius:6px;border:1px solid #ddd">
                        <label>Email</label>
                        <input name="email" value="<?=htmlspecialchars($profile['email'] ?? '')?>" style="width:100%;padding:8px;margin:6px 0;border-radius:6px;border:1px solid #ddd">
                        <label>DOB</label>
                        <input name="dob" value="<?=htmlspecialchars($profile['dob'] ?? '')?>" style="width:100%;padding:8px;margin:6px 0;border-radius:6px;border:1px solid #ddd">
                        <label>Role</label>
                        <input name="role" value="<?=htmlspecialchars($profile['role'] ?? ($_SESSION['role'] ?? ''))?>" readonly style="width:100%;padding:8px;margin:6px 0;border-radius:6px;border:1px solid #ddd;background:#f7f7f7">
                        <div style="margin-top:10px"><button class="btn" type="submit">Save</button></div>
                    </form>
                    <div style="margin-top:8px;text-align:center">
                        <form method="post" enctype="multipart/form-data" style="display:inline-block;align-items:center">
                            <input type="hidden" name="action" value="upload_pic">
                            <label for="profile_pic_input" style="display:inline-flex;align-items:center;gap:8px;margin-right:8px">
                                <span style="font-weight:600">Profile picture upload</span>
                                <input id="profile_pic_input" type="file" name="profile_pic" accept="image/*">
                            </label>
                            <button class="btn" type="submit">Upload</button>
                        </form>
                    </div>
                </div>

                <div style="flex:1">
                    <div class="panel">
                        <h2>REGISTERED EVENTS</h2>
                        <?php if(empty($registeredEvents)): ?>
                            <p>No registered events found.</p>
                        <?php else: ?>
                            <div class="grid">
                                <?php foreach($registeredEvents as $e):
                                    $title = $e['title'] ?? $e['name'] ?? ($e['event_name'] ?? 'Untitled');
                                    $img = $e['image_url'] ?? $e['image'] ?? $e['img'] ?? '';
                                    $date = $e['event_date'] ?? $e['date'] ?? '';

                                    // determine image to show for the event (accept remote URLs or local paths)
                                    $imgPath = '';
                                    if(!empty($img)){
                                        if(preg_match('#^https?://#i', $img)){
                                            $imgPath = $img;
                                        } elseif(file_exists(__DIR__ . '/' . $img)){
                                            $imgPath = $img;
                                        }
                                    }
                                    if(empty($imgPath)){
                                        $imgPath = 'includes/profile_pics/default.png';
                                    }
                                ?>
                                <div class="card">
                                    <div class="card-media" style="height:160px;overflow:hidden;display:flex;align-items:center;justify-content:center;background:#f3f3f3;border-radius:8px">
                                        <img src="<?=htmlspecialchars($imgPath)?>" alt="<?=htmlspecialchars($title)?>" style="width:100%;height:100%;object-fit:cover">
                                    </div>
                                    <h3><?=htmlspecialchars($title)?></h3>
                                    <?php if(!empty($date)): ?><div class="muted"><?=htmlspecialchars($date)?></div><?php endif; ?>
                                    <div style="margin-top:8px">
    <a class="btn" href="events.php?event_id=<?=urlencode($e['id'])?>">View</a>
</div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
        <?php else: ?>
            <div class="card">
                <p>Profile info not found in database. Showing session username instead.</p>
                <p><strong>Username:</strong> <?=htmlspecialchars($_SESSION['user'])?></p>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>
<?php include __DIR__ . '/contact.php'; ?>
