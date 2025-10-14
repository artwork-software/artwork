<template>
    <ToolSettingsHeader :title="$t('Interfaces')">
        <div>
                <div class="space-y-6">
                    <div @click="toggleSection(openSections.ARTWORK)"
                         class="cursor-pointer flex items-center justify-between bg-gray-100 p-4 rounded">
                        <span class="font-semibold">{{ $t('Artwork interface') }}</span>
                        <ChevronDownIcon :class="['transition-transform', { 'rotate-180': openSection === openSections.ARTWORK }]"
                                         class="w-5 h-5"/>
                    </div>
                    <transition name="fade">
                        <div v-if="openSection === openSections.ARTWORK" class="p-4 bg-white rounded shadow">
                            <ArtworkApiSettings
                                :tokens="tokens"
                            />
                        </div>
                    </transition>
                </div>
                <!-- Sage API -->
                <div class="space-y-6" v-if="sageSettings">
                    <div @click="toggleSection(openSections.SAGE)"
                         class="cursor-pointer flex items-center justify-between bg-gray-100 p-4 rounded">
                        <span class="font-semibold">{{ $t('Sage interface') }}</span>
                        <ChevronDownIcon :class="['transition-transform', { 'rotate-180': openSection === openSections.SAGE }]"
                                         class="w-5 h-5"/>
                    </div>
                    <transition name="fade">
                        <div v-if="openSection === openSections.SAGE" class="p-4 bg-white rounded shadow">
                            <SageApiSettings :sage-settings="sageSettings" :table-column-order="tableColumnOrder"/>
                        </div>
                    </transition>
                </div>
            </div>
    </ToolSettingsHeader>
</template>

<script setup>
import {onMounted, ref} from 'vue'
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue"
import {ChevronDownIcon} from '@heroicons/vue/solid'
import SageApiSettings from "@/Pages/Interfaces/Sage/SageApiSettings.vue";
import ArtworkApiSettings from "@/Pages/Interfaces/Artwork/ArtworkApiSettings.vue";

defineProps({
    title: String,
    sageSettings: Object,
    tableColumnOrder: Array,
    tokens: {
        type: Array,
        default: () => []
    }
})

const openSections = {
    ARTWORK: 'artwork',
    SAGE: 'sage'
}

const openSection = ref(openSections.ARTWORK)

onMounted(() => {
    openSection.value = openSections.ARTWORK
})

function toggleSection(section) {
    openSection.value = openSection.value === section ? null : section
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: all 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-5px);
}

/* Ensure dropdowns in child components are visible */
:deep(.dropdown-menu) {
    z-index: 50;
    position: absolute;
}
</style>
