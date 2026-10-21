<script setup lang="ts">
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import FullCalendar from '@fullcalendar/vue3';
import { Head, router } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { ref, onMounted, onBeforeUnmount, nextTick, watch, computed } from 'vue';
import EventLevelRadioGroup from '@/components/EventLevelRadioGroup.vue';
import { Textarea } from '@/components/ui/textarea';
import { destroy, index, store, update, reschedule as rescheduleRoute } from '@/routes/calendar';
import type { Team } from '@/types';
import type { CalendarEvent } from '@/types/calendar-events';
import type { EventLevel } from '@/types/event-level';

const calendarRef = ref<any>(null)
let observer: ResizeObserver

const props = defineProps<{
    events: CalendarEvent[];
    currentTeam: Team;
    permissions: {
        canEditEvents: boolean;
    };
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
            current_team: props.currentTeam.slug,
        }).url,
        form.value,
        {
            onSuccess: () => closeModal(),
            onFinish: () => {
                isSubmitting.value = false
            },
        }
    )
}

// Update Event
const updateEvent = async () => {
    if (!selectedEventId.value || isSubmitting.value) {
return
}

    isSubmitting.value = true

    await router.put(
        update({
            current_team: props.currentTeam.slug,
            calendar: selectedEventId.value,
        }).url,
        form.value,
        {
            onSuccess: () => closeModal(),
            onFinish: () => {
                isSubmitting.value = false
            },
        }
    )
}

// Reschedule Event
const reschedule = (info: any) => {
    const event = info.event;

    router.patch(rescheduleRoute({
        current_team: props.currentTeam.slug,
        calendar: Number(event.id)
    }).url, {
        start_date: toDateOnly(event.startStr),
        end_date: event.endStr ? toDateOnly(event.endStr) : null,
    }, {
        preserveScroll: true,
        onError: () => info.revert(),
    });
}

