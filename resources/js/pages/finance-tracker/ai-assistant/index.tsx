import React, { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Bot, Send, User, Trash2 } from 'lucide-react';
import { router } from '@inertiajs/react';
import { v1 as uuidv1 } from 'uuid';
import ReactMarkdown from 'react-markdown';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'AI Assistant',
        href: '/ai-assistant',
    },
];

interface Message {
    id: string;
    content: string;
    role: string;
    timestamp: Date;
}

interface Props {
    chatHistory: Message[];
}

export default function AIAssistant({chatHistory}: Props) {
    const [inputMessage, setInputMessage] = useState('');

    const handleSendMessage = async () => {
        if (inputMessage.trim() === '') return;

        const userMessage: Message = {
            id: uuidv1(),
            content: inputMessage,
            role: 'user',
            timestamp: new Date(),
        };

        let dataToSubmit = chatHistory
        dataToSubmit.push(userMessage)

        router.post(
            route('ai-assistant.chat'),
            {
                chatHistory: dataToSubmit,
            },
            {
                preserveScroll: true,
                onSuccess: () => setInputMessage(''),
            });
    };

    const handleDeleteChatHistory = () => {
        if (confirm('Are you sure you want to delete your chat history?')) {
            router.delete(route('ai-assistant.delete-chat-history'));
        }
    };

    const handleKeyDown = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            handleSendMessage();
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="AI Assistant" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    {/* Chat Interface */}
                    <div className="lg:col-span-3 bg-white dark:bg-gray-800 rounded-xl shadow flex flex-col h-[calc(100vh-12rem)]">
                        {/* Chat Header */}
                        <div className="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <div className="flex items-center">
                                <Bot className="h-6 w-6 text-blue-500 mr-2" />
                                <h2 className="text-lg font-semibold">AI Financial Assistant</h2>
                            </div>
                            <button
                                onClick={handleDeleteChatHistory}
                                className="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-full transition-colors"
                                title="Delete chat history"
                            >
                                <Trash2 className="h-5 w-5" />
                            </button>
                        </div>

                        {/* Messages */}
                        <div className="flex-1 overflow-y-auto p-4 space-y-4">
                            {chatHistory.map((message) => (
                                <div
                                    key={message.id}
                                    className={`flex ${message.role === 'user' ? 'justify-end' : 'justify-start'}`}
                                >
                                    <div
                                        className={`max-w-[80%] rounded-lg p-3 ${
                                            message.role === 'user'
                                                ? 'bg-blue-500 text-white'
                                                : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'
                                        }`}
                                    >
                                        <div className="flex items-start mb-1">
                                            {!(message.role === 'user') && <Bot className="h-5 w-5 mr-2 mt-0.5" />}
                                            {message.role === 'user' && <User className="h-5 w-5 mr-2 mt-0.5" />}
                                            <div className="whitespace-pre-wrap markdown-content">
                                                {message.role === 'user' ? (
                                                    <p>{message.content}</p>
                                                ) : (
                                                    <ReactMarkdown>{message.content}</ReactMarkdown>
                                                )}
                                            </div>
                                        </div>
                                        <p className="text-xs opacity-70 text-right">
                                            {message.timestamp}
                                        </p>
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Input */}
                        <div className="p-4 border-t border-gray-200 dark:border-gray-700">
                            <div className="flex items-center">
                                <textarea
                                    value={inputMessage}
                                    onChange={(e) => setInputMessage(e.target.value)}
                                    onKeyDown={handleKeyDown}
                                    placeholder="Type your message..."
                                    className="flex-1 border border-gray-300 dark:border-gray-600 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-200 resize-none"
                                    rows={2}
                                />
                                <button
                                    onClick={handleSendMessage}
                                    className="ml-2 p-2 bg-blue-500 text-white rounded-full disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <Send className="h-5 w-5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
