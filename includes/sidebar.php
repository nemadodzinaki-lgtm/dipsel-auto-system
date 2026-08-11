<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

// Current page info for active menu highlighting
$currentFile = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

// Helper to check if a menu link matches the current page
function isActive($link) {
    global $currentFile, $currentDir;
    $targetFile = basename($link);
    $targetDir  = basename(dirname($link));
    if ($targetDir == '.' || $targetDir == '..') {
        return $currentFile == $targetFile;
    }
    return ($currentDir == $targetDir && $currentFile == $targetFile);
}

$userRole = $_SESSION['role'] ?? 'Customer';

// --------------------------------------------------------------------
// Define menu items per role (sections with title and items)
// Each item: [ 'label', 'icon' (FontAwesome class), 'link', 'roles' ]
// --------------------------------------------------------------------

$menuSections = [];

// ----- SuperAdmin & Admin (full access) -----
if ($userRole === 'SuperAdmin' || $userRole === 'Admin') {
    $menuSections = [
        [
            'title' => null,
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'fa-home', 'link' => '../superadmin/index.php', 'roles' => ['SuperAdmin','Admin']],
            ]
        ],
        [
            'title' => 'MANAGEMENT',
            'items' => [
                ['label' => 'Vehicles',       'icon' => 'fa-car',             'link' => '../vehicles/index.php',          'roles' => ['SuperAdmin','Admin']],
                ['label' => 'Customers',      'icon' => 'fa-users',           'link' => '../customers_AdminManagement/index.php', 'roles' => ['SuperAdmin','Admin']],
                ['label' => 'Employees',      'icon' => 'fa-user-tie',        'link' => '../employees/index.php',         'roles' => ['SuperAdmin','Admin']],
                ['label' => 'Suppliers',      'icon' => 'fa-truck',           'link' => '../suppliers/index.php',         'roles' => ['SuperAdmin','Admin']],
                ['label' => 'Workshop',       'icon' => 'fa-tools',           'link' => '../workshop/index.php',          'roles' => ['SuperAdmin','Admin']],
                ['label' => 'Service Booking','icon' => 'fa-boxes',           'link' => '../Service_Booking/index.php',   'roles' => ['SuperAdmin','Admin']],
                ['label' => 'Sales',          'icon' => 'fa-cash-register',   'link' => '../vehicle_sales/index.php',     'roles' => ['SuperAdmin','Admin']],
                //['label' => 'Finance',        'icon' => 'fa-coins',           'link' => '../finance/index.php',           'roles' => ['SuperAdmin','Admin']],
                 ['label' => 'Appointments', 'icon' => 'fa-receipt',      'link' => '../../dashboard/appointments/index.php',    'roles' => ['superAdmin', 'Admin']],
                
                ['label' => 'Leave Applications',      'icon' => 'fa-calendar-check',   'link' => '../leave/index.php',         'roles' => ['SuperAdmin','Admin']],
                ['label' => 'Insurance',      'icon' => 'fa-file-contract',   'link' => '../insurance/index.php',         'roles' => ['SuperAdmin','Admin']],
                ['label' => 'CRM',            'icon' => 'fa-comments',        'link' => '../crm/index.php',               'roles' => ['SuperAdmin','Admin']],
                ['label' => 'Home Slider',    'icon' => 'fa-sliders-h',       'link' => '../homeslider/index.php',        'roles' => ['SuperAdmin','Admin']],
            ]
        ],
        [
            'title' => 'REPORTS',
            'items' => [
                ['label' => 'Vehicle Submissions',   'icon' => 'fa-chart-line', 'link' => '../Review_selling_cars/vehicle_submission.php', 'roles' => ['SuperAdmin','Admin']],
               // ['label' => 'Audit Logs','icon' => 'fa-history',    'link' => '../audit/index.php',    'roles' => ['SuperAdmin','Admin']],
               // ['label' => 'Backup',    'icon' => 'fa-database',   'link' => '../backup/index.php',   'roles' => ['SuperAdmin','Admin']],
            ]
        ],
        [
            'title' => 'SYSTEM',
            'items' => [
                ['label' => 'Settings', 'icon' => 'fa-cogs',          'link' => '../settings/settings.php', 'roles' => ['SuperAdmin','Admin']],
                ['label' => 'Profile',  'icon' => 'fa-user-circle',   'link' => '../../dashboard/Profile/profile.php',        'roles' => ['SuperAdmin','Admin']],
                ['label' => 'Logout',   'icon' => 'fa-sign-out-alt',  'link' => '../../api/auth/logout.php','roles' => ['SuperAdmin','Admin']],
            ]
        ],
    ];
}

