<template>
    <Switch v-if="!roomMode" v-model="enableMultiEdit" @update:model-value="$emit('update:multiEdit', enableMultiEdit)" :class="[enableMultiEdit ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none z-50']">
        <span :class="[enableMultiEdit ? 'translate-x-5' : 'translate-x-0', 'relative inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
            <span :class="[enableMultiEdit ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in z-50', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                <ToolTipComponent
                    direction="bottom"
                    :tooltip-text="$t('Edit')"
                    icon="IconPencil"
                    icon-size="h-4 w-4"
                />
            </span>
            <span :class="[enableMultiEdit ? 'opacity-100 duration-200 ease-in z-50' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                <ToolTipComponent
                    direction="bottom"
                    :tooltip-text="$t('Edit')"
                    icon="IconPencil"
                    icon-size="h-4 w-4"
                />
            </span>
        </span>
    </Switch>
</template>

<script setup>

import {IconPencil} from "@tabler/icons-vue";
import {Switch} from "@headlessui/vue";
import {ref, watch} from "vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";

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
