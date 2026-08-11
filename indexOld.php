<?php
// ─── Database Connection ──────────────────────────────────────────────
include 'config/database.php';

// ─── Fetch Site Settings ──────────────────────────────────────────────
function getSetting($pdo, $key) {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['setting_value'] : '';
}

$companyName   = getSetting($pdo, 'company_name') ?: 'AutoMarket';
$logoPath      = getSetting($pdo, 'logo_path');
$footerAbout   = getSetting($pdo, 'footer_about');
$footerEmail   = getSetting($pdo, 'footer_email');
$footerPhone   = getSetting($pdo, 'footer_phone');
$footerAddress = getSetting($pdo, 'footer_address');
$facebookUrl   = getSetting($pdo, 'facebook_url');
$twitterUrl    = getSetting($pdo, 'twitter_url');
$instagramUrl  = getSetting($pdo, 'instagram_url');
$tiktokUrl     = getSetting($pdo, 'tiktok_url');

// ─── Fetch Hero Sliders ───────────────────────────────────────────────
$slides = [];
$sql = "SELECT * FROM homepage_sliders WHERE is_active = 'Yes' ORDER BY display_order ASC";
$stmt = $pdo->query($sql);
$slides = $stmt->fetchAll();

// ─── Fetch Featured Vehicles ──────────────────────────────────────────
$featuredVehicles = [];
$sql = "
    SELECT
        v.vehicle_id,
        v.make,
        v.model,
        v.manufacture_year,
        v.price,
        v.fuel_type,
        v.transmission,
        v.body_type,
        vi.image_path
    FROM vehicles v
    LEFT JOIN vehicle_images vi
        ON v.vehicle_id = vi.vehicle_id AND vi.is_primary = 'Yes'
    WHERE v.featured = 'Yes' AND v.status = 'Available'
    ORDER BY v.created_at DESC
    LIMIT 8
";
$stmt = $pdo->query($sql);
$featuredVehicles = $stmt->fetchAll();

// ─── Fetch Categories ─────────────────────────────────────────────────
$categories = [];
$sql = "SELECT DISTINCT body_type FROM vehicles WHERE status = 'Available' ORDER BY body_type ASC";
$stmt = $pdo->query($sql);
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN, 0); // flat array

// ─── Fetch All Available Vehicles ─────────────────────────────────────
$vehicles = [];
$sql = "
    SELECT
        v.vehicle_id,
        v.make,
        v.model,
        v.manufacture_year,
        v.price,
        v.fuel_type,
        v.transmission,
        v.body_type,
        vi.image_path
    FROM vehicles v
    LEFT JOIN vehicle_images vi
        ON v.vehicle_id = vi.vehicle_id AND vi.is_primary = 'Yes'
    WHERE v.status = 'Available'
    ORDER BY v.created_at DESC
";
$stmt = $pdo->query($sql);
$vehicles = $stmt->fetchAll();

