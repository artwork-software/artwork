<template>
    <app-layout :title="this.title + '-' + this.$t('Inventory Management')">
        <div class="w-full pl-14">
            <div class="mb-5">
                <h2 class="headline1 mb-2">{{ this.$t('Inventory Management') }}</h2>
                <div class="headline3Light">
                    {{ this.$t('Maintain the inventory of all projects for your artwork.') }}
                </div>
            </div>
            <InventoryTabs class="mb-5"/>
            <div class="flex flex-col w-full h-full">
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
