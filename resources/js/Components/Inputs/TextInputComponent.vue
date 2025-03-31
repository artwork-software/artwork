<template>
    <PlaceholderInputLabelContainer :noMarginTop="noMarginTop">
        <input class="input peer"
               placeholder="placeholder"
               :id="this.id"
               :value="this.modelValue"
               :type="type"
               :required="required"
               :class="isSmall ? '' : 'h-12'"
               :disabled="disabled"
               :readonly="readonly"
               :maxlength="maxlength"
               @input="this.$emit('update:modelValue', $event.target.value)"
        />
        <div v-if="this.modelValue?.length > 0" class="absolute right-1 top-0 bottom-0 flex items-center pr-2">
            <button @click="this.$emit('update:modelValue', '')" class="text-gray-500 hover:text-artwork-buttons-create">
                <component is="IconX" class="size-4" />
            </button>
        </div>
        <PlaceholderLabel :for="this.id" :label="this.label" v-if="showLabel" :is-small="isSmall"/>
    </PlaceholderInputLabelContainer>
</template>

<script>
import {defineComponent} from "vue";
import PlaceholderInputLabelContainer from "@/Components/Inputs/Container/PlaceholderInputLabelContainer.vue";
import PlaceholderLabel from "@/Components/Inputs/Labels/PlaceholderLabel.vue";

export default defineComponent({
    components: {PlaceholderLabel, PlaceholderInputLabelContainer},
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
