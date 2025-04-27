// Initialize Charts with Real Data
function initRatingsChart() {
    const canvas = document.getElementById('ratingsChart');
    if (!canvas) return;
    // Make chart responsive to container size
    canvas.style.width = '90%';
    canvas.style.height = '90%';
    // Get the rating data passed from Twig
    const ratingData = window.ratingDistribution || {
        5: 0,
        4: 0,
        3: 0,
        2: 0,
        1: 0
    };

    // Prepare chart data
    const data = {
        labels: ['5 Stars', '4 Stars', '3 Stars', '2 Stars', '1 Star'],
        datasets: [{
            data: [
                ratingData[5] || 0,
                ratingData[4] || 0,
                ratingData[3] || 0,
                ratingData[2] || 0,
                ratingData[1] || 0
            ],
            backgroundColor: [
                '#4B49AC',
                '#FFF07B',
                '#DBF0FE',
                '#333333',
                '#a5a4e6'
            ],
            borderWidth: 0,
            font: {
                size: 9,
            }
        }]
    };

    // Create the chart
    new Chart(canvas, {
        type: 'pie',
        data: data,
        options: {

            responsive: true,
            plugins: {
                legend: {
                    position: 'right'
                },
                tooltip: {
                    callbacks: {
                        label: function (context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? Math.round((context.raw / total) * 100) : 0;
                            return `${context.label}: ${context.raw} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}
console.log('Rating data:', window.ratingDistribution);

function initSalesChart() {
    const canvas = document.getElementById('salesChart');
    if (!canvas) {
        console.error('Sales chart canvas not found');
        return;
    }
    // Make chart responsive to container size
    canvas.style.width = '100%';
    canvas.style.height = '100%';
    // Get the sales data passed from Twig
    const salesData = window.salesData || {
        labels: [],
        quantities: []
    };

    // If no data, show empty state
    if (salesData.labels.length === 0) {
        canvas.closest('.chart-section').innerHTML = `
            <div class="empty-chart">
                <i class="fas fa-chart-bar"></i>
                <p>No sales data available yet</p>
            </div>
        `;
        return;
    }

    const ctx = canvas.getContext('2d');

    const data = {
        labels: salesData.labels,
        datasets: [{
            label: 'Units Sold',
            data: salesData.quantities,
            backgroundColor: '#FFF07B',
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
                    title: {
                        display: true,
                        text: 'Units Sold',
                        color: '#4B49AC',
                        font: {
                            size: 14
                        }
                    },
                    grid: {
                        display: true,
                        drawBorder: false,
                        color: '#DBF0FE'
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        color: '#333333',
                        precision: 0 // Ensure whole numbers
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Products',
                        color: '#4B49AC',
                        font: {
                            size: 14
                        }
                    },
                    color: '#333333',
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 10
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
                        label: function (context) {
                            return `Sales: ${context.raw} units`;
                        }
                    }
                }
            }
        }
    };

    new Chart(ctx, config);
}


document.addEventListener('DOMContentLoaded', function () {
    initRatingsChart();
    initSalesChart();

    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Remove active class from all buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));

            // Add active class to clicked button
            this.classList.add('active');

            // Hide all tab contents
            tabContents.forEach(content => {
                content.style.display = 'none';
            });

            // Show the selected tab content
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).style.display = 'block';
        });
    });

});