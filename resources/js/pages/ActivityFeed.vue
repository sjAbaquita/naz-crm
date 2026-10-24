<template>
  <div class="max-w-4xl mx-auto py-8">
    <h1 class="text-2xl font-bold mb-4">What's New</h1>

    <div class="bg-white rounded-lg shadow divide-y">
      <template v-if="activities.length">
        <details v-for="activity in activities" :key="activity.id" class="group p-4 hover:bg-gray-50">
          <summary class="flex items-center justify-between cursor-pointer select-none">
            <div class="flex items-center space-x-2">
              <svg class="w-5 h-5 text-gray-500 group-open:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              <svg class="w-5 h-5 text-gray-500 hidden group-open:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              <span class="font-medium text-gray-800">{{ activity.description }}</span>
            </div>
            <span class="text-sm text-gray-400 whitespace-nowrap">{{ formatDate(activity.created_at) }}</span>
          </summary>
          <div class="mt-2 text-sm text-gray-600 pl-8">
            <p><strong>Action:</strong> {{ activity.action }}</p>
            <p v-if="activity.subject_type && activity.subject_id">
              <strong>Subject:</strong> {{ activity.subject_type }} #{{ activity.subject_id }}
            </p>
            <pre v-if="activity.properties" class="mt-2 bg-gray-100 p-2 rounded">{{ JSON.stringify(activity.properties, null, 2) }}</pre>
          </div>
        </details>
      </template>
      <div v-else class="p-4 text-gray-500">No recent activity.</div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { PropType } from 'vue';
import type { Activity } from '@/types/activity';

const props = defineProps({
  activities: {
    type: Array as PropType<Activity[]>,
    required: true,
  },
});

function formatDate(dateStr: string): string {
  const d = new Date(dateStr);
  return d.toLocaleString(undefined, { dateStyle: 'short', timeStyle: 'short' });
}
</script>

<style scoped>
/* Simple accordion styling – using native <details> */
</style>
