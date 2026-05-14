<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import type { EventLevel } from '@/types/event-level';

// Props
defineProps<{
  	modelValue: EventLevel | ''
}>()

// Emit (for v-model)
const emit = defineEmits<{
  	(e: 'update:modelValue', value: EventLevel): void
}>()

// Options
const options: EventLevel[] = ['success', 'primary', 'warning', 'danger']

// Handle change
const updateValue = (value: any) => {
	emit('update:modelValue', value as EventLevel)
}
</script>

<template>
	<RadioGroup
		:model-value="modelValue"
		@update:model-value="updateValue"
		class="flex flex-wrap gap-4"
	>
		<div
			v-for="level in options"
			:key="level"
			class="flex items-center space-x-2 cursor-pointer"
			>
			<RadioGroupItem
				:id="`level-${level}`"
				:value="level"
				class="cursor-pointer"
			/>
			<Label 
					:for="`level-${level}`"
					:class="{
								'text-red-500': level === 'danger',
								'text-green-500': level === 'success',
								'text-blue-500': level === 'primary',
								'text-yellow-500': level === 'warning',
							}"
					class=" cursor-pointer"
			>
				{{ level }}
			</Label>
		</div>
	</RadioGroup>
</template>