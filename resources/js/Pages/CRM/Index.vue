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
                    <button v-else class="ui-button-add" @click="showCreateModal = true">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('New contact') }}
                    </button>
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
                    @page-change="onPageChange"
                    :empty-title="$t('No contacts')"
                    :empty-message="$t('No contacts found for this type.')"
                >
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

        <!-- Filter Modal -->
        <CrmFilterModal
            v-if="showFilterModal"
            :filterable-properties="filterableProperties"
            :current-filters="activeFilters"
            @close="showFilterModal = false"
            @apply="applyFilters"
        />
    </AppLayout>
</template>

<script setup>
import { ref, watch, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { useTranslation } from '@/Composeables/Translation.js'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import BaseTable from '@/Artwork/Table/BaseTable.vue'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import CreateContactModal from '@/Pages/CRM/Components/CreateContactModal.vue'
import CrmFilterModal from '@/Pages/CRM/Components/CrmFilterModal.vue'
import ToolTipComponent from '@/Components/ToolTips/ToolTipComponent.vue'
import { IconAddressBook, IconCirclePlus, IconEye, IconTrash } from '@tabler/icons-vue'
import debounce from 'lodash.debounce'

const props = defineProps({
    contactTypes: { type: Array, required: true },
    activeType: { type: Object, default: null },
    contacts: { type: Object, default: null },
})

const $t = useTranslation()

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
    const cols = [{ key: 'display_name', label: 'Name', sortable: false }]

    if (props.activeType?.properties) {
        props.activeType.properties
            .filter(p => p.pivot?.show_in_list && p.name !== 'Name')
            .forEach(p => {
                cols.push({
                    key: `prop_${p.id}`,
                    label: p.name,
                    sortable: false,
                    accessor: (row) => {
                        const pv = (row.property_values ?? []).find(v => v.crm_property_id === p.id)
                        return pv?.value ?? '-'
                    },
                })
            })
    }

    return cols
})

const switchType = (slug) => {
    activeFilters.value = {}
    router.get(route('crm.index'), { type: slug }, {
        preserveState: true,
        preserveScroll: true,
    })
}

const reloadContacts = () => {
    const data = { type: props.activeType?.slug, search: searchInput.value || '' }
    const nonEmpty = Object.fromEntries(
        Object.entries(activeFilters.value).filter(([, v]) =>
            Array.isArray(v) ? v.length > 0 : v !== null && v !== '' && v !== undefined
        )
    )
    if (Object.keys(nonEmpty).length > 0) data.filters = JSON.stringify(nonEmpty)
    router.reload({ data, preserveScroll: true, only: ['contacts'] })
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
    router.reload({
        only: ['contacts'],
        data: { page: newPage, per_page: pageSize, type: props.activeType?.slug },
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
