<template>
    <div class="button glassy group relative overflow-hidden">
        <!-- If left is true, text will be on the left side -->
        <p v-if="left" class="max-w-0 overflow-hidden opacity-0 transform -translate-x-2 transition-all duration-300 ease-in-out group-hover:max-w-xs group-hover:opacity-100 group-hover:translate-x-0 whitespace-nowrap group-hover:px-2 group-hover:text-artwork-buttons-create">
            {{ $t(text) }}
        </p>
        <span>
            <component :is="icon" class="group-hover:text-artwork-buttons-create" :class="[iconSize]" />
        </span>
        <p v-if="!left" class="max-w-0 overflow-hidden opacity-0 transform -translate-x-2 transition-all duration-300 ease-in-out group-hover:max-w-xs group-hover:opacity-100 group-hover:translate-x-0 whitespace-nowrap group-hover:px-2 group-hover:text-artwork-buttons-create">
            {{ $t(text) }}
        </p>
    </div>

</template>

<script setup lang="ts">

import {IconPlus} from "@tabler/icons-vue";

const props = defineProps({
    icon: {
        // erlaubt String oder echte Komponente (Function/Object)
        type: [String, Function, Object] as PropType<string | Component>,
        default: IconPlus,
    },
    iconSize: {
        type: String,
        default: 'size-5'
    },
    text: {
        type: String,
        required: true
    },
    left: {
        type: Boolean,
        default: false
    }
})

</script>

<style scoped>

</style>