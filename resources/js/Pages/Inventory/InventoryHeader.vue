<template>
    <app-layout :title="title + '-' + $t('Inventory Management')">
        <div class="mt-5 inventory-management-container">
            <div class="headline-container">
                <h2>{{ $t('Inventory Management') }}</h2>
                <div>
                    {{ $t('Maintain the inventory of all projects for your artwork.') }}
                </div>
            </div>
            <InventoryTabs class="inventory-tabs"/>
            <div class="inventory-content-container">
                <slot></slot>
            </div>
        </div>
    </app-layout>
</template>

<script>
import {defineComponent} from "vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import InventoryTabs from "@/Pages/Inventory/InventoryTabs.vue";

export default defineComponent({
    props: [
        'title'
    ],
    components: {
        InventoryTabs,
        AppLayout
    }
})
</script>
