<template>
    <BaseModal @closed="$emit('closeModal')" v-if="show" modal-image="/Svgs/Overlays/illu_project_edit.svg">
            <div class="mx-4">
                <div class="font-bold text-text text-2xl my-2">
                    {{ title }}
                </div>
                <IconX @click="emit('closeModal')"
                       class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-text-subtle absolute cursor-pointer"
                       aria-hidden="true"/>

                <div class="text-text-subtle subpixel-antialiased">
                    {{ description }}
                </div>

                <input :placeholder="$t('Enter a new name for the heading')"
                       id="title"
                       v-model="newName"
                       class="mt-4 p-4 border border-border resize-none w-full text-sm/5 font-semibold text-text placeholder:text-sm/5 font-bold text-text-subtle placeholder:subpixel-antialiased
                        focus:outline-none focus:ring-0 focus:border-text-subtle focus:border-1 w-full border-border"
                />

                <div class="flex justify-between mt-6">
                    <FormButton @click="emit('update', newName)" :text="$t('Save')"/>
                    <div class="flex my-auto">
                            <span @click="emit('closeModal')"
                                  class="text-sm/5 font-bold text-text-subtle cursor-pointer">{{$t('No, not really')}}</span>
                    </div>
                </div>
            </div>
    </BaseModal>
</template>

<script setup>
import {IconX} from "@tabler/icons-vue";
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
