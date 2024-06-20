<template>
    <BaseModal @closed="$emit('closeModal')" v-if="show" modal-image="/Svgs/Overlays/illu_project_edit.svg">
            <div class="mx-4">
                <div class="font-bold text-primary text-2xl my-2">
                    {{ title }}
                </div>
                <XIcon @click="emit('closeModal')"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                       aria-hidden="true"/>

                <div class="text-secondary subpixel-antialiased">
                    {{ description }}
                </div>

                <input :placeholder="$t('Enter a new name for the heading')"
                       id="title"
                       v-model="newName"
                       class="mt-4 p-4 inputMain resize-none w-full xsDark placeholder:xsLight placeholder:subpixel-antialiased
                        focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"
                />

                <div class="flex justify-between mt-6">
                    <FormButton @click="emit('update', newName)" :text="$t('Save')"/>
                    <div class="flex my-auto">
                            <span @click="emit('closeModal')"
                                  class="xsLight cursor-pointer">{{$t('No, not really')}}</span>
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script setup>
import {XIcon} from "@heroicons/vue/outline"
import {ref} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

const props = defineProps({
    show: Boolean,
    title: String,
    description: String
})

const newName = ref('')

const emit = defineEmits(['update', 'closeModal'])
</script>

<style scoped>

</style>
