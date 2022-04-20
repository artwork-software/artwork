<template>
    <app-layout title="Checklisten Management">
        <div class="max-w-screen-lg my-12 ml-20 mr-40">
            <div class="flex flex-1 flex-wrap">
                <div class="w-full flex my-auto justify-between">
                    <div class="flex">
                        <h2 class="text-2xl flex">Checklistenvorlagen</h2>
                        <button @click="openAddChecklistTemplateModal" type="button"
                                class="flex my-auto ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                            <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                        </button>
                        <div v-if="$page.props.can.show_hints" class="flex mt-1">
                            <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                            <span
                                class="font-nanum text-secondary tracking-tight ml-1 my-auto tracking-tight text-lg">Bearbeite deine Vorlage</span>
                        </div>
                    </div>
                    <div class="flex items-center">

                        <div class="inset-y-0 mr-3 pointer-events-none">
                            <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                        </div>
                    </div>
                    <ul role="list" class="mt-6 w-full">
                        <li v-for="(template,index) in checklistTemplates.data" :key="template.email"
                            class="py-6 flex justify-between">
                            <div class="flex">
                                <div class="ml-3 my-auto w-full justify-start mr-6">
                                    <div class="flex my-auto">
                                        <p class="text-lg mr-3 font-semibold subpixel-antialiased text-primary">
                                            {{ template.name }} </p>
                                        <p class="ml-1 text-sm font-medium text-primary my-auto"> angelegt am
                                            {{ template.created_at }} von
                                            <img class="h-6 w-6 rounded-full flex justify-start"
                                                 :src="template.last_editor.profile_photo_url"
                                                 alt=""/>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex">
                                <div class="flex mr-8 items-center">
                                    <Menu as="div" class="my-auto relative">
                                        <div class="flex">
                                            <MenuButton
                                                class="flex">
                                                <DotsVerticalIcon
                                                    class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                    aria-hidden="true"/>
                                            </MenuButton>
                                            <div v-if="$page.props.can.show_hints && index === 0"
                                                 class="absolute flex w-40 ml-6">
                                                <div>
                                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-1"/>
                                                </div>
                                                <div class="flex">
                                                    <span
                                                        class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite einen Nutzer</span>
                                                </div>
                                            </div>
                                        </div>
                                        <transition enter-active-class="transition ease-out duration-100"
                                                    enter-from-class="transform opacity-0 scale-95"
                                                    enter-to-class="transform opacity-100 scale-100"
                                                    leave-active-class="transition ease-in duration-75"
                                                    leave-from-class="transform opacity-100 scale-100"
                                                    leave-to-class="transform opacity-0 scale-95">
                                            <MenuItems
                                                class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                <div class="py-1">
                                                    <MenuItem v-slot="{ active }">
                                                        <a :href="getEditHref(project)"
                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <PencilAltIcon
                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                aria-hidden="true"/>
                                                            Bearbeiten
                                                        </a>
                                                    </MenuItem>
                                                    <MenuItem v-slot="{ active }">
                                                        <a href="#" @click="duplicateProject(project)"
                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <DuplicateIcon
                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                aria-hidden="true"/>
                                                            Duplizieren
                                                        </a>
                                                    </MenuItem>
                                                    <MenuItem v-slot="{ active }">
                                                        <a href="#" @click="openDeleteProjectModal(project)"
                                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                            <TrashIcon
                                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                aria-hidden="true"/>
                                                            Löschen
                                                        </a>
                                                    </MenuItem>
                                                </div>
                                            </MenuItems>
                                        </transition>
                                    </Menu>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>

import {Inertia} from "@inertiajs/inertia";
import {SearchIcon,DotsVerticalIcon,PencilAltIcon,TrashIcon,DuplicateIcon} from "@heroicons/vue/outline";
import {PlusSmIcon }from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import AppLayout from '@/Layouts/AppLayout.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";

export default {
    name: "Checklist Create",
    props: ['checklistTemplates'],
    components: {
        PlusSmIcon,
        SvgCollection,
        AppLayout,
        SearchIcon,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem,
        TrashIcon,
        DotsVerticalIcon,
        PencilAltIcon,
        DuplicateIcon
    },
    data() {
        return {}
    },
    methods: {},
    setup() {
        return {}
    }
}
</script>

<style scoped>

</style>
