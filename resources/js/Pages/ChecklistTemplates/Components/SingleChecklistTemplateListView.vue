<template>
    <div class="bg-gray-100 px-5 py-2 rounded-lg xxsLight mb-2 hover:bg-gray-200 transition-all duration-300 ease-in-out group relative">
        <div class="grid grid-cols-12 grid-rows-1 gap-4">
            <Link class="col-span-8 flex items-center" :href="route('checklist_templates.edit', {checklist_template: checklist_template.id})">
                <div class="">
                    <div class="flex items-center headline3">{{ checklist_template.name }}</div>
                    <div>
                        <p>{{ checklist_template.task_templates?.length ?? 0 }} {{ $t('Tasks') }}</p>
                    </div>
                </div>

            </Link>
            <div class="col-span-4 col-start-9 flex items-center">
                <div class="flex w-full items-center justify-between">
                    <div class="flex items-center gap-x-3">
                        <UserPopoverTooltip :user="checklist_template.user" height="8" width="8"/>
                        <div>
                            {{ checklist_template.created_at }}
                        </div>
                    </div>
                    <div class="flex justify-end items-center">
                        <BaseMenu class="hidden group-hover:block" no-relative>
                            <MenuItem v-slot="{ active }">
                                <a :href="route('checklist_templates.edit', {checklist_template: checklist_template.id})"
                                   :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased capitalize']">
                                    <IconEdit stroke-width="1.5"
                                              class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                              aria-hidden="true"/>
                                    {{ $t('Edit')}}
                                </a>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <a href="#" @click="duplicateTemplate"
                                   :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    <IconCopy stroke-width="1.5"
                                              class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                              aria-hidden="true"/>
                                    {{$t('Duplicate')}}
                                </a>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <a href="#" @click="showDeleteTemplateModal = true"
                                   :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    <IconTrash stroke-width="1.5"
                                               class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                               aria-hidden="true"/>
                                    {{ $t('Delete') }}
                                </a>
                            </MenuItem>
                        </BaseMenu>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Delete Project Modal -->
    <BaseModal @closed="showDeleteTemplateModal = false" v-if="showDeleteTemplateModal" modal-image="/Svgs/Overlays/illu_warning.svg">
        <div class="mx-4">
            <div class="headline1 my-2">
                {{$t('Delete checklist template')}}
            </div>
            <div class="errorText">
                {{ $t('Are you sure you want to delete the checklist template {0}?', [checklist_template.name])}}
            </div>
            <div class="flex justify-between mt-6">
                <FormButton
                    @click="deleteTemplate"
                    :text=" $t('Delete')"
                >
                </FormButton>
                <div class="flex my-auto">
                            <span @click="showDeleteTemplateModal = false"
                                  class="xsLight cursor-pointer">{{ $t('No, not really')}}</span>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>

import {Link, router} from "@inertiajs/vue3";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {MenuItem} from "@headlessui/vue";
import {IconCopy, IconEdit, IconTrash} from "@tabler/icons-vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import {ref} from "vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const props = defineProps({
    checklist_template: {
        type: Object,
        required: true
    },
})

const duplicateTemplate = () => {
    router.post(route('checklist_templates.duplicate', { checklistTemplate: props.checklist_template.id }))
}

const showDeleteTemplateModal = ref(false)


const deleteTemplate = () => {
    router.delete(route('checklist_templates.destroy', { checklist_template: props.checklist_template.id }))
    showDeleteTemplateModal.value = false
}
</script>

<style scoped>

</style>
