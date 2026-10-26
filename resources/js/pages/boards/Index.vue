<script setup lang="ts">
import { router, useForm, usePage } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import { Plus, Trash2, X, User, ChevronDown, ChevronRight } from 'lucide-vue-next';
import Sortable from 'sortablejs'
import { ref, onMounted, nextTick, watch } from 'vue'
import AttachmentList from '@/components/AttachmentList.vue';
import AttachmentUpload from '@/components/AttachmentUpload.vue';
import SubtaskList from '@/components/SubtaskList.vue';

import cardsRoutes from '@/routes/cards';
import columnRoutes from '@/routes/column';
import type { Team } from '@/types';
import type { Board, BoardCard, BoardPermissions, UserMinimal, Label } from '@/types/boards';
import type { Activity } from '@/types/activity';

type Props = {
    board: Board;
    currentTeam: Team;
    permissions: BoardPermissions;
    teamMembers: UserMinimal[];
    labels: Label[];
};

const props = defineProps<Props>()
const page = usePage();

// Modal State
const showCardModal = ref(false)
const editingCard = ref<BoardCard | null>(null)
const isAddingCard = ref(false)

const cardForm = useForm({
    title: '',
    description: '',
    column_id: null as number | null,
    assigned_to: null as number | null,
    labels: [] as number[],
    start_date: '',
    due_date: '',
    priority: 'Low',
})

const columnForm = useForm({
    title: '',
})

// Activity log for the selected card
const cardActivityLog = ref<{ action: string; description: string; created_at: string; user?: { name: string } | null }[]>([])
const showActivityLog = ref(false)
const newLabelName = ref('')

const loadCardActivity = (card: BoardCard) => {
    // Filter activities related to this card from recent activities (shared via Inertia middleware)
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
}

const initDrag = async () => {
    await nextTick()

    // Column dragging
    const columnsEl = document.getElementById('board-columns')

    if (columnsEl && props.permissions.canManageColumns) {
        new Sortable(columnsEl, {
            animation: 150,
            handle: '.column-handle',
            ghostClass: 'opacity-50',
            onEnd: () => {
                const columnIds = Array.from(columnsEl.children)
                    .map(el => (el as HTMLElement).dataset.id)
                    .filter(id => id !== 'add-column-button')
                    .map(Number)

                router.post(columnRoutes.reorder(props.currentTeam.slug).url, {
                    columns: columnIds
                }, {
                    preserveScroll: true,
                })
            }
        })
    }

    // Card dragging
    props.board.columns.forEach((column) => {
        const el = document.getElementById(`col-${column.id}`)

        if (!el) {
            return
        }

        new Sortable(el, {
            group: 'kanban',
            animation: 150,
            ghostClass: 'opacity-50',
            onEnd: (evt) => {
                const { item, to, newIndex } = evt
                const cardId = Number(item.dataset.id)
                const newColId = Number(to.id.replace('col-', ''))

                router.patch(cardsRoutes.move({
                    current_team: props.currentTeam.slug,
                    id: cardId
                }).url, {
                    column_id: newColId,
                    position: newIndex ?? 0
                }, {
                    preserveScroll: true,
                    onError: () => router.reload(),
                })
            }
        })
    })
}

// Column Actions
const editingColumnId = ref<number | string | null>(null)
const columnInputRef = ref<HTMLInputElement | null>(null)

const startEditingColumn = (id: number | string) => {
    if (!props.permissions.canManageColumns && id !== 'add-column') {
return
}

    editingColumnId.value = id
    nextTick(() => {
        columnInputRef.value?.focus()
    })
}

const addColumn = () => {
    if (!columnForm.title.trim()) {
        editingColumnId.value = null

        return
    }

    columnForm.post(columnRoutes.store(props.currentTeam.slug).url, {
        preserveScroll: true,
        onSuccess: () => {
            columnForm.reset()
            editingColumnId.value = null
        }
    })
}

const deleteColumn = (id: number) => {
    if (!confirm('Are you sure you want to delete this column and all its cards?')) {
return
}

    router.delete(columnRoutes.destroy({
        current_team: props.currentTeam.slug,
        id
    }).url, {
        preserveScroll: true
    })
}

