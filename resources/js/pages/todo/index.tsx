import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Card } from '@/components/ui/card';
import React from 'react';
import { X, Pencil, Check } from 'lucide-react';
import { PageProps } from '@inertiajs/core';

interface Todo {
    id: number;
    title: string;
    completed: boolean;
}

interface Props extends PageProps {
    todos: {
        data: Todo[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'To do',
        href: '/todos',
    },
];

export default function index({ todos }: Props) {
    const initialForm = useForm({
        title: '',
        completed: false,
    });

    const updateForm = useForm({
        id: 0,
        title: '',
        completed: false,
    });

    const handleUpdate = (todo: Todo) => {
        updateForm.patch(
            `/todos/${todo.id}`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    updateForm.setData({
                        id: 0,
                        title: '',
                        completed: false,
                    })
                },
            }
        );
    };

    const handleStore = (e: React.FormEvent) => {
        e.preventDefault();

        initialForm.post(route('todos.store'), {
            preserveScroll: true,
            onSuccess: () => {
                initialForm.setData({
                    title: '',
                    completed: false,
                });
            },
        });
    };

    const handleToggleTodo = (todo: Todo) => {
        router.patch(`/todos/${todo.id}/toggle-completed`);
    };

    const deleteTodo = (todo: Todo) => {
        router.delete(`/todos/${todo.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Todo List" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card className="p-4">
                    <form onSubmit={handleStore} className="flex gap-2">
                        <Input
                            type="text"
                            value={initialForm.data.title}
                            onChange={(e) => initialForm.setData({ ...initialForm.data, title: e.target.value })}
                            placeholder="Add a new todo..."
                            className="flex-1"
                        />
                        <Button type="submit">Add Todo</Button>
                    </form>
                </Card>

                <Card className="flex flex-col gap-2 p-4">
                    <div className="flex flex-col gap-2">
                        {todos.data.map((todo) => (
                            <div
                                key={todo.id}
                                className="flex items-center justify-between gap-2 rounded-lg border p-3"
                            >
                                <div className="flex items-center gap-2 flex-1">
                                    <Checkbox
                                        checked={todo.completed}
                                        onCheckedChange={() => handleToggleTodo(todo)}
                                    />
                                    {updateForm.data.id === todo.id ? (
                                        <Input
                                            type="text"
                                            value={updateForm.data.title}
                                            onChange={(e) => updateForm.setData({ ...updateForm.data, title: e.target.value })}
                                            className="flex-1"
                                        />
                                    ) : (
                                        <span
                                            className={
                                                todo.completed ? 'text-muted-foreground line-through' : ''
                                            }
                                        >
                                            {todo.title}
                                        </span>
                                    )}
                                </div>
                                <div className="flex gap-2">
                                    {updateForm.data.id === todo.id ? (
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => handleUpdate(todo)}
                                        >
                                            <Check className="h-4 w-4" />
                                        </Button>
                                    ) : (
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() => updateForm.setData(todo)}
                                        >
                                            <Pencil className="h-4 w-4" />
                                        </Button>
                                    )}
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        onClick={() => deleteTodo(todo)}
                                    >
                                        <X className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                    {todos.data.length === 0 && (
                        <div className="text-center text-muted-foreground">
                            No todos yet. Add one above!
                        </div>
                    )}

                    {/* Pagination */}
                    {todos.last_page > 1 && (
                        <div className="flex items-center justify-center gap-2 mt-4">
                            {todos.links.map((link, i) => (
                                <Link
                                    key={i}
                                    href={link.url || ''}
                                    className={`px-4 py-2 rounded-md ${
                                        link.active
                                            ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                            : 'border border-input bg-background hover:bg-accent hover:text-accent-foreground'
                                    } ${!link.url ? 'pointer-events-none opacity-50' : ''}`}
                                    preserveScroll
                                    preserveState
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    )}

                </Card>
            </div>
        </AppLayout>
    );
}
