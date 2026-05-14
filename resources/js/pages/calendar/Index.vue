<script setup lang="ts">
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import FullCalendar from '@fullcalendar/vue3';
import { Head, router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref, onMounted, onBeforeUnmount, nextTick, watch, computed } from 'vue';
import EventLevelRadioGroup from '@/components/EventLevelRadioGroup.vue';
import { Textarea } from '@/components/ui/textarea';
import {destroy, index, store, update} from '@/routes/calendar';
import type { Team } from '@/types';
import type { CalendarEvent } from '@/types/calendar-events';
import type { EventLevel } from '@/types/event-level';

const calendarRef = ref<any>(null)
let observer: ResizeObserver

const props = defineProps<{
    events: CalendarEvent[];
	currentTeam: {
		id: number | string
		slug?: string
	}
}>()

// Modal State
const showModal = ref(false)
const isEdit = ref(false)
const selectedEventId = ref<number | null>(null)
const isSubmitting = ref(false)


// Form State
const form = ref({
	title: '',
	start_date: '',
	end_date: '',
	event_level: 'success' as EventLevel | 'success',
	description: '',
	location: '',
})

// Events
const events = ref<CalendarEvent[]>([...props.events])

// Open Modal
const openModal = () => (showModal.value = true)
const closeModal = () => {
	showModal.value = false
	resetForm()
}

// Reset
const resetForm = () => {
	form.value = { title: '', description: '', location: '', start_date: '', end_date: '', event_level: 'success' }
	isEdit.value = false
	selectedEventId.value = null
}

// Add Event
const addEvent = async () => {
	if (isSubmitting.value) {
		return
	}

	isSubmitting.value = true

	await router.post(store({
			current_team: props.currentTeam.slug!,
		}).url,
		form.value,
		{
			onSuccess: () => {
				closeModal()
			},
			onError: (errors) => {
				console.log(errors)
			},
			onFinish: () => {
				isSubmitting.value = false
			},
		}
	)

	// await router.post('calendar', {
	// 	title: form.value.title,
	// 	start_date: form.value.start,
	// 	end_date: form.value.end,
	// 	description: form.value.description,
	// 	location: form.value.location,
	// 	event_level: form.value.level,
	// }, {
	// 	onSuccess: () => {
	// 		console.log('Event created!')
	// 		closeModal()
	// 	},

	// 	onError: (errors) => {
	// 		console.log(errors)
	// 	},
	// })
	// events.value.push(response.data)
}

// Update Event
const updateEvent = async () => {
	if (!selectedEventId.value) {
		return
	}

	if (isSubmitting.value) {
		return
	}

	isSubmitting.value = true

	await router.put(
		update({
			current_team: props.currentTeam.slug!,
			calendar: selectedEventId.value,
		}).url,
		form.value,
		{
			onSuccess: () => {
				closeModal()
			},
			onError: (errors) => {
				console.log(errors)
			},
			onFinish: () => {
				isSubmitting.value = false
			},
		}
	)
	// await router.put(`calendar/${selectedEventId.value}`, {
	// 	title: form.value.title,
	// 	start_date: form.value.start,
	// 	end_date: form.value.end,
	// 	description: form.value.description,
	// 	location: form.value.location,
	// 	event_level: form.value.level,
	// }, {
	// 	onSuccess: () => {
	// 		console.log('Event updated!')
	// 		closeModal()
	// 	},

	// 	onError: (errors) => {
	// 		console.log(errors)
	// 	},
	// })
}

// Delete Event
const deleteEvent = async () => {
	if (!selectedEventId.value) {
		return
	}

	if (isSubmitting.value) {
		return
	}

	isSubmitting.value = true
	await router.delete(destroy({
			current_team: props.currentTeam.slug!,
			calendar: selectedEventId.value,
		}).url,
		{
			onSuccess: () => {
				closeModal()
			},
			onError: (errors) => {
				console.log(errors)
			},
			onFinish: () => {
				isSubmitting.value = false
			},
		}
	)
}

const updateCalendarSize = () => {
  calendarRef.value?.getApi().updateSize()
}


const calendarEvents = computed(() => {
	const isAllDayEvent = (
		start: string,
		end?: string | null
	): boolean => {
		return !start.includes('T') && !(end && end.includes('T'))
	};

	return events.value.map(event => ({
		id: String(event.id),
		title: event.title,
		start: event.start_date,
		end: event.end_date,
		allDay: isAllDayEvent(
			event.start_date,
			event.end_date
		),
		extendedProps: {
			calendar: event.event_level,
			description: event.description,
			location: event.location,
		},
	}))
})
// console.log(calendarEvents)

