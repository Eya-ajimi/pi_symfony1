// Global object to store all chart instances
const appCharts = {
    instances: {},
    destroyAll: function () {
        Object.values(this.instances).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.instances = {};
    }
};

// Initialize Ratings Chart with proper canvas management
function initRatingsChart() {
    const canvas = document.getElementById('ratingsChart');
    if (!canvas) return;

    // Clear any existing chart instance
    if (appCharts.instances.ratingsChart) {
        appCharts.instances.ratingsChart.destroy();
        delete appCharts.instances.ratingsChart;
    }

    // Check if canvas is already in use
    if (canvas.__chartjs__) {
        const existingChart = Chart.getChart(canvas);
        if (existingChart) {
            existingChart.destroy();
        }
    }

    const ctx = canvas.getContext('2d');
    const ratingData = window.ratingDistribution || { 5: 0, 4: 0, 3: 0, 2: 0, 1: 0 };

    appCharts.instances.ratingsChart = new Chart(ctx, {
        type: 'pie',
        data: {
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
                borderWidth: 0
            }]
        },
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

// Initialize Sales Chart with canvas cleanup
function initSalesChart() {
    const canvas = document.getElementById('salesChart');
    if (!canvas) return;

    // Clear existing instance
    if (appCharts.instances.salesChart) {
        appCharts.instances.salesChart.destroy();
        delete appCharts.instances.salesChart;
    }

    // Check for existing Chart.js instance
    const existingChart = Chart.getChart(canvas);
    if (existingChart) {
        existingChart.destroy();
    }

    const ctx = canvas.getContext('2d');
    const salesData = window.salesData || { labels: [], quantities: [] };

    if (salesData.labels.length === 0) {
        canvas.closest('.chart-container').innerHTML = `
            <div class="empty-chart">
                <i class="fas fa-chart-bar"></i>
                <p>No sales data available</p>
            </div>
        `;
        return;
    }

    appCharts.instances.salesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: salesData.labels,
            datasets: [{
                label: 'Units Sold',
                data: salesData.quantities,
                backgroundColor: '#FFF07B',
                borderColor: '#4B49AC',
                borderWidth: 2,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        display: true,
                        drawBorder: false,
                        color: '#DBF0FE'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
}

// Global object to track chart instances
const chartInstances = {};

function initWeeklySalesChart() {
    const canvas = document.getElementById('weeklySalesChart');
    if (!canvas) {
        console.error('Weekly sales chart canvas not found');
        return;
    }

    // Destroy existing chart if it exists
    if (chartInstances.weeklySalesChart) {
        chartInstances.weeklySalesChart.destroy();
    }

    // Check for Chart.js internal instance
    const existingChart = Chart.getChart(canvas);
    if (existingChart) {
        existingChart.destroy();
    }

    const ctx = canvas.getContext('2d');
    const chartData = window.weeklySalesChartData || { labels: [], data: [] };
    
    // Filter out any zero values if needed (optional)
    const filteredData = {
        labels: chartData.labels,
        data: chartData.data.map(value => value || 0) // Ensure zeros are numbers
    };

    // Check if we have data to display
    if (chartData.labels.length === 0 || chartData.data.length === 0) {
        console.warn('No weekly sales data available');
        canvas.closest('.chart-container').innerHTML = `
            <div class="no-data-message">
                <i class="fas fa-chart-line"></i>
                <p>No sales data available for this period</p>
            </div>
        `;
        return;
    }

    // Create gradient
    const gradient = ctx.createLinearGradient(0, 0, 0, canvas.height);
    gradient.addColorStop(0, 'rgba(75, 73, 172, 0.6)');
    gradient.addColorStop(1, 'rgba(75, 73, 172, 0.1)');

    chartInstances.weeklySalesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.labels,
            datasets: [{
                label: 'Daily Sales (DT)',
                data: chartData.data,
                backgroundColor: gradient,
                borderColor: '#4B49AC',
                borderWidth: 2,
                pointBackgroundColor: '#4B49AC',
                pointRadius: 4,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#333333',
                    padding: 10,
                    cornerRadius: 6,
                    displayColors: false,
                    callbacks: {
                        label: function (context) {
                            return `Sales: ${context.raw.toFixed(2)} DT`;
                        },
                        title: function (context) {
                            return context[0].label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        callback: function (value) {
                            return value + ' DT';
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });
}

// Initialize all charts
function initAllCharts() {
    initWeeklySalesChart();
    // Add other chart initializations here if needed
}

// Wait for DOM and data to be ready
document.addEventListener('DOMContentLoaded', function () {
    console.log('Weekly sales data:', window.weeklySalesChartData);
    setTimeout(initAllCharts, 100); // Small delay to ensure everything is loaded
});

// Handle window resize
window.addEventListener('resize', function () {
    if (chartInstances.weeklySalesChart) {
        chartInstances.weeklySalesChart.resize();
    }
});