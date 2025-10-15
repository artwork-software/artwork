<template>
    <div class="space-y-8">
        <!-- Work profile -->
        <section class="rounded-3xl border border-zinc-200 bg-white shadow-sm">
            <div class="px-6 py-5 sm:px-8 sm:py-7">
                <h2 class="text-lg font-semibold text-zinc-900">
                    {{ $t('Work profile') }}
                </h2>
                <p class="mt-1 text-sm text-zinc-600">
                    {{ $t("Edit the user's work profile here.")}}
                </p>

                <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <BaseInput
                            id="workName"
                            v-model="workProfileForm.workName"
                            :label="$t('Job title')"
                            @focusout="updateWorkProfile"
                        />
                    </div>
                    <div class="md:col-span-2">
                        <BaseTextarea
                            id="jobDescription"
                            v-model="workProfileForm.workDescription"
                            :label="$t('Job description')"
                            :rows="4"
                            @focusout="updateWorkProfile"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- Freelancer settings -->
        <section class="rounded-3xl border border-zinc-200 bg-white shadow-sm">
            <div class="px-6 py-5 sm:px-8 sm:py-7">
                <h3 class="text-lg font-semibold text-zinc-900">
                    {{ $t('Freelancer Settings') }}
                </h3>
                <div
                    v-if="userType === 'user'"
                    class="mt-4 inline-flex items-center gap-3"
                >
                    <SwitchGroup as="div" class="flex items-center">
                        <Switch
                            v-model="workProfileForm.is_freelancer"
                            @update:modelValue="updateWorkProfile"
                            :disabled="workProfileForm.processing"
                            :class="[
                workProfileForm.is_freelancer ? 'bg-blue-600' : 'bg-zinc-300',
                workProfileForm.processing ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer',
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2'
              ]"
                        >
              <span
                  aria-hidden="true"
                  :class="[
                  workProfileForm.is_freelancer ? 'translate-x-6' : 'translate-x-1',
                  'inline-block h-4 w-4 transform rounded-full bg-white shadow transition'
                ]"
              />
                        </Switch>
                        <SwitchLabel as="span" class="ml-3 text-sm text-zinc-700">
                            {{ $t('Show as freelancer in the tool') }}
                        </SwitchLabel>
                    </SwitchGroup>
                </div>
            </div>
        </section>



        <!-- Crafts -->
        <section class="rounded-3xl border border-zinc-200 bg-white shadow-sm">
            <div class="px-6 py-5 sm:px-8 sm:py-7">
                <h3 class="text-lg font-semibold text-zinc-900">
                    {{ $t('Crafts') }}
                </h3>

                <!-- assignable to shifts -->
                <div class="mt-4">
                    <SwitchGroup as="div" class="flex items-center">
                        <Switch
                            v-model="craftSettingsForm.canBeAssignedToShifts"
                            :class="[
                craftSettingsForm.canBeAssignedToShifts ? 'bg-blue-600' : 'bg-zinc-300',
                'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2'
              ]"
                        >
              <span
                  aria-hidden="true"
                  :class="[
                  craftSettingsForm.canBeAssignedToShifts ? 'translate-x-6' : 'translate-x-1',
                  'inline-block h-4 w-4 transform rounded-full bg-white shadow transition'
                ]"
              />
                        </Switch>
                        <SwitchLabel as="span" class="ml-3 text-sm text-zinc-700">
                            {{ $t('May be assigned to shifts') }}
                        </SwitchLabel>
                    </SwitchGroup>
                </div>

                <!-- shift qualifications -->
                <div class="mt-6">
                    <h4 class="text-sm font-medium text-zinc-900">
                        {{ $t('Shift qualifications')}}
                    </h4>
                    <div class="mt-3 space-y-3">
                        <SwitchGroup
                            v-for="sq in computedShiftQualifications"
                            :key="sq.id"
                            as="div"
                            class="flex items-center justify-between rounded-2xl border border-zinc-200 px-3 py-2"
                        >
                            <div class="text-sm text-zinc-800">
                                {{ $t('Can be used as {shiftQualificationName}', { shiftQualificationName: sq.name }) }}
                            </div>
                            <Switch
                                v-model="sq.toggled"
                                @update:modelValue="updateUserShiftQualification(sq)"
                                :class="[
                  sq.toggled ? 'bg-blue-600' : 'bg-zinc-300',
                  'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2'
                ]"
                            >
                <span
                    aria-hidden="true"
                    :class="[
                    sq.toggled ? 'translate-x-6' : 'translate-x-1',
                    'inline-block h-4 w-4 transform rounded-full bg-white shadow transition'
                  ]"
                />
                            </Switch>
                        </SwitchGroup>
                    </div>
                </div>

                <!-- Shift planner for (visible crafts the user can plan) -->
                <div v-if="userType === 'user'" class="mt-6">
                    <h4 class="mb-3 text-sm font-medium text-zinc-900">
                        {{ $t('Shift planner for')}}
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        <div
                            v-for="(craft, index) in user.accessibleCrafts"
                            :key="craft.id || index"
                            class="group inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 pr-3"
                        >
              <span
                  class="ml-1 block size-8 rounded-full border-2"
                  :style="{
                  backgroundColor: backgroundColorWithOpacityOld(craft.color, 75),
                  borderColor: craft.color
                }"
              />
                            <span class="text-sm text-zinc-800">
                {{ craft.name }}
              </span>
                        </div>
                    </div>
                </div>


                <div v-if="craftSettingsForm.canBeAssignedToShifts" class="mt-8">
                    <h4 class="text-sm font-medium text-zinc-900">
                        {{ $t('Can be used in the following crafts') }}
                    </h4>

                    <label for="selectedCraftsToAdd" class="mt-2 block text-xs text-zinc-500">
                        {{ $t('Assign new crafts') }}
                    </label>

                    <div class="mt-2 flex flex-wrap items-center gap-3">

                        <!-- Multi-select Listbox -->
                        <Listbox as="div" id="selectedCraftsToAdd" class="relative" v-model="selectedCraftsToAssign" multiple>
                            <ListboxButton
                                class="flex w-80 items-center justify-between rounded-xl border border-zinc-300 bg-white px-3 py-2 text-left text-sm text-zinc-900 hover:bg-zinc-50 focus:outline-none focus:ring-2 focus:ring-blue-600"
                            >
                                <span class="block truncate">
                                  <template v-if="selectedCraftsToAssign.length">
                                    {{ $t('{count} selected', { count: selectedCraftsToAssign.length }) }}
                                  </template>
                                  <span v-else class="text-zinc-500">{{ $t('Select crafts') }}</span>
                                </span>
                                <ChevronDownIcon class="h-5 w-5 text-zinc-400" aria-hidden="true" />
                            </ListboxButton>

                            <ListboxOptions
                                class="absolute z-10 mt-2 max-h-64 w-80 overflow-auto rounded-xl border border-zinc-200 bg-white p-1 text-sm shadow-lg focus:outline-none"
                            >
                                <!-- 1) Empty state -->
                                <template v-if="filteredAssignableCrafts.length === 0">
                                    <div
                                        class="cursor-default rounded-lg px-2 py-2 text-zinc-400"
                                        aria-disabled="true"
                                    >
                                        {{ $t('No crafts match your search.') }}
                                    </div>
                                </template>

                                <!-- 2) Header + Options -->
                                <template v-else>
                                    <!-- Sticky header: select all / clear -->
                                    <div
                                        class="sticky top-0 z-10 mb-1 flex items-center justify-between rounded-lg bg-zinc-50 px-2 py-1 text-xs"
                                    >
                                        <button type="button" class="underline" @click="selectAllFiltered">
                                            {{ $t('Select all') }}
                                        </button>
                                        <button type="button" class="underline" @click="selectedCraftsToAssign = []">
                                            {{ $t('Clear') }}
                                        </button>
                                    </div>

                                    <!-- Options -->
                                    <ListboxOption
                                        v-for="assignableCraft in filteredAssignableCrafts"
                                        :key="assignableCraft.id"
                                        as="template"
                                        :value="assignableCraft.id"
                                        v-slot="{ active, selected, disabled }"
                                        :class="[
        'flex cursor-pointer items-center justify-between rounded-lg px-2 py-2'
      ]"
                                    >
                                    <div>
                                         <span :class="selected ? 'font-medium' : 'font-normal'">
        {{ assignableCraft.name }}
      </span>
                                        <CheckIcon
                                            v-if="selected"
                                            class="h-5 w-5"
                                            :class="active ? 'text-white' : 'text-blue-600'"
                                            aria-hidden="true"
                                        />
                                    </div>
                                    </ListboxOption>
                                </template>
                            </ListboxOptions>
                        </Listbox>

                        <BaseUIButton
                            :disabled="selectedCraftsToAssign.length === 0"
                            :label="$t('Assign {count} crafts', { count: selectedCraftsToAssign.length })"
                            @click="assignCraftsBulk"
                            is-add-button
                        />
                    </div>

                    <!-- Assigned crafts list (unchanged) -->
                    <div class="mt-4 flex flex-wrap gap-2">
                        <div
                            v-for="(craft, index) in user.assignedCrafts"
                            :key="craft.id || index"
                            class="group inline-flex items-center gap-2 rounded-full border border-zinc-200 bg-zinc-50 pr-2"
                        >
                      <span
                          class="ml-1 block size-8 rounded-full border-2"
                          :style="{ backgroundColor: backgroundColorWithOpacityOld(craft.color, 75), borderColor: craft.color }"
                      />
                            <span class="text-sm text-zinc-800">{{ craft.name }}</span>
                            <button type="button" @click="removeCraft(craft.id)" class="rounded-full p-1 hover:bg-zinc-100">
                                <XIcon class="h-4 w-4 text-zinc-400 hover:text-red-600" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup>