// ----- Employee (restricted access) -----
if ($userRole === 'Employee') {
    $menuSections = [
        [
            'title' => null,
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'fa-home', 'link' => '../employee/index.php', 'roles' => ['Employee']],
            ]
        ],
        [
            'title' => 'MANAGEMENT',
            'items' => [
                ['label' => 'Vehicles',       'icon' => 'fa-car',           'link' => '../vehicles/vehicles.php',          'roles' => ['Employee']],
               // ['label' => 'Customers',      'icon' => 'fa-users',         'link' => '../customers_AdminManagement/index.php', 'roles' => ['Employee']],
                ['label' => 'Test Drives',      'icon' => 'fa-truck',         'link' => '../test_drives/test_drives.php',         'roles' => ['Employee']],
               // ['label' => 'Workshop',       'icon' => 'fa-tools',         'link' => '../workshop/index.php',          'roles' => ['Employee']],
                ['label' => 'Bookings Management','icon' => 'fa-boxes',         'link' => '../bookings/bookings.php',   'roles' => ['Employee']],
               // ['label' => 'Sales',          'icon' => 'fa-cash-register', 'link' => '../vehicle_sales/index.php',     'roles' => ['Employee']],
            ]
        ],
       // [
            //'title' => 'REPORTS',
           // 'items' => [
              //  ['label' => 'Reports', 'icon' => 'fa-chart-line', 'link' => '../reports/index.php', 'roles' => ['Employee']],
          //  ]
       // ],
        [
            'title' => 'SYSTEM',
            'items' => [
                ['label' => 'Profile', 'icon' => 'fa-user-circle', 'link' => '../../EmployeesDashboard/Profile/profile.php',        'roles' => ['Employee']],
                ['label' => 'Logout',  'icon' => 'fa-sign-out-alt', 'link' => '../../api/auth/logout.php','roles' => ['Employee']],
            ]
        ],
    ];
}

// ----- Customer -----
if ($userRole === 'Customer') {
    $menuSections = [
        [
            'title' => null,
            'items' => [
                ['label' => 'Dashboard', 'icon' => 'fa-home', 'link' => '../../customerDashboard/customer/index.php', 'roles' => ['Customer']],
            ]
        ],
        [
            'title' => 'VEHICLES',
            'items' => [
                ['label' => 'Buy Cars',     'icon' => 'fa-car',          'link' => '../../customerDashboard/vehicles_sales/index.php',    'roles' => ['Customer']],
                ['label' => 'Sell',  'icon' => 'fa-car-side',     'link' => '../sell/vehicle_submissions.php', 'roles' => ['Customer']],
                ['label' => 'Appointments', 'icon' => 'fa-receipt',      'link' => '../../customerDashboard/purchase_history/history.php',    'roles' => ['Customer']],
            ]
        ],
        [
            'title' => 'SERVICES',
            'items' => [
                ['label' => 'Book Service',    'icon' => 'fa-calendar-check', 'link' => '../../CustomerDashboard/bookings/create.php', 'roles' => ['Customer']],
                ['label' => 'Workshop Status', 'icon' => 'fa-tools',          'link' => '../../CustomerDashboard/workshop/index.php',        'roles' => ['Customer']],
                ['label' => 'Insurance',       'icon' => 'fa-file-contract',  'link' => '../../customerDashboard/insurance/index.php',       'roles' => ['Customer']],
            
            ]
        ],
        [
            'title' => 'ACCOUNT',
            'items' => [
                ['label' => 'My Profile',       'icon' => 'fa-user-circle',  'link' => '../../customerDashboard/profile/index.php',                  'roles' => ['Customer']],
                
                ['label' => 'Support',          'icon' => 'fa-comments',     'link' => '../../customerDashboard/Support/index.php',   'roles' => ['Customer']],
                ['label' => 'Logout',           'icon' => 'fa-sign-out-alt', 'link' => '../../api/auth/logout.php',          'roles' => ['Customer']],
            ]
        ],
    ];
}

