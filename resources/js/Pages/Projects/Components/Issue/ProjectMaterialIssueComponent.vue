<!-- components/ProjectMaterialIssuesSection.vue -->
<template>
    <section class="space-y-5">
        <!-- Top-Bar -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-zinc-900">{{ $t('Material Issue')}}</h2>
                <p class="text-sm text-zinc-500">{{ $t('Linked material issues for this project.')}}</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 active:translate-y-px"
                    @click="toggleAll(false)">
                    {{ $t('Close all')}}
                </button>
                <button
                    type="button"
                    class="rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 active:translate-y-px"
                    @click="toggleAll(true)">
                    {{ $t('Expand all')}}
                </button>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm font-semibold text-zinc-800 hover:bg-zinc-50 active:translate-y-px"
                    @click="openCreateMaterialIssue">
                    <IconPlus class="size-4" />
                    {{ $t('New')}}
                </button>
            </div>
        </div>

        <!-- Empty -->
        <div
            v-if="projectIssues.length === 0"
            class="rounded-2xl border border-zinc-200 bg-white/60 backdrop-blur p-12 text-center shadow-sm"
        >
            <IconPackage class="mx-auto mb-3 size-8 text-zinc-400" />
            <h3 class="text-base font-medium text-zinc-900">{{ $t('No material expenses')}}</h3>
            <p class="mt-1 text-sm text-zinc-500">{{ $t('Create a material issue via “New.”')}}</p>
        </div>

        <!-- List -->
        <article
            v-for="issue in projectIssues"
            :key="issue.id"
            class="group rounded-2xl border border-white/50 ring-1 ring-zinc-200/60 bg-white/60 backdrop-blur shadow-sm transition-all hover:shadow-md"
        >
            <!-- Header -->
            <header class="flex flex-col gap-3 p-5 md:flex-row md:items-start md:justify-between" :aria-expanded="isOpen(issue.id)">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-md border border-zinc-200 bg-white p-1.5 hover:bg-zinc-50"
                            :aria-label="isOpen(issue.id) ? 'Zuklappen' : 'Aufklappen'"
                            @click="toggle(issue.id)"
                        >
                            <component :is="isOpen(issue.id) ? IconChevronUp : IconChevronDown" class="size-4 text-zinc-600" />
                        </button>

                        <h3 class="truncate text-lg font-semibold text-zinc-900">
                            {{ issue.name || `Materialausgabe #${issue.id}` }}
                        </h3>

                        <!-- Gelbes Warn-Icon bei abweichendem Zeitraum -->
                        <ToolTipComponent
                            icon="IconAlertTriangle"
                            v-if="isPeriodDeviating(issue)"
                            class="size-4 text-amber-500"
                            :tooltip-text="$t('Material issue period differs from project period')"
                        />
                    </div>

                    <div class="mt-1 flex flex-wrap items-center gap-2 text-sm text-zinc-600">
                            <span class="inline-flex items-center gap-1.5 rounded-md bg-zinc-50 px-2.5 py-1">
                              <IconCalendar class="size-4" />
                                  {{ issue.start_date_time }}
                                </span>
                        <IconChevronRight class="hidden size-4 text-zinc-400 md:inline" />
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-zinc-50 px-2.5 py-1">
                              <IconCalendar class="size-4" />
                              {{ issue.end_date_time }}
                            </span>
                                        <span class="inline-flex items-center gap-1.5 rounded-md bg-zinc-50 px-2.5 py-1">
                              <IconClock class="size-4" />
                              ~ {{ diffDays(issue) }} {{ $t('Days')}}
                            </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1"
                            :class="issue.special_items_done ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                        >
                          <component :is="issue.special_items_done ? IconCircleCheck : IconAlertTriangle" class="size-4" />
                          {{ $t('Special items')}} {{ issue.special_items_done ? $t('Completed') : $t('Special items not completed') }}
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 active:translate-y-px"
                        @click="openEditIssue(issue)">
                        <IconEdit class="size-4" />
                        {{ $t('Edit')}}
                    </button>
                </div>
            </header>

            <!-- Body -->
            <transition
                enter-active-class="transition-opacity duration-150"
                enter-from-class="opacity-0"
                leave-active-class="transition-opacity duration-150"
                leave-to-class="opacity-0"
            >
                <div v-show="isOpen(issue.id)" class="divide-y divide-zinc-100">
                    <!-- === Artikel (Cards-Grid) === -->
                    <section class="p-5">
                        <!-- Kopfzeile mit Summen -->
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <IconPackage class="size-5 text-zinc-500" />
                                <h4 class="text-sm font-semibold text-zinc-900">{{ $t('Article')}}</h4>
                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-600">
                                    {{ issue.articles?.length || 0 }}
                                  </span>
                            </div>
                            <div class="hidden sm:flex items-center gap-2">
                              <span class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1 text-xs text-zinc-700">
                                {{ $t('Total demand')}}: <strong>{{ totalNeed(issue) }}</strong>
                              </span>
                                                        <span class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1 text-xs text-zinc-700">
                                {{ $t('Total inventory')}}: <strong>{{ totalStock(issue) }}</strong>
                              </span>
                            </div>
                        </div>

                        <!-- Empty -->
                        <div
                            v-if="(issue.articles?.length || 0) === 0"
                            class="rounded-xl border border-zinc-100 bg-zinc-50 p-6 text-center text-sm text-zinc-500"
                        >
                            {{ $t('No articles assigned.')}}
                        </div>

                        <!-- Cards Grid -->
                        <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                            <div
                                v-for="a in issue.articles"
                                :key="a.id"
                                class="rounded-xl border border-white/40 bg-white/60 backdrop-blur p-4 ring-1 transition hover:shadow-sm"
                                :class="cardClass(a)"
                            >
                                <!-- Header -->
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-zinc-900" :title="a.name">
                                            {{ a.name }}
                                        </div>
                                        <div
                                            v-if="a.description"
                                            class="mt-1 inline-flex items-center rounded-full bg-white px-2 py-0.5 text-[11px] text-zinc-600 ring-1 ring-zinc-200"
                                        >
                                            {{ a.description }}
                                        </div>
                                    </div>

                                    <!-- Status -->
                                    <span class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-medium ring-1" :class="statusChipClass(a)">
                                      {{ statusChipText(a) }}
                                    </span>
                                </div>

                                <!-- Meta -->
                                <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                    <span v-if="a.manufacturer?.name" class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-0.5 text-[11px] text-zinc-700 ring-1 ring-zinc-200">
                                      <IconBuildingFactory class="size-3.5" /> {{ a.manufacturer.name }}
                                    </span>
                                                                <span v-if="a.room?.name" class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-0.5 text-[11px] text-zinc-700 ring-1 ring-zinc-200">
                                      <IconHome class="size-3.5" /> {{ a.room.name }}
                                    </span>
                                </div>

                                <!-- KPIs -->
                                <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                                    <div class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1 text-zinc-600">
                                        {{ $t('Demand') }}
                                        <div class="text-sm font-semibold text-zinc-900">{{ a.pivot?.quantity || 0 }}</div>
                                    </div>
                                    <div class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1 text-zinc-600">
                                        {{ $t('In stock') }}
                                        <div class="text-sm font-semibold text-zinc-900">{{ a.quantity }}</div>
                                    </div>
                                    <div class="rounded-lg border border-zinc-200 bg-white px-2.5 py-1 text-zinc-600 text-right">
                                        {{ $t('Planned') }}
                                        <div class="text-sm font-semibold text-zinc-900">{{ needProgress(a) }}%</div>
                                    </div>
                                </div>

                                <!-- Progress -->
                                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-zinc-200">
                                    <div
                                        class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-emerald-500 transition-[width]"
                                        :style="{ width: (needProgress(a) || 0) + '%' }"
                                    />
                                </div>
                                <div class="mt-1 flex items-center justify-between text-[11px] text-zinc-500">
                                    <span>{{ $t('Planned')}}</span>
                                    <span>{{ a.pivot?.quantity || 0 }} / {{ a.quantity }}</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Sonderteile -->
                    <section class="p-5">
                        <div class="mb-3 flex items-center gap-2">
                            <IconSticker2 class="size-5 text-zinc-500" />
                            <h4 class="text-sm font-semibold text-zinc-900">{{ $t('Special items')}}</h4>
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-600">
                                {{ issue.special_items?.length || 0 }}
                              </span>
                        </div>

                        <div v-if="(issue.special_items?.length || 0) > 0" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div v-for="sp in issue.special_items" :key="sp.id" class="flex items-start gap-3 rounded-xl border border-zinc-100 bg-zinc-50/60 p-4">
                                <IconAlertTriangle class="mt-0.5 size-5 text-amber-500" />
                                <div>
                                    <div class="text-sm font-medium text-zinc-900">
                                        {{ sp.name }} <span class="ml-1 text-zinc-500">× {{ sp.quantity }}</span>
                                    </div>
                                    <div v-if="sp.description" class="text-sm text-zinc-600">{{ sp.description }}</div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="rounded-xl border border-zinc-100 bg-zinc-50 p-6 text-center text-sm text-zinc-500">
                            {{ $t('No special items assigned.')}}
                        </div>
                    </section>

                    <!-- Dateien -->
                    <section class="p-5">
                        <div class="mb-3 flex items-center gap-2">
                            <IconFileText class="size-5 text-zinc-500" />
                            <h4 class="text-sm font-semibold text-zinc-900">{{ $t('Files')}}</h4>
                            <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs text-zinc-600">
                              {{ issue.files?.length || 0 }}
                            </span>
                        </div>

                        <div v-if="(issue.files?.length || 0) > 0" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <div v-for="f in issue.files" :key="f.id" class="flex items-center justify-between gap-3 rounded-xl border border-zinc-100 bg-zinc-50/60 p-4">
                                <div class="flex min-w-0 flex-1 items-center gap-3">
                                    <!-- Thumbnail -->
                                    <FilePreview
                                        v-if="isPreviewable(f)"
                                        :src="fileUrl(f)"
                                        :name="displayName(f)"
                                        :type="isPdf(f) ? 'pdf' : 'image'"
                                        size="sm"
                                        @open="openPreview(f)"
                                    />
                                    <component v-else is="IconFileText" class="size-5 shrink-0 text-zinc-400" aria-hidden="true" />

                                    <!-- Name + Meta -->
                                    <div class="min-w-0 flex-1">
                                        <div class="truncate text-sm font-medium text-zinc-900">{{ displayName(f) }}</div>
                                        <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-zinc-500">
                                            <span v-if="f.created_at" class="inline-flex items-center gap-1">
                                              • <span>{{ $t('Uploaded') }}:</span>
                                              <time :datetime="f.created_at">{{ f.created_at }}</time>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 hover:bg-zinc-50 active:translate-y-px"
                                    @click="$emit('download-file', f)"
                                >
                                    <IconDownload class="size-4" />
                                    {{ $t('Download') }}
                                </button>
                            </div>
                        </div>

                        <div v-else class="rounded-xl border border-zinc-100 bg-zinc-50 p-6 text-center text-sm text-zinc-500">
                            {{ $t('No files assigned.') }}
                        </div>
                    </section>
                </div>
            </transition>
        </article>
    </section>
    <!-- Lightbox Preview -->
    <teleport to="body">
        <div v-if="lightboxOpen"
             class="fixed inset-0 z-[1000] flex items-center justify-center bg-black/60 p-4"
             @click.self="closePreview">
            <div class="relative w-[92vw] max-w-5xl rounded-2xl bg-white p-3 shadow-xl">
                <button type="button"
                        class="absolute right-3 top-3 rounded-full p-1.5 text-zinc-600 hover:text-zinc-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                        @click="closePreview"
                        aria-label="Close preview">
                    ✕
                </button>

                <!-- Bild groß -->
                <img v-if="lightboxType === 'image'"
                     :src="lightboxSrc"
                     :alt="lightboxName"
                     class="mx-auto max-h-[80vh] w-auto object-contain"
                     loading="eager" />

                <!-- PDF groß -->
                <div v-else-if="lightboxType === 'pdf'" class="max-h-[80vh] overflow-auto">
                    <div class="mb-3 flex items-center justify-center gap-2">
                        <button type="button"
                                class="rounded-lg px-2 py-1 text-sm font-medium ring-1 ring-inset ring-zinc-300 disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                :disabled="!canPrev"
                                @click="prevPage">
                            ‹ {{ $t('Prev') }}
                        </button>

                        <span class="text-sm text-zinc-600">
                              {{ currentPage }} / {{ totalPages }}
                            </span>

                        <button type="button"
                                class="rounded-lg px-2 py-1 text-sm font-medium ring-1 ring-inset ring-zinc-300 disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                :disabled="!canNext"
                                @click="nextPage">
                            {{ $t('Next') }} ›
                        </button>
                    </div>

                    <VuePDF :pdf="lightboxPdf" :page="currentPage" fit-parent class="mx-auto" />
                </div>
            </div>
        </div>
    </teleport>

    <IssueOfMaterialModal
        v-if="showIssueOfMaterialModal"
        @close="showIssueOfMaterialModal = false"
        :project="project"
        :issue-of-material="materialIssueToEdit"
        :is-in-project-component="true"
    />
