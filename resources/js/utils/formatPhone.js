export function normalizePhone(value) {
    return String(value ?? '').replace(/\D/g, '').slice(0, 11);
}

export function formatPhone(value) {
    const digits = normalizePhone(value);

    if (digits.length === 0) {
        return '';
    }

    if (digits.length <= 2) {
        return `(${digits}`;
    }

    const areaCode = digits.slice(0, 2);
    const number = digits.slice(2);

    if (number.length <= 4) {
        return `(${areaCode}) ${number}`;
    }

    const isMobile = number[0] === '9' || number.length > 8;

    if (isMobile) {
        if (number.length <= 5) {
            return `(${areaCode}) ${number}`;
        }

        return `(${areaCode}) ${number.slice(0, 5)}-${number.slice(5)}`;
    }

    return `(${areaCode}) ${number.slice(0, 4)}-${number.slice(4)}`;
}
