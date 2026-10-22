<script setup lang="ts">
import { Bell } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Activity } from '@/types/activity';
import type { Team } from '@/types';

const props = defineProps<{
    activities: Activity[];
    currentTeam: Team | null;
}>();

// Debug log to verify props are received
// eslint-disable-next-line no-console
console.log('ActivityBell props:', props);

const isOpen = ref(false);

const page = usePage();
const auth = computed(() => page.props.auth);

function formatDate(dateStr: string): string {
    const d = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - d.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;

    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

function getActivityIcon(action: string): string {
    const iconMap: Record<string, string> = {
        'created_card': '📝',
        'updated_card': '✏️',
        'moved_card': '🔄',
        'created_event': '📅',
        'updated_event': '📅',
    };
    return iconMap[action] || '•';
}

function getActionText(action: string): string {
    const textMap: Record<string, string> = {
        'created_card': 'created',
        'updated_card': 'updated',
        'moved_card': 'moved',
        'created_event': 'scheduled',
        'updated_event': 'updated',
    };
    return textMap[action] || action;
}
</script>

<template>
    <div class="relative">
        <button
            @click="isOpen = !isOpen"
            class="relative flex h-9 w-9 items-center justify-center rounded-lg hover:bg-accent transition-colors"
        >
            <Bell class="h-5 w-5 opacity-80" />
            <span
                v-if="activities.length > 0"
                class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-red-500 text-[10px] font-bold text-white flex items-center justify-center"
            >
                {{ activities.length > 9 ? '9+' : activities.length }}
            </span>
        </button>

        <div
            v-if="isOpen"
            class="absolute right-0 top-full mt-2 w-80 max-h-[500px] overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg z-50"
        >
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="font-semibold text-gray-900 dark:text-white">What's New</h3>
                <button @click="isOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="overflow-y-auto max-h-[400px]">
                <div v-if="activities.length === 0" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                    <Bell class="w-12 h-12 mx-auto mb-2 opacity-30" />
                    <p>No recent activity</p>
                </div>
                <div v-else class="divide-y divide-gray-100 dark:divide-gray-700">
                    <div
                        v-for="activity in activities.slice(0, 10)"
                        :key="activity.id"
                        class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                    >
                        <div class="flex items-start gap-2">
                            <span class="text-lg">{{ getActivityIcon(activity.action) }}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-900 dark:text-white truncate">
                                    <span class="font-medium">{{ activity.user?.name || 'Someone' }}</span>
                                    {{ getActionText(activity.action) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                    {{ activity.description }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                    {{ formatDate(activity.created_at) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-4 py-2 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                <a
                    v-if="currentTeam"
                    :href="`/${currentTeam.slug}/activity`"
                    class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline block text-center"
                >
                    View all activity →
                </a>
            </div>
        </div>

        <div v-if="isOpen" class="fixed inset-0 z-40" @click="isOpen = false"></div>
    </div>
</template>