</template>

<script setup lang="ts">
import { ref, computed, onBeforeUnmount, watch } from 'vue'
import {
    IconPlus, IconEdit, IconPackage, IconCalendar, IconClock, IconChevronDown, IconChevronUp,
    IconChevronRight, IconAlertTriangle, IconFileText, IconDownload, IconHome, IconBuildingFactory,
    IconSticker2, IconCircleCheck,
} from '@tabler/icons-vue'
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import FilePreview from "@/Artwork/Files/FilePreview.vue";
import { VuePDF, usePDF } from '@tato30/vue-pdf'

type Manufacturer = { id:number; name:string }
type Room = { id:number; name:string }
type ArticlePivot = { quantity:number } & Record<string, unknown>
type Article = {
    id:number; name:string; description?:string|null; quantity:number;
    room?:Room|null; manufacturer?:Manufacturer|null; pivot?:ArticlePivot|null
}
type SpecialItem = { id:number; name:string; quantity:number; description?:string|null }
type InternalIssue = {
    id:number; name:string|null; project_id:number|string|null;
    start_date:string; start_time:string; end_date:string; end_time:string;
    start_date_time:string; end_date_time:string;
    special_items_done:boolean; articles:Article[]; special_items:SpecialItem[]; files:FileItem[];
}
type Project = {
    id:number|string; name?:string;
    start_date?:string; end_date?:string; start_time?:string; end_time?:string;
}

