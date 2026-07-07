export function normalizeTaxDocument(value) {
    return String(value ?? '').replace(/\D/g, '');
}

function hasRepeatedDigits(digits) {
    return /^(\d)\1+$/.test(digits);
}

export function formatCpf(value) {
    const digits = normalizeTaxDocument(value).slice(0, 11);

    return digits
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
}

export function formatCnpj(value) {
    const digits = normalizeTaxDocument(value).slice(0, 14);

    return digits
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2');
}

export function formatCpfOrCnpj(value) {
    const digits = normalizeTaxDocument(value).slice(0, 14);

    if (digits.length <= 11) {
        return formatCpf(digits);
    }

    return formatCnpj(digits);
}

export function isValidCpf(value) {
    const cpf = normalizeTaxDocument(value);

    if (cpf.length !== 11 || hasRepeatedDigits(cpf)) {
        return false;
    }

    let sum = 0;

    for (let index = 0; index < 9; index += 1) {
        sum += Number(cpf.charAt(index)) * (10 - index);
    }

    let digit = (sum * 10) % 11;

    if (digit === 10) {
        digit = 0;
    }

    if (digit !== Number(cpf.charAt(9))) {
        return false;
    }

    sum = 0;

    for (let index = 0; index < 10; index += 1) {
        sum += Number(cpf.charAt(index)) * (11 - index);
    }

    digit = (sum * 10) % 11;

    if (digit === 10) {
        digit = 0;
    }

    return digit === Number(cpf.charAt(10));
}

export function isValidCnpj(value) {
    const cnpj = normalizeTaxDocument(value);

    if (cnpj.length !== 14 || hasRepeatedDigits(cnpj)) {
        return false;
    }

    const firstWeights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    const secondWeights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    let sum = 0;

    for (let index = 0; index < 12; index += 1) {
        sum += Number(cnpj.charAt(index)) * firstWeights[index];
    }

    let digit = sum % 11;
    digit = digit < 2 ? 0 : 11 - digit;

    if (digit !== Number(cnpj.charAt(12))) {
        return false;
    }

    sum = 0;

    for (let index = 0; index < 13; index += 1) {
        sum += Number(cnpj.charAt(index)) * secondWeights[index];
    }

    digit = sum % 11;
    digit = digit < 2 ? 0 : 11 - digit;

    return digit === Number(cnpj.charAt(13));
}

export function isValidCpfOrCnpj(value) {
    const digits = normalizeTaxDocument(value);

    if (digits.length <= 11) {
        return isValidCpf(digits);
    }

    return isValidCnpj(digits);
}

export function formatTaxDocumentField(key, value) {
    if (key === 'cpf') {
        return formatCpf(value);
    }

    if (key === 'cpf_cnpj') {
        return formatCpfOrCnpj(value);
    }

    return value;
}

export function isTaxDocumentFieldComplete(key, value) {
    if (key === 'cpf') {
        return isValidCpf(value);
    }

    if (key === 'cpf_cnpj') {
        return isValidCpfOrCnpj(value);
    }

    return false;
}

export function taxDocumentFieldMaxLength(key) {
    if (key === 'cpf') {
        return 14;
    }

    if (key === 'cpf_cnpj') {
        return 18;
    }

    return null;
}

export function validateTaxDocumentField(key, value) {
    if (key !== 'cpf' && key !== 'cpf_cnpj') {
        return null;
    }

    if (!String(value ?? '').trim()) {
        return null;
    }

    if (key === 'cpf') {
        return isValidCpf(value) ? null : 'Informe um CPF válido.';
    }

    return isValidCpfOrCnpj(value) ? null : 'Informe um CPF ou CNPJ válido.';
}