// Delete Event
const deleteEvent = async () => {
    if (!selectedEventId.value || isSubmitting.value) {
return
}

    if (!confirm('Are you sure you want to delete this event?')) {
return
}

    isSubmitting.value = true
    await router.delete(destroy({
            current_team: props.currentTeam.slug,
            calendar: selectedEventId.value,
        }).url,
        {
            onSuccess: () => closeModal(),
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

const toDateOnly = (date?: string | null) => {
    if (!date) {
return '';
}

    const d = new Date(date);

    return d.toISOString().split('T')[0];
}

const mapEventToForm = (id: number | string) => {
    const selectedEvent = events.value.find(event => event.id === Number(id));

    if (!selectedEvent) {
return;
}

    form.value.title = selectedEvent.title ?? '';
    form.value.start_date = toDateOnly(selectedEvent.start_date) ?? '';
    form.value.end_date = toDateOnly(selectedEvent.end_date) ?? '';
    form.value.description = selectedEvent.description ?? '';
    form.value.location = selectedEvent.location ?? '';
    form.value.event_level = selectedEvent.event_level as EventLevel ?? 'success';
}

// Calendar Config
const calendarOptions = computed(() => ({
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    selectable: true,
    editable: true,

    height: "100%",
    expandRows: true,

    events: calendarEvents.value,

    select: (info: any) => {
        resetForm()
        // Use info.start directly to avoid timezone issues with startStr/endStr
        const startDate = new Date(info.start);
        const endDate = new Date(info.end);

        // For all-day events, end is exclusive (start of next day), so subtract 1 day
        if (info.allDay) {
            endDate.setDate(endDate.getDate() - 1);
        }

        form.value.start_date = toDateOnly(startDate.toISOString());
        form.value.end_date = toDateOnly(endDate.toISOString());
        openModal()
    },

    eventClick: (info: any) => {
        const event = info.event
        isEdit.value = true
        selectedEventId.value = Number(event.id)
        mapEventToForm(event.id);
        openModal()
    },

    eventDrop: reschedule,
    eventResize: reschedule,

    headerToolbar: {
        left: 'prev,next today',
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
                <div class="event-fc-color flex fc-event-main ${bgColor} sm:p-1 p-0 rounded-sm sm:h-auto h-[10px] w-full overflow-hidden">
                    <div class="fc-daygrid-event-dot rounded-full ${bgColor}" style="height: auto; width: 5px; border-width: 0; margin: 0px; flex-shrink: 0; ${bgStyles}"></div>
                    <div class="fc-event-title text-[0.45rem] sm:text-xs font-medium ml-1 truncate" style="color: var(--color-gray-800);">${arg.event.title}</div>
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

    <div class="flex h-screen flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 bg-white dark:bg-gray-900">
        <div class="h-full flex flex-col">
            <div class="flex-1 min-h-0 overflow-hidden custom-calendar">
                <FullCalendar ref="calendarRef" :options="calendarOptions" />
            </div>
        </div>

        <!-- Floating Button -->
        <div class="fixed bottom-10 right-10 z-50 group">
            <button
                @click="openForm"
                class="w-14 h-14 flex items-center justify-center rounded-full bg-blue-600 text-white shadow-lg hover:bg-blue-700 transition-transform hover:scale-110"
            >
                <Plus class="w-6 h-6" />
            </button>
        </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4">
            <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm" @click="closeModal"></div>

            <div class="relative w-full max-w-xl bg-white dark:bg-gray-900 rounded-2xl shadow-xl overflow-hidden flex flex-col max-h-[90vh]">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800 shrink-0">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ !isEdit ? 'Create Event' : (props.permissions.canEditEvents ? 'Edit Event' : 'Event Details') }}
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ !isEdit ? 'Schedule a new team activity' : 'View or update event information' }}
                        </p>
                    </div>
                    <button @click="closeModal" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Body -->
                <div class="p-6 space-y-6 overflow-y-auto">
                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Event Title</label>
                        <input
                            v-model="form.title"
                            :readonly="isEdit && !props.permissions.canEditEvents"
                            type="text"
                            placeholder="e.g. Weekly Sync"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-shadow disabled:opacity-50"
                        />
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Description</label>
                        <Textarea
                            v-model="form.description"
                            :readonly="isEdit && !props.permissions.canEditEvents"
                            placeholder="Add details about this event..."
                            class="w-full min-h-[100px] bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700"
                        />
                    </div>

                    <!-- Location -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Location</label>
                        <input
                            v-model="form.location"
                            :readonly="isEdit && !props.permissions.canEditEvents"
                            type="text"
                            placeholder="e.g. Conference Room or Meeting Link"
                            class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-shadow"
                        />
                    </div>

                    <!-- Color/Level -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Event Color</label>
                        <EventLevelRadioGroup
                            v-model="form.event_level"
                            :disabled="isEdit && !props.permissions.canEditEvents"
                        />
                    </div>

                    <!-- Dates -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Start Date</label>
                            <input
                                v-model="form.start_date"
                                :readonly="isEdit && !props.permissions.canEditEvents"
                                type="date"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-shadow"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">End Date</label>
                            <input
                                v-model="form.end_date"
                                :readonly="isEdit && !props.permissions.canEditEvents"
                                type="date"
                                class="w-full px-4 py-2.5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition-shadow"
                            />
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between shrink-0">
                    <div>
                        <button
                            v-if="isEdit && props.permissions.canEditEvents"
                            @click="deleteEvent"
                            :disabled="isSubmitting"
                            class="text-sm font-semibold text-red-600 hover:text-red-700 transition-colors disabled:opacity-50"
                        >
                            Delete Event
                        </button>
                    </div>
                    <div class="flex gap-3">
                        <button
                            @click="closeModal"
                            class="px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            v-if="!isEdit || props.permissions.canEditEvents"
                            @click="isEdit ? updateEvent() : addEvent()"
                            :disabled="isSubmitting || !form.title.trim() || !form.start_date"
                            class="px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ isEdit ? 'Save Changes' : 'Create Event' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
