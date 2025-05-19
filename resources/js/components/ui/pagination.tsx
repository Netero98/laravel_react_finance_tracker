import React from 'react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export interface PaginationProps {
  links: {
    url: string | null;
    label: string;
    active: boolean;
  }[];
  className?: string;
}

export function Pagination({ links, className }: PaginationProps) {
  return (
    <div className={cn('flex items-center justify-center space-x-2 mt-4', className)}>
      {links.map((link, index) => {
        // Skip if the link is just "..."
        if (link.label === '...') {
          return (
            <span
              key={`ellipsis-${index}`}
              className="px-2 py-1 text-gray-500"
            >
              ...
            </span>
          );
        }

        // Parse the label to display properly
        let label = link.label;
        if (label === '&laquo; Previous') {
          label = 'Previous';
        } else if (label === 'Next &raquo;') {
          label = 'Next';
        }

        return (
          <div key={index}>
            {link.url === null ? (
              <span
                className={cn(
                  'px-2 py-1 rounded-md text-sm',
                  link.active
                    ? 'bg-primary text-primary-foreground'
                    : 'text-gray-500'
                )}
              >
                {label}
              </span>
            ) : (
              <Link
                href={link.url}
                className={cn(
                  'px-2 py-1 rounded-md text-sm',
                  link.active
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-background hover:bg-accent hover:text-accent-foreground border border-input'
                )}
                preserveScroll
              >
                {label}
              </Link>
            )}
          </div>
        );
      })}
    </div>
  );
}
