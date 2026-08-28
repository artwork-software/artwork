<template>
    <InventorySettingsHeader
        :title="$t('Categories')"
        :description="$t('Edit and create categories for your inventory.')"
    >
        <template #actions>
            <BaseUIButton variant="primary" hide-icon @click="showAddEditCategoryModal = true">
                <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                {{ $t('Create Category') }}
            </BaseUIButton>
        </template>

        <SettingsGuideBanner
            class="mb-4"
            storage-key="settings-guide.inventory.categories"
            title="How does this area work?"
            :paragraphs="[
                'Categories form the tree structure of the inventory sidebar. At the same time they define the field schema of the articles: the properties assigned to a category become the fields of its articles.',
                'Caution: deleting a category also irrevocably deletes all articles in it.',
            ]"
        />
        <div class="mb-10 rounded-lg bg-surface border border-border-subtle w-full shadow-raised p-5">

            <div class="my-8 overflow-x-auto">
                <table class="min-w-full divide-y divide-border">
                    <thead>
                    <tr class="divide-x divide-border-subtle">
                        <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-text sm:pl-0">Name</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Sub-Categories') }}</th>
                        <th scope="col" class="px-4 py-3.5 text-left text-sm font-semibold text-text">{{ $t('Properties') }}</th>
                        <th scope="col" class="py-3.5 pr-4 pl-4 text-left text-sm font-semibold text-text sm:pr-0 sticky right-0 bg-surface">{{ $t('Actions') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle bg-white">
                    <tr v-for="category in categories.data" :key="category?.id" class="divide-x divide-border-subtle">
                        <SingleCategoryInSettings :category="category" :properties="properties" :rooms="rooms" :manufacturers="manufacturers" />
                    </tr>
                    </tbody>
                </table>
            </div>

            <BasePaginator
                property-name="categories"
                :entities="categories"
            />

            <AddEditCategoryModal
                :category="null"
                :properties="properties"
                :rooms="rooms"
                :manufacturers="manufacturers"
                v-if="showAddEditCategoryModal"
                @close="showAddEditCategoryModal = false"
            />

        </div>
    </InventorySettingsHeader>
</template>

<script setup>

import InventorySettingsHeader from "@/Pages/InventorySetting/Components/InventorySettingsHeader.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";
import AddEditCategoryModal from "@/Pages/InventorySetting/Components/AddEditCategoryModal.vue";
import {ref} from "vue";
import SingleCategoryInSettings from "@/Pages/InventorySetting/Components/SingleCategoryInSettings.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";
import {IconCirclePlus} from "@tabler/icons-vue";

const props = defineProps({
    categories: {
        type: Object,
        required: true
    },
    properties: {
        type: Object,
        required: true
    },
    rooms: {
        type: Object,
        required: true
    },
    manufacturers: {
        type: Object,
        required: true
    }
})

const showAddEditCategoryModal = ref(false)

</script>

<style scoped>
</style>
