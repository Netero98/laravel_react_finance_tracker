import React from 'react';
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
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

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
        balance: number;
        currency: string;
        originalBalance: number;
    }[];
}

export default function Dashboard({ balanceHistory, currentBalance, walletData }: Props) {
    // Line chart data for balance history
    const lineChartData: any = {
        labels: balanceHistory.map(item => new Date(item.date).toLocaleDateString()),
        datasets: [
            {
                label: 'Balance History (USD)',
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
                text: 'Historical Balance (USD)',
            },
        },
    };

    // Pie chart data for wallet proportions
    const pieChartData: any = {
        labels: walletData.map(wallet => wallet.name),
        datasets: [
            {
                data: walletData.map(wallet => wallet.balance),
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

    const pieChartOptions: any = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'right' as const,
            },
            title: {
                display: true,
                text: 'Wallet Distribution (USD)',
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
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border p-4 bg-white dark:bg-gray-800">
                        <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Current Balance</h3>
                        <div className="flex justify-center items-center h-[150px]">
                            <p className="text-3xl font-bold text-green-600">${currentBalance.toFixed(2)}</p>
                        </div>
                    </div>
                    <div className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border p-4 bg-white dark:bg-gray-800">
                        <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Wallet Distribution</h3>
                        <div className="h-[150px]">
                            <Pie data={pieChartData} options={pieChartOptions} />
                        </div>
                    </div>
                    <div className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <div className="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[400px] flex-1 overflow-hidden rounded-xl border p-4 bg-white dark:bg-gray-800">
                    <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">Balance history</h3>
                    <div className="w-full h-[350px]">
                        <Line data={lineChartData} options={lineChartOptions} />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
