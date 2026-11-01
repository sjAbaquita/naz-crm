<script setup lang="ts">
import { PropType } from 'vue';
import { Head } from '@inertiajs/vue3';
import type { Activity } from '@/types/activity';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps({
  activities: {
    type: Array as PropType<Activity[]>,
    required: true,
  },
  team: {
    type: Object as PropType<any>,
    required: true,
  }
});

function formatDate(dateStr: string): string {
  const d = new Date(dateStr);
  return d.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

defineOptions({
    layout: (props: { team?: any | null }) => ({
        breadcrumbs: [
            {
                title: 'Activity Feed',
                href: props.team ? `/${props.team.slug}/activity` : '/activity',
            },
        ],
    }),
});
</script>

<template>
	<Head title="Activity Feed" />

    <div class="max-w-4xl mx-auto py-8 px-4">
        <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">Activity Feed</h1>

        <div class="space-y-4">
            <template v-if="activities.length">
                <Card v-for="activity in activities" :key="activity.id" class="shadow-sm">
                    <CardHeader class="pb-2">
                        <div class="flex justify-between items-center text-sm">
                            <span class="font-medium text-blue-600 dark:text-blue-400">
                                {{ activity.user?.name || 'System' }}
                            </span>
                            <span class="text-gray-400 text-xs">{{ formatDate(activity.created_at) }}</span>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <p class="text-gray-800 dark:text-gray-200">{{ activity.description }}</p>
                    </CardContent>
                </Card>
            </template>
            <div v-else class="p-8 text-center bg-gray-50 dark:bg-gray-800 rounded-lg text-gray-500">
                No recent activity.
            </div>
        </div>
    </div>
</template>
