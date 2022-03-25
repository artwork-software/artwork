<template>
    <app-layout title="Dashboard">
        <div class="py-12">
            <div class="max-w-screen-2xl my-12 flex flex-row mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-1 flex-wrap justify-between">
                    <div class="flex">
                    <div class="w-full flex my-auto">
                    <h2 class="text-2xl">Alle User</h2>
                        <button  @click="addUser" type="button" class="flex my-auto ml-6 pr-2 items-center font-bold p-1 border border-transparent rounded-full shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <PlusSmIcon class="h-5 w-5 " aria-hidden="true" /> User hinzufügen
                        </button>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="inset-y-0 mr-3 pointer-events-none">
                        <SearchIcon class="h-5 w-5" aria-hidden="true" />
                    </div>
                </div>
                    <ul role="list" class="mt-4 divide-y divide-gray-200 w-full">
                        <li v-for="person in users.data" :key="person.email" class="py-4 flex justify-between">
                            <img class="h-14 w-14 rounded-full flex justify-start" :src="person.image" alt="" />
                            <div class="ml-3 my-auto w-full justify-start mr-6">
                                <div class="flex my-auto">
                                <p class="text-md font-medium text-gray-900">{{ person.name }},</p><p class="ml-1 text-sm font-medium text-gray-900 my-auto"> {{person.business}}, {{person.position}}</p>
                                </div>
                                <div class="flex">
                                <p class="text-sm text-gray-500">{{person.phone_number}}, </p><p class="ml-2 text-sm text-gray-500">{{ person.email }}</p>
                                </div>
                            </div>
                            <Menu as="div" class="relative inline-block text-left">
                                <div>
                                    <MenuButton class=" mr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-100 focus:ring-indigo-500">
                                        <span class="sr-only">Open options</span>
                                        <DotsVerticalIcon class="h-6 w-6" aria-hidden="true" />
                                    </MenuButton>
                                </div>

                                <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems class="origin-top-right absolute right-0 mt-2 w-56 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none">
                                        <div class="py-1">
                                            <MenuItem v-slot="{ active }">
                                                <a href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">Bearbeiten</a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a href="#" :class="[active ? 'bg-gray-100 text-gray-900' : 'text-gray-700', 'block px-4 py-2 text-sm']">Löschen</a>
                                            </MenuItem>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>

                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";

const people = [
    {
        name: 'Calvin Hawkins',
        email: 'calvin.hawkins@example.com',
        business: "Kampnagel",
        position: "Chef",
        phone_number: "0170 5265985",
        image:
            'https://images.unsplash.com/photo-1491528323818-fdd1faba62cc?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
    },
    {
        name: 'Kristen Ramos',
        email: 'kristen.ramos@example.com',
        business: "Kampnagel",
        position: "Chef",
        phone_number: "0170 5265985",
        image:
            'https://images.unsplash.com/photo-1550525811-e5869dd03032?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
    },
    {
        name: 'Ted Fox',
        email: 'ted@examaple.com',
        business: "Kampnagel",
        position: "Chef",
        phone_number: "0170 5265985",
        image:
            'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
    },
]
import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {DotsVerticalIcon} from '@heroicons/vue/outline'
import{PlusSmIcon} from '@heroicons/vue/solid'
import {SearchIcon} from "@heroicons/vue/outline";


export default defineComponent({
    components: {
        AppLayout,
        DotsVerticalIcon,
        PlusSmIcon,
        SearchIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
    },
    props: ['users'],
    methods:{
      addUser(){

      }
    },
    setup() {
        return {
            people,
        }
    }
})
</script>
