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

        <div class="card white p-5 mt-4">
            <h3 class="font-lexend font-semibold text-primary mb-4">
                {{ $t('Inventory number display') }}
            </h3>
            <div class="space-y-6">
                <div>
                    <BaseCheckbox
                        v-model="displayForm.inventory_show_inventory_number_as_name"
                        :label="$t('Show inventory number as name')"
                        :description="$t('If active, detailed articles display the inventory number instead of the name.')"
                        @change="saveDisplaySettings"
                    />
                </div>
                <div>
                    <BaseInput
                        id="inventory_number_prefix"
                        v-model="displayForm.inventory_number_prefix"
                        :label="$t('Inventory number prefix')"
                        :placeholder="$t('e.g. INV-')"
                        @change="saveDisplaySettings"
                    />
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $t('A prefix displayed before all inventory numbers (e.g. INV-). Does not change stored data.') }}
                    </p>
                </div>
            </div>
        </div>
    </InventorySettingsHeader>
</template>

<script setup>

import InventorySettingsHeader from "@/Pages/InventorySetting/Components/InventorySettingsHeader.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    inventoryDetailedArticlesAlwaysQuantityOne: {
        type: Boolean,
        default: false
    },
    inventoryShowInventoryNumberAsName: {
        type: Boolean,
        default: false
    },
    inventoryNumberPrefix: {
        type: String,
        default: ''
    }
})

const form = useForm({
    enabled: props.inventoryDetailedArticlesAlwaysQuantityOne
})

const displayForm = useForm({
    inventory_show_inventory_number_as_name: props.inventoryShowInventoryNumberAsName,
    inventory_number_prefix: props.inventoryNumberPrefix
})

const save = (value) => {
    form.enabled = value
    form.patch(route('inventory-management.settings.general.update-detailed-articles-always-quantity-one'), {
        preserveScroll: true
    })
}

const saveDisplaySettings = () => {
    displayForm.patch(route('inventory-management.settings.general.update-inventory-display-settings'), {
        preserveScroll: true
    })
}
</script>
