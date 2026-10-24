    </div>
</div>

<script>
(function() {
    var sidebar = document.getElementById('adminSidebar');
    var toggle = document.getElementById('sidebarToggle');
    var overlay = document.getElementById('adminOverlay');
    var userCard = document.querySelector('.sidebar-user');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (toggle) {
        toggle.addEventListener('click', openSidebar);
    }

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    if (userCard) {
        userCard.addEventListener('click', function() {
            window.location.href = '<?php echo url_path('/admin/admin_profile'); ?>';
        });
    }

    window.addEventListener('resize', function() {
        if (window.innerWidth > 768 && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
})();
</script>
</body>
</html>
