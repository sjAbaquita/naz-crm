export type Label = {
    id: number;
    name: string;
    color: string;
};

export type BoardPermissions = {
    canManageColumns: boolean;
    canEditCards: boolean;
};

export type UserMinimal = {
    id: number;
    name: string;
    email: string;
};

export type Subtask = {
    id: number;
    board_card_id: number;
    title: string;
    is_completed: boolean;
    position: number;
    created_by: number | null;
    updated_by: number | null;
    creator?: { id: number; name: string } | null;
    updater?: { id: number; name: string } | null;
    created_at: string;
    updated_at: string;
};

export type BoardCardDependency = {
    id: number;
    board_card_id: number;
    depends_on_card_id: number;
    created_at: string;
    updated_at: string;
};

// Update BoardCard type:
export type Attachment = {
    id: number;
    board_card_id: number;
    filename: string;
    filepath: string;
    mime_type: string;
    size: number;
    uploaded_by: number;
    created_at: string;
    updated_at: string;
};

export type BoardCard = {
    id: number;
    column_id: number;
    title: string;
    description: string | null;
    position: number;
    assigned_to: number | null;
    assignee?: UserMinimal | null;
    labels?: Label[];
    start_date?: string | null;
    due_date?: string | null;
    priority?: 'Low' | 'Medium' | 'High' | 'Urgent';
    subtasks?: Subtask[];
    dependencies?: BoardCardDependency[];
    is_completable?: boolean;
    attachments?: Attachment[];
};

export type Card = BoardCard;

export type BoardColumn = {
    id: number;
    board_id?: number;
    title: string;
    position?: number;
    cards: BoardCard[];
};

export type Column = BoardColumn;

export type Board = {
    id: number;
    name: string;
    columns: BoardColumn[];
};
