<template>
    <div class="w-full">
        <!-- Initial-Load fehlgeschlagen: ohne Ist-Stand kein Bearbeiten/Emitten -->
        <div v-if="loadFailed" class="flex items-center gap-x-3 rounded-lg border border-danger-border bg-danger-surface px-3 py-2">
            <span class="text-xs text-danger">{{ $t('Failed to load data') }}</span>
            <button
                type="button"
                class="text-xs font-medium text-accent-600 hover:underline shrink-0"
                @click="loadLinkedContacts"
            >
                {{ $t('Retry') }}
            </button>
        </div>

        <!-- Verknüpfte CRM-Künstler*innen -->
        <div v-if="linkedContacts.length > 0" class="space-y-2">
            <div
                v-for="contact in linkedContacts"
                :key="contact.id"
                class="rounded-lg border border-border-subtle bg-white overflow-hidden"
            >
                <div class="flex items-center gap-x-2 px-3 py-2">
                    <img
                        :src="contact.profile_photo_url"
                        :alt="contact.display_name"
                        class="h-7 w-7 rounded-full object-cover shrink-0"
                    />
                    <button
                        type="button"
                        class="flex flex-1 items-center gap-x-2 text-left min-w-0"
                        @click="toggleExpanded(contact.id)"
                    >
                        <span class="text-sm font-medium text-text truncate">{{ contact.display_name }}</span>
                        <span
                            v-if="contact.contact_type"
                            class="inline-flex items-center rounded-full bg-surface-sunken px-2 py-0.5 text-xs text-text-muted shrink-0"
                        >
                            {{ $t(contact.contact_type.name) }}
                        </span>
                        <component
                            :is="expandedIds.includes(contact.id) ? IconChevronUp : IconChevronDown"
                            class="h-4 w-4 text-text-subtle shrink-0"
                        />
                    </button>
                    <button
                        v-if="canEdit"
                        type="button"
                        class="text-text-subtle hover:text-danger transition-colors shrink-0"
                        :title="$t('Unlink')"
                        @click="unlink(contact)"
                    >
                        <IconLinkOff class="h-4 w-4" stroke-width="1.5" />
                    </button>
                </div>

                <!-- Aufgeklappte Karte mit den (nicht vertraulichen) Kontaktinfos -->
                <div v-if="expandedIds.includes(contact.id)" class="border-t border-border-subtle px-3 py-2">
                    <div v-if="detailsLoading[contact.id]" class="text-xs text-text-subtle py-1">
                        {{ $t('Loading data...') }}
                    </div>
                    <template v-else-if="details[contact.id]">
                        <div v-if="visibleGroupsFor(contact.id).length === 0" class="text-xs text-text-subtle py-1">
                            {{ $t('No information available') }}
                        </div>
                        <div v-for="group in visibleGroupsFor(contact.id)" :key="group.id" class="py-1.5">
                            <div class="text-xs font-semibold text-text-subtle uppercase tracking-wide mb-1">
                                {{ $t(group.name) }}
                            </div>
                            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5">
                                <div v-for="property in group.properties" :key="property.id">
                                    <dt class="text-xs text-text-subtle">{{ $t(property.name) }}</dt>
                                    <dd class="text-sm text-text break-words">
                                        <template v-if="property.type === 'checkbox'">
                                            <component
                                                :is="propertyValue(contact.id, property.id) === '1' ? IconCheck : IconX"
                                                class="h-4 w-4"
                                                :class="propertyValue(contact.id, property.id) === '1' ? 'text-success' : 'text-text-subtle'"
                                            />
                                        </template>
                                        <template v-else-if="property.type === 'link'">
                                            <a
                                                :href="propertyValue(contact.id, property.id)"
                                                target="_blank"
                                                class="text-accent-600 hover:underline"
                                            >
                                                {{ propertyValue(contact.id, property.id) }}
                                            </a>
                                        </template>
                                        <template v-else-if="property.type === 'date'">
                                            {{ formatDate(propertyValue(contact.id, property.id)) }}
                                        </template>
                                        <template v-else>
                                            {{ propertyValue(contact.id, property.id) }}
                                        </template>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                        <div v-if="canViewCrm" class="pt-1.5">
                            <a
                                :href="route('crm.contacts.show', contact.id)"
                                target="_blank"
                                class="inline-flex items-center gap-x-1 text-xs text-accent-600 hover:underline"
                            >
                                <IconExternalLink class="h-3.5 w-3.5" stroke-width="1.5" />
                                {{ $t('Open in CRM') }}
                            </a>
                        </div>
                    </template>
                    <div v-else class="text-xs text-danger py-1">
                        {{ $t('Failed to load data') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Verknüpfen-Button + Suche -->
        <div v-if="canEdit && !loadFailed" class="mt-2">
            <div v-if="!showSearch" class="flex flex-wrap items-center gap-x-4 gap-y-1">
                <button
                    type="button"
                    class="inline-flex items-center gap-x-1.5 text-xs text-accent-600 hover:text-accent-700 transition-colors"
                    @click="openSearch"
                >
                    <IconLink class="h-4 w-4" stroke-width="1.5" />
                    {{ $t('Link with CRM artist') }}
                </button>
                <button
                    v-if="canViewCrm"
                    type="button"
                    class="inline-flex items-center gap-x-1.5 text-xs text-accent-600 hover:text-accent-700 transition-colors"
                    @click="showCreateModal = true"
                >
                    <IconUserPlus class="h-4 w-4" stroke-width="1.5" />
                    {{ $t('Create new CRM artist and link') }}
                </button>
            </div>
            <div v-else class="relative">
                <div class="flex items-center gap-x-2">
                    <BaseInput
                        :id="'crm-artist-search-' + uid"
                        v-model="searchQuery"
                        :label="$t('Search CRM artist')"
                        without-translation
                        class="w-full"
                    />
                    <button
                        type="button"
                        class="text-text-subtle hover:text-text-muted transition-colors shrink-0"
                        :title="$t('Close')"
                        @click="closeSearch"
                    >
                        <IconX class="h-4 w-4" stroke-width="1.5" />
                    </button>
                </div>
                <div
                    v-if="searchQuery.length > 0"
                    class="absolute z-30 mt-1 w-full max-h-60 overflow-auto rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 text-sm"
                >
                    <div v-if="searching" class="px-4 py-3 text-xs text-text-subtle">
                        {{ $t('Loading data...') }}
                    </div>
                    <template v-else>
                        <button
                            v-for="result in selectableResults"
                            :key="result.id"
                            type="button"
                            class="flex w-full items-center gap-x-2 px-4 py-2.5 text-left hover:bg-surface-sunken"
                            @click="link(result)"
                        >
                            <img
                                :src="result.profile_photo_url"
                                :alt="result.display_name"
                                class="h-7 w-7 rounded-full object-cover"
                            />
                            <span class="truncate text-text">{{ result.display_name }}</span>
                        </button>
                        <div v-if="selectableResults.length === 0" class="px-4 py-3 text-xs text-text-subtle">
                            {{ $t('No CRM artists found') }}
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <CreateCrmArtistModal
            v-if="showCreateModal"
            @close="showCreateModal = false"
            @created="onCreatedContact"
        />
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, getCurrentInstance } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import CreateCrmArtistModal from '@/Components/Crm/CreateCrmArtistModal.vue';
import { usePermission } from '@/Composeables/Permission.js';
import {
    IconCheck,
    IconChevronDown,
    IconChevronUp,
    IconExternalLink,
    IconLink,
    IconLinkOff,
    IconUserPlus,
    IconX,
} from '@tabler/icons-vue';

const props = defineProps({
    // Bestehendes Projekt: Verknüpfungen werden initial geladen
    projectId: {
        type: [Number, String],
        default: null,
    },
    // true: Link/Unlink sofort per Request (Tab-Komponente);
    // false: nur lokale Liste + Emit der IDs (Formular-Modus im Modal)
    immediate: {
        type: Boolean,
        default: false,
    },
    canEdit: {
        type: Boolean,
        default: true,
    },
    // Bereits geladene Verknüpfungen (spart den Initial-Request)
    initialContacts: {
        type: Array,
        default: null,
    },
});

const emit = defineEmits(['update:contactIds']);

const { proxy } = getCurrentInstance();
const $t = proxy.$t;
const page = usePage();
const { can } = usePermission(page.props);

const uid = Math.random().toString(36).slice(2, 8);

const linkedContacts = ref(props.initialContacts ? [...props.initialContacts] : []);
const loadFailed = ref(false);
const expandedIds = ref([]);
const details = ref({});
const detailsLoading = ref({});

const showSearch = ref(false);
const searchQuery = ref('');
const searchResults = ref([]);
const searching = ref(false);
const showCreateModal = ref(false);
let searchTimeout = null;

const canViewCrm = computed(() => can('can view crm'));

const selectableResults = computed(() => {
    const linkedIds = new Set(linkedContacts.value.map((c) => c.id));
    return searchResults.value.filter((r) => !linkedIds.has(r.id));
});

onMounted(loadLinkedContacts);

async function loadLinkedContacts() {
    if (props.initialContacts === null && props.projectId) {
        try {
            const response = await axios.get(route('projects.tabs.artist-name', { project: props.projectId }));
            linkedContacts.value = response.data.linked_crm_contacts ?? [];
            loadFailed.value = false;
        } catch (e) {
            // Ohne geladenen Ist-Stand keine IDs emittieren — ein leeres Array
            // würde beim Speichern alle bestehenden Verknüpfungen löschen.
            loadFailed.value = true;
            return;
        }
    }
    emitIds();
}

watch(searchQuery, (value) => {
    clearTimeout(searchTimeout);
    if (!value) {
        searchResults.value = [];
        return;
    }
    searching.value = true;
    searchTimeout = setTimeout(async () => {
        try {
            const response = await axios.get(route('crm.contacts.search'), {
                params: { search: value, type_slug: 'artist' },
            });
            searchResults.value = response.data ?? [];
        } catch (e) {
            searchResults.value = [];
        } finally {
            searching.value = false;
        }
    }, 300);
});

function emitIds() {
    if (loadFailed.value) {
        return;
    }
    emit('update:contactIds', linkedContacts.value.map((c) => c.id));
}

function openSearch() {
    showSearch.value = true;
    searchQuery.value = '';
    searchResults.value = [];
}

function closeSearch() {
    showSearch.value = false;
    searchQuery.value = '';
    searchResults.value = [];
}

async function link(contact) {
    if (props.immediate && props.projectId) {
        try {
            const response = await axios.post(
                route('projects.crm-contacts.store', { project: props.projectId }),
                { crm_contact_id: contact.id }
            );
            linkedContacts.value = response.data.linked_crm_contacts ?? linkedContacts.value;
        } catch (e) {
            return;
        }
    } else {
        linkedContacts.value = [...linkedContacts.value, contact];
    }
    closeSearch();
    emitIds();

    // Karte direkt aufklappen, damit man sofort prüfen kann, ob es die richtige Person ist
    if (!expandedIds.value.includes(contact.id)) {
        expandedIds.value = [...expandedIds.value, contact.id];
    }
    loadDetails(contact.id);
}

async function unlink(contact) {
    if (props.immediate && props.projectId) {
        try {
            const response = await axios.delete(
                route('projects.crm-contacts.destroy', { project: props.projectId, crmContact: contact.id })
            );
            linkedContacts.value = response.data.linked_crm_contacts ?? linkedContacts.value;
        } catch (e) {
            return;
        }
    } else {
        linkedContacts.value = linkedContacts.value.filter((c) => c.id !== contact.id);
    }
    expandedIds.value = expandedIds.value.filter((id) => id !== contact.id);
    emitIds();
}

// Frisch im CRM angelegter Kontakt → direkt mit dem Projekt verknüpfen
function onCreatedContact(contact) {
    showCreateModal.value = false;
    link(contact);
}

function toggleExpanded(contactId) {
    if (expandedIds.value.includes(contactId)) {
        expandedIds.value = expandedIds.value.filter((id) => id !== contactId);
        return;
    }
    expandedIds.value = [...expandedIds.value, contactId];
    loadDetails(contactId);
}

async function loadDetails(contactId) {
    if (details.value[contactId] || detailsLoading.value[contactId]) {
        return;
    }
    detailsLoading.value = { ...detailsLoading.value, [contactId]: true };
    try {
        const response = await axios.get(route('crm.contacts.data', { crmContact: contactId }));
        details.value = { ...details.value, [contactId]: response.data };
    } catch (e) {
        // Fehlerzustand: details[contactId] bleibt leer, Template zeigt Hinweis
    } finally {
        detailsLoading.value = { ...detailsLoading.value, [contactId]: false };
    }
}

function propertyValue(contactId, propertyId) {
    const contact = details.value[contactId]?.contact;
    const pv = contact?.property_values?.find((v) => v.crm_property_id === propertyId);
    return pv?.value ?? '';
}

// Nur Gruppen/Properties des Kontakttyps mit vorhandenen Werten anzeigen –
// vertrauliche Gruppen filtert das Backend (crm.contacts.data) bereits heraus.
function visibleGroupsFor(contactId) {
    const data = details.value[contactId];
    if (!data) {
        return [];
    }
    const typePropertyIds = new Set((data.contact?.contact_type?.properties ?? []).map((p) => p.id));

    return (data.propertyGroups ?? [])
        .map((group) => ({
            ...group,
            properties: (group.properties ?? []).filter(
                (p) => typePropertyIds.has(p.id) && propertyValue(contactId, p.id) !== ''
            ),
        }))
        .filter((group) => group.properties.length > 0);
}

const formatDate = (value) => {
    if (!value) return '-';
    const date = new Date(value);
    if (isNaN(date.getTime())) return value;
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    return `${day}.${month}.${year}`;
};
</script>
