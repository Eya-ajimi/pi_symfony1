document.addEventListener('DOMContentLoaded', function () {
    console.log("[DEBUG] DOM fully loaded. Initializing sidebar...");

    if (typeof jQuery == 'undefined') {
        console.error("[ERROR] jQuery not loaded! Sidebar will not function.");
        return;
    }

    $(function () {
        try {
            const $body = $('body');
            const $menuToggle = $('#menu_toggle');
            const $sidebarMenu = $('#sidebar-menu');
            const $leftCol = $('.left_col');
            const $rightCol = $('.right_col');
            const $topNav = $('.top_nav');
            const currentUrl = window.location.href.split(/[?#]/)[0];

            if (!$body.hasClass('nav-md') && !$body.hasClass('nav-sm')) {
                $body.addClass('nav-md');
                console.log("[DEBUG] Default nav-md class added");
            }

            const setContentHeight = function () {
                const bodyHeight = $body.outerHeight();
                const leftColHeight = $leftCol.eq(1).height() || 0;
                const contentHeight = Math.max(bodyHeight, leftColHeight);
                $rightCol.css('min-height', contentHeight);
                console.log("[DEBUG] Content height set to:", contentHeight);
            };

            const toggleMenu = function () {
                $body.toggleClass('nav-md nav-sm');
                const leftPos = $body.hasClass('nav-md')
                    ? 'var(--sidebar-width)'
                    : 'var(--sidebar-collapsed-width)';
                $topNav.css('left', leftPos);
                setContentHeight();
                console.log("[DEBUG] Menu toggled. Current state:", $body.attr('class'));
            };

            $sidebarMenu.find('a[href="' + currentUrl + '"]')
                .parent('li').addClass('current-page')
                .parents('ul').show()
                .parent().addClass('active');

            $menuToggle.off('click').on('click', toggleMenu);
            $(window).off('resize').on('resize', setContentHeight);

            setContentHeight();
            $topNav.css('left', $body.hasClass('nav-md')
                ? 'var(--sidebar-width)'
                : 'var(--sidebar-collapsed-width)');

            console.log("[SUCCESS] Sidebar initialized successfully");

            // ================================
            // Top Menu Toggle Logic
            // ================================

        } catch (e) {
            console.error("[ERROR] Sidebar initialization failed:", e);
        }
    });
})