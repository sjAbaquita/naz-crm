export type BoardPermissions = {
    canManageColumns: boolean;
    canEditCards: boolean;
};

export type UserMinimal = {
    id: number;
    name: string;
    email: string;
};

export type BoardCard = {
    id: number;
    column_id: number;
    title: string;
    description: string | null;
    position: number;
    assigned_to: number | null;
    assignee?: UserMinimal | null;
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
