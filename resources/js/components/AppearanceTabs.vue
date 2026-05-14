<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { useAppearance } from '@/composables/useAppearance';
import { useSidebar } from "./ui/sidebar/utils"

const { appearance, updateAppearance } = useAppearance();

const isOpen = ref(false);

const { state } = useSidebar()

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;

const activeTab = () => tabs.find(t => t.value === appearance.value);

const handleClickOutside = (e: MouseEvent) => {
  if (!(e.target as HTMLElement).closest('.relative')) {
    isOpen.value = false
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside))
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside))

</script>

<!-- <template>
    <div
        class="inline-flex justify-center gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800"
    >
        <button
            v-for="{ value, Icon, label } in tabs"
            :key="value"
            @click="updateAppearance(value)"
            :class="[
                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                appearance === value
                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
            ]"
        >
            <component :is="Icon" class="-ml-1 h-4 w-4" />
            <span class="ml-1.5 text-sm hidden">{{ label }}</span>
        </button>
    </div>
</template> -->

<template>
	<div class="relative">
		<!-- COLLAPSED MODE -->
		<div v-if="state === 'collapsed'">
			<button
				@click="isOpen = !isOpen"
				class="flex items-center justify-center rounded-md p-2 bg-neutral-100 dark:bg-neutral-800 hover:bg-neutral-200 dark:hover:bg-neutral-700 h-8 w-8"
			>
				<component :is="activeTab()?.Icon" class="h-5 w-5" />
			</button>

			<!-- Floating menu -->
			<div
				v-if="isOpen"
				class="absolute left-full ml-2 bottom-0 z-50 rounded-lg bg-white shadow-lg p-1 dark:bg-neutral-800 border dark:border-neutral-700"
			>
				<button
					v-for="{ value, Icon, label } in tabs"
					:key="value"
					@click="() => { updateAppearance(value); isOpen = false }"
					class="flex items-center gap-2 w-full px-3 py-2 rounded-md text-sm hover:bg-neutral-100 dark:hover:bg-neutral-700"
				>
					<component :is="Icon" class="h-4 w-4" />
					<span>{{ label }}</span>
				</button>
			</div>
		</div>

		<!-- EXPANDED MODE -->
		<div
			v-else
			class="inline-flex justify-center gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800 w-full"
		>
			<button
				v-for="{ value, Icon } in tabs"
				:key="value"
				@click="updateAppearance(value)"
				:class="[
					'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
					appearance === value
						? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
						: 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
					]"
			>
				<component :is="Icon" class="-ml-1 h-4 w-4" />
				<!-- <span class="ml-1.5 text-sm">{{ label }}</span> -->
			</button>
		</div>

	</div>
</template>
