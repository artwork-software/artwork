<template>
    <Switch v-if="!roomMode" v-model="enableMultiEdit" @update:model-value="$emit('update:multiEdit', enableMultiEdit)" :class="[enableMultiEdit ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-6 w-14 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
        <span :class="[enableMultiEdit ? 'translate-x-7' : 'translate-x-0', 'pointer-events-none relative inline-block h-8 w-8 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
            <span :class="[enableMultiEdit ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                <IconPencil stroke-width="1.5" class="w-5 h-5" />
            </span>
            <span :class="[enableMultiEdit ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                <IconPencil stroke-width="1.5" class="w-5 h-5" />
            </span>
        </span>
    </Switch>
</template>

<script setup>

import {IconPencil} from "@tabler/icons-vue";
import {Switch} from "@headlessui/vue";
import {ref, watch} from "vue";

const props = defineProps({
    multiEdit: {
        type: Boolean,
        required: true
    },
    roomMode: {
        type: Boolean,
        default: false,
    }
})

const emit = defineEmits(['update:multiEdit'])
const enableMultiEdit = ref(props.multiEdit)


// add watch on props.multiEdit
watch(() => props.multiEdit, (value) => {
    enableMultiEdit.value = value
})
</script>

<style scoped>

</style>