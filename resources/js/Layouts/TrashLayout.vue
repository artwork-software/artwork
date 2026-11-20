<!-- resources/js/Layouts/TrashLayout.vue (oder dein Pfad) -->
<template>
    <Head>
        <link rel="icon" type="image/png" :href="page.props.small_logo" />
        <title>{{ $t("Recycle bin") }} - {{ page.props.page_title }}</title>
    </Head>

    <div class="artwork-container-fluid">
        <!-- Header -->
        <div class="pt-6">
            <ToolbarHeader
                :icon="IconTrash"
                :title="$t('Recycle bin')"
                :description="$t('You can restore objects from your recycle bin or delete them permanently. Items are automatically deleted permanently after 30 days.')"
                band
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
                        :aria-current="tab.key === activeKey ? 'page' : undefined"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm transition
                               focus:outline-none focus:ring-2 focus:ring-surface-inverse/30"
                        :class="tab.key === activeKey
                            ? 'border-surface-inverse/30 bg-surface-inverse/10 text-text'
                            : 'border-border-subtle bg-white text-text-muted hover:bg-surface-sunken'"
                        @click="goTo(tab.href)"
                    >
                        <span class="truncate max-w-[220px]">{{ tab.name }}</span>
                        <span v-if="counts[tab.key] > 0"
                              class="rounded-full bg-surface-inverse/10 px-1.5 text-xs font-medium tabular-nums text-text"
                              :aria-label="counts[tab.key] === 1 ? $t('1 entry') : $t('{count} entries', { count: counts[tab.key] })">
                            {{ counts[tab.key] }}
                        </span>

                        <IconCheck v-if="tab.key === activeKey" class="h-4 w-4" />
                    </button>
                </div>

            </div>
        </div>

        <!-- Content -->
        <div class="py-6">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from "vue"
import axios from "axios"
import { Head, router, usePage } from "@inertiajs/vue3"
import { IconCheck, IconTrash } from "@tabler/icons-vue"
import { getCurrentInstance } from "vue"
import {can, is} from "laravel-permission-to-vuejs"
import {usePermission} from "@/Composeables/Permission.js"
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue"

// Zugriff auf $can / $canAny (wie vorher aus deinem Mixin-System)
const page = usePage()
const instance = getCurrentInstance()
const proxy = instance?.proxy
const { canAny } = usePermission(page.props)

// der Budget-Tab rendert eine Seite außerhalb von Trash/
const activeKey = computed(() => page.component === "BudgetSettingsTemplates/TrashIndex" ? "Trash/Budget" : page.component)

const tabsMap = computed(() => {
    return {
        "Trash/Projects": {
            key: "Trash/Projects",
            name: proxy?.$t ? proxy.$t("Projects") : "Projects",
            href: route("projects.trashed"),
            // Routen-Middleware: can access trash (Modul-Rechte entscheiden nicht mehr über den Tab)
            available: can('can access trash') || is('artwork admin'),
        },
        "Trash/Areas": {
            key: "Trash/Areas",
            name: proxy?.$t ? proxy.$t("Areas ") : "Areas",
            href: route("areas.trashed"),
            available: can('can access trash') || is('artwork admin'),
        },
        "Trash/Rooms": {
            key: "Trash/Rooms",
            name: proxy?.$t ? proxy.$t("Rooms") : "Rooms",
            href: route("rooms.trashed"),
            available: can('can access trash') || is('artwork admin'),
        },
        "Trash/Events": {
            key: "Trash/Events",
            name: proxy?.$t ? proxy.$t("Events") : "Events",
            href: route("events.trashed"),
            available: can('can access trash') || is('artwork admin'),
        },
        "Trash/Shifts": {
            key: "Trash/Shifts",
            name: proxy?.$t ? proxy.$t("Shifts") : "Shifts",
            href: route("shifts.trashed"),
            // Papierkorb-Recht + Dienstplanung (Gewerks-Scoping serverseitig)
            available: (can('can access trash') && can('can plan shifts')) || is('artwork admin'),
        },
        "Trash/ProjectSettings": {
            key: "Trash/ProjectSettings",
            name: proxy?.$t ? proxy.$t("Project Settings") : "Project Settings",
            href: route("projects.settings.trashed"),
            available: can('can access trash') || is('artwork admin'),
        },
        "Trash/SageNotAssignedData": {
            key: "Trash/SageNotAssignedData",
            name: proxy?.$t ? proxy.$t("Sage API data sets") : "Sage API data sets",
            href: route("sageNotAssignedData.trashed"),
            available: canAny(["can view project sage data", "can view global sage data"]) || is('artwork admin'),
        },
        "Trash/BudgetManagementAccount": {
            key: "Trash/BudgetManagementAccount",
            name: proxy?.$t ? proxy.$t("Accounts") : "Accounts",
            href: route("budget-settings.account-management.trash-accounts"),
            // wie die Routen: Budget-Einstellungen, nicht der globale Budgetzugriff
            available: can("change budget settings") || is('artwork admin'),
        },
        "Trash/BudgetManagementCostUnit": {
            key: "Trash/BudgetManagementCostUnit",
            name: proxy?.$t ? proxy.$t("Cost Units") : "Cost Units",
            href: route("budget-settings.account-management.trash-cost-units"),
            // wie die Routen: Budget-Einstellungen, nicht der globale Budgetzugriff
            available: can("change budget settings") || is('artwork admin'),
        },
        "Trash/InventoryArticles": {
            key: "Trash/InventoryArticles",
            name: proxy?.$t ? proxy.$t("Articles") : "Articles",
            href: route("inventory.articles.trash"),
            available: can("inventory.delete") || is('artwork admin'),
        },
        "Trash/Budget": {
            key: "Trash/Budget",
            name: proxy?.$t ? proxy.$t("Budget") : "Budget",
            href: route("project.budget.trashed"),
            available: can('can manage global project budgets') || is('artwork admin'),
        },
        "Trash/CrmContacts": {
            key: "Trash/CrmContacts",
            name: proxy?.$t ? proxy.$t("CRM contacts") : "CRM contacts",
            href: route("crm.contacts.trashed"),
            available: can("crm manager") || is('artwork admin'),
        },
    }
})

// Zahl der Einträge je Tab; nach jeder Aktion im Papierkorb neu geholt
const counts = ref({})
let countRequest = 0

const loadCounts = () => {
    const request = ++countRequest
    axios.get(route('trash.counts'), { skipErrorToast: true })
        .then((response) => {
            // eine ältere Antwort darf eine neuere nicht überschreiben
            if (request === countRequest) {
                counts.value = response.data?.counts ?? {}
            }
        })
        .catch(() => {})
}

let removeSuccessListener = null
onMounted(() => {
    loadCounts()
    removeSuccessListener = router.on('success', loadCounts)
})
onUnmounted(() => removeSuccessListener?.())

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