const props = defineProps<{
    project: Project
    materials: InternalIssue[]
    defaultOpen?: boolean
}>()

const emit = defineEmits<{
    (e:'create-issue', payload:{ project_id:number|string }):void
    (e:'edit-issue', issue:InternalIssue):void
    (e:'download-file', file:FileItem):void
}>()

const showIssueOfMaterialModal = ref(false)
const materialIssueToEdit = ref(null)

/** Nur Issues, die zu diesem Projekt gehören */
const projectIssues = computed(() => props.materials.filter(m => String(m.project_id ?? '') === String(props.project.id)))

/** Expand/Collapse State */
const openSet = ref<Set<number>>(new Set())
const initiallyOpen = props.defaultOpen ?? true
projectIssues.value.forEach(i => { if (initiallyOpen) openSet.value.add(i.id) })
function isOpen(id:number){ return openSet.value.has(id) }
function toggle(id:number){ openSet.value.has(id) ? openSet.value.delete(id) : openSet.value.add(id) }
function toggleAll(open:boolean){
    openSet.value = new Set()
    if(open) projectIssues.value.forEach(i => openSet.value.add(i.id))
}

function padTime(t?: string | null, fallback: string = '00:00:00'): string {
    if (!t) return fallback;
    // HH:mm:ss
    if (/^\d{2}:\d{2}:\d{2}$/.test(t)) return t;
    // HH:mm -> HH:mm:00
    if (/^\d{2}:\d{2}$/.test(t)) return `${t}:00`;
    return fallback;
}

