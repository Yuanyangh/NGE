<div>
    <div class="rounded-xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            <h2 class="text-base font-semibold text-slate-900 dark:text-white">Commission Trend (30 Days)</h2>
        </div>

        <div class="p-5">
            <div
                x-data="commissionChart()"
                x-init="initChart()"
                class="relative"
                style="height: 300px;"
            >
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function commissionChart() {
            return {
                chart: null,
                initChart() {
                    const isDark = document.documentElement.classList.contains('dark');
                    const gridColor = isDark ? 'rgba(148, 163, 184, 0.1)' : 'rgba(148, 163, 184, 0.2)';
                    const textColor = isDark ? '#94a3b8' : '#64748b';

                    const ctx = this.$refs.canvas.getContext('2d');

                    this.chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: @json($chartLabels),
                            datasets: [
                                {
                                    label: 'Affiliate',
                                    data: @json($affiliateData),
                                    borderColor: '#6366f1',
                                    backgroundColor: 'rgba(99, 102, 241, 0.08)',
                                    tension: 0.3,
                                    fill: true,
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 4,
                                    pointHoverBackgroundColor: '#6366f1',
                                },
                                {
                                    label: 'Viral',
                                    data: @json($viralData),
                                    borderColor: '#10b981',
                                    backgroundColor: 'rgba(16, 185, 129, 0.08)',
                                    tension: 0.3,
                                    fill: true,
                                    borderWidth: 2,
                                    pointRadius: 0,
                                    pointHoverRadius: 4,
                                    pointHoverBackgroundColor: '#10b981',
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top',
                                    align: 'end',
                                    labels: {
                                        color: textColor,
                                        usePointStyle: true,
                                        pointStyle: 'circle',
                                        boxWidth: 6,
                                        padding: 16,
                                        font: {
                                            family: 'Inter, sans-serif',
                                            size: 12,
                                        },
                                    },
                                },
                                tooltip: {
                                    backgroundColor: isDark ? '#1e293b' : '#fff',
                                    titleColor: isDark ? '#f1f5f9' : '#0f172a',
                                    bodyColor: isDark ? '#cbd5e1' : '#475569',
                                    borderColor: isDark ? '#334155' : '#e2e8f0',
                                    borderWidth: 1,
                                    padding: 12,
                                    titleFont: { family: 'Inter, sans-serif', weight: '600' },
                                    bodyFont: { family: 'Inter, sans-serif' },
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': $' + context.parsed.y.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                        },
                                    },
                                },
                            },
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        color: textColor,
                                        font: { family: 'Inter, sans-serif', size: 11 },
                                        maxRotation: 0,
                                        maxTicksLimit: 8,
                                    },
                                    border: { display: false },
                                },
                                y: {
                                    grid: { color: gridColor },
                                    ticks: {
                                        color: textColor,
                                        font: { family: 'Inter, sans-serif', size: 11 },
                                        callback: function(value) {
                                            return '$' + value.toLocaleString();
                                        },
                                    },
                                    border: { display: false },
                                    beginAtZero: true,
                                },
                            },
                        },
                    });
                },
            };
        }
    </script>
</div>
