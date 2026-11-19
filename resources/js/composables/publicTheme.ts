import { usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types';

export interface PublicThemeTokens {
    primary?: string | null;
    accent?: string | null;
    surface?: string | null;
    ink?: string | null;
    muted?: string | null;
}

export const publicThemeStyle = (theme?: PublicThemeTokens) => {
    if (usePage<SharedProps>().props.publicPortalTheme === 'man4-banjar') {
        theme = { primary: '#047857', accent: '#b45309', surface: '#f8fafc', ink: '#0f172a', muted: '#475569' };
    }

    return {
        '--public-primary': theme?.primary || '#3157a4',
        '--public-accent': theme?.accent || theme?.primary || '#3157a4',
        '--public-surface': theme?.surface || '#f5f8fb',
        '--public-panel-surface': '#ffffff',
        '--public-panel-border': '#d8e2ee',
        '--public-hero-contrast': theme?.ink || '#123247',
        '--public-ink': theme?.ink || '#173044',
        '--public-muted': theme?.muted || '#526673',
    };
};
