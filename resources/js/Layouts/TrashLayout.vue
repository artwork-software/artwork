<!-- resources/js/Layouts/TrashLayout.vue (oder dein Pfad) -->
<template>
    <Head>
        <link rel="icon" type="image/png" :href="page.props.small_logo" />
        <title>{{ $t("Recycle bin") }} - {{ page.props.page_title }}</title>
    </Head>

    <div class="artwork-container">
        <!-- Header -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
            <ToolbarHeader
                :icon="IconTrash"
                :title="$t('Recycle bin')"
                :description="$t('You can restore objects from your recycle bin or delete them permanently. Items are automatically deleted permanently after 30 days.')"
                icon-bg-class="bg-blue-600/10 text-blue-700"
                :search-enabled="false"
            >
                <template #actions>
                    <!-- optional: Platz für zukünftige Actions -->
                </template>
            </ToolbarHeader>

            <!-- Tabs -->
            <div class="mt-5">
                <div class="flex flex-wrap items-center gap-2">
                    <button
                        v-for="tab in availableTabs"
                        :key="tab.key"
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition
                               focus:outline-none focus:ring-2 focus:ring-primary/30"
                        :class="tab.key === activeKey
                            ? 'border-primary/30 bg-primary/10 text-primary'
                            : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'"
                        @click="goTo(tab.href)"
                    >
                        <span class="truncate max-w-[220px]">{{ tab.name }}</span>

                        <IconCheck v-if="tab.key === activeKey" class="h-4 w-4" />
                    </button>
                </div>

            </div>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue"
import { Head, usePage } from "@inertiajs/vue3"
import { IconCheck, IconTrash } from "@tabler/icons-vue"
import { getCurrentInstance } from "vue"
import {can, is} from "laravel-permission-to-vuejs"
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue"

// Zugriff auf $can / $canAny (wie vorher aus deinem Mixin-System)
const page = usePage()
const instance = getCurrentInstance()
const proxy = instance?.proxy

const activeKey = computed(() => page.component)

const tabsMap = computed(() => {
    return {
        "Trash/Projects": {
            key: "Trash/Projects",
            name: proxy?.$t ? proxy.$t("Projects") : "Projects",
            href: route("projects.trashed"),
            available: true,
        },
        "Trash/Areas": {
            key: "Trash/Areas",
            name: proxy?.$t ? proxy.$t("Areas") : "Areas",
            href: route("areas.trashed"),
            available: true,
        },
        "Trash/Rooms": {
            key: "Trash/Rooms",
            name: proxy?.$t ? proxy.$t("Rooms") : "Rooms",
            href: route("rooms.trashed"),
            available: true,
        },
        "Trash/Events": {
            key: "Trash/Events",
            name: proxy?.$t ? proxy.$t("Events") : "Events",
            href: route("events.trashed"),
            available: true,
        },
        "Trash/ProjectSettings": {
            key: "Trash/ProjectSettings",
            name: proxy?.$t ? proxy.$t("Project Settings") : "Project Settings",
            href: route("projects.settings.trashed"),
            available: true,
        },
        "Trash/SageNotAssignedData": {
            key: "Trash/SageNotAssignedData",
            name: proxy?.$t ? proxy.$t("Sage API data sets") : "Sage API data sets",
            href: route("sageNotAssignedData.trashed"),
            available: can("can view and delete sage100-api-data") || is('artwork admin'),
        },
        "Trash/BudgetManagementAccount": {
            key: "Trash/BudgetManagementAccount",
            name: proxy?.$t ? proxy.$t("Accounts") : "Accounts",
            href: route("budget-settings.account-management.trash-accounts"),
            available: can("can manage global project budgets|can manage all project budgets without docs") || is('artwork admin'),
        },
        "Trash/BudgetManagementCostUnit": {
            key: "Trash/BudgetManagementCostUnit",
            name: proxy?.$t ? proxy.$t("Cost Units") : "Cost Units",
            href: route("budget-settings.account-management.trash-cost-units"),
            available: can("can manage global project budgets|can manage all project budgets without docs") || is('artwork admin'),
        },
        "Trash/InventoryArticles": {
            key: "Trash/InventoryArticles",
            name: proxy?.$t ? proxy.$t("Articles") : "Articles",
            href: route("inventory.articles.trash"),
            available: true,
        },
        "Trash/Budget": {
            key: "Trash/Budget",
            name: proxy?.$t ? proxy.$t("Budget") : "Budget",
            href: route("project.budget.trashed"),
            available: true,
        },
    }
})

const availableTabs = computed(() =>
    Object.values(tabsMap.value).filter((t) => !!t.available)
)

function goTo(href) {
    if (!href) return
    proxy?.$inertia?.get(href, {}, { preserveState: true, preserveScroll: true })
}
</script>

<style scoped>
/* bewusst leer – Tailwind übernimmt */
</style>
