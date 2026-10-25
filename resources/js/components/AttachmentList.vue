<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Card, CardHeader, CardTitle, CardContent, CardFooter } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuTrigger, DropdownMenuContent, DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { MoreHorizontal } from 'lucide-vue-next';
import { defineEmits, defineProps } from 'vue';

const props = defineProps<{
    attachments: Array<{
        id: number | string;
        filename: string;
        size: number; // bytes
    }>;
    cardId: number | string;
    teamSlug: string;
}>();

const emit = defineEmits(['refresh']);

const download = (attachmentId: number | string) => {
    router.visit(route('attachments.download', { team: props.teamSlug, card: props.cardId, attachment: attachmentId }));
};

const del = (attachmentId: number | string) => {
    router.delete(route('attachments.destroy', { team: props.teamSlug, card: props.cardId, attachment: attachmentId }), {
        preserveScroll: true,
        onSuccess: () => emit('refresh'),
    });
};

const formatSize = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<template>
    <Card class="w-full">
        <CardHeader>
            <CardTitle>Attachments</CardTitle>
        </CardHeader>
        <CardContent>
            <ul class="space-y-2">
                <li v-for="item in props.attachments" :key="item.id" class="flex items-center justify-between p-2 border rounded">
                    <div class="flex items-center space-x-2">
                        <span class="font-medium">{{ item.filename }}</span>
                        <span class="text-sm text-muted-foreground">({{ formatSize(item.size) }})</span>
                    </div>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="ghost" size="sm">
                                <MoreHorizontal class="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @click="download(item.id)">Download</DropdownMenuItem>
                            <DropdownMenuItem @click="del(item.id)" class="text-destructive">Delete</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </li>
            </ul>
        </CardContent>
        <CardFooter></CardFooter>
    </Card>
</template>
