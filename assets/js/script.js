// Sidebar Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const wrapper = document.getElementById('wrapper');
    const sidebar = document.getElementById('sidebar-wrapper');
    
    // Check for saved sidebar state
    const savedState = localStorage.getItem('sidebarState');
    if (savedState === 'collapsed' && window.innerWidth > 768) {
        wrapper.classList.add('toggled');
    }
    
    // Toggle sidebar on button click
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (window.innerWidth <= 768) {
                // Mobile: show/hide sidebar with overlay
                sidebar.classList.toggle('show');
                toggleOverlay();
            } else {
                // Desktop: collapse/expand sidebar
                wrapper.classList.toggle('toggled');
                localStorage.setItem('sidebarState', wrapper.classList.contains('toggled') ? 'collapsed' : 'expanded');
            }
        });
    }
    
    // Create overlay for mobile
    function createOverlay() {
        const overlay = document.createElement('div');
        overlay.className = 'overlay';
        overlay.id = 'sidebarOverlay';
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
        document.body.appendChild(overlay);
    }
    
    function toggleOverlay() {
        let overlay = document.getElementById('sidebarOverlay');
        if (!overlay) {
            createOverlay();
            overlay = document.getElementById('sidebarOverlay');
        }
        overlay.classList.toggle('show');
    }
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('show');
            const overlay = document.getElementById('sidebarOverlay');
            if (overlay) {
                overlay.classList.remove('show');
            }
        }
    });
    
    // Auto-hide flash messages after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            if (alert.classList.contains('show')) {
                alert.classList.remove('show');
                setTimeout(function() {
                    alert.remove();
                }, 150);
            }
        }, 5000);
    });
    
    // Avatar preview on file input change
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.getElementById('avatarPreview');
    
    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    avatarPreview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to perform this action?')) {
                e.preventDefault();
            }
        });
    });
});
