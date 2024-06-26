<template>
    <tr class="w-full flex flex-row absolute justify-center -translate-y-[2px] group h-0.5">
        <td class="w-full flex flex-row justify-center">
            <div class="w-[99%] h-0.5 cursor-pointer flex flex-row justify-center border-dashed group-hover:border-t-2 group-hover:border-artwork-buttons-create relative">
                <div class="absolute -top-[39px] hidden group-hover:flex flex-col gap-y-2 items-center uppercase text-xs subpixel-antialiased">
                    <div class="flex flex-row px-2 rounded-2xl bg-primary border border-white text-white">Neue Kategorie hinzufügen</div>
                    <IconCirclePlus stroke-width="1.5" class="h-6 w-6 text-white bg-artwork-buttons-create rounded-full" />
                </div>
            </div>
        </td>
    </tr>
</template>

<script setup>
import {IconCirclePlus} from "@tabler/icons-vue";
</script>
