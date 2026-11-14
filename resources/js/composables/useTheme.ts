import { readonly, ref } from 'vue';

type Theme = 'light' | 'dark';
const storageKey = 'school-suite-theme';
const systemTheme = () => window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
function readPreference(): Theme {
    try {
        const stored = localStorage.getItem(storageKey);
        if (stored === 'light' || stored === 'dark') return stored;
    } catch { /* Storage is optional. */ }
    return systemTheme();
}

const preference = ref<Theme>(readPreference());
let lightPage = false;
let transition: ViewTransition | undefined;
let cleanupTimer: ReturnType<typeof setTimeout> | undefined;

function applyTheme() {
    const theme = lightPage ? 'light' : preference.value;
    document.documentElement.dataset.theme = theme;
    document.documentElement.style.colorScheme = theme;
}

export function setPageTheme(component: string) {
    lightPage = component.startsWith('Public/') || component === 'Welcome' || component === 'Auth/Login';
    applyTheme();
}

function setTheme(theme: Theme) {
    preference.value = theme;
    try { localStorage.setItem(storageKey, theme); } catch { /* Storage is optional. */ }
    transition?.skipTransition();
    clearTimeout(cleanupTimer);
    const root = document.documentElement;
    root.classList.remove('theme-changing');
    if (lightPage) {
        applyTheme();
    } else if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        root.classList.add('theme-changing');
        applyTheme();
        cleanupTimer = setTimeout(() => root.classList.remove('theme-changing'), 220);
    } else if (document.startViewTransition) {
        transition = document.startViewTransition(applyTheme);
        // A second toggle can intentionally cancel a snapshot before it is ready.
        void transition.ready.catch(() => undefined);
        void transition.finished.catch(() => undefined);
    } else {
        root.classList.add('theme-changing');
        applyTheme();
        cleanupTimer = setTimeout(() => root.classList.remove('theme-changing'), 220);
    }
}

export function setupThemeSync() {
    window.addEventListener('storage', event => {
        if (event.key === storageKey || event.key === null) {
            preference.value = readPreference();
            applyTheme();
        }
    });
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        try {
            const stored = localStorage.getItem(storageKey);
            if (stored === 'light' || stored === 'dark') return;
        } catch { /* Follow system without storage. */ }
        preference.value = systemTheme();
        applyTheme();
    });
}

export function useTheme() {
    return { theme: readonly(preference), toggleTheme: () => setTheme(preference.value === 'dark' ? 'light' : 'dark') };
}
