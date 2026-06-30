export function formatBarbershopUsernameInput(value) {
    return String(value ?? '').replace(/\s+/g, '_');
}