?>
<!-- ===================== SIDEBAR HTML ===================== -->
<!-- FontAwesome CDN – ensures icons show on all pages -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Mobile Toggle Button (hidden on desktop) -->
<button id="sidebarToggle" class="sidebar-toggle" aria-label="Toggle navigation">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay (hidden on desktop) -->
<div id="sidebarOverlay" class="sidebar-overlay"></div>

<aside id="sidebar">
    <!-- Header -->
    <div class="sidebar-header">
        <img src="https://mir-s3-cdn-cf.behance.net/project_modules/disp/c51cca151156061.6306d5fa13bef.png"
             alt="Logo" class="logo">
        <h3>Dipsel Auto</h3>
        <small><?= htmlspecialchars($userRole) ?></small>
    </div>

    <!-- Menu -->
    <ul class="sidebar-menu">
        <?php foreach ($menuSections as $section): ?>
            <?php if ($section['title'] !== null): ?>
                <li class="menu-title"><?= htmlspecialchars($section['title']) ?></li>
            <?php endif; ?>

            <?php foreach ($section['items'] as $item): ?>
                <?php if (in_array($userRole, $item['roles'])): ?>
                    <li class="<?= isActive($item['link']) ? 'active' : '' ?>">
                        <a href="<?= htmlspecialchars($item['link']) ?>">
                            <i class="fas <?= htmlspecialchars($item['icon']) ?>"></i>
                            <span><?= htmlspecialchars($item['label']) ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </ul>
</aside>

