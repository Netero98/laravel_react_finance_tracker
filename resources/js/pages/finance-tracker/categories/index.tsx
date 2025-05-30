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
import { router } from '@inertiajs/react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Category, BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
import { Info } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

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
    categories: PaginatedData<Category>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categories',
        href: '/categories',
    },
];

export default function Index({ categories }: Props) {
    const [isOpen, setIsOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState<Category | null>(null);
    const [formData, setFormData] = useState({
        name: '',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        if (editingCategory) {
            router.put(`/categories/${editingCategory.id}`, formData);
        } else {
            router.post('/categories', formData);
        }
        setIsOpen(false);
        setEditingCategory(null);
        setFormData({ name: '' });
    };

    const handleEdit = (category: Category) => {
        setEditingCategory(category);
        setFormData({ name: category.name });
        setIsOpen(true);
    };

    const handleDelete = (id: number) => {
        if (confirm('Are you sure you want to delete this category? All category transactions will be deleted as well. This action cannot be undone.')) {
            router.delete(`/categories/${id}`);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Categories" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex justify-between items-center mb-6">
                    <h2 className="text-xl font-semibold">Categories</h2>
                    <Dialog open={isOpen} onOpenChange={setIsOpen}>
                        <DialogTrigger asChild>
                            <Button>Add Category</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {editingCategory ? 'Edit Category' : 'Add New Category'}
                                </DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div>
                                    <Input
                                        placeholder="Category Name"
                                        value={formData.name}
                                        onChange={(e) =>
                                            setFormData({ ...formData, name: e.target.value })
                                        }
                                    />
                                </div>
                                <Button type="submit">
                                    {editingCategory ? 'Update' : 'Create'}
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {categories.data.map((category) => (
                            <TableRow key={category.id}>
                                <TableCell>
                                    {category.name}
                                    {category.is_system && category.name === "Transfer" && (
                                        <TooltipProvider>
                                            <Tooltip delayDuration={0}>
                                                <TooltipTrigger asChild onClick={(e) => e.preventDefault()}>
                                                    <Button
                                                        variant="ghost"
                                                        className="ml-2 h-4 w-4 p-0 hover:bg-transparent"
                                                    >
                                                        <Info className="h-4 w-4 cursor-help text-muted-foreground" />
                                                    </Button>
                                                </TooltipTrigger>
                                                <TooltipContent>
                                                    <p>This is system category made for making transfers between wallets</p>
                                                </TooltipContent>
                                            </Tooltip>
                                        </TooltipProvider>
                                    )}
                                </TableCell>
                                <TableCell>
                                    { !category.is_system && <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleEdit(category)}
                                        className="m-2"
                                    >
                                        Edit
                                    </Button> }
                                    { !category.is_system &&  <Button
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => handleDelete(category.id)}
                                    >
                                        Delete
                                    </Button> }

                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>

                <Pagination links={categories.links} />
            </div>
        </AppLayout>
    );
}
