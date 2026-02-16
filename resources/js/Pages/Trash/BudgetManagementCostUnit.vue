<template>
    <div class="w-full flex justify-end items-center ml-8 -mt-14">
        <div v-if="!this.showSearchbar" @click="openSearchbar"
             class="cursor-pointer inset-y-0 mr-3">
            <SearchIcon class="h-5 w-5" aria-hidden="true"/>
        </div>
        <button v-if="trashedCostUnits.length > 0" @click="showConfirmDeleteAll = true"
                class="cursor-pointer text-red-500 hover:text-red-700 mr-3">
            <TrashIcon class="h-5 w-5" aria-hidden="true"/>
        </button>
        <div v-else class="flex items-center w-64 mr-2">
            <div>
                <input type="text"
                       :placeholder="$t('Search')"
                       ref="searchBarInput"
                       v-model="this.searchText"
                       class="h-10 sDark inputMain rounded-lg placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
            </div>
            <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
        </div>
    </div>
    <div v-for="trashedCostUnit in this.filteredTrashedCostUnits"
         class="flex w-full bg-white my-2 border border-gray-200">
        <button class="bg-artwork-buttons-create hover:bg-artwork-buttons-hover flex" @click="trashedCostUnit.hidden = !trashedCostUnit.hidden">
            <ChevronUpIcon v-if="trashedCostUnit.hidden === true"
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
                    {{ trashedCostUnit.cost_unit_number }}
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
                                                  'budget-settings.account-management.trash-cost-units.restore',
                                                  {
                                                      budgetManagementCostUnit: trashedCostUnit.id
                                                  }
                                              )"
                                              :class="[active ? 'bg-primaryHover text-white' :
                                                'text-secondary',
                                                'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                            <RefreshIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
                                                aria-hidden="true"/>
                                            {{ $t('Restore') }}
                                        </Link>
                                    </MenuItem>
                                    <MenuItem v-slot="{ active }">
                                        <Link method="delete"
                                              :href="route(
                                                  'budget-settings.account-management.trash-cost-units.forceDelete',
                                                  {
                                                      budgetManagementCostUnit: trashedCostUnit.id
                                                  }
                                              )"
                                              :class="[active ? 'bg-primaryHover text-white' :
                                                'text-secondary',
                                                'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                            <TrashIcon
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-artwork-buttons-hover"
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
            <div class="w-full mt-6 mb-12" v-if="trashedCostUnit.hidden">
                {{ trashedCostUnit.title }}
            </div>
        </div>
    </div>

    <ConfirmDeleteModal
        v-if="showConfirmDeleteAll"
        :title="$t('Delete all')"
        :description="$t('Are you sure you want to permanently delete all items in the recycle bin for this category?')"
        @closed="showConfirmDeleteAll = false"
        @delete="forceDeleteAll"
    />
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";
import {ChevronUpIcon, ChevronDownIcon, DotsVerticalIcon, RefreshIcon, SearchIcon} from "@heroicons/vue/solid";
import {TrashIcon, XIcon} from "@heroicons/vue/outline";
import {Menu, MenuButton,MenuItems,MenuItem } from "@headlessui/vue";
import { Link } from "@inertiajs/vue3";
import Input from "@/Layouts/Components/InputComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";

export default {
    name: "BudgetManagementCostUnitsTrashed",
    layout: [AppLayout, TrashLayout],
    props: ['trashedCostUnits'],
    components: {
        ConfirmDeleteModal,
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
            showConfirmDeleteAll: false,
        }
    },
    computed: {
        filteredTrashedCostUnits() {
            if (!this.searchText) {
                return this.trashedCostUnits;
            }

            return this.trashedCostUnits.filter((trashedCostUnit) => {
                return trashedCostUnit.cost_unit_number.includes(this.searchText) ||
                    trashedCostUnit.title.includes(this.searchText);
            });
        }
    },
    methods: {
        closeSearchbar() {
            this.showSearchbar = false
            this.searchText = ''
        },
        openSearchbar(){
            this.showSearchbar = !this.showSearchbar;
            this.$nextTick(() => {
                if (this.showSearchbar) {
                    this.$refs.searchBarInput.focus();
                }
            });
        },
        forceDeleteAll() {
            this.$inertia.delete(route('budget-settings.account-management.trash-cost-units.forceDeleteAll'), {
                onSuccess: () => {
                    this.showConfirmDeleteAll = false;
                }
            });
        },
    }
}
</script>
