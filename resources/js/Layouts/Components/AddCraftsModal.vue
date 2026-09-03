<template>
    <ArtworkBaseModal
        v-if="open"
        @close="closeModal"
        :title="$t(craftToEdit ? 'Edit craft' : 'Create craft')"
        :description="$t('Define the specifications of your trade and who may plan shifts for it.')"
    >
        <!-- Basics ------------------------------------------------------>
        <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm">
            <BasePageTitle
                :title="$t('Edit basic data')"
                :description="$t('General information about this craft.')"
            />

            <div class="border-b border-dashed border-border-subtle pb-3">
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-7 items-center">
                    <div class="sm:col-span-1 flex items-center w-full">
                        <ColorPickerComponent :color="craft.color" @updateColor="onPickColor"/>

                    </div>

                    <div class="sm:col-span-4">
                        <BaseInput
                            :label="$t('Name of the craft') + '*'"
                            v-model="craft.name"
                            id="name"
                            required
                            :error="craft.errors.name"
                        />
                    </div>

                    <div class="sm:col-span-2">
                        <BaseInput
                            :label="$t('Abbreviation') + '*'"
                            v-model="craft.abbreviation"
                            :maxlength="4"
                            id="abbreviation"
                            required
                            :error="craft.errors.abbreviation"
                        />
                    </div>
                </div>
                <p class="mt-2 text-xs text-text-subtle">{{$t('Choose a color for fast visual recognition in calendars and lists.')}}</p>
            </div>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="sm:col-span-full">
                    <BaseInput
                        type="number"
                        v-model.number="craft.notify_days"
                        :maxlength="3"
                        required
                        id="notify_days"
                        :label="$t('Days until notification if shift not fully staffed')"
                        :min="0"
                        :max="100"
                    />
                    <p class="mt-2 text-xs text-text-subtle">{{$t('We will alert you when a shift remains under-staffed after this many days.')}}</p>
                </div>

                <div v-if="shiftCommitWorkflowEnabled" class="sm:col-span-full">
                    <BaseInput
                        type="number"
                        v-model.number="craft.commit_request_deadline_days"
                        id="commit_request_deadline_days"
                        :label="$t('Deadline for shift plan request (days before start of week)')"
                        :min="0"
                        :max="365"
                    />
                    <p class="mt-2 text-xs text-text-subtle">{{$t('If no shift plan request has been submitted for a calendar week by this deadline, the craft management will be notified. Leave empty to disable the reminder.')}}</p>
                </div>

                <div class="sm:col-span-full flex items-center gap-3 rounded-xl border border-border-subtle bg-surface-sunken/70 p-4">
                    <input id="universally_applicable" v-model="craft.universally_applicable" type="checkbox" class="h-4 w-4 rounded border-border text-text focus:ring-surface-inverse focus:ring-2">
                    <label for="universally_applicable" class="text-sm text-text">{{$t('Universally applicable')}}</label>
                </div>
            </div>
        </section>

        <!-- Shift planning permissions ---------------------------------->
        <section class="mt-6 rounded-2xl border border-border-subtle bg-white p-6 shadow-sm">
            <BasePageTitle
                :title="$t('Shift planning permissions')"
                :description="$t('Control who may schedule this craft in shifts.')"
            />

            <div class="mt-4 flex items-center gap-3">
                <span class="text-sm" :class="enabled ? 'text-text-subtle' : 'text-text font-medium'">{{$t('Allocable to a limited extent')}}</span>
                <Switch v-model="enabled" :class="[enabled ? 'bg-accent-600' : 'bg-border-subtle', 'relative inline-flex h-5 w-10 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none']">
                    <span aria-hidden="true" :class="[enabled ? 'translate-x-5' : 'translate-x-0', 'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']" />
                </Switch>
                <span class="text-sm" :class="!enabled ? 'text-text-subtle' : 'text-text font-medium'">{{$t('Can be scheduled by all shift planners')}}</span>
            </div>

            <div v-if="!enabled" class="mt-4">
                <div class="sm:w-96">
                    <Listbox as="div">
                        <div class="relative">
                            <ListboxButton class="relative w-full cursor-pointer rounded-xl border border-border-subtle bg-white py-2 pl-3 pr-9 text-left text-sm shadow-sm hover:border-border focus:outline-none focus:ring-2 focus:ring-surface-inverse/20 focus:border-surface-inverse/40 transition">
                                <span class="block truncate text-left">{{$t('Select users')}}</span>
                                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                              <PropertyIcon name="IconChevronDown" stroke-width="1.5" class="h-5 w-5 text-text" aria-hidden="true"/>
                            </span>
                            </ListboxButton>
                            <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                                <ListboxOptions class="absolute z-50 mt-1 max-h-56 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg border border-border-subtle ring-opacity-5 focus:outline-none sm:text-sm">
                                    <ListboxOption as="template" v-for="user in usersWithPermission" :key="user.id" :value="user" v-slot="{ active }">
                                        <li @click="togglePlanner(user, 'shift_planer')" :class="[active ? 'bg-surface-sunken' : '', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                            <span>{{ user.full_name }}</span>
                                        </li>
                                    </ListboxOption>
                                </ListboxOptions>
                            </transition>
                        </div>
                    </Listbox>
                </div>

                <div class="mt-4">
                    <div v-if="craftShiftPlaner.length === 0" class="rounded-xl border border-dashed border-border-subtle p-4 text-sm text-text-subtle">{{$t('No specific planners selected yet.')}}</div>
                    <ul class="mt-2 grid gap-3 sm:grid-cols-2">
                        <li v-for="user in craftShiftPlaner" :key="'planner-' + user.id" class="flex items-center justify-between rounded-xl border border-border-subtle bg-surface-sunken p-3">
                            <div class="flex items-center gap-3">
                                <img class="size-9 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                                <span class="text-sm">{{ user.first_name }} {{ user.last_name }}</span>
                            </div>
                            <button type="button" @click="togglePlanner(user, 'shift_planer')" class="p-1" aria-label="{{$t('Remove')}}">
                                <PropertyIcon name="IconCircleX" stroke-width="1.5" class="h-5 w-5 text-text hover:text-danger"/>
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- Managers ----------------------------------------------------->
        <section class="mt-6 rounded-2xl border border-border-subtle bg-white p-6 shadow-sm">
            <div class="mb-2">
                <BasePageTitle
                    :title="$t('Craft manager')"
                    :description="$t('Assign department management for this craft. They will be highlighted in overviews.')"
                />
            </div>

            <UserSearch
                :label="$t('Add department management')"
                @user-selected="addSelectedToCraftManagers"
                :search-workers="false"
                :current-craft="craft"
                :dont-close-on-select="false"
            />

            <ul class="mt-3 grid gap-3 sm:grid-cols-2">
                <li v-for="user in managers" :key="'manager-' + (user.id || user.pivot?.craft_manager_id)" class="flex items-center justify-between rounded-xl border border-border-subtle bg-surface-sunken p-3">
                    <div class="flex items-center gap-3">
                        <img class="size-9 rounded-full object-cover" :src="user.profile_photo_url" alt="" />
                        <span class="text-sm">{{ user.first_name }} {{ user.last_name }}</span>
                    </div>
                    <button type="button" @click="deleteDepartmentManager(user)" class="p-1" aria-label="$t('Delete department management')">
                        <PropertyIcon name="IconCircleX" stroke-width="1.5" class="h-5 w-5 text-text hover:text-danger"/>
                    </button>
                </li>
            </ul>
        </section>

        <!-- Managers ----------------------------------------------------->
        <section class="mt-6 rounded-2xl border border-border-subtle bg-white p-6 shadow-sm">
            <div class="mb-2">
                <BasePageTitle
                    :title="$t('Functions')"
                    :description="$t('Add or remove functions for this craft.')"
                />
            </div>

            <ArtworkBaseListbox
                v-model="craft.qualifications"
                :items="propQualifications"
                by="id"
                multiple
                option-label="name"
                option-key="id"
                :use-translations="false"
                :label="null"
                :placeholder="$t('Select functions')"
            />
        </section>

        <!-- Footer ------------------------------------------------------->
        <div class="flex items-center justify-end mt-6">
            <BaseUIButton :label="$t('Save')" @click="saveCraft" is-add-button/>
        </div>
    </ArtworkBaseModal>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
    Switch,
} from '@headlessui/vue'
import { IconChevronDown, IconCircleX } from '@tabler/icons-vue'

