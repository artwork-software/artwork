<template>

    <PlaceholderInputLabelContainer>
        <input :id="this.id"
               :value="this.modelValue"
               @input="this.$emit('update:modelValue', $event.target.value)"
               :type="modelValue ? 'time' : 'text'"
               onfocus="(this.type='time')"
               :required="required"
               class="input peer"
               :class="[isSmall ? '' : 'h-12', classes]"
               placeholder="placeholder"/>
        <PlaceholderLabel :for="this.id" :label="this.label" :is-small="isSmall"/>
    </PlaceholderInputLabelContainer>


    <!--<InputLabelContainer>
        <Label :for="this.id" :label="this.label"/>
        <input :id="this.id"
               :value="this.modelValue"
               @input="this.$emit('update:modelValue', $event.target.value)"
               type="time"
               class="p-1.5 text-sm  w-full focus:border-primary focus:ring-0 border-2 border-gray-300"
                :class="isSmall ? '' : 'h-12'"
        />
    </InputLabelContainer>-->
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
            required: false,
            default: false
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
