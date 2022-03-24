<template>
    <app-layout title="Dashboard">
        <div class="py-12">
            <div class="max-w-screen-2xl my-12 flex flex-row justify-between mx-auto sm:px-6 lg:px-8">
                <div class="flex flex-wrap">
                    <div class="w-full">
                    <h2 class="text-2xl ml-4">Alle User</h2>
                    </div>
                    <ul role="list" class="mt-4 divide-y divide-gray-200 max-w-xl">
                        <li v-for="person in people" :key="person.email" class="py-4 flex justify-between">
                            <img class="h-14 w-14 rounded-full flex justify-start" :src="person.image" alt="" />
                            <div class="ml-3 my-auto w-full justify-start mr-6">
                                <div class="flex my-auto">
                                <p class="text-md font-medium text-gray-900">{{ person.name }},</p><p class="ml-1 text-sm font-medium text-gray-900 my-auto"> {{person.business}}, {{person.position}}</p>
                                </div>
                                <div class="flex">
                                <p class="text-sm text-gray-500">{{person.phone_number}}, </p><p class="ml-2 text-sm text-gray-500">{{ person.email }}</p>
                                </div>
                            </div>
                            <DotsVerticalIcon class="mr-3 justify-end flex-shrink-0 h-6 w-6 text-gray-600 my-auto" aria-hidden="true"/>
                        </li>
                    </ul>
                </div>
                <div class="flex justify-center">
                    Alle Abteilungen
                </div>


            </div>
        </div>
    </app-layout>
</template>

<script>
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


export default defineComponent({
    components: {
        AppLayout,
        DotsVerticalIcon
    },
    setup() {
        return {
            people
        }
    }
})
</script>
