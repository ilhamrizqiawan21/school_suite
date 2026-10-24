export interface User {
    id: number;
    name: string;
    email: string;
    username?: string;
    role?: string;
    role_label?: string;
}

export interface School {
    id: number;
    name: string;
    npsn: string;
}

export interface SharedProps {
    appName?: string;
    auth?: {
        user?: User;
        school?: School;
    };
    school?: {
        name: string;
    };
    activePeriod?: {
        academic_year: string;
        term: string;
        label: string;
        is_active: boolean;
    };
    flash?: {
        message?: string;
        error?: string;
    };
    [key: string]: unknown;
}
