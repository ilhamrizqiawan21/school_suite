export interface PublicThemeTokens {
    primary?: string | null;
    accent?: string | null;
    surface?: string | null;
    ink?: string | null;
    muted?: string | null;
}

export const publicThemeStyle = (theme?: PublicThemeTokens) => ({
    '--public-primary': theme?.primary || '#3157a4',
    '--public-accent': theme?.accent || theme?.primary || '#3157a4',
    '--public-surface': theme?.surface || '#f5f8fb',
    '--public-ink': theme?.ink || '#173044',
    '--public-muted': theme?.muted || '#526673',
});
