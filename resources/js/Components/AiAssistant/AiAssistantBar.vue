<!--
    KI-Bar (core-ki-bar-ui, Konzept C4 §1, §3, §4, §6, §8).

    Einmal im persistenten Layout gemountet, geoeffnet per Ctrl/Cmd+K oder ueber den Knopf in der
    Seitenleiste. Der Dialog von headlessui bringt Fokusfalle und Esc mit (C4 §8).

    Antworttext ist der einzige Knoten mit v-html — und der kommt aus renderAiMarkdown(), das HTML
    escaped und nur relative Links zulaesst (C4 §4). Alle anderen Modelltexte (Tool-Label,
    Fehlermeldung, Vorschau) stehen als Text im Template.
-->
<template>
    <TransitionRoot as="template" :show="state.open">
        <Dialog class="relative z-[130]" @close="assistant.close()">
            <TransitionChild
                as="template"
                enter="ease-out duration-150"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in duration-100"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-surface-inverse/40" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-y-auto p-4 sm:p-6 md:p-10">
                <div class="mx-auto flex min-h-full max-w-4xl items-start justify-center">
                    <TransitionChild
                        as="template"
                        enter="ease-out duration-150"
                        enter-from="opacity-0 translate-y-2"
                        enter-to="opacity-100 translate-y-0"
                        leave="ease-in duration-100"
                        leave-from="opacity-100 translate-y-0"
                        leave-to="opacity-0 translate-y-2"
                    >
                        <DialogPanel
                            class="flex max-h-[80vh] w-full overflow-hidden rounded-2xl border border-border-subtle bg-surface shadow-overlay"
                            data-ai-bar
                        >
                            <AiConversationPanel
                                v-if="state.panelOpen"
                                :conversations="state.conversations"
                                :active-id="state.conversationId"
                                :status="state.conversationsStatus"
                                @open="openConversation"
                                @new="assistant.startNewConversation()"
                                @delete="assistant.deleteConversation($event)"
                            />

                            <div class="flex min-w-0 flex-1 flex-col">
                                <!-- Kopfzeile -->
                                <div class="flex items-center gap-2 border-b border-border-subtle px-4 py-3">
                                    <div class="grid size-8 shrink-0 place-items-center rounded-xl bg-accent-50 text-accent-600 border border-accent-200">
                                        <PropertyIcon name="IconSparkles" class="size-4" :stroke-width="1.5" aria-hidden="true" />
                                    </div>
                                    <DialogTitle class="min-w-0 flex-1 truncate text-sm font-semibold text-text">
                                        {{ $t('AI assistant') }}
                                    </DialogTitle>

                                    <button
                                        type="button"
                                        class="rounded-md p-1.5 text-text-subtle hover:text-text"
                                        :title="$t('Conversations')"
                                        @click="togglePanel"
                                    >
                                        <PropertyIcon name="IconHistory" class="size-5" :stroke-width="1.5" aria-hidden="true" />
                                        <span class="sr-only">{{ $t('Conversations') }}</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-md p-1.5 text-text-subtle hover:text-text"
                                        :title="$t('New conversation')"
                                        @click="assistant.startNewConversation()"
                                    >
                                        <PropertyIcon name="IconPlus" class="size-5" :stroke-width="1.5" aria-hidden="true" />
                                        <span class="sr-only">{{ $t('New conversation') }}</span>
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-md p-1.5 text-text-subtle hover:text-text"
                                        :title="$t('Close')"
                                        @click="assistant.close()"
                                    >
                                        <PropertyIcon name="IconX" class="size-5" :stroke-width="1.5" aria-hidden="true" />
                                        <span class="sr-only">{{ $t('Close') }}</span>
                                    </button>
                                </div>

                                <!-- Verlauf -->
                                <div ref="scrollArea" class="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-4">
                                    <p v-if="state.turns.length === 0" class="text-sm text-text-subtle">
                                        {{ $t('Ask a question about your projects, schedules or shifts.') }}
                                    </p>

                                    <div v-for="turn in state.turns" :key="turn.clientTurnId" class="space-y-2">
                                        <div class="flex justify-end">
                                            <p class="max-w-[80%] whitespace-pre-wrap break-words rounded-2xl bg-accent-50 px-3 py-2 text-sm text-text">
                                                {{ turn.question }}
                                            </p>
                                        </div>

                                        <ul v-if="turn.tools.length > 0" class="space-y-1">
                                            <li
                                                v-for="tool in turn.tools"
                                                :key="`${turn.clientTurnId}-${tool.id}`"
                                                class="flex items-center gap-2 text-xs text-text-subtle"
                                            >
                                                <PropertyIcon
                                                    :name="tool.status === 'error' ? 'IconAlertCircle' : 'IconTool'"
                                                    class="size-4"
                                                    :class="tool.status === 'error' ? 'text-danger' : ''"
                                                    :stroke-width="1.5"
                                                    aria-hidden="true"
                                                />
                                                <span class="break-words">{{ tool.label }}</span>
                                            </li>
                                        </ul>

                                        <!-- eslint-disable-next-line vue/no-v-html -->
                                        <div
                                            v-if="turn.answerHtml"
                                            class="ai-answer max-w-none text-sm text-text"
                                            data-ai-answer
                                            v-html="turn.answerHtml"
                                        />

                                        <p v-if="turn.status === 'cancelled'" class="text-xs text-text-subtle">
                                            {{ $t('Cancelled') }}
                                        </p>

                                        <AiActionConfirm
                                            v-if="turn.confirm"
                                            :preview="turn.confirm.preview"
                                            :expires-at="turn.confirm.expiresAt"
                                        />
                                    </div>

                                    <div v-if="state.status === 'streaming'" class="flex items-center gap-2 text-xs text-text-subtle">
                                        <span class="size-2 animate-pulse rounded-full bg-accent-600" aria-hidden="true" />
                                        {{ $t('The assistant is working...') }}
                                        <button type="button" class="underline hover:text-text" @click="assistant.cancel()">
                                            {{ $t('Stop') }}
                                        </button>
                                    </div>
                                </div>

                                <!-- Fehler (0.3 §5) -->
                                <div
                                    v-if="state.error"
                                    class="mx-4 mb-2 rounded-lg border px-3 py-2 text-sm"
                                    :class="state.status === 'interrupted'
                                        ? 'border-warning-border bg-warning-surface text-text'
                                        : 'border-danger-border bg-danger-surface text-text'"
                                    data-ai-error
                                >
                                    <div class="flex items-start gap-2">
                                        <PropertyIcon name="IconAlertCircle" class="mt-0.5 size-4 shrink-0" :stroke-width="1.5" aria-hidden="true" />
                                        <div class="min-w-0 flex-1">
                                            <p class="break-words">{{ errorText }}</p>
                                            <div class="mt-2 flex gap-3 text-xs">
                                                <button
                                                    v-if="state.error.retryable && !state.inputLocked"
                                                    type="button"
                                                    class="underline"
                                                    @click="assistant.retryLast()"
                                                >
                                                    {{ $t('Retry') }}
                                                </button>
                                                <button
                                                    v-if="state.status === 'interrupted'"
                                                    type="button"
                                                    class="underline"
                                                    @click="assistant.startNewConversation()"
                                                >
                                                    {{ $t('Start a new conversation') }}
                                                </button>
                                                <button type="button" class="underline" @click="assistant.dismissError()">
                                                    {{ $t('Close') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Eingabe (C4 §8) -->
                                <div class="border-t border-border-subtle px-4 py-3">
                                    <slot name="transparency" />

                                    <div class="flex items-end gap-2">
                                        <textarea
                                            ref="input"
                                            v-model="message"
                                            rows="2"
                                            :maxlength="MAX_MESSAGE_LENGTH"
                                            :disabled="state.inputLocked"
                                            :placeholder="$t('Ask the AI assistant...')"
                                            class="min-h-11 flex-1 resize-none rounded-xl border border-border-subtle bg-surface px-3 py-2 text-sm text-text placeholder:text-text-subtle focus:border-accent-500 focus:ring-0 disabled:bg-surface-canvas"
                                            data-ai-input
                                            @keydown.enter="onEnter"
                                        />
                                        <button
                                            type="button"
                                            class="grid size-11 shrink-0 place-items-center rounded-xl bg-accent-600 text-white disabled:cursor-not-allowed disabled:opacity-50"
                                            :disabled="!canSend"
                                            data-ai-send
                                            @click="send"
                                        >
                                            <PropertyIcon name="IconSend" class="size-5" :stroke-width="1.5" aria-hidden="true" />
                                            <span class="sr-only">{{ $t('Send') }}</span>
                                        </button>
                                    </div>
                                    <p class="mt-1 text-[11px] text-text-subtle">
                                        {{ $t('Enter sends, Shift+Enter adds a line break') }}
                                    </p>
                                </div>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>

<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { useI18n } from 'vue-i18n'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'
import AiActionConfirm from '@/Components/AiAssistant/AiActionConfirm.vue'
import AiConversationPanel from '@/Components/AiAssistant/AiConversationPanel.vue'
import { MAX_MESSAGE_LENGTH, useAiAssistant } from '@/Composeables/useAiAssistant'

const assistant = useAiAssistant()
const state = assistant.state
const { t } = useI18n()

const message = ref('')
const input = ref(null)
const scrollArea = ref(null)

/** Fehlertexte je Code (0.3 §5). Die Meldung aus artwork-ai ist Modellinhalt und wird nicht gezeigt. */
const ERROR_TEXTS = {
    token_expired: 'The session has expired. Please try again.',
    rate_limited: 'Too many requests. Please wait a moment.',
    budget_exceeded: 'The monthly budget of this organisation is used up.',
    tenant_disabled: 'The assistant is switched off for this instance.',
    provider_error: 'The AI provider is not responding.',
    transcript_failed: 'The conversation could not be saved. Please start a new one.',
    unauthorized: 'The assistant could not authenticate. Please reload the page.',
    internal: 'Unexpected error.',
}

const errorText = computed(() => {
    if (!state.error) {
        return ''
    }

    const base = t(ERROR_TEXTS[state.error.code] ?? ERROR_TEXTS.internal)

    if (state.error.code === 'rate_limited' && state.error.retryAfterSeconds) {
        return `${base} (${state.error.retryAfterSeconds} s)`
    }

    return base
})

const canSend = computed(() => message.value.trim() !== '' && assistant.canSend())

const send = async () => {
    if (!canSend.value) {
        return
    }

    const text = message.value
    message.value = ''
    await assistant.send(text)
}

/** Enter sendet, Shift+Enter macht einen Zeilenumbruch (C4 §8). */
const onEnter = (event) => {
    if (event.shiftKey) {
        return
    }

    event.preventDefault()
    send()
}

const togglePanel = async () => {
    assistant.togglePanel()

    if (state.panelOpen) {
        await assistant.loadConversations()
    }
}

const openConversation = async (id) => {
    await assistant.openConversation(id)
}

/**
 * Ctrl/Cmd+K oeffnet und schliesst die Bar. Der Listener haengt am Dokument, unterdrueckt die
 * Standardaktion aber nicht, solange in einem contenteditable getippt wird (Brief „Fallstricke").
 */
const onKeydown = (event) => {
    if (event.key?.toLowerCase() !== 'k' || !(event.metaKey || event.ctrlKey) || event.altKey) {
        return
    }

    if (event.target?.isContentEditable) {
        return
    }

    event.preventDefault()
    assistant.toggle()
}

const scrollToBottom = () => {
    if (scrollArea.value) {
        scrollArea.value.scrollTop = scrollArea.value.scrollHeight
    }
}

watch(
    () => state.turns.map((turn) => turn.answer.length + turn.tools.length).join(','),
    () => nextTick(scrollToBottom),
)

watch(
    () => state.open,
    async (open) => {
        if (!open) {
            return
        }

        await nextTick()
        input.value?.focus()
        scrollToBottom()
    },
)

onMounted(() => document.addEventListener('keydown', onKeydown))
onUnmounted(() => document.removeEventListener('keydown', onKeydown))
</script>

<style scoped>
/* Der einzige v-html-Knoten: Markdown aus renderAiMarkdown(). */
.ai-answer :deep(p) { margin-bottom: 0.5rem; }
.ai-answer :deep(p:last-child) { margin-bottom: 0; }
.ai-answer :deep(ul) { list-style: disc; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.ai-answer :deep(ol) { list-style: decimal; padding-left: 1.25rem; margin-bottom: 0.5rem; }
.ai-answer :deep(h1), .ai-answer :deep(h2), .ai-answer :deep(h3) { font-weight: 600; margin-bottom: 0.25rem; }
.ai-answer :deep(a) { text-decoration: underline; }
.ai-answer :deep(code) { font-family: ui-monospace, monospace; font-size: 0.85em; }
.ai-answer :deep(pre) { overflow-x: auto; padding: 0.5rem; border-radius: 0.5rem; background: var(--color-surface-sunken); }
</style>
