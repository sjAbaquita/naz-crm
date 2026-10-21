<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3'
import { Plus, Trash2, X, User } from 'lucide-vue-next';
import Sortable from 'sortablejs'
import { ref, onMounted, nextTick, watch } from 'vue'

import cardsRoutes from '@/routes/cards';
import columnRoutes from '@/routes/column';
import type { Team } from '@/types';
import type { Board, BoardCard, BoardPermissions, UserMinimal } from '@/types/boards';

type Props = {
    board: Board;
    currentTeam: Team;
    permissions: BoardPermissions;
    teamMembers: UserMinimal[];
};

const props = defineProps<Props>()

// Modal State
const showCardModal = ref(false)
const editingCard = ref<BoardCard | null>(null)
const isAddingCard = ref(false)

const cardForm = useForm({
    title: '',
    description: '',
    column_id: null as number | null,
    assigned_to: null as number | null,
})

const columnForm = useForm({
    title: '',
})

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
    showCardModal.value = true
}

const openAddCardModal = (columnId: number) => {
    editingCard.value = null
    isAddingCard.value = true
    cardForm.reset()
    cardForm.column_id = columnId
    cardForm.assigned_to = null
    showCardModal.value = true
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
