<template>
    <div class="w-full">
        <div v-if="items.length > 0" v-for="(item,index) in items" :key="item.id" class="flex items-center justify-between border-b-2 border-gray-200">
            <div class="py-5 flex items-center">
                <BaseFilterTag :filter="item" @remove-filter="forceDelete(item)" />
                <div class="ml-2">{{type}}</div>
            </div>
            <div class="w-1/12 flex justify-end">
                <Menu as="div" class="my-auto relative">
                    <div class="flex z-0">
                        <MenuButton
                            class="flex z-0 rounded-full p-2 text-tagText bg-tagBg">
                            <DotsVerticalIcon class="flex-shrink-0 h-6 w-6 text-gray-600 my-auto z-0" aria-hidden="true"/>
                        </MenuButton>
                    </div>
                    <transition enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                        <MenuItems
                            class="z-10 origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                            <div class="py-1">
                                <MenuItem v-slot="{ active }" class="z-10">
                                    <a @click="restoreItem(item)"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <RefreshIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        {{ $t('Restore')}}
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }" class="z-10">
                                    <a @click="forceDelete(item)"
                                       :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TrashIcon
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                            aria-hidden="true"/>
                                        {{ $t('Delete permanently')}}
                                    </a>
                                </MenuItem>
                            </div>
                        </MenuItems>
                    </transition>
                </Menu>

            </div>
        </div>
    </div>
</template>

<script>

import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {DotsVerticalIcon, TrashIcon} from "@heroicons/vue/outline";
import {RefreshIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";

export default {
    mixins: [Permissions],
    props: ['items', 'type', 'model'],
    components: {
        BaseFilterTag,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        DotsVerticalIcon,
        RefreshIcon,
        TrashIcon,
    },
    methods: {
        forceDelete(item){
            this.$inertia.delete(route(`${this.model}.force`, { id: item.id}))
        },
        restoreItem(item){
            this.$inertia.patch(route(`${this.model}.restore`, { id: item.id}))
        }

    }
}

</script>

<style scoped>

</style>