import {computed, nextTick, ref, watch} from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import {
    Listbox, ListboxButton, ListboxOption, ListboxOptions,
    Switch, SwitchGroup, SwitchLabel
} from '@headlessui/vue'
import { CheckIcon } from '@heroicons/vue/solid'
import { ChevronDownIcon, XIcon } from '@heroicons/vue/outline'

import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import AddButtonSmall from '@/Layouts/Components/General/Buttons/AddButtonSmall.vue'
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    userType: { type: String, required: true },                 // 'user' | 'freelancer' | 'serviceProvider'
    user: { type: Object, required: true },
    shiftQualifications: { type: Array, required: true },
})

/* Forms */
const workProfileForm = useForm({
    workName: props.user.work_name,
    workDescription: props.user.work_description,
    is_freelancer: props.user.is_freelancer,
})

const craftSettingsForm = useForm({
    canBeAssignedToShifts: props.user.can_work_shifts,
})

/* UI state */
const selectedCraftToAssign = ref(null)

/* Computed: shift qualifications mapped w/ toggled flag */
const computedShiftQualifications = computed(() => {
    return (props.shiftQualifications || []).map((sq) => {
        const has = (props.user.shiftQualifications || []).some(u => u.id === sq.id)
        return { id: sq.id, name: sq.name, toggled: has }
    })
})