import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BasePageTitle from '@/Artwork/Titles/BasePageTitle.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import ColorPickerComponent from '@/Components/Globale/ColorPickerComponent.vue'
import UserSearch from '@/Components/SearchBars/UserSearch.vue'
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

/* ---------------- Props / Emits ---------------- */
const props = defineProps<{
    craftToEdit?: any | null
    usersWithPermission: Array<any>
    propQualifications: Array<any>
}>()
const emit = defineEmits<{ (e: 'closed', v: boolean): void }>()

/* ---------------- Local state ------------------ */
const open = ref(true)

const craft = useForm({
    name: props.craftToEdit?.name ?? '',
    abbreviation: props.craftToEdit?.abbreviation ?? '',
    users: [] as number[],
    assignable_by_all: true,
    color: props.craftToEdit?.color ?? '#ffffff',
    notify_days: props.craftToEdit?.notify_days ?? 0,
    // beim Bearbeiten den gespeicherten Wert übernehmen (null = Erinnerung aus), bei Neuanlage Default 14
    commit_request_deadline_days: props.craftToEdit ? props.craftToEdit.commit_request_deadline_days : 14,
    universally_applicable: props.craftToEdit?.universally_applicable ?? false,
    managersToBeAssigned: [] as Array<{ manager_id: number; manager_type: string }>,
    qualifications: props.craftToEdit?.qualifications ?? [],
})

