<template>
    <div class="grid w-full grid-cols-[1fr_auto] items-start gap-4">
        <!-- Left: Info -->
        <div class="min-w-0">
            <div class="flex items-start justify-between gap-x-10">
                <p class="truncate text-base font-semibold text-zinc-900">
                    {{ manufacturer.name }}
                </p>
            </div>

            <div class="mt-1 space-y-1 text-xs text-zinc-500">
                <p class="truncate">{{ manufacturer.address }}</p>
                <p class="truncate">{{ manufacturer.contact_person }}</p>
                <p class="truncate">{{ manufacturer.phone }}</p>
                <p class="truncate">{{ manufacturer.email }}</p>
                <p class="truncate">
                    {{ $t('Customer Number') }}:
                    <span class="font-medium text-zinc-700">{{ manufacturer.customer_number }}</span>
                </p>
            </div>
        </div>

        <!-- Right: Actions -->
        <div class="flex flex-none items-center gap-2">
            <a
                v-if="manufacturer.website"
                :href="manufacturer.website"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center rounded-md px-2.5 py-1.5 text-xs font-medium
               text-blue-600 ring-1 ring-inset ring-blue-200 hover:bg-blue-50 transition"
            >
                {{ $t('Manufacturer website') }}
            </a>

            <BaseMenu has-no-offset white-menu-background>
                <BaseMenuItem white-menu-background
                    @click="showAddEditManufacturerModal = true"
                    :icon="IconEdit"
                    :title="$t('Edit Manufacturer')"
                />
                <BaseMenuItem white-menu-background
                    :icon="IconTrash"
                    :title="$t('Delete Manufacturer')"
                    @click="showDeleteModal = true"
                />
            </BaseMenu>
        </div>
    </div>

    <!-- Modals -->
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
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseMenuItem from '@/Components/Menu/BaseMenuItem.vue'
import ConfirmDeleteModal from '@/Layouts/Components/ConfirmDeleteModal.vue'
import CreateOrUpdateManufacturerModal from '@/Pages/Manufacturer/Components/Modals/CreateOrUpdateManufacturerModal.vue'
import { IconEdit, IconTrash } from '@tabler/icons-vue'

const props = defineProps({
    manufacturer: {
        type: Object,
        required: true,
    },
})

const showAddEditManufacturerModal = ref(false)
const showDeleteModal = ref(false)

const deleteManufacturer = () => {
    router.delete(route('manufacturer.destroy', { manufacturer: props.manufacturer.id }))
}
</script>

<style scoped></style>
