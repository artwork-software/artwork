<template>
    <Head>
        <link rel="icon" type="image/png" :href="usePage().props.small_logo" />
        <title>{{ $t('Recycle bin') }} - {{ usePage().props.page_title }}</title>
    </Head>
    <div class="artwork-container">

        <div>
            <ToolbarHeader
                :icon="IconTrash"
                title="Recycle bin"
                description="You can restore objects from your recycle bin or delete them permanently. Items are automatically deleted permanently after 30 days."
                icon-bg-class="bg-blue-600/10 text-blue-700"
                :search-enabled="false"
            >
                <template #actions>
                    <Listbox as="div" class="sm:col-span-3 mb-4" v-model="selectedTrash">
                        <div class="relative">
                            <ListboxButton class="flex cursor-pointer bg-white relative pr-14 font-semibold py-2 mt-4 text-left
                                    focus:outline-none focus:ring-0 focus:ring-primary
                                    focus:border-primary sm:text-sm">
                                            <span class="block truncate items-center primary">
                                                <span>{{ selectedTrash.name }}</span>
                                            </span>
                                <span
                                    class="relative flex items-center pr-2">
                                         <ChevronDownIcon class="ml-2 h-5 w-5 text-primary" aria-hidden="true"/>
                                        </span>
                            </ListboxButton>

                            <transition leave-active-class="transition ease-in duration-100"
                                        leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions
                                    class="absolute w-40 z-10 mt-1 w-full bg-artwork-navigation-background shadow-lg rounded-md text-base ring-1
                                            ring-black ring-opacity-5 overflow-y-auto focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" class="max-h-8"
                                                   v-for="page in trashSites"
                                                   v-show="page.available"
                                                   :key="page.name"
                                                   :value="page"
                                                   v-slot="{ active, selected }">
                                        <li @click="redirectToPage(page.href)" :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group cursor-pointer flex items-center justify-between p-3 text-sm subpixel-antialiased']">
                                                    <span
                                                        :class="[selected ? 'font-bold text-white' : 'font-normal', 'block truncate']">
                                                        {{ page.name }}
                                                    </span>
                                            <span :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' :
                                                        'text-secondary',
                                                        'group flex items-center text-sm subpixel-antialiased']">
                                                        <CheckIcon v-if="selected"
                                                                   class="h-5 w-5 flex text-success"
                                                                   aria-hidden="true"/>
                                                    </span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>
                </template>
            </ToolbarHeader>
        </div>

        <div class="container mt-20">
            <slot />
        </div>
    </div>
</template>

<script>
import {Listbox, ListboxButton, ListboxOptions, ListboxOption, MenuItem} from "@headlessui/vue";
import {SearchIcon, ChevronDownIcon, CheckIcon} from "@heroicons/vue/solid";
import {Head, router, usePage} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import {XIcon} from "@heroicons/vue/outline";
import Input from "@/Layouts/Components/InputComponent.vue";
import {
    IconCheck,
    IconChevronDown,
    IconChevronUp,
    IconFileExport,
    IconGeometry,
    IconPlus,
    IconTrash
} from "@tabler/icons-vue";
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";

export default {
    name: "TrashLayout",
    mixins: [Permissions],
    data() {
        return {
            selectedTrash: null,
        }
    },
    created() {
        this.selectedTrash = this.trashSites[this.$page.component];
    },
    methods: {
        IconTrash,
        IconPlus,
        IconFileExport,
        IconGeometry,
        usePage,
        redirectToPage(href) {
            this.$inertia.get(href, {}, { preserveState: true });
        }
    },
    computed: {
        trashSites() {
            return {
                'Trash/Projects': {
                    name: this.$t('Projects'),
                    href: route('projects.trashed'),
                    available: true
                },
                'Trash/Areas': {
                    name: this.$t('Areas'),
                    href: route('areas.trashed'),
                    available: true
                },
                'Trash/Rooms': {
                    name: this.$t('Rooms'),
                    href: route('rooms.trashed'),
                    available: true
                },
                'Trash/Events': {
                    name: this.$t('Events'),
                    href: route('events.trashed'),
                    available: true
                },
                'Trash/ProjectSettings': {
                    name: this.$t('Project Settings'),
                    href: route('projects.settings.trashed'),
                    available: true
                },
                'Trash/SageNotAssignedData': {
                    name: this.$t('Sage API data sets'),
                    href: route('sageNotAssignedData.trashed'),
                    available: this.$can('can view and delete sage100-api-data')
                },
                'Trash/BudgetManagementAccount': {
                    name: this.$t('Accounts'),
                    href: route('budget-settings.account-management.trash-accounts'),
                    available: this.$canAny(
                        [
                            'can manage global project budgets',
                            'can manage all project budgets without docs'
                        ]
                    ),
                },
                'Trash/BudgetManagementCostUnit': {
                    name: this.$t('Cost Units'),
                    href: route('budget-settings.account-management.trash-cost-units'),
                    available: this.$canAny(
                        [
                            'can manage global project budgets',
                            'can manage all project budgets without docs'
                        ]
                    ),
                },
                'Trash/InventoryArticles': {
                    name: this.$t('Articles'),
                    href: route('inventory.articles.trash'),
                    available: true
                }
            }
        }
    },
    components: {
        ToolbarHeader,
        IconChevronUp,
        MenuItem,
        IconChevronDown,
        IconCheck,
        Head,
        Input, XIcon,
        Listbox,
        SearchIcon,
        ListboxButton,
        ListboxOptions,
        ListboxOption,
        CheckIcon,
        ChevronDownIcon
    }
}
</script>

<style scoped>

</style>
