import React from 'react';
import { Line } from 'react-chartjs-2';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    ChartData,
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
}

export default function Dashboard({ balanceHistory, currentBalance }: Props) {
    const chartData: ChartData<'line'> = {
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

    const chartOptions = {
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border p-4 flex flex-col justify-center items-center bg-white dark:bg-gray-800">
                        <h3 className="text-lg font-medium text-gray-700 dark:text-gray-300">Current Balance</h3>
                        <p className="text-3xl font-bold text-green-600">${currentBalance.toFixed(2)}</p>
                    </div>
                    <div className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="border-sidebar-border/70 dark:border-sidebar-border relative overflow-hidden rounded-xl border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <div className="border-sidebar-border/70 dark:border-sidebar-border relative min-h-[400px] flex-1 overflow-hidden rounded-xl border p-4 bg-white dark:bg-gray-800">
                    <h2 className="text-xl font-semibold mb-4">Balance History</h2>
                    <div className="w-full h-[350px]">
                        <Line data={chartData} options={chartOptions} />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
