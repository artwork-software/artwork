<template>
    <!-- v-if: das v-model-Objekt wird im Setup initialisiert und erreicht den Parent
         erst nach dem ersten Render-Tick -->
    <section v-if="model" class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4">
        <div>
            <h2 class="text-sm font-semibold text-text">{{ $t('Display settings') }}</h2>
            <p class="mt-1 text-xs text-text-muted">
                {{ $t('Preset from your current calendar display settings — changes only apply to this export.') }}
            </p>
        </div>

        <!-- Farbquelle -->
        <div class="space-y-2">
            <label class="block text-sm font-medium text-text-muted">
                {{ $t('Event color') }}
            </label>
            <fieldset class="flex flex-col sm:flex-row gap-2">
                <div
                    v-for="mode in colorModes"
                    :key="mode.id"
                    class="relative flex-1"
                >
                    <input
                        :id="`${idPrefix}-colorSource-${mode.id}`"
                        :name="`${idPrefix}-color-source`"
                        type="radio"
                        :value="mode.id"
                        v-model="colorSource"
                        class="peer absolute inset-0 h-0 w-0 opacity-0"
                    />
                    <label
                        :for="`${idPrefix}-colorSource-${mode.id}`"
                        class="block cursor-pointer rounded-xl border px-4 py-3 text-sm transition
                        peer-checked:border-surface-inverse peer-checked:bg-surface-inverse peer-checked:text-text-inverse
                        border-border-subtle bg-white text-text hover:bg-surface-sunken hover:text-text"
                    >
                        {{ mode.label }}
                    </label>
                </div>
            </fieldset>
        </div>

        <!-- Künstler:innen-Namen statt Termintitel -->
        <div>
            <div class="flex items-center gap-2">
                <input
                    :id="`${idPrefix}-show_artist_names_as_title`"
                    v-model="model.show_artist_names_as_title"
                    type="checkbox"
                    class="h-4 w-4 rounded border-border text-accent-600 focus:ring-accent-600"
                />
                <label :for="`${idPrefix}-show_artist_names_as_title`" class="text-sm text-text cursor-pointer">
                    {{ $t('Artist names instead of event title') }}
                </label>
            </div>
            <p v-if="artistTitleActive && !compact" class="mt-1 ml-6 text-xs text-text-muted">
                {{ $t('Artist names replace the event name; events without artists still show the event name.') }}
            </p>
        </div>

        <template v-if="!compact">
            <!-- Termininhalt -->
            <div class="border-t-2 border-dashed border-border pt-4 space-y-2">
                <label class="block text-sm font-medium text-text-muted">
                    {{ $t('Event content') }}
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-4">
                    <div v-for="option in contentOptions" :key="option.key" class="flex items-center gap-2">
                        <input
                            :id="`${idPrefix}-${option.key}`"
                            :checked="isContentOptionChecked(option.key)"
                            :disabled="isContentOptionLocked(option.key)"
                            type="checkbox"
                            class="h-4 w-4 rounded border-border text-accent-600 focus:ring-accent-600 disabled:opacity-50"
                            @change="model[option.key] = $event.target.checked"
                        />
                        <label
                            :for="`${idPrefix}-${option.key}`"
                            class="text-sm"
                            :class="isContentOptionLocked(option.key) ? 'text-text-subtle cursor-not-allowed' : 'text-text cursor-pointer'"
                        >
                            {{ option.label }}
                        </label>
                    </div>
                </div>
            </div>

            <!-- Sichtbarkeit -->
            <div class="border-t-2 border-dashed border-border pt-4 space-y-2">
                <label class="block text-sm font-medium text-text-muted">
                    {{ $t('Visibility') }}
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2 gap-x-4">
                    <div v-for="option in visibilityOptions" :key="option.key" class="flex items-center gap-2">
                        <input
                            :id="`${idPrefix}-${option.key}`"
                            v-model="model[option.key]"
                            type="checkbox"
                            class="h-4 w-4 rounded border-border text-accent-600 focus:ring-accent-600"
                        />
                        <label :for="`${idPrefix}-${option.key}`" class="text-sm text-text cursor-pointer">
                            {{ option.label }}
                        </label>
                    </div>
                </div>
            </div>
        </template>
    </section>
</template>

<script setup>
import {computed} from 'vue'
import {usePage} from '@inertiajs/vue3'
import {useTranslation} from '@/Composeables/Translation.js'