// Card Actions
const openCardModal = (card: BoardCard) => {
    editingCard.value = card
    isAddingCard.value = false
    cardForm.title = card.title
    cardForm.description = card.description || ''
    cardForm.column_id = card.column_id
    cardForm.assigned_to = card.assigned_to || null
    cardForm.labels = card.labels?.map(l => l.id) || []
    cardForm.start_date = card.start_date || ''
    cardForm.due_date = card.due_date || ''
    cardForm.priority = card.priority || 'Low'
    loadCardActivity(card)
    showCardModal.value = true
}

const openAddCardModal = (columnId: number) => {
    editingCard.value = null
    isAddingCard.value = true
    cardForm.reset()
    cardForm.column_id = columnId
    cardForm.assigned_to = null
    cardForm.start_date = ''
    cardForm.due_date = ''
    cardForm.priority = 'Low'
    showCardModal.value = true
}

const createLabel = () => {
    if (!newLabelName.value.trim()) {
        return
    }
    router.post(`/${props.currentTeam.slug}/labels`, {
        name: newLabelName.value,
        color: '#3b82f6',
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newLabelName.value = ''
            router.reload({ only: ['board', 'labels'] })
        }
    })
}

const saveCard = () => {
    if (isAddingCard.value) {
        cardForm.post(cardsRoutes.store(props.currentTeam.slug).url, {
            onSuccess: () => closeModal()
        })
    } else if (editingCard.value) {
        cardForm.put(cardsRoutes.update({
            current_team: props.currentTeam.slug,
            id: editingCard.value.id
        }).url, {
            onSuccess: () => closeModal()
        })
    }
}

const deleteCard = () => {
    if (!editingCard.value || !confirm('Are you sure?')) {
return
}

    router.delete(cardsRoutes.destroy({
        current_team: props.currentTeam.slug,
        id: editingCard.value.id
    }).url, {
        onSuccess: () => closeModal()
    })
}

const closeModal = () => {
    showCardModal.value = false
    editingCard.value = null
    cardForm.reset()
}

const refreshCardSubtasks = () => {
    router.reload({
        only: ['board'],
        onSuccess: (page) => {
            const updatedBoard = page.props.board as Board;

            const updatedCard = updatedBoard.columns
                ?.flatMap(column => column.cards ?? [])
                .find(card => card.id === editingCard.value?.id);

            if (updatedCard && editingCard.value) {
                editingCard.value.subtasks = updatedCard.subtasks ?? [];
            }
        },
    });
};

// Refresh the entire card (including attachments) after an attachment action
const refreshCard = () => {
    router.reload({
        only: ['board'],
        onSuccess: (page) => {
            const updatedBoard = page.props.board as Board;
            const updatedCard = updatedBoard.columns
                ?.flatMap(column => column.cards ?? [])
                .find(card => card.id === editingCard.value?.id);

            if (updatedCard && editingCard.value) {
                // Update all card fields that could have changed, especially attachments
                editingCard.value = {
                    ...editingCard.value,
                    ...updatedCard,
                } as any;
            }
        },
    });
};

onMounted(() => {
    initDrag()
})

watch(() => props.board.columns, () => {
    initDrag()
}, { deep: true })

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Board',
                href: props.currentTeam
                    ? columnRoutes.store(props.currentTeam.slug).url // fallback to index if possible, using store as proxy for slug
                    : '/',
            },
        ],
    }),
});

</script>

