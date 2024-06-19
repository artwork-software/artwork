<template>
    <app-layout :title="$t('Sources of funding')">
        <div class="">
            <div class="max-w-screen-xl mb-40 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div>
                        <p class="items-center flex mr-2 headline1 mb-11">{{ $t('Sources of funding')}}</p>
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
                                                <IconChevronDown stroke-width="1.5" class="h-5 w-5" aria-hidden="true"/>
                                             </span>
                                        </div>
                                    </ListboxButton>
                                    <transition leave-active-class="transition ease-in duration-100"
                                                leave-from-class="opacity-100" leave-to-class="opacity-0">
                                        <ListboxOptions
                                            class="absolute w-80 z-10 mt-12 bg-artwork-navigation-background shadow-lg max-h-64 p-3 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
                                            <ListboxOption as="template" class="max-h-8"
                                                           v-for="filter in moneySourceFilters"
                                                           :key="filter.name"
                                                           :value="filter"
                                                           v-slot="{ active, selected }">
                                                <li :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group cursor-pointer flex items-center justify-between py-2 px-3 text-sm subpixel-antialiased']">
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
                            <div class="flex items-center">
                                <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                                     class="cursor-pointer inset-y-0 mr-3">
                                    <IconSearch stroke-width="1.5" class="h-5 w-5" aria-hidden="true"/>
                                </div>
                                <div v-else class="flex items-center w-full mr-2">
                                    <input type="text"
                                           :placeholder="$t('Search for sources')"
                                           v-model="moneySource_query"
                                           class="h-10 inputMain placeholder:xsLight placeholder:subpixel-antialiased focus:outline-none focus:ring-0 focus:border-secondary focus:border-1 w-full border-gray-300"/>
                                    <IconX class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="flex items-center relative h-10 w-10">
                                    <IconFilter stroke-width="1.5" @click="showMoneySourceFilters = !showMoneySourceFilters"
                                          class="h-6 w-6 mx-2 cursor-pointer"/>
                                    <div v-if="showMoneySourceFilters"
                                         class="w-72 absolute top-10 h-auto bg-artwork-navigation-background p-2 flex flex-col z-50">
                                        <Disclosure v-slot="{ open }">
                                            <DisclosureButton
                                                class="flex w-full py-2 px-2 justify-between rounded-lg bg-artwork-navigation-background text-left text-sm font-medium focus:outline-none focus-visible:ring-purple-500">
                                                <span
                                                    :class="open ? 'font-bold text-white' : 'font-medium text-secondary'">{{ $t('All categories')}}</span>
                                                <ChevronDownIcon :class="open ? 'rotate-180 transform' : ''"
                                                                 class="h-4 w-4 mt-0.5 text-white"/>
                                            </DisclosureButton>
                                            <DisclosurePanel>
                                                <div class="flex flex-col gap-1 px-2"
                                                     v-if="moneySourceCategories.length > 0">
                                                    <div v-for="moneySourceCategory in moneySourceCategories"
                                                         class="flex flex-row items-center">
                                                        <input
                                                            class="text-success h-4 w-4 border-1 border-darkGray bg-darkGrayBg focus:border-none"
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
                                                            {{ moneySourceCategory.name }}
                                                        </label>
                                                    </div>
                                                </div>
                                                <div v-else class="xxsLight px-2">
                                                    {{ $t('No categories for funding sources have been created yet.')}}
                                                </div>
                                            </DisclosurePanel>
                                        </Disclosure>
                                        <hr class="border-secondary rounded-full border-2 mt-2 mb-2">
                                        <div class="flex flex-row px-2">
                                            <input
                                                class="text-success h-4 w-4 border-1 border-darkGray bg-darkGrayBg focus:border-none cursor-pointer"
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
                                                {{$t('Sources with open tasks')}}
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
                                            {{ $t('Period')}}
                                        </div>
                                    </div>
                                </div>
                                <Menu as="div" class="my-auto relative ml-2">
                                    <div class="flex">
                                        <MenuButton
                                            class="flex">
                                            <IconArrowsSort stroke-width="1.5"
                                                 class=" flex-shrink-0 h-6 w-6 my-auto"
                                                 aria-hidden="true" :alt="$t('Sort')"/>
                                        </MenuButton>
                                    </div>
                                    <transition enter-active-class="transition ease-out duration-100"
                                                enter-from-class="transform opacity-0 scale-95"
                                                enter-to-class="transform opacity-100 scale-100"
                                                leave-active-class="transition ease-in duration-75"
                                                leave-from-class="transform opacity-100 scale-100"
                                                leave-to-class="transform opacity-0 scale-95">
                                        <MenuItems
                                            class="origin-top-right z-10 absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-artwork-navigation-background ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                            <div class="py-1">
                                                <MenuItem class="cursor-pointer" v-slot="{ active }">
                                                    <div @click="changeSortAlgorithm('name')"
                                                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        {{$t('Alphabetical')}}
                                                        <IconSortDescending
                                                            v-if="sortType === 'name' && sortOrder === 'descending'"
                                                            class="ml-2 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        <IconSortAscending
                                                            v-if="sortType === 'name' && sortOrder === 'ascending'"
                                                            class="ml-2 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                    </div>
                                                </MenuItem>
                                                <MenuItem class="cursor-pointer" v-slot="{ active }">
                                                    <div @click="changeSortAlgorithm('funding_start_date')"
                                                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        {{$t('Start date')}}
                                                        <IconSortDescending
                                                            v-if="sortType === 'funding_start_date' && sortOrder === 'descending'"
                                                            class="ml-2 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        <IconSortAscending
                                                            v-if="sortType === 'funding_start_date' && sortOrder === 'ascending'"
                                                            class="ml-2 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                    </div>
                                                </MenuItem>
                                                <MenuItem class="cursor-pointer" v-slot="{ active }">
                                                    <div @click="changeSortAlgorithm('funding_end_date')"
                                                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        {{$t('End date')}}
                                                        <IconSortDescending
                                                            v-if="sortType === 'funding_end_date' && sortOrder === 'descending'"
                                                            class="ml-2 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        <IconSortAscending
                                                            v-if="sortType === 'funding_end_date' && sortOrder === 'ascending'"
                                                            class="ml-2 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                    </div>
                                                </MenuItem>
                                                <MenuItem class="cursor-pointer" v-slot="{ active }">
                                                    <div @click="changeSortAlgorithm('created_at')"
                                                         :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                        {{ $t('Created on')}}
                                                        <IconSortDescending
                                                            v-if="sortType === 'created_at' && sortOrder === 'descending'"
                                                            class="ml-2 h-5 w-5 text-primaryText group-hover:text-white"
                                                            aria-hidden="true"/>
                                                        <IconSortAscending
                                                            v-if="sortType === 'created_at' && sortOrder === 'ascending'"
                                                            class="ml-2 h-5 w-5 text-primaryText group-hover:text-white"/>
                                                    </div>
                                                </MenuItem>
                                            </div>
                                        </MenuItems>
                                    </transition>
                                </Menu>
                                <div class="flex ml-3"
                                     v-if="$can('view edit add money_sources') || $can('can edit and delete money sources') || $role('artwork admin')">
                                    <AddButtonSmall @click="openAddMoneySourceModal" :text="$t('New')"/>
                                </div>
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
                                      :displayed-text="$t('Sources with open tasks')"
                                      :method="deactivateOpenTasksFilter"/>
                    </div>
                    <ul role="list" class="mt-5 w-full">
                        <li v-for="(moneySource) in filteredMoneySources"
                            :key="moneySource.id"
                        >
                            <div class="py-5 flex flex-col justify-between border-b-2 border-gray-200 my-2"
                                 v-if="($page.props.myMoneySources.some(source => source.money_source_id === moneySource.id) || $canAny('view edit add money_sources','can edit and delete money sources'))">
                                <div class="flex flex-row w-full">
                                    <div class="flex w-full items-center">
                                        <div class="flex items-center w-full">
                                            <img v-if="moneySource.is_group" src="/Svgs/IconSvgs/icon_group_red.svg"
                                                 class=" h-6 w-6 ml-5" alt="groupIcon"/>
                                            <Link :class="moneySource.is_group ? 'ml-2' : 'ml-5'" :href="getEditHref(moneySource)"
                                                  class="sDark my-auto w-full">
                                                {{ moneySource.name }}
                                            </Link>
                                        </div>
                                        <div v-if="moneySource.group_id !== null"
                                             class="w-full flex items-center xxsLight subpixel-antialiased ml-14 mt-1">
                                            {{ $t('Belongs to')}}:
                                            {{
                                                this.moneySourceGroups.find(sourceGroup => sourceGroup.id === moneySource.group_id).name
                                            }}
                                        </div>
                                    </div>
                                    <div
                                        v-if="moneySource.pinned_by_users && moneySource.pinned_by_users.includes($page.props.user.id)"
                                        class="flex items-center xxsLight subpixel-antialiased ml-14 mt-1">
                                        <IconPinned  stroke-width="1.5" class="h-5 w-5 mr-4 text-primary"/>
                                    </div>
                                    <div class="flex">
                                        <BaseMenu>
                                            <MenuItem class="cursor-pointer" v-slot="{ active }">
                                                <a :href="getEditHref(moneySource)"
                                                   :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased capitalize']">
                                                    <IconEdit stroke-width="1.5"
                                                              class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                              aria-hidden="true"/>
                                                    {{ $t('edit')}}
                                                </a>
                                            </MenuItem>
                                            <MenuItem class="cursor-pointer" v-slot="{ active }"
                                                      v-if="getMemberInMoneySource(moneySource).write_access.includes($page.props.user.id) || getMemberInMoneySource(moneySource).competent.includes($page.props.user.id) || $can('view edit add money_sources') || $can('can edit and delete money sources') || $role('artwork admin')">
                                                <a @click="duplicateMoneySource(moneySource)"
                                                   :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <IconCopy stroke-width="1.5"
                                                              class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                              aria-hidden="true"/>
                                                    {{ $t('Duplicate')}}
                                                </a>
                                            </MenuItem>
                                            <MenuItem class="cursor-pointer" v-slot="{ active }"
                                                      v-if="getMemberInMoneySource(moneySource).write_access.includes($page.props.user.id) || getMemberInMoneySource(moneySource).competent.includes($page.props.user.id) || $can('view edit add money_sources') || $can('can edit and delete money sources') || $role('artwork admin')">
                                                <a @click="pinMoneySource(moneySource)"
                                                   :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <IconPin
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    {{moneySource.pinned_by_users && moneySource.pinned_by_users.includes($page.props.user.id) ? $t('Undo pinning') : $t('Pin')}}
                                                </a>
                                            </MenuItem>
                                            <MenuItem class="cursor-pointer" v-slot="{ active }"
                                                      v-if="getMemberInMoneySource(moneySource).write_access.includes($page.props.user.id) || getMemberInMoneySource(moneySource).competent.includes($page.props.user.id) || $can('can edit and delete money sources') || $role('artwork admin')">
                                                <a @click="openDeleteSourceModal(moneySource)"
                                                   :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <IconTrash stroke-width="1.5"
                                                               class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                               aria-hidden="true"/>
                                                    {{ $t('Delete')}}
                                                </a>
                                            </MenuItem>
                                        </BaseMenu>
                                    </div>
                                </div>
                                <div class="flex xxsLight items-center subpixel-antialiased">
                                    <div class="flex items-center pl-5 py-1 pr-1">
                                        {{ this.toCurrencyString(moneySource.amount + moneySource.sumOfPositions) }}€ /
                                        {{ this.toCurrencyString(moneySource.amount) }}€
                                    </div>
                                    <div class="" v-if="moneySource.funding_start_date && moneySource.funding_end_date">
                                        |
                                        Förderzeitraum: {{ formatDateString(moneySource.funding_start_date) }} -
                                        {{ formatDateString(moneySource.funding_end_date) }}
                                    </div>
                                </div>
                                <div v-if="moneySource.description" class="flex xxsLight items-center pl-5 subpixel-antialiased">
                                    {{ moneySource.description}}
                                </div>
                                <div class="flex items-center xxsLight pl-5 pt-1 subpixel-antialiased" v-if="moneySource.history[0]">
                                    Letzte Änderung: {{ moneySource.history[0]?.created_at }} von
                                    <NewUserToolTip class="ml-2" :height="6" :width="6" v-if="moneySource.history[0]?.changes[0]?.changed_by"
                                                    :user="moneySource.history[0]?.changes[0]?.changed_by" :id="moneySource.history[0]?.changes[0]?.changed_by?.id + moneySource.id"/>
                                    {{moneySource.history[0]?.changes[0]?.changed_by?.first_name}}
                                    {{moneySource.history[0]?.changes[0]?.changed_by?.last_name}}
                                </div>
                                <div class="ml-5 mt-2 flex flex-wrap">
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
        :title="$t('Delete funding source/group')"
        :description="$t('Are you sure you want to delete the funding source/group {0}?', [sourceToDelete.name])"
        @closed="afterConfirm(false)"
        @delete="afterConfirm(true)"/>

    <BaseModal @closed="closeTimeSpanFilterModal" v-if="timeSpanFilterModalVisible" modal-image="/Svgs/Overlays/illu_project_edit.svg" >
            <div class="mx-4">
                <h1 class="my-1 flex">
                    <div class="flex-grow headline1">
                        {{$t('Select period')}}
                    </div>
                </h1>
                <h2 class="xsLight mb-2 mt-8">
                    {{$t('Please select the time period in which the financial sources should be displayed.')}}
                </h2>
                <div class="flex flex-row justify-between">
                    <input class="w-1/2" type="date" v-model="timeSpanFilterStart"/>
                    <input class="w-1/2" type="date" v-model="timeSpanFilterEnd"/>
                </div>
                <div class="w-full flex justify-center my-3">
                    <FormButton :disabled="timeSpanFilterStart === null || timeSpanFilterEnd === null"
                               :text="$t('Filtering')"
                               @click="activateTimeSpanFilter"/>
                </div>
            </div>
    </BaseModal>

    <MoneySourceHistoryComponent
        @closed="closeMoneySourceHistoryModal"
        v-if="showMoneySourceHistory"
        :history="moneySourceToShowHistoryOf.history"
    ></MoneySourceHistoryComponent>
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
import {
    ChevronDownIcon,
    DotsVerticalIcon,
    SearchIcon,
    TrashIcon,
    XIcon,
    ArrowNarrowDownIcon,
    ArrowNarrowUpIcon
} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import CreateMoneySourceComponent from "@/Layouts/Components/CreateMoneySourceComponent.vue";
import {DuplicateIcon, PencilAltIcon} from "@heroicons/vue/outline";
import {Link} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import Input from "@/Layouts/Components/InputComponent.vue";
import {IconPin} from '@tabler/icons-vue';
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import Label from "@/Jetstream/Label.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import MoneySourceHistoryComponent from "@/Layouts/Components/MoneySourceHistoryComponent.vue";
import NewUserToolTip from "@/Layouts/Components/NewUserToolTip.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import IconLib from "@/Mixins/IconLib.vue";
import CurrencyFloatToStringFormatter from "@/Mixins/CurrencyFloatToStringFormatter.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";


