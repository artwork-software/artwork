<template>
    <div class="w-full flex justify-end items-center ml-8 -mt-14">
        <div v-if="!this.showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
             class="cursor-pointer inset-y-0 mr-3">
            <SearchIcon class="h-5 w-5" aria-hidden="true"/>
        </div>
        <div v-else class="flex items-center w-64 mr-2">
            <div>
                <input type="text"
                       :placeholder="$t('Search')"
                       v-model="this.searchText"
                       class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
            </div>
            <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
        </div>
    </div>
    <div v-for="sageNotAssignedData in this.filteredTrashedSageNotAssignedData"
         class="flex w-full bg-white my-2 border border-gray-200">
        <button class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover flex" @click="sageNotAssignedData.hidden = !sageNotAssignedData.hidden">
            <ChevronUpIcon v-if="sageNotAssignedData.hidden === true"
                           class="h-6 w-6 text-white my-auto"
            />
            <ChevronDownIcon v-else
                             class="h-6 w-6 text-white my-auto"
            />
        </button>
        <div class="flex mt-8 w-full ml-4 flex-wrap p-4">
            <div class="flex justify-between w-full">
                <div class="my-auto">
                    <span class="text-2xl leading-6 font-bold font-lexend text-gray-900">
                    {{ sageNotAssignedData.buchungstext }}
                    </span>
                </div>
                <div class="flex items-center">
                    <Menu as="div" class="my-auto relative">
                        <div class="flex">
                            <MenuButton
                                class="flex bg-tagBg p-0.5 rounded-full">
                                <DotsVerticalIcon
                                    class=" flex-shrink-0 h-6 w-6 text-menuartwork-buttons-create my-auto"
                                    aria-hidden="true"/>
                            </MenuButton>
                        </div>
                        <transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="transform opacity-0 scale-95"
                            enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="origin-top-right absolute right-0 w-56 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                        <Link method="patch"
                                              :href="route(
                                                  'sageNotAssignedData.restore',
                                                  {
                                                      sageNotAssignedData: sageNotAssignedData.id
                                                  }
                                              )"
                                              :class="[active ? 'bg-primaryHover text-white' :
                                                'text-secondary',
                                                'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                            <RefreshIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Restore') }}
                                        </Link>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <Link method="delete"
                                              :href="route(
                                                  'sageNotAssignedData.forceDelete',
                                                  {
                                                      sageNotAssignedData: sageNotAssignedData.id
                                                  }
                                              )"
                                              :class="[active ? 'bg-primaryHover text-white' :
                                                'text-secondary',
                                                'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                aria-hidden="true"/>
                                            {{ $t('Delete permanently') }}
                                        </Link>
                                    </MenuItem>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                </div>
            </div>
            <div class="w-full mt-6 mb-12" v-if="sageNotAssignedData.hidden">
                <div class="grid grid-cols-2">
                    <span class="xsLight">{{ $t('Creditor') }}</span>
                    <span class="xsLight text-black">{{ sageNotAssignedData.kreditor }}</span>
                    <span class="xsLight">{{ $t('Betrag') }}</span>
                    <span class="xsLight text-black">{{ sageNotAssignedData.buchungsbetrag }} EUR</span>
                    <span class="xsLight">{{ $t('Booking text') }}</span>
                    <span class="xsLight text-black">{{ sageNotAssignedData.buchungstext }}</span>

                    <span class="xsLight mt-4">{{ $t('Document number') }}</span>
                    <span class="xsLight text-black mt-4">{{ sageNotAssignedData.belegnummer }}</span>
                    <span class="xsLight">{{ $t('Document date') }}</span>
                    <span class="xsLight text-black">
                        {{ this.formatBookingDataDate(sageNotAssignedData.belegdatum) }}
                    </span>

                    <span class="xsLight mt-4">{{ $t('General ledger account') }}</span>
                    <span class="xsLight text-black mt-4">{{ sageNotAssignedData.sa_kto }}</span>
                    <span class="xsLight">{{ $t('Cost bearer') }}</span>
                    <span class="xsLight text-black">{{ sageNotAssignedData.kst_traeger }}</span>
                    <span class="xsLight">{{ $t('Cost center') }}</span>
                    <span class="xsLight text-black">{{ sageNotAssignedData.kst_stelle }}</span>

                    <span class="xsLight mt-4">{{ $t('Booking date') }}</span>
                    <span class="xsLight text-black mt-4">
                        {{ this.formatBookingDataDate(sageNotAssignedData.buchungsdatum) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";
import {ChevronUpIcon, ChevronDownIcon, DotsVerticalIcon, RefreshIcon, SearchIcon} from "@heroicons/vue/solid";
import {TrashIcon, XIcon} from "@heroicons/vue/outline";
import {Menu, MenuButton,MenuItems,MenuItem } from "@headlessui/vue";
import { Link } from "@inertiajs/vue3";
import Input from "@/Layouts/Components/InputComponent.vue";

export default {
    name: "SageNotAssignedDataTrashed",
    layout: [AppLayout, TrashLayout],
    props: ['sageNotAssignedDataTrashed'],
    components: {
        Input,
        XIcon,
        SearchIcon,
        ChevronDownIcon,
        ChevronUpIcon,
        Menu,
        MenuButton,
        DotsVerticalIcon,
        MenuItems,
        MenuItem,
        RefreshIcon,
        TrashIcon,
        Link
    },
    data() {
        return {
            showSearchbar: false,
            searchText: '',
        }
    },
    computed: {
        filteredTrashedSageNotAssignedData() {
            if (!this.searchText) {
                return this.sageNotAssignedDataTrashed;
            }

            return this.sageNotAssignedDataTrashed.filter((sageNotAssignedData) => {
                return sageNotAssignedData.buchungstext.includes(this.searchText);
            });
        }
    },
    methods: {
        closeSearchbar() {
            this.showSearchbar = false
            this.searchText = ''
        },
        formatBookingDataDate(dateString) {
            let parts = dateString.split('T');
            parts = parts[0].split('-');

            return parts[2] + '.' + parts[1] + '.' + parts[0];
        }
    }
}
</script>
