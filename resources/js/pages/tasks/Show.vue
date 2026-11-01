<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import SubtaskList from '@/components/SubtaskList.vue';
import AttachmentList from '@/components/AttachmentList.vue';
import AttachmentUpload from '@/components/AttachmentUpload.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ArrowLeft, Calendar, Flag, Link, User, ChevronDown, ChevronRight } from 'lucide-vue-next';
import type { Team } from '@/types';
import type { BoardCard, Label } from '@/types/boards';
import type { Activity } from '@/types/activity';
import cardsRoutes from '@/routes/cards';

interface Props {
    card: BoardCard & { creator?: { id: number; name: string } | null };
    team: { slug: string };
    permissions: {
        canEditCards: boolean;
        canManageSubtasks: boolean;
    };
}

const props = defineProps<Props>();
const page = usePage();
const currentUser = computed(() => (page.props.auth as any).user);

const isEditing = ref(false);
const cardForm = useForm({
    title: props.card.title,
    description: props.card.description || '',
    assigned_to: props.card.assigned_to || null,
    labels: props.card.labels?.map(l => l.id) || [],
    start_date: props.card.start_date || '',
    due_date: props.card.due_date || '',
    priority: props.card.priority || 'Low',
});

// Activity log for the selected card
const cardActivityLog = ref<{ action: string; description: string; created_at: string; user?: { name: string } | null }[]>([])
const showActivityLog = ref(false)

const loadCardActivity = async (card: BoardCard) => {
    try {
        const response = await fetch(`/${props.team.slug}/cards/${card.id}/activity`);
        if (response.ok) {
            const data = await response.json();
            // Assuming the new endpoint returns a list of activities directly
            // If it returns card data with activities nested, adjust accordingly
            if (data.activities) {
                cardActivityLog.value = data.activities.map((a: Activity) => ({
                    action: a.action,
                    description: a.description,
                    created_at: a.created_at,
                    user: a.user || null,
                }));
                showActivityLog.value = cardActivityLog.value.length > 0;
                return;
            }
        }
    } catch (error) {
        console.error('Failed to fetch card activity, falling back to recent activities', error);
    }

    // Fallback to filtering recent activities (shared via Inertia middleware)
    const recentActivities = page.props.recentActivities as Activity[];
    const cardActivities = recentActivities.filter(a =>
        (a.subject_type === 'App\\Models\\BoardCard' || a.subject_type === 'App\\\\Models\\\\BoardCard')
        && a.subject_id === card.id
    )
    cardActivityLog.value = cardActivities.map(a => ({
        action: a.action,
        description: a.description,
        created_at: a.created_at,
        user: a.user || null,
    }))
    showActivityLog.value = cardActivities.length > 0
};

// Load activity on component mount and when card data changes
watch(() => props.card, (newCard) => {
    loadCardActivity(newCard);
}, { immediate: true });

// Labels
const newLabelName = ref('');
const availableLabels = computed(() => (page.props.labels as Label[]) || []);

const createLabel = () => {
    if (!newLabelName.value.trim()) {
        return;
    }
    router.post(`/${props.team.slug}/labels`, {
        name: newLabelName.value,
        color: '#3b82f6',
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newLabelName.value = '';
            router.reload({ only: ['card', 'labels'] });
        },
    });
};

const saveCard = () => {
    if (!props.permissions.canEditCards) {
        return;
    }
    cardForm.put(cardsRoutes.update({
        current_team: props.team.slug,
        id: props.card.id
    }).url, {
        onSuccess: () => {
            isEditing.value = false;
            router.reload({ only: ['card'] });
        },
    });
};

const deleteCard = () => {
    if (!confirm('Are you sure you want to delete this card?')) {
        return;
    }
    router.delete(cardsRoutes.destroy({
        current_team: props.team.slug,
        id: props.card.id
    }).url, {
        onSuccess: () => {
            goBack();
        },
    });
};

const canEditSubtask = (subtask: any) => {
    return subtask.created_by === currentUser.value.id || props.permissions.canManageSubtasks;
};

const goBack = () => {
    router.visit(`/${props.team.slug}/board`);
};

const refreshCardSubtasks = () => {
    router.reload({ only: ['card'] });
};

const refreshCardAttachments = () => {
    router.reload({ only: ['card'] });
};

