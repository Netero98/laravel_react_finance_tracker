import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { BreadcrumbItem, Category, Transaction, Wallet } from '@/types';
import { Button } from '@/components/ui/button';
import { formatCurrency } from '@/utils/formatters';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
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
    exchangeRates: Record<string, number>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transactions',
        href: '/transactions',
    },
];

export default function Index({ transactions, categories, wallets, exchangeRates }: Props) {
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
        use_auto_conversion: true,
        to_amount: '',
        use_balance_input: true,
        new_balance: '',
    });

    const [isTransfer, setIsTransfer] = useState(false);
    const [calculatedAmount, setCalculatedAmount] = useState<number | null>(null);

    // Calculate converted amount when relevant fields change
    React.useEffect(() => {
        if (
            isTransfer &&
            formData.use_auto_conversion &&
            formData.amount &&
            formData.from_wallet_id &&
            formData.to_wallet_id
        ) {
            const fromWallet = wallets.data.find(w => w.id.toString() === formData.from_wallet_id);
            const toWallet = wallets.data.find(w => w.id.toString() === formData.to_wallet_id);

            if (fromWallet && toWallet && fromWallet.currency !== toWallet.currency) {
                const amount = parseFloat(formData.amount);
                const fromRate = exchangeRates[fromWallet.currency] || 1;
                const toRate = exchangeRates[toWallet.currency] || 1;

                // Convert to USD then to target currency
                const amountInUSD = amount / fromRate;
                const convertedAmount = amountInUSD * toRate;

                setCalculatedAmount(convertedAmount);

                // Update to_amount field with the calculated amount
                if (formData.use_auto_conversion) {
                    setFormData(prev => ({
                        ...prev,
                        to_amount: convertedAmount.toFixed(2)
                    }));
                }
            } else {
                setCalculatedAmount(null);
            }
        } else {
            setCalculatedAmount(null);
        }
    }, [
        isTransfer,
        formData.use_auto_conversion,
        formData.amount,
        formData.from_wallet_id,
        formData.to_wallet_id,
        wallets,
        exchangeRates
    ]);

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        let amount = formData.amount;

        if (formData.use_balance_input) {
            if (!formData.new_balance || !formData.wallet_id) {
                return;
            }

            const newBalance = parseFloat(formData.new_balance);
            const wallet = wallets.data.find(w => w.id.toString() === formData.wallet_id);

            if (!wallet) {
                console.log('Wallet not found');
                return;
            }

            const walletBalance = parseFloat(wallet.current_balance);

            if (isNaN(newBalance) || isNaN(walletBalance)) {
                console.log('Invalid number:', { newBalance, walletBalance });
                return;
            }

            amount = newBalance - walletBalance;
        }

        const dataToSubmit = { ...formData, amount: amount };

        // If it's a transfer between different currencies and auto conversion is enabled,
        // make sure the to_amount is set to the calculated amount
        if (isTransfer && formData.use_auto_conversion && calculatedAmount !== null) {
            dataToSubmit.to_amount = calculatedAmount.toString();
        }

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
            use_auto_conversion: true,
            to_amount: '',
            use_balance_input: false,
            new_balance: '',
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
            from_wallet_id: '',
            to_wallet_id: '',
            use_auto_conversion: true,
            to_amount: '',
            use_balance_input: false,
            new_balance: '',
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
                                <div className="space-y-2">
                                    <div className="flex items-center space-x-2">
                                        <input
                                            type="checkbox"
                                            id="use_balance_input"
                                            checked={formData.use_balance_input}
                                            onChange={(e) => {
                                                setFormData({
                                                    ...formData,
                                                    use_balance_input: e.target.checked,
                                                })
                                            }}
                                            className="h-4 w-4 rounded border-gray-300"
                                        />
                                        <label htmlFor="use_balance_input" className="text-sm font-medium">
                                            Enter new balance instead of amount
                                        </label>
                                    </div>

                                    {formData.use_balance_input
                                    ? (
                                        <Input
                                            type="number"
                                            step="100"
                                            placeholder="New Balance"
                                            value={formData.new_balance}
                                            onChange={(e) => {
                                                const newBalance = e.target.value;
                                                setFormData({ ...formData, new_balance: newBalance });
                                            }}
                                        />
                                    ) : (
                                        <Input
                                            type="number"
                                            step="100"
                                            placeholder="Amount"
                                            value={formData.amount}
                                            onChange={(e) =>
                                                setFormData({ ...formData, amount: e.target.value })
                                            }
                                        />
                                    )}
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
                                            onValueChange={(value) => {
                                                setFormData({ ...formData, wallet_id: value });
                                            }}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select wallet" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {wallets.data.map((wallet) => (
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
                                                    {wallets.data.map((wallet) => (
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
                                                    {wallets.data.map((wallet) => (
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

                                        {formData.from_wallet_id && formData.to_wallet_id && (() => {
                                            const fromWallet = wallets.data.find(w => w.id.toString() === formData.from_wallet_id);
                                            const toWallet = wallets.data.find(w => w.id.toString() === formData.to_wallet_id);

                                            if (fromWallet && toWallet && fromWallet.currency !== toWallet.currency) {
                                                return (
                                                    <>
                                                        <div className="flex items-center space-x-2 mt-4">
                                                            <input
                                                                type="checkbox"
                                                                id="use_auto_conversion"
                                                                checked={formData.use_auto_conversion}
                                                                onChange={(e) =>
                                                                    setFormData({
                                                                        ...formData,
                                                                        use_auto_conversion: e.target.checked
                                                                    })
                                                                }
                                                                className="h-4 w-4 rounded border-gray-300"
                                                            />
                                                            <label htmlFor="use_auto_conversion" className="text-sm font-medium">
                                                                Calculate at current exchange rate
                                                            </label>
                                                        </div>

                                                        {formData.use_auto_conversion && calculatedAmount !== null ? (
                                                            <div className="mt-2 text-sm">
                                                                <span className="font-medium">Converted amount: </span>
                                                                {formatCurrency(calculatedAmount)} {toWallet.currency}
                                                            </div>
                                                        ) : (
                                                            <div className="mt-2">
                                                                <label className="block text-sm font-medium mb-1">
                                                                    Amount to receive in {toWallet.currency}
                                                                </label>
                                                                <Input
                                                                    type="number"
                                                                    step="0.01"
                                                                    placeholder={`Amount in ${toWallet.currency}`}
                                                                    value={formData.to_amount}
                                                                    onChange={(e) =>
                                                                        setFormData({
                                                                            ...formData,
                                                                            to_amount: e.target.value
                                                                        })
                                                                    }
                                                                    disabled={formData.use_auto_conversion}
                                                                />
                                                            </div>
                                                        )}
                                                    </>
                                                );
                                            }
                                            return null;
                                        })()}
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
