<script setup lang="ts">

import { Plus } from 'lucide-vue-next';

import { Ellipsis } from 'lucide-vue-next';
import Sortable from 'sortablejs'
import { ref, onMounted, nextTick, reactive, watch } from 'vue'

import boards from '@/routes/board';
import type { Team } from '@/types';
import type { Board } from '@/types/boards';

// Modal State
const showModal = ref(false)

type Props = {
    board: Board;
};

const props = defineProps<Props>()

const localBoard = reactive({ ...props.board })

const initDrag = async () => {
	await nextTick()

	props.board.columns.forEach((column) => {
		const el = document.getElementById(`col-${column.id}`)

		if (!el) {
			return
		}

		new Sortable(el, {
			group: 'kanban',
			animation: 150,
			ghostClass: 'opacity-50',

			onAdd: (evt: any) => {
				const cardId = evt.item.dataset.id
				const newColumnId = column.id
				const newIndex = evt.newIndex ?? 0

				// connect to backend
				console.log('MOVE CARD:', {
					cardId,
					newColumnId,
					newIndex,
				})
			},

			onUpdate: (evt: any) => {
				const cardId = evt.item.dataset.id
				const sameColumnId = column.id
				const newIndex = evt.newIndex ?? 0

				console.log('REORDER CARD:', {
					cardId,
					sameColumnId,
					newIndex,
				})
			},
		})
	})
}

// Modal
const openModal = () => (showModal.value = true)
const closeModal = () => {
	showModal.value = false
}


const editingId = ref<number | string | null>(null);
const inputRefs = ref<Record<string | number, HTMLInputElement | null>>({})

// List
// New list
const newListTitle = ref<string | null>(null);
const addList = () => {
	if (!newListTitle.value?.trim()) {
		return
	}

	const newColumn = {
		id: Date.now(), // temporary ID (replace with backend later)
		board_id: localBoard.id,
		title: newListTitle.value,
		position: localBoard.columns.length,
		cards: [],
		created_at: new Date().toISOString(),
		updated_at: new Date().toISOString()
	}

	localBoard.columns.push(newColumn)

	// reset
	newListTitle.value = null;
	stopEditing();
}
// Edit list
const startEditing = async (id: any) => {
  	editingId.value = id;
	console.log(editingId.value)

	await nextTick()

	const input = inputRefs.value[id]
	input?.focus()
	input?.select()
}
const stopEditing = () => {
	const input = inputRefs.value['add-list']
	input?.blur();

  	editingId.value = null;
}

// Card
// Add card
const addCard = (columnId: number) => {
	showModal.value = true;
	
	const column = localBoard.columns.find(c => c.id === columnId)

  	if (!column) {
		return
	}

	const newCard = {
		id: Date.now(),
		column_id: columnId,
		title: 'Test Title',
		description: 'Sample description',
		position: column.cards.length,
		created_at: new Date().toISOString(),
		updated_at: new Date().toISOString()
	}

	column.cards.push(newCard)

	// reset
}

onMounted(() => {
  	initDrag()
})

watch(
	() => props.board,
	(newBoard) => {
		Object.assign(localBoard, newBoard)
	}
)

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Board',
                href: props.currentTeam
                    ? boards.index(props.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});

</script>

