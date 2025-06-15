import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { type BreadcrumbItem } from '@/types';
import React, { type PropsWithChildren } from 'react';
import AppearanceToggleTab from '@/components/appearance-tabs';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent variant="sidebar">
                <div className="flex justify-between flex-wrap items-center mr-5">
                    <AppSidebarHeader breadcrumbs={breadcrumbs} />
                    <AppearanceToggleTab className="h-10"/>
                </div>
                {children}
            </AppContent>
        </AppShell>
    );
}
