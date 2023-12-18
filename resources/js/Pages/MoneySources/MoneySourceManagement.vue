<template>
    <app-layout>
        <div class="">
            <div class="max-w-screen-xl mb-40 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div>
                        <p class="items-center flex mr-2 headline1 mb-11">Finanzierungsquellen</p>
                    </div>
                    <div class="w-full flex my-auto">
                        <div class="flex justify-between w-full">
                            <div class="flex">
                                <Listbox as="div" class="flex" v-model="moneySourceFilter">
                                    <ListboxButton
                                        class="bg-white w-full relative py-2 cursor-pointer focus:outline-none">
                                        <div class="flex items-center my-auto">
                                            <p class="items-center flex mr-2 xsDark">
                                                {{ moneySourceFilter.name }}</p>
                                            <span
                                                class="inset-y-0 flex items-center pr-2 pointer-events-none">
                                                <ChevronDownIcon class="h-5 w-5" aria-hidden="true"/>
                                             </span>
                                        </div>
                                    </ListboxButton>
                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions
                                            class="absolute w-80 z-10 mt-12 bg-primary shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
                                            <ListboxOption as="template" class="max-h-8"
                                                           v-for="filter in moneySourceFilters"
                                                           :key="filter.name"
                                                           :value="filter"
                                                           v-slot="{ active, selected }">
                                                <li :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
                                                        <span
                                                            :class="[selected ? 'xsWhiteBold' : 'xsLight', 'block truncate']">
                                                            {{ filter.name }}
                                                        </span>
                                                </li>
                                            </ListboxOption>
                                        </ListboxOptions>
                                    </transition>
                                </Listbox>
                            </div>
                            <div class="flex items-center ml-8">
                                <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                                     class="cursor-pointer inset-y-0 mr-3">
                                    <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                                </div>
                                <div v-else class="flex items-center w-full mr-2">
                                    <input type="text"
                                           placeholder="Suche nach Quellen"
                                           v-model="moneySource_query"
                                           class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                                </div>
                                <div class="flex items-center ml-8 relative h-10 w-10">
                                    <img @click="showMoneySourceFilters = !showMoneySourceFilters" src="/Svgs/IconSvgs/icon_filter.svg" class="h-6 w-6 mx-2 cursor-pointer" />
                                    <div v-if="showMoneySourceFilters"
                                         class="w-72 absolute top-10 h-auto bg-primary p-2 flex flex-col">
                                        <Disclosure v-slot="{ open }">
                                            <DisclosureButton class="flex w-full py-2 px-2 justify-between rounded-lg bg-primary text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500">
                                                <span :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">Alle Kategorien</span>
                                                <ChevronDownIcon :class="open ? 'rotate-180 transform' : ''" class="h-4 w-4 mt-0.5 text-white"/>
                                            </DisclosureButton>
                                            <DisclosurePanel>
                                                <div class="flex flex-col gap-1 px-2">
                                                    <div v-for="moneySourceCategory in moneySourceCategories"
                                                         class="flex flex-row items-center">
                                                        <input class="text-success h-4 w-4 border-1 border-darkGray bg-darkGrayBg focus:border-none"
                                                               v-model="categoryFilters"
                                                               :id="'moneySourceCategoryFilterId-' + moneySourceCategory.id"
                                                               type="checkbox"
                                                               :value="moneySourceCategory.id"
                                                        />
                                                        <label :class="[
                                                                  categoryFilters.includes(moneySourceCategory.id) ?
                                                                      'text-white' :
                                                                      'text-secondary',
                                                                  'cursor-pointer text-xs text-secondary subpixel-antialiased ml-1.5'
                                                               ]"
                                                               :for="'moneySourceId-' + moneySourceCategory.id">
                                                            {{moneySourceCategory.name}}
                                                        </label>
                                                    </div>
                                                </div>
                                            </DisclosurePanel>
                                        </Disclosure>
                                        <hr class="border-secondary rounded-full border-2 mt-2 mb-2">
                                        <div class="flex flex-row px-2">
                                            <input class="text-success h-4 w-4 border-1 border-darkGray bg-darkGrayBg focus:border-none cursor-pointer"
                                                   v-model="openTasksFilter"
                                                   id="openTasksFilter"
                                                   type="checkbox"
                                            />
                                            <label :class="[
                                                        openTasksFilter ?
                                                            'text-white' :
                                                            'text-secondary',
                                                        'cursor-pointer text-xs subpixel-antialiased ml-1.5'
                                                   ]"
                                                   for="openTasksFilter">
                                                Quellen mit offenen Aufgaben
                                            </label>
                                        </div>
                                        <hr class="border-secondary rounded-full border-2 mt-2 mb-2">
                                        <div @click="openTimeSpanFilterModal"
                                             :class="[
                                                        timeSpanFilterActive ?
                                                            'text-white' :
                                                            'text-secondary',
                                                        'cursor-pointer text-xs subpixel-antialiased ml-1.5'
                                                   ]">
                                            Zeitraum
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex"
                                 v-if="$can('view edit add money_sources') || $can('can edit and delete money sources') || $role('artwork admin')">
                                <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                    <span
                                        class="hind ml-1 my-auto">Lege neue Finanzierungsquellen oder -gruppen an</span>
                                    <img src="/Svgs/IconSvgs/icon_grey_arrow_right.svg"
                                         class="h-4 w-4 ml-1"
                                         aria-hidden="true"/>
                                </div>
                                <AddButton @click="openAddMoneySourceModal" text="Neu" mode="page"/>
                            </div>
                        </div>
                    </div>
                    <div class="w-full flex flex-wrap">
                        <TagComponent v-if="timeSpanFilterActive"
                                      :displayed-text="formatDateString(timeSpanFilterStart) + ' - ' + formatDateString(timeSpanFilterEnd)"
                                      :method="deactivateTimeSpanFilter"/>
                        <TagComponent v-if="categoryFilters.length > 0"
                                      v-for="categoryFilter in categoryFilters"
                                      :displayed-text="this.moneySourceCategories.find((value) => value.id === categoryFilter).name"
                                      :method="() => deactivateCategoryFilter(categoryFilter)"/>
                        <TagComponent v-if="openTasksFilter"
                                      displayed-text="Quellen mit offenen Aufgaben"
                                      :method="deactivateOpenTasksFilter"/>
                    </div>
                    <ul role="list" class="mt-5 w-full">
                        <li v-for="(moneySource) in filteredMoneySources"
                            :key="moneySource.id"
                        >
                            <div class="py-5 flex flex-col justify-between border-b-2 border-gray-200 my-2"
                                 v-if="($page.props.myMoneySources.some(source => source.money_source_id === moneySource.id) || $page.props.is_money_source_admin || $canAny('view edit add money_sources','can edit and delete money sources'))">
                                <div class="flex flex-row w-full">
                                    <div class="flex w-full">
                                        <div class="flex">
                                            <img v-if="moneySource.is_group" src="/Svgs/IconSvgs/icon_group_red.svg"
                                                 class=" h-6 w-6 ml-5" alt="groupIcon"/>
                                            <Link :href="getEditHref(moneySource)"
                                                  class="sDark ml-5 my-auto w-full justify-start mr-6">
                                                {{ moneySource.name }}
                                            </Link>
                                        </div>
                                        <div v-if="moneySource.group_id !== null"
                                             class="w-full flex items-center xxsLight subpixel-antialiased ml-14 mt-1">
                                            Gehört zu:
                                            {{
                                                this.moneySourceGroups.find(sourceGroup => sourceGroup.id === moneySource.group_id).name
                                            }}
                                        </div>
                                    </div>
                                    <div v-if="moneySource.pinned_by_users && moneySource.pinned_by_users.includes($page.props.user.id)"
                                         class="flex items-center xxsLight subpixel-antialiased ml-14 mt-1">
                                        <IconPin class="h-5 w-5 mr-4 text-primary"/>
                                    </div>
                                    <div class="flex">
                                        <Menu as="div" class="my-auto relative">
                                            <div class="flex">
                                                <MenuButton
                                                    class="flex bg-tagBg p-0.5 rounded-full">
                                                    <DotsVerticalIcon
                                                        class=" flex-shrink-0 h-6 w-6 text-menuButtonBlue my-auto"
                                                        aria-hidden="true"/>
                                                </MenuButton>
                                            </div>
                                            <transition enter-active-class="transition ease-out duration-100"
                                                        enter-from-class="transform opacity-0 scale-95"
                                                        enter-to-class="transform opacity-100 scale-100"
                                                        leave-active-class="transition ease-in duration-75"
                                                        leave-from-class="transform opacity-100 scale-100"
                                                        leave-to-class="transform opacity-0 scale-95">
                                                <MenuItems
                                                    class="origin-top-right z-10 absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                                    <div class="py-1">
                                                        <MenuItem class="cursor-pointer" v-slot="{ active }">
                                                            <a :href="getEditHref(moneySource)"
                                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                <PencilAltIcon
                                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                    aria-hidden="true"/>
                                                                Bearbeiten
                                                            </a>
                                                        </MenuItem>
                                                        <MenuItem class="cursor-pointer" v-slot="{ active }" v-if="getMemberInMoneySource(moneySource).write_access.includes($page.props.user.id) || getMemberInMoneySource(moneySource).competent.includes($page.props.user.id) || $can('view edit add money_sources') || $can('can edit and delete money sources') || $role('artwork admin')">
                                                            <a @click="duplicateMoneySource(moneySource)"
                                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                <DuplicateIcon
                                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                    aria-hidden="true"/>
                                                                Duplizieren
                                                            </a>
                                                        </MenuItem>
                                                        <MenuItem class="cursor-pointer" v-slot="{ active }" v-if="getMemberInMoneySource(moneySource).write_access.includes($page.props.user.id) || getMemberInMoneySource(moneySource).competent.includes($page.props.user.id) || $can('view edit add money_sources') || $can('can edit and delete money sources') || $role('artwork admin')">
                                                            <a @click="pinMoneySource(moneySource)"
                                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                <IconPin
                                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                    aria-hidden="true"/>
                                                                Anpinnen
                                                            </a>
                                                        </MenuItem>
                                                        <MenuItem class="cursor-pointer" v-slot="{ active }" v-if="getMemberInMoneySource(moneySource).write_access.includes($page.props.user.id) || getMemberInMoneySource(moneySource).competent.includes($page.props.user.id) || $can('can edit and delete money sources') || $role('artwork admin')">
                                                            <a @click="openDeleteSourceModal(moneySource)"
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
                                <div class="ml-5 mt-4 flex flex-wrap">
                                    <TagComponent v-for="moneySourceCategory in moneySource.categories"
                                                  :key="moneySourceCategory.id"
                                                  :displayed-text="moneySourceCategory.name"
                                                  :hide-x="true"
                                    />
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </app-layout>
    <create-money-source-component
        v-if="showMoneySourceModal"
        @closed="onCreateMoneySourceModalClose()"
        :moneySourceGroups="this.moneySourceGroups"
    />
    <confirm-delete-modal
        v-if="showDeleteSourceModal"
        title="Finanzierungsquelle/gruppe löschen"
        :description="'Bist du sicher, dass du die Finanzierungsquelle/Gruppe ' + this.sourceToDelete.name + ' löschen möchtest?'"
        @closed="afterConfirm(false)"
        @delete="afterConfirm(true)"/>

    <jet-dialog-modal :show="timeSpanFilterModalVisible" @close="closeTimeSpanFilterModal">
        <template #content>
            <img src="/Svgs/Overlays/illu_project_edit.svg" class="-ml-6 -mt-8 mb-4" alt="Illustration Projekt bearbeiten"/>
            <XIcon @click="closeTimeSpanFilterModal()"
                 class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                 aria-hidden="true"/>
            <div class="mx-4">
                <h1 class="my-1 flex">
                    <div class="flex-grow headline1">
                        Zeitraum wählen
                    </div>
                </h1>
                <h2 class="xsLight mb-2 mt-8">
                    Bitte wähle die Zeitspanne in der die Finanzquellen angezeigt werden sollen.
                </h2>
                <div class="flex flex-row justify-between">
                    <input class="w-1/2" type="date" v-model="timeSpanFilterStart"/>
                    <input class="w-1/2" type="date" v-model="timeSpanFilterEnd"/>
                </div>
                <div class="w-full flex justify-center my-3">
                    <AddButton :disabled="timeSpanFilterStart === null || timeSpanFilterEnd === null"
                               text="Filtern"
                               mode="modal"
                               @click="activateTimeSpanFilter"/>
                </div>
            </div>
        </template>
    </jet-dialog-modal>
