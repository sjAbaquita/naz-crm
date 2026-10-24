<script setup lang="ts">
import { Plus, Trash2, Check, Pencil, X } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';

interface Props {
    cardId: number;
    subtasks: {
        id: number;
        title: string;
        is_completed: boolean;
        creator?: { id: number; name: string } | null;
        updater?: { id: number; name: string } | null;
        created_by: number | null;
    }[];
    canCreate: boolean;
    canEdit: (subtask: any) => boolean;
    teamSlug: string;
}

const props = defineProps<Props>();
const page = usePage();
const currentUser = computed(() => (page.props.auth as any).user);

const showAddForm = ref(false);
const newSubtaskForm = useForm({
    title: '',
});

const editingId = ref<number | null>(null);
const editForm = useForm({
    title: '',
});

const addSubtask = () => {
    newSubtaskForm.post(`/${props.teamSlug}/cards/${props.cardId}/subtasks`, {
        preserveScroll: true,
        onSuccess: () => {
            newSubtaskForm.reset();
            showAddForm.value = false;
        },
    });
};

const startEdit = (subtask: any) => {
    editingId.value = subtask.id;
    editForm.title = subtask.title;
};

const saveEdit = (subtask: any) => {
    editForm.put(`/${props.teamSlug}/cards/${props.cardId}/subtasks/${subtask.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
            editForm.reset();
        },
    });
};

const toggleComplete = (subtask: any) => {
    router.put(`/${props.teamSlug}/cards/${props.cardId}/subtasks/${subtask.id}`, {
        is_completed: !subtask.is_completed,
    }, {
        preserveScroll: true,
    });
};

const deleteSubtask = (subtask: any) => {
    if (confirm('Are you sure?')) {
        router.delete(`/${props.teamSlug}/cards/${props.cardId}/subtasks/${subtask.id}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center justify-between">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                Subtasks ({{ subtasks.filter(s => s.is_completed).length }}/{{ subtasks.length }})
            </h4>
            <button
                v-if="canCreate"
                @click="showAddForm = !showAddForm"
                class="text-xs text-blue-600 hover:text-blue-700"
            >
                <Plus class="w-3 h-3 inline" /> Add
            </button>
        </div>

        <div v-if="showAddForm" class="flex gap-2">
            <input
                v-model="newSubtaskForm.title"
                @keyup.enter="addSubtask"
                placeholder="Subtask title..."
                class="flex-1 px-2 py-1 text-sm bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded"
            />
            <button
                @click="addSubtask"
                :disabled="newSubtaskForm.processing || !newSubtaskForm.title.trim()"
                class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
            >
                Add
            </button>
        </div>

        <ul class="space-y-1">
            <li
                v-for="subtask in subtasks"
                :key="subtask.id"
                class="flex items-center gap-2 group"
            >
                <button
                    @click="toggleComplete(subtask)"
                    class="flex-shrink-0"
                >
                    <Check
                        v-if="subtask.is_completed"
                        class="w-4 h-4 text-green-600"
                    />
                    <div
                        v-else
                        class="w-4 h-4 border-2 border-gray-300 dark:border-gray-600 rounded"
                    />
                </button>

                <div v-if="editingId === subtask.id" class="flex-1 flex gap-2">
                    <input
                        v-model="editForm.title"
                        @keyup.enter="saveEdit(subtask)"
                        @keyup.esc="editingId = null"
                        class="flex-1 px-2 py-1 text-sm bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded"
                        autofocus
                    />
                    <button
                        @click="saveEdit(subtask)"
                        class="px-2 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                        Save
                    </button>
                    <button
                        @click="editingId = null"
                        class="px-2 py-1 text-sm bg-gray-200 dark:bg-gray-600 rounded hover:bg-gray-300"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <span
                    v-else
                    :class="['flex-1 text-sm', subtask.is_completed ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-300']"
                >
                    {{ subtask.title }}
                </span>

                <div v-if="canEdit(subtask)" class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                        @click="startEdit(subtask)"
                        class="p-1 text-gray-400 hover:text-blue-600"
                    >
                        <Pencil class="w-3 h-3" />
                    </button>
                    <button
                        @click="deleteSubtask(subtask)"
                        class="p-1 text-gray-400 hover:text-red-600"
                    >
                        <Trash2 class="w-3 h-3" />
                    </button>
                </div>
            </li>
        </ul>
    </div>
</template>
