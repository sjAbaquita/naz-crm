<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
import { index as boardIndexRoute } from '@/routes/board';
import { index as calendarIndexRoute } from '@/routes/calendar';
import type { Team } from '@/types';
import type { CalendarEvent } from '@/types/calendar-events';
import { toDateOnly } from '@/utils/helpers';

interface ColumnSummary {
    id: number;
    title: string;
    cardCount: number;
}

interface Props {
    currentTeam: Team;
    columnSummaries: ColumnSummary[];
    upcomingEvents: CalendarEvent[];
}

const props = defineProps<Props>();

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: props.currentTeam
                    ? dashboard(props.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 bg-white dark:bg-gray-900">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Team Overview</h1>

        <div class="grid gap-4 md:grid-cols-3">
            <!-- Column Summaries -->
            <div
                v-for="column in props.columnSummaries"
                :key="column.id"
                class="bg-gray-100 dark:bg-gray-800 rounded-lg shadow-sm p-5 flex flex-col items-center justify-center"
            >
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">{{ column.title }}</h3>
                <p class="text-4xl font-bold text-blue-600 dark:text-blue-400 mt-2">{{ column.cardCount }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-400">cards</p>
            </div>

            <!-- Empty state for columns -->
            <div
                v-if="props.columnSummaries.length === 0"
                class="md:col-span-3 bg-gray-100 dark:bg-gray-800 rounded-lg shadow-sm p-6 text-center text-gray-500 dark:text-gray-400"
            >
                <p class="text-lg font-medium mb-2">No columns yet!</p>
                <p>Create your first column on the <Link :href="boardIndexRoute(props.currentTeam.slug)" class="text-blue-600 hover:underline dark:text-blue-400">Board page</Link>.</p>
            </div>
        </div>

        <!-- Upcoming Events -->
        <div class="mt-6 bg-gray-100 dark:bg-gray-800 rounded-lg shadow-sm p-6 flex-1">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Upcoming Events</h2>
                <Link :href="calendarIndexRoute(props.currentTeam.slug)" class="text-blue-600 hover:underline dark:text-blue-400 text-sm font-medium">View All</Link>
            </div>

            <div v-if="props.upcomingEvents.length > 0" class="space-y-3">
                <div
                    v-for="event in props.upcomingEvents"
                    :key="event.id"
                    class="flex items-center p-3 bg-white dark:bg-gray-700 rounded-md shadow-xs border border-gray-200 dark:border-gray-600"
                >
                    <div
                        :class="{
                            'bg-blue-500': event.event_level === 'primary',
                            'bg-green-500': event.event_level === 'success',
                            'bg-red-500': event.event_level === 'danger',
                            'bg-yellow-500': event.event_level === 'warning',
                        }"
                        class="w-2 h-2 rounded-full mr-3 shrink-0"
                    ></div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 dark:text-white">{{ event.title }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ toDateOnly(event.start_date) }} - {{ toDateOnly(event.end_date) }}
                            <span v-if="event.creator"> (by {{ event.creator.name }})</span>
                        </p>
                    </div>
                </div>
            </div>
            <div v-else class="text-center text-gray-500 dark:text-gray-400 py-6">
                <p class="text-lg font-medium mb-2">No upcoming events.</p>
                <p>Plan your next event on the <Link :href="calendarIndexRoute(props.currentTeam.slug)" class="text-blue-600 hover:underline dark:text-blue-400">Calendar page</Link>.</p>
            </div>
        </div>
    </div>
</template>
