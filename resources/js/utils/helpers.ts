export const toDateOnly = (date?: string | null) => {
    if (!date) {
        return '';
    }

    const d = new Date(date);
    if (isNaN(d.getTime())) {
        return date;
    }

    return d.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};