// NEW: Multi-select state + search
const selectedCraftsToAssign = ref([])
const craftSearch = ref('')
const isAssigning = ref(false)
const filteredAssignableCrafts = computed(() => {
    const q = (craftSearch.value || '').toLowerCase().trim()
    let list = props.user.assignableCrafts || []
    if (q) {
        list = list.filter(c => (c.name || '').toLowerCase().includes(q))
    }
    // Optional: doppelte Sicherheit – entferne bereits ausgewiesene
    const assignedIds = new Set((props.user.assignedCrafts || []).map(c => c.id))
    return list.filter(c => !assignedIds.has(c.id))
})

const selectAllFiltered = () => {
    selectedCraftsToAssign.value = filteredAssignableCrafts.value.map(c => c.id)
}

/* Watch craft settings dirty -> patch automatically */
watch(() => craftSettingsForm.isDirty, (dirty) => {
    if (!dirty) return

    let desiredRoute = null
    let routeParameter = null
    switch (props.userType) {
        case 'user':
            desiredRoute = 'user.update.craftSettings'
            routeParameter = { user: props.user.id }
            break
        case 'freelancer':
            desiredRoute = 'freelancer.update.craftSettings'
            routeParameter = { freelancer: props.user.id }
            break
        case 'serviceProvider':
            desiredRoute = 'service_provider.update.craftSettings'
            routeParameter = { serviceProvider: props.user.id }
            break
    }

    if (desiredRoute) {
        craftSettingsForm.patch(route(desiredRoute, routeParameter), {
            preserveScroll: true,
            preserveState: true,
        })
    }
})

/* Actions */
const updateUserShiftQualification = (shiftQualification) => {
    let desiredRoute = null
    let routeParameter = null

    switch (props.userType) {
        case 'user':
            desiredRoute = 'user.update.shift-qualification'
            routeParameter = { user: props.user.id }
            break
        case 'freelancer':
            desiredRoute = 'freelancer.update.shift-qualification'
            routeParameter = { freelancer: props.user.id }
            break
        case 'serviceProvider':
            desiredRoute = 'service_provider.update.shift-qualification'
            routeParameter = { serviceProvider: props.user.id }
            break
    }

    if (desiredRoute) {
        router.patch(
            route(desiredRoute, routeParameter),
            { shiftQualificationId: shiftQualification.id, create: shiftQualification.toggled },
            { preserveScroll: true, preserveState: true }
        )
    }
}

