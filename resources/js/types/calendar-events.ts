import type { EventLevel } from "./event-level";

export type CalendarEvent = {
	id: number,
	title: string,
	description: string,
	location: string,
	event_level: string,
	start_date: string,
	end_date: string,
	extendedProps: {
		calendar: EventLevel,
		description: string,
		location: string,
	},
	creator?: { id: number, name: string };
}