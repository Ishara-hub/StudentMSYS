document.addEventListener('DOMContentLoaded', function() {
    // Theme Management
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    
    // Initialize with light theme by default
    const currentTheme = localStorage.getItem('theme') || 'light';
    html.setAttribute('data-bs-theme', currentTheme);
    themeToggle.innerHTML = currentTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
    
    // Theme toggle handler
    themeToggle.addEventListener('click', function() {
        const newTheme = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        html.setAttribute('data-bs-theme', newTheme);
        themeToggle.innerHTML = newTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        localStorage.setItem('theme', newTheme);
        document.cookie = `darkMode=${newTheme === 'dark'}; path=/; max-age=31536000`;
    });
    
    // Sidebar Management
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarCollapse = document.getElementById('sidebarCollapse');
    const mainContent = document.querySelector('.main-content');
    
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
        mainContent.classList.toggle('content-expanded');
    });
    
    sidebarCollapse.addEventListener('click', function() {
        sidebar.classList.remove('show');
        mainContent.classList.add('content-expanded');
    });
    
    // Responsive sidebar handling
    function handleResize() {
        if (window.innerWidth < 992) {
            sidebar.classList.remove('show');
            mainContent.classList.add('content-expanded');
        } else {
            sidebar.classList.add('show');
            mainContent.classList.remove('content-expanded');
        }
    }
    
    // Initialize and add event listener
    handleResize();
    window.addEventListener('resize', handleResize);
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Real-time clock in footer
    function updateClock() {
        const now = new Date();
        const clockElement = document.getElementById('footerClock');
        if (clockElement) {
            clockElement.textContent = now.toLocaleTimeString();
        }
    }
    setInterval(updateClock, 1000);
    updateClock();
});