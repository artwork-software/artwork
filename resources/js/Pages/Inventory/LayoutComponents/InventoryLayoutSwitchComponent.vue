<template>
    <Switch v-model="newGridLayout" @update:modelValue="$emit('update:gridLayout', newGridLayout)" :class="[gridLayout ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:ring-2 focus:ring-artwork-buttons-create focus:ring-offset-2 focus:outline-hidden']">
        <span class="sr-only">Use setting</span>
        <span :class="[gridLayout ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none relative inline-block size-5 transform rounded-full bg-white ring-0 shadow-sm transition duration-200 ease-in-out']">
            <span :class="[gridLayout ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                <component :is="IconLayoutList" class="size-3"/>
            </span>
            <span
                :class="[gridLayout ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']"
                aria-hidden="true">
                <component :is="IconLayoutGrid" class="size-3"/>
            </span>
        </span>
    </Switch>
</template>

<script setup>

import {Switch} from "@headlessui/vue";
import {ref} from "vue";
import {IconLayoutGrid, IconLayoutList} from "@tabler/icons-vue";

const props = defineProps({
    gridLayout: {
        type: Boolean,
        required: true
    }
})

const emit = defineEmits(["update:gridLayout"]);

const newGridLayout = ref(props.gridLayout);
</script>

<style scoped>

</style>