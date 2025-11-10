<template>
    <UserHeader
        title="Manufacturers"
        description="Edit and create manufacturers for your inventory."
    >
        <!-- Headbar -->
        <template #tabBar>
            <ToolbarHeader
                :icon="IconBuildingFactory2"
                title="Manufacturers"
                icon-bg-class="bg-yellow-600/10 text-yellow-700"
                v-model="searchManufacturerInput"
                :description="manufacturers?.data?.length ? `${manufacturers?.data?.length} ${$t('Manufacturer')}` : ''"
                :search-enabled="true"
                :search-label="$t('Search for Manufacturer')"
                :search-tooltip="$t('Search')"
            >
                <template #actions>

                    <button class="ui-button-add" @click="showAddEditManufacturerModal = true">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('Create Manufacturer') }}
                    </button>
                </template>
            </ToolbarHeader>
        </template>

        <!-- Content -->
        <template #default>

            <BaseTable
                :rows="manufacturers.data"
                :columns="cols"
                row-key="id"
                :total="manufacturers.total"
                :page-size="manufacturers.per_page"
                v-model:page="page"
                @page-change="onPageChange"
                empty-title="Keine Manufacturers"
                empty-message="Derzeit sind keine Einträge vorhanden."
            >

                <!-- Name (Avatar + Name + Email) -->
                <template #cell-name="{ row }">
                    <div class="flex items-center">
                        <div class="size-11 shrink-0">
                            <img :src="row.profile_photo_url" alt="" class="size-11 rounded-full object-cover" />
                        </div>
                        <div class="ml-4">
                            <div class="font-medium text-gray-900">{{ row.name }}</div>
                            <div class="mt-1 text-gray-500">{{ row.email }}</div>
                        </div>
                    </div>
                </template>

                <!-- Title + Department -->
                <template #cell-address="{ row }">
                    <p class="truncate" v-html="newLineAddress(row.address)"></p>
                </template>


                <!-- Actions -->
                <template #row-actions="{ row }">
                    <BaseMenu has-no-offset white-menu-background>
                        <BaseMenuItem :icon="IconEdit" title="Edit" white-menu-background  @click="openEditModal(row)" />
                        <BaseMenuItem :icon="IconTrash" title="Delete" white-menu-background  @click="openDeleteModal(row)" />
                    </BaseMenu>
                </template>
            </BaseTable>

        </template>
    </UserHeader>

    <!-- Modal -->
    <CreateOrUpdateManufacturerModal
        v-if="showAddEditManufacturerModal"
        @close="closeAddEditManufacturerModal"
        :manufacturer="manufacturer"
    />

    <ConfirmDeleteModal
        v-if="showDeleteModal"
        @close="showDeleteModal = false"
        :title="$t('Delete Manufacturer')"
        :description="$t('Are you sure you want to delete this manufacturer?')"
        @delete="deleteManufacturer"
    />
</template>

<script setup lang="ts">
import { ref, watch, nextTick } from 'vue'
import {router, usePage} from '@inertiajs/vue3'
import UserHeader from '@/Pages/Users/UserHeader.vue'
import CreateOrUpdateManufacturerModal from '@/Pages/Manufacturer/Components/Modals/CreateOrUpdateManufacturerModal.vue'
import debounce from 'lodash.debounce'

const props = defineProps({
    manufacturers: { type: Object, required: true },
})

import BaseTable, { type TableColumn } from '@/Artwork/Table/BaseTable.vue'
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import {IconCirclePlus, IconBuildingFactory2, IconEdit, IconTrash} from "@tabler/icons-vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";

const cols = ref<TableColumn[]>([
    { key: 'name',  label: 'Name',  sortable: false },
    { key: 'address', label: 'Address', sortable: false },
    { key: 'phone', label: 'Phone', sortable: false },
    { key: 'contact_person', label: 'Contact Person', sortable: false },
])


// aktuelles Page aus dem Backend vorbesetzen
const page = ref(props.manufacturers.current_page ?? 1)

// falls sich die Antwort vom Server ändert, Page mitsinchen
watch(() => props.manufacturers.current_page, (v) => { if (v) page.value = v })

const showAddEditManufacturerModal = ref(false)
const searchManufacturerInput = ref(usePage().props?.urlParameters?.search ?? '')
const searchBarInput = ref(null)
const manufacturer = ref(null)
const showDeleteModal = ref(false)

/** Debounced search */
const searchManufacturers = debounce(() => {
    router.reload({
        data: { search: searchManufacturerInput.value },
        preserveScroll: true,
        only: ['manufacturers'],
    })
}, 500)

watch(searchManufacturerInput, () => searchManufacturers())

// ne line add \n on address
const newLineAddress = (address: string) => {
    return address.replace('\n', '<br>')
}

function onPageChange({ page: newPage, pageSize }: { page: number; pageSize: number }) {

    router.reload({
            only: ['manufacturers'],
            data: {
                page: newPage,
                per_page: pageSize
            },
        },
    )
}

const openEditModal = (acc: Object) => {
    manufacturer.value = acc
    showAddEditManufacturerModal.value = false
    nextTick(() => {
        showAddEditManufacturerModal.value = true
    })
}

const openDeleteModal = (acc: Object) => {
    manufacturer.value = acc
    showDeleteModal.value = false
    nextTick(() => {
        showDeleteModal.value = true
    })
}

const deleteManufacturer = () => {
    router.delete(route('manufacturers.destroy', manufacturer.value.id), {
        preserveState: true,
        onSuccess: () => {
            showDeleteModal.value = false
            manufacturer.value = null
        },
        onError: () => {
            showDeleteModal.value = false
            manufacturer.value = null
        }
    })
}

const closeAddEditManufacturerModal = () => {
    showAddEditManufacturerModal.value = false
    manufacturer.value = null
}
</script>

<style scoped></style>
