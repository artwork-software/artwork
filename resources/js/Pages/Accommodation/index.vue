<template>
    <UserHeader title="Accommodations" description="Manage your accommodations.">

        <template #tabBar>
            <ToolbarHeader
                :icon="IconLamp2"
                title="Accommodations"
                icon-bg-class="bg-emerald-600/10 text-emerald-700"
                :description="accommodations?.length ? `${accommodations?.length} ${$t('Accommodations')}` : ''"
                :search-enabled="false"
            >
                <template #actions>

                    <button class="ui-button-add" @click="showCreateOrUpdateModal = true">
                        <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                        {{ $t('Create new Accommodation') }}
                    </button>
                </template>
            </ToolbarHeader>
        </template>


        <template #default>
            <BaseTable
                :rows="accommodations"
                :columns="cols"
                row-key="id"
                v-model:page="page"
                empty-title="Keine Unterkünfte"
                empty-message="Derzeit sind keine Einträge vorhanden."
            >

                <!-- Name (Avatar + Name + Email) -->
                <template #cell-name="{ row }">
                    <Link class="flex items-center" :href="route('accommodation.show', row.id)">
                        <div class="size-11 shrink-0">
                            <img :src="row.profile_photo_url" alt="" class="size-11 rounded-full object-cover" />
                        </div>
                        <div class="ml-4">
                            <div class="font-medium text-gray-900">{{ row.name }}</div>
                            <div class="mt-1 text-gray-500">{{ row.email }}</div>
                        </div>
                    </Link>
                </template>

                <!-- Title + Department -->
                <template #cell-address="{ row }">
                    <div>
                        <div class="text-xs font-medium text-gray-900">{{ row.street }}</div>
                        <div class="text-xs font-medium text-gray-900" v-if="row.zip_code && row.location">{{ row.zip_code }}, {{ row.location }}</div>
                    </div>
                </template>

                <!-- Actions -->
                <template #cell-room_types="{ row }">
                    <!-- [ { "id": 1, "name": "dfgdfgdfg", "created_at": "2025-09-26T11:31:10.000000Z", "updated_at": "2025-09-26T11:31:10.000000Z", "pivot": { "accommodation_id": 1, "accommodation_room_type_id": 1, "cost_per_night": "15.00" } } ] -->
                    <div>
                        <!-- map with comma -->
                        <span v-for="(roomType, index) in row.room_types" :key="roomType.id">
                            {{ roomType.name }}<span v-if="index < row.room_types.length - 1">, </span>
                        </span>
                    </div>
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

    <UpdateOrCreateAccommodation
        v-if="showCreateOrUpdateModal"
        :room-types="roomTypes"
        @close="closeAddEditAccommodationModal"
        :accommodation="accommodation"
    />

    <ArtworkBaseDeleteModal
        v-if="showDeleteModal"
        title="Delete accommodation"
        description="Are you sure you want to delete this accommodation?"
        @close="showDeleteModal = false"
        @delete="deleteAccommodation"
    />
</template>

<script setup lang="ts">

import UserHeader from "@/Pages/Users/UserHeader.vue";
import {defineAsyncComponent, ref, nextTick} from "vue";
import {IconLamp2, IconCirclePlus, IconEdit, IconTrash} from "@tabler/icons-vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import BaseTable, { type TableColumn } from '@/Artwork/Table/BaseTable.vue'
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {Link, router} from "@inertiajs/vue3";
import ArtworkBaseDeleteModal from "@/Artwork/Modals/ArtworkBaseDeleteModal.vue";

const props = defineProps({
    accommodations: {
        type: Object,
        required: true,
        default: []
    },
    roomTypes: {
        type: Object,
        required: false,
        default: () => []
    }
})


const showCreateOrUpdateModal = ref(false)
const showDeleteModal = ref(false)
const accommodation = ref([] as any)
const UpdateOrCreateAccommodation = defineAsyncComponent({
    loader: () => import('@/Pages/Accommodation/Components/UpdateOrCreateAccommodation.vue'),
    delay: 200,
    timeout: 3000,
})

const SingleAccommodation = defineAsyncComponent({
    loader: () => import('@/Pages/Accommodation/Components/SingleAccommodation.vue'),
    delay: 200,
    timeout: 3000,
})

const ArtworkBaseButton = defineAsyncComponent({
    loader: () => import('@/Artwork/Buttons/ArtworkBaseButton.vue'),
    delay: 200,
    timeout: 3000,
})

const cols = ref<TableColumn[]>([
    { key: 'name',  label: 'Name',  sortable: false },
    { key: 'address', label: 'Address', sortable: false },
    { key: 'phone_number', label: 'Phone', sortable: false },
    { key: 'room_types', label: 'Room types', sortable: false },
])

const page = ref(1)

const newLineAddress = (address: string) => {
    return address.replace('\n', '<br>')
}

const openEditModal = (acc: Object) => {
    accommodation.value = acc
    showCreateOrUpdateModal.value = false
    nextTick(() => {
        showCreateOrUpdateModal.value = true
    })
}

const openDeleteModal = (acc: Object) => {
    accommodation.value = acc
    showDeleteModal.value = false
    nextTick(() => {
        showDeleteModal.value = true
    })
}

const deleteAccommodation = () => {
    router.delete(route('accommodation.destroy', accommodation.value.id), {
        preserveState: true,
        onSuccess: () => {
            showDeleteModal.value = false
            accommodation.value = []
        },
        onError: () => {
            showDeleteModal.value = false
            accommodation.value = []
        }
    })
}

const closeAddEditAccommodationModal = () => {
    showCreateOrUpdateModal.value = false
    accommodation.value = []
}
</script>


<style scoped>

</style>