export default defineComponent({
    mixins: [Permissions, IconLib, CurrencyFloatToStringFormatter],
    components: {
        BaseModal,
        BaseMenu,
        FormButton,
        AddButtonSmall,
        NewUserToolTip,
        MoneySourceHistoryComponent,
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
        IconPin,
        ArrowNarrowUpIcon,
        ArrowNarrowDownIcon,
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
            return this.sortMoneySources(filteredMoneySources);
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
        getMemberInMoneySource(moneySource) {
            const returnArray = {
                competent: [],
                write_access: []
            }
            moneySource.users.forEach((user) => {
                if (user.pivot.competent) {
                    returnArray.competent.push(user.id);
                }
                if (user.pivot.write_access) {
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
        sortMoneySources(array) {
            let compareFunction;

            // Grundlegender Vergleich für angepinnte Elemente
            const pinCompare = (a, b) => {
                const isAPinned = a.pinned_by_users && a.pinned_by_users.includes(this.$page.props.user.id);
                const isBPinned = b.pinned_by_users && b.pinned_by_users.includes(this.$page.props.user.id);

                if (isAPinned && !isBPinned) return -1;
                if (!isAPinned && isBPinned) return 1;
                return 0; // Beide gleich (entweder beide angepinnt oder keines von beiden)
            };

            // Sortierfunktionen je nach Sortiertyp
            switch (this.sortType) {
                case 'name':
                    compareFunction = (a, b) => {
                        let pinComparison = pinCompare(a, b);
                        if (pinComparison !== 0) return pinComparison;
                        let nameA = a.name.toLowerCase();
                        let nameB = b.name.toLowerCase();
                        return (nameA < nameB ? -1 : 1);
                    };
                    break;
                case 'funding_start_date':
                case 'funding_end_date':
                case 'created_at':
                    compareFunction = (a, b) => {
                        let pinComparison = pinCompare(a, b);
                        if (pinComparison !== 0) return pinComparison;
                        let dateA = new Date(a[this.sortType]);
                        let dateB = new Date(b[this.sortType]);
                        return (dateA - dateB);
                    };
                    break;
                default:
                    compareFunction = (a, b) => {
                        let pinComparison = pinCompare(a, b);
                        if (pinComparison !== 0) return pinComparison;
                        let nameA = a.name.toLowerCase();
                        let nameB = b.name.toLowerCase();
                        return (nameA < nameB ? -1 : 1);
                    };
            }

            if (this.sortOrder === 'descending') {
                compareFunction = ((originalCompareFunction) => (a, b) => -1 * originalCompareFunction(a, b))(compareFunction);
            }

            return array.sort(compareFunction);
        },
        changeSortAlgorithm(sortType) {
            if (this.sortType === sortType) {
                this.sortOrder = this.sortOrder === 'ascending' ? 'descending' : 'ascending';
            } else {
                this.sortType = sortType;
                this.sortOrder = 'ascending';
            }
        },
        closeMoneySourceHistoryModal(){
            this.showMoneySourceHistory = false;
            this.moneySourceToShowHistoryOf = null;
        },
        openMoneySourceHistoryModal(moneySource){
            this.moneySourceToShowHistoryOf = moneySource;
            this.showMoneySourceHistory = true;
        }

    },
    data() {
        return {
            moneySourceFilters: [{
                'name': this.$t('All funding sources & groups'),
                'type': 'all'
            }, {'name': this.$t('All sources of funding'), 'type': 'single'}, {
                'name': this.$t('All funding groups'),
                'type': 'group'
            }],
            moneySourceFilter: {'name': this.$t('All funding sources & groups'), 'type': 'all'},
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
            sortType: null,
            sortOrder: null,
            showMoneySourceHistory: false,
            moneySourceToShowHistoryOf: null,
        }
    }
})
</script>
