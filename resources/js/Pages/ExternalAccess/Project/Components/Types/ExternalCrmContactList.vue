<template>
    <div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h3 class="text-sm font-semibold text-text">{{ title }}</h3>
                <p v-if="description" class="mt-1 whitespace-pre-line text-sm text-text-muted">{{ description }}</p>
            </div>

            <div v-if="canAdd" class="relative shrink-0">
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-surface-inverse px-3 py-2 text-sm font-medium text-white"
                    :aria-expanded="contactTypes.length > 1 ? String(typeMenuOpen) : undefined"
                    @click="onAddClick"
                >
                    <IconPlus class="size-4" />
                    {{ addLabel }}
                </button>
                <ul
                    v-if="typeMenuOpen"
                    class="absolute right-0 z-10 mt-1 w-56 overflow-hidden rounded-lg border border-border-subtle bg-white text-sm shadow-lg"
                >
                    <li v-for="type in contactTypes" :key="type.id">
                        <button type="button" class="block w-full px-3 py-2 text-left hover:bg-surface-sunken" @click="openCreate(type)">
                            {{ type.name }}
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <p v-if="limitReached" class="mt-3 text-xs text-text-subtle">
            {{ $t('Maximum number of contacts reached ({max}).', { max: maxContacts }) }}
        </p>

        <p v-if="loading" class="mt-4 text-xs text-text-subtle">{{ $t('Loading data...') }}</p>
        <p v-else-if="loadError" class="mt-4 text-xs text-danger">{{ loadError }}</p>
        <p v-else-if="contacts.length === 0" class="mt-4 text-sm text-text-subtle">{{ $t('No persons added yet.') }}</p>

        <ul v-else class="mt-4 space-y-2">
            <li v-for="contact in contacts" :key="contact.id" class="rounded-xl border border-border-subtle">
                <button
                    type="button"
                    class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
                    :aria-expanded="String(isOpen(contact))"
                    @click="toggle(contact)"
                >
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-text">{{ contact.display_name }}</span>
                        <span class="block text-xs text-text-subtle">
                            {{ contact.contact_type?.name }}
                            <template v-if="contact.created_by_me"> · {{ $t('added by you') }}</template>
                        </span>
                    </span>
                    <IconChevronDown class="size-4 shrink-0 text-text-subtle transition-transform" :class="isOpen(contact) ? 'rotate-180' : ''" />
                </button>

                <div v-if="isOpen(contact)" class="border-t border-border-subtle px-4 py-3">
                    <dl v-if="contact.fields.length" class="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                        <div v-for="field in contact.fields" :key="field.property_id">
                            <dt class="text-xs text-text-subtle">{{ field.name }}</dt>
                            <dd class="whitespace-pre-line break-words text-sm text-text">{{ formatValue(field) }}</dd>
                        </div>
                    </dl>
                    <p v-else class="text-xs text-text-subtle">{{ $t('No further details.') }}</p>

                    <div v-if="contact.can_edit && editable" class="mt-3 flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 text-xs font-medium text-text ring-1 ring-inset ring-border"
                            @click="openEdit(contact)"
                        >
                            {{ $t('Edit') }}
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 text-xs font-medium text-danger ring-1 ring-inset ring-danger-border"
                            @click="remove(contact)"
                        >
                            {{ $t('Remove') }}
                        </button>
                    </div>
                </div>
            </li>
        </ul>

        <p v-if="actionError" class="mt-2 text-xs text-danger">{{ actionError }}</p>

        <ExternalCrmContactModal
            v-if="modal"
            :route-params="routeParams"
            :contact-type="modal.contactType"
            :contact="modal.contact"
            @close="modal = null"
            @saved="onSaved"
        />
    </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import { IconChevronDown, IconPlus } from '@tabler/icons-vue'
import { useTranslation } from '@/Composeables/Translation.js'
import ExternalCrmContactModal from './ExternalCrmContactModal.vue'

const props = defineProps({
    component: { type: Object, required: true },
    projectId: { type: Number, required: true },
    tabId: { type: Number, required: true },
    scope: { type: Object, required: true },
})

const $t = useTranslation()

const schema = computed(() => props.component.data_schema || {})
const title = computed(() => schema.value.title || props.component.name)
const description = computed(() => schema.value.description || '')
const editable = computed(() => props.component.is_writable && props.scope.access_type === 'write')
const routeParams = computed(() => ({ project: props.projectId, tab: props.tabId, component: props.component.component_id }))

const contacts = ref([])
const contactTypes = ref([])
const maxContacts = ref(null)
const loading = ref(true)
const loadError = ref('')
const actionError = ref('')
const openIds = ref(new Set())
const typeMenuOpen = ref(false)
const modal = ref(null)

const limitReached = computed(() => maxContacts.value !== null && contacts.value.length >= maxContacts.value)
const canAdd = computed(() => editable.value && contactTypes.value.length > 0 && !limitReached.value)
const addLabel = computed(() => contactTypes.value.length === 1
    ? $t('Add {type}', { type: contactTypes.value[0].name })
    : $t('Add contact'))

async function fetchContacts() {
    loading.value = true
    loadError.value = ''
    try {
        const { data } = await axios.get(route('external.project.tab.crm-contacts.index', routeParams.value))
        contacts.value = data.contacts ?? []
        contactTypes.value = data.contact_types ?? []
        maxContacts.value = data.max_contacts ?? null
    } catch (e) {
        loadError.value = $t('Could not load data.')
    } finally {
        loading.value = false
    }
}

function isOpen(contact) {
    return openIds.value.has(contact.id)
}

function toggle(contact) {
    const next = new Set(openIds.value)
    next.has(contact.id) ? next.delete(contact.id) : next.add(contact.id)
    openIds.value = next
}

function onAddClick() {
    if (contactTypes.value.length === 1) {
        openCreate(contactTypes.value[0])
        return
    }
    typeMenuOpen.value = !typeMenuOpen.value
}

function openCreate(type) {
    typeMenuOpen.value = false
    modal.value = { contactType: type, contact: null }
}

function openEdit(contact) {
    modal.value = { contactType: contact.contact_type, contact }
}

function onSaved(saved) {
    const index = contacts.value.findIndex((c) => c.id === saved.id)
    if (index >= 0) {
        contacts.value.splice(index, 1, saved)
    } else {
        contacts.value.push(saved)
        openIds.value = new Set([...openIds.value, saved.id])
    }
}

async function remove(contact) {
    if (!window.confirm($t('Remove {name} from the list?', { name: contact.display_name }))) return
    actionError.value = ''
    try {
        await axios.delete(route('external.project.tab.crm-contacts.destroy', { ...routeParams.value, crmContact: contact.id }))
        contacts.value = contacts.value.filter((c) => c.id !== contact.id)
    } catch (e) {
        actionError.value = e?.response?.data?.message ?? $t('Could not save. Try again.')
    }
}

function formatValue(field) {
    if (field.type === 'checkbox') return field.value === '1' ? $t('Yes') : $t('No')
    if (field.type === 'date' && field.value) return new Date(field.value).toLocaleDateString()
    return field.value
}

onMounted(fetchContacts)
</script>
