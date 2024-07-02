<template>
    <tr class="add-new-category-row group">
        <td class="add-new-category-td">
            <div class="container group-hover:!border-t-2 group-hover:border-artwork-buttons-create">
                <div class="absolute-container group-hover:!flex">
                    <div>{{ $t('Add new category') }}</div>
                    <IconCirclePlus stroke-width="1.5" class="icon"/>
                </div>
            </div>
        </td>
    </tr>
</template>

<script setup>
import {IconCirclePlus} from "@tabler/icons-vue";
</script>
