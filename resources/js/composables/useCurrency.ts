export function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('sr-RS', {
        style: 'currency',
        currency: 'RSD',
        minimumFractionDigits: 2,
    }).format(amount);
}

export function formatNumber(value: number): string {
    return new Intl.NumberFormat('sr-RS', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(value);
}
