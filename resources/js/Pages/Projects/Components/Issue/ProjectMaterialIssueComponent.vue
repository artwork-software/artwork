<!-- components/ProjectMaterialIssuesSection.vue -->
<template>
    <section class="space-y-5">
        <div v-if="loadMaterialsError" class="mb-2 text-xs text-rose-600">
            {{ loadMaterialsError }}
        </div>
        <div v-else-if="isLoadingMaterials" class="mb-2 text-xs text-secondary">
            {{ $t('Loading data...') }}
        </div>
        <!-- Top-Bar -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-zinc-900">{{ $t('Material Issue')}}</h2>
                <p class="text-sm text-zinc-500">{{ $t('Linked material issues for this project.')}}</p>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="new-button"
                    @click="toggleAll(false)">
                    {{ $t('Close all')}}
                </button>
                <button
                    type="button"
                    class="new-button"
                    @click="toggleAll(true)">
                    {{ $t('Expand all')}}
                </button>
                <button
                    type="button"
                    class="new-button"
                    @click="openCreateMaterialIssue">
                    <IconPlus class="size-4" />
                    {{ $t('New')}}
                </button>
            </div>
        </div>

        <!-- Tag-Filter -->
        <div v-if="availableTags.length" class="flex flex-col gap-1 mt-2">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-zinc-500">
                    {{ $t('Filter by tags') }}:
                </span>

                <button
                    v-for="tag in availableTags"
                    :key="tag.id"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium transition-colors"
                    :style="tagChipStyle(tag)"
                    :class="selectedTagIds.includes(tag.id)
                        ? 'bg-opacity-20'
                        : 'bg-white/80 hover:bg-white'"
                    @click="toggleTag(tag.id)"
                >
                    <span
                        class="inline-block h-2 w-2 rounded-full border border-white/60"
                        :style="{ backgroundColor: tag.color || '#4f46e5' }"
                    />
                    <span class="truncate max-w-[120px]">
                        {{ tag.name }}
                    </span>
                </button>

                <button
                    v-if="selectedTagIds.length"
                    type="button"
                    class="ml-1 text-[11px] text-zinc-500 hover:text-zinc-800 underline underline-offset-2"
                    @click="clearTags"
                >
                    {{ $t('Reset tag filter') }}
                </button>
            </div>

            <!-- 👇 Aktive Tag-Filter anzeigen -->
            <div v-if="selectedTagIds.length" class="flex flex-wrap items-center gap-2 pl-1">
                <span class="text-[11px] text-zinc-500">
                    {{ $t('Active tag filters') }}:
                </span>

                <button
                    v-for="tag in activeTags"
                    :key="tag.id"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[11px] font-medium transition-colors"
                    :style="tagChipStyle(tag)"
                    @click="toggleTag(tag.id)"
                >
                    <span
                        class="inline-block h-1.5 w-1.5 rounded-full border border-white/60"
                        :style="{ backgroundColor: tag.color || '#4f46e5' }"
                    />
                    <span class="truncate max-w-[100px]">
                        {{ tag.name }}
                    </span>
                    <span class="text-[10px] opacity-70">×</span>
                </button>
            </div>
        </div>

        <!-- Empty -->
        <div
            v-if="filteredIssues.length === 0"
            class="rounded-2xl border border-zinc-200 bg-white/60 backdrop-blur p-12 text-center shadow-sm"
        >
            <IconPackage class="mx-auto mb-3 size-8 text-zinc-400" />
            <h3 class="text-base font-medium text-zinc-900">{{ $t('No material expenses')}}</h3>
            <p class="mt-1 text-sm text-zinc-500">{{ $t('Create a material issue via “New.”')}}</p>
        </div>

        <!-- List -->
        <article
            v-for="issue in filteredIssues"
            :key="issue.id"
            class="group rounded-2xl border border-white/50 ring-1 ring-zinc-200/60 bg-white/60 backdrop-blur shadow-sm transition-all hover:shadow-md"
        >
            <!-- Header -->
            <header class="flex flex-col gap-3 p-5 md:flex-row md:items-start md:justify-between" :aria-expanded="isOpen(issue.id)">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="new-button"
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
                            :icon="IconAlertTriangle"
                            v-if="isPeriodDeviating(issue)"
                            classes="!text-red-500"
                            icon-size="!size-4"
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
                        <span v-if="issue.special_items?.length > 0"
                            class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1"
                            :class="issue.special_items_done ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                        >
                          <component :is="issue.special_items_done ? IconCircleCheck : IconAlertTriangle" class="size-4"  />
                          {{ issue.special_items_done ? $t('Special items Completed') : $t('Special items not completed') }}
                        </span>
                    </div>
                    <div v-if="issue.notes" class="mt-1 text-sm text-zinc-600 whitespace-pre-line">
                        {{ issue.notes }}
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        class="new-button"
                        @click="openEditIssue(issue)">
                        <IconEdit class="size-4" />
                        {{ $t('Edit')}}
                    </button>
                    <button
                        type="button"
                        class="new-button text-red-500 hover:text-red-700"
                        @click="openDeleteModal(issue)">
                        <IconTrash class="size-4" />
                        {{ $t('Delete')}}
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
                                <!-- Article Layout with Image -->
                                <div class="flex items-start gap-4">
                                    <!-- Article Image -->
                                    <div v-if="a?.images?.length" class="shrink-0" style="max-width: 80px">
                                        <div class="group relative cursor-zoom-in overflow-hidden rounded-lg border border-zinc-200 shadow-sm" @click="openArticleLightbox(0, a.images)">
                                            <img :src="'/storage/' + a.images[0].image" :alt="a.images[0].alt || ''" class="block h-auto w-full object-cover" @error="(e) => e.target.src = usePage().props.big_logo" />
                                            <div class="pointer-events-none absolute inset-0 grid place-items-center bg-black/0 transition group-hover:bg-black/30">
                                                <component :is="IconWindowMaximize" class="h-3 w-3 text-white opacity-0 transition group-hover:opacity-100" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Article Content -->
                                    <div class="min-w-0 flex-1">
                                        <!-- Header -->
                                        <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-zinc-900" :title="a.name">
                                            {{ a.name }}
                                        </div>
                                        <div
                                            v-if="a.description"
                                            class="mt-1 text-[11px] text-zinc-500"
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

                                        <!-- Tags des Artikels -->
                                        <div
                                            v-if="a.tags && a.tags.length"
                                            class="mt-1 flex flex-wrap items-center gap-1"
                                        >
                                        <span
                                            v-for="tag in a.tags"
                                            :key="tag.id"
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium border"
                                            :style="tagChipStyle(tag)"
                                        >
                                            <span
                                                class="inline-block h-1.5 w-1.5 rounded-full border border-white/60"
                                                :style="{ backgroundColor: tag.color || '#4f46e5' }"
                                            />
                                            <span class="truncate max-w-[100px]">
                                                {{ tag.name }}
                                            </span>
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
                                    <component v-else :is="IconFileText" class="size-5 shrink-0 text-zinc-400" aria-hidden="true" />

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
                                    class="new-button"
                                    @click="downloadFile(f)"
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


    <!-- Article Images Galleria -->
    <Galleria
        v-model:activeIndex="articleActiveIndex"
        v-model:visible="articleDisplayCustom"
        :value="articleLightboxImages"
        :responsiveOptions="responsiveOptions"
        :numVisible="7"
        :pt="{ mask: { onClick: onArticleMaskClick } }"
        :circular="true"
        :fullScreen="true"
        :showItemNavigators="true"
        :showThumbnails="true"
    >
        <template #item="slotProps">
            <img :src="'/storage/' + slotProps.item.image" :alt="slotProps.item.alt || ''" style="width: 60%; display: block" @error="(e) => e.target.src = usePage().props.big_logo" />
        </template>
        <template #thumbnail="slotProps">
            <img :src="'/storage/' + slotProps.item.image" :alt="slotProps.item.alt || ''" class="w-20 max-w-20" style="display: block" @error="(e) => e.target.src = usePage().props.big_logo" />
        </template>
    </Galleria>

    <IssueOfMaterialModal
        v-if="showIssueOfMaterialModal"
        @close="showIssueOfMaterialModal = false"
        @saved="handleMaterialIssueSaved"
        :project="project"
        :issue-of-material="materialIssueToEdit"
        :is-in-project-component="true"
        :first-event="headerObject?.firstEventInProject"
        :last-event="headerObject?.lastEventInProject"
    />

    <ConfirmDeleteModal
        v-if="showDeleteModal"
        :title="$t('Delete material issue')"
        :description="$t('Are you sure you want to delete this material issue?')"
        @closed="showDeleteModal = false"
        @delete="confirmDelete"
    />
