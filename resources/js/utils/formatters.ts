/**
 * Format a number with commas as thousands separators
 * @param value - The number to format
 * @param decimals - The number of decimal places (default: 2)
 * @returns The formatted number as a string
 */
export function formatCurrency(value: number, decimals: number = 2): string {
  // First, fix the number to the specified number of decimal places
  const fixed = value.toFixed(decimals);

  // Split the number into integer and decimal parts
  const parts = fixed.split('.');
  const integerPart = parts[0];
  const decimalPart = parts.length > 1 ? '.' + parts[1] : '';

  // Add commas to the integer part
  const formattedIntegerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

  // Combine the formatted integer part with the decimal part
  return formattedIntegerPart + decimalPart;
}
