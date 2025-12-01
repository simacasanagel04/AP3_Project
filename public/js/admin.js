/**
 * ============================================================================
 * FILE: public/js/admin.js
 * PURPOSE: Handles sidebar navigation, user dropdown, and scroll persistence
 * FIXES: Mobile logout button visibility & clickability
 * ============================================================================
 */

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');

    // --- Scroll Position Logic ---
    if (sidebar) {
        const scrollKey = 'sidebarScrollPos';

        // 1. RESTORE SCROLL POSITION when the page loads
        const savedScrollPos = localStorage.getItem(scrollKey);
        if (savedScrollPos) {
            sidebar.scrollTop = savedScrollPos;
        }

        // 2. SAVE SCROLL POSITION when a navigation link is clicked
        const navLinks = sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                localStorage.setItem(scrollKey, sidebar.scrollTop);
            });
        });
        
        // 3. Fallback: Save scroll position periodically on scroll event
        let scrollTimer;
        sidebar.addEventListener('scroll', function() {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(() => {
                localStorage.setItem(scrollKey, sidebar.scrollTop);
            }, 50); 
        });
    }
    
    // --- Initialize Hamburger Menu & User Dropdown ---
    initHamburger(); 
    initUserDropdown();
});


// Function to handle the collapse/expand of the sidebar
function initHamburger() {
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (!menuToggle || !sidebar || !overlay) return; 

    const toggleSidebar = () => {
        // Close user dropdown whenever sidebar is toggled (good UX)
        const userDropdownMenu = document.getElementById('userDropdownMenu');
        const userDropdownToggle = document.getElementById('userDropdownToggle');
        if (userDropdownMenu) userDropdownMenu.classList.remove('show');
        if (userDropdownToggle) userDropdownToggle.setAttribute('aria-expanded', 'false');

        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        document.body.classList.toggle('sidebar-open');
    };

    menuToggle.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', toggleSidebar);
    
    // Auto-hide logic for large screen resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    });
}


// Function to handle the user dropdown menu (Settings & Logout)
function initUserDropdown() {
    const userDropdownToggle = document.getElementById('userDropdownToggle');
    const userDropdownMenu = document.getElementById('userDropdownMenu');

    if (!userDropdownToggle || !userDropdownMenu) return;

    // Toggle dropdown on button click
    userDropdownToggle.addEventListener('click', function(event) {
        event.stopPropagation(); // Prevent document click from closing it immediately
        const isVisible = userDropdownMenu.classList.contains('show');
        userDropdownMenu.classList.toggle('show');
        userDropdownToggle.setAttribute('aria-expanded', !isVisible);
    });

    // Close the dropdown if the user clicks outside of it
    document.addEventListener('click', function(event) {
        // Check if the click is outside both the toggle button and the menu
        if (!userDropdownToggle.contains(event.target) && !userDropdownMenu.contains(event.target)) {
            if (userDropdownMenu.classList.contains('show')) {
                userDropdownMenu.classList.remove('show');
                userDropdownToggle.setAttribute('aria-expanded', 'false');
            }
        }
    });
    
    // Prevent dropdown from closing when clicking inside it
    userDropdownMenu.addEventListener('click', function(event) {
        event.stopPropagation();
    });
}