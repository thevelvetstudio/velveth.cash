export function formatCOP(value: number | string | null | undefined) {
    const amount = Number(value ?? 0);

    return new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        maximumFractionDigits: 0,
    }).format(amount);
}

export function formatDate(value: string | Date | null | undefined) {
    if (!value) {
        return '-';
    }

    const rawValue = typeof value === 'string' ? value : value.toISOString();
    const date = new Date(rawValue);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    const hasTime = rawValue.includes('T') || rawValue.includes(' ');

    return new Intl.DateTimeFormat('es-CO', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        ...(hasTime ? { hour: 'numeric', minute: '2-digit' } : {}),
    }).format(date);
}
