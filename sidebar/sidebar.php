<?php
// ensure session is available for admin role checks
if(session_status() === PHP_SESSION_NONE) session_start();

// Build a base URL path for links and images that works regardless of server document root
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');

// Check if we're in admin folder and adjust base path
if(strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
    $base = dirname($base); // Go up one level
}

if($base === '') $base = '/';
if($base === '/') $base = '';
$base_path = $base ? $base . '/' : '';
// current script basename (used to highlight active nav item)
$cur = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>

<!-- Sidebar toggle for small screens -->
<button id="sidebar-toggle" aria-label="Toggle navigation" class="sidebar-toggle">☰</button>

<aside class="sidebar" id="site-sidebar">
    <div class="logo">
    <img class="logo-img" src="<?= $base_path ?>sidebar/<?= rawurlencode('utb logo.png') ?>" alt="UTB Logo">
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            
        <?php endif; ?>
    </div>
    
    <nav class="nav">
        <!-- Different Home links based on role -->
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <?php $isActive = ($cur === 'admin_dashboard.php') ? 'active' : ''; ?>
            <a href="<?= $base_path ?>admin/admin_dashboard.php" class="<?= $isActive ?>" title="Dashboard" <?= $isActive ? 'aria-current="page"' : '' ?>>
                <span class="icon"><img src="<?= $base_path ?>sidebar/home.png" alt="Home"></span>
            </a>
        <?php else: ?>
            <?php $isActive = ($cur === 'welcome.php' || $cur === 'index.php') ? 'active' : ''; ?>
            <a href="<?= $base_path ?>welcome.php" class="<?= $isActive ?>" title="Home" <?= $isActive ? 'aria-current="page"' : '' ?>>
                <span class="icon"><img src="<?= $base_path ?>sidebar/home.png" alt="Home"></span>
            </a>
        <?php endif; ?>
        
        <!-- Events link ONLY for students -->
        <?php if(!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
            <?php $isActive = ($cur === 'events.php') ? 'active' : ''; ?>
            <a href="<?= $base_path ?>events.php" class="<?= $isActive ?>" title="Events" <?= $isActive ? 'aria-current="page"' : '' ?>>
                <span class="icon"><img src="<?= $base_path ?>sidebar/events.png" alt="Events"></span>
            </a>
        <?php endif; ?>
        
        <!-- Admin-only links -->
        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
           <?php $isActive = ($cur === 'manage_events.php') ? 'active' : ''; ?>
           <a href="<?= $base_path ?>admin/manage_events.php" class="<?= $isActive ?>" title="Event Manager" <?= $isActive ? 'aria-current="page"' : '' ?>>
                <span> </span> <span class="icon"><img src="<?= $base_path ?>sidebar/eventmanager.png" alt="Event Manager"></span>
            </a>
            
               <?php $isActive = ($cur === 'admin_reports.php') ? 'active' : ''; ?>
               <a href="<?= $base_path ?>admin/admin_reports.php" class="<?= $isActive ?>" title="Reports" <?= $isActive ? 'aria-current="page"' : '' ?>>
                    <span class="icon"><img src="<?= $base_path ?>sidebar/report.png" alt="Reports"></span>
                </a>
                <?php $isActive = ($cur === 'manage_users.php') ? 'active' : ''; ?>
                <a href="<?= $base_path ?>admin/manage_users.php" class="<?= $isActive ?>" title="Manage Users" <?= $isActive ? 'aria-current="page"' : '' ?>>
                    <span class="icon"><img src="<?= $base_path ?>sidebar/manageusers.png" alt="Manage Users"></span>
                </a>
        <?php endif; ?>
        
        <!-- Profile link for everyone -->
        <?php $isActive = ($cur === 'profile.php') ? 'active' : ''; ?>
        <a href="<?= $base_path ?>profile.php" class="<?= $isActive ?>" title="Profile" <?= $isActive ? 'aria-current="page"' : '' ?> >
            <span class="icon"><img src="<?= $base_path ?>sidebar/profile.png" alt="Profile"></span>
        </a>
        
        <!-- Logout link for everyone -->
        <?php $isActive = ($cur === 'logout.php') ? 'active' : ''; ?>
        <a href="<?= $base_path ?>logout.php" class="<?= $isActive ?>" title="Logout" <?= $isActive ? 'aria-current="page"' : '' ?>>
            <span class="icon"><img src="<?= $base_path ?>sidebar/logout.png" alt="Logout"></span>
        </a>
    </nav>
</aside>
<div id="sidebar-overlay" class="sidebar-overlay" aria-hidden="true"></div>

<style>
/* Responsive behavior and toggle for sidebar (desktop + mobile) */
.sidebar{ transition: transform .22s ease, box-shadow .22s ease; }

/* Toggle button (visible on all sizes) */
.sidebar-toggle{
    position: fixed;
    top: 12px;
    left: 12px;
    z-index: 1200;
    border: none;
    background: #11676a;
    color: #fff;
    width: 40px;
    height: 40px;
    border-radius: 6px;
    font-size: 20px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
}

/* Mobile: off-canvas sidebar with overlay */
@media (max-width: 900px){
    .sidebar{
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 260px;
        transform: translateX(-110%);
        z-index: 1100;
        background: white;
        box-shadow: 2px 0 12px rgba(0,0,0,0.08);
    }
    body.sidebar-open .sidebar{ transform: translateX(0); }
    .sidebar-overlay{ display:none; }
    body.sidebar-open .sidebar-overlay{
        display:block;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.38);
        z-index: 1050;
    }
}

/* Desktop: allow collapsing the sidebar (slides out) */
@media (min-width: 901px){
    .sidebar{
        position: relative; /* keep existing layout on desktop */
        transform: translateX(0);
        width: 260px;
    }
    body.sidebar-collapsed .sidebar{
        transform: translateX(-260px);
    }
    /* overlay not used on desktop */
    .sidebar-overlay{ display:none; }
}
</style>

<script src="<?= $base_path ?>includes/site.js"></script>