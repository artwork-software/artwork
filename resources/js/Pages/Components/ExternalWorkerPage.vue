<template>
    <!-- Gemeinsames Gerüst der Seiten für Freelancer:innen und Dienstleister: Zurück-Link, Kopf mit Menü,
         Reiter (in der Adresse geführt) und der Inhalt des gewählten Reiters als Slot gleichen Namens. -->
    <div class="mx-auto mt-6 max-w-7xl pb-20">
        <Link :href="route('users')" class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-text">
            <IconChevronLeft class="size-4" />
            {{ $t('Back to overview') }}
        </Link>

        <div class="mt-3 flex items-center justify-between gap-3">
            <div class="flex min-w-0 items-center gap-3">
                <img :src="photoUrl" alt="" class="size-16 shrink-0 rounded-full object-cover shadow-sm ring-2 ring-white" />
                <h1 class="truncate text-xl font-semibold text-text">{{ name }}</h1>
            </div>
            <BaseMenu v-if="canManage" has-no-offset white-menu-background>
                <BaseMenuItem :icon="IconTrash" :title="deleteTitle" white-menu-background @click="showConfirmDelete = true" />
            </BaseMenu>
        </div>

        <div class="my-8 w-full overflow-x-auto">
            <nav class="flex gap-2">
                <button
                    v-for="tab in visibleTabs"
                    :key="tab.key"
                    type="button"
                    :aria-current="tab.key === currentTab ? 'page' : undefined"
                    :class="[ tab.key === currentTab
                        ? 'bg-accent-50 text-accent-700 ring-1 ring-inset ring-accent-600'
                        : 'text-text-muted hover:text-text hover:bg-surface-sunken',
                      'inline-flex shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium whitespace-nowrap'
                    ]"
                    @click="selectTab(tab.key)"
                >
                    {{ $t(tab.name) }}
                </button>
            </nav>
        </div>

        <slot :name="currentTab" />

        <ConfirmDeleteModal
            v-if="showConfirmDelete"
            :title="$t(deleteTitle)"
            :description="$t('Are you sure you want to delete {name} permanently? This cannot be undone.', { name })"
            @closed="showConfirmDelete = false"
            @delete="destroy"
        />
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { IconChevronLeft, IconTrash } from '@tabler/icons-vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import { searchWithTab, tabFromSearch } from '@/Composeables/urlTab.js'

defineOptions({ name: 'ExternalWorkerPage' })

const props = defineProps({
    name: { type: String, required: true },
    photoUrl: { type: String, default: '' },
    // [{ key, name, visible }]; name ist ein Übersetzungsschlüssel
    tabs: { type: Array, required: true },
    defaultTab: { type: String, required: true },
    // Stammdaten bearbeiten und löschen dürfen
    canManage: { type: Boolean, default: false },
    deleteHref: { type: String, required: true },
    deleteTitle: { type: String, default: 'Delete' },
})

const visibleTabs = computed(() => props.tabs.filter((tab) => tab.visible))
const currentTab = ref(tabFromSearch(
    window.location.search,
    visibleTabs.value.map((tab) => tab.key),
    props.defaultTab,
))
const showConfirmDelete = ref(false)

const selectTab = (key) => {
    currentTab.value = key
    // nur die Adresse anpassen, kein Seitenaufruf
    const url = window.location.pathname + searchWithTab(window.location.search, key, props.defaultTab)
    router.replace({ url, preserveState: true, preserveScroll: true })
}

const destroy = () => {
    router.delete(props.deleteHref, {
        onFinish: () => {
            showConfirmDelete.value = false
        },
    })
}
</script>
