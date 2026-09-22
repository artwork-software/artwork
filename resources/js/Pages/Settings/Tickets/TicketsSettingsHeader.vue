<template>
    <AppLayout :title="$t('artwork tickets')">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconBuildingStore"
                :title="$t('artwork tickets')"
                icon-bg-class="bg-accent-50 text-accent-700"
                :description="description"
                :search-enabled="false"
            >
                <template #actions>
                    <slot name="actions"></slot>
                </template>
            </ToolbarHeader>

            <BaseTabs :tabs="tabs" />

            <div class="mt-6">
                <slot></slot>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { IconBuildingStore } from '@tabler/icons-vue'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import BaseTabs from '@/Artwork/Tabs/BaseTabs.vue'

defineProps({
    description: { type: String, required: true },
})

const { t } = useI18n()

const tabs = [
    { name: t('Overview'), href: route('settings.tickets'), current: route().current('settings.tickets'), permission: true },
    { name: t('Details & bank account'), href: route('settings.tickets.billing'), current: route().current('settings.tickets.billing'), permission: true },
    { name: t('Rooms & price classes'), href: route('settings.tickets.rooms'), current: route().current('settings.tickets.rooms'), permission: true },
    { name: t('Reductions'), href: route('settings.tickets.reductions'), current: route().current('settings.tickets.reductions'), permission: true },
    { name: t('Team'), href: route('settings.tickets.team'), current: route().current('settings.tickets.team'), permission: true },
]
</script>
