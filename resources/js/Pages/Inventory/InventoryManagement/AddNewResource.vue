<template>
    <tr class="add-new-row group">
        <td class="add-new-td" :colspan="colspan">
            <div class="container group-hover:!border-t-2 group-hover:border-artwork-buttons-create">
                <div class="absolute-container group-hover:!flex">
                    <div>{{ text }}</div>
                    <IconCirclePlus stroke-width="1.5" class="icon"/>
                </div>
            </div>
        </td>
    </tr>
</template>

<script setup>
import {IconCirclePlus} from "@tabler/icons-vue";

defineProps({
    text: {
        type: String,
        required: true
    },
    colspan: {
        type: Number,
        required: true
    }
})
</script>
