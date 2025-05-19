import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Transaction, Category, Wallet, BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
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

interface Props {
    transactions: Transaction[];
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
        type: 'expense' as 'income' | 'expense',
        category_id: '',
        wallet_id: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingTransaction) {
            router.put(`/transactions/${editingTransaction.id}`, formData);
        } else {
            router.post('/transactions', formData);
        }
        setIsOpen(false);
        setEditingTransaction(null);
        setFormData({
            amount: '',
            description: '',
            date: new Date().toISOString().split('T')[0],
            type: 'expense',
            category_id: '',
            wallet_id: '',
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
                                        value={formData.type}
                                        onValueChange={(value) =>
                                            setFormData({ ...formData, type: value as 'income' | 'expense' })
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select type" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="income">Income</SelectItem>
                                            <SelectItem value="expense">Expense</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Select
                                        value={formData.category_id}
                                        onValueChange={(value) =>
                                            setFormData({ ...formData, category_id: value })
                                        }
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {categories
                                                .filter((category) => category.type === formData.type)
                                                .map((category) => (
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
                            <TableHead>Type</TableHead>
                            <TableHead>Category</TableHead>
                            <TableHead>Description</TableHead>
                            <TableHead>Amount</TableHead>
                            <TableHead>Wallet</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {transactions.map((transaction) => (
                            <TableRow key={transaction.id}>
                                <TableCell>
                                    {new Date(transaction.date).toLocaleDateString()}
                                </TableCell>
                                <TableCell className="capitalize">
                                    {transaction.type}
                                </TableCell>
                                <TableCell>{transaction.category.name}</TableCell>
                                <TableCell>{transaction.description}</TableCell>
                                <TableCell
                                    className={
                                        transaction.type === 'income'
                                            ? 'text-green-600'
                                            : 'text-red-600'
                                    }
                                >
                                    {transaction.type === 'income' ? '+' : '-'}
                                    {Math.abs(transaction.amount).toFixed(2)}
                                </TableCell>
                                <TableCell>{transaction.wallet.name}</TableCell>
                                <TableCell>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleEdit(transaction)}
                                        className="mr-2"
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
            </div>
        </AppLayout>
    );
}
