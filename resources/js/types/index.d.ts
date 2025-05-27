import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Category {
    id: number;
    name: string;
    is_system: boolean;
    created_at: string;
    updated_at: string;
}

export interface Wallet {
    id: number;
    name: string;
    initial_balance: number;
    currency: string;
    created_at: string;
    updated_at: string;
}

export interface Transaction {
    id: number;
    amount: number;
    description: string | null;
    date: string;
    category_id: number;
    wallet_id: number;
    category: Category;
    wallet: Wallet;
    created_at: string;
    updated_at: string;
}
