<template>
    <AppLayout :title="$t('Artwork-Tickets')">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconBuildingStore"
                :title="$t('Artwork-Tickets')"
                icon-bg-class="bg-accent-50 text-accent-700"
                :description="description"
                :search-enabled="false"
            >
                <template #actions>
                    <slot name="actions"></slot>
                </template>
                <template v-if="testMode" #extra>
                    <div class="mt-3 flex flex-wrap items-center gap-3 rounded-md border border-warning-border bg-warning-surface px-3.5 py-2 text-[13px] text-warning">
                        <IconFlask class="size-4 shrink-0" />
                        <span class="mr-auto"><strong class="font-semibold">{{ $t('Test mode.') }}</strong> {{ $t('Test payments only; the shop opens with its test login. Going live deletes the test sales.') }}</span>
                        <button type="button" class="ui-button-add" @click="goLiveOpen = true">
                            <IconRocket class="size-3.5" />{{ $t('Go live') }}
                        </button>
                    </div>
                </template>
            </ToolbarHeader>

            <GoLiveDialog v-if="goLiveOpen" @close="goLiveOpen = false" />

            <BaseTabs :tabs="tabs" />

            <div class="mt-6">
                <slot></slot>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { IconBuildingStore, IconFlask, IconRocket } from '@tabler/icons-vue'
import { useI18n } from 'vue-i18n'
import GoLiveDialog from '@/Pages/Settings/Tickets/GoLiveDialog.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import BaseTabs from '@/Artwork/Tabs/BaseTabs.vue'

defineProps({
    description: { type: String, required: true },
})

const { t } = useI18n()

/* Every tab says the house is still testing, with the way out next to it. */
const testMode = computed(() => usePage().props.ticketing?.testMode === true)
const goLiveOpen = ref(false)

const tabs = [
    { name: t('Overview'), href: route('settings.tickets'), current: route().current('settings.tickets'), permission: true },
    { name: t('Details & payouts'), href: route('settings.tickets.billing'), current: route().current('settings.tickets.billing'), permission: true },
    { name: t('Rooms & price classes'), href: route('settings.tickets.rooms'), current: route().current('settings.tickets.rooms'), permission: true },
    { name: t('Production groups'), href: route('settings.tickets.groups'), current: route().current('settings.tickets.groups'), permission: true },
    { name: t('Reductions'), href: route('settings.tickets.reductions'), current: route().current('settings.tickets.reductions'), permission: true },
    { name: t('Passes & subscriptions'), href: route('settings.tickets.passes'), current: route().current('settings.tickets.passes'), permission: true },
    { name: t('Team'), href: route('settings.tickets.team'), current: route().current('settings.tickets.team'), permission: true },
]
</script>
