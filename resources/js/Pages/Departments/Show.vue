<template>
    <app-layout :title="$t('Team profile') + ' (' + department.name + ')'">
        <div class="max-w-screen-lg my-12 ml-20 mr-40">
            <div class="flex-wrap">
                <div class="flex">
                    <h2 class="font-lexend font-black text-[clamp(24px,3vw,30px)]/[34px] text-text">{{ $t('Team profile')}}</h2>
                </div>
                <div class="flex mt-12">
                    <Menu as="div" class=" relative">
                        <div>
                            <MenuButton class="flex items-center rounded-full">
                                <IconChevronDown v-if="teamForm.svg_name === ''"
                                                 class="ml-1 flex-shrink-0 mt-1 h-20 w-20 flex my-auto items-center font-semibold rounded-full shadow-sm text-white bg-black"></IconChevronDown>
                                <TeamIconCollection v-else class="h-24 w-24" :iconName="teamForm.svg_name"/>
                            </MenuButton>
                        </div>
                        <transition enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95">
                            <MenuItems
                                class="z-40 overflow-y-auto origin-top-right absolute right-0 mt-2 shadow-lg py-1 bg-surface-inverse ring-1 ring-black ring-opacity-5">
                                <MenuItem v-for="item in iconMenuItems" v-slot="{ active }">
                                    <button type="button" @click="teamForm.svg_name = item.iconName; editTeam()"
                                          :class="[active ? 'bg-text-inverse/10 ' : 'text-text-subtle', 'group flex w-full items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <TeamIconCollection class="h-16 w-16" :iconName="item.iconName"/>
                                    </button>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </Menu>
                    <div class="relative w-full ml-8 mt-8 w-3/4 max-w-xl">
                        <input id="teamName" v-model="teamForm.name" @focusout="editTeam" type="text"
                               class="peer pl-0 h-12 w-full text-xl font-bold focus:border-t-transparent focus:border-surface-inverse border-l-0 border-t-0 border-r-0 border-b-2 border-border text-text placeholder-text-subtle placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="teamName"
                               class="absolute left-0 text-sm -top-7 text-text-muted -top-3.5 transition-all subpixel-antialiased text-text-subtle peer-placeholder-shown:text-base peer-placeholder-shown:text-text-subtle peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                    </div>
                </div>
                <div class="flex items-center mt-16 mr-8">
                    <BaseUIButton variant="primary" hide-icon @click="openChangeTeamMembersModal">
                        {{ $t('Edit team')}}
                    </BaseUIButton>
                </div>

                <!-- Team Members Table -->
                <div class="mt-8">
                    <div v-if="teamForm.users.length === 0">
                        <span class="text-sm/5 font-bold text-text-subtle">{{ $t('No team members added yet')}}</span>
                    </div>
                    <BaseTable
                        v-else
                        :rows="teamForm.users"
                        :columns="userCols"
                        row-key="id"
                        empty-title="Keine Teammitglieder"
                        empty-message="No team members added yet"
                    >
                        <!-- Name (Avatar + Name) -->
                        <template #cell-name="{ row }">
                            <div class="flex items-center">
                                <UserPopoverTooltip :id="row.id" :user="row" height="11" width="11" />
                                <div class="ml-4">
                                    <div class="font-medium text-text">{{ row.first_name }} {{ row.last_name }}</div>
                                </div>
                            </div>
                        </template>

                        <!-- Position -->
                        <template #cell-position="{ row }">
                            <span class="text-text-muted">{{ row.position || '-' }}</span>
                        </template>

                        <!-- Actions -->
                        <template #row-actions="{ row }">
                            <BaseMenu has-no-offset white-menu-background>
                                <MenuItem v-slot="{ active }">
                                    <a @click="removeUserFromTeam(row)"
                                       :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-text-subtle', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased cursor-pointer']">
                                        <IconTrash
                                            class="mr-3 h-5 w-5 text-primaryText group-hover:text-accent-700"
                                            aria-hidden="true"/>
                                        {{ $t('Remove person from team')}}
                                    </a>
                                </MenuItem>
                            </BaseMenu>
                        </template>
                    </BaseTable>
                </div>
                <div class="flex mt-12">
                    <span @click="openDeleteTeamModal()" class="text-sm/5 font-bold text-text-subtle cursor-pointer">{{ $t('Delete team permanently')}}</span>
                </div>
            </div>

        </div>
        <!-- Team löschen Modal -->
        <ArtworkBaseModal
            v-if="deletingTeam"
            title="Delete Team"
            modal-size="sm:max-w-lg"
            @close="closeDeleteTeamModal"
        >
            <p class="text-sm text-text">
                {{ $t('Are you sure you want to delete the team { teamName } from the system?', { teamName: department.name }) }}
            </p>
            <template #footer>
                <BaseUIButton
                    label="No, not really"
                    is-cancel-button
                    hide-icon
                    @click="closeDeleteTeamModal"
                />
                <BaseUIButton
                    label="Delete"
                    variant="danger"
                    icon="IconTrash"
                    @click="deleteTeam"
                />
            </template>
        </ArtworkBaseModal>
        <!--Alle Nutzer aus Team löschen Modal -->
        <ArtworkBaseModal
            v-if="deletingAllTeamMembers"
            title="Delete all team members"
            modal-size="sm:max-w-lg"
            @close="closeDeleteAllTeamMembersModal"
        >
            <p class="text-sm text-text">
                {{ $t('Are you sure you want to remove all members of the team { teamName }?', { teamName: department.name }) }}
            </p>
            <template #footer>
                <BaseUIButton
                    label="No, not really"
                    is-cancel-button
                    hide-icon
                    @click="closeDeleteAllTeamMembersModal"
                />
                <BaseUIButton
                    label="Delete all team members"
                    variant="danger"
                    icon="IconTrash"
                    @click="deleteAllTeamMembers"
                />
            </template>
        </ArtworkBaseModal>
        <!-- Change TeamMember Modal -->
        <ArtworkBaseModal
            v-if="showChangeTeamMemberModal"
            title="Edit team members"
            description="Enter the name of the user you want to add to the team."
            modal-size="sm:max-w-lg"
            @close="closeChangeTeamMembersModal"
        >
            <div class="space-y-4">
                <div>
                    <BaseInput
                        id="userSearch"
                        v-model="user_query"
                        label="Name"
                        :placeholder="$t('Search for users')"
                        autocomplete="off"
                        autofocus
                    />
                    <ul
                        v-if="user_search_results.length > 0 && user_query.length > 0"
                        class="mt-2 max-h-60 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
                    >
                        <li v-for="user in user_search_results" :key="user.id">
                            <button
                                type="button"
                                class="block w-full cursor-pointer truncate px-3 py-2 text-left text-sm font-medium text-text transition-colors duration-150 hover:bg-surface-hover focus-visible:bg-surface-hover motion-reduce:transition-none"
                                @click="addUserToTeamUsersArray(user)"
                            >
                                {{ user.first_name }} {{ user.last_name }}
                            </button>
                        </li>
                    </ul>
                </div>
                <ul
                    v-if="teamForm.users.length > 0"
                    class="max-h-72 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
                >
                    <li
                        v-for="user in teamForm.users"
                        :key="user.id"
                        class="flex items-center justify-between gap-x-3 px-3 py-2"
                    >
                        <div class="flex min-w-0 items-center gap-x-3">
                            <UserPopoverTooltip :id="user.id" :user="user" height="8" width="8" />
                            <span class="truncate text-sm font-medium text-text">
                                {{ user.first_name }} {{ user.last_name }}
                            </span>
                        </div>
                        <button
                            type="button"
                            class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 hover:bg-surface-hover hover:text-danger motion-reduce:transition-none"
                            :aria-label="$t('Remove person from team')"
                            @click="deleteUserFromTeam(user)"
                        >
                            <IconX class="size-4" stroke-width="1.5" aria-hidden="true"/>
                        </button>
                    </li>
                </ul>
                <p v-else class="text-xs text-text-muted">
                    {{ $t('No team members added yet') }}
                </p>
            </div>
            <template #footer>
                <BaseUIButton
                    label="Cancel"
                    is-cancel-button
                    hide-icon
                    :disabled="teamForm.processing"
                    @click="closeChangeTeamMembersModal"
                />
                <BaseUIButton
                    label="Save"
                    variant="primary"
                    icon="IconDeviceFloppy"
                    :processing="teamForm.processing"
                    @click="editTeam"
                />
            </template>
        </ArtworkBaseModal>
    </app-layout>
