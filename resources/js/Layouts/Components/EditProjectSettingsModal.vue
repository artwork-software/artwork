<script>
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

export default {
    name: "EditProjectSettingsModal",
    components: {FormButton, Input, ColorPickerComponent, BaseModal},
    props: {
        item: Object,
        title: String,
        description: String,
    },
    data(){
        return {
            itemCopy: {...this.item},
        }
    },
    emits: ['close', 'update'],
    methods: {
        UpdateColor(color){
            this.itemCopy.color = color
        },
        update(){
            this.$emit('update', this.itemCopy);
            this.$emit('close')
        },
    }
}
</script>

<template>
    <BaseModal @closed="$emit('close')">
        <div class="font-black font-lexend text-primary text-3xl my-2 mb-6">
            {{ title }}
        </div>
        <p class="text-artwork-buttons-context subpixel-antialiased">{{ description }}</p>

        <div class="my-5 flex items-center w-full">
            <ColorPickerComponent class="w-fit" @update-color="UpdateColor" :color="itemCopy.color"  />
            <input type="text" v-model="itemCopy.name" class="h-12 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300" />
        </div>
        <div class="flex justify-between mt-6">
            <FormButton :text="$t('Save')" @click="update" />
            <div class="flex my-auto">
                <span @click="$emit('close')" class="xsLight cursor-pointer">{{ $t('No, not really') }}</span>
            </div>
        </div>
    </BaseModal>
</template>

<style scoped>

</style>
