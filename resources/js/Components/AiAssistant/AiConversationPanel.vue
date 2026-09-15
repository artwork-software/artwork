<!--
    Seitenpanel der KI-Bar: eigene Gespraeche auflisten, oeffnen, neu anlegen, loeschen
    (core-ki-bar-ui, Konzept C4 §6, C3 §5).

    Die Liste kommt aus GET /ai/conversations (Web-Guard, immer nur die eigenen Gespraeche).
    Geloescht wird erst nach einer Rueckfrage direkt am Eintrag.
-->
<template>
    <aside class="flex w-64 shrink-0 flex-col border-r border-border-subtle bg-surface-sunken/60">
        <div class="flex items-center justify-between px-3 py-2">
            <h3 class="text-xs font-semibold uppercase tracking-wide text-text-subtle">{{ $t('Conversations') }}</h3>
            <button
                type="button"
                class="rounded-md p-1 text-text-subtle hover:text-text"
                :title="$t('New conversation')"
                @click="$emit('new')"
            >
                <PropertyIcon name="IconPlus" class="size-4" :stroke-width="1.5" aria-hidden="true" />
                <span class="sr-only">{{ $t('New conversation') }}</span>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto px-2 pb-3">
            <p v-if="status === 'loading'" class="px-1 py-2 text-xs text-text-subtle">{{ $t('Loading...') }}</p>
            <p v-else-if="status === 'error'" class="px-1 py-2 text-xs text-danger">{{ $t('Unexpected error.') }}</p>
            <p v-else-if="conversations.length === 0" class="px-1 py-2 text-xs text-text-subtle">
                {{ $t('No conversations yet') }}
            </p>

            <ul v-else class="space-y-1">
                <li v-for="conversation in conversations" :key="conversation.id">
                    <div
                        class="group rounded-lg px-2 py-1.5"
                        :class="conversation.id === activeId ? 'bg-white shadow-sm' : 'hover:bg-white/70'"
                    >
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="min-w-0 flex-1 text-left"
                                @click="$emit('open', conversation.id)"
                            >
                                <span class="block truncate text-sm text-text">{{ conversation.title }}</span>
                                <span class="block text-[11px] text-text-subtle">
                                    {{ formatDate(conversation.last_turn_at) }}
                                    <template v-if="conversation.status === 'interrupted'">
                                        · {{ $t('Conversation interrupted') }}
                                    </template>
                                </span>
                            </button>
                            <button
                                type="button"
                                class="shrink-0 rounded-md p-1 text-text-subtle opacity-0 transition group-hover:opacity-100 hover:text-danger"
                                :title="$t('Delete')"
                                @click="pendingDeleteId = conversation.id"
                            >
                                <PropertyIcon name="IconTrash" class="size-4" :stroke-width="1.5" aria-hidden="true" />
                                <span class="sr-only">{{ $t('Delete') }}</span>
                            </button>
                        </div>

                        <div v-if="pendingDeleteId === conversation.id" class="mt-2 rounded-md bg-danger-surface p-2">
                            <p class="text-xs text-text">{{ $t('Delete conversation?') }}</p>
                            <div class="mt-2 flex gap-2">
                                <button
                                    type="button"
                                    class="rounded-md bg-danger px-2 py-1 text-xs font-semibold text-white"
                                    @click="confirmDelete(conversation.id)"
                                >
                                    {{ $t('Delete') }}
                                </button>
                                <button
                                    type="button"
                                    class="rounded-md border border-border-subtle px-2 py-1 text-xs text-text"
                                    @click="pendingDeleteId = null"
                                >
                                    {{ $t('Cancel') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
    </aside>
</template>

<script setup>
import { ref } from 'vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

defineProps({
    conversations: {
        type: Array,
        default: () => [],
    },
    activeId: {
        type: String,
        default: null,
    },
    status: {
        type: String,
        default: 'idle',
    },
})

const emit = defineEmits(['open', 'new', 'delete'])

const pendingDeleteId = ref(null)

const confirmDelete = (id) => {
    pendingDeleteId.value = null
    emit('delete', id)
}

const formatDate = (value) => {
    if (!value) {
        return ''
    }

    const date = new Date(value)

    return Number.isNaN(date.getTime()) ? '' : date.toLocaleString()
}
</script>