// Anzeigeeinstellungen eines Kalender-Exports: beim Öffnen mit den aktiven
// Kalender-Anzeigeeinstellungen des Users vorbelegt, pro Export anpassbar
// (die gespeicherten User-Settings bleiben unangetastet). Das v-model-Objekt
// wandert als `displaySettings` in den Export-Request.

const $t = useTranslation()

const props = defineProps({
    // Nur Farbquelle + Künstler:innen statt Titel (Spielplan-Export)
    compact: {
        type: Boolean,
        default: false,
    },
    // Tagesbemerkungen anbieten (nur PDF-Kalender, feature-/rechtegated durch den Parent)
    showDayRemarks: {
        type: Boolean,
        default: false,
    },
    // Eindeutige input-ids/names pro Einbaustelle
    idPrefix: {
        type: String,
        default: 'display',
    },
})

const model = defineModel({type: Object, default: null})

const pageProps = usePage().props

// Vorbelegung aus den aktiven Kalender-Anzeigeeinstellungen; default-true-Spalten
// (event_name, show_event_admission, show_day_remarks) auch ohne Settings-Zeile korrekt
const buildDefaults = () => {
    const settings = pageProps.auth?.user?.calendar_settings ?? {}
    return {
        use_event_status_color: !!settings.use_event_status_color,
        use_main_category_color: !!settings.use_main_category_color,
        show_artist_names_as_title: !!settings.show_artist_names_as_title,
        event_name: settings.event_name !== false,
        description: !!settings.description,
        project_artists: !!settings.project_artists,
        project_status: !!settings.project_status,
        project_management: !!settings.project_management,
        show_event_creator: !!settings.show_event_creator,
        show_event_admission: settings.show_event_admission !== false,
        show_event_status: !!settings.show_event_status,
        show_day_remarks: settings.show_day_remarks !== false,
        hide_unoccupied_rooms: !!settings.hide_unoccupied_rooms,
        show_planned_events: !!settings.show_planned_events,
    }
}

if (!model.value) {
    model.value = buildDefaults()
}

const colorModes = computed(() => [
    {id: 'eventType', label: $t('Color by event type')},
    {id: 'eventStatus', label: $t('Color by event status')},
    {id: 'mainCategory', label: $t('Color by main category of project')},
])

// Radio auf die beiden exklusiven Settings-Booleans gemappt (wie im Kalender)
const colorSource = computed({
    get: () => {
        if (model.value?.use_main_category_color) return 'mainCategory'
        if (model.value?.use_event_status_color) return 'eventStatus'
        return 'eventType'
    },
    set: (value) => {
        model.value.use_main_category_color = value === 'mainCategory'
        model.value.use_event_status_color = value === 'eventStatus'
    },
})

// „Künstler:innen-Namen statt Termintitel" koppelt die beiden Titel-Checkboxen:
// Künstler:innen erscheinen als Titel (angezeigt als angewählt), der Terminname
// wird ersetzt (angezeigt als abgewählt) — beide gesperrt. Die zugrunde liegenden
// Werte bleiben unangetastet, damit Termine ohne Künstler:innen serverseitig
// weiter auf den Terminnamen zurückfallen (gleiche Logik wie im Kalender).
const artistTitleActive = computed(() => !!model.value?.show_artist_names_as_title)

const isContentOptionLocked = (key) =>
    artistTitleActive.value && (key === 'event_name' || key === 'project_artists')

const isContentOptionChecked = (key) => {
    if (isContentOptionLocked(key)) {
        return key === 'project_artists'
    }
    return !!model.value?.[key]
}

const contentOptions = computed(() => {
    const options = [
        {key: 'event_name', label: $t('Event name')},
        {key: 'description', label: $t('Description')},
        {key: 'project_artists', label: $t('Artists')},
        {key: 'project_status', label: $t('Project Status')},
        {key: 'project_management', label: $t('Project managers')},
        {key: 'show_event_creator', label: $t('Event creator')},
    ]
    if (pageProps.event_admission_module) {
        options.push({key: 'show_event_admission', label: $t('Admission')})
    }
    if (pageProps.event_status_module) {
        options.push({key: 'show_event_status', label: $t('Event status spelled out')})
    }
    if (props.showDayRemarks) {
        options.push({key: 'show_day_remarks', label: $t('Day remarks')})
    }
    return options
})

const visibilityOptions = computed(() => [
    {key: 'hide_unoccupied_rooms', label: $t('Hide unoccupied rooms')},
    {key: 'show_planned_events', label: $t('Show planned events')},
])
</script>