const priorityColors = {
    Low: 'bg-gray-100 text-gray-800',
    Medium: 'bg-blue-100 text-blue-800',
    High: 'bg-orange-100 text-orange-800',
    Urgent: 'bg-red-100 text-red-800',
};
</script>

<template>
	<Head :title="card.title" />

	<div class="max-w-5xl mx-auto p-6 space-y-6">
		<div class="flex items-center justify-between">
			<Button variant="ghost" @click="goBack">
				<ArrowLeft class="w-4 h-4 mr-2" /> Back to Board
			</Button>
			<div class="flex gap-2">
				<Button v-if="permissions.canEditCards && !isEditing" @click="isEditing = true">Edit</Button>
				<Button v-if="isEditing" variant="outline" @click="isEditing = false">Cancel</Button>
				<Button v-if="isEditing" @click="saveCard">Save Changes</Button>
				<Button v-if="permissions.canEditCards" variant="destructive" @click="deleteCard">Delete Card</Button>
			</div>
		</div>

		<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
			<div class="md:col-span-2 space-y-6">
				<Card>
					<CardHeader>
						<CardTitle>
							<input v-if="isEditing" v-model="cardForm.title" class="w-full text-lg font-bold p-2 border rounded" />
							<span v-else>{{ card.title }}</span>
						</CardTitle>
					</CardHeader>
					<CardContent>
						<textarea v-if="isEditing" v-model="cardForm.description" class="w-full p-2 border rounded min-h-[100px]"></textarea>
						<p v-else class="whitespace-pre-wrap text-gray-700 dark:text-gray-300">{{ card.description }}</p>
					</CardContent>
				</Card>

				<Card>
					<CardHeader>
						<CardTitle>Subtasks</CardTitle>
					</CardHeader>
					<CardContent>
						<SubtaskList
							:cardId="card.id"
							:subtasks="card.subtasks || []"
							:canCreate="permissions.canManageSubtasks"
							:canEdit="canEditSubtask"
							:teamSlug="team.slug"
							@refresh="refreshCardSubtasks"
						/>
					</CardContent>
				</Card>

				<Card>
					<CardHeader>
						<CardTitle>Attachments</CardTitle>
					</CardHeader>
					<CardContent>
						<AttachmentUpload :cardId="card.id" :teamSlug="team.slug" @refresh="refreshCardAttachments" />
						<AttachmentList :attachments="card.attachments || []" :cardId="card.id" :teamSlug="team.slug" @refresh="refreshCardAttachments" />
					</CardContent>
				</Card>
			</div>

			<div class="space-y-6">
				<Card>
					<CardHeader>
						<CardTitle>Details</CardTitle>
					</CardHeader>
					<CardContent class="space-y-4">
						<div>
							<label class="block text-sm font-medium text-gray-700">Assignee</label>
							<select v-if="isEditing" v-model="cardForm.assigned_to" class="w-full p-2 border rounded">
								<option value="">Unassigned</option>
								<option v-for="member in (page.props.teamMembers as any[])" :key="member.id" :value="member.id">{{ member.name }}</option>
							</select>
							<span v-else class="text-sm">{{ card.assignee?.name || 'Unassigned' }}</span>
						</div>

						<div>
							<label class="block text-sm font-medium text-gray-700">Priority</label>
							<select v-if="isEditing" v-model="cardForm.priority" class="w-full p-2 border rounded">
								<option value="Low">Low</option>
								<option value="Medium">Medium</option>
								<option value="High">High</option>
								<option value="Urgent">Urgent</option>
							</select>
							<Badge v-else :class="priorityColors[card.priority]">{{ card.priority }}</Badge>
						</div>

						<div>
							<label class="block text-sm font-medium text-gray-700">Dates</label>
							<div v-if="isEditing" class="grid grid-cols-2 gap-2">
								<input v-model="cardForm.start_date" type="date" class="w-full p-2 border rounded" />
								<input v-model="cardForm.due_date" type="date" class="w-full p-2 border rounded" />
							</div>
							<div v-else class="text-sm">
								<p>Start: {{ card.start_date || 'N/A' }}</p>
								<p>Due: {{ card.due_date || 'N/A' }}</p>
							</div>
						</div>
					</CardContent>
				</Card>
			</div>
		</div>
	</div>
</template>
