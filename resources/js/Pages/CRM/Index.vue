<template>
    <AppLayout :title="$t('CRM')">
        <div class="mt-5 mx-auto container pb-20">
            <ToolbarHeader
                :icon="IconAddressBook"
                :title="$t('CRM')"
                icon-bg-class="bg-indigo-600/10 text-indigo-700"
                v-model="searchInput"
                :description="contacts?.total ? `${contacts.total} ${$t('Contacts')}` : ''"
                :search-enabled="true"
                :search-label="$t('Search contacts')"
                :search-tooltip="$t('Search')"
            >
                <template #actions>
                    <!--
                    <button v-if="canImport" class="ui-button flex items-center gap-1.5" @click="showExportModal = true">
                        <component :is="IconDownload" stroke-width="1" class="size-5" />
                        {{ $t('Export') }}
                    </button>
                    -->
                    <!-- TODO: Externe-Einladen-Feature vorerst ausgeblendet (noch nicht ausgereift)
                    <button
                        v-if="can('can invite externals')"
                        class="ui-button flex items-center gap-1.5"
                        @click="showInviteModal = true"
                    >
                        <component :is="IconUserPlus" stroke-width="1" class="size-5" />
                        {{ $t('Invite external') }}
                    </button>
                    -->
                    <Link v-if="canImport" :href="route('crm.duplicates')" class="ui-button flex items-center gap-1.5">
                        <component :is="IconUsers" stroke-width="1" class="size-5" />
                        {{ $t('Find duplicates') }}
                    </Link>
                    <div v-if="hasFilterableProperties" class="relative inline-flex">
                        <ToolTipComponent
                            direction="bottom"
                            :tooltip-text="$t('Filter')"
                            icon="IconFilter"
                            icon-size="h-5 w-5"
                            @click="showFilterModal = true"
                            classes-button="ui-button"
                        />
                        <span
                            v-if="activeFilterCount > 0"
                            class="absolute -top-1 -right-1 flex items-center justify-center size-4 rounded-full bg-blue-500 text-white text-[10px] font-bold pointer-events-none"
                        >
                            {{ activeFilterCount }}
                        </span>
                    </div>
                    <span v-if="isMirroredType" class="text-xs text-gray-500">
                        <template v-if="activeType?.slug === 'user'">{{ $t('Creation only possible in user management') }}</template>
                        <template v-else-if="activeType?.slug === 'freelancer'">{{ $t('Creation only possible in freelancer management') }}</template>
                        <template v-else-if="activeType?.slug === 'service_provider'">{{ $t('Creation only possible in service provider management') }}</template>
                    </span>
                    <template v-else>
                        <Link v-if="canImport" :href="route('crm.import')" class="ui-button flex items-center gap-1.5">
                            <component :is="IconUpload" stroke-width="1" class="size-5" />
                            {{ $t('Import') }}
                        </Link>
                        <button class="ui-button-add" @click="showCreateModal = true">
                            <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                            {{ $t('New contact') }}
                        </button>
                    </template>
                </template>
            </ToolbarHeader>

            <Transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0 -translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-1"
            >
                <div v-if="successMessage" class="mt-4 rounded-md bg-green-50 p-3">
                    <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
                </div>
            </Transition>

            <!-- Import Result Banner -->
            <div v-if="importResult" class="mt-4 space-y-2">
                <div v-if="importResult.created > 0" class="rounded-md bg-green-50 p-3">
                    <p class="text-sm font-medium text-green-800">
                        {{ $t('{count} contacts created', { count: importResult.created }) }}
                    </p>
                </div>
                <div v-if="importResult.updated > 0" class="rounded-md bg-blue-50 p-3">
                    <p class="text-sm font-medium text-blue-800">
                        {{ $t('{count} existing contacts updated', { count: importResult.updated }) }}
                    </p>
                </div>
                <div v-if="importResult.duplicates > 0" class="rounded-md bg-gray-50 border border-gray-200 p-3">
                    <p class="text-sm font-medium text-gray-700">
                        {{ $t('{count} rows skipped as duplicates', { count: importResult.duplicates }) }}
                    </p>
                </div>
                <div v-if="importResult.skipped?.length > 0" class="rounded-md bg-yellow-50 border border-yellow-200 p-3">
                    <button
                        class="flex items-center gap-2 text-sm font-medium text-yellow-800 w-full text-left"
                        @click="showSkipped = !showSkipped"
                    >
                        {{ $t('{count} rows skipped', { count: importResult.skipped.length }) }}
                        <svg class="size-4 transition-transform" :class="showSkipped ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <ul v-if="showSkipped" class="mt-2 space-y-1">
                        <li v-for="(skip, i) in importResult.skipped" :key="i" class="text-xs text-yellow-700">
                            {{ $t('Row') }} {{ skip.row }}: {{ skip.reason }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Contact Type Tabs -->
            <div class="mt-4 border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="Tabs">
                    <button
                        v-for="type in contactTypes"
                        :key="type.id"
                        @click="switchType(type.slug)"
                        class="group inline-flex items-center border-b-2 py-4 px-1 text-sm font-medium whitespace-nowrap"
                        :class="type.id === activeType?.id
                            ? 'border-current'
                            : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                        :style="type.id === activeType?.id && type.color ? { color: type.color } : {}"
                    >
                        <PropertyIcon v-if="type.icon" :name="type.icon" class="mr-2 h-5 w-5" :style="type.color ? { color: type.color } : {}" />
                        {{ $t(type.name) }}
                        <span
                            v-if="type.id === activeType?.id && contacts?.total"
                            class="ml-2 rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :style="type.color ? { backgroundColor: type.color + '15', color: type.color } : {}"
                        >
                            {{ contacts.total }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Bulk action bar -->
            <div v-if="selectedIds.size > 0" class="mt-4 flex items-center gap-4 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5">
                <span class="text-sm font-medium text-indigo-900">{{ $t('{count} selected', { count: selectedIds.size }) }}</span>
                <button type="button" class="text-sm text-indigo-600 hover:text-indigo-500" @click="toggleSelectPage">
                    {{ allOnPageSelected ? $t('Deselect all') : $t('Select all on this page') }}
                </button>
                <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="selectedIds = new Set()">
                    {{ $t('Clear selection') }}
                </button>
                <button type="button" class="ml-auto flex items-center gap-1 text-sm font-medium text-red-600 hover:text-red-500" @click="showBulkDeleteModal = true">
                    <component :is="IconTrash" class="size-4" />
                    {{ $t('Delete selection') }}
                </button>
            </div>

            <!-- Contacts Table -->
            <div class="mt-6">
                <BaseTable
                    v-if="contacts?.data?.length"
                    :rows="contacts.data"
                    :columns="columns"
                    row-key="id"
                    :total="contacts.total"
                    :page-size="contacts.per_page"
                    v-model:page="page"
                    :sort-key="sortState.key"
                    :sort-direction="sortState.direction"
                    @page-change="onPageChange"
                    @sort-change="onSortChange"
                    :empty-title="$t('No contacts')"
                    :empty-message="$t('No contacts found for this type.')"
                >
                    <template #cell-_select="{ row }">
                        <input
                            type="checkbox"
                            class="rounded border-gray-300 text-indigo-600"
                            :checked="selectedIds.has(row.id)"
                            @click.stop
                            @change="toggleSelect(row.id)"
                        />
                    </template>

                    <template #cell-display_name="{ row }">
                        <Link :href="route('crm.contacts.show', row.id)" class="flex items-center hover:text-indigo-600">
                            <div class="size-10 shrink-0">
                                <img :src="row.profile_photo_url" alt="" class="size-10 rounded-full object-cover" />
                            </div>
                            <div class="ml-3">
                                <div class="font-medium text-gray-900">{{ row.display_name }}</div>
                            </div>
                        </Link>
                    </template>

                    <template #row-actions="{ row }">
                        <BaseMenu has-no-offset white-menu-background>
                            <BaseMenuItem :icon="IconEye" :title="$t('View')" white-menu-background @click="$inertia.visit(route('crm.contacts.show', row.id))" />
                            <BaseMenuItem v-if="!isMirroredType" :icon="IconTrash" :title="$t('Delete')" white-menu-background @click="openDeleteModal(row)" />
                        </BaseMenu>
                    </template>
                </BaseTable>

                <div v-else class="text-center py-12">
                    <component :is="IconAddressBook" class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $t('No contacts') }}</h3>
                    <p v-if="isMirroredType" class="mt-1 text-sm text-gray-500">
                        <template v-if="activeType?.slug === 'user'">{{ $t('Creation only possible in user management') }}</template>
                        <template v-else-if="activeType?.slug === 'freelancer'">{{ $t('Creation only possible in freelancer management') }}</template>
                        <template v-else-if="activeType?.slug === 'service_provider'">{{ $t('Creation only possible in service provider management') }}</template>
                    </p>
                    <template v-else>
                        <p class="mt-1 text-sm text-gray-500">{{ $t('Get started by creating a new contact.') }}</p>
                        <div class="mt-6">
                            <button class="ui-button-add" @click="showCreateModal = true">
                                <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                                {{ $t('New contact') }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Create Contact Modal -->
        <CreateContactModal
            v-if="showCreateModal"
            :contact-types="contactTypes"
            :active-type="activeType"
            @close="showCreateModal = false"
        />

        <!-- Delete Confirmation -->
        <ConfirmDeleteModal
            v-if="showDeleteModal"
            @close="showDeleteModal = false"
            :title="$t('Delete contact')"
            :description="$t('Are you sure you want to delete this contact? This action cannot be undone.')"
            @delete="deleteContact"
        />

        <!-- Bulk Delete Confirmation -->
        <ConfirmDeleteModal
            v-if="showBulkDeleteModal"
            @close="showBulkDeleteModal = false"
            :title="$t('Delete selection')"
            :description="$t('Are you sure you want to delete {count} contacts?', { count: selectedIds.size })"
            @delete="bulkDelete"
        />

        <!-- Filter Modal -->
        <CrmFilterModal
            v-if="showFilterModal"
            :filterable-properties="filterableProperties"
            :current-filters="activeFilters"
            @close="showFilterModal = false"
            @apply="applyFilters"
        />

        <!-- Export Modal -->
        <CrmExportModal
            v-if="showExportModal"
            :contact-types="contactTypes"
            :active-type="activeType"
            :current-search="searchInput"
            :current-filters="activeFilters"
            @close="showExportModal = false"
        />

        <!-- Invite External Modal — vorerst ausgeblendet (noch nicht ausgereift)
        <InviteExternalModal
            v-if="showInviteModal"
            source="crm_index"
            :contact-types="contactTypes"
            @close="showInviteModal = false"
            @success="showSuccess($t('Invitation sent.'))"
        />
        -->
    </AppLayout>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { router, Link, usePage } from '@inertiajs/vue3'
import { useTranslation } from '@/Composeables/Translation.js'
import { usePermission } from '@/Composeables/Permission.js'
// Externe-Einladen-Feature vorerst ausgeblendet (noch nicht ausgereift)
// import InviteExternalModal from '@/Pages/CRM/Components/InviteExternalModal.vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import BaseTable from '@/Artwork/Table/BaseTable.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import CreateContactModal from '@/Pages/CRM/Components/CreateContactModal.vue'
import CrmFilterModal from '@/Pages/CRM/Components/CrmFilterModal.vue'
import CrmExportModal from '@/Pages/CRM/Components/CrmExportModal.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import { IconAddressBook, IconCirclePlus, IconEye, IconTrash, IconUpload, IconDownload, IconUserPlus, IconUsers } from '@tabler/icons-vue'
import debounce from 'lodash.debounce'

const props = defineProps({
    contactTypes: { type: Array, required: true },
    activeType: { type: Object, default: null },
    contacts: { type: Object, default: null },
    canImport: { type: Boolean, default: false },
    importResult: { type: Object, default: null },
})

const $t = useTranslation()
const { can } = usePermission(usePage().props)

const mirroredSlugs = ['user', 'freelancer', 'service_provider']

const isMirroredType = computed(() => mirroredSlugs.includes(props.activeType?.slug))

const successMessage = ref('')
const showSuccess = (msg) => {
    successMessage.value = msg
    setTimeout(() => { successMessage.value = '' }, 3000)
}

const searchInput = ref('')
const page = ref(props.contacts?.current_page ?? 1)
const showCreateModal = ref(false)
const showDeleteModal = ref(false)
const showFilterModal = ref(false)
const showExportModal = ref(false)
const showInviteModal = ref(false)
const showSkipped = ref(false)
const contactToDelete = ref(null)
const activeFilters = ref({})

const filterableProperties = computed(() =>
    (props.activeType?.properties ?? []).filter(p => p.pivot?.is_filterable && p.type !== 'upload')
)
const activeFilterCount = computed(() =>
    Object.values(activeFilters.value).filter(v =>
        Array.isArray(v) ? v.length > 0 : v !== null && v !== '' && v !== undefined
    ).length
)
const hasFilterableProperties = computed(() => filterableProperties.value.length > 0)

watch(() => props.contacts?.current_page, (v) => { if (v) page.value = v })

// Build dynamic columns from properties that have show_in_list
const columns = computed(() => {
    const cols = []

    if (!isMirroredType.value) {
        cols.push({ key: '_select', label: '', sortable: false, width: '40px' })
    }

    cols.push({ key: 'display_name', label: 'Name', sortable: true })

    if (props.activeType?.properties) {
        props.activeType.properties
            .filter(p => p.pivot?.show_in_list)
            .forEach(p => {
                cols.push({
                    key: `prop_${p.id}`,
                    label: p.name,
                    sortable: true,
                    type: p.type,
                    accessor: (row) => {
                        const pv = (row.property_values ?? []).find(v => v.crm_property_id === p.id)
                        return formatCellValue(p.type, pv?.value)
                    },
                })
            })
    }

    return cols
})

// Datum immer DD.MM.YYYY, Checkboxen lesbar statt "1"/"0"
const formatCellValue = (type, raw) => {
    if (raw === null || raw === undefined || raw === '') return '-'
    if (type === 'date') {
        const date = new Date(raw)
        if (isNaN(date.getTime())) return raw
        const day = String(date.getDate()).padStart(2, '0')
        const month = String(date.getMonth() + 1).padStart(2, '0')
        return `${day}.${month}.${date.getFullYear()}`
    }
    if (type === 'checkbox') {
        return raw === '1' ? '✓' : '—'
    }
    return raw
}

const sortState = ref({ key: null, direction: null })

const switchType = (slug) => {
    activeFilters.value = {}
    sortState.value = { key: null, direction: null }
    selectedIds.value = new Set()
    router.get(route('crm.index'), { type: slug }, {
        preserveState: true,
        preserveScroll: true,
    })
}

// One place that assembles the full query state so page changes and sorting
// never drop the active search/filters again.
const buildQueryData = () => {
    const data = { type: props.activeType?.slug, search: searchInput.value || '' }
    const nonEmpty = Object.fromEntries(
        Object.entries(activeFilters.value).filter(([, v]) =>
            Array.isArray(v) ? v.length > 0 : v !== null && v !== '' && v !== undefined
        )
    )
    if (Object.keys(nonEmpty).length > 0) data.filters = JSON.stringify(nonEmpty)
    if (sortState.value.key && sortState.value.direction) {
        data.sort = sortState.value.key
        data.direction = sortState.value.direction
    }
    if (props.contacts?.per_page) data.per_page = props.contacts.per_page
    return data
}

const reloadContacts = (extra = {}) => {
    selectedIds.value = new Set()
    router.reload({
        data: { ...buildQueryData(), page: 1, ...extra },
        preserveScroll: true,
        only: ['contacts'],
    })
}

const debouncedReload = debounce(() => reloadContacts(), 500)

watch(searchInput, (val) => {
    if (!val) {
        debouncedReload.cancel()
        reloadContacts()
    } else {
        debouncedReload()
    }
})

const applyFilters = (filters) => {
    activeFilters.value = filters
    reloadContacts()
}

function onPageChange({ page: newPage, pageSize }) {
    selectedIds.value = new Set()
    router.reload({
        only: ['contacts'],
        data: { ...buildQueryData(), page: newPage, per_page: pageSize ?? props.contacts?.per_page },
    })
}

function onSortChange({ key, direction }) {
    sortState.value = direction ? { key, direction } : { key: null, direction: null }
    reloadContacts()
}

// -- Bulk selection / delete --
const selectedIds = ref(new Set())
const showBulkDeleteModal = ref(false)

const allOnPageSelected = computed(() =>
    (props.contacts?.data?.length ?? 0) > 0 && props.contacts.data.every(c => selectedIds.value.has(c.id))
)

const toggleSelect = (id) => {
    if (selectedIds.value.has(id)) {
        selectedIds.value.delete(id)
    } else {
        selectedIds.value.add(id)
    }
}

const toggleSelectPage = () => {
    if (allOnPageSelected.value) {
        selectedIds.value = new Set()
    } else {
        selectedIds.value = new Set((props.contacts?.data ?? []).map(c => c.id))
    }
}

const bulkDelete = () => {
    const count = selectedIds.value.size
    router.post(route('crm.contacts.bulk-destroy'), { ids: [...selectedIds.value] }, {
        preserveScroll: true,
        onSuccess: () => {
            showBulkDeleteModal.value = false
            selectedIds.value = new Set()
            showSuccess($t('{count} contacts deleted', { count }))
        },
    })
}

const openDeleteModal = (contact) => {
    contactToDelete.value = contact
    showDeleteModal.value = true
}

const deleteContact = () => {
    router.delete(route('crm.contacts.destroy', contactToDelete.value.id), {
        preserveState: true,
        onSuccess: () => {
            showDeleteModal.value = false
            contactToDelete.value = null
            showSuccess($t('Contact deleted successfully'))
        },
    })
}
</script>
