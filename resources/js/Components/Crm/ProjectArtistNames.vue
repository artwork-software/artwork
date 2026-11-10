<template>
    <span class="inline">
        <template v-for="(part, index) in parts" :key="index">
            <template v-if="index > 0">, </template>
            <CrmContactPopoverTooltip
                v-if="part.contact"
                :contact="part.contact"
                class="!inline-flex align-baseline"
                :is-white="isWhite"
                auto-flip-vertical
                @click.stop
            >
                <span class="cursor-pointer underline decoration-dotted underline-offset-2 hover:decoration-solid">{{ part.text }}</span>
            </CrmContactPopoverTooltip>
            <span v-else>{{ part.text }}</span>
        </template>
    </span>
</template>

<script setup>
import { computed } from 'vue'
import CrmContactPopoverTooltip from '@/Components/Crm/CrmContactPopoverTooltip.vue'

/**
 * Künstler*innen eines Projekts: Anzeige-Text (Freitext plus verknüpfte CRM-Künstler*innen, wie vom Backend
 * geliefert); Namen verknüpfter Kontakte öffnen per Klick die CRM-Infokarte (Rechte prüft der Tooltip-Endpunkt).
 */
const props = defineProps({
    text: { type: String, default: '' },
    // [{ id, name }] aus projects.linked_artists
    linkedArtists: { type: Array, default: () => [] },
    isWhite: { type: Boolean, default: true },
})

const parts = computed(() => {
    const byName = new Map((props.linkedArtists ?? []).map((artist) => [String(artist.name).trim(), artist]))
    return String(props.text ?? '')
        .split(', ')
        .filter((token) => token.trim() !== '')
        .map((token) => {
            const artist = byName.get(token.trim())
            return artist
                ? { text: token, contact: { id: artist.id, display_name: artist.name } }
                : { text: token, contact: null }
        })
})
</script>
