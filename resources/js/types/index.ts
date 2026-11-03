export interface AuthUser {
    id: number;
    name: string;
    email: string;
}

export interface SharedProps {
    auth: { user: AuthUser; roles: string[]; permissions: string[] } | null;
    flash: { success: string | null };
    [key: string]: unknown;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
}
