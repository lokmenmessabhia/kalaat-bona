<?php
declare(strict_types=1);
$s = $s ?? get_settings();
?>
    </main>

    <?php if (!($hide_footer ?? false)): ?>
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-brand">
                <div class="footer-logo-wrap">
                    <img src="<?php echo url_path('/' . ltrim((string)$s['site_logo'], '/')); ?>" alt="<?php echo htmlspecialchars((string)$s['site_name']); ?>" class="footer-logo">
                    <div>
                        <h3><?php echo htmlspecialchars((string)$s['site_name']); ?></h3>
                        <span class="footer-tagline" data-i18n="footer_tagline">L'excellence du football à Annaba</span>
                    </div>
                </div>
                <p class="footer-desc" data-i18n="footer_description">Façonner l'avenir du football à travers l'excellence et le dévouement. Notre académie forme les champions de demain.</p>
                <div class="footer-social">
                    <a href="#" class="social-link" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="social-link" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="social-link" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                    <a href="#" class="social-link" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h3 data-i18n="navigation">Navigation</h3>
                <ul class="footer-links">
                    <li><a href="<?php echo url_path('/index'); ?>"><i class="fa-solid fa-chevron-right"></i> <span data-i18n="home">Accueil</span></a></li>
                    <li><a href="<?php echo url_path('/services'); ?>"><i class="fa-solid fa-chevron-right"></i> <span data-i18n="services">Programmes</span></a></li>
                    <li><a href="<?php echo url_path('/formulair'); ?>"><i class="fa-solid fa-chevron-right"></i> <span data-i18n="inscription">Inscription</span></a></li>
                    <li><a href="<?php echo url_path('/portal_login'); ?>"><i class="fa-solid fa-chevron-right"></i> <span data-i18n="connexion_nav">Connexion</span></a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3 data-i18n="footer_contact">Contact</h3>
                <ul class="footer-contact">
                    <li><i class="fa-solid fa-location-dot"></i> <span data-i18n="address_city">Annaba, Algérie</span></li>
                    <li><i class="fa-solid fa-phone"></i> +213 (0) XXX XX XX XX</li>
                    <li><i class="fa-solid fa-envelope"></i> contact@kalaatbona.com</li>
                    <li><i class="fa-solid fa-clock"></i> <span data-i18n="hours">Lun-Sam: 8h-18h</span></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars((string)$s['site_name']); ?>. <span data-i18n="all_rights_reserved">Tous droits réservés.</span></p>
        </div>
    </footer>
    <?php endif; ?>

    <script src="<?php echo url_path('/js/ui-manager.js'); ?>"></script>
    <script>
        const h = document.getElementById('navHamburger'), nr = document.getElementById('navRight'), no = document.getElementById('navOverlay'), nc = document.getElementById('navCloseBtn');
        function toggleNav(open) { h && h.classList.toggle('active', open); nr && nr.classList.toggle('active', open); no && no.classList.toggle('active', open); document.body.style.overflow = open ? 'hidden' : ''; }
        if (h && nr && no) {
            h.addEventListener('click', () => toggleNav(true));
            nc && nc.addEventListener('click', () => toggleNav(false));
            no.addEventListener('click', () => toggleNav(false));
        }
        window.addEventListener('scroll', () => { const el = document.getElementById('scroll-progress'); if (el) el.style.width = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight) * 100) + '%'; });
        document.querySelectorAll('.reveal').forEach(el => { const o = new IntersectionObserver((e) => { e.forEach(i => { if (i.isIntersecting) i.target.classList.add('revealed'); }); }, { threshold: 0.1 }); o.observe(el); });
        document.querySelectorAll('a[href^="#"]').forEach(a => { a.addEventListener('click', function(e) { e.preventDefault(); const t = document.querySelector(this.getAttribute('href')); if (t) t.scrollIntoView({ behavior: 'smooth' }); }); });

        // Page transition — drops on departure, swipes up on arrival
        (function() {
            const pt = document.getElementById('page-transition');
            if (!pt) return;

            // Arrival: curtain visible from navigation, slide it up
            if (sessionStorage.getItem('pageTrans') === '1') {
                sessionStorage.removeItem('pageTrans');
                pt.style.transition = 'none';
                pt.style.transform = 'translateY(0)';
                pt.offsetHeight;
                setTimeout(() => {
                    pt.style.transition = 'transform 0.4s ease-out';
                    pt.style.transform = 'translateY(-100%)';
                }, 400);
            }

            // Departure: intercept internal links
            document.querySelectorAll('a[href]').forEach(a => {
                const href = a.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('http') || href.startsWith('//')) return;
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    sessionStorage.setItem('pageTrans', '1');
                    pt.classList.add('active');
                    setTimeout(() => { window.location.href = href; }, 450);
                });
            });
        })();
    </script>
</body>
</html>