<!-- ===================== STYLES ===================== -->
<style>
    /* ── Sidebar Base ── */
    #sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: 270px;
        height: 100vh;
        background: linear-gradient(180deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        color: #e0e0e0;
        padding: 20px 0 30px 0;
        overflow-y: auto;
        z-index: 1050;
        transition: all 0.3s;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.2);
        display: flex;
        flex-direction: column;
    }

    #sidebar::-webkit-scrollbar {
        width: 5px;
    }
    #sidebar::-webkit-scrollbar-track {
        background: transparent;
    }
    #sidebar::-webkit-scrollbar-thumb {
        background: #4a4a6a;
        border-radius: 10px;
    }
    #sidebar::-webkit-scrollbar-thumb:hover {
        background: #667eea;
    }

    /* ── Sidebar Header ── */
    .sidebar-header {
        text-align: center;
        padding: 0 20px 20px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        margin-bottom: 15px;
    }
    .sidebar-header .logo {
        width: 60px;
        height: 60px;
        object-fit: contain;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.05);
        padding: 8px;
        margin-bottom: 8px;
        transition: transform 0.3s;
    }
    .sidebar-header .logo:hover {
        transform: scale(1.05);
    }
    .sidebar-header h3 {
        font-size: 1.3rem;
        font-weight: 700;
        color: #fff;
        margin: 0;
        letter-spacing: 0.5px;
    }
    .sidebar-header small {
        display: block;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #8899bb;
        margin-top: 2px;
    }

    /* ── Sidebar Menu ── */
    .sidebar-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        flex: 1;
        display: flex;
        flex-direction: column;  /* allows last item to be pushed down */
    }
    .sidebar-menu li {
        margin: 2px 12px;
        border-radius: 10px;
        transition: background 0.2s;
    }
    .sidebar-menu li.menu-title {
        font-size: 0.65rem;
        text-transform: uppercase;
        letter-spacing: 1.2px;
        color: #6a7a9a;
        padding: 15px 12px 6px 12px;
        margin: 0 12px;
        font-weight: 600;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }
    .sidebar-menu li a {
        display: flex;
        align-items: center;
        padding: 10px 16px;
        color: #c8d0e0;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;          /* now bold like Admin */
        border-radius: 10px;
        transition: all 0.2s;
        white-space: nowrap;
        gap: 14px;
    }
    .sidebar-menu li a i {
        width: 24px;
        font-size: 1.1rem;
        text-align: center;
        color: #6a7a9a;
        transition: color 0.2s;
    }
    .sidebar-menu li a span {
        flex: 1;
    }

    /* Push the last item (Logout) to the bottom */
    .sidebar-menu li:last-child {
        margin-top: auto;
    }

    /* Hover */
    .sidebar-menu li:not(.menu-title):hover {
        background: rgba(255, 255, 255, 0.05);
    }
    .sidebar-menu li:not(.menu-title):hover a {
        color: #ffffff;
    }
    .sidebar-menu li:not(.menu-title):hover a i {
        color: #a0b4d0;
    }

    /* Active */
    .sidebar-menu li.active {
        background: rgba(102, 126, 234, 0.2);
        box-shadow: inset 3px 0 0 #667eea;
    }
    .sidebar-menu li.active a {
        color: #ffffff;
        font-weight: 600;
    }
    .sidebar-menu li.active a i {
        color: #667eea;
    }

    /* Logout special – keep red colour, but margin-top: auto already applied */
    .sidebar-menu li:last-child a {
        color: #f28b82;
    }
    .sidebar-menu li:last-child a i {
        color: #f28b82;
    }
    .sidebar-menu li:last-child:hover {
        background: rgba(242, 139, 130, 0.15);
    }
    .sidebar-menu li:last-child:hover a {
        color: #ff6b6b;
    }
    .sidebar-menu li:last-child:hover a i {
        color: #ff6b6b;
    }

    /* ── Toggle Button & Overlay (hidden on desktop) ── */
    .sidebar-toggle {
        display: none;  /* hidden on desktop */
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1060;
        background: #1a1a2e;
        color: #fff;
        border: none;
        padding: 10px 12px;
        border-radius: 6px;
        font-size: 1.5rem;
        cursor: pointer;
        transition: background 0.2s;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    .sidebar-toggle:hover {
        background: #2a2a4a;
    }
    .sidebar-toggle i {
        pointer-events: none;
    }

    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 1040;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .sidebar-overlay.active {
        opacity: 1;
    }

    /* ── Responsive: Mobile (< 992px) ── */
    @media (max-width: 992px) {
        /* Sidebar becomes a sliding panel */
        #sidebar {
            position: fixed;
            left: -100%;
            width: 280px;
            height: 100vh;
            top: 0;
            padding: 20px 0 30px 0;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.3);
            transition: left 0.3s ease;
            border-radius: 0;
            z-index: 1050;
        }
        #sidebar.open {
            left: 0;
        }

        /* Show toggle button & overlay on mobile */
        .sidebar-toggle {
            display: block;
        }
        .sidebar-overlay {
            display: block;
            pointer-events: none;  /* prevents interaction when hidden */
        }
        .sidebar-overlay.active {
            pointer-events: auto;
        }

        /* Optional: adjust header and menu for smaller screens */
        .sidebar-header .logo {
            width: 50px;
            height: 50px;
        }
        .sidebar-header h3 {
            font-size: 1.1rem;
        }
        .sidebar-menu li a {
            padding: 8px 14px;
            font-size: 0.85rem;
        }
    }

    /* ── Ensure desktop styles are untouched ── */
    @media (min-width: 993px) {
        #sidebar {
            left: 0 !important;   /* always visible */
            width: 270px !important;
        }
        .sidebar-toggle {
            display: none !important;
        }
        .sidebar-overlay {
            display: none !important;
        }
    }
</style>

<!-- ===================== JAVASCRIPT ===================== -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');

        // Only run on mobile (width < 992px)
        function isMobile() {
            return window.innerWidth < 992;
        }

        function openSidebar() {
            if (!isMobile()) return;
            sidebar.classList.add('open');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden'; // prevent scrolling
        }

        function closeSidebar() {
            if (!isMobile()) return;
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Toggle
        toggleBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (sidebar.classList.contains('open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        // Overlay click closes sidebar
        overlay.addEventListener('click', closeSidebar);

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                closeSidebar();
            }
        });

        // Handle window resize: if going from mobile to desktop, ensure sidebar is visible and overlay hidden
        window.addEventListener('resize', function() {
            if (!isMobile()) {
                // On desktop, force sidebar visible, overlay hidden
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Initially, on mobile sidebar is closed
        if (isMobile()) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
</script>