function iso(date?: string | null, time?: string | null): string {
    if (!date) return '';
    return `${date}T${padTime(time)}`;
}

function getProjectBounds(project: any) {
    const fe = project?.first_event;
    const le = project?.last_event;

    const pStartDate =
        fe?.formatted_dates?.start_without_time ??
        fe?.event_date_without_time?.start_clear ??
        null;

    const pStartTime =
        fe?.formatted_dates?.startTime ??
        fe?.start_time_without_day ??
        '00:00';

    const pEndDate =
        le?.formatted_dates?.end_without_time ??
        // fallback: wenn end_without_time fehlen sollte, nimm start_without_time des letzten Events
        le?.formatted_dates?.start_without_time ??
        null;

    const pEndTime =
        le?.formatted_dates?.endTime ??
        le?.end_time_without_day ??
        '23:59';

    const pStartISO = iso(pStartDate, pStartTime);
    const pEndISO   = iso(pEndDate,   pEndTime);

    return { pStartISO, pEndISO };
}

function isPeriodDeviating(issue: InternalIssue): boolean {
    const { pStartISO, pEndISO } = getProjectBounds(props.project);

    // ohne vollständigen Projektzeitraum keine Warnung
    if (!pStartISO || !pEndISO) return false;

    const iStartISO = iso(issue.start_date, issue.start_time || '00:00:00');
    const iEndISO   = iso(issue.end_date,   issue.end_time   || '23:59:59');

    // exakter Vergleich (inkl. Uhrzeit, normalisiert auf HH:mm:ss)
    return (iStartISO !== pStartISO) || (iEndISO !== pEndISO);
}

