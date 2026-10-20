<?php
session_start();
require_once '../php/db_config.php';
include_once '../asset/header/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.html");
    exit();
}

$currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

$pageTitles = [
    'dashboard'       => 'Dashboard',
    'prod-list'       => 'Product List',
    'stock-control'   => 'Stock Control',
    'sales'           => 'Sales Orders',
    'reports'         => 'Sales Reports',
    'branch-list'     => 'Manage Branches',
    'branch-staff'    => 'Branch Staff',
    'customers'       => 'Customers & Contacts',
    'activity-logs'   => 'Activity Logs'
];

$pageTitle = $pageTitles[$currentPage] ?? 'Dashboard';

$contentFile = '../asset/adminContent/' . $currentPage . '.php';
if (!file_exists($contentFile)) {
    $contentFile = '../asset/adminContent/dashboard.php';
}

$navItems = [
    'dashboard' => [
        'type'  => 'link',
        'href'  => '../page/admin.php?page=dashboard',
        'icon'  => 'bi-speedometer2',
        'label' => 'Dashboard'
    ],
    'inventory_parent' => [
        'type'          => 'dropdown',
        'fallback_href' => '../page/admin.php?page=prod-list', // Collapsed state shortcut
        'icon'          => 'bi-box-seam',
        'label'         => 'Inventory Management',
        'children' => [
            'prod-list'     => ['href' => '../page/admin.php?page=prod-list', 'label' => 'Product List'],
            'stock-control' => ['href' => '../page/admin.php?page=stock-control', 'label' => 'Stock Control']
        ]
    ],
    'sales' => [
        'type'  => 'link',
        'href'  => '../page/admin.php?page=sales',
        'icon'  => 'bi-cart-check',
        'label' => 'Sales'
    ],
    'reports' => [
        'type'  => 'link',
        'href'  => '../page/admin.php?page=reports',
        'icon'  => 'bi-graph-up',
        'label' => 'Reports'
    ],
    'branches_parent' => [
        'type'          => 'dropdown',
        'fallback_href' => '../page/admin.php?page=branch-list', // Collapsed state shortcut
        'icon'          => 'bi-shop',
        'label'         => 'Branch Management',
        'children' => [
            'branch-list'  => ['href' => '../page/admin.php?page=branch-list', 'label' => 'Branch List'],
            'branch-staff' => ['href' => '../page/admin.php?page=branch-staff', 'label' => 'Branch Staff']
        ]
    ],
    'customers' => [
        'type'  => 'link',
        'href'  => '../page/admin.php?page=customers',
        'icon'  => 'bi-people',
        'label' => 'Customers & Contacts'
    ],
    'activity-logs' => [
        'type'  => 'link',
        'href'  => '../page/admin.php?page=activity-logs',
        'icon'  => 'bi-clock-history',
        'label' => 'Activity Logs'
    ],
];

$viewOrderModalContentFile = '../asset/modal/viewOrderModal.php';
$logoutModalContentFile = '../asset/modal/logoutModal.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JC Gas - Admin <?php echo $pageTitle; ?></title>
</head>

<body id="layoutWrapper">

    <div id="alertBannerContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1060; width: 350px;"></div>

    <header>
        <h3 class="m-0 p-0 fw-bold"><i class="bi bi-fire"></i> JC Gas Admin</h3>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</span>
        </div>
    </header>

    <aside class="text-white" id="sidebar">
        <div class="sidebar-header p-2 d-flex justify-content-center align-items-center">
            <button class="btn sidebar-toggle-btn text-white" onclick="toggleSidebar()" title="Toggle Sidebar">
                <i class="bi bi-list fs-5" id="sidebarToggleIcon"></i>
                <span class="sidebar-header-title ms-2">MENU</span>
            </button>
        </div>

        <div class="sidebar-nav">
            <ul class="navbar-nav">
                <?php foreach ($navItems as $key => $item): ?>
                    <?php if ($item['type'] === 'link'): ?>
                        <li class="nav-item <?php echo ($currentPage === $key) ? 'active' : ''; ?>">
                            <a href="<?php echo $item['href']; ?>" class="nav-link">
                                <i class="bi <?php echo $item['icon']; ?> me-2"></i>
                                <span class="nav-text"><?php echo $item['label']; ?></span>
                            </a>
                        </li>
                    <?php elseif ($item['type'] === 'dropdown'): ?>
                        <?php
                        $isChildActive = array_key_exists($currentPage, $item['children']);
                        ?>
                        <!-- Storing the quick shortcut page link in a data-attribute for JS to read -->
                        <li class="nav-item dropdown-wrapper <?php echo $isChildActive ? 'submenu-open' : ''; ?>">
                            <a href="javascript:void(0);"
                                class="nav-link dropdown-toggle-custom"
                                data-fallback="<?php echo $item['fallback_href']; ?>"
                                onclick="handleDropdownClick(this, event)">
                                <div>
                                    <i class="bi <?php echo $item['icon']; ?> me-1"></i>
                                    <span class="nav-text"><?php echo $item['label']; ?></span>
                                </div>
                                <i class="bi bi-chevron-down submenu-arrow <?php echo $isChildActive ? 'rotate' : ''; ?>"></i>
                            </a>
                            <ul class="submenu-list" style="<?php echo $isChildActive ? 'display: block;' : 'display: none;'; ?>">
                                <?php foreach ($item['children'] as $childKey => $childItem): ?>
                                    <li class="submenu-item <?php echo ($currentPage === $childKey) ? 'child-active' : ''; ?>">
                                        <a href="<?php echo $childItem['href']; ?>" class="submenu-link">
                                            <span class="nav-text"><?php echo $childItem['label']; ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="d-flex justify-content-center align-items-center p-2">
            <a href="#" id="logoutTriggerBtn" class="btn btn-sm btn-danger w-100">
                <i class="bi bi-box-arrow-right"></i><span class="nav-text ms-1">Logout</span>
            </a>
        </div>
    </aside>

    <main class="px-3" id="mainContent">
        <?php include $contentFile; ?>
    </main>

    <?php include $viewOrderModalContentFile; ?>
    <?php include $logoutModalContentFile; ?>

    <footer class="bg-dark-navy text-center">
        <p class="m-0 p-1 text-white">&copy; <?php echo date('Y'); ?> JC Gas. All rights reserved.</p>
    </footer>

    <script src="../js/sidebar.js"></script>
    <script src="../js/bannerAlert.js"></script>

</body>

</html>