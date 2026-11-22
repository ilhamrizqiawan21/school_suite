import { usePage } from '@inertiajs/vue3';

type ContentBlocks = Record<string, Record<string, unknown>>;

export function usePublicContentText() {
    const page = usePage<{ contentBlocks?: ContentBlocks; publicContentBlocks?: ContentBlocks }>();

    return (block: string, field: string, fallback: string): string => {
        const value = page.props.contentBlocks?.[block]?.[field] ?? page.props.publicContentBlocks?.[block]?.[field];

        return typeof value === 'string' ? value : fallback;
    };
}
