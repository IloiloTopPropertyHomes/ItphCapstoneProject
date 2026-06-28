function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}

// sidebar.js
(function () {
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebarOverlay');
    const hamburger = document.getElementById('hamburgerBtn');
    const DESKTOP   = 1024;

    function isDesktop() { return window.innerWidth >= DESKTOP; }

    function initState() {
        if (isDesktop()) {
            sidebar.classList.add('open');
            if (hamburger) hamburger.classList.remove('active');
        } else {
            sidebar.classList.remove('open');
            if (overlay) overlay.classList.remove('active');
            if (hamburger) hamburger.classList.remove('active');
        }
    }

    window.openSidebar = function () {
        sidebar.classList.add('open');
        if (overlay && !isDesktop()) overlay.classList.add('active');
        if (hamburger) hamburger.classList.add('active');
        document.body.style.overflow = isDesktop() ? '' : 'hidden';
    };

    window.closeSidebar = function () {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        if (hamburger) hamburger.classList.remove('active');
        document.body.style.overflow = '';
    };

    window.toggleSidebar = function () {
        sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    };

    window.autoClose = function () { if (!isDesktop()) closeSidebar(); };

    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(initState, 100);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeSidebar();
    });

    initState();
}());
