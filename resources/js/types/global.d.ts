import type { Auth } from '@/types/auth';
import type { Team } from '@/types/teams';

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            currentTeam: Team | null;
            teams: Team[];
            [key: string]: unknown;
        };
    }
}

declare global {
    function route(name?: string, params?: Record<string, any> | Array<any>, absolute?: boolean, config?: any): any;
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: any;
        $page: any;
        $headManager: any;
    }
}