// ─── Workshop Services (static) ──────────────────────────────────────
$workshopServices = [
    [
        'id'    => 1,
        'name'  => 'Oil Change',
        'icon'  => '🛢️',
        'description' => 'Keep your engine running smoothly with our premium oil change service.',
        'price' => 'R450+'
    ],
    [
        'id'    => 2,
        'name'  => 'Brake Repair',
        'icon'  => '🔧',
        'description' => 'Ensure your safety with a full brake inspection and pad replacement.',
        'price' => 'R650+'
    ],
    [
        'id'    => 3,
        'name'  => 'Tire Rotation & Balance',
        'icon'  => '⚙️',
        'description' => 'Extend tyre life and improve fuel efficiency with our rotation service.',
        'price' => 'R300+'
    ],
    [
        'id'    => 4,
        'name'  => 'Engine Diagnostics',
        'icon'  => '🔍',
        'description' => 'Modern diagnostics to pinpoint any engine issues quickly and accurately.',
        'price' => 'R550+'
    ],
    [
        'id'    => 5,
        'name'  => 'Battery Replacement',
        'icon'  => '🔋',
        'description' => 'Reliable battery testing and replacement to keep you on the road.',
        'price' => 'R800+'
    ],
    [
        'id'    => 6,
        'name'  => 'Air Conditioning Service',
        'icon'  => '❄️',
        'description' => 'Stay cool with A/C recharge, leak detection, and full system check.',
        'price' => 'R400+'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($companyName) ?> – Find Your Dream Car</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ─── Reset & Base ──────────────────────────────────────────────── */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f7fb;
            color: #1a2634;
            line-height: 1.6;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 24px;
        }

        /* ─── Header ──────────────────────────────────────────────────────── */
        header {
            background: #0b1a2e;
            color: #fff;
            padding: 18px 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .logo-img {
            height: 40px;
            width: auto;
        }
        .logo-text span {
            color: #4fc3f7;
        }

        nav {
            display: flex;
            gap: 28px;
            font-weight: 500;
        }
        nav a {
            transition: color 0.2s;
        }
        nav a:hover {
            color: #4fc3f7;
        }

        .header-actions {
            display: flex;
            gap: 16px;
            align-items: center;
        }
        .btn-outline {
            border: 2px solid #4fc3f7;
            color: #4fc3f7;
            padding: 8px 20px;
            border-radius: 40px;
            font-weight: 600;
            transition: background 0.2s, color 0.2s;
        }
        .btn-outline:hover {
            background: #4fc3f7;
            color: #0b1a2e;
        }
        .btn-solid {
            background: #4fc3f7;
            color: #0b1a2e;
            padding: 8px 24px;
            border-radius: 40px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-solid:hover {
            background: #81d4fa;
        }

        /* ─── Hero Slider ────────────────────────────────────────────────── */
        .hero {
            position: relative;
            height: 700px;
            overflow: hidden;
            border-radius: 0 0 40px 40px;
            margin-bottom: 48px;
        }

        .hero-slider {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .hero-slide {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.8s ease;
        }
        .hero-slide.active {
            opacity: 1;
            z-index: 1;
        }

        .hero-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.45);
            display: flex;
            align-items: center;
            z-index: 2;
        }

        .hero-content {
            max-width: 700px;
            color: #fff;
        }
        .hero-content h1 {
            font-size: 64px;
            font-weight: 800;
            margin-bottom: 20px;
        }
        .hero-content p {
            font-size: 22px;
            margin-bottom: 30px;
            line-height: 1.7;
        }

        .hero-btn {
            display: inline-block;
            padding: 15px 35px;
            background: #0ea5e9;
            border-radius: 40px;
            font-weight: bold;
            color: #fff;
            transition: background 0.3s;
        }
        .hero-btn:hover {
            background: #0284c7;
        }

        /* ─── Search Bar ─────────────────────────────────────────────────── */
        .search-section {
            margin: -24px auto 48px;
            position: relative;
            z-index: 10;
            max-width: 700px;
            padding: 0 24px;
        }

        .search-box {
            display: flex;
            background: #fff;
            border-radius: 60px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }
        .search-box input {
            flex: 1;
            border: none;
            padding: 18px 28px;
            font-size: 1rem;
            outline: none;
            color: #1a2634;
        }
        .search-box button {
            background: #4fc3f7;
            border: none;
            padding: 0 32px;
            font-weight: 700;
            color: #0b1a2e;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.2s;
        }
        .search-box button:hover {
            background: #81d4fa;
        }

        /* ─── Section Titles ─────────────────────────────────────────────── */
        .section-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 32px;
            color: #0b1a2e;
        }
        .section-title span {
            color: #1d4a7a;
        }

        /* ─── Car Cards ──────────────────────────────────────────────────── */
        .featured-slider,
        .car-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 28px;
            margin-bottom: 56px;
        }

        .car-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .car-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.1);
        }
        .car-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #d9e2ec;
        }
        .car-info {
            padding: 20px 20px 24px;
        }
        .car-info h3 {
            font-size: 1.3rem;
            margin-bottom: 6px;
        }
        .car-info .details {
            display: flex;
            gap: 12px;
            font-size: 0.9rem;
            color: #4a5c6e;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }
        .car-info .price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1d4a7a;
        }
        .car-info .btn-view {
            display: inline-block;
            margin-top: 12px;
            background: #0b1a2e;
            color: #fff;
            padding: 8px 20px;
            border-radius: 40px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .car-info .btn-view:hover {
            background: #1d4a7a;
        }

        /* ─── Workshop Services ──────────────────────────────────────────── */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 28px;
            margin-bottom: 56px;
        }

        .service-card {
            background: #fff;
            border-radius: 20px;
            padding: 28px 20px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .service-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.1);
        }
        .service-card .icon {
            font-size: 3.5rem;
            display: block;
            margin-bottom: 12px;
        }
        .service-card h3 {
            font-size: 1.3rem;
            margin-bottom: 8px;
        }
        .service-card p {
            color: #4a5c6e;
            font-size: 0.95rem;
            margin-bottom: 16px;
        }
        .service-card .service-price {
            font-weight: 700;
            color: #1d4a7a;
            font-size: 1.1rem;
        }
        .service-card .btn-service {
            display: inline-block;
            margin-top: 12px;
            background: #0b1a2e;
            color: #fff;
            padding: 8px 20px;
            border-radius: 40px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .service-card .btn-service:hover {
            background: #1d4a7a;
        }

        /* ─── Categories ─────────────────────────────────────────────────── */
        .category-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 48px;
        }
        .category-pill {
            background: #e9eef3;
            padding: 10px 28px;
            border-radius: 40px;
            font-weight: 600;
            color: #1a2634;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            border: none;
            font-size: 1rem;
        }
        .category-pill:hover,
        .category-pill.active {
            background: #0b1a2e;
            color: #fff;
        }

        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 0;
            font-size: 1.2rem;
            color: #4a5c6e;
        }

        /* ─── Testimonials ───────────────────────────────────────────────── */
        .testimonials {
            background: #e9eef3;
            border-radius: 40px;
            padding: 48px 40px;
            margin: 56px 0;
        }
        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 32px;
        }
        .testimonial-item {
            background: #fff;
            padding: 24px;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
        }
        .testimonial-item p {
            font-style: italic;
            margin-bottom: 12px;
        }
        .testimonial-item .author {
            font-weight: 700;
            color: #0b1a2e;
        }

        /* ─── Footer ─────────────────────────────────────────────────────── */
        footer {
            background: #0b1a2e;
            color: #b0c4d9;
            padding: 40px 0 24px;
            border-radius: 40px 40px 0 0;
            margin-top: 48px;
        }
        .footer-inner {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 32px;
        }
        .footer-col h4 {
            color: #fff;
            margin-bottom: 12px;
        }
        .footer-col ul {
            list-style: none;
        }
        .footer-col ul li {
            margin-bottom: 8px;
        }
        .footer-col ul li a:hover {
            color: #4fc3f7;
        }
        .footer-bottom {
            border-top: 1px solid #1d3a5a;
            padding-top: 20px;
            margin-top: 32px;
            text-align: center;
            font-size: 0.9rem;
        }

        /* ─── Social Icons in Footer ────────────────────────────────────── */
        .social-icons {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }
        .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #1d3a5a;
            color: #b0c4d9;
            transition: background 0.2s, color 0.2s;
        }
        .social-icons a:hover {
            background: #4fc3f7;
            color: #0b1a2e;
        }

        /* ─── Responsive ─────────────────────────────────────────────────── */
        @media (max-width: 900px) {
            .header-inner {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            nav {
                justify-content: center;
                flex-wrap: wrap;
                gap: 16px;
            }
            .header-actions {
                justify-content: center;
            }
            .hero-content h1 {
                font-size: 40px;
            }
            .hero-content p {
                font-size: 18px;
            }
            .hero {
                height: 500px;
            }
        }

        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 28px;
            }
            .hero-content p {
                font-size: 16px;
            }
            .hero {
                height: 400px;
            }
            .section-title {
                font-size: 1.6rem;
            }
            .search-box input {
                padding: 14px 16px;
            }
            .search-box button {
                padding: 0 18px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>

    <!-- ═══ HEADER ═══ -->
    <header>
        <div class="container header-inner">
            <div class="logo">
                <?php if (!empty($logoPath)): ?>
                    <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($companyName) ?>" class="logo-img">
                <?php endif; ?>
                <span class="logo-text"><?= htmlspecialchars($companyName) ?></span>
            </div>
            <nav>
                <a href="#home">Home</a>
                <a href="#cars">Cars</a>
                <a href="#">Buy</a>
                <a href="#workshop">Workshop</a>
                <a href="#">Sell</a>
                <a href="#footer">Contact</a>
            </nav>
            <div class="header-actions">
                <a href="login.php" class="btn-outline">Login</a>
                <a href="register.php" class="btn-solid">Register</a>
            </div>
        </div>
    </header>

    <!-- ═══ HERO SLIDER ═══ -->
    <section class="hero" id="home">
        <div class="hero-slider">
            <?php foreach ($slides as $index => $slide): ?>
                <div class="hero-slide <?= $index === 0 ? 'active' : '' ?>">
                    <img src="<?= htmlspecialchars($slide['image_path']) ?>" alt="<?= htmlspecialchars($slide['title']) ?>">
                    <div class="hero-overlay">
                        <div class="container">
                            <div class="hero-content">
                                <h1><?= htmlspecialchars($slide['title']) ?></h1>
                                <p><?= htmlspecialchars($slide['subtitle']) ?></p>
                                <a href="<?= htmlspecialchars($slide['button_link']) ?>" class="hero-btn">
                                    <?= htmlspecialchars($slide['button_text']) ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ═══ SEARCH BAR ═══ -->
    <div class="search-section">
        <div class="search-box" id="searchBox">
            <input type="text" id="searchInput" placeholder="Search by brand, model, or category..." />
            <button id="searchBtn">Search</button>
        </div>
    </div>

    <!-- ═══ MAIN CONTENT ═══ -->
    <div class="container">

        <!-- Featured Vehicles -->
        <h2 class="section-title" id="cars">Featured <span>Vehicles</span></h2>
        <div class="featured-slider">
            <?php foreach ($featuredVehicles as $vehicle): ?>
                <div class="car-card">
                    <img src="<?= htmlspecialchars($vehicle['image_path']) ?>" alt="<?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?>">
                    <div class="car-info">
                        <h3><?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?></h3>
                        <div class="details">
                            <span><?= htmlspecialchars($vehicle['manufacture_year']) ?></span>
                            <span><?= htmlspecialchars($vehicle['fuel_type']) ?></span>
                            <span><?= htmlspecialchars($vehicle['transmission']) ?></span>
                        </div>
                        <div class="price">R<?= number_format($vehicle['price'], 2) ?></div>
                        <a href="vehicle-details.php?id=<?= $vehicle['vehicle_id'] ?>" class="btn-view">View Details</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Categories -->
        <h2 class="section-title">Browse by <span>Category</span></h2>
        <div class="category-grid" id="categoryFilter">
            <button class="category-pill active" data-category="all">All</button>
            <?php foreach ($categories as $category): ?>
                <button class="category-pill" data-category="<?= strtolower(htmlspecialchars($category)) ?>">
                    <?= htmlspecialchars($category) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- All Cars (filterable) -->
        <div id="carListings">
            <div class="car-grid" id="carGrid">
                <?php foreach ($vehicles as $vehicle): ?>
                    <div class="car-card"
                         data-category="<?= strtolower(htmlspecialchars($vehicle['body_type'])) ?>"
                         data-search="<?= strtolower(htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model'] . ' ' . $vehicle['body_type'] . ' ' . $vehicle['fuel_type'] . ' ' . $vehicle['transmission'])) ?>">
                        <img src="<?= htmlspecialchars($vehicle['image_path']) ?>" alt="<?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?>">
                        <div class="car-info">
                            <h3><?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?></h3>
                            <div class="details">
                                <span><?= htmlspecialchars($vehicle['manufacture_year']) ?></span>
                                <span><?= htmlspecialchars($vehicle['fuel_type']) ?></span>
                                <span><?= htmlspecialchars($vehicle['transmission']) ?></span>
                            </div>
                            <div class="price">R<?= number_format($vehicle['price'], 2) ?></div>
                            <a href="vehicle-details.php?id=<?= $vehicle['vehicle_id'] ?>" class="btn-view">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ─── WORKSHOP SERVICES ─── -->
        <h2 class="section-title" id="workshop">Our <span>Workshop Services</span></h2>
        <div class="services-grid">
            <?php foreach ($workshopServices as $service): ?>
                <div class="service-card">
                    <span class="icon"><?= $service['icon'] ?></span>
                    <h3><?= htmlspecialchars($service['name']) ?></h3>
                    <p><?= htmlspecialchars($service['description']) ?></p>
                    <div class="service-price"><?= htmlspecialchars($service['price']) ?></div>
                    <a href="#" class="btn-service">Book Now</a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Testimonials -->
        <div class="testimonials">
            <h2 class="section-title" style="margin-bottom: 24px;">What Our <span>Customers Say</span></h2>
            <div class="testimonial-grid">
                <div class="testimonial-item">
                    <p>“Found my perfect SUV within a week. The search filters made it so easy.”</p>
                    <div class="author">— Sarah K.</div>
                </div>
                <div class="testimonial-item">
                    <p>“Selling my car was hassle‑free. Got a fair price and quick payment.”</p>
                    <div class="author">— Michael R.</div>
                </div>
                <div class="testimonial-item">
                    <p>“The financing options helped me afford a brand new Tesla. Highly recommend!”</p>
                    <div class="author">— Jessica W.</div>
                </div>
            </div>
        </div>

    </div>

    <!-- ═══ FOOTER ═══ -->
    <footer>
        <div class="container" id="footer">
            <div class="footer-inner">
                <div class="footer-col">
                    <h4><?= htmlspecialchars($companyName) ?></h4>
                    <ul>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>For Buyers</h4>
                    <ul>
                        <li><a href="#">Search Cars</a></li>
                        <li><a href="#">Financing</a></li>
                        <li><a href="#">Insurance</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>For Sellers</h4>
                    <ul>
                        <li><a href="#">List Your Car</a></li>
                        <li><a href="#">Pricing</a></li>
                        <li><a href="#">Support</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Contact</h4>
                    <ul>
                        <li><a href="mailto:<?= htmlspecialchars($footerEmail) ?>"><?= htmlspecialchars($footerEmail) ?></a></li>
                        <li><a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $footerPhone)) ?>"><?= htmlspecialchars($footerPhone) ?></a></li>
                        <li><?= htmlspecialchars($footerAddress) ?></li>
                    </ul>
                    <div class="social-icons">
                        <?php if (!empty($facebookUrl)): ?>
                            <a href="<?= htmlspecialchars($facebookUrl) ?>" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($twitterUrl)): ?>
                            <a href="<?= htmlspecialchars($twitterUrl) ?>" aria-label="Twitter"><i class="fab fa-x-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($instagramUrl)): ?>
                            <a href="<?= htmlspecialchars($instagramUrl) ?>" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty($tiktokUrl)): ?>
                            <a href="<?= htmlspecialchars($tiktokUrl) ?>" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($companyName) ?>. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- ═══ JAVASCRIPT ═══ -->
    <script>
        // ─── Hero Slider ────────────────────────────────────────────────────
        (function() {
            const slides = document.querySelectorAll(".hero-slide");
            let current = 0;

            function changeSlide() {
                slides[current].classList.remove("active");
                current = (current + 1) % slides.length;
                slides[current].classList.add("active");
            }
            setInterval(changeSlide, 5000);
        })();

        // ─── Search & Filter ──────────────────────────────────────────────
        (function() {
            const searchInput = document.getElementById('searchInput');
            const searchBtn = document.getElementById('searchBtn');
            const categoryPills = document.querySelectorAll('.category-pill');
            const carCards = document.querySelectorAll('#carGrid .car-card');

            function filterCars() {
                const query = searchInput.value.trim().toLowerCase();
                let activeCategory = 'all';
                categoryPills.forEach(pill => {
                    if (pill.classList.contains('active')) {
                        activeCategory = pill.dataset.category;
                    }
                });

                let visibleCount = 0;
                carCards.forEach(card => {
                    const searchData = card.dataset.search || '';
                    const category = card.dataset.category || '';
                    const matchesSearch = searchData.includes(query);
                    const matchesCategory = (activeCategory === 'all' || category === activeCategory);

                    if (matchesSearch && matchesCategory) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                const grid = document.getElementById('carGrid');
                let noMsg = grid.querySelector('.no-results');
                if (visibleCount === 0) {
                    if (!noMsg) {
                        noMsg = document.createElement('div');
                        noMsg.className = 'no-results';
                        noMsg.textContent = '😕 No cars match your criteria. Try a different search!';
                        grid.appendChild(noMsg);
                    }
                    noMsg.style.display = '';
                } else {
                    if (noMsg) noMsg.style.display = 'none';
                }
            }

            searchBtn.addEventListener('click', filterCars);
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') filterCars();
                filterCars(); // live filtering
            });

            categoryPills.forEach(pill => {
                pill.addEventListener('click', function() {
                    categoryPills.forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                    filterCars();
                });
            });

            // Initial filter
            filterCars();
        })();

        // ─── Scroll restoration ──────────────────────────────────────────
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        window.onbeforeunload = function() {
            window.scrollTo(0, 0);
        };
        window.addEventListener('load', function() {
            window.scrollTo(0, 0);
        });
    </script>

</body>
</html>