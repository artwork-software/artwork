<template>
    <div class="min-w-0 w-full">
        <div class="flex items-start justify-between gap-x-10">
            <p class="text-sm/6 font-semibold text-gray-900">{{ manufacturer.name }}</p>

        </div>
        <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
            <p class="whitespace-nowrap">
                {{ manufacturer.address }}
            </p>
        </div>
        <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
            <p class="whitespace-nowrap">
                {{ manufacturer.contact_person }}
            </p>
        </div>
        <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
            <p class="whitespace-nowrap">
                {{ manufacturer.phone }}
            </p>
        </div>
        <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
            <p class="whitespace-nowrap">
                {{ manufacturer.email }}
            </p>
        </div>
        <div class="mt-1 flex items-center gap-x-2 text-xs/5 text-gray-500">
            <p class="whitespace-nowrap">
                {{ $t('Customer Number') }}: {{ manufacturer.customer_number }}
            </p>
        </div>
    </div>
    <div class="flex flex-none items-center gap-x-4">
        <a v-if="manufacturer.website" :href="manufacturer.website" target="_blank" class="inline-flex items-center rounded-md bg-gray-50 hover:bg-blue-50 hover:text-blue-500 duration-200 ease-in-out px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-500/10 ring-inset">
            {{ $t('Manufacturer website') }}
        </a>
        <BaseMenu has-no-offset>
            <BaseMenuItem @click="showAddEditManufacturerModal = true" icon="IconEdit" title="Edit Manufacturer" />
            <BaseMenuItem icon="IconTrash" title="Delete Manufacturer" @click="showDeleteModal = true"/>
        </BaseMenu>
    </div>

    <CreateOrUpdateManufacturerModal
        v-if="showAddEditManufacturerModal"
        @close="showAddEditManufacturerModal = false"
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

<script setup>

import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import CreateOrUpdateManufacturerModal
    from "@/Pages/Manufacturer/Components/Modals/CreateOrUpdateManufacturerModal.vue";
import {ref} from "vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router, Link} from "@inertiajs/vue3";

const props = defineProps({
    manufacturer: {
        type: Object,
        required: true
    }
})

const showAddEditManufacturerModal = ref(false)
const showDeleteModal = ref(false)

const deleteManufacturer = () => {
    router.delete(route('manufacturer.destroy', {manufacturer: props.manufacturer.id}))
}
</script>

<style scoped>

</style>