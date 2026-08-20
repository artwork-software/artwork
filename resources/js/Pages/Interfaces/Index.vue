<template>
    <ToolSettingsHeader :title="$t('Interfaces')">
        <SettingsGuideBanner
            class="mt-6 mb-6"
            storage-key="settings-guide.tool.interfaces"
            title="How does this area work?"
            :paragraphs="[
                'The artwork interface manages API keys with which external systems can read data from your artwork (incoming access).',
                'The Sage interface connects your Sage accounting and imports booking data into the project budgets (outgoing connection).',
            ]"
        />
        <div>
                <div class="space-y-6" v-if="canManageTokens">
                    <div @click="toggleSection(openSections.ARTWORK)"
                         class="cursor-pointer flex items-center justify-between bg-surface-sunken p-4 rounded">
                        <span class="font-semibold">{{ $t('Artwork interface') }}</span>
                        <IconChevronDown :class="['transition-transform', { 'rotate-180': openSection === openSections.ARTWORK }]"
                                         class="w-5 h-5"/>
                    </div>
                    <transition name="fade">
                        <div v-if="openSection === openSections.ARTWORK" class="p-4 bg-white rounded shadow">
                            <ArtworkApiSettings
                                :tokens="tokens"
                                :available-scopes="availableScopes"
                            />
                        </div>
                    </transition>
                </div>
                <!-- Webhooks -->
                <div class="space-y-6" v-if="canManageWebhooks">
                    <div @click="toggleSection(openSections.WEBHOOKS)"
                         class="cursor-pointer flex items-center justify-between bg-surface-sunken p-4 rounded">
                        <span class="font-semibold">{{ $t('Webhooks') }}</span>
                        <IconChevronDown :class="['transition-transform', { 'rotate-180': openSection === openSections.WEBHOOKS }]"
                                         class="w-5 h-5"/>
                    </div>
                    <transition name="fade">
                        <div v-if="openSection === openSections.WEBHOOKS" class="p-4 bg-white rounded shadow">
                            <WebhookSettings
                                :endpoints="webhookEndpoints"
                                :available-events="webhookEvents"
                            />
                        </div>
                    </transition>
                </div>
                <!-- Sage API -->
                <div class="space-y-6" v-if="canManageTokens && sageSettings">
                    <div @click="toggleSection(openSections.SAGE)"
                         class="cursor-pointer flex items-center justify-between bg-surface-sunken p-4 rounded">
                        <span class="font-semibold">{{ $t('Sage interface') }}</span>
                        <IconChevronDown :class="['transition-transform', { 'rotate-180': openSection === openSections.SAGE }]"
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
import {IconChevronDown} from "@tabler/icons-vue";
import {onMounted, ref} from 'vue'
import ToolSettingsHeader from "@/Pages/ToolSettings/ToolSettingsHeader.vue"
import SageApiSettings from "@/Pages/Interfaces/Sage/SageApiSettings.vue";
import ArtworkApiSettings from "@/Pages/Interfaces/Artwork/ArtworkApiSettings.vue";
import WebhookSettings from "@/Pages/Interfaces/Webhooks/WebhookSettings.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";

const props = defineProps({
    title: String,
    sageSettings: Object,
    tableColumnOrder: Array,
    canManageTokens: {
        type: Boolean,
        default: false
    },
    tokens: {
        type: Array,
        default: () => []
    },
    availableScopes: {
        type: Array,
        default: () => []
    },
    canManageWebhooks: {
        type: Boolean,
        default: false
    },
    webhookEndpoints: {
        type: Array,
        default: () => []
    },
    webhookEvents: {
        type: Array,
        default: () => []
    }
})

const openSections = {
    ARTWORK: 'artwork',
    WEBHOOKS: 'webhooks',
    SAGE: 'sage'
}

// Wer nur Webhooks verwalten darf, sieht die Artwork-Sektion nicht — dann startet Webhooks offen.
const initialSection = () => props.canManageTokens ? openSections.ARTWORK : openSections.WEBHOOKS

const openSection = ref(initialSection())

onMounted(() => {
    openSection.value = initialSection()
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
