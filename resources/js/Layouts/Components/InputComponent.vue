<template>
    <div>
        <input type="text"
               :placeholder="placeholder"
               @input="updateContent"
               v-model="modelValue"
               class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
    </div>
</template>

<script>
import {InformationCircleIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection";

export default {
    name: "Input",
    components: {},
    props: ['modelValue', 'placeholder'],
    emits:['update:modelValue'],
    data (){
        return{
           modelValue: this.modelValue
        }
    },
    methods: {
        updateContent(){
            this.$emit('update:modelValue', this.modelValue)
        }
    },

}
</script>

<style scoped>



</style>
