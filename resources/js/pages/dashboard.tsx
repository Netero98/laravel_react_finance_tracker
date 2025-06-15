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
import { type BreadcrumbItem, DisplayableWallet } from '@/types';
import { Head } from '@inertiajs/react';
import { Responsive, WidthProvider } from 'react-grid-layout';
import 'react-grid-layout/css/styles.css';
import 'react-resizable/css/styles.css';
import { X } from 'lucide-react';
import { motion, useMotionValue, useTransform, animate } from 'framer-motion';
import { formatCurrency } from '@/utils/formatters';
import { Combobox } from '@/components/ui/combobox';
import { Button } from '@/components/ui/button';
import AppearanceToggleTab from '@/components/appearance-tabs';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { BurgerMenuIcon } from '@/components/ui/burger-menu-icon';

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
    balanceHistoryUSD: {
        date: string;
        balance: number;
    }[];
    currentBalanceUSD: number;
    walletData: {
        name: string;
        walletCurrentBalanceUSD: number;
        currency: string;
    }[];
    currentMonthExpensesUSD: {
        name: string;
        amount: number;
    }[];
    currentMonthIncomeUSD: {
        name: string;
        amount: number;
    }[];
    exchangeRates: []
}

type CurrentCurrencyData = {
    chosenCurrency: string,
    currentBalanceInChosenCurrency: number,
    balanceHistoryInChosenCurrency: [],
    currentMonthExpensesInChosenCurrency: number,
    currentMonthIncomeInChosenCurrency: number,
    preparedWalletData: DisplayableWallet[],
}

