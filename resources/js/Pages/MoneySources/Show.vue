<template>
    <app-layout>
        <div class="max-w-screen-2xl my-12 pl-20 pr-10 flex flex-row">
            <div class="flex w-8/12 flex-col">
                <div class="flex ">
                    <h2 class="flex font-black font-lexend text-primary tracking-wide text-3xl">
                        {{ moneySource.name }}</h2>
                    <Menu as="div" class="my-auto mt-3 relative"
                          v-if="this.$page.props.is_admin">
                        <div class="flex items-center -mt-1">
                            <MenuButton
                                class="flex ml-6">
                                <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                  aria-hidden="true"/>
                            </MenuButton>
                            <div v-if="$page.props.can.show_hints" class="absolute flex w-48 ml-12">
                                <div>
                                    <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                </div>
                                <div class="flex">
                                    <span class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite die Basisdaten</span>
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
                                class="origin-top-left absolute left-0 mr-4 mt-2 w-72 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                <div class="py-1">
                                    <MenuItem
                                        v-if="this.$page.props.is_admin"
                                        v-slot="{ active }">
                                        <a @click="openEditMoneySourceModal"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <PencilAltIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Basisdaten bearbeiten
                                        </a>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <a href="#" @click="duplicateMoneySource(this.moneySource)"
                                           :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                            <DuplicateIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            Duplizieren
                                        </a>
                                    </MenuItem>
                                    <MenuItem
                                        v-if="this.$page.props.is_admin"
                                        v-slot="{ active }">
                                        <a @click="openDeleteMoneySourceModal(this.moneySource)"
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
                <div class="flex items-center w-full justify-between mt-4">
                    <div class="mt-2 xsDark text-xs flex items-center"
                         v-if="moneySource['users']">
                        <div class="flex items-center">
                            zuständig:
                            <div v-for="user in moneySource['users']">
                                <img v-if="user"
                                     :data-tooltip-target="user?.id"
                                     :src="user?.profile_photo_url"
                                     :alt="user?.first_name"
                                     class="ml-3 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                <UserTooltip v-if="user?.changed_by"
                                             :user="user"/>
                            </div>
                        </div>
                    </div>
                    <div class="flex mt-2 mx-4 xsDark items-center">
                        erstellt von
                        <img v-if="moneySource.creator[0]"
                             :data-tooltip-target="moneySource.creator[0]?.id"
                             :src="moneySource.creator[0]?.profile_photo_url"
                             :alt="moneySource.creator[0]?.first_name"
                             class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                        <UserTooltip v-if="moneySource.creator[0]"
                                     :user="moneySource.creator[0]"/>
                    </div>
                    <button class="ml-4 mt-3 subpixel-antialiased flex items-center linkText cursor-pointer"
                            @click="openMoneySourceHistoryModal()">
                        <ChevronRightIcon
                            class="-mr-0.5 h-4 w-4  group-hover:text-white"
                            aria-hidden="true"/>
                        Verlauf ansehen
                    </button>
                </div>
                <div class="mr-14 my-4 subpixel-antialiased text-secondary">
                    {{ moneySource.description }}
                </div>
                <div class="mt-12 flex">
                    <div class="w-1/2 xsLight uppercase border-r-2">
                        Ursprungsvolumen
                        <div class="bigNumber my-4">
                            {{moneySource.amount}}
                        </div>
                    </div>
                    <div class="w-1/2 xsLight uppercase ml-6">
                        Noch Verfügbar
                        <div class="bigNumber my-4">
                            {{moneySource.amount}}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Div with Bg-Color -->
        <div class="w-full h-full mb-48">
            <div class="max-w-screen-2xl bg-lightBackgroundGray">
                <div class="headline4 py-12 ml-20">
                Untergeordnete Finanzierungsquellen
                </div>
            </div>
        </div>
    </app-layout>
</template>

<script>


import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
} from "@headlessui/vue";
import {
    PencilAltIcon,
    TrashIcon,
    DuplicateIcon,
} from "@heroicons/vue/outline";
import {
    DotsVerticalIcon,
    ChevronRightIcon
} from "@heroicons/vue/solid";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import SvgCollection from "@/Layouts/Components/SvgCollection";



export default {
    name: "MoneySourceShow",
    props: ['moneySource'],
    components: {
        AppLayout,
        UserTooltip,
        ChevronRightIcon,
        Menu,
        MenuItem,
        MenuItems,
        MenuButton,
        DuplicateIcon,
        PencilAltIcon,
        TrashIcon,
        DotsVerticalIcon,
        SvgCollection
    },
    computed: {},
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
