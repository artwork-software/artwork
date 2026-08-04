<template>
    <app-layout :title="$t('Shift Settings') + ' - ' + title">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconSettings"
                :title="title || $t('Shift Settings')"
                icon-bg-class="bg-success text-success"
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
import {can, is} from 'laravel-permission-to-vuejs';
import {usePage} from '@inertiajs/vue3';

export default defineComponent({
    props: ['title', 'description'],
    components: {
        AppLayout,
        ToolbarHeader,
        BaseTabs
    },
    setup() {
        const $t = useTranslation();
        const granularPermissionsEnabled = Boolean(usePage().props.shift_settings_access?.granular_permissions_enabled);
        const canAccessArea = (area) => !granularPermissionsEnabled
            || is('artwork admin')
            || can(`shift.settings.${area}.view`)
            || can(`shift.settings.${area}.edit`);

        const tabs = [
            {
                name: $t('Shift Settings'),
                href: route('shift.settings'),
                current: route().current('shift.settings'),
                permission: canAccessArea('general')
            },
            {
                name: $t('Day Services'),
                href: route('day-service.index'),
                current: route().current('day-service.index'),
                permission: canAccessArea('day_services')
            },
            {
                name: $t('Work Time Pattern'),
                href: route('shift.work-time-pattern'),
                current: route().current('shift.work-time-pattern'),
                permission: canAccessArea('work_time_patterns')
            },
            {
                name: $t('shift groups'),
                href: route('shift-groups.index'),
                current: route().current('shift-groups.index'),
                permission: canAccessArea('shift_groups')
            },
            {
                name: $t('User Contracts'),
                href: route('user-contract-settings.index'),
                current: route().current('user-contract-settings.index'),
                permission: canAccessArea('user_contracts')
            },
            {
                name: $t('shift templates'),
                href: route('single-shift-presets.index'),
                current: route().current('single-shift-presets.index'),
                permission: canAccessArea('shift_templates')
            },

            {
                name: $t('Shift preset groups'),
                href: route('shift-preset-groups.index'),
                current: route().current('shift-preset-groups.index'),
                permission: canAccessArea('shift_templates')
            },
            {
                name: $t('Shift warnings - rules'),
                href: route('shift-rules.index'),
                current: route().current('shift-rules.index'),
                permission: canAccessArea('rules'),
            },
            {
                name: $t('Open violations'),
                href: route('shift-rules.pending'),
                current: route().current('shift-rules.pending'),
                permission: canAccessArea('rules'),
            }
        ];

        return {
            IconSettings,
            tabs
        }
    }
});
</script>