</template>

<script>
import {IconCheck, IconChevronDown, IconCircleX, IconDotsVertical, IconEdit, IconTrash, IconX} from "@tabler/icons-vue";


import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import AppLayout from '@/Layouts/AppLayout.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import JetButton from "@/Jetstream/Button.vue";
import JetInput from "@/Jetstream/Input.vue";
import JetInputError from "@/Jetstream/InputError.vue";
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import {router} from "@inertiajs/vue3";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTable from "@/Artwork/Table/BaseTable.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const iconMenuItems = [
    {iconName: 'icon_ausstellung'},
    {iconName: 'icon_ausstellung_foto'},
    {iconName: 'icon_bildung_bibliothek'},
    {iconName: 'icon_bildung_kulturell'},
    {iconName: 'icon_dienst_abend'},
    {iconName: 'icon_dienst_kasse'},
    {iconName: 'icon_dienst_reinigung'},
    {iconName: 'icon_dienst_sicherheit'},
    {iconName: 'icon_dramaturgie'},
    {iconName: 'icon_dramaturgie_kurator'},
    {iconName: 'icon_dramaturgie_tanz'},
    {iconName: 'icon_einhorn'},
    {iconName: 'icon_festival'},
    {iconName: 'icon_kommunikation_marketing'},
    {iconName: 'icon_kommunikation_vertrieb'},
    {iconName: 'icon_orga_finanzen'},
    {iconName: 'icon_orga_kuenstlerischesbuero'},
    {iconName: 'icon_orga_leitung'},
    {iconName: 'icon_orga_personal'},
    {iconName: 'icon_orga_sekretariat'},
    {iconName: 'icon_orga_verwaltung'},
    {iconName: 'icon_technik'},
    {iconName: 'icon_technik_audiovideo'},
    {iconName: 'icon_technik_buehne'},
    {iconName: 'icon_technik_haus'},
    {iconName: 'icon_technik_licht'},
    {iconName: 'icon_technik_veranstaltung'},
    {iconName: 'icon_vermietung'},
]

