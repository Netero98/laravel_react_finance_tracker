import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Transaction, Category, Wallet, BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { formatCurrency } from '@/utils/formatters';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { router } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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
    transactions: PaginatedData<Transaction>;
    categories: Category[];
    wallets: Wallet[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transactions',
        href: '/transactions',
    },
];

export default function Index({ transactions, categories, wallets }: Props) {
    const [isOpen, setIsOpen] = useState(false);
    const [editingTransaction, setEditingTransaction] = useState<Transaction | null>(null);
    const [formData, setFormData] = useState({
        amount: '',
        description: '',
        date: new Date().toISOString().split('T')[0],
        category_id: '',
        wallet_id: '',
        from_wallet_id: '',
        to_wallet_id: '',
    });

    const [isTransfer, setIsTransfer] = useState(false);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        const dataToSubmit = { ...formData };

        if (editingTransaction) {
            router.put(`/transactions/${editingTransaction.id}`, dataToSubmit);
        } else {
            router.post('/transactions', dataToSubmit);
        }

        setIsOpen(false);
        setEditingTransaction(null);
        setIsTransfer(false);
        setFormData({
            amount: '',
            description: '',
            date: new Date().toISOString().split('T')[0],
            category_id: '',
            wallet_id: '',
            from_wallet_id: '',
            to_wallet_id: '',
        });
    };

    const handleEdit = (transaction: Transaction) => {
        setEditingTransaction(transaction);
        setFormData({
            amount: Math.abs(transaction.amount).toString(),
            description: transaction.description || '',
            date: new Date(transaction.date).toISOString().split('T')[0],
            type: transaction.type,
            category_id: transaction.category_id.toString(),
            wallet_id: transaction.wallet_id.toString(),
        });
        setIsOpen(true);
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this transaction?')) {
            router.delete(`/transactions/${id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transactions" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex justify-between items-center mb-6">
                    <h2 className="text-xl font-semibold">Transactions</h2>
                    <Dialog open={isOpen} onOpenChange={setIsOpen}>
                        <DialogTrigger asChild>
                            <Button>Add Transaction</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {editingTransaction ? 'Edit Transaction' : 'Add New Transaction'}
                                </DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        placeholder="Amount"
                                        value={formData.amount}
                                        onChange={(e) =>
                                            setFormData({ ...formData, amount: e.target.value })
                                        }
                                    />
                                </div>
                                <div>
                                    <Input
                                        placeholder="Description"
                                        value={formData.description}
                                        onChange={(e) =>
                                            setFormData({ ...formData, description: e.target.value })
                                        }
                                    />
                                </div>
                                <div>
                                    <Input
                                        type="date"
                                        value={formData.date}
                                        onChange={(e) =>
                                            setFormData({ ...formData, date: e.target.value })
                                        }
                                    />
                                </div>
                                <div>
                                    <Select
                                        value={formData.category_id}
                                        onValueChange={(value) => {
                                            const selectedCategory = categories.find(
                                                (cat) => cat.id.toString() === value
                                            );
                                            const isTransferCategory = selectedCategory?.name === 'Transfer' && selectedCategory?.is_system;
                                            setIsTransfer(isTransferCategory);
                                            setFormData({ ...formData, category_id: value });
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories.map((category) => (
                                                    <SelectItem
                                                        key={category.id}
                                                        value={category.id.toString()}
                                                    >
                                                        {category.name}
                                                    </SelectItem>
                                                ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                {!isTransfer ? (
                                    <div>
                                        <Select
                                            value={formData.wallet_id}
                                            onValueChange={(value) =>
                                                setFormData({ ...formData, wallet_id: value })
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select wallet" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {wallets.map((wallet) => (
                                                    <SelectItem
                                                        key={wallet.id}
                                                        value={wallet.id.toString()}
                                                    >
                                                        {wallet.name} ({wallet.currency})
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                ) : (
                                    <>
                                        <div>
                                            <label className="block text-sm font-medium mb-1">From Wallet</label>
                                            <Select
                                                value={formData.from_wallet_id}
                                                onValueChange={(value) =>
                                                    setFormData({ ...formData, from_wallet_id: value, wallet_id: value })
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select source wallet" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {wallets.map((wallet) => (
                                                        <SelectItem
                                                            key={wallet.id}
                                                            value={wallet.id.toString()}
                                                        >
                                                            {wallet.name} ({wallet.currency})
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium mb-1">To Wallet</label>
                                            <Select
                                                value={formData.to_wallet_id}
                                                onValueChange={(value) =>
                                                    setFormData({ ...formData, to_wallet_id: value })
                                                }
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select destination wallet" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {wallets.map((wallet) => (
                                                        <SelectItem
                                                            key={wallet.id}
                                                            value={wallet.id.toString()}
                                                        >
                                                            {wallet.name} ({wallet.currency})
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </>
                                )}
                                <Button type="submit">
                                    {editingTransaction ? 'Update' : 'Create'}
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead className="hidden lg:table-cell">Description</TableHead>
                            <TableHead>Amount</TableHead>
                            <TableHead className="hidden lg:table-cell">Wallet</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {transactions.data.map((transaction) => (
                            <TableRow key={transaction.id}>
                                <TableCell>
                                    {new Date(transaction.date).toLocaleDateString()}
                                </TableCell>
                                <TableCell>{transaction.category.name}</TableCell>
                                <TableCell className="hidden lg:table-cell">{transaction.description}</TableCell>
                                <TableCell
                                    className={
                                        transaction.amount > 0
                                            ? 'text-green-600'
                                            : 'text-red-600'
                                    }
                                >
                                    {transaction.amount > 0 ? '+' : '-'}
                                    {formatCurrency(Math.abs(transaction.amount))}
                                </TableCell>
                                <TableCell className="hidden lg:table-cell">{transaction.wallet.name}</TableCell>
                                <TableCell>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleEdit(transaction)}
                                        className="m-2"
                                    >
                                        Edit
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => handleDelete(transaction.id)}
                                    >
                                        Delete
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                <Pagination links={transactions.links} />
            </div>
        </AppLayout>
    );
}
