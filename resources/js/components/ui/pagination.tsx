import React from 'react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { useWindowSize } from '@/hooks/use-window-size';

export interface PaginationProps {
  links: {
    url: string | null;
    label: string;
    active: boolean;
  }[];
  className?: string;
}

export function Pagination({ links, className }: PaginationProps) {
  const { width } = useWindowSize();
  const isMobile = width ? width < 640 : false; // sm breakpoint is 640px

  // Filter links for mobile view - keep only Previous, Next, current page, and first/last page
  const filteredLinks = React.useMemo(() => {
    if (!isMobile) return links;

    return links.filter((link, index) => {
      // Always keep Previous and Next links
      if (link.label === '&laquo; Previous' || link.label === 'Next &raquo;') {
        return true;
      }

      // Always keep the active (current) page
      if (link.active) {
        return true;
      }

      // Keep first and last numeric pages if they're not the current page
      const isNumeric = !isNaN(Number(link.label));
      if (isNumeric) {
        // Find the active link index
        const activeIndex = links.findIndex(l => l.active);

        // Keep pages that are immediately adjacent to the active page
        if (Math.abs(index - activeIndex) === 1) {
          return true;
        }

        // Keep first and last page
        if (index === 1 || index === links.length - 2) {
          return true;
        }
      }

      return false;
    });
  }, [links, isMobile]);

  return (
    <div className={cn('flex items-center justify-center space-x-2 mt-4', className)}>
      {filteredLinks.map((link, index) => {
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
