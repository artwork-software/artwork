<template>
    <ArtworkBaseModal
        @close="closeModal"
        :title="$t('Project process')"
        :description="$t('Here you can see what was changed by whom and when.')"
    >
        <div class="mx-4">
            <div class="mt-2 max-h-96 overflow-y-auto pr-1.5" role="region" :aria-label="$t('Project')">
                <!-- Leerer Zustand -->
                <div
                    v-if="projectItems.length === 0"
                    class="rounded-xl border border-zinc-200 bg-zinc-50/70 p-4 text-sm text-zinc-600"
                >
                    {{ $t('No entries found') }}
                </div>

                <!-- Timeline -->
                <ol
                    v-else
                    role="list"
                    class="relative pl-8 space-y-2
                           before:content-[''] before:absolute before:left-3 before:top-0 before:bottom-0 before:w-px
                           before:bg-artwork-buttons-hover/60 before:z-0"
                >
                    <li
                        v-for="(historyItem, index) in projectItems"
                        :key="index"
                        class="relative"
                    >
                        <!-- Punkt auf der Linie -->
                        <span
                            class="absolute -left-6 top-5 block h-2.5 w-2.5 rounded-full bg-artwork-buttons-hover ring-2 ring-white z-10"
                            aria-hidden="true"
                        ></span>

                        <!-- Card -->
                        <div class="rounded-xl border border-zinc-200 bg-white/85 p-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <!-- Datum / Zeit -->
                                <span
                                    class="inline-flex h-6 items-center rounded-full border border-artwork-navigation-color/25 bg-artwork-navigation-color/10 px-2 text-[11px] font-medium text-artwork-buttons-hover tabular-nums"
                                >
                                    {{ historyItem.created_at }}
                                </span>

                                <!-- User -->
                                <UserPopoverTooltip
                                    :user="historyItem.changer || historyItem.change_by"
                                    :id="`project-${index}`"
                                    height="6"
                                    width="6"
                                />

                                <!-- Beschreibung -->
                                <div class="min-w-0 grow text-xs text-zinc-700 subpixel-antialiased">
                                    {{
                                        $t(
                                            historyItem.changes[0].translationKey,
                                            historyItem.changes[0].translationKeyPlaceholderValues
                                        )
                                    }}
                                </div>
                            </div>
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed } from 'vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import UserPopoverTooltip from '@/Layouts/Components/UserPopoverTooltip.vue'

defineOptions({ name: 'ProjectHistoryWithoutBudgetComponent' })

const props = defineProps({
    project_history: {
        type: Array,
        required: true,
    },
})

const emit = defineEmits(['closed'])

function closeModal(bool) {
    emit('closed', bool)
}

/**
 * Wir zeigen ALLES, was "project" oder "public_changes" ist.
 * - "project" ist dein neuer Typ
 * - "public_changes" ist dein alter Typ aus der ersten Version
 */
const projectItems = computed(() =>
    (props.project_history ?? []).filter(h => {
        const t = h?.changes?.[0]?.type
        return t === 'project' || t === 'public_changes'
    })
)
</script>

<style scoped>
.tabular-nums {
    font-variant-numeric: tabular-nums;
}
</style>
