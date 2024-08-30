<template>
    <PlaceholderInputLabelContainer :noMarginTop="noMarginTop">
        <input :id="this.id"
               :value="this.modelValue"
               @input="this.$emit('update:modelValue', $event.target.value)"
               :type="type"
               :required="required"
               class="input peer"
               :class="isSmall ? '' : 'h-12'"
               placeholder="placeholder"
               :disabled="disabled"
                :readonly="readonly"
               :maxlength="maxlength"
        />
        <PlaceholderLabel :for="this.id" :label="this.label" :is-small="isSmall"/>
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
            type: String,
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
    },
    emits: [
        'update:modelValue'
    ]
});
</script>
