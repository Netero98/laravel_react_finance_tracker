import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatCurrency } from '@/utils/formatters';
import { router } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Wallet, BreadcrumbItem } from '@/types';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Pagination } from '@/components/ui/pagination';

interface PaginatedData<T> {
    data: T[];
    links: {
        url: string | null;
        label: string;
        active: boolean;
    }[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
}

interface Props {
    wallets: PaginatedData<Wallet>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Wallets',
        href: '/wallets',
    },
];

export default function Index({ wallets }: Props) {
    const [isOpen, setIsOpen] = useState(false);
    const [editingWallet, setEditingWallet] = useState<Wallet | null>(null);
    const [formData, setFormData] = useState({
        name: '',
        initial_balance: '',
        currency: 'USD',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingWallet) {
            router.put(`/wallets/${editingWallet.id}`, formData);
        } else {
            router.post('/wallets', formData);
        }
        setIsOpen(false);
        setEditingWallet(null);
        setFormData({ name: '', initial_balance: '', currency: 'USD' });
    };

    const handleEdit = (wallet: Wallet) => {
        setEditingWallet(wallet);
        setFormData({
            name: wallet.name,
            initial_balance: wallet.initial_balance.toString(),
            currency: wallet.currency,
        });
        setIsOpen(true);
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this wallet?')) {
            router.delete(`/wallets/${id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Wallets" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex justify-between items-center mb-6">
                    <h2 className="text-xl font-semibold">Wallets</h2>
                    <Dialog open={isOpen} onOpenChange={setIsOpen}>
                        <DialogTrigger asChild>
                            <Button>Add Wallet</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {editingWallet ? 'Edit Wallet' : 'Add New Wallet'}
                                </DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <Input
                                        placeholder="Wallet Name"
                                        value={formData.name}
                                        onChange={(e) =>
                                            setFormData({ ...formData, name: e.target.value })
                                        }
                                    />
                                </div>
                                <div>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        placeholder="Initial Balance"
                                        value={formData.initial_balance}
                                        onChange={(e) =>
                                            setFormData({ ...formData, initial_balance: e.target.value })
                                        }
                                    />
                                </div>
                                <div>
                                    <Input
                                        placeholder="Currency (e.g., USD)"
                                        value={formData.currency}
                                        maxLength={3}
                                        onChange={(e) =>
                                            setFormData({ ...formData, currency: e.target.value.toUpperCase() })
                                        }
                                    />
                                </div>
                                <Button type="submit">
                                    {editingWallet ? 'Update' : 'Create'}
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Initial balance</TableHead>
                            <TableHead>Currency</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {wallets.data.map((wallet) => (
                            <TableRow key={wallet.id}>
                                <TableCell>{wallet.name}</TableCell>
                                <TableCell>{formatCurrency(Number(wallet.initial_balance))}</TableCell>
                                <TableCell>{wallet.currency}</TableCell>
                                <TableCell>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleEdit(wallet)}
                                        className="m-2"
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => handleDelete(wallet.id)}
                                    >
                                        Delete
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                <Pagination links={wallets.links} />
            </div>
        </AppLayout>
    );
}
