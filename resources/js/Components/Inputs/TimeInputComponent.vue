<template>
    <div class="flex flex-col relative w-full" :class="noMarginTop || showLabel ? '' : 'mt-5'">
        <label v-if="showLabel"
               :for="this.id"
               class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">
            {{ this.label }}<span v-if="required" class="text-danger"> *</span>
        </label>
        <input :id="this.id"
               :value="this.modelValue"
               @input="this.$emit('update:modelValue', $event.target.value)"
               :type="modelValue ? 'time' : 'text'"
               onfocus="(this.type='time', this.value === '' ? this.value='00:00' : '')"
               :required="required"
               class="input"
               :class="[isSmall ? 'h-7 text-xs' : 'h-8', classes]"/>
    </div>
</template>

<script>
import {defineComponent} from "vue";

export default defineComponent({
    props: {
        id: {
            type: String,
            required: true
        },
        label: {
            type: String,
            required: true
        },
        noMarginTop: {
            type: Boolean,
            default: false
        },
        modelValue: {
            type: [String, null],
            required: true
        },
        isSmall: {
            type: Boolean,
            default: false
        },
        required: {
            type: Boolean,
            required: false,
            default: false
        },
        classes: {
            type: String,
            default: ''
        },
        isDark: {
            type: Boolean,
            default: false
        },
        showLabel: {
            type: Boolean,
            default: true
        }
    },
    emits: [
        'update:modelValue'
    ]
});
</script>
