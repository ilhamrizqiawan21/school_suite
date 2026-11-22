export interface AdminTheme {
    primary: string;
    accent: string;
    success: string;
    warning: string;
    danger: string;
    font: 'system' | 'serif';
    radius: number;
    density: 'comfortable' | 'compact';
    navigation_default?: 'active' | 'expanded';
}

export const defaultAdminTheme: AdminTheme = {
    primary: '#3157a4', accent: '#7255ae', success: '#277450', warning: '#80500d', danger: '#a82f43',
    font: 'system', radius: 8, density: 'comfortable', navigation_default: 'active',
};

export function applyAdminBranding(theme?: Partial<AdminTheme> | null) {
    const root = document.documentElement;
    const keys = ['primary', 'accent', 'success', 'warning', 'danger'] as const;
    if (!theme) {
        delete root.dataset.adminBranding;
        for (const key of keys) root.style.removeProperty(`--admin-${key}`);
        for (const key of ['font', 'radius', 'padding']) root.style.removeProperty(`--admin-${key}`);
        return;
    }
    const values = { ...defaultAdminTheme, ...theme };
    root.dataset.adminBranding = 'true';
    root.style.setProperty('--school-primary', /^#[0-9a-f]{6}$/i.test(values.primary) ? values.primary : defaultAdminTheme.primary);
    for (const key of keys) {
        root.style.setProperty(`--admin-${key}`, /^#[0-9a-f]{6}$/i.test(values[key]) ? values[key] : defaultAdminTheme[key]);
    }
    root.style.setProperty('--admin-font', values.font === 'serif' ? 'Georgia, "Times New Roman", serif' : 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif');
    root.style.setProperty('--admin-radius', `${values.radius === 6 ? 6 : 8}px`);
    root.style.setProperty('--admin-padding', values.density === 'compact' ? '12px' : '20px');
}
