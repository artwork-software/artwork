<template>
    <app-layout :title="$t('Material Sets') + (title ? ' - ' + title : '')">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconPackage"
                :title="title || $t('Material Sets')"
                icon-bg-class="bg-orange-600/10 text-orange-700"
                :description="description || $t('Manage material sets for inventory planning.')"
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
import {IconPackage} from '@tabler/icons-vue';

export default defineComponent({
    props: ['title', 'description'],
    components: {
        AppLayout,
        ToolbarHeader
    },
    data() {
        return {
            IconPackage
        }
    }
});
</script>

<style scoped>

</style>
