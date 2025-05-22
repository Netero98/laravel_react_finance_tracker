import React, { useState, useEffect } from 'react';
import { Line, Pie } from 'react-chartjs-2';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
} from 'chart.js';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Responsive, WidthProvider } from 'react-grid-layout';
import 'react-grid-layout/css/styles.css';
import 'react-resizable/css/styles.css';
import { Settings, X } from 'lucide-react';

const ResponsiveGridLayout = WidthProvider(Responsive);

// Register Chart.js components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    ArcElement,
    Title,
    Tooltip,
    Legend
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface Props {
    balanceHistory: {
        date: string;
        balance: number;
    }[];
    currentBalance: number;
    walletData: {
        name: string;
        walletCurrentBalanceUSD: number;
        currency: string;
    }[];
    currentMonthExpenses: {
        name: string;
        amount: number;
    }[];
    currentMonthIncome: {
        name: string;
        amount: number;
    }[];
}

export default function Dashboard({ balanceHistory, currentBalance, walletData, currentMonthExpenses, currentMonthIncome }: Props) {
    // Define available charts
    const availableCharts = [
        { id: 'balance', title: 'Current Balance' },
        { id: 'wallet', title: 'Wallet Distribution' },
        { id: 'expenses', title: 'Current Month Expenses' },
        { id: 'income', title: 'Current Month Income' },
        { id: 'history', title: 'Balance History' }
    ];

    // Define the initial layouts for different breakpoints
    const defaultLayouts = {
        lg: [
            { i: 'balance', x: 0, y: 0, w: 1, h: 1, minW: 1, minH: 1 },
            { i: 'wallet', x: 1, y: 0, w: 1, h: 1, minW: 1, minH: 1 },
            { i: 'expenses', x: 2, y: 0, w: 1, h: 1, minW: 1, minH: 1 },
            { i: 'income', x: 0, y: 1, w: 1, h: 1, minW: 1, minH: 1 },
            { i: 'history', x: 1, y: 1, w: 2, h: 2, minW: 2, minH: 1 }
        ],
        md: [
            { i: 'balance', x: 0, y: 0, w: 1, h: 1, minW: 1, minH: 1 },
            { i: 'wallet', x: 1, y: 0, w: 1, h: 1, minW: 1, minH: 1 },
            { i: 'expenses', x: 0, y: 1, w: 1, h: 1, minW: 1, minH: 1 },
            { i: 'income', x: 1, y: 1, w: 1, h: 1, minW: 1, minH: 1 },
            { i: 'history', x: 0, y: 2, w: 2, h: 2, minW: 2, minH: 1 }
        ],
        sm: [
            { i: 'balance', x: 0, y: 0, w: 1, h: 1, minW: 1, minH: 1 },
            { i: 'wallet', x: 0, y: 1, w: 1, h: 1, minW: 1, minH: 1 },
            { i: 'expenses', x: 0, y: 2, w: 1, h: 1, minW: 1, minH: 1 },
            { i: 'income', x: 0, y: 3, w: 1, h: 1, minW: 1, minH: 1 },
            { i: 'history', x: 0, y: 4, w: 1, h: 2, minW: 1, minH: 1 }
        ]
    };

    // State to store the current layouts
    const [layouts, setLayouts] = useState(defaultLayouts);

    // State to track which charts are visible
    const [visibleCharts, setVisibleCharts] = useState<string[]>([]);

    // State to control settings panel visibility
    const [showSettings, setShowSettings] = useState(false);

    // Load saved layouts and visible charts from localStorage if available
    useEffect(() => {
        // Load saved layouts
        const savedLayouts = localStorage.getItem('dashboardLayouts');
        if (savedLayouts) {
            try {
                setLayouts(JSON.parse(savedLayouts));
            } catch (e) {
                console.error('Failed to parse saved layouts', e);
            }
        }

        // Load saved visible charts or set all charts visible by default
        const savedVisibleCharts = localStorage.getItem('dashboardVisibleCharts');
        if (savedVisibleCharts) {
            try {
                setVisibleCharts(JSON.parse(savedVisibleCharts));
            } catch (e) {
                console.error('Failed to parse saved visible charts', e);
                setVisibleCharts(availableCharts.map(chart => chart.id));
            }
        } else {
            // By default, all charts are visible
            setVisibleCharts(availableCharts.map(chart => chart.id));
        }
    }, []);

    // Save layouts to localStorage when they change
    const handleLayoutChange = (currentLayout: any, allLayouts: any) => {
        setLayouts(allLayouts);
        localStorage.setItem('dashboardLayouts', JSON.stringify(allLayouts));
    };

    // Toggle chart visibility
    const toggleChartVisibility = (chartId: string) => {
        setVisibleCharts(prev => {
            const newVisibleCharts = prev.includes(chartId)
                ? prev.filter(id => id !== chartId)
                : [...prev, chartId];

            // Save to localStorage
            localStorage.setItem('dashboardVisibleCharts', JSON.stringify(newVisibleCharts));

            return newVisibleCharts;
        });
    };
    // Line chart data for balance history
    const lineChartData: any = {
        labels: balanceHistory.map(item => new Date(item.date).toLocaleDateString()),
        datasets: [
            {
                label: 'USD',
                data: balanceHistory.map(item => item.balance),
                fill: false,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                tension: 0.1,
                borderWidth: 1,
            },
        ],
    };

    const lineChartOptions: any = {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: false,
            },
        },
        plugins: {
            legend: {
                position: 'top' as const,
            },
            title: {
                display: true,
                text: '',
            },
        },
    };

    // Pie chart data for wallet proportions
    const pieChartData: any = {
        labels: walletData.map(wallet => wallet.name),
        datasets: [
            {
                data: walletData.map(wallet => wallet.walletCurrentBalanceUSD),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                ],
                borderWidth: 1,
            },
        ],
    };

    // Pie chart data for current month expenses
    const expensesPieChartData: any = {
        labels: currentMonthExpenses.map(expense => expense.name),
        datasets: [
            {
                data: currentMonthExpenses.map(expense => expense.amount),
                backgroundColor: [
                    'rgba(255, 99, 132, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                ],
                borderWidth: 1,
            },
        ],
    };

    // Pie chart data for current month income
    const incomePieChartData: any = {
        labels: currentMonthIncome.map(income => income.name),
        datasets: [
            {
                data: currentMonthIncome.map(income => income.amount),
                backgroundColor: [
                    'rgba(75, 192, 192, 0.6)',
                    'rgba(54, 162, 235, 0.6)',
                    'rgba(255, 206, 86, 0.6)',
                    'rgba(153, 102, 255, 0.6)',
                    'rgba(255, 159, 64, 0.6)',
                    'rgba(255, 99, 132, 0.6)',
                ],
                borderColor: [
                    'rgba(75, 192, 192, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(255, 99, 132, 1)',
                ],
                borderWidth: 1,
            },
        ],
    };

    const pieChartOptions: any = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right' as const,
            },
            title: {
                display: true,
                text: '',
            },
            tooltip: {
                callbacks: {
                    label: function(context: any) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        const total = context.dataset.data.reduce((a: number, b: number) => a + b, 0);
                        const percentage = Math.round((value / total) * 100);
                        return `${label}: $${value.toFixed(2)} (${percentage}%)`;
                    }
                }
            }
        },
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="mb-4 flex items-center justify-between">
                    <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-200">Dashboard</h2>
                    <div className="flex space-x-2">
                        <button
                            onClick={() => setShowSettings(!showSettings)}
                            className="rounded-md bg-gray-200 dark:bg-gray-700 px-3 py-1 text-sm flex items-center space-x-1 hover:bg-gray-300 dark:hover:bg-gray-600"
                        >
                            <Settings className="h-4 w-4" />
                            <span>Charts</span>
                        </button>
                        <button
                            onClick={() => {
                                // Reset layout
                                setLayouts(defaultLayouts);
                                // Reset visible charts to show all charts
                                const allChartIds = availableCharts.map(chart => chart.id);
                                setVisibleCharts(allChartIds);
                                localStorage.setItem('dashboardVisibleCharts', JSON.stringify(allChartIds));
                            }}
                            className="rounded-md bg-blue-500 px-3 py-1 text-sm text-white hover:bg-blue-600"
                        >
                            Reset Layout
                        </button>
                    </div>
                </div>

                {/* Settings Panel */}
                {showSettings && (
                    <div className="mb-4 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-md">
                        <div className="flex justify-between items-center mb-2">
                            <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300">Chart Settings</h3>
                            <button
                                onClick={() => setShowSettings(false)}
                                className="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                            {availableCharts.map(chart => (
                                <div key={chart.id} className="flex items-center space-x-2">
                                    <input
                                        type="checkbox"
                                        id={`chart-${chart.id}`}
                                        checked={visibleCharts.includes(chart.id)}
                                        onChange={() => toggleChartVisibility(chart.id)}
                                        className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <label htmlFor={`chart-${chart.id}`} className="text-sm text-gray-700 dark:text-gray-300">
                                        {chart.title}
                                    </label>
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {visibleCharts.length === 0 ? (
                    <div className="flex justify-center items-center p-8 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                        <p className="text-gray-500 dark:text-gray-400">No charts selected. Use the Charts button to select charts to display.</p>
                    </div>
                ) : (
                    <ResponsiveGridLayout
                        className="layout"
                        layouts={layouts}
                        breakpoints={{ lg: 1200, md: 996, sm: 768, xs: 480, xxs: 0 }}
                        cols={{ lg: 3, md: 2, sm: 1, xs: 1, xxs: 1 }}
                        rowHeight={150}
                        onLayoutChange={handleLayoutChange}
                        isDraggable={true}
                        isResizable={true}
                        margin={[16, 16]}
                        containerPadding={[0, 0]}
                    >
                        {visibleCharts.includes('balance') && (
                            <div
                                key="balance"
                                className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border p-4 bg-white dark:bg-gray-800"
                            >
                                <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Current Balance (USD)</h3>
                                <div className="flex justify-center items-center h-[calc(100%-40px)]">
                                    <p className="text-3xl font-bold text-green-600">${currentBalance.toFixed(2)}</p>
                                </div>
                            </div>
                        )}

                        {visibleCharts.includes('wallet') && (
                            <div
                                key="wallet"
                                className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border p-4 bg-white dark:bg-gray-800"
                            >
                                <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Wallet Distribution (USD)</h3>
                                <div className="h-[calc(100%-40px)]">
                                    <Pie data={pieChartData} options={pieChartOptions} />
                                </div>
                            </div>
                        )}

                        {visibleCharts.includes('expenses') && (
                            <div
                                key="expenses"
                                className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border p-4 bg-white dark:bg-gray-800"
                            >
                                <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Current Month Expenses (USD)</h3>
                                {currentMonthExpenses.length > 0 && (
                                    <div className="flex justify-center items-center mb-2">
                                        <p className="text-xl font-bold text-red-600">
                                            ${currentMonthExpenses.reduce((total, expense) => total + expense.amount, 0).toFixed(2)}
                                        </p>
                                    </div>
                                )}
                                <div className="h-[calc(100%-70px)]">
                                    {currentMonthExpenses.length > 0 ? (
                                        <Pie data={expensesPieChartData} options={pieChartOptions} />
                                    ) : (
                                        <div className="flex justify-center items-center h-full">
                                            <p className="text-gray-500">No expenses this month</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {visibleCharts.includes('income') && (
                            <div
                                key="income"
                                className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border p-4 bg-white dark:bg-gray-800"
                            >
                                <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Current Month Income (USD)</h3>
                                {currentMonthIncome.length > 0 && (
                                    <div className="flex justify-center items-center mb-2">
                                        <p className="text-xl font-bold text-green-600">
                                            ${currentMonthIncome.reduce((total, income) => total + income.amount, 0).toFixed(2)}
                                        </p>
                                    </div>
                                )}
                                <div className="h-[calc(100%-70px)]">
                                    {currentMonthIncome.length > 0 ? (
                                        <Pie data={incomePieChartData} options={pieChartOptions} />
                                    ) : (
                                        <div className="flex justify-center items-center h-full">
                                            <p className="text-gray-500">No income this month</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {visibleCharts.includes('history') && (
                            <div
                                key="history"
                                className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border p-4 bg-white dark:bg-gray-800"
                            >
                                <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Balance history</h3>
                                <div className="h-[calc(100%-40px)]">
                                    <Line data={lineChartData} options={lineChartOptions} />
                                </div>
                            </div>
                        )}
                    </ResponsiveGridLayout>
                )}
            </div>
        </AppLayout>
    );
}