const toDateOnly = (date?: string) => {
	if (!date) {
		return '';
	}

	const d = new Date(date);

	return d.toISOString().split('T')[0];
}

const mapEventToForm = (id: number) => {
	const selectedEvent = events.value.find(event => event.id === Number(id));
	
	form.value.title = selectedEvent?.title ?? '';
	form.value.start_date = toDateOnly(selectedEvent?.start_date) ?? '';
	form.value.end_date = toDateOnly(selectedEvent?.end_date) ?? '';
	form.value.description = selectedEvent?.description ?? '';
	form.value.location = selectedEvent?.location ?? '';
	form.value.event_level = selectedEvent?.event_level as EventLevel ?? '';
}

// Calendar Config
const calendarOptions = computed(() => ({
	plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
	initialView: 'dayGridMonth',
	selectable: true,

	height: "100%",
  	expandRows: true,

	events: calendarEvents.value,

	select: (info: any) => {
		resetForm()
		form.value.start_date = info.startStr
		form.value.end_date = info.endStr || info.startStr
		openModal()
	},

	eventClick: (info: any) => {
		const event = info.event

		isEdit.value = true
		selectedEventId.value = event.id

		mapEventToForm(event.id);

		openModal()
	},

	headerToolbar: {
		left: 'prev,next',
		center: 'title',
		right: 'dayGridMonth,timeGridWeek,timeGridDay',
	},

	eventContent: (arg: any) => {
		const level = arg.event.extendedProps.calendar

		const colorMap: Record<string, string> = {
			danger: 'bg-red-50',
			success: 'bg-green-50',
			primary: 'bg-blue-50',
			warning: 'bg-yellow-50',
		}
		const styles: Record<string, string> = {
			danger: 'background-color: var(--color-red-500);',
			success: 'background-color: var(--color-green-500);',
			primary: 'background-color: var(--color-blue-500);',
			warning: 'background-color: var(--color-yellow-500);',
		}

		const bgColor = colorMap[level] || 'bg-gray-400'
		
		const bgStyles = styles[level] || 'bg-gray-400'

		return {
			html: `
				<div class="event-fc-color flex fc-event-main ${bgColor} sm:p-1 p-0 rounded-sm sm:h-auto h-[10px]">
					<div class="fc-daygrid-event-dot rounded-full ${bgColor}" style="height: auto; width: 5px; border-width: 0; margin: 0px;${bgStyles}"></div>
					<div class="fc-event-time" style="color: var(--color-gray-800);"></div>
					<div class="fc-event-title text-[0.45rem] sm:text-sm" style="color: var(--color-gray-800);">${arg.event.title}</div>
				</div>
			`,
		}
	},
}))

const openForm = () => {
	resetForm()
	form.value.start_date = new Date().toISOString().split('T')[0]
	openModal()
}

onMounted(() => {
	observer = new ResizeObserver(() => {
		updateCalendarSize()
	})

	if (calendarRef.value?.$el) {
		observer.observe(calendarRef.value.$el)
	}
})

onBeforeUnmount(() => {
  	observer?.disconnect()
})

watch(showModal, (val) => {
	if (!val) {
		nextTick(() => {
			updateCalendarSize()
		})
	}
})

