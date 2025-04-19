document.addEventListener('DOMContentLoaded', function() {
    initializeFilters();
    setupSearch();
    initChart();
    
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
    const reservationCountEl = document.getElementById('reservation-count');
    const totalReservations = document.querySelectorAll('#reservationsTable tbody tr').length;
    
    updateReservationCount(totalReservations, totalReservations);
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update active button state
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const filterValue = this.getAttribute('data-filter');
            const rows = document.querySelectorAll('#reservationsTable tbody tr');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                
                if (filterValue === 'all') {
                    row.style.display = '';
                    visibleCount++;
                } else if (status === filterValue) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update the count display
            updateReservationCount(visibleCount, totalReservations);
            document.getElementById('showing-count').textContent = visibleCount;
        });
    });
}

function setupSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#reservationsTable tbody tr');
            
            let visibleCount = 0;
            
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
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Update count
            document.getElementById('showing-count').textContent = visibleCount;
            updateReservationCount(visibleCount, rows.length);
        });
    }
}

function updateReservationCount(visible, total) {
    const countEl = document.getElementById('reservation-count');
    if (countEl) {
        countEl.textContent = `Showing ${visible} of ${total} reservations`;
    }
}

function initChart() {
    const ctx = document.getElementById('reservationChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Reservations',
                    backgroundColor: 'rgba(0, 171, 85, 0.1)',
                    borderColor: '#00ab55',
                    data: [5, 8, 7, 12, 11, 9, 14, 15, 13, 11, 8, 10],
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        backgroundColor: 'rgba(255, 255, 255, 0.9)',
                        titleColor: '#333',
                        bodyColor: '#666',
                        borderColor: '#e0e0e0',
                        borderWidth: 1,
                        padding: 12,
                        boxPadding: 6,
                        usePointStyle: true
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { borderDash: [2, 2] } }
                }
            }
        });
    }
}