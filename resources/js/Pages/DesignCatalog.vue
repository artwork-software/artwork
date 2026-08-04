<template>
    <div class="min-h-screen bg-surface-canvas p-8">
        <div class="mx-auto max-w-6xl space-y-12 pb-24">
            <header>
                <h1 class="font-lexend text-2xl font-semibold text-text">Design-Katalog — Design-Basis v1</h1>
                <p class="mt-1 text-xs text-text-muted">
                    Nur lokal erreichbar (/_design). Vergleichsbasis für alle Katalog-Komponenten in allen Varianten und Zuständen.
                </p>
            </header>

            <!-- ============ TOKENS ============ -->
            <section class="space-y-6">
                <h2 class="font-lexend text-lg font-semibold text-text">Farb- &amp; Token-Swatches</h2>

                <div>
                    <h3 class="mb-2 font-lexend text-[15px] font-semibold text-text">Flächen</h3>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div v-for="swatch in surfaceSwatches" :key="swatch.token" class="rounded-md border border-border-subtle bg-surface p-2">
                            <div class="h-12 rounded-sm border border-border-subtle" :class="swatch.class" />
                            <div class="mt-1.5 text-[11px] font-semibold text-text tabular-nums">{{ swatch.token }}</div>
                            <div class="text-[11px] text-text-subtle tabular-nums">{{ swatch.hex }}</div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="mb-2 font-lexend text-[15px] font-semibold text-text">Rahmen</h3>
                    <div class="grid grid-cols-3 gap-3">
                        <div v-for="swatch in borderSwatches" :key="swatch.token" class="rounded-md border border-border-subtle bg-surface p-2">
                            <div class="h-12 rounded-sm border-2 bg-surface" :class="swatch.class" />
                            <div class="mt-1.5 text-[11px] font-semibold text-text tabular-nums">{{ swatch.token }}</div>
                            <div class="text-[11px] text-text-subtle tabular-nums">{{ swatch.hex }}</div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="mb-2 font-lexend text-[15px] font-semibold text-text">Text</h3>
                    <div class="space-y-1 rounded-md border border-border-subtle bg-surface p-4">
                        <p class="text-sm text-text">text-text — Primärer Text (#1C1F24)</p>
                        <p class="text-sm text-text-muted">text-text-muted — Sekundärer Text (#5F6169)</p>
                        <p class="text-sm text-text-subtle">text-text-subtle — Tertiärer Text (#74767E)</p>
                        <p class="rounded-sm bg-surface-inverse px-2 py-1 text-sm text-text-inverse">text-text-inverse auf bg-surface-inverse (#F4F4F2 auf #222831)</p>
                    </div>
                </div>

                <div>
                    <h3 class="mb-2 font-lexend text-[15px] font-semibold text-text">Akzent</h3>
                    <div class="flex flex-wrap gap-3">
                        <div v-for="swatch in accentSwatches" :key="swatch.token" class="w-24 rounded-md border border-border-subtle bg-surface p-2">
                            <div class="h-10 rounded-sm" :class="swatch.class" />
                            <div class="mt-1.5 text-[11px] font-semibold text-text tabular-nums">{{ swatch.token }}</div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="mb-2 font-lexend text-[15px] font-semibold text-text">Status</h3>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div
                            v-for="status in statusSwatches"
                            :key="status.name"
                            class="rounded-md border p-3"
                            :class="[status.surfaceClass, status.borderClass]"
                        >
                            <div class="text-sm font-medium" :class="status.textClass">{{ status.name }}</div>
                            <div class="mt-0.5 text-[11px] tabular-nums" :class="status.textClass">bg-{{ status.name }}-surface · border-{{ status.name }}-border</div>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="mb-2 font-lexend text-[15px] font-semibold text-text">Radien, Schatten &amp; Typo</h3>
                    <div class="flex flex-wrap items-start gap-3">
                        <div class="rounded-sm border border-border bg-surface px-4 py-3 text-xs text-text-muted">rounded-sm (4px)</div>
                        <div class="rounded-md border border-border bg-surface px-4 py-3 text-xs text-text-muted">rounded-md (6px)</div>
                        <div class="rounded-lg border border-border bg-surface px-4 py-3 text-xs text-text-muted">rounded-lg (10px)</div>
                        <div class="rounded-full border border-border bg-surface px-4 py-3 text-xs text-text-muted">rounded-full (pill)</div>
                        <div class="rounded-md bg-surface px-4 py-3 text-xs text-text-muted shadow-raised">shadow-raised</div>
                        <div class="rounded-md bg-surface px-4 py-3 text-xs text-text-muted shadow-overlay">shadow-overlay</div>
                    </div>
                    <div class="mt-3 space-y-1 rounded-md border border-border-subtle bg-surface p-4">
                        <p class="font-lexend text-lg font-semibold text-text">Heading — font-lexend semibold</p>
                        <p class="text-[13px] text-text tabular-nums">Data — Inter 13px tabular-nums: 1.234,56 € · 08:15–17:30</p>
                        <p class="text-xs text-text-muted">Caption — text-xs text-text-muted</p>
                        <p class="text-[11px] font-semibold text-text tabular-nums">Micro — Inter 600 11px tabular-nums: KW 32</p>
                    </div>
                </div>
            </section>

            <!-- ============ BUTTONS ============ -->
            <section class="space-y-4">
                <h2 class="font-lexend text-lg font-semibold text-text">BaseUIButton</h2>
                <div v-for="variant in buttonVariants" :key="variant" class="rounded-md border border-border-subtle bg-surface p-4">
                    <div class="mb-3 text-[11px] font-semibold text-text-muted tabular-nums">variant="{{ variant }}"</div>
                    <div class="space-y-3">
                        <div v-for="size in buttonSizes" :key="size" class="flex flex-wrap items-center gap-3">
                            <span class="w-8 text-[11px] text-text-subtle tabular-nums">{{ size }}</span>
                            <BaseUIButton :variant="variant" :size="size" label="Normal" hide-icon />
                            <BaseUIButton :variant="variant" :size="size" label="Disabled" hide-icon disabled />
                            <BaseUIButton :variant="variant" :size="size" label="Processing" processing />
                            <BaseUIButton :variant="variant" :size="size" label="Mit Icon" icon="IconCalendar" />
                            <BaseUIButton :variant="variant" :size="size" icon="IconSettings" aria-label="Nur Icon" />
                        </div>
                    </div>
                </div>
            </section>

            <!-- ============ FORM ELEMENTS ============ -->
            <section class="space-y-4">
                <h2 class="font-lexend text-lg font-semibold text-text">Formularelemente</h2>

                <div class="rounded-md border border-border-subtle bg-surface p-4">
                    <h3 class="mb-3 font-lexend text-[15px] font-semibold text-text">BaseInput</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <BaseInput id="demo-input-normal" v-model="demo.inputNormal" label="Normal" without-translation />
                        <BaseInput id="demo-input-required" v-model="demo.inputRequired" label="Pflichtfeld" required without-translation />
                        <BaseInput id="demo-input-error" v-model="demo.inputError" label="Mit Fehler" error="Dieses Feld ist ungültig." without-translation />
                        <BaseInput id="demo-input-disabled" v-model="demo.inputDisabled" label="Deaktiviert" disabled without-translation />
                        <BaseInput id="demo-input-small" v-model="demo.inputSmall" label="Klein (isSmall)" is-small without-translation />
                    </div>
                </div>

                <div class="rounded-md border border-border-subtle bg-surface p-4">
                    <h3 class="mb-3 font-lexend text-[15px] font-semibold text-text">BaseTextarea</h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <BaseTextarea id="demo-textarea-normal" v-model="demo.textareaNormal" label="Beschreibung" :rows="3" />
                        <BaseTextarea id="demo-textarea-required" v-model="demo.textareaRequired" label="Pflicht-Beschreibung" required :rows="3" />
                        <BaseTextarea id="demo-textarea-disabled" v-model="demo.textareaDisabled" label="Deaktiviert" disabled :rows="3" />
                    </div>
                </div>

                <div class="rounded-md border border-border-subtle bg-surface p-4">
                    <h3 class="mb-3 font-lexend text-[15px] font-semibold text-text">BaseCheckbox</h3>
                    <div class="space-y-3">
                        <BaseCheckbox v-model="demo.checkboxNormal" label="Normal" description="Mit Beschreibungstext" />
                        <BaseCheckbox v-model="demo.checkboxChecked" label="Angehakt" />
                        <BaseCheckbox v-model="demo.checkboxRequired" label="Pflichtfeld" required />
                        <BaseCheckbox :model-value="true" label="Deaktiviert (angehakt)" disabled />
                        <BaseCheckbox :model-value="false" label="Deaktiviert" disabled />
                    </div>
                </div>

                <div class="rounded-md border border-border-subtle bg-surface p-4">
                    <h3 class="mb-3 font-lexend text-[15px] font-semibold text-text">ArtworkBaseToggle</h3>
                    <div class="space-y-3">
                        <ArtworkBaseToggle v-model="demo.toggleOn" label="Aktiv" description="Mit Beschreibungstext" />
                        <ArtworkBaseToggle v-model="demo.toggleOff" label="Inaktiv" />
                        <ArtworkBaseToggle :model-value="true" label="Deaktiviert (an)" disabled />
                        <ArtworkBaseToggle v-model="demo.toggleSmall" label="Kompakt (isSmall)" is-small />
                    </div>
                </div>
            </section>

            <!-- ============ CHIPS ============ -->
            <section class="space-y-4">
                <h2 class="font-lexend text-lg font-semibold text-text">Chips</h2>
                <div class="rounded-md border border-border-subtle bg-surface p-4">
                    <h3 class="mb-3 font-lexend text-[15px] font-semibold text-text">BaseChip</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <BaseChip variant="neutral">Neutral</BaseChip>
                        <BaseChip variant="accent">Akzent</BaseChip>
                        <BaseChip variant="success">Erfolg</BaseChip>
                        <BaseChip variant="warning">Warnung</BaseChip>
                        <BaseChip variant="danger">Gefahr</BaseChip>
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <BaseChip variant="neutral" :count="12">Neutral</BaseChip>
                        <BaseChip variant="accent" :count="3">Akzent</BaseChip>
                        <BaseChip variant="success" :count="99">Erfolg</BaseChip>
                        <BaseChip variant="warning" count="7+">Warnung</BaseChip>
                        <BaseChip variant="danger" :count="0">Gefahr</BaseChip>
                    </div>
                </div>
                <div class="rounded-md border border-border-subtle bg-surface p-4">
                    <h3 class="mb-3 font-lexend text-[15px] font-semibold text-text">FilterChip</h3>
                    <div class="flex flex-wrap items-center gap-2">
                        <FilterChip
                            v-for="chip in demo.filterChips"
                            :key="chip"
                            :label="chip"
                            @remove="removeFilterChip(chip)"
                        />
                        <BaseUIButton
                            v-if="demo.filterChips.length === 0"
                            variant="ghost"
                            size="sm"
                            label="Chips zurücksetzen"
                            hide-icon
                            @click="resetFilterChips"
                        />
                    </div>
                </div>
            </section>

            <!-- ============ FEEDBACK ============ -->
            <section class="space-y-4">
                <h2 class="font-lexend text-lg font-semibold text-text">Feedback</h2>

                <div class="rounded-md border border-border-subtle bg-surface p-4">
                    <h3 class="mb-3 font-lexend text-[15px] font-semibold text-text">EmptyState</h3>
                    <EmptyState
                        title="Keine Projekte vorhanden"
                        description="Lege ein erstes Projekt an, um hier eine Übersicht zu sehen."
                        action-label="Projekt anlegen"
                        icon="IconFolderPlus"
                        @action="demo.emptyStateClicks++"
                    />
                    <p v-if="demo.emptyStateClicks" class="mt-2 text-[11px] text-text-subtle tabular-nums">action-Emit ausgelöst: {{ demo.emptyStateClicks }}×</p>
                    <div class="mt-4">
                        <EmptyState title="Nur Titel, ohne Beschreibung und Aktion" />
                    </div>
                </div>

                <div class="rounded-md border border-border-subtle bg-surface p-4">
                    <h3 class="mb-3 font-lexend text-[15px] font-semibold text-text">BaseSkeleton</h3>
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                        <div>
                            <p class="mb-2 text-[11px] text-text-subtle">line (3 Zeilen)</p>
                            <BaseSkeleton variant="line" :lines="3" />
                        </div>
                        <div>
                            <p class="mb-2 text-[11px] text-text-subtle">block (width/height als Klassen)</p>
                            <BaseSkeleton variant="block" width="w-full" height="h-20" />
                        </div>
                        <div>
                            <p class="mb-2 text-[11px] text-text-subtle">circle (48px per Style)</p>
                            <BaseSkeleton variant="circle" width="48px" height="48px" />
                        </div>
                    </div>
                </div>

                <div class="rounded-md border border-border-subtle bg-surface p-4">
                    <h3 class="mb-3 font-lexend text-[15px] font-semibold text-text">BaseAlertComponent</h3>
                    <BaseAlertComponent type="success" message="Erfolgreich gespeichert." />
                    <BaseAlertComponent type="info" message="Hinweis: Diese Einstellung wirkt sich auf alle Nutzer*innen aus." />
                    <BaseAlertComponent type="warning" message="Achtung: Es gibt ungespeicherte Änderungen." />
                    <BaseAlertComponent type="error" message="Fehler: Die Aktion konnte nicht ausgeführt werden." />
                </div>
            </section>

            <!-- ============ KPI ============ -->
            <section class="space-y-4">
                <h2 class="font-lexend text-lg font-semibold text-text">KpiTile</h2>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <KpiTile label="Projekte" :value="128" icon="IconFolder" trend="+12 seit letztem Monat" />
                    <KpiTile label="Auslastung" value="87 %" icon="IconChartBar" />
                    <KpiTile label="Offene Anfragen" :value="14" icon="IconInbox" trend="−3 diese Woche" href="/_design" />
                    <KpiTile label="Ohne Icon &amp; Trend" :value="42" />
                </div>
                <p class="text-[11px] text-text-subtle">Die dritte Kachel ist ein Inertia-Link (href auf diese Seite).</p>
            </section>

            <!-- ============ TABLE ============ -->
            <section class="space-y-4">
                <h2 class="font-lexend text-lg font-semibold text-text">BaseTable</h2>
                <div class="rounded-md border border-border-subtle bg-surface p-4">
                    <h3 class="font-lexend text-[15px] font-semibold text-text">Mit Daten</h3>
                    <BaseTable :rows="tableRows" :columns="tableColumns">
                        <template #cell-status="{ value }">
                            <BaseChip :variant="value === 'Aktiv' ? 'success' : value === 'Entwurf' ? 'neutral' : 'warning'">{{ value }}</BaseChip>
                        </template>
                        <template #row-actions>
                            <BaseUIButton variant="ghost" size="sm" label="Öffnen" hide-icon />
                        </template>
                    </BaseTable>
                </div>
                <div class="rounded-md border border-border-subtle bg-surface p-4">
                    <h3 class="font-lexend text-[15px] font-semibold text-text">Empty-State</h3>
                    <BaseTable
                        :rows="[]"
                        :columns="tableColumns"
                        empty-title="Keine Einträge"
                        empty-message="Es sind aktuell keine Daten vorhanden."
                    >
                        <template #empty-cta>
                            <BaseUIButton variant="secondary" size="sm" label="Eintrag anlegen" hide-icon />
                        </template>
                    </BaseTable>
                </div>
            </section>

            <!-- ============ TABS ============ -->
            <section class="space-y-4">
                <h2 class="font-lexend text-lg font-semibold text-text">BaseTabs</h2>
                <div class="rounded-md border border-border-subtle bg-surface p-4">
                    <BaseTabs
                        :tabs="demoTabs"
                        navigation-mode="buttons"
                        :use-translation="false"
                        @tab-click="selectTab"
                    />
                    <p class="text-xs text-text-muted">Aktiver Tab: <span class="font-semibold text-text">{{ activeTabName }}</span></p>
                </div>
            </section>

            <!-- ============ MODAL ============ -->
            <section class="space-y-4">
                <h2 class="font-lexend text-lg font-semibold text-text">ArtworkBaseModal</h2>
                <div class="rounded-md border border-border-subtle bg-surface p-4">
                    <BaseUIButton variant="primary" size="md" label="Modal öffnen" icon="IconWindowMaximize" @click="demo.modalOpen = true" />
                    <ArtworkBaseModal
                        v-if="demo.modalOpen"
                        title="Demo-Modal"
                        description="So sieht das Standard-Modal aus."
                        @close="demo.modalOpen = false"
                    >
                        <div class="space-y-4">
                            <p class="text-[13px] text-text">Modal-Inhalt mit Formularelementen:</p>
                            <BaseInput id="demo-modal-input" v-model="demo.modalInput" label="Name" without-translation />
                            <div class="flex justify-end gap-2">
                                <BaseUIButton variant="secondary" size="md" label="Abbrechen" hide-icon @click="demo.modalOpen = false" />
                                <BaseUIButton variant="primary" size="md" label="Speichern" icon="IconDeviceFloppy" @click="demo.modalOpen = false" />
                            </div>
                        </div>
                    </ArtworkBaseModal>
                </div>
            </section>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import ArtworkBaseToggle from '@/Artwork/Toggles/ArtworkBaseToggle.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import FilterChip from '@/Artwork/Filter/FilterChip.vue'
import EmptyState from '@/Artwork/Feedback/EmptyState.vue'
import BaseSkeleton from '@/Artwork/Feedback/BaseSkeleton.vue'
import KpiTile from '@/Artwork/Cards/KpiTile.vue'
import BaseAlertComponent from '@/Components/Alerts/BaseAlertComponent.vue'
import BaseTable from '@/Artwork/Table/BaseTable.vue'
import BaseTabs from '@/Artwork/Tabs/BaseTabs.vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'

defineOptions({ name: 'DesignCatalog' })

/* ---------- Token-Swatches ---------- */
const surfaceSwatches = [
    { token: 'bg-surface', class: 'bg-surface', hex: '#FFFFFF' },
    { token: 'bg-surface-canvas', class: 'bg-surface-canvas', hex: '#F6F6F4' },
    { token: 'bg-surface-sunken', class: 'bg-surface-sunken', hex: '#F1F1EE' },
    { token: 'bg-surface-inverse', class: 'bg-surface-inverse', hex: '#222831' },
]

const borderSwatches = [
    { token: 'border-border-subtle', class: 'border-border-subtle', hex: '#E7E7E3' },
    { token: 'border-border', class: 'border-border', hex: '#D6D6D0' },
    { token: 'border-border-strong', class: 'border-border-strong', hex: '#A9A9A1' },
]

const accentSwatches = [
    { token: 'accent-50', class: 'bg-accent-50' },
    { token: 'accent-100', class: 'bg-accent-100' },
    { token: 'accent-200', class: 'bg-accent-200' },
    { token: 'accent-500', class: 'bg-accent-500' },
    { token: 'accent-600', class: 'bg-accent-600' },
    { token: 'accent-700', class: 'bg-accent-700' },
    { token: 'accent-900', class: 'bg-accent-900' },
]

const statusSwatches = [
    { name: 'success', surfaceClass: 'bg-success-surface', borderClass: 'border-success-border', textClass: 'text-success' },
    { name: 'warning', surfaceClass: 'bg-warning-surface', borderClass: 'border-warning-border', textClass: 'text-warning' },
    { name: 'danger', surfaceClass: 'bg-danger-surface', borderClass: 'border-danger-border', textClass: 'text-danger' },
    { name: 'info', surfaceClass: 'bg-info-surface', borderClass: 'border-info-border', textClass: 'text-info' },
]

/* ---------- Buttons ---------- */
const buttonVariants = ['primary', 'secondary', 'ghost', 'danger']
const buttonSizes = ['sm', 'md', 'lg']

/* ---------- Demo-State ---------- */
const demo = reactive({
    inputNormal: '',
    inputRequired: '',
    inputError: 'Fehlerhafter Wert',
    inputDisabled: 'Nicht änderbar',
    inputSmall: '',
    textareaNormal: '',
    textareaRequired: '',
    textareaDisabled: 'Nicht änderbar',
    checkboxNormal: false,
    checkboxChecked: true,
    checkboxRequired: false,
    toggleOn: true,
    toggleOff: false,
    toggleSmall: true,
    filterChips: ['Zeitraum: 01.08.2026–31.08.2026', 'Raum: Große Bühne', 'Status: Bestätigt'],
    emptyStateClicks: 0,
    modalOpen: false,
    modalInput: '',
})

function removeFilterChip(chip) {
    demo.filterChips = demo.filterChips.filter((c) => c !== chip)
}

function resetFilterChips() {
    demo.filterChips = ['Zeitraum: 01.08.2026–31.08.2026', 'Raum: Große Bühne', 'Status: Bestätigt']
}

/* ---------- Tabelle ---------- */
const tableColumns = [
    { key: 'name', label: 'Name', sortable: true },
    { key: 'status', label: 'Status' },
    { key: 'date', label: 'Datum', sortable: true },
    { key: 'budget', label: 'Budget', align: 'right' },
]

const tableRows = [
    { id: 1, name: 'Sommerfestival', status: 'Aktiv', date: '15.08.2026', budget: '12.500,00 €' },
    { id: 2, name: 'Wintergala', status: 'Entwurf', date: '03.12.2026', budget: '8.200,00 €' },
    { id: 3, name: 'Tanzpremiere', status: 'In Prüfung', date: '21.09.2026', budget: '5.750,00 €' },
]

/* ---------- Tabs ---------- */
const demoTabs = ref([
    { name: 'Übersicht', current: true, permission: true, icon: 'IconLayoutDashboard' },
    { name: 'Termine', current: false, permission: true, icon: 'IconCalendar', count: 4 },
    { name: 'Dokumente', current: false, permission: true, icon: 'IconFile', count: 12 },
    { name: 'Einstellungen', current: false, permission: true, icon: 'IconSettings' },
])

function selectTab(tab) {
    demoTabs.value = demoTabs.value.map((t) => ({ ...t, current: t.name === tab.name }))
}

const activeTabName = computed(() => demoTabs.value.find((t) => t.current)?.name ?? '—')
</script>
