<template>
    <div>
        <input type="text"
               :placeholder="placeholder"
               @input="updateContent"
               v-model="model"
               class="h-12 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
    </div>
</template>

<script>
import Permissions from "@/Mixins/Permissions.vue";

export default {
    name: "Input",
    mixins: [Permissions],
    components: {},
    props: ['modelValue', 'placeholder'],
    emits: ['update:modelValue'],
    data (){
        return {
           model: this.modelValue
        }
    },
    methods: {
        updateContent(){
            this.$emit('update:modelValue', this.model)
        }
    },
}
</script>

<style scoped>



</style>