<template> 

	<div class="flex flex-col p-4 h-[calc(100vh-80px)] dark:bg-white overflow-hidden"> 
	<div class="flex gap-4 overflow-x-auto dark:bg-white overflow-y-hidden flex-1"> 
		<div v-for="col in props.board.columns" :key="col.id" class="sm:w-[20%] w-56 bg-gray-100 rounded-lg shadow flex flex-col shrink-0 h-fit max-h-[calc(100vh-160px)] overflow-hidden" >
			<div class="flex justify-between px-3 pt-3 shrink-0">
				<h2 v-if="editingId !== col.id" @click="startEditing(col.id)" class="font-bold mb-3 text-gray-700">{{ col.title }}</h2>
				<input
					v-else
					:ref="el => inputRefs[col.id] = el as HTMLInputElement | null"
					v-model="col.title"
					@blur="stopEditing"
					@keyup.enter="stopEditing"
					class="font-bold text-gray-700 border-2 active:border-sky-500 focus:border-sky-500 border-sky-500 mb-3 w-full"
				/>
				<Ellipsis v-if="editingId !== col.id" class="text-gray-800" />
			</div>
			
			<!-- DROP AREA --> 
			<div :id="`col-${col.id}`" class="flex flex-col gap-2 p-3 pt-0 flex-1 overflow-y-auto">
				<div v-for="card in col.cards" :key="card.id" :data-id="card.id" @click="openModal" class="p-2 bg-white text-gray-800 rounded-md shadow cursor-pointer" > 
					{{ card.title }} 
				</div>
			</div>
			<div class="pt-2 text-gray-700 flex flex-col shrink-0 cursor-pointer" > 
				<button
					@click="addCard(col.id)"
					class="h-10 flex items-center justify-start bg-transparent hover:bg-gray-200 transition cursor-pointer p-3"
				>
					<Plus class="w-6 h-6" />
					<span>Add a card</span>
				</button>
			</div>
		</div>

		<div class="sm:w-[20%] w-56 bg-gray-50 text-gray-700 h-fit rounded-none shadow flex flex-col shrink-0" >
			<button
			 	v-if="editingId !== `add-list`"
				@click="startEditing(`add-list`)"
				class="h-10 flex items-center justify-center bg-transparent hover:bg-gray-200 transition cursor-pointer"
			>
				<Plus class="w-6 h-6" />
				<span>Add another list</span>
			</button>
			<input
				v-else
				:ref="el => inputRefs[`add-list`] = el as HTMLInputElement | null"
				v-model="newListTitle"
				@blur="addList"
				@keyup.enter="addList"
				class="font-bold text-gray-700 border-2 active:border-sky-500 focus:border-sky-500 border-sky-500 mb-3 w-full"
			/>
		</div>

	</div>

	<!-- Modal -->
	<div v-if="showModal" class="fixed flex inset-0 items-center justify-center p-5 overflow-y-auto modal z-99999">
		<div class="modal-close-btn fixed inset-0 h-full w-full bg-gray-400/50 backdrop-blur-[32px]"></div>
		<div class="modal-dialog relative flex w-full max-w-175 flex-col overflow-y-auto rounded-3xl bg-white p-6 lg:p-11 dark:bg-gray-900">

			<!-- Close Button -->
			<button @click="closeModal()" class="modal-close-btn transition-color absolute top-5 right-5 z-999 flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 text-gray-400 hover:bg-gray-200 hover:text-gray-600 sm:h-11 sm:w-11 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/[0.07] dark:hover:text-gray-300 cursor-pointer">
				<svg class="fill-current" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M6.04289 16.5418C5.65237 16.9323 5.65237 17.5655 6.04289 17.956C6.43342 18.3465 7.06658 18.3465 7.45711 17.956L11.9987 13.4144L16.5408 17.9565C16.9313 18.347 17.5645 18.347 17.955 17.9565C18.3455 17.566 18.3455 16.9328 17.955 16.5423L13.4129 12.0002L17.955 7.45808C18.3455 7.06756 18.3455 6.43439 17.955 6.04387C17.5645 5.65335 16.9313 5.65335 16.5408 6.04387L11.9987 10.586L7.45711 6.04439C7.06658 5.65386 6.43342 5.65386 6.04289 6.04439C5.65237 6.43491 5.65237 7.06808 6.04289 7.4586L10.5845 12.0002L6.04289 16.5418Z" fill="" />
				</svg>
			</button>

			<div class="flex flex-col px-2 overflow-y-auto modal-content custom-scrollbar">

				<!-- Modal Header -->
				<div class="modal-header">
					<h5 class="mb-2 font-semibold text-gray-800 modal-title text-theme-xl lg:text-2xl dark:text-white/90">
						Add
					</h5>
					<p class="text-sm text-gray-500 dark:text-gray-400">
						Plan your next big moment: schedule or edit an event to stay on track
					</p>
				</div>

				<!-- Modal Body -->
				<div class="mt-8 modal-body">

					<!-- Event Title -->
					<div>
						<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
							Event Title
						</label>
						<input id="event-title" type="text" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="Enter event title" />
					</div>

					<!-- Event Color -->
					<div class="mt-6">
						<label class="block mb-4 text-sm font-medium text-gray-700 dark:text-gray-400">
							Event Color
						</label>
						<div class="flex flex-wrap items-center gap-4 sm:gap-5">

							Priority

						</div>
					</div>

					<!-- Start Date -->
					<div class="mt-6">
						<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
							Enter Start Date
						</label>
						<div class="relative">
							<input id="event-start-date" type="date" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 cursor-pointer" onclick="this.showPicker()" />
							<span class="absolute top-1/2 right-3.5 -translate-y-1/2 pointer-events-none">
								<svg class="fill-gray-700 dark:fill-gray-400" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M4.33317 0.0830078C4.74738 0.0830078 5.08317 0.418794 5.08317 0.833008V1.24967H8.9165V0.833008C8.9165 0.418794 9.25229 0.0830078 9.6665 0.0830078C10.0807 0.0830078 10.4165 0.418794 10.4165 0.833008V1.24967L11.3332 1.24967C12.2997 1.24967 13.0832 2.03318 13.0832 2.99967V4.99967V11.6663C13.0832 12.6328 12.2997 13.4163 11.3332 13.4163H2.6665C1.70001 13.4163 0.916504 12.6328 0.916504 11.6663V4.99967V2.99967C0.916504 2.03318 1.70001 1.24967 2.6665 1.24967L3.58317 1.24967V0.833008C3.58317 0.418794 3.91896 0.0830078 4.33317 0.0830078ZM4.33317 2.74967H2.6665C2.52843 2.74967 2.4165 2.8616 2.4165 2.99967V4.24967H11.5832V2.99967C11.5832 2.8616 11.4712 2.74967 11.3332 2.74967H9.6665H4.33317ZM11.5832 5.74967H2.4165V11.6663C2.4165 11.8044 2.52843 11.9163 2.6665 11.9163H11.3332C11.4712 11.9163 11.5832 11.8044 11.5832 11.6663V5.74967Z" fill="" />
								</svg>
							</span>
						</div>
					</div>

					<!-- End Date -->
					<div class="mt-6">
						<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
							Enter End Date
						</label>
						<div class="relative">
							<input id="event-end-date" type="date" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 cursor-pointer" onclick="this.showPicker()" />
							<span class="absolute top-1/2 right-3.5 -translate-y-1/2 pointer-events-none">
								<svg class="fill-gray-700 dark:fill-gray-400" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
									<path fill-rule="evenodd" clip-rule="evenodd" d="M4.33317 0.0830078C4.74738 0.0830078 5.08317 0.418794 5.08317 0.833008V1.24967H8.9165V0.833008C8.9165 0.418794 9.25229 0.0830078 9.6665 0.0830078C10.0807 0.0830078 10.4165 0.418794 10.4165 0.833008V1.24967L11.3332 1.24967C12.2997 1.24967 13.0832 2.03318 13.0832 2.99967V4.99967V11.6663C13.0832 12.6328 12.2997 13.4163 11.3332 13.4163H2.6665C1.70001 13.4163 0.916504 12.6328 0.916504 11.6663V4.99967V2.99967C0.916504 2.03318 1.70001 1.24967 2.6665 1.24967L3.58317 1.24967V0.833008C3.58317 0.418794 3.91896 0.0830078 4.33317 0.0830078ZM4.33317 2.74967H2.6665C2.52843 2.74967 2.4165 2.8616 2.4165 2.99967V4.24967H11.5832V2.99967C11.5832 2.8616 11.4712 2.74967 11.3332 2.74967H9.6665H4.33317ZM11.5832 5.74967H2.4165V11.6663C2.4165 11.8044 2.52843 11.9163 2.6665 11.9163H11.3332C11.4712 11.9163 11.5832 11.8044 11.5832 11.6663V5.74967Z" fill="" />
								</svg>
							</span>
						</div>
					</div>

				</div>

				<!-- Modal Footer -->
				<div class="flex items-center gap-3 mt-6 modal-footer sm:justify-end">
					<button @click="closeModal()" type="button" class="modal-close-btn flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3 cursor-pointer">
						Close
					</button>
					<!-- <button @click="updateEvent()" v-if="isEdit" type="button" class="btn btn-add-event bg-brand-500 hover:bg-brand-600 flex w-full justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-white sm:w-auto bg-gray-50 hover:border-gray-300 hover:bg-slate-200 dark:bg-slate-500 dark:hover:bg-white/3 cursor-pointer">
						Update Changes
					</button> -->
					<button type="button" class="btn btn-add-event bg-brand-500 hover:bg-brand-600 flex w-full justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-white sm:w-auto bg-gray-50 hover:border-gray-300 hover:bg-slate-200 dark:bg-slate-500 dark:hover:bg-white/3 cursor-pointer">
						Add Event
					</button>
				</div>

			</div>
		</div>
	</div>
	</div>

</template>

<!-- <template>
  <div class="flex gap-4 p-4 overflow-x-auto h-screen bg-gray-100">

	<div class="grid auto-rows-min gap-4"  :class="'md:grid-cols-'+props.board.columns.length">
    <div
      v-for="col in props.board.columns"
      :key="col.id"
      class="grid auto-rows-min gap-4"
    >
      <h2 class="font-bold mb-3">{{ col.title }}</h2>

      <div :id="`col-${col.id}`" class="flex flex-col gap-2 min-h-50">

        <div
          v-for="card in col.cards"
          :key="card.id"
          :data-id="card.id"
          class="p-2 bg-gray-50 rounded shadow cursor-move"
        >
          {{ card.title }}
        </div>

      </div>
    </div>
	</div>

  </div>
</template> -->