/** Hilfen */
function diffDays(issue:InternalIssue):number{
    const start = new Date(iso(issue.start_date, issue.start_time))
    const end   = new Date(iso(issue.end_date, issue.end_time))
    const ms = Math.max(0, end.getTime() - start.getTime())
    return Math.max(1, Math.round(ms / (1000*60*60*24)))
}
function needProgress(a:Article):number{
    const need = Math.max(0, a.pivot?.quantity ?? 0)
    const stock = Math.max(1, a.quantity || 1)
    return Math.min(100, Math.round((need/stock)*100))
}
function toLocal(dt:string){ return dt }

const openEditIssue = (issue:InternalIssue) => {
    materialIssueToEdit.value = issue
    showIssueOfMaterialModal.value = true
}

const openCreateMaterialIssue = () => {
    materialIssueToEdit.value = null
    showIssueOfMaterialModal.value = true
}

function totalNeed(issue: { articles?: { pivot?: { quantity?: number|null } | null }[] | null }) {
    return (issue.articles ?? []).reduce((sum, a) => sum + (a?.pivot?.quantity ?? 0), 0)
}
function totalStock(issue: { articles?: { quantity?: number|null }[] | null }) {
    return (issue.articles ?? []).reduce((sum, a) => sum + (a?.quantity ?? 0), 0)
}
function getNeed(a: { pivot?: { quantity?: number|null } | null }) {
    return Math.max(0, a?.pivot?.quantity ?? 0)
}
function getStock(a: { quantity?: number|null }) {
    return Math.max(0, a?.quantity ?? 0)
}
function cardTone(a: any): 'ok' | 'warn' | 'over' {
    const need = getNeed(a), stock = getStock(a)
    if (need > stock) return 'over'
    if ((needProgress(a) || 0) >= 80) return 'warn'
    return 'ok'
}
function cardClass(a: any) {
    const t = cardTone(a)
    if (t === 'over') return 'ring-rose-300 bg-rose-50/40'
    if (t === 'warn') return 'ring-amber-300 bg-amber-50/40'
    return 'ring-zinc-200'
}
function statusChipText(a: any) {
    const t = cardTone(a)
    if (t === 'over') return 'Überbedarf'
    if (t === 'warn') return 'hoch ausgelastet'
    return 'OK'
}
function statusChipClass(a: any) {
    const t = cardTone(a)
    if (t === 'over') return 'bg-rose-50 text-rose-700 ring-rose-200'
    if (t === 'warn') return 'bg-amber-50 text-amber-700 ring-amber-200'
    return 'bg-emerald-50 text-emerald-700 ring-emerald-200'
}
type FileItem = {
    id: number
    name?: string | null
    original_name?: string | null
    file_path?: string | null
    created_at?: string | null
    mime_type?: string | null
} & Record<string, unknown>

