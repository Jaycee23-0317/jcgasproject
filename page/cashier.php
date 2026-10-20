<?php
session_start();
require_once '../php/db_config.php';
include_once '../asset/header/header.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Cashier') {
    header("Location: ../index.html");
    exit();
}

$currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

$pageTitles = [
    'dashboard' => 'Dashboard',
    'shop' => 'Shop',
    'orders' => 'Orders',
    'reports' => 'Reports',
    'settings' => 'Settings',
    'about' => 'About'
];

$pageTitle = $pageTitles[$currentPage] ?? 'Dashboard';

$contentFile = '../asset/cashierContent/' . $currentPage . '.php';
if (!file_exists($contentFile)) {
    $contentFile = '../asset/cashierContent/dashboard.php';
}

$navItems = [
    'dashboard' => [
        'type'  => 'link',
        'href'  => '../page/cashier.php?page=dashboard',
        'icon'  => 'bi-speedometer2',
        'label' => 'Dashboard'
    ],
    'shop' => [
        'type'  => 'link',
        'href'  => '../page/cashier.php?page=shop',
        'icon'  => 'bi-shop',
        'label' => 'Shop'
    ],
    'orders' => [
        'type'  => 'link',
        'href'  => '../page/cashier.php?page=orders',
        'icon'  => 'bi-cart-check',
        'label' => 'Orders'
    ],
    'reports'
    => [
        'type'  => 'link',
        'href'  => '../page/cashier.php?page=reports',
        'icon'  => 'bi-graph-up',
        'label' => 'Reports'
    ],
    'settings_parent' => [
        'type'          => 'dropdown',
        'fallback_href' => '../page/cashier.php?page=account', // Collapsed state shortcut
        'icon'          => 'bi-person',
        'label'         => 'Settings',
        'children' => [
            'account'     => ['href' => '../page/cashier.php?page=account', 'label' => 'Account'],
            'password' => ['href' => '../page/cashier.php?page=password', 'label' => 'Password']
        ]
    ],
    'about'
    => [
        'type'  => 'link',
        'href'  => '../page/cashier.php?page=about',
        'icon'  => 'bi-info-circle',
        'label' => 'About'
    ],
];

$logoutModalContentFile = '../asset/modal/logoutModal.php';
$viewOrderModalContentFile = '../asset/modal/viewOrderModal.php';
$reviewOrderModalContentFile = '../asset/modal/reviewOrderModal.php';
$confirmOrderModalContentFile = '../asset/modal/confirmOrderModal.php';
$confirmOrderStatusModalContentFile = '../asset/modal/confirmOrderStatusModal.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JC Gas - Cashier <?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../css/shop.css">
    <link rel="stylesheet" href="../css/cashier.css">
</head>

<body id="layoutWrapper">

    <div id="alertBannerContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1060; width: 350px;"></div>

    <header>
        <h3 class="m-0 p-0 fw-bold"><i class="bi bi-fire"></i> JC Gas Cashier</h3>
        <div class="">
            <!-- <i class="bi bi-shop me-1"></i><span id="branchName">Hello</span> | -->
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

    <main id="mainContent">
        <?php include $contentFile; ?>
    </main>

    <footer class="bg-dark-navy text-center">
        <p class="m-0 p-1 text-white">&copy; <?php echo date('Y'); ?> JC Gas. All rights reserved.</p>
    </footer>

    <!-- Custom Abandon Cart Confirmation Modal -->
    <div class="modal fade" id="abandonCartModal" tabindex="-1" aria-labelledby="abandonCartModalLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow-lg text-white" style="background-color: #1e2d4a; border-radius: 12px;">

                <!-- Modal Body -->
                <div class="modal-body text-center p-4">
                    <div class="mb-3">
                        <!-- Warning Icon Container with Glowing Background -->
                        <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-25 rounded-circle" style="width: 60px; height: 60px;">
                            <i class="bi bi-cart-x-fill text-danger fs-2 animate-pulse"></i>
                        </div>
                    </div>

                    <h5 class="modal-title fw-bold text-light mb-2" id="abandonCartModalLabel">Abandon Shopping Cart?</h5>
                    <p class="text-muted small mb-4 px-2">
                        You have active items inside your selection sheet. Leaving this page will clear your cart data completely.
                    </p>

                    <!-- Actions Grid Split Layout -->
                    <div class="row g-2">
                        <div class="col-6">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-100 py-2 border-secondary text-muted" data-bs-dismiss="modal">
                                Keep Shopping
                            </button>
                        </div>
                        <div class="col-6">
                            <button type="button" id="btnConfirmAbandon" class="btn btn-sm btn-gas-danger w-100 py-2 fw-semibold">
                                Yes, Leave Page
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <?php include $viewOrderModalContentFile; ?>
    <?php include $reviewOrderModalContentFile; ?>
    <?php include $confirmOrderModalContentFile; ?>
    <?php include $confirmOrderStatusModalContentFile; ?>
    <?php include $logoutModalContentFile; ?>

    <script src="../js/cashier.js"></script>
    <script src="../js/sidebar.js"></script>
    <script src="../js/bannerAlert.js"></script>
</body>

</html>