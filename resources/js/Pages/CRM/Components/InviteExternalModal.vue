<template>
    <ArtworkBaseModal
        :title="$t('Invite external')"
        :description="$t('Invite an external person to maintain their own data.')"
        @close="$emit('close')"
    >
        <div class="space-y-4 mt-4">
            <!-- Email -->
            <div>
                <BaseInput
                    id="invite-email"
                    v-model="form.email"
                    type="email"
                    :label="$t('Email')"
                    required
                />
                <p v-if="form.errors.email" class="text-xs text-danger mt-1">{{ form.errors.email }}</p>
            </div>

            <!-- Contact type -->
            <div>
                <Listbox as="div" v-model="form.crm_contact_type_id">
                    <ListboxLabel class="componentLabel">{{ $t('Contact type') }}</ListboxLabel>
                    <div class="relative mt-2">
                        <ListboxButton class="menu-button bg-white">
                            <div class="block truncate">{{ selectedTypeLabel }}</div>
                            <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                                <IconChevronDown class="h-5 w-5 text-text-subtle" aria-hidden="true" />
                            </span>
                        </ListboxButton>
                        <ListboxOptions
                            class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base ring-1 shadow-lg ring-black/5 focus:outline-hidden sm:text-sm"
                        >
                            <ListboxOption
                                as="template"
                                v-for="type in contactTypes"
                                :key="type.id"
                                :value="type.id"
                                v-slot="{ active, selected: isSelected }"
                            >
                                <li :class="[active ? 'bg-accent-600 text-white' : 'text-text', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                                    <span :class="[isSelected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ type.name }}</span>
                                </li>
                            </ListboxOption>
                        </ListboxOptions>
                    </div>
                </Listbox>
                <p v-if="requirements && !requirements.invitable" class="text-xs text-danger mt-1">
                    {{ $t('This contact type cannot be invited for external access.') }}
                </p>
            </div>

            <!-- Public required fields (inviter must provide so the entity can be created) -->
            <div v-for="field in publicRequiredFields" :key="`pub-${field}`">
                <BaseInput
                    :id="`pub-${field}`"
                    v-model="form.public_field_values[field]"
                    :label="publicFieldLabel(field)"
                    required
                />
            </div>

            <!-- Confidential required fields (external never sees these) -->
            <template v-if="confidentialRequiredProperties.length">
                <div class="rounded-lg bg-surface-sunken border border-border-subtle p-3 space-y-3">
                    <p class="text-xs text-text-muted">
                        {{ $t('These fields will not be visible to the external person and must be filled in by you in advance.') }}
                    </p>
                    <div v-for="property in confidentialRequiredProperties" :key="`conf-${property.id}`">
                        <BaseInput
                            :id="`conf-${property.id}`"
                            v-model="form.confidential_field_values[property.id]"
                            :label="property.name"
                            required
                        />
                    </div>
                </div>
            </template>

            <!-- Tab selection (only when invited from a project tab) -->
            <template v-if="source === 'project_tab'">
                <div class="space-y-2">
                    <label class="componentLabel">{{ $t('Shared tabs') }}</label>
                    <div
                        v-for="tab in availableTabs"
                        :key="tab.id"
                        class="rounded-md border border-border-subtle p-3"
                    >
                        <label class="flex items-center gap-2">
                            <input type="checkbox" :value="tab.id" v-model="selectedTabIds" class="rounded" />
                            <span class="text-sm">{{ tab.name }}</span>
                        </label>

                        <div v-if="selectedTabIds.includes(tab.id)" class="mt-3 grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="text-xs text-text-muted">{{ $t('Access') }}</label>
                                <select v-model="tabConfig[tab.id].access_type" class="menu-button bg-white mt-1">
                                    <option value="read">{{ $t('Read only') }}</option>
                                    <option value="write">{{ $t('Read and write') }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs text-text-muted">{{ $t('From') }}</label>
                                <input type="date" v-model="tabConfig[tab.id].valid_from" class="menu-button bg-white mt-1" />
                            </div>
                            <div>
                                <label class="text-xs text-text-muted">{{ $t('Until') }}</label>
                                <input type="date" v-model="tabConfig[tab.id].valid_to" class="menu-button bg-white mt-1" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visibility warning: the external sees ALL components of a shared tab -->
                <div
                    v-if="selectedTabIds.length"
                    class="rounded-lg bg-warning-surface border border-warning-border p-4 text-sm text-warning"
                >
                    <p class="font-semibold">{{ $t('Important') }}</p>
                    <p class="mt-1">
                        {{ $t('The external person will see all components of the selected tab(s), regardless of their visibility settings. Consider creating a dedicated tab for external access.') }}
                    </p>
                </div>
            </template>

            <p v-if="form.errors.source_reference_project_id" class="text-xs text-danger">
                {{ form.errors.source_reference_project_id }}
            </p>
            <p v-if="serverError" class="text-xs text-danger">{{ serverError }}</p>
        </div>

        <div class="flex justify-end gap-2 mt-6">
            <button type="button" class="ui-button-cancel" @click="$emit('close')">{{ $t('Cancel') }}</button>
            <button
                type="button"
                class="ui-button-add"
                :disabled="form.processing || (requirements && !requirements.invitable)"
                @click="submit"
            >
                {{ $t('Send invitation') }}
            </button>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import axios from 'axios'
import {
    Listbox,
    ListboxButton,
    ListboxLabel,
    ListboxOption,
    ListboxOptions,
} from '@headlessui/vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import { useTranslation } from '@/Composeables/Translation.js'
import { IconChevronDown } from '@tabler/icons-vue'

const props = defineProps({
    source: { type: String, default: 'crm_index' }, // 'crm_index' | 'project_tab'
    contactTypes: { type: Array, default: () => [] },
    project: { type: Object, default: null },
    availableTabs: { type: Array, default: () => [] },
    preselectedTabId: { type: Number, default: null },
})

// Contact types can be passed in (CRM index) or fetched lazily (project tab entry point).
const contactTypes = ref([...props.contactTypes])

const emit = defineEmits(['close', 'success'])
const $t = useTranslation()

const fieldLabels = {
    first_name: 'First name',
    last_name: 'Last name',
    name: 'Name',
    display_name: 'Name',
    provider_name: 'Provider name',
    contact_person: 'Contact person',
}
const publicFieldLabel = (field) => $t(fieldLabels[field] ?? field)

const requirements = ref(null)
const serverError = ref('')

const today = new Date().toISOString().slice(0, 10)
const defaultValidTo = new Date(new Date().setMonth(new Date().getMonth() + 3)).toISOString().slice(0, 10)

const selectedTabIds = ref(props.preselectedTabId ? [props.preselectedTabId] : [])
const tabConfig = ref({})
props.availableTabs.forEach((tab) => {
    tabConfig.value[tab.id] = { access_type: 'read', valid_from: today, valid_to: defaultValidTo }
})

const form = useForm({
    email: '',
    crm_contact_type_id: contactTypes.value[0]?.id ?? null,
    source: props.source,
    source_reference_project_id: props.project?.id ?? null,
    tab_scopes: [],
    public_field_values: {},
    confidential_field_values: {},
})

const selectedType = computed(() => contactTypes.value.find((t) => t.id === form.crm_contact_type_id))
const selectedTypeLabel = computed(() => selectedType.value?.name ?? $t('Select contact type'))

const publicRequiredFields = computed(() => requirements.value?.public_required_fields ?? [])
const confidentialRequiredProperties = computed(() => requirements.value?.confidential_required_properties ?? [])

const loadRequirements = async () => {
    if (!form.crm_contact_type_id) return
    serverError.value = ''
    form.public_field_values = {}
    form.confidential_field_values = {}
    try {
        const { data } = await axios.get(
            route('crm.externals.contact-types.requirements', { crmContactType: form.crm_contact_type_id })
        )
        requirements.value = data
    } catch (e) {
        requirements.value = null
    }
}

const fetchContactTypes = async () => {
    if (contactTypes.value.length) return
    try {
        const { data } = await axios.get(route('crm.externals.contact-types.index'))
        contactTypes.value = data.contact_types ?? []
        if (!form.crm_contact_type_id) {
            form.crm_contact_type_id = contactTypes.value[0]?.id ?? null
        }
    } catch (e) {
        contactTypes.value = []
    }
}

watch(() => form.crm_contact_type_id, loadRequirements)
onMounted(async () => {
    await fetchContactTypes()
    await loadRequirements()
})

const submit = () => {
    form.tab_scopes = selectedTabIds.value.map((tabId) => ({
        project_tab_id: tabId,
        access_type: tabConfig.value[tabId].access_type,
        valid_from: tabConfig.value[tabId].valid_from,
        valid_to: tabConfig.value[tabId].valid_to,
    }))

    form.post(route('crm.externals.invitations.store'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('success')
            emit('close')
        },
        onError: (errors) => {
            serverError.value = errors.message ?? ''
        },
    })
}
</script>
