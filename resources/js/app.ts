import '../css/app.css';
import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { setPageTheme, setupThemeSync } from './composables/useTheme';

setupThemeSync();

type InertiaVisitUrl = Parameters<typeof router.visit>[0];

const appBasePath = document.querySelector<HTMLMetaElement>('meta[name="app-base-path"]')
    ?.content
    ?.replace(/\/+$/, '') ?? '';

const prefixAppPath = (path: string): string => {
    if (!appBasePath || !path.startsWith('/') || path.startsWith('//')) return path;
    if (path === appBasePath || path.startsWith(`${appBasePath}/`)) return path;

    return `${appBasePath}${path}`;
};

const normalizeVisitUrl = (url: InertiaVisitUrl): InertiaVisitUrl => {
    if (typeof url === 'string') return prefixAppPath(url);
    if (url instanceof URL) return url;

    return { ...url, url: prefixAppPath(url.url) };
};

const originalVisit = router.visit.bind(router);
router.visit = ((url: InertiaVisitUrl, options?: Parameters<typeof router.visit>[1]) => {
    originalVisit(normalizeVisitUrl(url), options);
}) as typeof router.visit;

const setupTableScrollAffordances = () => {
    const tableScrollers = new Set<HTMLElement>();
    const resizeObserver = typeof ResizeObserver === 'undefined'
        ? null
        : new ResizeObserver(() => window.requestAnimationFrame(refreshTableScrollers));

    const updateTableScroller = (element: HTMLElement) => {
        const maxScrollLeft = element.scrollWidth - element.clientWidth;
        const hasOverflow = maxScrollLeft > 1;
        const canScrollLeft = hasOverflow && element.scrollLeft > 1;
        const canScrollRight = hasOverflow && element.scrollLeft < maxScrollLeft - 1;

        element.classList.toggle('table-scroll-affordance', hasOverflow);
        element.classList.toggle('can-scroll-left', canScrollLeft);
        element.classList.toggle('can-scroll-right', canScrollRight);
    };

    const refreshTableScrollers = () => {
        const currentTableScrollers = new Set<HTMLElement>();

        document.querySelectorAll<HTMLElement>('.table-scroll, .table-wrap, .table-card, .overflow-x-auto, [class*="table-wrap"]').forEach((element) => {
            const isTableScroller = element.classList.contains('table-scroll')
                || element.classList.contains('table-wrap')
                || element.classList.contains('table-card')
                || Array.from(element.classList).some((className) => className.includes('table-wrap'))
                || Array.from(element.children).some((child) => child.tagName === 'TABLE');

            if (isTableScroller) currentTableScrollers.add(element);
        });

        tableScrollers.forEach((element) => {
            if (!currentTableScrollers.has(element)) {
                resizeObserver?.unobserve(element);
                tableScrollers.delete(element);
            }
        });

        currentTableScrollers.forEach((element) => {
            if (tableScrollers.has(element)) return;

            tableScrollers.add(element);
            element.addEventListener('scroll', () => updateTableScroller(element), { passive: true });
            resizeObserver?.observe(element);
        });

        tableScrollers.forEach(updateTableScroller);
    };

    const scheduleRefresh = () => window.requestAnimationFrame(refreshTableScrollers);

    window.addEventListener('resize', scheduleRefresh);
    router.on('navigate', scheduleRefresh);
    scheduleRefresh();
};

createInertiaApp({
    title: (title) => (title ? `${title} — School Suite` : 'School Suite'),
    resolve: (name) => {
        setPageTheme(name);
        const pages = import.meta.glob<DefineComponent>('./Pages/**/*.vue');
        const page = pages[`./Pages/${name}.vue`];
        if (!page) {
            throw new Error(`Inertia page not found: ./Pages/${name}.vue`);
        }
        return page();
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
        setupTableScrollAffordances();
    },
});