</template>

<script setup lang="ts">
import { ref, computed, onBeforeUnmount, watch, onMounted, provide } from 'vue'
import axios from 'axios'
import {
    IconPlus, IconEdit, IconPackage, IconCalendar, IconClock, IconChevronDown, IconChevronUp,
    IconChevronRight, IconAlertTriangle, IconFileText, IconDownload, IconHome, IconBuildingFactory,
    IconSticker2, IconCircleCheck, IconWindowMaximize, IconTrash,
} from '@tabler/icons-vue'
import IssueOfMaterialModal from "@/Pages/IssueOfMaterial/IssueOfMaterialModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import { router } from '@inertiajs/vue3';
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import FilePreview from "@/Artwork/Files/FilePreview.vue";
import { VuePDF, usePDF } from '@tato30/vue-pdf'
import Galleria from "primevue/galleria";
import {usePage} from "@inertiajs/vue3";

type Manufacturer = { id:number; name:string }
type Room = { id:number; name:string }

// 🔹 Tag-Typ
type Tag = { id:number; name:string; color?:string | null }

type ArticlePivot = { quantity:number } & Record<string, unknown>
type Article = {
    id:number; name:string; description?:string|null; quantity:number;
    room?:Room|null; manufacturer?:Manufacturer|null; pivot?:ArticlePivot|null;
    images?: { image: string; alt?: string }[] | null;
    // 🔹 Tags an Artikeln
    tags?: Tag[] | null;
}
type SpecialItem = { id:number; name:string; quantity:number; description?:string|null }
type InternalIssue = {
    id:number; name:string|null; project_id:number|string|null;
    project?: Project | null;
    start_date:string; start_time:string; end_date:string; end_time:string;
    start_date_time:string; end_date_time:string;
    notes?:string|null;
    special_items_done:boolean; articles:Article[]; special_items:SpecialItem[]; files:FileItem[];
}
type Project = {
    id:number|string; name?:string;
    start_date?:string; end_date?:string; start_time?:string; end_time?:string;
}

