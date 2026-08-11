<?php
if (!isset($currentUser)) {
    exit('Authentication Error');
}
?>

<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top px-4 py-2">

    <div class="container-fluid">

        <!-- Mobile Sidebar Button (REMOVED – now using the floating toggle from sidebar) -->
        <!-- <button class="btn btn-outline-primary d-lg-none me-3" id="menu-toggle">
            <i class="fas fa-bars"></i>
        </button> -->

        <!-- Search -->
        <form class="d-flex flex-grow-1">

            <div class="input-group">

                <span class="input-group-text bg-white border-end-0">
                    <i class="fas fa-search text-muted"></i>
                </span>

                <input
                    type="search"
                    class="form-control border-start-0"
                    placeholder="Search..."
                >

            </div>

        </form>

        <!-- Right Side -->

        <ul class="navbar-nav ms-auto align-items-center">

            <!-- Notifications -->

            <li class="nav-item dropdown me-2">

                <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">

                    <i class="fas fa-bell fa-lg"></i>

                    <span class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle">

                        0

                    </span>

                </a>

                <ul class="dropdown-menu dropdown-menu-end">

                    <li>

                        <h6 class="dropdown-header">

                            Notifications

                        </h6>

                    </li>

                    <li>

                        <span class="dropdown-item-text text-muted">

                            No notifications.

                        </span>

                    </li>

                </ul>

            </li>

            <!-- Messages -->

            <li class="nav-item dropdown me-3">

                <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown">

                    <i class="fas fa-envelope fa-lg"></i>

                    <span class="badge rounded-pill bg-primary position-absolute top-0 start-100 translate-middle">

                        0

                    </span>

                </a>

                <ul class="dropdown-menu dropdown-menu-end">

                    <li>

                        <h6 class="dropdown-header">

                            Messages

                        </h6>

                    </li>

                    <li>

                        <span class="dropdown-item-text text-muted">

                            No messages.

                        </span>

                    </li>

                </ul>

            </li>

            <!-- User -->

            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle d-flex align-items-center"
                   href="#"
                   data-bs-toggle="dropdown">

                    <div class="d-none d-sm-block">
                        <strong>
                            <?= htmlspecialchars($currentUser['first_name']) ?>
                        </strong>
                        <br>
                        <small class="text-muted">
                            <?= htmlspecialchars($currentUser['admin_level'] ?? 'Super Admin') ?>
                        </small>
                    </div>

                </a>
                <ul class="dropdown-menu dropdown-menu-end">

                    <li>

                        <a class="dropdown-item" href="../../profile.php">

                            <i class="fas fa-user me-2"></i>

                            Profile

                        </a>

                    </li>

                    <li>

                        <a class="dropdown-item" href="../settings/index.php">

                            <i class="fas fa-cog me-2"></i>

                            Settings

                        </a>

                    </li>

                    <li><hr class="dropdown-divider"></li>

                    <li>

                        <a class="dropdown-item text-danger" href="../../api/auth/logout.php">

                            <i class="fas fa-sign-out-alt me-2"></i>

                            Logout

                        </a>

                    </li>

                </ul>

            </li>

        </ul>

    </div>

</nav>

<!-- JavaScript removed – the sidebar now uses its own toggle (floating button + overlay) -->

<style>
.navbar{
background:#fff;
border-bottom:1px solid #ececec;
z-index:1040;
}
.navbar .form-control{
box-shadow:none;
}
.navbar .dropdown-menu{
border:none;
border-radius:15px;
box-shadow:0 8px 25px rgba(0,0,0,.08);
}
.navbar .dropdown-item{
padding:.65rem 1rem;
}
.navbar .dropdown-item:hover{
background:#f5f7fb;
}
.navbar img{
object-fit:cover;
}
.badge{
font-size:.6rem;
}
</style>