export interface User {
    id: number;
    name: string;
    email: string;
    username?: string;
    role?: string;
    role_label?: string;
    phone?: string;
    avatar_url?: string | null;
}

export interface School {
    id: number;
    name: string;
    npsn: string;
    logo_url?: string | null;
    favicon_url?: string | null;
    theme_primary?: string;
}

export interface Student {
    id: number;
    school_id: number;
    nis: string;
    nisn?: string | null;
    name: string;
    gender: 'L' | 'P';
    birth_place?: string | null;
    birth_date?: string | null;
    religion?: string | null;
    address?: string | null;
    parent_phone?: string | null;
    parent_name?: string | null;
    status: 'active' | 'graduated' | 'transferred' | 'dropped_out';
    enrollments?: Array<{
        id: number;
        class_group?: {
            id: number;
            name: string;
            grade_level: number;
        };
    }>;
}

export interface Teacher {
    id: number;
    school_id: number;
    nip?: string | null;
    name: string;
    gender: 'L' | 'P';
    phone?: string | null;
    email?: string | null;
    employment_status?: string | null;
    is_active: boolean;
    homeroom_class_groups?: Array<{
        id: number;
        name: string;
    }>;
}

export interface ClassGroup {
    id: number;
    school_id: number;
    academic_year_id: number;
    grade_level: number;
    name: string;
    homeroom_teacher_id?: number | null;
    capacity: number;
    enrollments_count?: number;
    homeroom_teacher?: Teacher | null;
}

export interface SharedProps {
    appName?: string;
    auth?: {
        user?: User;
        school?: School;
    };
    school?: {
        name: string;
        logo_url?: string | null;
        favicon_url?: string | null;
        theme_primary?: string;
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

