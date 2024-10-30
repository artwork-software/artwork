<template>

    <PlaceholderInputLabelContainer :no-margin-top="noMarginTop">
        <input :id="this.id"
               :value="this.modelValue"
               @input="this.$emit('update:modelValue', $event.target.value)"
               :type="modelValue ? 'time' : 'text'"
               onfocus="(this.type='time', this.value === '' ? this.value='00:00' : '')"
               :required="required"
               class="input peer"
               :class="[isSmall ? '' : 'h-12', classes]"
               placeholder="placeholder"/>
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
