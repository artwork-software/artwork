<template>
    <PlaceholderInputLabelContainer :no-margin-top="noMarginTop">
        <input :id="this.id"
               :value="this.modelValue"
               @input="this.$emit('update:modelValue', $event.target.value)"
               :type="modelValue ? typeString : 'text'"
               @focus="(event) => event.target.type = typeString"
               :required="required"
               class="input peer"
               :class="[isSmall ? '' : 'h-12', classes]"
               :disabled="disabled"
               placeholder="placeholder"
               min="2020-01-01"
        />
        <PlaceholderLabel :for="this.id" :label="this.label" :is-small="isSmall" :is-dark="isDark" v-if="showLabel"/>
    </PlaceholderInputLabelContainer>
</template>

<script>
import {defineComponent} from "vue";
import InputLabelContainer from "@/Components/Inputs/Container/InputLabelContainer.vue";
import Label from "@/Components/Inputs/Labels/Label.vue";
import PlaceholderLabel from "@/Components/Inputs/Labels/PlaceholderLabel.vue";
import PlaceholderInputLabelContainer from "@/Components/Inputs/Container/PlaceholderInputLabelContainer.vue";

export default defineComponent({
    components: {PlaceholderInputLabelContainer, PlaceholderLabel, Label, InputLabelContainer},
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
        noMarginTop: {
            type: Boolean,
            default: false
        },
        required: {
            type: Boolean,
            required: false,
            default: false
        },
        isSmall: {
            type: Boolean,
            default: false
        },
        classes: {
            type: String,
            default: ''
        },
        disabled: {
            type: Boolean,
            default: false
        },
        isDark: {
            type: Boolean,
            default: false
        },
        showLabel: {
            type: Boolean,
            default: true
        },
        typeString: {
            type: String,
            default: 'date'
        },
    },
    emits: [
        'update:modelValue'
    ]
});
</script>
