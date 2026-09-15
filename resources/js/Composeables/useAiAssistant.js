/**
 * Zustand und Ablauf der KI-Bar (core-ki-bar-ui, Konzept C4 §2, §5, §6; 0.2 §4; 0.3 §1–8).
 *
 * `reactive()` auf Modulebene statt Store-Framework: die Bar ist einmal im persistenten Layout
 * gemountet, der Zustand ueberlebt Seitenwechsel.
 *
 * Das Session-Token liegt ausschliesslich hier im Speicher — nie in localStorage, sessionStorage
 * oder einem Cookie (C4 §2). Ein Reload verwirft es, die naechste Anfrage holt ein neues.
 */
import { reactive } from 'vue'
import { usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { AiStreamError, streamChat, streamConfirm } from '@/Components/AiAssistant/sseClient'
import { renderAiMarkdown } from '@/Components/AiAssistant/aiMarkdown'
import { cfg } from '@/runtimeConfig'

/** 0.2 §4: unter zwei Minuten Restlaufzeit wird vor dem naechsten Turn neu geholt. */
const TOKEN_REFRESH_MARGIN_MS = 2 * 60 * 1000

/** 0.3 §2: der Server schneidet laengere Nachrichten ab, die Bar laesst sie gar nicht erst zu. */
export const MAX_MESSAGE_LENGTH = 4000

/** 0.3 §5: nach diesen Fehlern bleibt die Eingabe gesperrt, bis die Seite neu geladen wird. */
const INPUT_LOCKING_CODES = ['budget_exceeded', 'tenant_disabled']

const state = reactive({
    open: false,
    /** idle | streaming | error | interrupted */
    status: 'idle',
    error: null,
    inputLocked: false,
    token: null,
    expiresAt: null,
    scopes: [],
    conversationId: null,
    turns: [],
    conversations: [],
    conversationsStatus: 'idle',
    pendingConfirm: null,
    panelOpen: false,
})

let controller = null
let renderHandle = null

function uuid() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID()
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (char) => {
        const random = (Math.random() * 16) | 0
        const value = char === 'x' ? random : (random & 0x3) | 0x8

        return value.toString(16)
    })
}

function endpoint() {
    return cfg('ai.endpoint_url', '')
}

/** Kontext pro Turn (C4 §5): nur Route, Projekt, Sprache, Version — nichts aus dem DOM. */
function turnContext() {
    const props = usePage().props ?? {}
    const context = {}

    if (typeof window.route === 'function') {
        context.route = window.route().current() ?? null
    }

    const projectId = props.project?.id ?? props.project?.data?.id ?? props.project_id ?? null

    if (projectId) {
        context.project_id = projectId
    }

    context.locale = props.auth?.user?.language ?? document.documentElement.lang ?? null
    context.artwork_version = props.artwork_version ?? null

    return context
}

/**
 * Holt ein Token, wenn keines da ist oder es in unter zwei Minuten ablaeuft (0.2 §4).
 * Laeuft ueber die Web-Session; axios schickt den CSRF-Cookie wie bei jedem anderen Web-Request.
 */
async function ensureToken(force = false) {
    const expiresIn = state.expiresAt ? state.expiresAt - Date.now() : 0

    if (!force && state.token && expiresIn > TOKEN_REFRESH_MARGIN_MS) {
        return state.token
    }

    const { data } = await axios.post(window.route('ai.session.store'))

    state.token = data.access_token
    state.expiresAt = Date.parse(data.expires_at)
    state.scopes = data.scopes ?? []

    return state.token
}

function newTurn(message) {
    return {
        clientTurnId: uuid(),
        turnId: null,
        question: message,
        answer: '',
        answerHtml: '',
        tools: [],
        error: null,
        confirm: null,
        result: null,
        /** streaming | completed | cancelled | error | awaiting_confirmation */
        status: 'streaming',
        retriedAfterTokenExpiry: false,
    }
}

