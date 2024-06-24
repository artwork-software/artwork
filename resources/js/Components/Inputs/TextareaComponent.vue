<template>
    <InputLabelContainer>
        <Label :for="this.id" :label="this.label"/>
        <textarea :id="this.id"
                  class="p-1.5 text-sm h-12 w-full focus:border-primary focus:ring-0 border-2 border-gray-300"
                  @input="this.$emit('update:modelValue', $event.target.value)"
                  :rows="this.rows"
                  :cols="this.cols"
                  :value="this.modelValue"/>
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
        'modelValue',
        'rows',
        'cols'
    ],
    emits: [
        'update:modelValue'
    ]
});
</script>
