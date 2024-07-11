<template>
    <IconCirclePlus stroke-width="1.5" class="add-new-resource-icon" :title="text"/>
</template>

<script setup>
import {IconCirclePlus} from "@tabler/icons-vue";

defineProps({
    text: {
        type: String,
        required: true
    }
})
</script>
