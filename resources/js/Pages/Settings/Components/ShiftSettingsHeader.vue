<template>
    <app-layout :title="$t('Shift Settings') + ' - ' + title">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconSettings"
                :title="title || $t('Shift Settings')"
                icon-bg-class="bg-green-600/10 text-green-700"
                :description="description || $t('Define global settings for shift scheduling.')"
                :search-enabled="false"
            >
                <template #actions>
                    <slot name="actions"></slot>
                </template>
            </ToolbarHeader>

            <!-- Tabs outside the header card -->
            <BaseTabs :tabs="tabs" />

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
import {IconSettings} from '@tabler/icons-vue';
import {useTranslation} from "@/Composeables/Translation.js";

export default defineComponent({
    props: ['title', 'description'],
    components: {
        AppLayout,
        ToolbarHeader,
        BaseTabs
    },
    setup() {
        const $t = useTranslation();

        const tabs = [
            {
                name: $t('Shift Settings'),
                href: route('shift.settings'),
                current: route().current('shift.settings'),
                permission: true
            },
            {
                name: $t('Day Services'),
                href: route('day-service.index'),
                current: route().current('day-service.index'),
                permission: true
            },
            {
                name: $t('Work Time Pattern'),
                href: route('shift.work-time-pattern'),
                current: route().current('shift.work-time-pattern'),
                permission: true
            },
            {
                name: $t('shift groups'),
                href: route('shift-groups.index'),
                current: route().current('shift-groups.index'),
                permission: true
            },
            {
                name: $t('User Contracts'),
                href: route('user-contract-settings.index'),
                current: route().current('user-contract-settings.index'),
                permission: true
            },
            {
                name: $t('shift templates'),
                href: route('single-shift-presets.index'),
                current: route().current('single-shift-presets.index'),
                permission: true
            },

            {
                name: $t('Shift preset groups'),
                href: route('shift-preset-groups.index'),
                current: route().current('shift-preset-groups.index'),
                permission: true
            },
            {
                name: $t('Shift warnings - rules'),
                href: route('shift-rules.index'),
                current: route().current('shift-rules.index'),
                permission: true,
            }
        ];

        return {
            IconSettings,
            tabs
        }
    }
});
</script>
