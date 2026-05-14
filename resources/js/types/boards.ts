export type Card = { 
	id: number; 
	column_id: number,
	title: string,
	description: string,
	position: number,
}
export type Column = { 
	id: number; 
	title: string; 
	cards: Card[] ;
}
export type Board = {
	id: number;
	name: string;
	columns: Column[];
}