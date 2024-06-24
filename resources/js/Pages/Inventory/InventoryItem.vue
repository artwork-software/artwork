<template>
    <tr class="h-16">
        <template v-for="(cell) in item.cells">
            <InventoryCell :cell="cell"/>
        </template>
    </tr>
</template>

<script setup>
import InventoryCell from "@/Pages/Inventory/InventoryCell.vue";

const props = defineProps({
    item: Object
});
</script>
