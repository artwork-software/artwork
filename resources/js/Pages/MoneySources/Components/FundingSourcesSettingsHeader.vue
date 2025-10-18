<template>
    <app-layout :title="$t('Sources of funding') + (title ? ' - ' + title : '')">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconCurrencyDollar"
                :title="title || $t('Sources of funding')"
                icon-bg-class="bg-emerald-600/10 text-emerald-700"
                :description="description || $t('Define categories. These can then be filtered in the overview.')"
                :search-enabled="false"
            >
                <template #actions>
                    <slot name="actions"></slot>
                </template>
            </ToolbarHeader>

            <!-- Tabs outside the header card (if needed in future) -->
            <!-- <BaseTabs :tabs="tabs" navigation-mode="links" /> -->

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
import BaseTabs from "@/Artwork/Tabs/BaseTabs.vue";
import {IconCurrencyDollar} from '@tabler/icons-vue';

export default defineComponent({
    props: ['title', 'description'],
    components: {
        AppLayout,
        ToolbarHeader,
        BaseTabs
    },
    data() {
        return {
            IconCurrencyDollar,
            // Future tabs can be added here if needed
            // tabs: []
        }
    }
});
</script>

<style scoped>

</style>
