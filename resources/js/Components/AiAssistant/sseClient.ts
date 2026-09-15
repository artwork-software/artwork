/**
 * SSE-Client der KI-Bar (core-ki-bar-ui, Konzept 0.3 §1–5, C4 §3).
 *
 * Ein Turn = ein Request. Das Session-Token geht nur im Authorization-Header raus, deshalb
 * `fetch` + `ReadableStream` statt `EventSource` (0.2 §6). Kein Reconnect, kein Retry: ein
 * Verbindungsabbruch ist ein gescheiterter Turn, den der Mensch wiederholt.
 *
 * `credentials` bleibt bewusst auf 'omit' — artwork-ai laeuft cross-origin und ist ohne Cookies
 * freigegeben (0.3 §10).
 */
import { createParser } from 'eventsource-parser'

export interface AiSseEvent {
    event: string
    data: Record<string, unknown>
}

export interface AiTurnContext {
    route?: string | null
    project_id?: number | string | null
    locale?: string | null
    artwork_version?: string | null
}

export interface AiChatRequest {
    endpoint: string
    token: string
    message: string
    clientTurnId: string
    conversationId?: string | null
    context?: AiTurnContext
    signal?: AbortSignal
}

export interface AiConfirmRequest {
    endpoint: string
    token: string
    actionId: string
    conversationId?: string | null
    signal?: AbortSignal
}

/** Fehler vor dem Stream (HTTP-Status) — die Codes entsprechen contracts/token-contract.md. */
export class AiStreamError extends Error {
    public readonly code: string
    public readonly status: number
    public readonly retryable: boolean

    constructor(code: string, status: number, retryable = false) {
        super(code)
        this.name = 'AiStreamError'
        this.code = code
        this.status = status
        this.retryable = retryable
    }
}

const RETRYABLE_CODES = ['token_expired', 'rate_limited', 'provider_error']

/** Basis-URL ohne abschliessenden Schraegstrich; lokal z.B. `/ai/dev-stub`, im Betrieb `https://…/v1`. */
function join(endpoint: string, path: string): string {
    return `${endpoint.replace(/\/+$/, '')}${path}`
}

function headers(token: string): Record<string, string> {
    return {
        'Content-Type': 'application/json',
        Accept: 'text/event-stream',
        Authorization: `Bearer ${token}`,
        // Muss dem Hostnamen in der Tenant-Registry entsprechen — ohne Port (0.2 §2).
        'X-Artwork-Instance': window.location.hostname,
    }
}

async function toStreamError(response: Response): Promise<AiStreamError> {
    let code = 'internal'

    try {
        const body = await response.json()
        if (body && typeof body.error === 'string') {
            code = body.error
        }
    } catch {
        code = response.status === 401 ? 'unauthorized' : 'internal'
    }

    return new AiStreamError(code, response.status, RETRYABLE_CODES.includes(code))
}

async function* readEvents(response: Response): AsyncGenerator<AiSseEvent> {
    if (!response.body) {
        throw new AiStreamError('internal', response.status)
    }

    const queue: AiSseEvent[] = []
    const parser = createParser({
        onEvent(message) {
            // Ohne Event-Namen ist es kein Event des Protokolls (Heartbeats sind Kommentarzeilen).
            if (!message.event) {
                return
            }

            let data: Record<string, unknown> = {}

            try {
                data = JSON.parse(message.data)
            } catch {
                return
            }

            queue.push({ event: message.event, data })
        },
    })

    const reader = response.body.getReader()
    const decoder = new TextDecoder()

    for (;;) {
        const { value, done } = await reader.read()

        if (value) {
            parser.feed(decoder.decode(value, { stream: true }))

            while (queue.length > 0) {
                yield queue.shift() as AiSseEvent
            }
        }

        if (done) {
            break
        }
    }

    while (queue.length > 0) {
        yield queue.shift() as AiSseEvent
    }
}

/** Body nach 0.3 §2; unbekannte Felder verwirft der Server, es werden keine geschickt. */
export async function streamChat(request: AiChatRequest): Promise<AsyncGenerator<AiSseEvent>> {
    const response = await fetch(join(request.endpoint, '/chat'), {
        method: 'POST',
        credentials: 'omit',
        headers: headers(request.token),
        signal: request.signal,
        body: JSON.stringify({
            conversation_id: request.conversationId ?? null,
            message: request.message,
            client_turn_id: request.clientTurnId,
            context: request.context ?? {},
        }),
    })

    if (!response.ok) {
        throw await toStreamError(response)
    }

    return readEvents(response)
}

/** Fall C (W2 fuellt die Logik): Bestaetigung ist ein neuer Request mit eigenem Stream (0.3 §6). */
export async function streamConfirm(request: AiConfirmRequest): Promise<AsyncGenerator<AiSseEvent>> {
    const response = await fetch(join(request.endpoint, `/actions/${request.actionId}/confirm`), {
        method: 'POST',
        credentials: 'omit',
        headers: headers(request.token),
        signal: request.signal,
        body: JSON.stringify({ conversation_id: request.conversationId ?? null }),
    })

    if (!response.ok) {
        throw await toStreamError(response)
    }

    return readEvents(response)
}
