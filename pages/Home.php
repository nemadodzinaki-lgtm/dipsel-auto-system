<?php
// pages/home.php
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <?php include __DIR__ . '/../includes/header.php'; ?>

</head>

<body>

    <!-- Announcement Bar -->
    <?php include __DIR__ . '/../includes/announcement.php'; ?>

    <!-- Navigation -->
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <!-- Main Content -->
    <main>

        <!-- Hero -->
        <?php include __DIR__ . '/home/hero.php'; ?>

        <!-- Search -->
        <?php include __DIR__ . '/home/search.php'; ?>

        <!-- Featured Vehicles -->
        <?php include __DIR__ . '/home/featured.php'; ?>

        <!-- Vehicle Categories -->
        <?php include __DIR__ . '/home/categories.php'; ?>

        <!-- Workshop Services -->
        <?php include __DIR__ . '/home/services.php'; ?>

        <!-- Why Choose Us -->
        <?php include __DIR__ . '/home/why.php'; ?>

        <!-- Statistics -->
        <?php include __DIR__ . '/home/statistics.php'; ?>

        <!-- Testimonials -->
        <?php include __DIR__ . '/home/testimonials.php'; ?>

        <!-- Latest Vehicles -->
        <?php include __DIR__ . '/home/latest.php'; ?>

        <!-- Call To Action -->
        <?php include __DIR__ . '/home/cta.php'; ?>

    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <!-- JavaScript -->
    <script src="assets/js/app.js"></script>

</body>

</html>