export default function Dashboard({
    balanceHistoryUSD,
    currentBalanceUSD,
    walletData,
    currentMonthExpensesUSD,
    currentMonthIncomeUSD,
    exchangeRates
}: Props) {
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
            { i: 'balance', x: 0, y: 0, w: 2, h: 1, minW: 1, minH: 1 },
            { i: 'wallet', x: 2, y: 0, w: 2, h: 1, minW: 1, minH: 1 },
            { i: 'expenses', x: 4, y: 0, w: 2, h: 1, minW: 1, minH: 1 },
            { i: 'income', x: 0, y: 1, w: 2, h: 1, minW: 1, minH: 1 },
            { i: 'history', x: 2, y: 1, w: 4, h: 2, minW: 2, minH: 1 }
        ],
        md: [
            { i: 'balance', x: 0, y: 0, w: 2, h: 1, minW: 1, minH: 1 },
            { i: 'wallet', x: 2, y: 0, w: 2, h: 1, minW: 1, minH: 1 },
            { i: 'expenses', x: 0, y: 1, w: 2, h: 1, minW: 1, minH: 1 },
            { i: 'income', x: 2, y: 1, w: 2, h: 1, minW: 1, minH: 1 },
            { i: 'history', x: 0, y: 2, w: 4, h: 2, minW: 2, minH: 1 }
        ],
        sm: [
            { i: 'balance', x: 0, y: 0, w: 2, h: 1, minW: 1, minH: 1 },
            { i: 'wallet', x: 0, y: 1, w: 2, h: 1, minW: 1, minH: 1 },
            { i: 'expenses', x: 0, y: 2, w: 2, h: 1, minW: 1, minH: 1 },
            { i: 'income', x: 0, y: 3, w: 2, h: 1, minW: 1, minH: 1 },
            { i: 'history', x: 0, y: 4, w: 2, h: 2, minW: 1, minH: 1 }
        ]
    };

    // State to store the current layouts
    const [layouts, setLayouts] = useState(defaultLayouts);

    // State to track which charts are visible
    const [visibleCharts, setVisibleCharts] = useState<string[]>([]);

    // State to control settings panel visibility
    const [showSettings, setShowSettings] = useState(false);

    // State to control whether charts are draggable
    const [isDraggable, setIsDraggable] = useState(false);

    const [currentAnimation, setCurrentAnimation] = useState(null);

    const [currentCurrencyData, setCurrentCurrencyData] = useState<CurrentCurrencyData>({
        chosenCurrency: 'USD',
        currentBalanceInChosenCurrency: currentBalanceUSD,
        balanceHistoryInChosenCurrency: balanceHistoryUSD,
        currentMonthExpensesInChosenCurrency: currentMonthExpensesUSD,
        currentMonthIncomeInChosenCurrency: currentMonthIncomeUSD,
        preparedWalletData: walletData.map((wallet) => {
            const walletCurrentBalanceInChosenCurrency = wallet.walletCurrentBalanceUSD;
            return {
                ...wallet,
                walletCurrentBalanceInChosenCurrency,
            }
        })
    });

    const x = useMotionValue(0);
    const rotate = useTransform(x, [-100, 100], [-20, 20]);

    useEffect(() => {
        if (isDraggable) {
            const animation = animate(x, [-5, 5, -5, 5, 0], {
                type: "keyframes",
                duration: 0.3,
                repeat: Infinity,
                repeatType: "loop",
            });

            setCurrentAnimation(animation)

            return () => animation.stop();
        }

        if (!isDraggable) {
            animate(x, 0);
            currentAnimation?.stop()
            setCurrentAnimation(null)
        }
    }, [isDraggable]);

    // Load saved layouts and visible charts from localStorage if available
    useEffect(() => {
        // Load saved layouts
        const savedLayouts = localStorage.getItem('dashboardLayouts');
        if (savedLayouts) {
            try {
                const parsedLayouts = JSON.parse(savedLayouts);

                // Ensure minW and minH values are preserved from defaultLayouts
                const updatedLayouts = { ...parsedLayouts };

                // For each breakpoint (lg, md, sm)
                Object.keys(updatedLayouts).forEach(breakpoint => {
                    // For each item in the layout
                    if (updatedLayouts[breakpoint]) {
                        updatedLayouts[breakpoint] = updatedLayouts[breakpoint].map((item: any) => {
                            // Find the corresponding item in defaultLayouts
                            const defaultItem = defaultLayouts[breakpoint as keyof typeof defaultLayouts]?.find((d: any) => d.i === item.i);

                            // If found, ensure minW and minH are preserved
                            if (defaultItem) {
                                return {
                                    ...item,
                                    minW: defaultItem.minW,
                                    minH: defaultItem.minH
                                };
                            }
                            return item;
                        });
                    }
                });

                setLayouts(updatedLayouts);
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

    function setCurrencyContext(chosenCurrency: string) {
        const rate = exchangeRates[chosenCurrency] || 1;
        const currentBalanceInChosenCurrency = currentBalanceUSD * rate;

        const balanceHistoryInChosenCurrency = balanceHistoryUSD.map(item => ({
            date: item.date,
            balance: item.balance * rate
        }));

        const currentMonthExpensesInChosenCurrency = currentMonthExpensesUSD.map(expense => ({
            name: expense.name,
            amount: expense.amount * rate
        }))

        const currentMonthIncomeInChosenCurrency = currentMonthIncomeUSD.map(income => ({
            name: income.name,
            amount: income.amount * rate
        }))

        const preparedWalletData = walletData.map(wallet => {
            const walletCurrentBalanceInChosenCurrency = wallet.walletCurrentBalanceUSD * rate;
            return {
                ...wallet,
                walletCurrentBalanceInChosenCurrency,
            }
        })

        setCurrentCurrencyData({
            chosenCurrency,
            currentBalanceInChosenCurrency,
            balanceHistoryInChosenCurrency,
            currentMonthExpensesInChosenCurrency,
            currentMonthIncomeInChosenCurrency,
            preparedWalletData,
        });
    }

    useEffect(() => {
        const savedChosenCurrency = localStorage.getItem('chosenCurrency');

        if (savedChosenCurrency && savedChosenCurrency !== currentCurrencyData.chosenCurrency) {
            setCurrencyContext(savedChosenCurrency)
        }
    }, [])

    // Save layouts to localStorage when they change
    const handleLayoutChange = (currentLayout: any, allLayouts: any) => {
        // Ensure minW and minH values are preserved
        const updatedLayouts = { ...allLayouts };

        // For each breakpoint (lg, md, sm)
        Object.keys(updatedLayouts).forEach(breakpoint => {
            // For each item in the layout
            updatedLayouts[breakpoint] = updatedLayouts[breakpoint].map((item: any) => {
                // Find the corresponding item in defaultLayouts
                const defaultItem = defaultLayouts[breakpoint as keyof typeof defaultLayouts]?.find((d: any) => d.i === item.i);

                // If found, ensure minW and minH are preserved
                if (defaultItem) {
                    return {
                        ...item,
                        minW: defaultItem.minW,
                        minH: defaultItem.minH
                    };
                }
                return item;
            });
        });

        setLayouts(updatedLayouts);
        localStorage.setItem('dashboardLayouts', JSON.stringify(updatedLayouts));
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
        labels: currentCurrencyData.balanceHistoryInChosenCurrency.map(item => item.date),
        datasets: [
            {
                label: currentCurrencyData.chosenCurrency,
                data: currentCurrencyData.balanceHistoryInChosenCurrency.map(item => item.balance),
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
        labels: currentCurrencyData.preparedWalletData.map(wallet => wallet.name),
        datasets: [
            {
                data: currentCurrencyData.preparedWalletData.map(wallet => wallet.walletCurrentBalanceInChosenCurrency),
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
        labels: currentCurrencyData.currentMonthExpensesInChosenCurrency.map(expense => expense.name),
        datasets: [
            {
                data: currentCurrencyData.currentMonthExpensesInChosenCurrency.map(expense => expense.amount),
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
        labels: currentCurrencyData.currentMonthIncomeInChosenCurrency.map(income => income.name),
        datasets: [
            {
                data: currentCurrencyData.currentMonthIncomeInChosenCurrency.map(income => income.amount),
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
                        return `${label}: ${formatCurrency(value)} (${percentage}%)`;
                    }
                }
            }
        },
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="md:hidden p-5 z-2 flex justify-end">
                <DropdownMenu>
                    <DropdownMenuTrigger className="bg-background hover:bg-secondary p-2 rounded-sm outline-1 outline-secondary">
                        <BurgerMenuIcon size={24} />
                    </DropdownMenuTrigger>
                    <DropdownMenuContent className="bg-background">
                        <DropdownMenuLabel>My Account</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem>Profile</DropdownMenuItem>
                        <DropdownMenuItem>Billing</DropdownMenuItem>
                        <DropdownMenuItem>Team</DropdownMenuItem>
                        <DropdownMenuItem>Subscription</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="hidden md:flex mb-4 items-center justify-between gap-5">
                    <div className="flex flex-wrap gap-2">
                        <Combobox
                            options={Object.keys(exchangeRates).map(currency => ({
                                value: currency,
                                label: currency
                            }))}
                            value={currentCurrencyData.chosenCurrency}
                            onChange={(value) => {
                                setCurrencyContext(value);
                                localStorage.setItem('chosenCurrency', value);
                            }}
                            placeholder="Select..."
                            searchPlaceholder="Search..."
                            className="w-30"
                        />
                        <Button
                            variant="outline"
                            onClick={() => setShowSettings(!showSettings)}
                        >
                            <span>Choose charts</span>
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => setIsDraggable(!isDraggable)}
                        >
                            {isDraggable ? 'Dragging On' : 'Drag Charts'}
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() => {
                                // Reset layout
                                setLayouts(defaultLayouts);
                                // Reset visible charts to show all charts
                                const allChartIds = availableCharts.map(chart => chart.id);
                                setVisibleCharts(allChartIds);
                                localStorage.setItem('dashboardLayouts', JSON.stringify(defaultLayouts));
                                localStorage.setItem('dashboardVisibleCharts', JSON.stringify(allChartIds));
                            }}
                        >
                            Reset Layout
                        </Button>
                    </div>
                </div>

                {/* Settings Panel */}
                {showSettings && (
                    <div className="mb-4 p-4 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-md">
                        <div className="flex justify-between items-center mb-2">
                            <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300">Available charts</h3>
                            <Button
                                variant="outline"
                                onClick={() => setShowSettings(false)}
                                className="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            >
                                <X className="h-5 w-5" />
                            </Button>
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
                        cols={{ lg: 6, md: 4, sm: 2, xs: 2, xxs: 2 }}
                        rowHeight={150}
                        onLayoutChange={handleLayoutChange}
                        isDraggable={isDraggable}
                        isResizable={isDraggable}
                        margin={[16, 16]}
                        containerPadding={[0, 0]}
                    >
                        {visibleCharts.includes('balance') && (
                            <div
                                key="balance"
                                className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border p-4 bg-white dark:bg-gray-800"
                            >
                                <motion.div style={{ rotate }}>
                                    <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Current Balance ({currentCurrencyData.chosenCurrency})</h3>
                                </motion.div>
                                <div className="flex justify-center items-center h-[calc(100%-40px)]">
                                    <p className="text-3xl font-bold text-green-600">{formatCurrency(currentCurrencyData.currentBalanceInChosenCurrency)}</p>
                                </div>
                            </div>
                        )}

                        {visibleCharts.includes('wallet') && (
                            <div
                                key="wallet"
                                className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border p-4 bg-white dark:bg-gray-800"
                            >
                                <motion.div style={{ rotate }}>
                                    <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Wallet Distribution ({currentCurrencyData.chosenCurrency})</h3>
                                </motion.div>
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
                                <motion.div style={{ rotate }}>
                                    <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Current Month Expenses ({currentCurrencyData.chosenCurrency})</h3>
                                    {currentCurrencyData.currentMonthExpensesInChosenCurrency.length > 0 && (
                                        <div className="flex justify-center items-center mb-2">
                                            <p className="text-xl font-bold text-red-600">
                                                {formatCurrency(currentCurrencyData.currentMonthExpensesInChosenCurrency.reduce((total, expense) => total + expense.amount, 0))}
                                            </p>
                                        </div>
                                    )}
                                </motion.div>
                                <div className="h-[calc(100%-70px)]">
                                    {currentCurrencyData.currentMonthExpensesInChosenCurrency.length > 0 ? (
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
                                <motion.div style={{ rotate }}>
                                    <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Current Month Income ({currentCurrencyData.chosenCurrency})</h3>
                                </motion.div>{currentCurrencyData.currentMonthIncomeInChosenCurrency.length > 0 && (
                                    <div className="flex justify-center items-center mb-2">
                                        <p className="text-xl font-bold text-green-600">
                                            {formatCurrency(currentCurrencyData.currentMonthIncomeInChosenCurrency.reduce((total, income) => total + income.amount, 0))}
                                        </p>
                                    </div>
                                )}
                                <div className="h-[calc(100%-70px)]">
                                    {currentCurrencyData.currentMonthIncomeInChosenCurrency.length > 0 ? (
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
                                <motion.div style={{ rotate }}>
                                    <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Balance history ({currentCurrencyData.chosenCurrency})</h3>
                                </motion.div>
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
