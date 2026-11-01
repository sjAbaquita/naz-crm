<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { Loader2, Paperclip } from 'lucide-vue-next';
import { ref } from 'vue';
import attachmentRouter from '@/routes/attachments';

const props = defineProps<{
    cardId: number | string;
    teamSlug: string;
}>();

const emit = defineEmits(['refresh']);

const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm({
    file: null as File | null,
});

const submit = () => {
    const url = attachmentRouter.store({
        current_team: props.teamSlug,
        card: props.cardId,
    }).url;
    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            if (fileInput.value) {
                fileInput.value.value = '';
            }
            emit('refresh');
        },
    });
};

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files?.length) {
        form.file = target.files[0];
        submit();
    }
};
</script>

<template>
    <div class="space-y-2">
        <div class="flex items-center gap-2">
            <input
                type="file"
                class="hidden"
                ref="fileInput"
                @change="onFileChange"
            />
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="form.processing"
                @click="fileInput?.click()"
            >
                <Loader2 v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                <Paperclip v-else class="mr-2 h-4 w-4" />
                Attach File
            </Button>

            <div v-if="form.progress" class="flex-1 h-1 bg-secondary rounded-full overflow-hidden">
                <div
                    class="h-full bg-primary transition-all duration-300"
                    :style="{ width: `${form.progress.percentage}%` }"
                ></div>
            </div>
        </div>

        <InputError :message="form.errors.file" />
    </div>
</template>
