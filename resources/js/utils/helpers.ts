export const toDateOnly = (date?: string | null) => {
    if (!date) {
return '';
}

    const d = new Date(date);

    return d.toISOString().split('T')[0];
};