const shiftCommitWorkflowEnabled = computed<boolean>(() => !!usePage().props.shiftCommitWorkflow)

const enabled = ref<boolean>(props.craftToEdit?.assignable_by_all ?? true)

const craftShiftPlaner = ref<Array<any>>(props.craftToEdit?.craft_shift_planer ?? [])
const managers = ref<Array<any>>(
    props.craftToEdit
        ? [
            ...(props.craftToEdit.managing_freelancers ?? []),
            ...(props.craftToEdit.managing_service_providers ?? []),
            ...(props.craftToEdit.managing_users ?? []),
        ]
        : []
)

// headlessui needs a v-model value, but we handle selection via click\ nconst dummySelect = ref<any | null>(null)

/* ---------------- Watchers / guards ----------- */
watch(
    () => craft.notify_days,
    (v) => {
        if (v == null) return
        if (v < 0) craft.notify_days = 0
        if (v > 100) craft.notify_days = 100
    }
)

/* ---------------- Methods --------------------- */
function closeModal(bool = false) {
    craft.reset('name', 'abbreviation', 'users', 'assignable_by_all')
    emit('closed', bool)
}

function onPickColor(color: string) {
    craft.color = color
}

function togglePlanner(user: any, type: 'shift_planer') {
    const idx = craftShiftPlaner.value.findIndex((u) => u.id === user.id)
    if (idx > -1) craftShiftPlaner.value.splice(idx, 1)
    else craftShiftPlaner.value.push(user)
}

function addSelectedToCraftManagers(user: any) {
    if (managers.value.findIndex((m) => m.id === user.id) > -1) return
    managers.value.push(user)
}

function deleteDepartmentManager(user: any) {
    const idx = managers.value.findIndex((m) => m.id === user.id)
    if (idx > -1) managers.value.splice(idx, 1)
}

function saveCraft() {
    // Managers payload
    craft.managersToBeAssigned = []
    managers.value.forEach((manager: any) => {
        craft.managersToBeAssigned.push({
            manager_id: manager.id ?? manager.pivot?.craft_manager_id,
            manager_type: manager.manager_type ?? manager.pivot?.craft_manager_type,
        })
    })

    // Clamp notify days
    if ((craft.notify_days as number) < 0) craft.notify_days = 0

    // leeres Zahlenfeld (v-model.number liefert dann '') als "keine Frist" speichern
    if (typeof craft.commit_request_deadline_days !== 'number' || Number.isNaN(craft.commit_request_deadline_days)) {
        craft.commit_request_deadline_days = null
    }

    // Shift planners
    if (!enabled.value) {
        craft.assignable_by_all = false
        craft.users = craftShiftPlaner.value.map((u) => u.id)
    } else {
        craft.assignable_by_all = true
        craft.users = []
    }

    if (props.craftToEdit?.id) {
        craft.patch(route('craft.update', props.craftToEdit.id), {
            preserveState: true,
            preserveScroll: true,
            onError: (errors) => {
                console.log('Validation errors:', errors)
            },
            onSuccess: () => {
                craft.reset('name', 'abbreviation', 'users', 'assignable_by_all')
                closeModal(true)
            },
        })
    } else {
        craft.post(route('craft.store'), {
            preserveState: true,
            preserveScroll: true,
            onError: (errors) => {
                console.log('Validation errors:', errors)
            },
            onSuccess: () => {
                craft.reset('name', 'abbreviation', 'users', 'assignable_by_all')
                closeModal(true)
            },
        })
    }
}
</script>