/**
 * Zustand der Aktion nach dem Bestaetigungs-Stream (core-ki-bar-confirm).
 *
 * Die Batch-ID kann nicht ueber SSE kommen — `done` traegt laut Contract nur `reason` —, und aus
 * dem Antworttext zu lesen waere geraten. Deshalb fragt die Bar artwork danach.
 */
async function loadActionResult(turn) {
    const { data } = await axios.get(window.route('ai.actions.show', turn.confirm.actionId))

    if (data.status !== 'executed' || !data.batch) {
        return
    }

    turn.confirm.status = 'executed'
    turn.result = {
        batchId: data.batch.id,
        undoUntil: data.batch.undo_until,
        links: data.batch.links ?? [],
        /** executed | reverting | reverted */
        status: data.batch.status === 'reverted' ? 'reverted' : 'executed',
        error: null,
    }
}

/**
 * Markdown wird ueber den kumulierten Text gerendert (C4 §4) — das ist bei langen Antworten
 * quadratisch, deshalb hoechstens ein Rendern pro Frame.
 */
function scheduleRender(turn) {
    if (renderHandle !== null) {
        return
    }

    const raf = typeof requestAnimationFrame === 'function'
        ? requestAnimationFrame
        : (callback) => setTimeout(callback, 16)

    renderHandle = raf(() => {
        renderHandle = null
        turn.answerHtml = renderAiMarkdown(turn.answer)
    })
}

function renderNow(turn) {
    if (renderHandle !== null && typeof cancelAnimationFrame === 'function') {
        cancelAnimationFrame(renderHandle)
    }

    renderHandle = null
    turn.answerHtml = renderAiMarkdown(turn.answer)
}

function applyError(turn, error) {
    turn.error = error
    turn.status = 'error'
    state.error = error

    if (INPUT_LOCKING_CODES.includes(error.code)) {
        state.inputLocked = true
    }

    // 0.3 §7: ohne geschriebenes Transkript ist das Gespraech nicht fortsetzbar.
    state.status = error.code === 'transcript_failed' ? 'interrupted' : 'error'
}

function errorFrom(payload) {
    return {
        code: typeof payload?.code === 'string' ? payload.code : 'internal',
        message: typeof payload?.message === 'string' ? payload.message : '',
        retryable: payload?.retryable === true,
        retryAfterSeconds: typeof payload?.retry_after_seconds === 'number' ? payload.retry_after_seconds : null,
    }
}

/** @returns {string|null} 'token_expired', wenn der Turn mit neuem Token zu wiederholen ist */
function handleEvent(event, turn) {
    const data = event.data ?? {}

    switch (event.event) {
        case 'meta':
            if (data.protocol !== 1) {
                applyError(turn, { code: 'internal', message: '', retryable: false, retryAfterSeconds: null })

                return 'unsupported_protocol'
            }

            state.conversationId = data.conversation_id ?? state.conversationId
            turn.turnId = data.turn_id ?? null

            return null
        case 'text.delta':
            turn.answer += typeof data.text === 'string' ? data.text : ''
            scheduleRender(turn)

            return null
        case 'tool.call':
            turn.tools.push({ id: data.id, name: data.name, label: data.label, status: 'running' })

            return null
        case 'tool.result':
            turn.tools
                .filter((tool) => tool.id === data.id)
                .forEach((tool) => {
                    tool.status = data.status === 'ok' ? 'ok' : 'error'
                })

            return null
        case 'confirm.request':
            turn.confirm = {
                actionId: data.action_id,
                tool: data.tool,
                preview: data.preview,
                expiresAt: data.expires_at,
                /** pending | confirming | executed | cancelled | error */
                status: 'pending',
            }
            state.pendingConfirm = turn.confirm

            return null
        case 'error':
            if (data.code === 'token_expired') {
                return 'token_expired'
            }

            applyError(turn, errorFrom(data))

            return null
        case 'done':
            renderNow(turn)
            turn.status = typeof data.reason === 'string' ? data.reason : 'completed'

            if (turn.status === 'completed' || turn.status === 'awaiting_confirmation') {
                state.status = 'idle'
            }

            // `done reason=error` ohne vorheriges error-Event waere ein Protokollverstoss — die Bar
            // darf dann trotzdem nicht im Zustand `streaming` haengen bleiben.
            if (turn.status === 'error' && !state.error) {
                applyError(turn, { code: 'internal', message: '', retryable: false, retryAfterSeconds: null })
            }

            return null
        default:
            return null
    }
}

