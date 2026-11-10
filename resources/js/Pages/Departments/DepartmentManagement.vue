<template>

      <UserHeader title="Teams" description="Manage your teams.">
            <template #tabBar>
                <!-- Toolbar -->
                <ToolbarHeader
                    band
                    :icon="IconUsersGroup"
                    :title="$t('Departments')"
                    :description="`${totalDepartments} ${$t('Departments')}`"
                    v-model="department_query"
                    :search-enabled="true"
                    :search-label="$t('Search for teams')"
                    :search-tooltip="$t('Search')"
                >
                    <template #actions>
                        <BaseUIButton variant="primary" on-band hide-icon @click="openAddTeamModal">
                            <component :is="IconCirclePlus" stroke-width="1" class="size-5" />
                            {{ $t('Create Team') }}
                        </BaseUIButton>
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
                                  <p class="font-medium text-text">{{ row.name }}</p>
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
                                                  <div :class="[active ? 'bg-surface-sunken' : '', 'flex items-center px-4 py-2 text-sm cursor-default']">
                                                      <img class="h-9 w-9 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                                                      <span class="ml-4 text-text-muted">
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
              <ArtworkBaseModal
                  v-if="addingTeam"
                  title="Create New Team"
                  description="Create a fixed team/department."
                  modal-size="sm:max-w-lg"
                  @close="closeAddTeamModal"
              >
                  <!-- bewusst kein <form>: Enter in der Nutzersuche soll das Team nicht anlegen -->
                  <div class="space-y-5">
                      <div>
                          <div class="flex items-end gap-x-4">
                              <div class="shrink-0">
                                  <span class="mb-1 block font-lexend text-xs font-medium text-text-muted">
                                      {{ $t('Icon') }} <span class="text-danger">*</span>
                                  </span>
                                  <Menu as="div" class="relative">
                                      <MenuButton
                                          class="flex size-12 cursor-pointer items-center justify-center rounded-lg border bg-surface transition-colors duration-150 hover:bg-surface-hover motion-reduce:transition-none"
                                          :class="form.svg_name === '' ? 'border-border' : 'border-border-subtle'"
                                          :aria-label="$t('Icon')"
                                      >
                                          <IconChevronDown v-if="form.svg_name === ''" class="size-4 text-text-subtle" stroke-width="1.5" aria-hidden="true" />
                                          <TeamIconCollection v-else class="size-10" :iconName="form.svg_name" alt="TeamIcon" />
                                      </MenuButton>
                                      <transition enter-active-class="transition duration-100 ease-out motion-reduce:transition-none"
                                                  enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0"
                                                  leave-active-class="transition duration-75 ease-in motion-reduce:transition-none"
                                                  leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1">
                                          <MenuItems
                                              class="absolute left-0 z-40 mt-2 grid max-h-64 w-72 grid-cols-4 gap-1 overflow-y-auto rounded-lg border border-border bg-surface p-2 shadow-overlay focus:outline-none"
                                          >
                                              <MenuItem v-for="item in iconMenuItems" :key="item.iconName" v-slot="{ active }">
                                                  <button
                                                      type="button"
                                                      :class="[
                                                          active ? 'bg-surface-hover' : '',
                                                          form.svg_name === item.iconName ? 'ring-2 ring-accent-500' : '',
                                                          'flex cursor-pointer items-center justify-center rounded-md p-1'
                                                      ]"
                                                      @click="form.svg_name = item.iconName"
                                                  >
                                                      <TeamIconCollection class="size-12" :iconName="item.iconName" alt="TeamIcon" />
                                                  </button>
                                              </MenuItem>
                                          </MenuItems>
                                      </transition>
                                  </Menu>
                              </div>
                              <BaseInput
                                  id="name"
                                  v-model="form.name"
                                  label="Name"
                                  required
                                  class="flex-1"
                                  :error="form.errors.name"
                                  autofocus
                              />
                          </div>
                          <p v-if="form.svg_name === ''" class="mt-1 text-[11.5px] text-danger">{{ $t('Selecting an icon is required') }}</p>
                      </div>

                      <div>
                          <h3 class="font-lexend text-sm font-semibold text-text">{{ $t('Add users') }}</h3>
                          <p class="mb-3 mt-0.5 text-xs text-text-muted">{{ $t('Enter the name of the user you want to add to the team.') }}</p>
                          <BaseInput
                              id="userSearch"
                              v-model="user_query"
                              label="Name"
                              :placeholder="$t('Search for users')"
                              autocomplete="off"
                          />
                          <ul
                              v-if="user_search_results.length > 0 && user_query.length > 0"
                              class="mt-2 max-h-60 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
                          >
                              <li v-for="user in user_search_results" :key="user.id">
                                  <button
                                      type="button"
                                      class="block w-full cursor-pointer truncate px-3 py-2 text-left text-sm font-medium text-text transition-colors duration-150 hover:bg-surface-hover focus-visible:bg-surface-hover motion-reduce:transition-none"
                                      @click="addUserToAssignedUsersArray(user)"
                                  >
                                      {{ user.first_name }} {{ user.last_name }}
                                  </button>
                              </li>
                          </ul>
                      </div>

                      <ul
                          v-if="form.assigned_users.length > 0"
                          class="max-h-72 divide-y divide-border-subtle overflow-y-auto rounded-lg border border-border bg-surface"
                      >
                          <li
                              v-for="(user, index) in form.assigned_users"
                              :key="user.id"
                              class="flex items-center justify-between gap-x-3 px-3 py-2"
                          >
                              <div class="flex min-w-0 items-center gap-x-3">
                                  <img class="size-8 shrink-0 rounded-full object-cover" :src="user.profile_photo_url" alt=""/>
                                  <span class="truncate text-sm font-medium text-text">{{ user.first_name }} {{ user.last_name }}</span>
                              </div>
                              <button
                                  type="button"
                                  class="inline-flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-text-subtle transition-colors duration-150 hover:bg-surface-hover hover:text-danger motion-reduce:transition-none"
                                  :aria-label="$t('Remove person from team')"
                                  @click="deleteUserFromTeam(index)"
                              >
                                  <IconX class="size-4" stroke-width="1.5" aria-hidden="true"/>
                              </button>
                          </li>
                      </ul>
                      <p v-if="form.errors.assigned_users" class="text-[11.5px] text-danger">{{ form.errors.assigned_users }}</p>
                  </div>
                  <template #footer>
                      <BaseUIButton
                          label="Cancel"
                          is-cancel-button
                          hide-icon
                          :disabled="form.processing"
                          @click="closeAddTeamModal"
                      />
                      <BaseUIButton
                          label="Create Team"
                          variant="primary"
                          icon="IconCirclePlus"
                          :processing="form.processing"
                          :disabled="form.name === '' || form.svg_name === ''"
                          @click="addTeam"
                      />
                  </template>
              </ArtworkBaseModal>

              <!-- Alle Mitglieder aus Team löschen Modal -->
              <ArtworkBaseModal
                  v-if="deletingAllTeamMembers"
                  title="Delete all team members"
                  modal-size="sm:max-w-lg"
                  @close="closeDeleteAllTeamMembersModal"
              >
                  <p class="text-sm text-text">
                      {{ $t('Are you sure you want to remove all members of the team { teamName }?', { teamName: teamToDeleteAllMembers?.name }) }}
                  </p>
                  <template #footer>
                      <BaseUIButton
                          label="No, not really"
                          is-cancel-button
                          hide-icon
                          :disabled="deleteMembersForm.processing"
                          @click="closeDeleteAllTeamMembersModal"
                      />
                      <BaseUIButton
                          label="Delete"
                          variant="danger"
                          icon="IconTrash"
                          :processing="deleteMembersForm.processing"
                          @click="deleteAllTeamMembers"
                      />
                  </template>
              </ArtworkBaseModal>

              <!-- Team löschen Modal -->
              <ArtworkBaseModal
                  v-if="deletingTeam"
                  title="Delete Team"
                  modal-size="sm:max-w-lg"
                  @close="closeDeleteTeamModal"
              >
                  <p class="text-sm text-text">
                      {{ $t('Are you sure you want to delete the team { teamName } from the system?', { teamName: teamToDelete?.name }) }}
                  </p>
                  <template #footer>
                      <BaseUIButton
                          label="No, not really"
                          is-cancel-button
                          hide-icon
                          :disabled="deleteTeamProcessing"
                          @click="closeDeleteTeamModal"
                      />
                      <BaseUIButton
                          label="Delete Team"
                          variant="danger"
                          icon="IconTrash"
                          :processing="deleteTeamProcessing"
                          @click="deleteTeam"
                      />
                  </template>
              </ArtworkBaseModal>

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
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

// Deine bestehenden Komponenten
import BaseMenu from '@/Components/Menu/BaseMenu.vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import TeamIconCollection from '@/Layouts/Components/TeamIconCollection.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'
import SuccessModal from '@/Layouts/Components/General/SuccessModal.vue'
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue'

// Icons
import {IconUsersGroup, IconCirclePlus, IconTrash, IconEdit, IconChevronDown, IconX} from '@tabler/icons-vue'
import UserHeader from "@/Pages/Users/UserHeader.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";

// Props
const props = defineProps<{ departments: any }>()

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
        route('departments'),
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
const deleteTeamProcessing = ref(false)
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
    if (!teamToDelete.value || deleteTeamProcessing.value) return
    deleteTeamProcessing.value = true
    router.delete(route('departments.destroy', { department: teamToDelete.value.id }), {
        onSuccess: () => {
            closeDeleteTeamModal()
            showSuccessModal('delete')
        },
        onFinish: () => {
            deleteTeamProcessing.value = false
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
