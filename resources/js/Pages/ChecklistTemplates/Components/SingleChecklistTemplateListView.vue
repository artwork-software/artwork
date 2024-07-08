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
                                   :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased capitalize']">
                                    <IconEdit stroke-width="1.5"
                                              class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                              aria-hidden="true"/>
                                    {{ $t('edit')}}
                                </a>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <a href="#" @click="duplicateTemplate"
                                   :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    <IconCopy stroke-width="1.5"
                                              class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                              aria-hidden="true"/>
                                    {{$t('Duplicate')}}
                                </a>
                            </MenuItem>
                            <MenuItem v-slot="{ active }">
                                <a href="#" @click="openDeleteTemplateModal"
                                   :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                    <IconTrash stroke-width="1.5"
                                               class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
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
</template>

<script setup>

import {Link, router, useForm} from "@inertiajs/vue3";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {MenuItem} from "@headlessui/vue";
import { IconEdit, IconCopy, IconTrash } from "@tabler/icons-vue";

const props = defineProps({
    checklist_template: {
        type: Object,
        required: true
    },
})

const duplicateTemplate = () => {
    router.post(route('checklist_templates.duplicate', { checklistTemplate: props.checklist_template.id }))
}

</script>

<style scoped>

</style>