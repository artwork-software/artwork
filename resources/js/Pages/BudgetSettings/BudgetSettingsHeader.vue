<template>
    <app-layout :title="$t('Budget Settings') + ' - ' + title">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconCurrencyDollar"
                :title="title || $t('Budget Settings')"
                icon-bg-class="bg-green-600/10 text-green-700"
                :description="description || $t('Define settings for your budget.')"
                :search-enabled="false"
            >
                <template #actions>
                    <slot name="actions"></slot>
                </template>
            </ToolbarHeader>

            <!-- Tabs outside the header card -->
            <BaseTabs :tabs="tabs" navigation-mode="links" />

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
import Permissions from "@/Mixins/Permissions.vue";

export default defineComponent({
    props: ['title', 'description'],
    mixins: [Permissions],
    components: {
        AppLayout,
        ToolbarHeader,
        BaseTabs
    },
    data() {
        return {
            IconCurrencyDollar,
            tabs: [
                {
                    name: this.$t('General'),
                    href: route('budget-settings.general'),
                    current: route().current('budget-settings.general'),
                    permission: this.$canAny([
                        'can manage global project budgets',
                        'can manage all project budgets without docs'
                    ])
                },
                {
                    name: this.$t('Account management'),
                    href: route('budget-settings.account-management'),
                    current: route().current('budget-settings.account-management'),
                    permission: this.$canAny([
                        'can manage global project budgets',
                        'can manage all project budgets without docs'
                    ])
                },
                {
                    name: this.$t('Budget templates'),
                    href: route('budget-settings.templates'),
                    current: route().current('budget-settings.templates'),
                    permission: this.$can('view budget templates')
                }
            ]
        }
    }
});
</script>

<style scoped>

</style>
