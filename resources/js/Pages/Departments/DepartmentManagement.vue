<template>

      <UserHeader title="Accommodations" description="Manage your accommodations.">
            <template #tabBar>
                <!-- Toolbar -->
                <ToolbarHeader
                    :icon="IconUsersGroup"
                    :title="$t('Departments')"
                    :description="`${totalDepartments} ${$t('Departments')}`"
                    icon-bg-class="bg-indigo-600/10 text-indigo-700"
                    v-model="department_query"
                    :search-enabled="true"
                    :search-label="$t('Search for teams')"
                    :search-tooltip="$t('Search')"
                >
                    <template #actions>
                        <button class="ui-button-add" @click="openAddTeamModal">
                            <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                            {{ $t('Create Team') }}
                        </button>
                    </template>
                </ToolbarHeader>
            </template>
          <template #default>
              <!-- Tabelle -->
              <div class="mt-6">
                  <BaseTable
                      :rows="rows"
                      :columns="cols"
                      row-key="id"
                      :total="totalForTable"
                      :page-size="perPage"
                      v-model:page="currentPage"
                      @page-change="onPageChange"
                      :empty-title="$t('No teams')"
                      :empty-message="$t('There are currently no teams.')"
                  >
                      <!-- Team (Icon + Name) -->
                      <template #cell-department="{ row }">
                          <div class="flex items-center">
                              <TeamIconCollection class="h-12 w-12 shrink-0" :iconName="row.svg_name" alt="TeamIcon" />
                              <Link :href="getEditHref(row)" class="ml-4 my-auto">
                                  <p class="font-medium text-gray-900">{{ row.name }}</p>
                              </Link>
                          </div>
                      </template>

                      <!-- Mitglieder (Avatare + Overflow) -->
                      <template #cell-members="{ row }">
                          <div class="flex items-center">
                              <div class="flex items-center -mr-3" v-for="user in row.users?.slice(0,9)" :key="user.id">
                                  <UserPopoverTooltip :id="user.id" :user="user" height="9" width="9" />
                              </div>

                              <div v-if="(row.users?.length || 0) >= 9" class="ml-4">
                                  <Menu as="div" class="relative">
                                      <div>
                                          <MenuButton class="flex items-center rounded-full focus:outline-none">
                                              <div
                                                  class="ml-1 h-9 w-9 flex items-center justify-center rounded-full shadow-sm text-white bg-black ring-2 ring-white"
                                              >
                                                  <span class="text-sm font-semibold">+{{ (row.users?.length || 0) - 9 }}</span>
                                              </div>
                                          </MenuButton>
                                      </div>
                                      <transition enter-active-class="transition ease-out duration-100" enter-from-class="opacity-0"
                                                  enter-to-class="opacity-100" leave-active-class="transition ease-in duration-75"
                                                  leave-from-class="opacity-100" leave-to-class="opacity-0">
                                          <MenuItems
                                              class="absolute right-0 mt-2 w-72 max-h-48 overflow-y-auto origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                          >
                                              <MenuItem v-for="user in row.users" :key="user.id" v-slot="{ active }">
                                                  <div :class="[active ? 'bg-gray-50' : '', 'flex items-center px-4 py-2 text-sm cursor-default']">
                                                      <img class="h-9 w-9 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                                                      <span class="ml-4 text-gray-700">
                                                        {{ user.first_name }} {{ user.last_name }}
                                                      </span>
                                                  </div>
                                              </MenuItem>
                                          </MenuItems>
                                      </transition>
                                  </Menu>
                              </div>
                          </div>
                      </template>

                      <!-- Aktionen -->
                      <template #row-actions="{ row }">
                          <BaseMenu classes="!w-fit" has-no-offset white-menu-background>
                              <BaseMenuItem :title="$t('Edit team')" :icon="IconEdit" as-link :link="getEditHref(row)" white-menu-background />
                              <BaseMenuItem :title="$t('Remove all team members')" :icon="IconTrash" @click="openDeleteAllTeamMembersModal(row)" white-menu-background />
                              <BaseMenuItem :title="$t('Delete Team')" :icon="IconTrash" @click="openDeleteTeamModal(row)" white-menu-background />
                          </BaseMenu>
                      </template>
                  </BaseTable>
              </div>

              <!-- Team erstellen Modal -->
              <BaseModal v-if="addingTeam" @closed="closeAddTeamModal" modal-image="/Svgs/Overlays/illu_team_new.svg">
                  <div class="mx-4">
                      <div class="headline1 my-2">{{ $t('Create New Team') }}</div>
                      <div class="xsLight subpixel-antialiased mt-4">{{ $t('Create a fixed team/department.') }}</div>

                      <div class="mt-12">
                          <div class="flex">
                              <Menu as="div" class="relative">
                                  <div>
                                      <MenuButton :class="[form.svg_name === '' ? 'border border-gray-400' : '']"
                                                  class="items-center rounded-full focus:outline-none h-12 w-12">
                                          <label v-if="form.svg_name === ''" class="text-gray-400 text-xs">Icon*</label>
                                          <IconChevronDown v-if="form.svg_name === ''" class="h-4 w-4 mx-auto text-black" />
                                          <TeamIconCollection v-else class="h-12 w-12" :iconName="form.svg_name" alt="TeamIcon" />
                                      </MenuButton>
                                  </div>
                                  <transition enter-active-class="transition-enter-active" enter-from-class="transition-enter-from"
                                              enter-to-class="transition-enter-to" leave-active-class="transition-leave-active"
                                              leave-from-class="transition-leave-from" leave-to-class="transition-leave-to">
                                      <MenuItems
                                          class="z-40 absolute mt-2 h-56 w-24 overflow-y-auto origin-top-right rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                      >
                                          <MenuItem v-for="item in iconMenuItems" :key="item.iconName" v-slot="{ active }">
                                              <div @click="form.svg_name = item.iconName"
                                                   :class="[active ? 'bg-gray-50' : '','px-3 py-2']">
                                                  <TeamIconCollection class="h-14 w-14" :iconName="item.iconName" alt="TeamIcon" />
                                              </div>
                                          </MenuItem>
                                      </MenuItems>
                                  </transition>
                              </Menu>

                              <div class="relative my-auto w-full ml-8 mr-12">
                                  <input id="name" v-model="form.name" type="text"
                                         class="peer pl-0 h-12 w-full border-b-2 border-gray-300 focus:border-indigo-600 focus:ring-0"
                                         />
                                  <label for="name"
                                         class="absolute left-0 -top-3.5 text-sm text-gray-600 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm">
                                      {{ $t('Name of the team*') }}
                                  </label>
                              </div>
                          </div>
                          <span v-if="form.svg_name === ''" class="text-red-500 text-xs mt-2">Icon auswählen notwendig*</span>

                          <div class="mt-12">
                              <div class="headline2 my-2">{{ $t('Add users') }}</div>
                              <div class="xsLight subpixel-antialiased">{{ $t('Enter the name of the user you want to add to the team.') }}</div>

                              <div class="mt-6 relative">
                                  <div class="my-auto w-full">
                                      <input id="userSearch" v-model="user_query" type="text" autocomplete="off"
                                             class="peer pl-0 h-12 w-full border-b-2 border-gray-300 focus:border-indigo-600 focus:ring-0"
                                             />
                                      <label for="userSearch"
                                             class="absolute left-0 -top-3.5 text-sm text-gray-600 transition-all peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm">
                                          {{ $t('Name') }}
                                      </label>
                                  </div>

                                  <transition leave-active-class="transition ease-in duration-100"
                                              leave-from-class="opacity-100" leave-to-class="opacity-0">
                                      <div v-if="user_search_results.length > 0 && user_query.length > 0"
                                           class="absolute z-10 mt-1 w-full max-h-60 overflow-auto bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                          <div class="border-gray-200">
                                              <div v-for="(user, index) in user_search_results" :key="index" class="flex items-center cursor-pointer">
                                                  <div class="flex-1 text-sm py-3">
                                                      <p @click="addUserToAssignedUsersArray(user)" class="font-bold px-4 hover:border-l-4 hover:border-l-emerald-500">
                                                          {{ user.first_name }} {{ user.last_name }}
                                                      </p>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                  </transition>
                              </div>
                          </div>

                          <div class="mt-4 space-y-3">
                              <div v-for="(user,index) in form.assigned_users" :key="user.id"
                                   class="flex items-center text-primary">
                                  <img class="h-11 w-11 rounded-full object-cover" :src="user.profile_photo_url" alt=""/>
                                  <span class="ml-4">{{ user.first_name }} {{ user.last_name }}</span>
                                  <button type="button" class="ml-2" @click="deleteUserFromTeam(index)">
                                      <span class="sr-only">User aus Team entfernen</span>
                                      <IconX class="h-5 w-5 hover:text-red-600"/>
                                  </button>
                              </div>
                          </div>

                          <div class="w-full text-center mt-6">
                              <FormButton @click="addTeam" :disabled="form.name === '' || form.svg_name === ''" :text="$t('Create Team')" />
                          </div>
                      </div>
                  </div>
              </BaseModal>

              <!-- Alle Mitglieder aus Team löschen Modal -->
              <BaseModal v-if="deletingAllTeamMembers" @closed="closeDeleteAllTeamMembersModal" modal-image="/Svgs/Overlays/illu_warning.svg">
                  <div class="mx-4">
                      <div class="headline1 my-2">{{ $t('Delete all team members') }}</div>
                      <div class="errorText mt-4">
                          {{ $t('Are you sure you want to remove all members of the team { teamName }?', { teamName: teamToDeleteAllMembers?.name }) }}
                      </div>
                      <div class="flex justify-between mt-6">
                          <button class="bg-white inline-flex items-center px-6 py-3 border text-base font-bold shadow-sm text-gray-800"
                                  @click="deleteAllTeamMembers">
                              {{ $t('Delete') }}
                          </button>
                          <div class="flex my-auto">
                              <span @click="closeDeleteAllTeamMembersModal" class="xsLight cursor-pointer">{{ $t('No, not really') }}</span>
                          </div>
                      </div>
                  </div>
              </BaseModal>

              <!-- Team löschen Modal -->
              <BaseModal v-if="deletingTeam" @closed="closeDeleteTeamModal" modal-image="/Svgs/Overlays/illu_warning.svg">
                  <div class="mx-4">
                      <div class="headline1 mt-6 my-2">{{ $t('Delete Team') }}</div>
                      <div class="errorText">
                          {{ $t('Are you sure you want to delete the team { teamName } from the system?', { teamName: teamToDelete?.name }) }}
                      </div>
                      <div class="flex justify-between mt-6">
                          <button class="bg-white inline-flex items-center px-6 py-3 border text-base font-bold shadow-sm text-gray-800"
                                  @click="deleteTeam">
                              {{ $t('Delete Team') }}
                          </button>
                          <div class="flex my-auto">
                              <span @click="closeDeleteTeamModal" class="xsLight cursor-pointer">{{ $t('No, not really') }}</span>
                          </div>
                      </div>
                  </div>
              </BaseModal>

              <!-- Success Modal -->
              <SuccessModal
                  :show="showSuccess"
                  @closed="closeSuccessModal"
                  :title="successHeading"
                  :description="$t('The changes have been saved successfully.')"
                  :button="$t('Close')"
              />
          </template>
      </UserHeader>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { router, useForm, Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

// Neue, wiederverwendbare Komponenten
import ToolbarHeader from "@/Artwork/Toolbar/ToolbarHeader.vue";
import BaseTable, { type TableColumn } from '@/Artwork/Table/BaseTable.vue'

// Deine bestehenden Komponenten
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import BaseModal from '@/Components/Modals/BaseModal.vue'
import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import SuccessModal from '@/Layouts/Components/General/SuccessModal.vue'
import FormButton from '@/Layouts/Components/General/Buttons/FormButton.vue'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'

// Icons
import {IconUsersGroup, IconCirclePlus, IconTrash, IconEdit, IconChevronDown, IconX} from '@tabler/icons-vue'
import UserHeader from "@/Pages/Users/UserHeader.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

// Props
const props = defineProps<{ departments: any; users: any }>()

const { t: $t } = useI18n()

/* ---------- Suche ---------- */
const department_query = ref<string>('')
const department_search_results = ref<any[]>([])
watch(department_query, async (q) => {
    if (!q || q.length === 0) {
        department_search_results.value = []
        return
    }
    const res = await axios.get('/departments/search', { params: { query: q } })
    department_search_results.value = res.data
})

/* ---------- Daten & Pagination ---------- */
const departmentsData = computed<any[]>(() => props.departments?.data ?? props.departments ?? [])
const rows = computed<any[]>(() => (department_query.value.length > 0 ? department_search_results.value : departmentsData.value))

const totalDepartments = computed<number>(() => props.departments?.total ?? departmentsData.value.length)
const perPage = computed<number | null>(() => props.departments?.per_page ?? null)
const totalForTable = computed<number | null>(() => (props.departments?.total ?? null))

const currentPage = ref<number>(props.departments?.current_page ?? 1)
watch(() => props.departments?.current_page, (v) => { if (v) currentPage.value = v })

function onPageChange({ page: newPage, pageSize }: { page: number; pageSize: number }) {
    // bestehende Query-Parameter übernehmen (Filter etc.)
    const currentQuery = Object.fromEntries(new URLSearchParams(window.location.search) as any)
    router.get(
        route('departments.index'),
        { ...currentQuery, page: newPage, per_page: pageSize },
        { preserveState: true, preserveScroll: true, replace: true, only: ['departments'] }
    )
}

/* ---------- Spalten ---------- */
const cols = ref<TableColumn[]>([
    { key: 'department', label: 'Department' },
    { key: 'members', label: 'Members', width: '260px' },
])

/* ---------- Links / Actions ---------- */
function getEditHref(department: any) {
    return route('departments.show', { department: department.id })
}

/* ---------- Realtime Reload ---------- */
onMounted(() => {
    try {
        Echo?.private('departments')?.listen('DepartmentUpdated', () => {
            router.reload({ only: ['departments'] })
        })
    } catch (_) { /* Echo optional */ }
})

/* ---------- Modals & Forms ---------- */
const addingTeam = ref(false)
const deletingTeam = ref(false)
const teamToDelete = ref<any | null>(null)
const deletingAllTeamMembers = ref(false)
const teamToDeleteAllMembers = ref<any | null>(null)
const showSuccess = ref(false)
const successHeading = ref('')

const form = useForm({
    svg_name: '',
    name: '',
    assigned_users: [] as any[],
})

const deleteMembersForm = useForm({
    _method: 'PUT',
    users: [] as number[],
})

function openAddTeamModal() {
    addingTeam.value = true
}
function closeAddTeamModal() {
    addingTeam.value = false
    form.assigned_users = []
    form.name = ''
    form.svg_name = ''
}

function showSuccessModal(type: 'add' | 'delete' | 'update') {
    successHeading.value =
        type === 'add' ? $t('Team successfully created') :
            type === 'delete' ? $t('Team successfully deleted') :
                $t('Team successfully processed')
    showSuccess.value = true
    setTimeout(() => closeSuccessModal(), 2000)
}
function closeSuccessModal() { showSuccess.value = false }

function addTeam() {
    form.post(route('departments.store'), {
        onSuccess: () => {
            closeAddTeamModal()
            showSuccessModal('add')
        }
    })
}

function openDeleteTeamModal(team: any) {
    teamToDelete.value = team
    deletingTeam.value = true
}
function closeDeleteTeamModal() {
    deletingTeam.value = false
    teamToDelete.value = null
}
function deleteTeam() {
    if (!teamToDelete.value) return
    router.delete(route('departments.destroy', { department: teamToDelete.value.id }), {
        onSuccess: () => {
            closeDeleteTeamModal()
            showSuccessModal('delete')
        }
    })
}

function openDeleteAllTeamMembersModal(team: any) {
    teamToDeleteAllMembers.value = team
    deletingAllTeamMembers.value = true
}
function closeDeleteAllTeamMembersModal() {
    deletingAllTeamMembers.value = false
    teamToDeleteAllMembers.value = null
}
function deleteAllTeamMembers() {
    if (!teamToDeleteAllMembers.value) return
    deleteMembersForm.patch(route('departments.remove.members', { department: teamToDeleteAllMembers.value.id }), {
        onSuccess: () => {
            closeDeleteAllTeamMembersModal()
        }
    })
}

/* ---------- User-Suche im Modal ---------- */
const user_query = ref<string>('')
const user_search_results = ref<any[]>([])
watch(user_query, async (q) => {
    if (!q || q.length === 0) {
        user_search_results.value = []
        return
    }
    const res = await axios.get('/users/search', { params: { query: q } })
    user_search_results.value = res.data
})

function addUserToAssignedUsersArray(user: any) {
    if (form.assigned_users.some(u => u.id === user.id)) {
        user_query.value = ''
        return
    }
    form.assigned_users.push(user)
    user_query.value = ''
}
function deleteUserFromTeam(index: number) {
    form.assigned_users.splice(index, 1)
}

/* ---------- Icon-Auswahl ---------- */
const iconMenuItems = [
    {iconName: 'icon_ausstellung'}, {iconName: 'icon_ausstellung_foto'}, {iconName: 'icon_bildung_bibliothek'},
    {iconName: 'icon_bildung_kulturell'}, {iconName: 'icon_dienst_abend'}, {iconName: 'icon_dienst_kasse'},
    {iconName: 'icon_dienst_reinigung'}, {iconName: 'icon_dienst_sicherheit'}, {iconName: 'icon_dramaturgie'},
    {iconName: 'icon_dramaturgie_kurator'}, {iconName: 'icon_dramaturgie_tanz'}, {iconName: 'icon_einhorn'},
    {iconName: 'icon_festival'}, {iconName: 'icon_kommunikation_marketing'}, {iconName: 'icon_kommunikation_vertrieb'},
    {iconName: 'icon_orga_finanzen'}, {iconName: 'icon_orga_kuenstlerischesbuero'}, {iconName: 'icon_orga_leitung'},
    {iconName: 'icon_orga_personal'}, {iconName: 'icon_orga_sekretariat'}, {iconName: 'icon_orga_verwaltung'},
    {iconName: 'icon_technik'}, {iconName: 'icon_technik_audiovideo'}, {iconName: 'icon_technik_buehne'},
    {iconName: 'icon_technik_haus'}, {iconName: 'icon_technik_licht'}, {iconName: 'icon_technik_veranstaltung'},
    {iconName: 'icon_vermietung'},
]
</script>
