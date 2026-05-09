<?php include("header.php"); ?>
    <!-- Main Content -->
        <!-- Content Area -->
        <div class="flex-1 overflow-y-auto p-4 md:p-8">
            
            <!-- Balance Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Main Balance Card -->
                <div class="bg-gradient-to-br from-gray-900 to-gray-800 text-white rounded-2xl p-6 shadow-xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-4 opacity-10">
                        <i class="fas fa-wallet text-9xl"></i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-gray-400 text-sm mb-1 uppercase tracking-wide">Available Balance</p>
                        <h1 class="text-4xl font-bold mb-6">$842.50</h1>
                        <button class="w-full py-3 bg-primary hover:bg-blue-600 rounded-lg font-semibold transition shadow-lg">
                            <i class="fas fa-arrow-up mr-2"></i>Withdraw Funds
                        </button>
                    </div>
                </div>

                <!-- Weekly Earnings -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wide">This Week</p>
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mt-1">$324.00</h2>
                        </div>
                        <div class="bg-green-100 dark:bg-green-900/30 p-3 rounded-lg">
                            <i class="fas fa-arrow-up text-success text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="text-success font-semibold">+12.5%</span>
                        <span class="text-gray-500 ml-2">vs last week</span>
                    </div>
                </div>

                <!-- Monthly Earnings -->
                <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-gray-500 dark:text-gray-400 text-sm uppercase tracking-wide">This Month</p>
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mt-1">$1,248</h2>
                        </div>
                        <div class="bg-blue-100 dark:bg-blue-900/30 p-3 rounded-lg">
                            <i class="fas fa-chart-line text-primary text-xl"></i>
                        </div>
                    </div>
                    <div class="flex items-center text-sm">
                        <span class="text-success font-semibold">+8.3%</span>
                        <span class="text-gray-500 ml-2">vs last month</span>
                    </div>
                </div>
            </div>

            <!-- Chart Section -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="font-bold text-lg dark:text-white">Weekly Performance</h3>
                    <select class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md text-sm p-2 text-gray-900 dark:text-white">
                        <option>This Week</option>
                        <option>Last Week</option>
                        <option>Last Month</option>
                    </select>
                </div>
                <div class="p-6">
                    <div class="h-64 w-full">
                        <canvas id="earningsChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="p-6 border-b border-gray-100 dark:border-gray-700">
                    <h3 class="font-bold text-lg dark:text-white">Recent Transactions</h3>
                </div>
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    
                    <!-- Transaction 1 -->
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 text-success flex items-center justify-center mr-4">
                                <i class="fas fa-arrow-down text-lg"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">Ride Payment #9921</p>
                                <p class="text-xs text-gray-500">Today, 10:30 AM • Skyline Business Park</p>
                            </div>
                        </div>
                        <span class="font-bold text-success text-lg">+$24.00</span>
                    </div>

                    <!-- Transaction 2 -->
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 text-success flex items-center justify-center mr-4">
                                <i class="fas fa-arrow-down text-lg"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">Ride Payment #9920</p>
                                <p class="text-xs text-gray-500">Today, 8:15 AM • Downtown Mall</p>
                            </div>
                        </div>
                        <span class="font-bold text-success text-lg">+$18.50</span>
                    </div>

                    <!-- Transaction 3 -->
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 text-primary flex items-center justify-center mr-4">
                                <i class="fas fa-bolt text-lg"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">Weekly Bonus</p>
                                <p class="text-xs text-gray-500">Yesterday • 25+ trips milestone</p>
                            </div>
                        </div>
                        <span class="font-bold text-success text-lg">+$50.00</span>
                    </div>

                    <!-- Transaction 4 -->
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 flex items-center justify-center mr-4">
                                <i class="fas fa-university text-lg"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">Bank Withdrawal</p>
                                <p class="text-xs text-gray-500">Jan 05, 2026 • Account ****4567</p>
                            </div>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-gray-300 text-lg">-$200.00</span>
                    </div>

                    <!-- Transaction 5 -->
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 text-success flex items-center justify-center mr-4">
                                <i class="fas fa-arrow-down text-lg"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">Ride Payment #9918</p>
                                <p class="text-xs text-gray-500">Jan 04, 2026 • Airport Terminal</p>
                            </div>
                        </div>
                        <span class="font-bold text-success text-lg">+$45.00</span>
                    </div>

                    <!-- Transaction 6 -->
                    <div class="p-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                        <div class="flex items-center">
                            <div class="w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400 flex items-center justify-center mr-4">
                                <i class="fas fa-star text-lg"></i>
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900 dark:text-white">5-Star Rating Bonus</p>
                                <p class="text-xs text-gray-500">Jan 03, 2026 • Excellence reward</p>
                            </div>
                        </div>
                        <span class="font-bold text-success text-lg">+$25.00</span>
                    </div>
                </div>

                <div class="p-4 text-center border-t border-gray-100 dark:border-gray-700">
                    <button class="text-primary hover:text-blue-600 font-semibold text-sm">
                        View All Transactions <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </main>

    <script src="script.js"></script>
    <script>
        // Chart Initialization
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('earningsChart');
            if(ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                        datasets: [{
                            label: 'Earnings ($)',
                            data: [45, 59, 80, 81, 56, 120, 100],
                            backgroundColor: '#3B82F6',
                            borderRadius: 6,
                            barThickness: 40
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { 
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1F2937',
                                padding: 12,
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                displayColors: false
                            }
                        },
                        scales: {
                            y: { 
                                beginAtZero: true,
                                grid: { 
                                    display: true,
                                    color: 'rgba(0,0,0,0.05)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value;
                                    }
                                }
                            },
                            x: { 
                                grid: { display: false }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>