</template>

<script>

import {defineComponent} from 'vue'
import AppLayout from '@/Layouts/AppLayout.vue'
import {
  Disclosure, DisclosureButton, DisclosurePanel,
  Listbox,
  ListboxButton,
  ListboxLabel,
  ListboxOption,
  ListboxOptions,
  Menu,
  MenuButton,
  MenuItem,
  MenuItems
} from "@headlessui/vue";
import {ChevronDownIcon, DotsVerticalIcon, SearchIcon, TrashIcon, XIcon} from "@heroicons/vue/solid";
import AddButton from "@/Layouts/Components/AddButton";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import InputComponent from "@/Layouts/Components/InputComponent";
import CreateMoneySourceComponent from "@/Layouts/Components/CreateMoneySourceComponent";
import {DuplicateIcon, PencilAltIcon} from "@heroicons/vue/outline";
import {Link} from "@inertiajs/inertia-vue3";
import Permissions from "@/mixins/Permissions.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import { IconPin } from '@tabler/icons-vue';
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import Label from "@/Jetstream/Label.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";


export default defineComponent({
    mixins: [Permissions],
    components: {
      JetDialogModal,
      DisclosurePanel,
      DisclosureButton,
      Disclosure,
      Label,
      TagComponent,
        Input,
        ConfirmDeleteModal,
        AppLayout,
        Listbox,
        ListboxButton,
        ListboxLabel,
        ListboxOption,
        ListboxOptions,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        ChevronDownIcon,
        AddButton,
        SvgCollection,
        SearchIcon,
        InputComponent,
        XIcon,
        CreateMoneySourceComponent,
        DotsVerticalIcon,
        PencilAltIcon,
        Link,
        DuplicateIcon,
        TrashIcon,
        IconPin
    },
    props: ['moneySourceCategories', 'moneySources', 'moneySourceGroups'],
    computed: {
        filteredMoneySources() {
            const allMoneySources = this.sourcesToShow();
            let filteredMoneySources = allMoneySources.filter(moneySource => {
                return moneySource.name.toLowerCase().includes(this.moneySource_query.toLowerCase());
            });

            if (this.categoryFilters.length > 0) {
                filteredMoneySources = filteredMoneySources.filter((moneySource) => {
                    if (moneySource.categories.length === 0) {
                        return false;
                    }

                    for (let i = 0; i < this.categoryFilters.length; i++) {
                        if (
                            !moneySource.categories
                                .some(moneySourceCategory => moneySourceCategory.id === this.categoryFilters[i])
                        ) {
                            return false;
                        }
                    }

                    return true;
                });
            }

            if (this.openTasksFilter) {
                filteredMoneySources = filteredMoneySources.filter((moneySource) => {
                    if (moneySource.money_source_tasks.length === 0) {
                        return false;
                    }

                    let hasMoneySourceTaskNotDone = false;
                    moneySource.money_source_tasks.forEach((moneySourceTask) => {
                        if (moneySourceTask.done === 0) {
                            hasMoneySourceTaskNotDone = true;
                        }
                    });
                    return hasMoneySourceTaskNotDone;
                });
            }

            if (this.timeSpanFilterActive) {
                let filterStartDate = new Date(this.timeSpanFilterStart);
                let filterEndDate = new Date(this.timeSpanFilterEnd);

                filteredMoneySources = filteredMoneySources.filter((moneySource) => {
                    if (moneySource.start_date === null || moneySource.end_date === null) {
                        return true;
                    }

                    let compareStartDate = new Date(moneySource.start_date),
                        compareEndDate = new Date(moneySource.end_date);

                    return compareStartDate <= filterEndDate && compareEndDate >= filterStartDate;
                });
            }

            // sorts by name but pin is always on top. Accounts for pinned_by_users being null
            filteredMoneySources.sort((a, b) => {
                if (a.pinned_by_users && a.pinned_by_users.includes(this.$page.props.user.id)) {
                    return -1;
                } else if (b.pinned_by_users && b.pinned_by_users.includes(this.$page.props.user.id)) {
                    return 1;
                } else {
                    return a.name.localeCompare(b.name);
                }
            });

            return filteredMoneySources;
        },
    },
    methods: {
        formatDateString(dateString) {
            let date = new Date(dateString),
                day = date.getDate(),
                month = date.getMonth() + 1,
                year = date.getFullYear();

            return (day < 10 ? '0' + day : day) + '.' + (month < 10 ? '0' + month : month) + '.' + year;
        },
        openTimeSpanFilterModal() {
            this.timeSpanFilterModalVisible = true;
        },
        closeTimeSpanFilterModal() {
            this.timeSpanFilterModalVisible = false;
        },
        activateTimeSpanFilter() {
            this.closeTimeSpanFilterModal();
            this.timeSpanFilterActive = true;
        },
        deactivateTimeSpanFilter() {
            this.timeSpanFilterActive = false;
            this.timeSpanFilterStart = null;
            this.timeSpanFilterEnd = null;
        },
        deactivateOpenTasksFilter() {
            document.getElementById('openTasksFilter').click();
        },
        deactivateCategoryFilter(categoryFilter) {
            document.getElementById('moneySourceCategoryFilterId-' + categoryFilter).click();
        },
        getMemberInMoneySource(moneySource){
            const returnArray = {
                competent: [],
                write_access: []
            }
            moneySource.users.forEach((user) => {
                if(user.pivot.competent){
                    returnArray.competent.push(user.id);
                }
                if(user.pivot.write_access){
                    returnArray.write_access.push(user.id);
                }
            })
            return returnArray;
        },
        sourcesToShow() {
            if (this.moneySourceFilter.type === 'all') {
                return this.moneySources
            } else if (this.moneySourceFilter.type === 'single') {
                return this.moneySources.filter(moneySource => moneySource.is_group === false);
            } else if (this.moneySourceFilter.type === 'group') {
                return this.moneySourceGroups;
            }
        },
        closeSearchbar() {
            this.showSearchbar = !this.showSearchbar;
            this.moneySource_query = ''
        },
        openAddMoneySourceModal() {
            this.showMoneySourceModal = true;
        },
        onCreateMoneySourceModalClose() {
            this.showMoneySourceModal = false;
        },
        getEditHref(moneySource) {
            return route('money_sources.show', {moneySource: moneySource.id});
        },
        duplicateMoneySource(moneySource) {
            this.$inertia.post(`/money_sources/${moneySource.id}/duplicate`);
        },
        pinMoneySource(moneySource) {
            this.$inertia.post(`/money_sources/${moneySource.id}/pin`);
        },
        deleteMoneySource(moneySource) {
            this.$inertia.delete(`/money_sources/${moneySource.id}`);
            this.showDeleteSourceModal = false;
        },
        openDeleteSourceModal(moneySourceToDelete) {
            this.sourceToDelete = moneySourceToDelete;
            this.showDeleteSourceModal = true;
        },
        async afterConfirm(bool) {
            if (!bool) return this.showDeleteSourceModal = false;

            this.deleteMoneySource(this.sourceToDelete)
        },
    },
    data() {
        return {
            moneySourceFilters: [{
                'name': 'Alle Finanzierungsquellen & -gruppen',
                'type': 'all'
            }, {'name': 'Alle Finanzierungsquellen', 'type': 'single'}, {
                'name': 'Alle Finanzierungsgruppen',
                'type': 'group'
            }],
            moneySourceFilter: {'name': 'Alle Finanzierungsquellen & -gruppen', 'type': 'all'},
            showSearchbar: false,
            moneySource_query: '',
            showMoneySourceModal: false,
            moneySource_search_results: [],
            showDeleteSourceModal: false,
            sourceToDelete: null,
            showMoneySourceFilters: false,
            categoryFilters: [],
            openTasksFilter: false,
            timeSpanFilterModalVisible: false,
            timeSpanFilterStart: null,
            timeSpanFilterEnd: null,
            timeSpanFilterActive: false,
        }
    }
})
</script>
