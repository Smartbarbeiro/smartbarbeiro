import { onMounted, onUnmounted } from 'vue';

export function useMarketingMobileMenu(menuId) {
    let menu = null;

    const onShow = () => {
        menu?.classList.add('mobile-menu-panel--opening');
        document.body.classList.add('mobile-menu-open');
    };

    const onShown = () => {
        menu?.classList.remove('mobile-menu-panel--opening');
    };

    const onHide = () => {
        menu?.classList.remove('mobile-menu-panel--opening');
        document.body.classList.remove('mobile-menu-open');
    };

    const closeOnAnchorClick = () => {
        if (window.innerWidth >= 992 || !menu?.classList.contains('show')) {
            return;
        }

        window.bootstrap?.Collapse.getOrCreateInstance(menu).hide();
    };

    onMounted(() => {
        menu = document.getElementById(menuId);

        if (!menu) {
            return;
        }

        menu.addEventListener('show.bs.collapse', onShow);
        menu.addEventListener('shown.bs.collapse', onShown);
        menu.addEventListener('hide.bs.collapse', onHide);

        menu.querySelectorAll('a[href*="#"]').forEach((link) => {
            link.addEventListener('click', closeOnAnchorClick);
        });
    });

    onUnmounted(() => {
        if (!menu) {
            return;
        }

        menu.removeEventListener('show.bs.collapse', onShow);
        menu.removeEventListener('shown.bs.collapse', onShown);
        menu.removeEventListener('hide.bs.collapse', onHide);

        menu.querySelectorAll('a[href*="#"]').forEach((link) => {
            link.removeEventListener('click', closeOnAnchorClick);
        });

        document.body.classList.remove('mobile-menu-open');
    });
}