async function runTurn(turn, allowTokenRetry = true) {
    state.status = 'streaming'
    state.error = null
    turn.status = 'streaming'
    controller = new AbortController()

    let expired = false

    try {
        const token = await ensureToken()
        const stream = await streamChat({
            endpoint: endpoint(),
            token,
            message: turn.question,
            clientTurnId: turn.clientTurnId,
            conversationId: state.conversationId,
            context: turnContext(),
            signal: controller.signal,
        })

        for await (const event of stream) {
            if (handleEvent(event, turn) === 'token_expired') {
                expired = true
            }
        }
    } catch (error) {
        if (error?.name === 'AbortError') {
            renderNow(turn)
            turn.status = 'cancelled'
            state.status = 'idle'

            return
        }

        if (error instanceof AiStreamError && error.code === 'token_expired') {
            expired = true
        } else {
            const code = error instanceof AiStreamError ? error.code : 'internal'
            applyError(turn, { code, message: '', retryable: false, retryAfterSeconds: null })

            return
        }
    } finally {
        controller = null
    }

    if (!expired) {
        return
    }

    // 0.2 §4 / 0.3 §5: genau ein Re-Issue, danach derselbe Turn mit derselben client_turn_id.
    if (!allowTokenRetry) {
        applyError(turn, { code: 'token_expired', message: '', retryable: false, retryAfterSeconds: null })

        return
    }

    state.token = null
    state.expiresAt = null
    turn.answer = ''
    turn.answerHtml = ''
    turn.tools = []
    turn.error = null
    turn.retriedAfterTokenExpiry = true

    await ensureToken(true)
    await runTurn(turn, false)
}

