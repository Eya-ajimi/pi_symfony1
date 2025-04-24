// Initialize Charts
function initRatingsChart() {
    const canvas = document.getElementById('ratingsChart');
    if (!canvas) {
        console.error('Ratings chart canvas not found');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    
    // Sample data for ratings distribution
    const data = {
        labels: ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
        datasets: [{
            data: [45, 30, 15, 7, 3], // Percentages of each rating
            backgroundColor: [
                '#4B49AC',
                '#7978E9',
                '#DBF0FE',
                '#FFF07B',
                '#FFC3A0'
            ],
            borderWidth: 0,
            hoverOffset: 4
        }]
    };
    
    const config = {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 15,
                        padding: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.label}: ${context.raw}%`;
                        }
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

function initSalesChart() {
    const canvas = document.getElementById('salesChart');
    if (!canvas) {
        console.error('Sales chart canvas not found');
        return;
    }
    
    const ctx = canvas.getContext('2d');
    
    // Sample data for product sales
    const data = {
        labels: ['T-Shirt', 'Sunglasses', 'Watch', 'Earbuds', 'Jacket', 'Sneakers'],
        datasets: [{
            label: 'Units Sold',
            data: [65, 42, 37, 25, 30, 52],
            backgroundColor: '#DBF0FE',
            borderColor: '#4B49AC',
            borderWidth: 2,
            borderRadius: 5,
            maxBarThickness: 30
        }]
    };
    
    const config = {
        type: 'bar',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        drawBorder: false,
                        color: 'rgba(200, 200, 200, 0.2)'
                    },
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(75, 73, 172, 0.8)',
                    padding: 10,
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    },
                    callbacks: {
                        label: function(context) {
                            return `Sales: ${context.raw} units`;
                        }
                    }
                }
            }
        }
    };
    
    new Chart(ctx, config);
}

// Helper function to animate number changes
function animateValue(element, start, end, duration) {
    if (!element) return;
    
    let startTimestamp = null;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        const value = Math.floor(progress * (end - start) + start);
        
        if (element.classList.contains('rating-value')) {
            // For decimal values like ratings
            element.textContent = (progress * (end - start) + start).toFixed(1);
        } else {
            element.textContent = value;
        }
        
        if (progress < 1) {
            window.requestAnimationFrame(step);
        }
    };
    window.requestAnimationFrame(step);
}

// A simple tooltip system
function initTooltips() {
    const elements = document.querySelectorAll('[data-tooltip]');
    
    elements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltipText = this.getAttribute('data-tooltip');
            
            const tooltip = document.createElement('div');
            tooltip.classList.add('custom-tooltip');
            tooltip.textContent = tooltipText;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.top = rect.bottom + 10 + 'px';
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            
            // Store the tooltip reference
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                document.body.removeChild(this._tooltip);
                this._tooltip = null;
            }
        });
    });
}

// Initialize dashboard with data
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts first
    initRatingsChart();
    initSalesChart();
    
    // Initial data load
    updateDashboardData();
    
    // Set up periodic refresh (every 5 minutes)
    setInterval(updateDashboardData, 5 * 60 * 1000);
    
    // Product card hover effects
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 5px rgba(0,0,0,0.05)';
        });
    });
    
    // Restock button functionality
    const restockButtons = document.querySelectorAll('.restock-btn');
    restockButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Show a temporary success message
            const originalText = this.textContent;
            this.textContent = 'Order Placed';
            this.style.backgroundColor = '#4CAF50';
            this.style.color = 'white';
            this.disabled = true;
            
            // Revert back after 2 seconds
            setTimeout(() => {
                this.textContent = originalText;
                this.style.backgroundColor = '';
                this.style.color = '';
                this.disabled = false;
            }, 2000);
        });
    });
    
    // Initialize tooltips
    initTooltips();
    
   
});

// Function to update dashboard data with fresh information
// function updateDashboardData() {
//     // Update product count with animation
//     animateValue(document.querySelector('.product-count h2'));
    
//     // Update average rating with animation
//     animateValue(document.querySelector('.rating-value'), 4.5, 4.7, 1000);
// }