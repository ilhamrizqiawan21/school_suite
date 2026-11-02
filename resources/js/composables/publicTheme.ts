export interface PublicThemeTokens {
    primary?: string | null;
    accent?: string | null;
    surface?: string | null;
    ink?: string | null;
    muted?: string | null;
}

export const publicThemeStyle = (theme?: PublicThemeTokens) => ({
    '--public-primary': theme?.primary || '#3157a4',
    '--public-accent': theme?.accent || '#8a661f',
    '--public-surface': theme?.surface || '#fffdf8',
    '--public-ink': theme?.ink || '#173044',
    '--public-muted': theme?.muted || '#526673',
});
