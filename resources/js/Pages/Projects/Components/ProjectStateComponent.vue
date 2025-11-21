<template>
    <div>
        <div v-if="loadError" class="text-xs text-rose-600">
            {{ loadError }}
        </div>
        <div v-else-if="loading" class="text-xs text-secondary">
            {{ $t('Loading data...') }}
        </div>
        <span v-else class="rounded-full items-center font-medium px-3 py-1 my-2 text-sm ml-2 mb-1 inline-flex"
              :class="projectState?.color ?? ''">
            {{ projectState?.name ?? '' }}
        </span>
    </div>
</template>
<script>
import {defineComponent} from "vue";
import axios from "axios";

export default defineComponent({
    props: [
        'project',
        'projectId',
        'loadedProjectInformation'
    ],
    data() {
        return {
            loading: false,
            loadError: null,
            projectState: this.project?.state ?? this.loadedProjectInformation?.['ProjectStateComponent'] ?? null
        };
    },
    mounted() {
        this.ensureStatusData();
    },
    methods: {
        async ensureStatusData() {
            if (this.projectState) {
                return;
            }

            const id = this.project?.id ?? this.projectId;
            if (!id) {
                return;
            }

            this.loading = true;
            this.loadError = null;

            try {
                const response = await axios.get(route('projects.tabs.status', {project: id}));
                this.projectState = response.data.state ?? null;
            } catch (e) {
                this.loadError = this.$t
                    ? this.$t('Status konnte nicht geladen werden.')
                    : 'Failed to load status.';
            } finally {
                this.loading = false;
            }
        }
    }
});
</script>