export function useAiAssistant() {
    return {
        state,

        open() {
            state.open = true
        },

        close() {
            state.open = false
        },

        toggle() {
            state.open = !state.open
        },

        togglePanel() {
            state.panelOpen = !state.panelOpen
        },

        dismissError() {
            state.error = null

            if (state.status === 'error') {
                state.status = 'idle'
            }
        },

        canSend() {
            return !state.inputLocked && state.status !== 'streaming'
        },

        async send(message) {
            const text = (message ?? '').trim()

            if (text === '' || !this.canSend()) {
                return
            }

            const turn = newTurn(text.slice(0, MAX_MESSAGE_LENGTH))
            state.turns.push(turn)
            state.pendingConfirm = null

            await runTurn(turn)
        },

        /** Wiederholung nach einem wiederholbaren Fehler — neuer Turn, neue client_turn_id. */
        async retryLast() {
            const last = state.turns[state.turns.length - 1]

            if (!last || state.status === 'streaming') {
                return
            }

            state.turns.pop()
            await this.send(last.question)
        },

        cancel() {
            controller?.abort()
        },

        /**
         * Bestaetigung (Konzept W2 §7, Schritte 4–7).
         *
         * Das Write-Token lebt ausschliesslich in dieser Funktion: es geht in genau einen Aufruf
         * und ist danach weg — kein `state`, kein localStorage, kein Cookie. Nach der Ausfuehrung
         * ist es ohnehin serverseitig revoziert (W2 §3).
         */
        async confirmAction(turn) {
            const action = turn?.confirm

            if (!action || action.status !== 'pending' || state.status === 'streaming') {
                return
            }

            action.status = 'confirming'
            state.status = 'streaming'
            state.error = null
            turn.status = 'streaming'
            controller = new AbortController()

            try {
                const { data } = await axios.post(window.route('ai.session.store'), {
                    scopes: ['ai:write'],
                    action_id: action.actionId,
                })

                const stream = await streamConfirm({
                    endpoint: endpoint(),
                    token: data.access_token,
                    actionId: action.actionId,
                    conversationId: state.conversationId,
                    signal: controller.signal,
                })

                for await (const event of stream) {
                    handleEvent(event, turn)
                }

                await loadActionResult(turn)
            } catch (error) {
                action.status = 'error'
                const code = error instanceof AiStreamError ? error.code : 'internal'
                applyError(turn, { code, message: '', retryable: false, retryAfterSeconds: null })
            } finally {
                controller = null

                if (state.status === 'streaming') {
                    state.status = 'idle'
                }

                if (action.status === 'confirming') {
                    action.status = 'pending'
                }

                state.pendingConfirm = action.status === 'pending' ? action : null
            }
        },

        /** Ablehnung (W2 §6): kein Token, kein Aufruf an artwork-ai. */
        async cancelAction(turn) {
            const action = turn?.confirm

            if (!action || action.status !== 'pending') {
                return
            }

            await axios.post(window.route('ai.actions.cancel', action.actionId))

            action.status = 'cancelled'
            state.pendingConfirm = null
            state.status = 'idle'
        },

        /** Ruecknahme (W3 §2). Die Rueckfrage stellt die Komponente, hier passiert sie wirklich. */
        async revertBatch(turn) {
            const result = turn?.result

            if (!result || result.status !== 'executed') {
                return
            }

            result.status = 'reverting'
            result.error = null

            try {
                await axios.post(window.route('ai.actions.batches.revert', result.batchId))
                result.status = 'reverted'
            } catch (error) {
                result.status = 'executed'
                result.error = {
                    status: error?.response?.status ?? 0,
                    message: error?.response?.data?.message ?? '',
                }
            }
        },

        startNewConversation() {
            this.cancel()
            state.conversationId = null
            state.turns = []
            state.pendingConfirm = null
            state.error = null
            state.status = 'idle'
        },

        async loadConversations() {
            state.conversationsStatus = 'loading'

            try {
                const { data } = await axios.get(window.route('ai.conversations.index'))
                state.conversations = data.data ?? []
                state.conversationsStatus = 'idle'
            } catch {
                state.conversationsStatus = 'error'
            }
        },

        /**
         * Oeffnet ein gespeichertes Gespraech. Gelesen wird ueber den API-v1-Endpunkt mit dem
         * Session-Token (Scope `ai:transcript`, C3) — die Web-Endpunkte koennen nur auflisten
         * und loeschen.
         */
        async openConversation(id) {
            const token = await ensureToken()
            const { data } = await axios.get(window.route('api.v1.ai.conversations.show', id), {
                headers: { Authorization: `Bearer ${token}` },
            })

            state.conversationId = data.conversation?.id ?? id
            state.pendingConfirm = null
            state.error = null
            state.status = 'idle'
            state.turns = (data.turns ?? []).map((turn) => ({
                clientTurnId: turn.client_turn_id,
                turnId: turn.id,
                question: turn.user_message,
                answer: turn.assistant_message ?? '',
                answerHtml: renderAiMarkdown(turn.assistant_message ?? ''),
                tools: (turn.tool_calls ?? []).map((call) => ({
                    id: call.id ?? null,
                    name: call.name ?? '',
                    label: call.label ?? call.name ?? '',
                    status: call.status ?? 'ok',
                })),
                error: turn.error_code ? { code: turn.error_code, message: '', retryable: false, retryAfterSeconds: null } : null,
                confirm: null,
                status: turn.status,
                retriedAfterTokenExpiry: false,
            }))

            if (state.turns.some((turn) => turn.status === 'interrupted')) {
                state.status = 'interrupted'
            }
        },

        async deleteConversation(id) {
            await axios.delete(window.route('ai.conversations.destroy', id))

            state.conversations = state.conversations.filter((conversation) => conversation.id !== id)

            if (state.conversationId === id) {
                this.startNewConversation()
            }
        },
    }
}