defineOptions({
    layout: (props: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Calendar',
                href: props.currentTeam
                    ? index(props.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});

watch(
    () => props.events,
    (newEvents) => {
        events.value = [...newEvents]
    },
    { deep: true }
)

</script>

<template>
    <Head title="Calendar" />

	<div class="flex h-screen flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
		<div class="h-full flex flex-col">
			<div class="flex-1 min-h-0 overflow-hidden custom-calendar">
				<FullCalendar ref="calendarRef" :options="calendarOptions" />
			</div>
		</div>

		<!-- Floating Button -->
		<div class="fixed bottom-4 right-10 z-50 group">
			<button
				@click="openForm"
				class="w-14 h-14 flex items-center justify-center rounded-full bg-slate-500 text-white shadow-lg hover:bg-slate-800 transition"
			>
				<Plus class="w-6 h-6" />
			</button>

			<div
				class="absolute right-full mr-3 top-1/2 -translate-y-1/2 hidden group-hover:block w-16 text-xs bg-black text-white  py-1 rounded"
			>
				Add Event
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
							{{ !isEdit ? 'Add Event' : 'Update Event' }}
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
							<input v-model="form.title" id="event-title" type="text" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" placeholder="Enter event title" />
						</div>

						<!-- Event Description -->
						<div class="mt-6">
							<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
								Event Description
							</label>
							<Textarea v-model="form.description" placeholder="Event Description" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"/>
						</div>

						<!-- Event Color -->
						<div class="mt-6">
							<label class="block mb-4 text-sm font-medium text-gray-700 dark:text-gray-400">
								Event Color
							</label>
							<div class="flex flex-wrap items-center gap-4 sm:gap-5">

								<EventLevelRadioGroup v-model="form.event_level" />

							</div>
						</div>

						<!-- Start Date -->
						<div class="mt-6">
							<label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
								Enter Start Date
							</label>
							<div class="relative">
								<input v-model="form.start_date" id="event-start-date" type="date" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 cursor-pointer" onclick="this.showPicker()" />
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
								<input v-model="form.end_date" id="event-end-date" type="date" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 cursor-pointer" onclick="this.showPicker()" />
								<span class="absolute top-1/2 right-3.5 -translate-y-1/2 pointer-events-none">
									<svg class="fill-gray-700 dark:fill-gray-400" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path fill-rule="evenodd" clip-rule="evenodd" d="M4.33317 0.0830078C4.74738 0.0830078 5.08317 0.418794 5.08317 0.833008V1.24967H8.9165V0.833008C8.9165 0.418794 9.25229 0.0830078 9.6665 0.0830078C10.0807 0.0830078 10.4165 0.418794 10.4165 0.833008V1.24967L11.3332 1.24967C12.2997 1.24967 13.0832 2.03318 13.0832 2.99967V4.99967V11.6663C13.0832 12.6328 12.2997 13.4163 11.3332 13.4163H2.6665C1.70001 13.4163 0.916504 12.6328 0.916504 11.6663V4.99967V2.99967C0.916504 2.03318 1.70001 1.24967 2.6665 1.24967L3.58317 1.24967V0.833008C3.58317 0.418794 3.91896 0.0830078 4.33317 0.0830078ZM4.33317 2.74967H2.6665C2.52843 2.74967 2.4165 2.8616 2.4165 2.99967V4.24967H11.5832V2.99967C11.5832 2.8616 11.4712 2.74967 11.3332 2.74967H9.6665H4.33317ZM11.5832 5.74967H2.4165V11.6663C2.4165 11.8044 2.52843 11.9163 2.6665 11.9163H11.3332C11.4712 11.9163 11.5832 11.8044 11.5832 11.6663V5.74967Z" fill="" />
									</svg>
								</span>
							</div>
						</div>

					</div>

					<!-- Modal Footer -->
					<div class="flex items-center gap-3 mt-6 modal-footer" :class="isEdit ? 'sm:justify-between' : 'sm:justify-end'">
						<div v-if="isEdit">
							<button @click="deleteEvent()"
									:disabled="isSubmitting"
									type="button"
									class="modal-close-btn flex w-full justify-center rounded-lg border border-gray-300 bg-red-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-400 sm:w-auto disabled:opacity-50 dark:border-gray-700 dark:bg-red-500 dark:text-white dark:hover:bg-red-400 cursor-pointer">
								Delete
							</button>
						</div>
						<div class="flex items-center gap-3">
							<button @click="closeModal()"
									type="button"
									class="modal-close-btn flex w-full justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 sm:w-auto dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/3 cursor-pointer">
								Close
							</button>
							<button @click="updateEvent()"
									v-if="isEdit"
									:disabled="isSubmitting"
									type="button"
									class="btn btn-add-event bg-brand-500 hover:bg-brand-600 flex w-full justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-white sm:w-auto disabled:opacity-50 bg-gray-50 hover:border-gray-300 hover:bg-slate-200 dark:bg-slate-500 dark:hover:bg-white/3 cursor-pointer">
								Update Changes
							</button>
							<button @click="addEvent()"
									v-if="!isEdit"
									:disabled="isSubmitting"
									type="button"
									class="btn btn-add-event bg-brand-500 hover:bg-brand-600 flex w-full justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-white sm:w-auto disabled:opacity-50 bg-gray-50 hover:border-gray-300 hover:bg-slate-200 dark:bg-slate-500 dark:hover:bg-white/3 cursor-pointer">
								Add Event
							</button>
						</div>
					</div>

				</div>
			</div>
		</div>
	</div>


</template>