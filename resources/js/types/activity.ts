export interface Activity {
    id: number;
    team_id: number;
    user_id: number | null;
    subject_type: string | null;
    subject_id: number | null;
    action: string;
    description: string;
    properties: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
    user?: {
        id: number;
        name: string;
    } | null;
    subject?: Record<string, unknown> | null;
}
