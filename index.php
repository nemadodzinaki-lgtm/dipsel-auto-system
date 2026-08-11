<?php
// ─── Database Connection ──────────────────────────────────────────────
include 'config/database.php';

// ─── Fetch Site Settings ──────────────────────────────────────────────
function getSetting($pdo, $key)
{
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
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

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
        'id'          => 1,
        'name'        => 'Oil Change',
        'icon'        => 'fa-oil-can',
        'description' => 'Keep your engine running smoothly with our premium oil change service.',
        'price'       => 'R450+'
    ],
    [
        'id'          => 2,
        'name'        => 'Brake Repair',
        'icon'        => 'fa-brake-warning',
        'description' => 'Ensure your safety with a full brake inspection and pad replacement.',
        'price'       => 'R650+'
    ],
    [
        'id'          => 3,
        'name'        => 'Tire Rotation & Balance',
        'icon'        => 'fa-circle-notch',
        'description' => 'Extend tyre life and improve fuel efficiency with our rotation service.',
        'price'       => 'R300+'
    ],
    [
        'id'          => 4,
        'name'        => 'Engine Diagnostics',
        'icon'        => 'fa-microscope',
        'description' => 'Modern diagnostics to pinpoint any engine issues quickly and accurately.',
        'price'       => 'R550+'
    ],
    [
        'id'          => 5,
        'name'        => 'Battery Replacement',
        'icon'        => 'fa-bolt',
        'description' => 'Reliable battery testing and replacement to keep you on the road.',
        'price'       => 'R800+'
    ],
    [
        'id'          => 6,
        'name'        => 'Air Conditioning Service',
        'icon'        => 'fa-snowflake',
        'description' => 'Stay cool with A/C recharge, leak detection, and full system check.',
        'price'       => 'R400+'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($companyName) ?> – Find Your Dream Car</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800;14..32,900&display=swap" rel="stylesheet" />
    <style>
        /* ═══════════════════════════════════════════════════
           RESET & BASE
           ═══════════════════════════════════════════════════ */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --navy: #0b1a2e;
            --navy-light: #132b47;
            --teal: #2dd4bf;
            --teal-dark: #14b8a6;
            --blue: #4fc3f7;
            --blue-dark: #0ea5e9;
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 12px 48px rgba(0, 0, 0, 0.12);
            --shadow-xl: 0 20px 60px rgba(0, 0, 0, 0.18);
            --radius: 16px;
            --radius-full: 9999px;
            --transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--gray-50);
            color: var(--gray-800);
            line-height: 1.6;
            overflow-x: hidden;
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

        /* ═══════════════════════════════════════════════════
           SCROLL ANIMATIONS
           ═══════════════════════════════════════════════════ */
        .reveal {
            opacity: 0;
            transform: translateY(40px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }

        .reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .reveal-left {
            opacity: 0;
            transform: translateX(-40px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }

        .reveal-left.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .reveal-right {
            opacity: 0;
            transform: translateX(40px);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }

        .reveal-right.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .reveal-scale {
            opacity: 0;
            transform: scale(0.92);
            transition: opacity 0.8s ease, transform 0.8s ease;
        }

        .reveal-scale.visible {
            opacity: 1;
            transform: scale(1);
        }

        /* ═══════════════════════════════════════════════════
           HEADER
           ═══════════════════════════════════════════════════ */
        header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 12px 0;
            background: rgba(11, 26, 46, 0.85);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            transition: background 0.3s, box-shadow 0.3s;
        }

        header.scrolled {
            background: rgba(11, 26, 46, 0.96);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }

        .header-inner {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: var(--white);
        }

        .logo-img {
            height: 38px;
            width: auto;
            border-radius: 8px;
        }

        .logo-text span {
            background: linear-gradient(135deg, var(--teal), var(--blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Desktop Nav */
        .nav-desktop {
            display: flex;
            align-items: center;
            gap: 32px;
        }

        .nav-desktop a {
            color: rgba(255, 255, 255, 0.75);
            font-weight: 500;
            font-size: 0.95rem;
            transition: color 0.2s;
            position: relative;
        }

        .nav-desktop a::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--teal), var(--blue));
            transition: width 0.3s;
        }

        .nav-desktop a:hover {
            color: var(--white);
        }

        .nav-desktop a:hover::after {
            width: 100%;
        }

        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn-outline {
            border: 2px solid rgba(255, 255, 255, 0.25);
            color: var(--white);
            padding: 7px 20px;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.25s;
        }

        .btn-outline:hover {
            background: var(--white);
            color: var(--navy);
            border-color: var(--white);
        }

        .btn-solid {
            background: linear-gradient(135deg, var(--teal), var(--blue-dark));
            color: var(--navy);
            padding: 7px 22px;
            border-radius: var(--radius-full);
            font-weight: 700;
            font-size: 0.9rem;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
        }

        .btn-solid:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(45, 212, 191, 0.35);
        }

        /* Mobile hamburger */
        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 4px;
            background: none;
            border: none;
        }

        .hamburger span {
            display: block;
            width: 26px;
            height: 2.5px;
            background: var(--white);
            border-radius: 4px;
            transition: all 0.3s;
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }

        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }

        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        /* Mobile nav */
        .nav-mobile {
            display: none;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            padding: 20px 0 12px;
            width: 100%;
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            margin-top: 8px;
        }

        .nav-mobile.open {
            display: flex;
        }

        .nav-mobile a {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
            font-size: 1rem;
            padding: 6px 0;
            transition: color 0.2s;
        }

        .nav-mobile a:hover {
            color: var(--white);
        }

        .nav-mobile .header-actions {
            flex-direction: column;
            width: 100%;
            gap: 10px;
            margin-top: 4px;
        }

        .nav-mobile .header-actions a {
            width: 100%;
            text-align: center;
            padding: 10px;
            border-radius: var(--radius-full);
        }

        .nav-mobile .btn-outline {
            border-color: rgba(255, 255, 255, 0.2);
        }

        /* ═══════════════════════════════════════════════════
           HERO SLIDER
           ═══════════════════════════════════════════════════ */
        .hero {
            position: relative;
            height: 100vh;
            min-height: 600px;
            max-height: 900px;
            overflow: hidden;
            margin-top: 0;
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
            transition: opacity 1s ease;
        }

        .hero-slide.active {
            opacity: 1;
            z-index: 1;
        }

        .hero-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.55) saturate(1.1);
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg,
                    rgba(11, 26, 46, 0.75) 0%,
                    rgba(11, 26, 46, 0.30) 70%,
                    rgba(11, 26, 46, 0.10) 100%);
            display: flex;
            align-items: center;
            z-index: 2;
        }

        .hero-content {
            max-width: 720px;
            color: var(--white);
            padding-top: 40px;
        }

        .hero-content .badge {
            display: inline-block;
            background: rgba(45, 212, 191, 0.20);
            backdrop-filter: blur(8px);
            padding: 6px 18px;
            border-radius: var(--radius-full);
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: var(--teal);
            border: 1px solid rgba(45, 212, 191, 0.25);
            margin-bottom: 20px;
        }

        .hero-content h1 {
            font-size: clamp(2.8rem, 6vw, 4.8rem);
            font-weight: 900;
            line-height: 1.08;
            margin-bottom: 16px;
            letter-spacing: -1px;
        }

        .hero-content h1 .highlight {
            background: linear-gradient(135deg, var(--teal), var(--blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-content p {
            font-size: clamp(1.05rem, 1.4vw, 1.3rem);
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 32px;
            line-height: 1.8;
            max-width: 560px;
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .hero-btn-primary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 36px;
            background: linear-gradient(135deg, var(--teal), var(--blue-dark));
            border-radius: var(--radius-full);
            font-weight: 700;
            font-size: 1rem;
            color: var(--navy);
            transition: transform 0.25s, box-shadow 0.25s;
            border: none;
            cursor: pointer;
        }

        .hero-btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(45, 212, 191, 0.40);
        }

        .hero-btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            background: rgba(255, 255, 255, 0.10);
            backdrop-filter: blur(8px);
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 1rem;
            color: var(--white);
            border: 1px solid rgba(255, 255, 255, 0.20);
            transition: background 0.25s, transform 0.25s;
            cursor: pointer;
        }

        .hero-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.20);
            transform: translateY(-3px);
        }

        /* Hero slide indicators */
        .hero-indicators {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 3;
        }

        .hero-indicators span {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.30);
            cursor: pointer;
            transition: all 0.3s;
        }

        .hero-indicators span.active {
            background: var(--teal);
            width: 28px;
            border-radius: 6px;
        }

        /* ═══════════════════════════════════════════════════
           FLOATING SEARCH BAR
           ═══════════════════════════════════════════════════ */
        .search-float {
            position: relative;
            z-index: 10;
            max-width: 820px;
            margin: -30px auto 50px;
            padding: 0 24px;
        }

        .search-box {
            display: flex;
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: box-shadow 0.3s;
        }

        .search-box:focus-within {
            box-shadow: 0 20px 60px rgba(11, 26, 46, 0.18);
        }

        .search-box input {
            flex: 1;
            border: none;
            padding: 18px 28px;
            font-size: 1rem;
            outline: none;
            color: var(--gray-800);
            font-weight: 500;
            background: transparent;
            min-width: 0;
        }

        .search-box input::placeholder {
            color: var(--gray-400);
            font-weight: 400;
        }

        .search-box button {
            background: linear-gradient(135deg, var(--teal), var(--blue-dark));
            border: none;
            padding: 0 34px;
            font-weight: 700;
            color: var(--navy);
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.25s;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .search-box button:hover {
            filter: brightness(1.05);
            padding: 0 40px;
        }

        /* ═══════════════════════════════════════════════════
           STATS BAR
           ═══════════════════════════════════════════════════ */
        .stats-bar {
            background: var(--white);
            border-radius: var(--radius);
            padding: 32px 40px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 56px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 24px;
            border: 1px solid var(--gray-200);
        }

        .stat-item {
            text-align: center;
        }

        .stat-item .number {
            font-size: 2.2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--navy), var(--teal-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .stat-item .label {
            font-size: 0.9rem;
            color: var(--gray-500);
            font-weight: 500;
            margin-top: 2px;
        }

        /* ═══════════════════════════════════════════════════
           SECTION TITLES
           ═══════════════════════════════════════════════════ */
        .section-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 32px;
        }

        .section-title {
            font-size: clamp(1.8rem, 3vw, 2.4rem);
            font-weight: 800;
            color: var(--gray-900);
            letter-spacing: -0.5px;
        }

        .section-title .accent {
            background: linear-gradient(135deg, var(--teal), var(--blue-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            color: var(--gray-500);
            font-size: 1.05rem;
            max-width: 600px;
        }

        .section-link {
            font-weight: 600;
            color: var(--teal-dark);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: gap 0.25s;
        }

        .section-link:hover {
            gap: 12px;
        }

        /* ═══════════════════════════════════════════════════
           WHY CHOOSE US
           ═══════════════════════════════════════════════════ */
        .why-section {
            padding: 20px 0 40px;
        }

        .why-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 28px;
            margin-top: 8px;
        }

        .why-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 32px 24px;
            text-align: center;
            border: 1px solid var(--gray-200);
            transition: all 0.3s;
            box-shadow: var(--shadow-sm);
        }

        .why-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: var(--teal);
        }

        .why-card .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(45, 212, 191, 0.12), rgba(79, 195, 247, 0.12));
            font-size: 1.8rem;
            color: var(--teal-dark);
            margin-bottom: 16px;
        }

        .why-card h3 {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .why-card p {
            color: var(--gray-500);
            font-size: 0.95rem;
        }

        /* ═══════════════════════════════════════════════════
           CAR CARDS
           ═══════════════════════════════════════════════════ */
        .car-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 28px;
            margin-bottom: 56px;
        }

        .car-card {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid var(--gray-200);
            transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-sm);
            position: relative;
        }

        .car-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--teal);
        }

        .car-card .img-wrap {
            position: relative;
            overflow: hidden;
            background: var(--gray-200);
            height: 210px;
        }

        .car-card .img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .car-card:hover .img-wrap img {
            transform: scale(1.05);
        }

        .car-card .card-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            background: linear-gradient(135deg, var(--teal), var(--blue-dark));
            color: var(--navy);
            font-weight: 700;
            font-size: 0.7rem;
            padding: 4px 14px;
            border-radius: var(--radius-full);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .car-card .wishlist-btn {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(8px);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.25s;
            color: var(--gray-400);
            font-size: 1rem;
        }

        .car-card .wishlist-btn:hover {
            background: var(--white);
            color: #ef4444;
            transform: scale(1.1);
        }

        .car-card .wishlist-btn.liked {
            color: #ef4444;
        }

        .car-info {
            padding: 20px 20px 24px;
        }

        .car-info .car-title {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .car-info .car-title .year {
            font-weight: 600;
            color: var(--gray-400);
            font-size: 0.85rem;
        }

        .car-info .specs {
            display: flex;
            gap: 14px;
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .car-info .specs span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .car-info .specs i {
            font-size: 0.7rem;
            color: var(--gray-400);
        }

        .car-info .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 4px;
        }

        .car-info .price {
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--navy);
        }

        .car-info .btn-view {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--navy);
            color: var(--white);
            padding: 8px 20px;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.25s;
            border: none;
            cursor: pointer;
        }

        .car-info .btn-view:hover {
            background: var(--teal-dark);
            gap: 12px;
        }

        /* ═══════════════════════════════════════════════════
           CATEGORY PILLS (enhanced)
           ═══════════════════════════════════════════════════ */
        .category-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 32px;
        }

        .category-pill {
            background: var(--white);
            padding: 10px 24px;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--gray-600);
            cursor: pointer;
            transition: all 0.25s;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .category-pill:hover {
            border-color: var(--teal);
            color: var(--navy);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .category-pill.active {
            background: linear-gradient(135deg, var(--teal), var(--blue-dark));
            color: var(--navy);
            border-color: transparent;
            box-shadow: 0 8px 24px rgba(45, 212, 191, 0.30);
        }

        .category-pill .count {
            background: rgba(0, 0, 0, 0.06);
            padding: 0 10px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .category-pill.active .count {
            background: rgba(11, 26, 46, 0.15);
        }

        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            font-size: 1.1rem;
            color: var(--gray-500);
            background: var(--white);
            border-radius: var(--radius);
            border: 1px dashed var(--gray-300);
        }

        .no-results i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 12px;
            color: var(--gray-300);
        }

        /* ═══════════════════════════════════════════════════
           WORKSHOP SERVICES
           ═══════════════════════════════════════════════════ */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 28px;
            margin-bottom: 56px;
        }

        .service-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 28px 20px;
            text-align: center;
            border: 1px solid var(--gray-200);
            transition: all 0.35s;
            box-shadow: var(--shadow-sm);
        }

        .service-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
            border-color: var(--teal);
        }

        .service-card .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(45, 212, 191, 0.10), rgba(79, 195, 247, 0.10));
            font-size: 1.8rem;
            color: var(--teal-dark);
            margin-bottom: 14px;
        }

        .service-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .service-card p {
            color: var(--gray-500);
            font-size: 0.9rem;
            margin-bottom: 14px;
            line-height: 1.5;
        }

        .service-card .service-price {
            font-weight: 700;
            color: var(--teal-dark);
            font-size: 1rem;
        }

        .service-card .btn-service {
            display: inline-block;
            margin-top: 12px;
            background: var(--navy);
            color: var(--white);
            padding: 8px 22px;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.25s;
            border: none;
            cursor: pointer;
        }

        .service-card .btn-service:hover {
            background: var(--teal-dark);
            transform: translateY(-2px);
        }

        /* ═══════════════════════════════════════════════════
           TESTIMONIALS
           ═══════════════════════════════════════════════════ */
        .testimonials {
            background: var(--white);
            border-radius: var(--radius);
            padding: 48px 40px;
            margin: 20px 0 56px;
            border: 1px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }

        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 32px;
            margin-top: 8px;
        }

        .testimonial-item {
            padding: 20px 0;
        }

        .testimonial-item .stars {
            color: #f59e0b;
            font-size: 0.9rem;
            margin-bottom: 8px;
            letter-spacing: 2px;
        }

        .testimonial-item p {
            font-style: italic;
            color: var(--gray-700);
            font-size: 1rem;
            line-height: 1.7;
            margin-bottom: 12px;
        }

        .testimonial-item .author-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .testimonial-item .avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--teal), var(--blue-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 700;
            font-size: 1.1rem;
        }

        .testimonial-item .author {
            font-weight: 700;
            color: var(--gray-800);
        }

        .testimonial-item .role {
            font-size: 0.8rem;
            color: var(--gray-400);
        }

        /* ═══════════════════════════════════════════════════
           NEWSLETTER
           ═══════════════════════════════════════════════════ */
        .newsletter {
            background: linear-gradient(135deg, var(--navy), var(--navy-light));
            border-radius: var(--radius);
            padding: 48px 40px;
            margin-bottom: 48px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 32px;
            border: 1px solid rgba(255, 255, 255, 0.06);
        }

        .newsletter-content h2 {
            color: var(--white);
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .newsletter-content p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.95rem;
        }

        .newsletter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            min-width: 280px;
            flex: 1;
            max-width: 480px;
        }

        .newsletter-form input {
            flex: 1;
            padding: 14px 22px;
            border-radius: var(--radius-full);
            border: none;
            outline: none;
            font-size: 0.95rem;
            background: rgba(255, 255, 255, 0.08);
            color: var(--white);
            min-width: 160px;
        }

        .newsletter-form input::placeholder {
            color: rgba(255, 255, 255, 0.4);
        }

        .newsletter-form button {
            padding: 14px 32px;
            border-radius: var(--radius-full);
            border: none;
            background: linear-gradient(135deg, var(--teal), var(--blue-dark));
            color: var(--navy);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s;
            white-space: nowrap;
        }

        .newsletter-form button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(45, 212, 191, 0.35);
        }

        /* ═══════════════════════════════════════════════════
           BACK TO TOP
           ═══════════════════════════════════════════════════ */
        .back-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--teal), var(--blue-dark));
            color: var(--navy);
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s;
            opacity: 0;
            pointer-events: none;
            transform: translateY(20px);
        }

        .back-top.visible {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .back-top:hover {
            transform: translateY(-4px) scale(1.05);
            box-shadow: 0 12px 40px rgba(45, 212, 191, 0.40);
        }

        /* ═══════════════════════════════════════════════════
           FOOTER
           ═══════════════════════════════════════════════════ */
        footer {
            background: var(--navy);
            color: rgba(255, 255, 255, 0.7);
            padding: 48px 0 24px;
            border-radius: var(--radius) var(--radius) 0 0;
            margin-top: 20px;
        }

        .footer-inner {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 40px;
        }

        .footer-col h4 {
            color: var(--white);
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 14px;
        }

        .footer-col ul {
            list-style: none;
        }

        .footer-col ul li {
            margin-bottom: 8px;
        }

        .footer-col ul li a {
            color: rgba(255, 255, 255, 0.6);
            transition: color 0.2s;
            font-size: 0.9rem;
        }

        .footer-col ul li a:hover {
            color: var(--teal);
        }

        .footer-col .social-icons {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .footer-col .social-icons a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.06);
            color: rgba(255, 255, 255, 0.6);
            transition: all 0.25s;
            font-size: 0.95rem;
        }

        .footer-col .social-icons a:hover {
            background: var(--teal);
            color: var(--navy);
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.06);
            padding-top: 24px;
            margin-top: 40px;
            text-align: center;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.4);
        }

        .footer-bottom .heart {
            color: #ef4444;
        }

        /* ═══════════════════════════════════════════════════
           RESPONSIVE
           ═══════════════════════════════════════════════════ */
        @media (max-width: 1024px) {
            .hero-content h1 {
                font-size: clamp(2.4rem, 5vw, 3.6rem);
            }
        }

        @media (max-width: 820px) {
            .nav-desktop {
                display: none;
            }

            .hamburger {
                display: flex;
            }

            .header-inner {
                flex-wrap: wrap;
            }

            .hero {
                height: 80vh;
                min-height: 500px;
                max-height: 700px;
            }

            .hero-content {
                padding-top: 20px;
            }

            .stats-bar {
                padding: 24px 20px;
                gap: 16px;
            }

            .stat-item .number {
                font-size: 1.6rem;
            }

            .testimonials {
                padding: 32px 20px;
            }

            .newsletter {
                padding: 32px 24px;
                flex-direction: column;
                text-align: center;
            }

            .newsletter-form {
                max-width: 100%;
                width: 100%;
            }

            .search-box {
                flex-wrap: wrap;
                border-radius: var(--radius);
            }

            .search-box input {
                width: 100%;
                padding: 14px 20px;
            }

            .search-box button {
                width: 100%;
                justify-content: center;
                padding: 14px;
                border-radius: 0 0 var(--radius) var(--radius);
            }

            .search-box button:hover {
                padding: 14px;
            }
        }

        @media (max-width: 480px) {
            .hero {
                height: 70vh;
                min-height: 420px;
            }

            .hero-content h1 {
                font-size: 2rem;
            }

            .hero-content p {
                font-size: 0.95rem;
            }

            .hero-buttons {
                flex-direction: column;
                width: 100%;
            }

            .hero-buttons a,
            .hero-buttons button {
                width: 100%;
                justify-content: center;
            }

            .hero-indicators {
                bottom: 20px;
            }

            .search-float {
                margin: -20px auto 30px;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .car-grid {
                grid-template-columns: 1fr;
            }

            .services-grid {
                grid-template-columns: 1fr 1fr;
            }

            .footer-inner {
                grid-template-columns: 1fr 1fr;
                gap: 24px;
            }

            .why-grid {
                grid-template-columns: 1fr 1fr;
            }

            .testimonial-grid {
                grid-template-columns: 1fr;
            }

            .back-top {
                bottom: 16px;
                right: 16px;
                width: 42px;
                height: 42px;
                font-size: 1rem;
            }
        }

        @media (max-width: 400px) {
            .services-grid {
                grid-template-columns: 1fr;
            }

            .why-grid {
                grid-template-columns: 1fr;
            }

            .footer-inner {
                grid-template-columns: 1fr;
            }

            .stats-bar {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- ═══ HEADER ═══ -->
    <header id="siteHeader">
        <div class="container header-inner">
            <div class="logo">
                <?php if (!empty($logoPath)): ?>
                    <img src="<?= htmlspecialchars($logoPath) ?>" alt="<?= htmlspecialchars($companyName) ?>" class="logo-img" />
                <?php endif; ?>
                <span class="logo-text"><?= htmlspecialchars($companyName) ?></span>
            </div>

            <nav class="nav-desktop">
                <a href="#home">Home</a>
                <a href="#cars">Cars</a>
                <a href="#workshop">Workshop</a>
                <a href="#footer">Contact</a>
            </nav>

            <div class="header-actions">
                <a href="login.php" class="btn-outline">Login</a>
                <a href="register.php" class="btn-solid">Register</a>
            </div>

            <button class="hamburger" id="hamburger" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>

            <nav class="nav-mobile" id="navMobile">
                <a href="#home">Home</a>
                <a href="#cars">Cars</a>
                <a href="#workshop">Workshop</a>
                <a href="#footer">Contact</a>
                <div class="header-actions">
                    <a href="login.php" class="btn-outline">Login</a>
                    <a href="register.php" class="btn-solid">Register</a>
                </div>
            </nav>
        </div>
    </header>

    <!-- ═══ HERO SLIDER ═══ -->
    <section class="hero" id="home">
        <div class="hero-slider">
            <?php foreach ($slides as $index => $slide): ?>
                <div class="hero-slide <?= $index === 0 ? 'active' : '' ?>">
                    <img src="<?= htmlspecialchars($slide['image_path']) ?>" alt="<?= htmlspecialchars($slide['title']) ?>" loading="lazy" />
                    <div class="hero-overlay">
                        <div class="container">
                            <div class="hero-content">
                                
                                <h1>
                                    <?= htmlspecialchars($slide['title']) ?>
                                    <br /><span class="highlight">Drive Your Dream</span>
                                </h1>
                                <p><?= htmlspecialchars($slide['subtitle']) ?></p>
                                <div class="hero-buttons">
                                    <a href="<?= htmlspecialchars($slide['button_link']) ?>" class="hero-btn-primary">
                                        <?= htmlspecialchars($slide['button_text']) ?> <i class="fas fa-arrow-right"></i>
                                    </a>
                                    <a href="#cars" class="hero-btn-secondary">
                                        <i class="fas fa-car"></i> Browse Cars
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="hero-indicators" id="heroIndicators">
            <?php foreach ($slides as $index => $slide): ?>
                <span class="<?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>"></span>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ═══ FLOATING SEARCH ═══ -->
    <div class="search-float">
        <div class="search-box" id="searchBox">
            <input type="text" id="searchInput" placeholder="Search by make, model, or body type…" autocomplete="off" />
            <button id="searchBtn"><i class="fas fa-search"></i> Search</button>
        </div>
    </div>

    <!-- ═══ STATS BAR ═══ -->
    <div class="container">
        <div class="stats-bar reveal">
            <div class="stat-item">
                <div class="number" data-count="1248">0</div>
                <div class="label">Cars Sold</div>
            </div>
            <div class="stat-item">
                <div class="number" data-count="96">0</div>
                <div class="label">Happy Customers</div>
            </div>
            <div class="stat-item">
                <div class="number" data-count="12">0</div>
                <div class="label">Years of Excellence</div>
            </div>
            <div class="stat-item">
                <div class="number" data-count="4.9">0</div>
                <div class="label">★ Average Rating</div>
            </div>
        </div>
    </div>

    <!-- ═══ WHY CHOOSE US ═══ -->
    <div class="container why-section">
        <div class="section-header reveal">
            <div>
                <h2 class="section-title">Why Choose <span class="accent"><?= htmlspecialchars($companyName) ?></span></h2>
                <p class="section-subtitle">We make buying and selling vehicles simple, transparent, and rewarding.</p>
            </div>
        </div>
        <div class="why-grid">
            <div class="why-card reveal" style="transition-delay:0.05s">
                <div class="icon-wrap"><i class="fas fa-shield-alt"></i></div>
                <h3>Trust & Transparency</h3>
                <p>Every vehicle is thoroughly inspected with full history reports available.</p>
            </div>
            <div class="why-card reveal" style="transition-delay:0.10s">
                <div class="icon-wrap"><i class="fas fa-hand-holding-usd"></i></div>
                <h3>Fair Pricing</h3>
                <p>Competitive, market-driven prices with no hidden fees or surprises.</p>
            </div>
            <div class="why-card reveal" style="transition-delay:0.15s">
                <div class="icon-wrap"><i class="fas fa-headset"></i></div>
                <h3>Expert Support</h3>
                <p>Our team is here to guide you through every step of your journey.</p>
            </div>
            <div class="why-card reveal" style="transition-delay:0.20s">
                <div class="icon-wrap"><i class="fas fa-clock"></i></div>
                <h3>Fast & Easy</h3>
                <p>Streamlined processes from search to sale — get on the road faster.</p>
            </div>
        </div>
    </div>

    <!-- ═══ MAIN CONTENT ═══ -->
    <div class="container">

        <!-- Featured Vehicles -->
        <div class="section-header reveal">
            <div>
                <h2 class="section-title" id="cars">Featured <span class="accent">Vehicles</span></h2>
                <p class="section-subtitle">Handpicked selection of our finest available cars.</p>
            </div>
            <a href="login.php" class="section-link">Book Now <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="car-grid reveal">
            <?php foreach ($featuredVehicles as $vehicle): ?>
                <div class="car-card">
                    <div class="img-wrap">
                        <img src="<?= htmlspecialchars($vehicle['image_path']) ?>" alt="<?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?>" loading="lazy" />
                        <span class="card-badge">Featured</span>
                        <button class="wishlist-btn" aria-label="Add to wishlist"><i class="far fa-heart"></i></button>
                    </div>
                    <div class="car-info">
                        <div class="car-title">
                            <?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?>
                            <span class="year"><?= htmlspecialchars($vehicle['manufacture_year']) ?></span>
                        </div>
                        <div class="specs">
                            <span><i class="fas fa-gas-pump"></i> <?= htmlspecialchars($vehicle['fuel_type']) ?></span>
                            <span><i class="fas fa-cog"></i> <?= htmlspecialchars($vehicle['transmission']) ?></span>
                            <span><i class="fas fa-car"></i> <?= htmlspecialchars($vehicle['body_type']) ?></span>
                        </div>
                        <div class="price-row">
                            <span class="price">R<?= number_format($vehicle['price'], 2) ?></span>
                            <a href="login.php?id=<?= $vehicle['vehicle_id'] ?>" class="btn-view">View <i class="fas fa-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Categories -->
        <div class="section-header reveal">
            <div>
                <h2 class="section-title">Browse by <span class="accent">Category</span></h2>
                <p class="section-subtitle">Find exactly what you're looking for.</p>
            </div>
        </div>
        <div class="category-grid reveal" id="categoryFilter">
            <button class="category-pill active" data-category="all">
                <i class="fas fa-th-large"></i> All
            </button>
            <?php foreach ($categories as $category): ?>
                <button class="category-pill" data-category="<?= strtolower(htmlspecialchars($category)) ?>">
                    <i class="fas fa-car"></i> <?= htmlspecialchars($category) ?>
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
                    <div class="img-wrap">
                        <img src="<?= htmlspecialchars($vehicle['image_path']) ?>" alt="<?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?>" loading="lazy" />
                        <button class="wishlist-btn" aria-label="Add to wishlist"><i class="far fa-heart"></i></button>
                    </div>
                    <div class="car-info">
                        <div class="car-title">
                            <?= htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']) ?>
                            <span class="year"><?= htmlspecialchars($vehicle['manufacture_year']) ?></span>
                        </div>
                        <div class="specs">
                            <span><i class="fas fa-gas-pump"></i> <?= htmlspecialchars($vehicle['fuel_type']) ?></span>
                            <span><i class="fas fa-cog"></i> <?= htmlspecialchars($vehicle['transmission']) ?></span>
                            <span><i class="fas fa-car"></i> <?= htmlspecialchars($vehicle['body_type']) ?></span>
                        </div>
                        <div class="price-row">
                            <span class="price">R<?= number_format($vehicle['price'], 2) ?></span>
                           <!--  <a href="vehicle-details.php?id=<?= $vehicle['vehicle_id'] ?>" class="btn-view">View <i class="fas fa-arrow-right"></i></a> -->
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- ─── WORKSHOP SERVICES ─── -->
    <div class="section-header reveal">
        <div>
            <h2 class="section-title" id="workshop">Our <span class="accent">Workshop Services</span></h2>
            <p class="section-subtitle">Professional care to keep your vehicle in peak condition.</p>
        </div>
       <a href="login.php?redirect=<?= urlencode('/CustomerDashboard/bookings/create.php') ?>" class="section-link">Book Now <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="services-grid reveal">
        <?php foreach ($workshopServices as $service): ?>
            <div class="service-card">
                <div class="icon"><i class="fas <?= $service['icon'] ?>"></i></div>
                <h3><?= htmlspecialchars($service['name']) ?></h3>
                <p><?= htmlspecialchars($service['description']) ?></p>
                <div class="service-price"><?= htmlspecialchars($service['price']) ?></div>
                <!-- <button class="btn-service"></button> -->
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ─── TESTIMONIALS ─── -->
    <div class="testimonials reveal">
        <div class="section-header" style="margin-bottom: 8px;">
            <div>
                <h2 class="section-title">What Our <span class="accent">Customers Say</span></h2>
                <p class="section-subtitle">Real stories from real people who found their perfect car.</p>
            </div>
        </div>
        <div class="testimonial-grid">
            <div class="testimonial-item">
                <div class="stars">★★★★★</div>
                <p>“Found my perfect SUV within a week. The search filters made it so easy and the team was incredibly helpful.”</p>
                <div class="author-wrap">
                    <div class="avatar">SK</div>
                    <div>
                        <div class="author">Sarah K.</div>
                        <div class="role">Cape Town</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-item">
                <div class="stars">★★★★★</div>
                <p>“Selling my car was hassle‑free. Got a fair price and quick payment. Highly recommend this platform.”</p>
                <div class="author-wrap">
                    <div class="avatar">MR</div>
                    <div>
                        <div class="author">Michael R.</div>
                        <div class="role">Johannesburg</div>
                    </div>
                </div>
            </div>
            <div class="testimonial-item">
                <div class="stars">★★★★★</div>
                <p>“The financing options helped me afford a brand new Tesla. Everything was seamless from start to finish.”</p>
                <div class="author-wrap">
                    <div class="avatar">JW</div>
                    <div>
                        <div class="author">Jessica W.</div>
                        <div class="role">Durban</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── NEWSLETTER ─── -->
    <div class="newsletter reveal">
        <div class="newsletter-content">
            <h2>🚀 Drive Into the Future</h2>
            <p>Subscribe for exclusive deals, new arrivals, and special offers.</p>
        </div>
        <form class="newsletter-form" onsubmit="event.preventDefault(); alert('Thanks for subscribing! 🎉');">
            <input type="email" placeholder="Enter your email address" required />
            <button type="submit">Subscribe <i class="fas fa-arrow-right"></i></button>
        </form>
    </div>

</div>

<!-- ═══ BACK TO TOP ═══ -->
<button class="back-top" id="backTop" aria-label="Back to top">
    <i class="fas fa-chevron-up"></i>
</button>

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
                    <li><a href="#">Press</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>For Buyers</h4>
                <ul>
                    <li><a href="#">Search Cars</a></li>
                    <li><a href="#">Financing</a></li>
                    <li><a href="#">Insurance</a></li>
                    <li><a href="#">Test Drive</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>For Sellers</h4>
                <ul>
                    <li><a href="#">List Your Car</a></li>
                    <li><a href="#">Pricing</a></li>
                    <li><a href="#">Sell Fast</a></li>
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
            &copy; <?= date('Y') ?> <?= htmlspecialchars($companyName) ?>. All rights reserved. Made with <span class="heart">♥</span> in South Africa.
        </div>
    </div>
</footer>

<!-- ═══ JAVASCRIPT ═══ -->
<script>
    (function() {
        'use strict';

        // ─── Hero Slider ────────────────────────────────────────────────────
        const slides = document.querySelectorAll('.hero-slide');
        const indicators = document.querySelectorAll('#heroIndicators span');
        let current = 0;
        let interval;

        function goToSlide(index) {
            slides.forEach((s, i) => s.classList.toggle('active', i === index));
            indicators.forEach((dot, i) => dot.classList.toggle('active', i === index));
            current = index;
        }

        function nextSlide() {
            goToSlide((current + 1) % slides.length);
        }

        function startSlider() {
            interval = setInterval(nextSlide, 5000);
        }

        function resetSlider() {
            clearInterval(interval);
            startSlider();
        }

        indicators.forEach((dot) => {
            dot.addEventListener('click', function() {
                goToSlide(parseInt(this.dataset.index));
                resetSlider();
            });
        });

        startSlider();

        // ─── Header scroll effect ──────────────────────────────────────────
        const header = document.getElementById('siteHeader');
        window.addEventListener('scroll', function() {
            header.classList.toggle('scrolled', window.scrollY > 60);
        });

        // ─── Mobile hamburger ──────────────────────────────────────────────
        const hamburger = document.getElementById('hamburger');
        const navMobile = document.getElementById('navMobile');

        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            navMobile.classList.toggle('open');
        });

        // Close mobile nav on link click
        navMobile.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', function() {
                hamburger.classList.remove('active');
                navMobile.classList.remove('open');
            });
        });

        // ─── Back to top ────────────────────────────────────────────────────
        const backBtn = document.getElementById('backTop');

        window.addEventListener('scroll', function() {
            backBtn.classList.toggle('visible', window.scrollY > 500);
        });

        backBtn.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });

        // ─── Scroll reveal (Intersection Observer) ────────────────────────
        const revealEls = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');

        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, {
            threshold: 0.12,
            rootMargin: '0px 0px -40px 0px'
        });

        revealEls.forEach(el => revealObserver.observe(el));

        // ─── Stat counter animation ────────────────────────────────────────
        const statNumbers = document.querySelectorAll('.stat-item .number');

        const statObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const target = parseFloat(el.dataset.count);
                    const isFloat = target % 1 !== 0;
                    const duration = 1600;
                    const startTime = performance.now();

                    function updateCounter(time) {
                        const progress = Math.min((time - startTime) / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        const currentVal = eased * target;
                        el.textContent = isFloat ? currentVal.toFixed(1) : Math.floor(currentVal);
                        if (progress < 1) {
                            requestAnimationFrame(updateCounter);
                        } else {
                            el.textContent = isFloat ? target.toFixed(1) : target;
                        }
                    }
                    requestAnimationFrame(updateCounter);
                    statObserver.unobserve(el);
                }
            });
        }, { threshold: 0.3 });

        statNumbers.forEach(el => statObserver.observe(el));

        // ─── Wishlist toggle ──────────────────────────────────────────────
        document.querySelectorAll('.wishlist-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const icon = this.querySelector('i');
                const isLiked = icon.classList.contains('fas');
                icon.classList.toggle('far');
                icon.classList.toggle('fas');
                this.classList.toggle('liked');
                if (isLiked) {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                } else {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                }
            });
        });

        // ─── Search & Filter ──────────────────────────────────────────────
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
                    noMsg.innerHTML = '<i class="fas fa-car-side"></i> No cars match your criteria. Try a different search!';
                    grid.appendChild(noMsg);
                }
                noMsg.style.display = '';
            } else {
                if (noMsg) noMsg.style.display = 'none';
            }
        }

        searchBtn.addEventListener('click', filterCars);
        searchInput.addEventListener('input', filterCars);
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') filterCars();
        });

        categoryPills.forEach(pill => {
            pill.addEventListener('click', function() {
                categoryPills.forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                filterCars();
            });
        });

        // ─── Workshop "Book Now" buttons ──────────────────────────────────
        document.querySelectorAll('.btn-service').forEach(btn => {
            btn.addEventListener('click', function() {
                const name = this.closest('.service-card').querySelector('h3').textContent;
                alert('🔧 Booking request for "' + name + '"\nWe\'ll contact you shortly to confirm!');
            });
        });

        // ─── Scroll restoration ──────────────────────────────────────────
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        window.addEventListener('load', function() {
            window.scrollTo(0, 0);
        });

        // ─── Smooth anchor links ──────────────────────────────────────────
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                const targetEl = document.querySelector(targetId);
                if (targetEl) {
                    e.preventDefault();
                    const offset = 80;
                    const top = targetEl.getBoundingClientRect().top + window.scrollY - offset;
                    window.scrollTo({ top, behavior: 'smooth' });
                }
            });
        });

    })();
</script>

</body>
</html>