const updateWorkProfile = () => {
    let desiredRoute = null
    let routeParameter = null

    switch (props.userType) {
        case 'user':
            desiredRoute = 'user.update.workProfile'
            routeParameter = { user: props.user.id }
            break
        case 'freelancer':
            desiredRoute = 'freelancer.update.workProfile'
            routeParameter = { freelancer: props.user.id }
            break
        case 'serviceProvider':
            desiredRoute = 'service_provider.update.workProfile'
            routeParameter = { serviceProvider: props.user.id }
            break
    }

    if (desiredRoute) {
        nextTick(() => {
            if (workProfileForm.isDirty) {
                workProfileForm.patch(route(desiredRoute, routeParameter), {
                    preserveScroll: true,
                    preserveState: true,
                })
            }
        })
    }
}

const assignCraft = () => {
    let desiredRoute = null
    let routeParameter = null

    switch (props.userType) {
        case 'user':
            desiredRoute = 'user.assign.craft'
            routeParameter = { user: props.user.id }
            break
        case 'freelancer':
            desiredRoute = 'freelancer.assign.craft'
            routeParameter = { freelancer: props.user.id }
            break
        case 'serviceProvider':
            desiredRoute = 'service_provider.assign.craft'
            routeParameter = { serviceProvider: props.user.id }
            break
    }

    if (desiredRoute) {
        router.patch(
            route(desiredRoute, routeParameter),
            { craftId: selectedCraftToAssign.value },
            {
                preserveScroll: true,
                onSuccess: () => (selectedCraftToAssign.value = null),
            }
        )
    }
}

const removeCraft = (craftId) => {
    let desiredRoute = null
    let routeParameter = null

    switch (props.userType) {
        case 'user':
            desiredRoute = 'user.remove.craft'
            routeParameter = { user: props.user.id, craft: craftId }
            break
        case 'freelancer':
            desiredRoute = 'freelancer.remove.craft'
            routeParameter = { freelancer: props.user.id, craft: craftId }
            break
        case 'serviceProvider':
            desiredRoute = 'service_provider.remove.craft'
            routeParameter = { serviceProvider: props.user.id, craft: craftId }
            break
    }

    if (desiredRoute) {
        router.delete(route(desiredRoute, routeParameter), {
            preserveScroll: true,
        })
    }
}

const assignCraftsBulk = async () => {
    if (selectedCraftsToAssign.value.length === 0) return

    // Routen ermitteln
    let bulkRoute = null
    let singleRoute = null
    let routeParameter = {}

    switch (props.userType) {
        case 'user':
            bulkRoute = 'user.assign.crafts.bulk'          // <- neuer Bulk-Endpunkt (empfohlen)
            singleRoute = 'user.assign.craft'              // Fallback
            routeParameter = { user: props.user.id }
            break
        case 'freelancer':
            bulkRoute = 'freelancer.assign.crafts.bulk'
            singleRoute = 'freelancer.assign.craft'
            routeParameter = { freelancer: props.user.id }
            break
        case 'serviceProvider':
            bulkRoute = 'service_provider.assign.crafts.bulk'
            singleRoute = 'service_provider.assign.craft'
            routeParameter = { serviceProvider: props.user.id }
            break
    }

    const payload = { craftIds: selectedCraftsToAssign.value }

    // 1) Versuche Bulk-Route (ein Request)
    if (bulkRoute) {
        try {
            await router.patch(
                route(bulkRoute, routeParameter),
                payload,
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => (selectedCraftsToAssign.value = []),
                }
            )
            return
        } catch (e) {
            // Fällt auf Einzel-Requests zurück – z. B. wenn Bulk-Endpunkt (noch) nicht existiert
            // (Fehler wird absichtlich nicht rethrown, wir probieren Fallback)
        }
    }

    // 2) Fallback: mehrere Einzel-Requests (notfalls sequentiell)
    if (singleRoute) {
        for (const id of selectedCraftsToAssign.value) {
            await router.patch(
                route(singleRoute, routeParameter),
                { craftId: id },
                { preserveScroll: true, preserveState: true }
            )
        }
        selectedCraftsToAssign.value = []
    }
}
/* Utils */
const backgroundColorWithOpacityOld = (color, percent = 15) => {
    if (!color) return `rgba(255, 255, 255, ${percent / 100})`
    return `rgba(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(
        color.slice(-2),
        16
    )}, ${percent / 100})`
}
</script>
