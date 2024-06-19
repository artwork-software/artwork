<template>
    <InputLabelContainer>
        <Label :for="this.id" :label="this.label"/>
        <input :id="this.id"
               :value="this.modelValue"
               @input="this.$emit('update:modelValue', $event.target.value)"
               type="time"
               class="p-1.5 text-sm h-12 w-full focus:border-primary focus:ring-0 border-2 border-gray-300"/>
    </InputLabelContainer>
</template>

<script>
import {defineComponent} from "vue";
import InputLabelContainer from "@/Components/Inputs/Container/InputLabelContainer.vue";
import Label from "@/Components/Inputs/Labels/Label.vue";

export default defineComponent({
    components: {Label, InputLabelContainer},
    props: [
        'id',
        'label',
        'modelValue'
    ],
    emits: [
        'update:modelValue'
    ]
});
</script>
