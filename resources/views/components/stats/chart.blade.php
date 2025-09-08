@props([
    'datasets' => [],
    'labels' => [],
    'type' => 'bar',
    'height' => 200,
    'width' => 500,
])

<div class="relative">
    <canvas id="stats-chart" class="w-full" style="max-height: 400px;"></canvas>
</div>

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('stats-chart').getContext('2d');
        const myChart = new Chart(ctx, {
            type: '{{ $type }}',
            data: {
                labels: @js($labels),
                datasets: @js($datasets)
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            pointStyle: 'line',
                            padding: 20,
                            font: {
                                size: 14,
                                weight: 'bold'
                            },
                            generateLabels: function(chart) {
                                return Chart.defaults.plugins.legend.labels.generateLabels(chart).map(function(label, index) {
                                    const dataset = chart.data.datasets[index];
                                    label.lineWidth = 4;
                                    label.strokeStyle = dataset.borderColor;
                                    label.fillStyle = dataset.borderColor;
                                    if (dataset.borderDash && dataset.borderDash.length > 0) {
                                        label.lineDash = dataset.borderDash;
                                        label.lineDashOffset = 0;
                                    }
                                    return label;
                                });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#f3f4f6',
                            borderDash: [2, 2]
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: '#6b7280',
                            precision: 0,
                            stepSize: 1
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 11
                            },
                            color: '#6b7280'
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                elements: {
                    point: {
                        radius: 4,
                        hoverRadius: 6,
                        pointStyle: 'circle'
                    },
                    line: {
                        tension: 0.3
                    }
                },
                datasets: {
                    line: {
                        pointStyle: 'circle',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        borderWidth: 3
                    }
                }
            }
        })
    </script>
@endpush