const props = defineProps<{
    project: Project
    materials?: InternalIssue[]
    defaultOpen?: boolean
    headerObject?: any
}>()

const tags = ref(usePage().props.tags ?? [])
const tagGroups = ref(usePage().props.tagGroups ?? [])

const emit = defineEmits<{
    (e:'create-issue', payload:{ project_id:number|string }):void
    (e:'edit-issue', issue:InternalIssue):void
    (e:'download-file', file:FileItem):void
}>()

const showIssueOfMaterialModal = ref(false)
const materialIssueToEdit = ref(null)
const showDeleteModal = ref(false)
const issueToDelete = ref<InternalIssue | null>(null)
const isLoadingMaterials = ref(false)
const loadMaterialsError = ref('')
const localMaterials = ref<InternalIssue[]>(props.materials ?? [])
const materialSets = ref([])

// Provide materialSets to child components
provide('materialSets', materialSets)

/** Article Lightbox State */
const articleLightboxImages = ref([])
const articleActiveIndex = ref(0)
const articleDisplayCustom = ref(false)
const responsiveOptions = ref([
    {
        breakpoint: '1024px',
        numVisible: 5
    },
    {
        breakpoint: '768px',
        numVisible: 3
    },
    {
        breakpoint: '560px',
        numVisible: 1
    }
])

watch(
    () => props.project?.id,
    () => {
        fetchMaterials()
    },
    { immediate: true }
)

async function fetchMaterials() {
    const projectId = props.project?.id

    if (!projectId) {
        return
    }

    isLoadingMaterials.value = true
    loadMaterialsError.value = ''

    try {
        const { data } = await axios.get(
            route('projects.tabs.material-issues', { project: projectId })
        )
        localMaterials.value = data?.materials ?? []
        materialSets.value = data?.materialSets ?? []
    } catch (error) {
        console.error(error)
        loadMaterialsError.value = 'Unable to load material issues.'
    } finally {
        isLoadingMaterials.value = false
    }
}

/** Nur Issues, die zu diesem Projekt gehören */
// 🔹 Issues dieses Projekts (wie gehabt)
const projectIssues = computed(() =>
    localMaterials.value.filter(m => String(m.project_id ?? '') === String(props.project.id))
)
/** Expand/Collapse State */
const openSet = ref<Set<number>>(new Set())
const initiallyOpen = props.defaultOpen ?? true