export default {
    mixins: [Permissions],
    name: "Show",
    props: ['department'],
    components: {
        ArtworkBaseModal,
        BaseInput,
        BaseMenu,
        BaseTable,
        BaseUIButton,
        UserPopoverTooltip,
        TeamIconCollection,
        AppLayout,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        IconX,
        IconEdit,
        IconTrash,
        IconDotsVertical,
        SvgCollection,
        IconCircleX,
        JetButton,
        JetInput,
        JetInputError,
        IconCheck,
        IconChevronDown,
        UserTooltip
    },
    data() {
        return {
            showChangeTeamMemberModal: false,
            deletingTeam: false,
            showSuccess: false,
            deletingAllTeamMembers: false,
            user_query: "",
            user_search_results: [],
            teamForm: this.$inertia.form({
                _method: 'PUT',
                name: this.department.name,
                svg_name: this.department.svg_name,
                users: this.department.users,
            }),
            userCols: [
                { key: 'name', label: 'Name', sortable: false },
                { key: 'position', label: 'Position', sortable: false },
            ],
        }
    },
    methods: {
        openDeleteTeamModal() {
            this.deletingTeam = true;
        },
        closeDeleteTeamModal() {
            this.deletingTeam = false;
        },
        openDeleteAllTeamMembersModal() {
            this.deletingAllTeamMembers = true;
        },
        closeDeleteAllTeamMembersModal() {
            this.deletingAllTeamMembers = false;
        },
        deleteTeam() {
            router.delete(`/departments/${this.department.id}`);
            this.closeDeleteTeamModal()
        },
        openChangeTeamMembersModal() {
            this.showChangeTeamMemberModal = true;
        },
        closeChangeTeamMembersModal() {
            this.showChangeTeamMemberModal = false;
        },
        deleteUserFromTeam(user) {
            this.teamForm.users.splice(this.teamForm.users.indexOf(user), 1);
        },
        removeUserFromTeam(user) {
            this.teamForm.users.splice(this.teamForm.users.indexOf(user), 1);
            this.teamForm.patch(route('departments.edit', {department: this.department.id}));
        },
        showSuccessButton() {
            this.showSuccess = true;
            setTimeout(() => {
                this.showSuccess = false
            }, 1000)
        },
        editTeam() {

            // Modal erst nach erfolgreichem Speichern schließen
            this.teamForm.patch(route('departments.edit', {department: this.department.id}), {
                onSuccess: () => this.closeChangeTeamMembersModal(),
            });
            this.showSuccessButton();
        },
        deleteAllTeamMembers() {
            this.teamForm.users = [];
            this.teamForm.patch(route('departments.edit', {department: this.department.id}));
            this.closeDeleteAllTeamMembersModal();
        },
        addUserToTeamUsersArray(user) {
            for (let teamUser of this.teamForm.users) {
                //if User is already in Team, do nothing
                if (user.id === teamUser.id) {
                    this.user_query = ""
                    return;
                }
            }
            this.teamForm.users.push(user);
            this.user_query = "";
            this.user_search_results = []
        },
    },
    watch: {
        user_query: {
            handler() {
                if (this.user_query.length > 0) {
                    axios.get('/users/search', {
                        params: {query: this.user_query}
                    }).then(response => {
                        this.user_search_results = response.data
                    })
                }
            },
            deep: true
        }
    },
    setup() {
        return {
            iconMenuItems
        }
    }
}
</script>

<style scoped>

</style>
