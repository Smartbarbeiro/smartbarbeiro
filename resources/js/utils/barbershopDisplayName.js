export function barbershopDisplayName(username, fallback = '') {
    const fromUsername = String(username ?? '').replace(/_/g, ' ').trim();

    if (fromUsername !== '') {
        return fromUsername;
    }

    return String(fallback ?? '').trim();
}
