<footer class="footer">
    <div class="container-fluid">
        <div class="row align-items-center g-2">
            <!-- Left: Copyright -->
            <div class="col-md-6 text-center text-md-start">
                <small class="text-muted">
                    <i class="fas fa-copyright me-1"></i>
                    <?= date('Y'); ?>
                    <strong>Dipsel Auto System</strong>.
                    All Rights Reserved.
                </small>
            </div>

            <!-- Right: Version & Role -->
            <div class="col-md-6 text-center text-md-end">
                <small class="text-muted">
                    <i class="fas fa-code-branch me-1"></i>
                    Version 1.0.0
                    <span class="mx-1">|</span>
                    <i class="fas fa-user-shield me-1"></i>
                    <?php
                    // Dynamically show the user's role, or fallback to 'Panel'
                    if (isset($currentUser) && !empty($currentUser['admin_level'])) {
                        echo htmlspecialchars($currentUser['admin_level']) . ' Panel';
                    } else {
                        echo 'Panel';
                    }
                    ?>
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS (required for dropdowns, etc.) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
.footer {
    background: #ffffff;
    border-top: 3px solid transparent;
    border-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%) 1;
    padding: 1.2rem 0;
    margin-top: 2.5rem;
    box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.04);
    transition: all 0.2s;
}

.footer small {
    font-size: 0.85rem;
    color: #6b7280;
    font-weight: 500;
    letter-spacing: 0.3px;
}

.footer strong {
    color: #1f2937;
    font-weight: 600;
}

.footer i {
    color: #667eea;
    opacity: 0.8;
    width: 1.1rem;
    text-align: center;
}

/* Responsive spacing */
@media (max-width: 767.98px) {
    .footer .text-md-end {
        text-align: center !important;
    }
    .footer .col-md-6 {
        margin-bottom: 0.2rem;
    }
    .footer .row {
        row-gap: 0.3rem;
    }
}

/* Optional hover effect on the role text */
.footer .text-muted i.fa-user-shield {
    transition: transform 0.2s;
}
.footer .text-muted:hover i.fa-user-shield {
    transform: scale(1.15);
}
</style>

</body>
</html>