/** ------- Preview-Logik ------- */
function displayName(file?: FileItem) {
    if (!file) return 'Datei'
    return (file.name ?? file.original_name ?? 'Datei')
}
function fileExtFromString(name?: string | null) {
    const n = (name || '').toLowerCase()
    return n.includes('.') ? n.split('.').pop()! : ''
}
function fileExtFromAny(file?: FileItem) {
    if (!file) return ''
    return fileExtFromString(file.name ?? file.original_name)
}
function isImage(file?: FileItem) {
    if (!file) return false
    const m = (file.mime_type || '').toLowerCase()
    const ext = fileExtFromAny(file)
    return m.startsWith('image/') || ['png','jpg','jpeg','gif','webp','avif','bmp','svg'].includes(ext)
}
function isPdf(file?: FileItem) {
    if (!file) return false
    const m = (file.mime_type || '').toLowerCase()
    const ext = fileExtFromAny(file)
    return m === 'application/pdf' || ext === 'pdf'
}
function isPreviewable(file?: FileItem) { return !!file && (isImage(file) || isPdf(file)) }

function fileUrl(file: FileItem) {
    // Server sollte optional ?inline=1 o.ä. erlauben, falls du direkt einbetten willst.
    return '/storage/' + (file.file_path || '')
}

function openPreview(file: FileItem) {
    if (!isPreviewable(file)) return
    lightboxType.value = isPdf(file) ? 'pdf' : 'image'
    lightboxName.value = displayName(file)
    lightboxSrc.value = fileUrl(file)
    lightboxOpen.value = true
}

const closePreview = () => {
    lightboxOpen.value = false
    lightboxType.value = null
    lightboxName.value = ''
    lightboxSrc.value = ''
    currentPage.value = 1
}

/* Lightbox State */
const lightboxOpen = ref(false)
const lightboxType = ref<'pdf'|'image'|null>(null)
const lightboxName = ref<string>('')
const lightboxSrc = ref<string>('')

const { pdf: lightboxPdf, pages } = usePDF(lightboxSrc) // pages = Ref<number | undefined>
const currentPage = ref(1)

const totalPages = computed(() => pages?.value ?? 1)
const canPrev = computed(() => currentPage.value > 1)
const canNext = computed(() => currentPage.value < totalPages.value)

// Bei Öffnen oder Source-Wechsel immer auf Seite 1
watch([lightboxOpen, lightboxSrc], () => { currentPage.value = 1 })

// Clamp, falls PDF neu lädt oder weniger Seiten hat
watch(pages, (n) => {
    if (!n) return
    if (currentPage.value > n) currentPage.value = n
})

// Navigation
function nextPage() { if (canNext.value) currentPage.value += 1 }
function prevPage() { if (canPrev.value) currentPage.value -= 1 }

// Keyboard (→ / ← / Esc)
function onKey(e: KeyboardEvent) {
    if (!lightboxOpen.value || lightboxType.value !== 'pdf') return
    if (e.key === 'ArrowRight') nextPage()
    if (e.key === 'ArrowLeft')  prevPage()
    if (e.key === 'Escape')     closePreview()
}

onBeforeUnmount(() => window.removeEventListener('keydown', onKey))


</script>

<style scoped>

</style>
