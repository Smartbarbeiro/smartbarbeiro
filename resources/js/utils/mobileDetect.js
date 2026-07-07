export function isMobilePhone() {
    if (typeof window === 'undefined' || typeof navigator === 'undefined') {
        return false;
    }

    const userAgent = navigator.userAgent || '';

    const isPhone = /Android.*Mobile|iPhone|iPod|Windows Phone|BlackBerry|Opera Mini|IEMobile/i.test(
        userAgent,
    );

    const isTablet =
        /iPad/i.test(userAgent) ||
        (/Android/i.test(userAgent) && !/Mobile/i.test(userAgent)) ||
        (navigator.maxTouchPoints > 1 && /Macintosh/i.test(userAgent));

    return isPhone && !isTablet;
}

export function getMobilePlatform() {
    if (typeof navigator === 'undefined') {
        return null;
    }

    const userAgent = navigator.userAgent || '';

    if (/iPhone|iPod|iPad/i.test(userAgent)) {
        return 'ios';
    }

    if (/Android/i.test(userAgent)) {
        return 'android';
    }

    return null;
}
