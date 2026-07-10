<template>
    <div class="my-2 flex items-start gap-x-4">
        <div class="flex-1 min-w-0">
            <div>
                <span class="componentLabel" :class="{'!text-white': inSidebar}">
                   {{ $t('Project Artist')}}
                </span>
            </div>
            <div v-if="loadError" class="mt-2 text-xs text-rose-600">
                {{ loadError }}
            </div>
            <div v-else-if="loading" class="mt-2 text-xs text-secondary">
                {{ $t('Loading data...') }}
            </div>
            <template v-else>
                <div class="mt-2 subpixel-antialiased xsDark" :class="{'!text-white': inSidebar}">
                    <span v-if="artistName">{{ artistName }}</span>
                </div>
                <!-- Sidebar: verknüpfte CRM-Künstler*innen nur als Namen anzeigen -->
                <div v-if="inSidebar && linkedContacts.length > 0" class="mt-1 text-xs !text-white">
                    {{ linkedContacts.map((contact) => contact.display_name).join(', ') }}
                </div>
                <div v-if="!inSidebar" class="mt-2">
                    <CrmArtistLinkManager
                        :project-id="resolvedProjectId"
                        :initial-contacts="linkedContacts"
                        :can-edit="canEditLinks"
                        immediate
                    />
                </div>
            </template>
        </div>
        <InfoButtonComponent :component="component" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { usePage } from '@inertiajs/vue3';
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";
import CrmArtistLinkManager from "@/Components/Crm/CrmArtistLinkManager.vue";
import { usePermission } from '@/Composeables/Permission.js';

const props = defineProps({
    project: {
        type: Object,
        required: true
    },
    projectId: {
        type: [Number, String],
        default: null
    },
    component: {
        type: Object,
        required: true
    },
    inSidebar: {
        type: Boolean,
        required: false,
        default: false
    }
})

const page = usePage();
const { canAny, hasAdminRole } = usePermission(page.props);

const loading = ref(false);
const loadError = ref(null);
const loaded = ref(false);
const artistName = ref(props.project?.artist_name ?? null);
const linkedContacts = ref([]);

const resolvedProjectId = computed(() => props.project?.id ?? props.projectId);

// Frontend-Gate: das Backend autorisiert Link/Unlink zusätzlich via ProjectPolicy::update
const canEditLinks = computed(() => {
    if (hasAdminRole() || canAny(['write projects', 'management projects', 'create and edit own project'])) {
        return true;
    }
    const userId = page.props.auth?.user?.id;
    const writeUsers = props.project?.write_auth ?? [];
    const managers = props.project?.project_managers ?? [];
    return [...writeUsers, ...managers].some((user) => user?.id === userId);
});

onMounted(() => {
    ensureArtistNameData();
});

async function ensureArtistNameData() {
    if (loaded.value || !resolvedProjectId.value) {
        return;
    }

    loading.value = true;
    loadError.value = null;

    try {
        const response = await axios.get(route('projects.tabs.artist-name', {project: resolvedProjectId.value}));
        artistName.value = response.data.artist_name ?? null;
        linkedContacts.value = response.data.linked_crm_contacts ?? [];
        loaded.value = true;
    } catch (e) {
        loadError.value = 'Failed to load artist name.';
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>

</style>
