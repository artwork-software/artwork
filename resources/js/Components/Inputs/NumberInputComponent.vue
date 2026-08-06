<template>
    <div class="flex flex-col relative w-full">
        <label :for="this.id"
               class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">
            {{ this.label }}<span v-if="required" class="text-danger"> *</span>
        </label>
        <input :id="this.id"
               :value="this.modelValue"
               @input="this.$emit('update:modelValue', $event.target.value)"
               type="number"
               class="input"
               :class="[isSmall ? 'h-7 text-xs' : 'h-8', classes]"
               :min="min"
               :max="max"
               :maxlength="maxlength"
               :step="step"
               :required="required"/>
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
        modelValue: {
            type: [String, null, Number],
            required: true
        },
        isSmall: {
            type: Boolean,
            default: false
        },
        required: {
            type: Boolean,
            default: false
        },
        min: {
            type: Number,
            default: 0
        },
        max: {
            type: Number,
            default: 200
        },
        maxlength: {
            type: Number,
            default: 200
        },
        step: {
            type: Number,
            default: 1
        },
        classes: {
            type: String,
            default: ''
        }
    },
    emits: [
        'update:modelValue'
    ]
});
</script>
