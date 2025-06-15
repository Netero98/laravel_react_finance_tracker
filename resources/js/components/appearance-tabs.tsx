import { Appearance, useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { Moon, Sun } from 'lucide-react';
import { HTMLAttributes } from 'react';

export default function AppearanceToggleTab({ className = '', ...props }: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();

    // Только две темы: light и dark
    const nextAppearance = (current: Appearance): Appearance => {
        return current === 'light' ? 'dark' : 'light';
    };

    const getIcon = (appearance: Appearance) => {
        return appearance === 'light' ? Sun : Moon;
    };

    const getLabel = (appearance: Appearance) => {
        return appearance === 'light' ? 'Light' : 'Dark';
    };

    const Icon = getIcon(appearance);
    const label = getLabel(appearance);

    return (
        <div className={cn('inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800', className)} {...props}>
            <button
                onClick={() => updateAppearance(nextAppearance(appearance))}
                className={cn(
                    'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                    'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                )}
            >
                <Icon className="-ml-1 h-4 w-4" />
                <span className="ml-1.5 text-sm">{label}</span>
            </button>
        </div>
    );
}
