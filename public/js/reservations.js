document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    setupSearch();
    
    // Add CSRF token to cancel forms
    document.querySelectorAll('.cancel-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to cancel this reservation?')) {
                this.submit();
            }
        });
    });
});

function initializeFilters() {
    const filterButtons = document.querySelectorAll('.filter-btn');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update active button state
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const filterValue = this.getAttribute('data-filter');
            const rows = document.querySelectorAll('#reservationsTable tbody tr');
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                
                if (filterValue === 'all') {
                    row.style.display = '';
                } else if (status === filterValue) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // If you want to persist the filter across pagination
            const url = new URL(window.location.href);
            url.searchParams.set('filter', filterValue);
            window.history.pushState({}, '', url);
        });
    });
}

function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#reservationsTable tbody tr');
            
            rows.forEach(row => {
                const rowContent = row.textContent.toLowerCase();
                const activeFilter = document.querySelector('.filter-btn.active')?.getAttribute('data-filter') || 'all';
                const status = row.getAttribute('data-status');
                
                let shouldShow = rowContent.includes(searchTerm);
                
                // Consider active filter
                if (activeFilter !== 'all' && status !== activeFilter) {
                    shouldShow = false;
                }
                
                if (shouldShow) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
}