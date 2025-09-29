<template>
    <app-layout :title="$t('Checklist templates') + (title ? ' - ' + title : '')">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconChecklist"
                :title="title || $t('Checklist templates')"
                icon-bg-class="bg-blue-600/10 text-blue-700"
                :description="description || $t('Create and manage templates for checklists that can be reused across projects.')"
                :search-enabled="false"
            >
                <template #actions>
                    <slot name="actions"></slot>
                </template>
            </ToolbarHeader>

            <div class="mt-6">
                <slot></slot>
            </div>
        </div>
    </app-layout>
</template>

<script>
import {defineComponent} from 'vue';
import AppLayout from "@/Layouts/AppLayout.vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import {IconChecklist} from '@tabler/icons-vue';

export default defineComponent({
    props: ['title', 'description'],
    components: {
        AppLayout,
        ToolbarHeader
    },
    data() {
        return {
            IconChecklist
        }
    }
});
</script>

<style scoped>

</style>
