<template>
    <div class="flex flex-col relative w-full" :class="noMarginTop || showLabel ? '' : 'mt-5'">
        <label v-if="showLabel"
               :for="this.id"
               class="mb-1 block font-lexend text-xs font-medium text-[#3F424A]">
            {{ this.label }}<span v-if="required" class="text-danger"> *</span>
        </label>
        <div class="relative">
            <input class="input"
                   :id="this.id"
                   :value="this.modelValue"
                   :type="type"
                   :required="required"
                   :class="[isSmall ? 'h-7 text-xs' : 'h-8', this.modelValue?.length > 0 ? 'pr-8' : '']"
                   :disabled="disabled"
                   :readonly="readonly"
                   :maxlength="maxlength"
                   @input="this.$emit('update:modelValue', $event.target.value)"
            />
            <div v-if="this.modelValue?.length > 0" class="absolute right-1 top-0 bottom-0 flex items-center pr-1">
                <button @click="this.$emit('update:modelValue', '')" class="text-text-subtle hover:text-danger">
                    <component :is="IconX" class="size-4" />
                </button>
            </div>
        </div>
    </div>
</template>

<script>
import {defineComponent} from "vue";
import {IconX} from "@tabler/icons-vue";

export default defineComponent({
    methods: {IconX},
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
            type: [String, null],
            required: true
        },
        isSmall: {
            type: Boolean,
            default: false
        },
        noMarginTop: {
            type: Boolean,
            default: false
        },
        required: {
            type: Boolean,
            default: false
        },
        type: {
            type: String,
            default: 'text'
        },
        disabled: {
            type: Boolean,
            default: false
        },
        readonly: {
            type: Boolean,
            default: false
        },
        maxlength: {
            type: Number,
            default: 255
        },
        showLabel: {
            type: Boolean,
            default: true
        },
    },
    emits: [
        'update:modelValue'
    ]
});
</script>
