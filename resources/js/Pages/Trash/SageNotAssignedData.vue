<template>
    <TrashSearchAndActions
        property-name="sageNotAssignedDataTrashed"
        :total="sageNotAssignedDataTrashed.total"
        @delete-all="showConfirmDeleteAll = true"
    />
    <div v-for="sageNotAssignedData in sageNotAssignedDataTrashed.data" :key="sageNotAssignedData.id"
         class="flex w-full bg-white my-2 border border-border-subtle">
        <button class="bg-accent-600 hover:bg-accent-700 flex" @click="sageNotAssignedData.hidden = !sageNotAssignedData.hidden">
            <IconChevronUp v-if="sageNotAssignedData.hidden === true"
                           class="h-6 w-6 text-white my-auto"
            />
            <IconChevronDown v-else
                             class="h-6 w-6 text-white my-auto"
            />
        </button>
        <div class="flex mt-8 w-full ml-4 flex-wrap p-4">
            <div class="flex justify-between w-full">
                <div class="my-auto">
                    <span class="text-2xl leading-6 font-bold font-lexend text-text">
                    {{ sageNotAssignedData.buchungstext }}
                    </span>
                </div>
                <div class="flex items-center">
                    <Menu as="div" class="my-auto relative">
                        <div class="flex">
                            <MenuButton
                                class="flex  p-0.5 rounded-full">
                                <IconDotsVertical
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
                                class="origin-top-right absolute right-0 w-56 shadow-lg bg-surface-inverse ring-1 ring-black ring-opacity-5 divide-y divide-white/10">
                                <div class="py-1">
                                    <MenuItem v-slot="{ active }">
                                        <Link method="patch"
                                              :href="route(
                                                  'sageNotAssignedData.restore',
                                                  {
                                                      sageNotAssignedData: sageNotAssignedData.id
                                                  }
                                              )"
                                              :class="[active ? ' text-white' :
                                                'text-text-subtle',
                                                'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                            <IconRefresh
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
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
                                              :class="[active ? ' text-white' :
                                                'text-text-subtle',
                                                'group flex items-center px-4 py-2 w-full text-sm subpixel-antialiased']">
                                            <IconTrash
                                                class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
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
                    <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Creditor') }}</span>
                    <span class="text-sm/5 font-bold text-text-subtle text-black">{{ sageNotAssignedData.kreditor }}</span>
                    <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Betrag') }}</span>
                    <span class="text-sm/5 font-bold text-text-subtle text-black">{{ sageNotAssignedData.buchungsbetrag }} EUR</span>
                    <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Booking text') }}</span>
                    <span class="text-sm/5 font-bold text-text-subtle text-black">{{ sageNotAssignedData.buchungstext }}</span>

                    <span class="text-sm/5 font-bold text-text-subtle mt-4">{{ $t('Document number') }}</span>
                    <span class="text-sm/5 font-bold text-text-subtle text-black mt-4">{{ sageNotAssignedData.belegnummer }}</span>
                    <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Document date') }}</span>
                    <span class="text-sm/5 font-bold text-text-subtle text-black">
                        {{ this.formatBookingDataDate(sageNotAssignedData.belegdatum) }}
                    </span>

                    <span class="text-sm/5 font-bold text-text-subtle mt-4">{{ $t('General ledger account') }}</span>
                    <span class="text-sm/5 font-bold text-text-subtle text-black mt-4">{{ sageNotAssignedData.sa_kto }}</span>
                    <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Cost bearer') }}</span>
                    <span class="text-sm/5 font-bold text-text-subtle text-black">{{ sageNotAssignedData.kst_traeger }}</span>
                    <span class="text-sm/5 font-bold text-text-subtle">{{ $t('Cost center') }}</span>
                    <span class="text-sm/5 font-bold text-text-subtle text-black">{{ sageNotAssignedData.kst_stelle }}</span>

                    <span class="text-sm/5 font-bold text-text-subtle mt-4">{{ $t('Booking date') }}</span>
                    <span class="text-sm/5 font-bold text-text-subtle text-black mt-4">
                        {{ this.formatBookingDataDate(sageNotAssignedData.buchungsdatum) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <BasePaginator
        v-if="sageNotAssignedDataTrashed.total > 0"
        :entities="sageNotAssignedDataTrashed"
        property-name="sageNotAssignedDataTrashed"
        class="mt-6"
    />

    <ConfirmDeleteModal
        v-if="showConfirmDeleteAll"
        :title="$t('Delete all')"
        :description="$t('Are you sure you want to permanently delete all items in the recycle bin for this category?')"
        @closed="showConfirmDeleteAll = false"
        @delete="forceDeleteAll"
    />
</template>

<script>
import {IconChevronDown, IconChevronUp, IconDotsVertical, IconRefresh, IconSearch, IconTrash, IconX} from "@tabler/icons-vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";
import {Menu, MenuButton,MenuItems,MenuItem } from "@headlessui/vue";
import { Link } from "@inertiajs/vue3";
import Input from "@/Layouts/Components/InputComponent.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import TrashSearchAndActions from "@/Pages/Trash/Components/TrashSearchAndActions.vue";
import BasePaginator from "@/Components/Paginate/BasePaginator.vue";

export default {
    name: "SageNotAssignedDataTrashed",
    layout: [AppLayout, TrashLayout],
    props: ['sageNotAssignedDataTrashed'],
    components: {
        BasePaginator,
        TrashSearchAndActions,
        ConfirmDeleteModal,
        Input,
        IconX,
        IconSearch,
        IconChevronDown,
        IconChevronUp,
        Menu,
        MenuButton,
        IconDotsVertical,
        MenuItems,
        MenuItem,
        IconRefresh,
        IconTrash,
        Link
    },
    data() {
        return {
            showConfirmDeleteAll: false,
        }
    },
    methods: {
        formatBookingDataDate(dateString) {
            let parts = dateString.split('T');
            parts = parts[0].split('-');

            return parts[2] + '.' + parts[1] + '.' + parts[0];
        },
        forceDeleteAll() {
            this.$inertia.delete(route('sageNotAssignedData.forceDeleteAll'), {
                onSuccess: () => {
                    this.showConfirmDeleteAll = false;
                }
            });
        },
    }
}
</script>
