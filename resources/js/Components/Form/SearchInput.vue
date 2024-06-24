<script setup>

import { onMounted, ref } from 'vue';

defineEmits(['update:modelValue']);

const input = ref(null);

const props = defineProps({
    label: {
        type: String,
        required: false,
    },
    modelValue: {
        type: String,
        required: false,
    },
    type: {
        type: String,
        default: 'text',
    },
    required: {
        type: Boolean,
        default: false,
    },
    noLabel: {
        type: Boolean,
        default: true,
    },
    placeholder: {
        type: String,
        required: false,
    },
});


defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <div :class="noLabel ? 'mb-1' : ''">
        <label v-if="noLabel" :for="label" class="block text-xs font-medium leading-6 text-gray-900">
            {{ label }} <span v-if="required" class="text-red-600">*</span>
        </label>
        <div :class="noLabel ? 'mt-1' : ''">
            <input :type="type" :value="modelValue" :required="required" @input="$emit('update:modelValue', $event.target.value)" :name="label" :id="label" class="w-full h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 border-gray-300" :placeholder="placeholder" />
        </div>
    </div>
</template>

<style scoped>

</style>
