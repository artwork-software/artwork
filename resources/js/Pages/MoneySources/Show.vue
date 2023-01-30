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
                                        <a @click="duplicateMoneySource(this.moneySource)"
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
                                        <a @click="openDeleteSourceModal(this.moneySource)"
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
                         v-if="moneySource.users">
                        <div class="flex items-center">
                            <div class="mr-2">
                            zuständig:
                            </div>
                            <div class="-ml-3" v-for="user in moneySource.users">
                                <img v-if="user"
                                     :data-tooltip-target="user?.id"
                                     :src="user?.profile_photo_url"
                                     :alt="user?.name"
                                     class="ml-3 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                                <UserTooltip v-if="user"
                                             :user="user"/>
                            </div>
                        </div>
                    </div>
                    <div class="flex mt-2 mx-4 xsDark items-center">
                        erstellt von
                        <img v-if="moneySource.creator"
                             :data-tooltip-target="moneySource.creator?.id"
                             :src="moneySource.creator?.profile_photo_url"
                             :alt="moneySource.creator?.first_name"
                             class="ml-2 ring-white ring-2 rounded-full h-7 w-7 object-cover"/>
                        <UserTooltip v-if="moneySource.creator"
                                     :user="moneySource.creator"/>
                    </div>
                    <button class="ml-4 mt-3 subpixel-antialiased flex items-center linkText cursor-pointer"
                            @click="openMoneySourceHistoryModal()">
                        <ChevronRightIcon
                            class="-mr-0.5 h-4 w-4  group-hover:text-white"
                            aria-hidden="true"/>
                        Verlauf ansehen
                    </button>
                </div>
                <div class="xsDark mt-4 flex items-center" v-if="moneySource.group_id">
                    <img src="/Svgs/IconSvgs/icon_group_red.svg"
                         class=" h-4 w-4" alt="groupIcon"/>
                    <div class="ml-2">
                    Gehört zu
                    </div>
                    <Link v-if="moneySource.group_id" :href="getEditHref(moneySource.group_id)" class="linkText ml-1 mt-0.5">
                        {{moneySource.moneySourceGroup.name}}
                    </Link>
                </div>
                <div class="mt-3 xsDark" v-if="moneySource.start_date && moneySource.end_date">
                    Laufzeit: {{formatDate(moneySource.start_date)}} - {{formatDate(moneySource.end_date)}}
                </div>
                <div class="mt-2 xsDark" v-if="moneySource.source_name">
                    Quelle: {{ moneySource.source_name}}
                </div>
                <div class="mr-14 my-3 subpixel-antialiased text-secondary">
                    {{ moneySource.description }}
                </div>
                <div class="mt-12 flex">
                    <div class="w-1/2 xsLight uppercase border-r-2">
                        Ursprungsvolumen
                        <div class="bigNumber my-4">
                            {{ currencyFormat(moneySource.amount) }}
                        </div>
                    </div>
                    <div class="w-1/2 xsLight uppercase ml-6">
                        Noch Verfügbar
                        <div class="bigNumber my-4" :class="moneySource.amount_available < 0 ? 'text-red-500' : ''">
                            {{ currencyFormat(moneySource.amount_available) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Div with Bg-Color -->
        <div class="w-full h-full mb-48" v-if="moneySource.is_group">
            <div class="max-w-screen-2xl bg-lightBackgroundGray">
                <div class="headline4 py-12 ml-20">
                Untergeordnete Finanzierungsquellen
                </div>
            </div>
        </div>
        <div class="w-full h-full mb-48" v-else>
            <div class="max-w-screen-2xl bg-lightBackgroundGray">
                <div class="headline4 py-12 ml-20">
                    Verlinkte Positionen
                </div>

                <div class="w-full ml-20 py-12">
                    <div class="flex border-b border-gray-300 pb-5 pt-5" v-for="position in moneySource.positions">
                        <div class="sum w-72 text-2xl" :class="position.type === 'COST' ? 'text-red-500' : ''">
                            <span v-if="position.type === 'EARNING'">+</span><span v-else>-</span> {{ currencyFormat(position.value) }}
                        </div>
                        <div class="project">
                            <div class="text-gray-400"><a :href="'/projects/' + position.project.id + '?openTab=budget'" class="text-buttonBlue ">{{ position.project.name }}</a> |<span class="ml-2 text-gray-400 text-sm">{{ position.created_at }}</span></div>
                            <div class="text-gray-400 text-sm mt-2">{{ position.mainPositionName }} | {{ position.subPositionName }} | Position</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <edit-money-source-component
            v-if="showEditMoneySourceModal"
            @closed="onEditMoneySourceModalClose()"
            :moneySource="this.moneySource"
            :moneySources="this.moneySources"
            :moneySourceGroups="this.moneySourceGroups"
        />
        <BaseSidenav :show="show" @toggle="this.show =! this.show">
            <MoneySourceSidenav :users="moneySource.users" :tasks="moneySource.tasks" :money_source="moneySource"></MoneySourceSidenav>
        </BaseSidenav>
    </app-layout>
    <confirmation-component
        v-if="showDeleteSourceModal"
        confirm="Löschen"
        titel="Finanzierungsquelle/gruppe löschen"
        :description="'Bist du sicher, dass du die Finanzierungsquelle/Gruppe ' + this.sourceToDelete.name + ' löschen möchtest?'"
        @closed="afterConfirm"/>


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
import {Link} from "@inertiajs/inertia-vue3";
import EditMoneySourceComponent from "@/Layouts/Components/EditMoneySourceComponent";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent";
import BaseSidenav from "@/Layouts/Components/BaseSidenav.vue";
import MoneySourceSidenav from "@/Layouts/Components/MoneySourceSidenav.vue";



export default {
    name: "MoneySourceShow",
    props: ['moneySource','moneySourceGroups','moneySources'],
    components: {
        MoneySourceSidenav,
        BaseSidenav,
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
        SvgCollection,
        Link,
        EditMoneySourceComponent,
        ConfirmationComponent,
    },
    computed: {},
    data() {
        return {
            showEditMoneySourceModal: false,
            showDeleteSourceModal: false,
            sourceToDelete: null,
            show: false,
        }
    },
    methods: {
        currencyFormat(number){
            const formatter = new Intl.NumberFormat('de-DE', {
                style: 'currency',
                currency: 'EUR',
            });
            return formatter.format(number);
        },
        getEditHref(moneySourceId) {
            return route('money_sources.show', {moneySource: moneySourceId});
        },
        formatDate(isoDate) {
            return isoDate.substring(8, 10) + '.' + isoDate.substring(5, 7) + '.' + isoDate.substring(0, 4)
        },
        openEditMoneySourceModal(){
            this.showEditMoneySourceModal = true;
        },
        onEditMoneySourceModalClose(){
            this.showEditMoneySourceModal = false;
        },
        duplicateMoneySource(moneySource){
            this.$inertia.post(`/money_sources/${moneySource.id}/duplicate`);
        },
        deleteMoneySource(moneySource){
            this.$inertia.delete(`/money_sources/${moneySource.id}`);
            this.showDeleteSourceModal = false;
        },
        openDeleteSourceModal(moneySourceToDelete){
            this.sourceToDelete = moneySourceToDelete;
            this.showDeleteSourceModal = true;
        },
        async afterConfirm(bool) {
            if (!bool) return this.showDeleteSourceModal = false;

            this.deleteMoneySource(this.sourceToDelete)
        },
    },
    setup() {
        return {}
    }
}

</script>

<style scoped>
</style>
