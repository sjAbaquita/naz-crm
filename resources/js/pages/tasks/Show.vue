<script setup lang="ts">
import { computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SubtaskList from '@/components/SubtaskList.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar, Flag, Link, User, ArrowLeft } from 'lucide-vue-next';

interface Props {
    card: {
        id: number;
        title: string;
        description: string | null;
        start_date: string | null;
        due_date: string | null;
        priority: 'Low' | 'Medium' | 'High' | 'Urgent';
        subtasks: Array<{
            id: number;
            title: string;
            is_completed: boolean;
            created_by: number | null;
            creator?: { id: number; name: string } | null;
            updater?: { id: number; name: string } | null;
        }>;
        dependencies: Array<{
            id: number;
            title: string;
            priority: string;
        }>;
        assignee?: { id: number; name: string } | null;
        labels?: Array<{ id: number; name: string; color: string }>;
    };
    team: { slug: string };
    permissions: {
        canEditCards: boolean;
        canManageSubtasks: boolean;
    };
}

const props = defineProps<Props>();
const page = usePage();
const currentUser = computed(() => (page.props.auth as any).user);

const priorityColors = {
    Low: 'bg-gray-100 text-gray-800',
    Medium: 'bg-blue-100 text-blue-800',
    High: 'bg-orange-100 text-orange-800',
    Urgent: 'bg-red-100 text-red-800',
};

const canEditSubtask = (subtask: any) => {
    return subtask.created_by === currentUser.value.id;
};

const goBack = () => {
    router.visit(`/${props.team.slug}/board`);
};

const reloadCard = () => {
    // Reload the card data without closing the modal
    router.reload({ preserveState: true, only: ['card'] } as any);
};
</script>

<template>
    <AppLayout>
        <Head :title="card.title" />

        <div class="max-w-4xl mx-auto p-6 space-y-6">
            <div class="flex items-center gap-4">
                <Button variant="ghost" size="icon" @click="goBack">
                    <ArrowLeft class="w-5 h-5" />
                </Button>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ card.title }}</h1>
                    <div class="flex items-center gap-2 mt-1">
                        <Badge :class="priorityColors[card.priority]">
                            <Flag class="w-3 h-3 mr-1" />
                            {{ card.priority }}
                        </Badge>
                        <span v-if="card.due_date" class="text-sm text-gray-500">
                            <Calendar class="w-3 h-3 inline mr-1" />
                            Due {{ new Date(card.due_date).toLocaleDateString() }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-2 space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Description</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-wrap">
                                {{ card.description || 'No description' }}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Subtasks</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <SubtaskList
                                :cardId="card.id"
                                :subtasks="card.subtasks"
                                :canCreate="permissions.canManageSubtasks"
                                :canEdit="canEditSubtask"
                                :teamSlug="team.slug"
                            />
                        </CardContent>
                    </Card>

                    <Card v-if="card.dependencies && card.dependencies.length > 0">
                        <CardHeader>
                            <CardTitle>Blocking Dependencies</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ul class="space-y-2">
                                <li
                                    v-for="dep in card.dependencies"
                                    :key="dep.id"
                                    class="flex items-center gap-2 text-sm"
                                >
                                    <Link class="w-4 h-4 text-gray-400" />
                                    <span>{{ dep.title }}</span>
                                    <Badge :class="priorityColors[dep.priority as keyof typeof priorityColors]" class="text-xs">
                                        {{ dep.priority }}
                                    </Badge>
                                </li>
                            </ul>
                        </CardContent>
                    </Card>
                </div>

                <div class="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <div v-if="card.assignee" class="flex items-center gap-2">
                                <User class="w-4 h-4 text-gray-400" />
                                <span class="text-sm">Assigned to {{ card.assignee.name }}</span>
                            </div>
                            <div v-if="card.start_date" class="flex items-center gap-2">
                                <Calendar class="w-4 h-4 text-gray-400" />
                                <span class="text-sm">Start {{ new Date(card.start_date).toLocaleDateString() }}</span>
                            </div>
                            <div v-if="card.labels && card.labels.length > 0">
                                <span class="text-sm text-gray-500">Labels:</span>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <span
                                        v-for="label in card.labels"
                                        :key="label.id"
                                        class="px-2 py-0.5 rounded text-xs"
                                        :style="{ backgroundColor: label.color, color: '#fff' }"
                                    >
                                        {{ label.name }}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
