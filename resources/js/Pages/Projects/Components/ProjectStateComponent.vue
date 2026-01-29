<template>
    <div>
        <h3 class="headline3">{{ $t('Project status') }}</h3>
        <div v-if="loadError" class="text-xs text-rose-600">
            {{ loadError }}
        </div>
        <div v-else-if="loading" class="text-xs text-secondary">
            {{ $t('Loading data...') }}
        </div>
        <span v-else class="rounded-full items-center font-medium px-3 py-1 my-2 text-sm ml-2 mb-1 inline-flex"
              :class="isHexColor(projectState?.color) ? '' : (projectState?.color ?? '')"
              :style="getStyles(projectState?.color)">
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
        isHexColor(color) {
            if (!color) return false;
            return /^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/.test(color);
        },
        getStyles(color) {
            if (!color) return {};
            if (this.isHexColor(color)) {
                const hexColor = color.startsWith('#') ? color : '#' + color;
                return {
                    backgroundColor: hexColor,
                    color: this.getTextColor(color)
                };
            }
            return { color: this.getTextColor(color) };
        },
        getTextColor(color) {
            if (!color) return '#000000';
            // Prüfe ob es ein HEX-Farbwert ist
            const hexMatch = color.match(/^#?([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/);
            if (hexMatch) {
                let hex = hexMatch[1];
                if (hex.length === 3) {
                    hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
                }
                const r = parseInt(hex.substring(0, 2), 16);
                const g = parseInt(hex.substring(2, 4), 16);
                const b = parseInt(hex.substring(4, 6), 16);
                // Berechne relative Luminanz (WCAG Formel)
                const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
                return luminance > 0.5 ? '#000000' : '#ffffff';
            }
            // Fallback für Tailwind-Klassen
            const lightColors = [
                'bg-white', 'bg-gray-50', 'bg-gray-100', 'bg-gray-200', 'bg-gray-300',
                'bg-yellow-50', 'bg-yellow-100', 'bg-yellow-200', 'bg-yellow-300', 'bg-yellow-400',
                'bg-lime-50', 'bg-lime-100', 'bg-lime-200', 'bg-lime-300', 'bg-lime-400'
            ];
            const isLight = lightColors.some(light => color.includes(light));
            return isLight ? '#000000' : '#ffffff';
        },
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
