document.addEventListener('DOMContentLoaded', function() {
    console.log("[DEBUG] DOM fully loaded. Running sidebar initialization...");

    // Initialize default state
    if (!$('body').hasClass('nav-md') && !$('body').hasClass('nav-sm')) {
        $('body').addClass('nav-md');
    }

    // Check jQuery availability
    if (typeof jQuery == 'undefined') {
        console.error("[ERROR] jQuery not loaded!");
        return;
    }

    // Initialize sidebar
    initSidebar();

    // Debug info
    console.log("[DEBUG] Initial body class:", $('body').attr('class'));
});

function initSidebar() {
    // DOM elements
    const $BODY = $('body');
    const $MENU_TOGGLE = $('#menu_toggle');
    const $SIDEBAR_MENU = $('#sidebar-menu');
    const $LEFT_COL = $('.left_col');
    const $RIGHT_COL = $('.right_col');
    const $TOP_NAV = $('.top_nav');
    const CURRENT_URL = window.location.href.split('#')[0].split('?')[0];

    // Set content height
    function setContentHeight() {
        const bodyHeight = $BODY.outerHeight();
        const leftColHeight = $LEFT_COL.eq(1).height();
        const contentHeight = Math.max(bodyHeight, leftColHeight);
        $RIGHT_COL.css('min-height', contentHeight);
    }

    // Toggle menu function
    function toggleMenu() {
        $BODY.toggleClass('nav-md nav-sm');
        
        // Explicitly set top nav position based on current state
        if ($BODY.hasClass('nav-md')) {
            $TOP_NAV.css('left', 'var(--sidebar-width)');
        } else {
            $TOP_NAV.css('left', 'var(--sidebar-collapsed-width)');
        }
        
        setContentHeight();
        $(window).trigger('resize');
    }

    // Initialize active menu
    $SIDEBAR_MENU.find('a[href="' + CURRENT_URL + '"]')
        .parent('li').addClass('current-page')
        .parents('ul').show()
        .parent().addClass('active');

    // Menu toggle event
    $MENU_TOGGLE.on('click', toggleMenu);

    // Set initial height and position
    setContentHeight();
    $TOP_NAV.css('left', $BODY.hasClass('nav-md') ? 'var(--sidebar-width)' : 'var(--sidebar-collapsed-width)');

    // Handle window resize
    $(window).on('resize', setContentHeight);
}


//+++++++++++++++++++++++++++++++++++++++SIDE BAR++++++++++++++++++++++++++++