<template>
    <div class="flex flex-col p-4 h-[calc(100vh-80px)] overflow-hidden">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Board</h1>
        </div>
        <div id="board-columns" class="flex gap-4 overflow-x-auto overflow-y-hidden flex-1 pb-4">
            <div
                v-for="col in props.board.columns"
                :key="col.id"
                :data-id="col.id"
                class="sm:w-80 w-64 bg-gray-100 dark:bg-gray-800 rounded-lg shadow-sm flex flex-col shrink-0 h-fit max-h-full overflow-hidden"
            >
                <div class="flex items-center justify-between px-3 py-3 shrink-0 column-handle cursor-grab active:cursor-grabbing">
                    <h2 class="font-bold text-gray-700 dark:text-gray-200 truncate">{{ col.title }}</h2>
                    <div v-if="props.permissions.canManageColumns" class="flex items-center gap-1">
                        <button @click="deleteColumn(col.id)" class="p-1 text-gray-400 hover:text-red-500 transition-colors">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- DROP AREA -->
                <div :id="`col-${col.id}`" class="flex flex-col gap-2 p-3 pt-0 flex-1 overflow-y-auto min-h-[50px]">
                    <div
                        v-for="card in col.cards"
                        :key="card.id"
                        :data-id="card.id"
                        @click="openCardModal(card)"
                        class="p-3 bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-md shadow-sm border border-gray-200 dark:border-gray-600 cursor-pointer hover:border-blue-400 dark:hover:border-blue-500 transition-colors"
                    >
                        <div class="font-medium">{{ card.title }}</div>
                        <div v-if="card.description" class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                            {{ card.description }}
                        </div>
                        <div v-if="card.assignee" class="flex items-center gap-1 mt-2 pt-2 border-t border-gray-100 dark:border-gray-600">
                            <User class="w-3 h-3 text-gray-400" />
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ card.assignee.name }}</span>
                        </div>
                        <div v-if="card.labels && card.labels.length > 0" class="flex items-center gap-1 mt-2 pt-2 border-t border-gray-100 dark:border-gray-600">
                            <span v-for="label in card.labels" :key="label.id" class="w-2 h-2 rounded-full inline-block" :style="{ background: label.color }" />
                            <span v-for="label in card.labels" :key="`label-${label.id}`" class="text-xs font-medium text-gray-500 dark:text-gray-400 ml-1">{{ label.name }}</span>
                        </div>

                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-gray-100 dark:border-gray-600">
                            <div v-if="card.subtasks && card.subtasks.length > 0" class="text-[10px] text-gray-500">
                                {{ card.subtasks.filter(s => s.is_completed).length }}/{{ card.subtasks.length }} subtasks
                            </div>
                            <Link
                                :href="`/${props.currentTeam.slug}/cards/${card.id}`"
                                class="text-xs text-blue-600 hover:text-blue-700 font-medium"
                                @click.stop
                            >
                                Details
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="p-2 shrink-0">
                    <button
                        @click="openAddCardModal(col.id)"
                        class="w-full h-10 flex items-center justify-start text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md transition-colors px-2"
                    >
                        <Plus class="w-4 h-4 mr-2" />
                        <span class="text-sm font-medium">Add a card</span>
                    </button>
                </div>
            </div>

            <!-- Add Column Button -->
            <div
                v-if="props.permissions.canManageColumns"
                id="add-column-button"
                class="sm:w-80 w-64 shrink-0"
            >
                <div v-if="editingColumnId === 'add-column'" class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg shadow-sm">
                    <input
                        ref="columnInputRef"
                        v-model="columnForm.title"
                        @blur="addColumn"
                        @keyup.enter="addColumn"
                        placeholder="Column title..."
                        class="w-full px-3 py-2 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none"
                    />
                </div>
                <button
                    v-else
                    @click="startEditingColumn('add-column')"
                    class="w-full h-12 flex items-center justify-center bg-gray-100/50 dark:bg-gray-800/50 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-600 dark:text-gray-400 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-700 transition-colors"
                >
                    <Plus class="w-5 h-5 mr-2" />
                    <span class="font-medium">Add another list</span>
                </button>
            </div>
        </div>

        <!-- Card Modal -->
        <div v-if="showCardModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeModal"></div>

            <div class="relative w-full max-w-lg bg-white dark:bg-gray-900 rounded-2xl shadow-xl overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ isAddingCard ? 'Add Card' : 'Card Details' }}
                    </h3>
                    <button @click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                        <input
                            v-model="cardForm.title"
                            :readonly="!props.permissions.canEditCards && !isAddingCard"
                            type="text"
                            placeholder="Card title"
                            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-shadow"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                        <textarea
                            v-model="cardForm.description"
                            :readonly="!props.permissions.canEditCards && !isAddingCard"
                            rows="4"
                            placeholder="Add a more detailed description..."
                            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-shadow resize-none"
                        ></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Assignee</label>
                        <select
                            v-model="cardForm.assigned_to"
                            :readonly="!props.permissions.canEditCards && !isAddingCard"
                            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-shadow"
                        >
                            <option value="">Unassigned</option>
                            <option
                                v-for="member in props.teamMembers"
                                :key="member.id"
                                :value="member.id"
                            >
                                {{ member.name }} ({{ member.email }})
                            </option>
                        </select>
                    </div>

                    <!-- Start Date Input -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Date</label>
                        <input
                            v-model="cardForm.start_date"
                            type="date"
                            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-shadow"
                        />
                    </div>

                    <!-- Due Date Input -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Due Date</label>
                        <input
                            v-model="cardForm.due_date"
                            type="date"
                            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-shadow"
                        />
                    </div>

                    <!-- Priority Selector -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priority</label>
                        <select
                            v-model="cardForm.priority"
                            class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-shadow"
                        >
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>

					<div v-if="1 !== 1">
						<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Labels</label>
						<select
							v-model="cardForm.labels"
							multiple
							class="w-full px-4 py-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-shadow"
						>
							<option
								v-for="label in ($page.props.labels as Label[])"
								:key="label.id"
								:value="label.id"
							>
								{{ label.name }}
							</option>
						</select>
					<div class="flex items-center mt-2 space-x-2">
						<input
							v-model="newLabelName"
							placeholder="New label name"
							class="flex-1 px-2 py-1 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded"
						/>
						<button
							@click="createLabel"
							class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
						> Add </button>
					</div>
					</div>

                    <!-- Activity Log Accordion -->
                        <div v-if="!isAddingCard && editingCard" class="border-t border-gray-100 dark:border-gray-800 pt-4 mt-4">
                            <SubtaskList
                                :cardId="editingCard.id"
                                :subtasks="editingCard.subtasks || []"
                                :canCreate="true"
                                :canEdit="(subtask) => subtask.created_by === ($page.props.auth as any).user.id"
                                :teamSlug="props.currentTeam.slug"
    							@refresh="refreshCardSubtasks"
                            />
                        </div>
						<div v-if="!isAddingCard && editingCard" class="border-t border-gray-100 dark:border-gray-800 pt-4 mt-4">
							<AttachmentUpload
								:cardId="editingCard.id"
								:teamSlug="props.currentTeam.slug"
								@refresh="refreshCard"
							/>
						</div>
						<div v-if="!isAddingCard && editingCard && editingCard.attachments && editingCard.attachments.length > 0" class="border-t border-gray-100 dark:border-gray-800 pt-4 mt-4">
							<AttachmentList
								:attachments="editingCard.attachments || []"
								:cardId="editingCard.id"
								:teamSlug="props.currentTeam.slug"
								@refresh="refreshCard"
							/>
						</div>

                    <div v-if="!isAddingCard && cardActivityLog.length > 0" class="border-t border-gray-100 dark:border-gray-800 pt-4 mt-4">
                        <button
                            @click="showActivityLog = !showActivityLog"
                            class="flex items-center justify-between w-full text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-xs uppercase tracking-wider text-gray-400">Activity Log</span>
                                <span class="bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-[10px] text-gray-500">{{ cardActivityLog.length }}</span>
                            </div>
                            <ChevronDown v-if="showActivityLog" class="w-4 h-4" />
                            <ChevronRight v-else class="w-4 h-4" />
                        </button>

                        <div v-if="showActivityLog" class="mt-3 space-y-3 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                            <div v-for="(log, idx) in cardActivityLog" :key="idx" class="flex gap-3 text-xs">
                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 mt-1.5 shrink-0"></div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-gray-700 dark:text-gray-300 leading-normal">
                                        <span class="font-bold">{{ log.user?.name || 'Someone' }}</span>
                                        {{ log.description.replace(/card '.*'/, '').trim() }}
                                    </p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">{{ new Date(log.created_at).toLocaleString() }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-between">
                    <div>
                        <button
                            v-if="!isAddingCard && props.permissions.canEditCards"
                            @click="deleteCard"
                            class="text-sm font-medium text-red-600 hover:text-red-700 transition-colors"
                        >
                            Delete Card
                        </button>
                    </div>
                    <div class="flex gap-3">
                        <button
                            @click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            v-if="isAddingCard || props.permissions.canEditCards"
                            @click="saveCard"
                            :disabled="cardForm.processing || !cardForm.title.trim()"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors shadow-sm"
                        >
                            {{ isAddingCard ? 'Create Card' : 'Save Changes' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #d1d5db;
  border-radius: 20px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: #4b5563;
}
</style>

