<template>
    <PlaceholderInputLabelContainer :noMarginTop="noMarginTop">
        <textarea :id="this.id"
                   :value="this.modelValue"
                   @input="this.$emit('update:modelValue', $event.target.value)"
                   type="text"
                   :required="required"
                   class="input peer"
                   placeholder="placeholder"
                  :rows="this.rows"
                  :cols="this.cols"
                  :maxlength="maxLength"
                  v-bind="$attrs"
        />

        <PlaceholderLabel :is-dark="isDark" :for="this.id" :label="this.label" v-if="showLabel" :is-small="isSmall"/>
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
            type: String,
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
        rows: {
            type: Number,
            default: 3
        },
        cols: {
            type: Number,
            default: 30
        },
        isDark: {
            type: Boolean,
            default: false
        },
        maxLength: {
            type: Number,
            default: 255,
            required: false
        },
        showLabel: {
            type: Boolean,
            default: true
        },
        noMarginTop: {
            type: Boolean,
            default: false
        }
    },
    emits: [
        'update:modelValue'
    ]
});
</script>
