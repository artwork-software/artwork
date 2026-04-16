<template>
    <InventorySettingsHeader
        :title="$t('General')"
        :description="$t('Define global settings for inventory planning.')"
    >
        <div class="card white p-5">
            <div class="space-y-6">
                <div>
                    <BaseCheckbox
                        v-model="form.enabled"
                        :label="$t('Detailed articles always with quantity 1')"
                        :description="$t('If this setting is active, the quantity field for detailed articles of items marked as \'single inventory capable\' is hidden. Each detailed article then always has a quantity of 1 and the quantity cannot be adjusted. This is useful when the \'single inventory capable\' property is always used for items that are unique.')"
                        @change="save"
                    />
                </div>
            </div>
        </div>
    </InventorySettingsHeader>
</template>

<script setup>

import InventorySettingsHeader from "@/Pages/InventorySetting/Components/InventorySettingsHeader.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    inventoryDetailedArticlesAlwaysQuantityOne: {
        type: Boolean,
        default: false
    }
})

const form = useForm({
    enabled: props.inventoryDetailedArticlesAlwaysQuantityOne
})

const save = (value) => {
    form.enabled = value
    form.patch(route('inventory-management.settings.general.update-detailed-articles-always-quantity-one'), {
        preserveScroll: true
    })
}
</script>
