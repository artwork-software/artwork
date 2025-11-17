<template>
    <div class="my-2 flex items-start gap-x-4">
        <div>
            <div>
                <label class="xsDark font-bold" :class="inSidebar ? 'xsLight' : 'xsDark'">
                   {{ $t('Project Artist')}}
                </label>
            </div>
            <div v-if="loadError" class="mt-2 text-xs text-rose-600">
                {{ loadError }}
            </div>
            <div v-else-if="loading" class="mt-2 text-xs text-secondary">
                {{ $t('Loading data...') }}
            </div>
            <div v-else class="mt-2 subpixel-antialiased xsDark">
                <span v-if="artistName">{{ artistName }}</span>
            </div>
        </div>
        <InfoButtonComponent :component="component" />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";

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

const loading = ref(false);
const loadError = ref(null);
const artistName = ref(props.project?.artist_name ?? null);

onMounted(() => {
    ensureArtistNameData();
});

async function ensureArtistNameData() {
    if (artistName.value) {
        return;
    }

    const id = props.project?.id ?? props.projectId;
    if (!id) {
        return;
    }

    loading.value = true;
    loadError.value = null;

    try {
        const response = await axios.get(route('projects.tabs.artist-name', {project: id}));
        artistName.value = response.data.artist_name ?? null;
    } catch (e) {
        loadError.value = 'Failed to load artist name.';
    } finally {
        loading.value = false;
    }
}
</script>

<style scoped>

</style>