import * as React from "react";
import { CheckIcon, ChevronDownIcon, ChevronUpIcon, SearchIcon } from "lucide-react";
import { cn } from "@/lib/utils";
import { Input } from "./input";

interface ComboboxOption {
  value: string;
  label?: string;
}

interface ComboboxProps {
  options: ComboboxOption[];
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  className?: string;
  searchPlaceholder?: string;
}

export function Combobox({
  options,
  value,
  onChange,
  placeholder = "Select an option",
  className,
  searchPlaceholder = "Search...",
}: ComboboxProps) {
  const [open, setOpen] = React.useState(false);
  const [searchQuery, setSearchQuery] = React.useState("");
  const inputRef = React.useRef<HTMLInputElement>(null);
  const containerRef = React.useRef<HTMLDivElement>(null);

  // Filter options based on search query
  const filteredOptions = React.useMemo(() => {
    if (!searchQuery) return options;
    return options.filter((option) => {
      const optionText = option.label || option.value;
      return optionText.toLowerCase().includes(searchQuery.toLowerCase());
    });
  }, [options, searchQuery]);

  // Handle click outside to close dropdown
  React.useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (containerRef.current && !containerRef.current.contains(event.target as Node)) {
        setOpen(false);
      }
    };

    document.addEventListener("mousedown", handleClickOutside);
    return () => {
      document.removeEventListener("mousedown", handleClickOutside);
    };
  }, []);

  // Focus input when dropdown opens
  React.useEffect(() => {
    if (open && inputRef.current) {
      inputRef.current.focus();
    }
  }, [open]);

  // Get display value
  const displayValue = React.useMemo(() => {
    if (!value) return placeholder;
    const selectedOption = options.find((option) => option.value === value);
    return selectedOption ? (selectedOption.label || selectedOption.value) : value;
  }, [value, options, placeholder]);

  return (
    <div className="relative" ref={containerRef}>
      <div
        className={cn(
          "border-input data-[placeholder]:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive flex h-9 w-full items-center justify-between rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none cursor-pointer",
          className
        )}
        onClick={() => setOpen(!open)}
      >
        <span className="truncate">{displayValue}</span>
        <ChevronDownIcon className="size-4 opacity-50" />
      </div>

      {open && (
        <div className="bg-popover text-popover-foreground absolute z-50 mt-1 max-h-60 w-full overflow-hidden rounded-md border shadow-md">
          <div className="flex items-center border-b px-3 py-2">
            <SearchIcon className="mr-2 size-4 opacity-50" />
            <Input
              ref={inputRef}
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder={searchPlaceholder}
              className="border-0 focus-visible:ring-0 focus-visible:ring-offset-0 h-8"
              onClick={(e) => e.stopPropagation()}
            />
          </div>
          <div className="max-h-48 overflow-y-auto p-1">
            {filteredOptions.length > 0 ? (
              filteredOptions.map((option) => (
                <div
                  key={option.value}
                  className={cn(
                    "focus:bg-accent focus:text-accent-foreground relative flex w-full cursor-pointer items-center rounded-sm py-1.5 px-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground",
                    value === option.value && "bg-accent text-accent-foreground"
                  )}
                  onClick={() => {
                    onChange(option.value);
                    setOpen(false);
                    setSearchQuery("");
                  }}
                >
                  <span className="flex-1">{option.label || option.value}</span>
                  {value === option.value && (
                    <CheckIcon className="size-4 ml-2" />
                  )}
                </div>
              ))
            ) : (
              <div className="py-6 text-center text-sm text-muted-foreground">No results found.</div>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