function isOpen(id:number){ return openSet.value.has(id) }
function toggle(id:number){ openSet.value.has(id) ? openSet.value.delete(id) : openSet.value.add(id) }
function toggleAll(open:boolean){
    openSet.value = new Set()
    if (open) filteredIssues.value.forEach(i => openSet.value.add(i.id))
}

// 🔹 wenn Issues geladen / geändert werden, initial öffnen
watch(projectIssues, (issues) => {
    if (!initiallyOpen) return
    if (!issues?.length) return
    if (openSet.value.size === 0) {
        openSet.value = new Set(issues.map(i => i.id))
    }
})

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
    // Extract dates from first and last event formatted_dates (already in YYYY-MM-DD format)
    const firstEvent = props.headerObject?.firstEventInProject
    const lastEvent = props.headerObject?.lastEventInProject

    const startDate = firstEvent?.formatted_dates?.start_without_time || ''
    const startTime = firstEvent?.formatted_dates?.startTime || '00:00'
    const endDate = lastEvent?.formatted_dates?.end_without_time || ''
    const endTime = lastEvent?.formatted_dates?.endTime || '23:59'

    materialIssueToEdit.value = {
        id: null,
        name: '',
        project_id: props.project?.id || null,
        start_date: startDate,
        start_time: startTime,
        end_date: endDate,
        end_time: endTime,
        room_id: null,
        notes: '',
        responsible_user_ids: [],
        special_items_done: false,
        files: [],
        articles: [],
        special_items: []
    }
    showIssueOfMaterialModal.value = true
}

const handleMaterialIssueSaved = () => {
    fetchMaterials()
}

const openDeleteModal = (issue: InternalIssue) => {
    issueToDelete.value = issue
    showDeleteModal.value = true
}

const confirmDelete = () => {
    if (!issueToDelete.value) return
    router.delete(route('issue-of-material.destroy', issueToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false
            issueToDelete.value = null
            fetchMaterials()
        },
        onError: () => {
            showDeleteModal.value = false
            issueToDelete.value = null
        }
    })
}

/** Article Lightbox Functions */
function openArticleLightbox(startIndex, images) {
    articleLightboxImages.value = images || []
    articleActiveIndex.value = startIndex || 0
    articleDisplayCustom.value = true
}

const onArticleMaskClick = (e) => {
    if (e.target === e.currentTarget) {
        articleDisplayCustom.value = false
    }
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

const downloadFile = (file: FileItem) => {
    window.open(fileUrl(file), '_blank')
}

// 🔹 ausgewählte Tag-IDs
const selectedTagIds = ref<number[]>([])


const availableTags = computed<Tag[]>(() => {
    return (tags.value as any[])
        .filter((t) => !!t) // safety
        .map((t) => ({
            id: t.id,
            name: t.name,
            color: t.color ?? null,
        }))
})

// 🔹 nach Tags gefilterte Issues
const filteredIssues = computed(() => {
    if (!selectedTagIds.value.length) {
        return projectIssues.value
    }

    return projectIssues.value.filter(issue =>
        // Issue muss *alle* ausgewählten Tags irgendwo in seinen Artikeln haben
        selectedTagIds.value.every(tagId => issueHasTag(issue, tagId))
    )
})

// 🔹 Tag-Helpers
const toggleTag = (tagId: number) => {
    const idx = selectedTagIds.value.indexOf(tagId)
    if (idx === -1) {
        selectedTagIds.value.push(tagId)
    } else {
        selectedTagIds.value.splice(idx, 1)
    }
}

const clearTags = () => {
    selectedTagIds.value = []
}

// 🔹 Tag-Chip-Style
const tagChipStyle = (tag: Tag) => {
    const base = tag.color || '#2563eb'
    return {
        backgroundColor: base + '10',
        borderColor: base + '40',
        color: base,
    }
}

// 🔹 aktuell aktive Tags (ausgewählt)
const activeTags = computed<Tag[]>(() => {
    const map = new Map<number, Tag>()
    for (const t of availableTags.value) {
        if (selectedTagIds.value.includes(t.id)) {
            map.set(t.id, t)
        }
    }
    return Array.from(map.values())
})

// 🔹 Hilfsfunktion: hat Issue diesen Tag in irgendeinem Artikel?
function issueHasTag(issue: InternalIssue, tagId: number): boolean {
    return (issue.articles || []).some(a =>
        (a.tags || []).some(t => t.id === tagId)
    )
}

</script>

<style scoped>

</style>
