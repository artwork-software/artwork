<template>
    <InventoryHeader :title="this.$t('Inventory')">
        Lorem Ipsum 1
    </InventoryHeader>
</template>

<script>

import {defineComponent} from "vue";
import InventoryHeader from "@/Pages/Inventory/InventoryHeader.vue";

export default defineComponent({
    components: {InventoryHeader},
})
</script>
