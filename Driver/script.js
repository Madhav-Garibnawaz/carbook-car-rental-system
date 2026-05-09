// Dark Mode Toggle - Uses localStorage to persist preference
document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    const html = document.documentElement;
    
    // Check saved preference or default to light mode
    const savedTheme = localStorage.getItem('theme') || 'light';
    html.classList.toggle('dark', savedTheme === 'dark');
    
    if (darkModeToggle) {
        darkModeToggle.addEventListener('click', function() {
            html.classList.toggle('dark');
            const newTheme = html.classList.contains('dark') ? 'dark' : 'light';
            localStorage.setItem('theme', newTheme);
        });
    }
});

// Status Toggle - Online/Offline
document.addEventListener('DOMContentLoaded', function() {
    const statusToggle = document.getElementById('statusToggle');
    const statusText = document.getElementById('statusText');
    const mapBg = document.querySelector('.map-bg');
    
    if (statusToggle && statusText) {
        statusToggle.addEventListener('change', function() {
            if (this.checked) {
                statusText.textContent = 'Online';
                if (mapBg) mapBg.style.filter = 'grayscale(0%)';
            } else {
                statusText.textContent = 'Offline';
                if (mapBg) mapBg.style.filter = 'grayscale(100%)';
            }
        });
    }
});
