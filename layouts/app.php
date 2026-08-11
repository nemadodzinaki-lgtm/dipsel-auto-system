<?php

$page = $page ?? 'home';

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<div class="app-container">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="content">

        <?php

        $pageFile = __DIR__ . "/../pages/$page.php";

        if(file_exists($pageFile)){
            include $pageFile;
        }else{
            echo "<h2>404 Page Not Found</h2>";
        }

        ?>

    </main>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>