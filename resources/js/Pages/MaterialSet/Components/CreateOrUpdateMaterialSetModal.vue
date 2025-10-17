<template>
    <ArtworkBaseModal
        :title="materialSet?.id ? $t('Edit Material Set') : $t('Create Material Set')"
        :description="materialSet?.id ? $t('Edit the material set details') : $t('Create a new material set')"
        modal-size="max-w-7xl"
        @close="$emit('close')"
    >
        <form @submit.prevent="submit" class="space-y-6">
            <!-- Stammdaten -->
            <section class="rounded-2xl bg-white/70 p-6 shadow-sm ring-1 ring-zinc-200 backdrop-blur">
                <h3 class="text-base font-semibold text-zinc-900">{{ $t('Basic information') }}</h3>
                <p class="mt-1 text-sm text-zinc-500">
                    {{ $t('Give your material set a clear name and optional description.') }}
                </p>

                <div class="mt-6 grid grid-cols-1 gap-4">
                    <BaseInput
                        v-model="materialSetForm.name"
                        id="material-set-name"
                        :label="$t('Name')"
                        required
                    />
                    <BaseTextarea
                        v-model="materialSetForm.description"
                        id="material-set-description"
                        :label="$t('Description')"
                        :rows="4"
                    />
                </div>
            </section>

            <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Linke Spalte: Suche / Filter / Liste -->
                <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm lg:col-span-1">
                    <!-- Sticky Tools -->
                    <div class="sticky top-0 z-10 rounded-t-2xl border-b border-zinc-100 bg-white/90 px-5 py-3 backdrop-blur">
                        <div class="flex w-full items-center gap-3">
                            <ArticleSearch
                                id="articleSearchInModal"
                                class="w-full"
                                :label="$t('Search Articles')"
                                @article-selected="addItemToSet"
                            />
                            <InventoryFunctionBarFilter @close="reloadArticlesWithNewFilter" />
                        </div>
                    </div>

                    <!-- Header -->
                    <div class="flex items-center justify-between px-5 py-3">
                        <h3 class="flex items-center gap-2 font-semibold">
                            <span class="inline-block size-2 rounded-full bg-indigo-500"></span>
                            {{ $t('Found Articles') }}
                        </h3>
                        <div v-if="articles?.length" class="text-sm text-zinc-500">
                            {{ articles.length }} {{ articles.length === 1 ? $t('article found') : $t('articles found') }}
                        </div>
                    </div>

                    <!-- Liste -->
                    <div ref="scrollContainer" class="max-h-[28rem] overflow-y-auto px-5 pb-5">
                        <!-- Skeletons -->
                        <div v-if="initialLoading" class="space-y-2">
                            <div v-for="i in 6" :key="i" class="animate-pulse rounded-xl border border-zinc-200 bg-zinc-50/60 p-3">
                                <div class="flex items-start gap-3">
                                    <div class="h-12 w-12 rounded-lg bg-zinc-200" />
                                    <div class="min-w-0 flex-1 space-y-2">
                                        <div class="h-4 w-2/3 rounded bg-zinc-200" />
                                        <div class="h-3 w-full rounded bg-zinc-100" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div v-else>
                            <div
                                v-for="article in articles"
                                :key="article.id"
                                class="mb-2 rounded-xl border border-zinc-200 bg-zinc-50/60 p-3 shadow-sm"
                            >
                                <button
                                    type="button"
                                    class="w-full text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded-xl"
                                    @click="onArticleClick(article)"
                                    :aria-label="$t('Add to set') + ': ' + (article.name || '')"
                                >
                                    <div class="flex items-start gap-3">
                                        <img
                                            v-if="article?.images?.[0]?.image"
                                            :src="imageSrc(article.images[0].image)"
                                            :alt="article.images[0].alt || ''"
                                            class="h-12 w-12 rounded-lg border border-zinc-200 object-cover"
                                            @error="onImgError"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start gap-2">
                                                <div class="truncate font-medium text-zinc-900">
                                                    {{ article.name }}
                                                </div>
                                                <span
                                                    v-if="isSelected(article.id)"
                                                    class="inline-flex shrink-0 items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200"
                                                >
                                                  {{ $t('in set') }} (×{{ qtyOf(article.id) }})
                                                </span>
                                            </div>

                                            <div v-if="article.description" class="text-xs text-zinc-500 line-clamp-2">
                                                {{ article.description }}
                                            </div>

                                            <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px] text-zinc-500">
                                            <span>
                                              {{ $t('Category') }}: {{ article?.category?.name || '—' }}
                                              <span v-if="article?.sub_category"> • {{ $t('Subcategory') }}: {{ article.sub_category.name }}</span>
                                            </span>
                                            </div>
                                        </div>

                                        <!-- Plus-Button, falls schon vorhanden -->
                                        <div v-if="isSelected(article.id)" class="self-start">
                                            <button
                                                type="button"
                                                class="rounded-lg px-2 py-1 text-sm font-medium text-indigo-700 ring-1 ring-inset ring-indigo-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
                                                @click.stop="incrementFromList(article.id)"
                                                :aria-label="$t('Increase quantity')"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </button>
                            </div>

                            <!-- Infinite Scroll Sentinel -->
                            <div ref="sentinel" class="flex items-center justify-center py-3">
                                <span v-if="loadingMore" class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent text-zinc-500" />
                                <span v-else-if="!hasMoreArticles" class="text-xs text-zinc-400">{{ $t('No more items') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rechte Spalte: Auswahl -->
                <div class="space-y-6 lg:col-span-2">
                    <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm">
                        <div class="rounded-t-2xl border-b border-zinc-100 px-5 py-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between md:gap-6">
                                <!-- Titel + Beschreibung -->
                                <div class="space-y-1">
                                    <h3 class="flex items-center gap-2 text-base font-semibold text-zinc-900">
                                        <span class="inline-block size-2 rounded-full bg-indigo-500 ring-2 ring-indigo-200/60"></span>
                                        {{ $t('Selected Articles') }}
                                    </h3>
                                    <p class="text-xs text-zinc-500">
                                        {{ $t('Here you can see the items you have selected for the material issue. Adjust the quantity or remove items.') }}
                                    </p>
                                </div>

                                <!-- Badges + Aktion -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <!-- Items -->
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full bg-zinc-50 px-2.5 py-1 text-[11px] font-medium text-zinc-700 ring-1 ring-inset ring-zinc-200"
                                    >
                                         <component :is="IconList" class="size-4" />
                                    <span class="whitespace-nowrap">{{ $t('Items') }}: {{ itemsCount }}</span>
                                  </span>

                                    <!-- Total qty -->
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full bg-zinc-50 px-2.5 py-1 text-[11px] font-medium text-zinc-700 ring-1 ring-inset ring-zinc-200"
                                    >
                                        <!-- Layers-Icon -->
                                        <component :is="IconStackForward" class="size-4" />
                                        <span class="whitespace-nowrap">{{ $t('Total qty') }}: {{ totalQuantity }}</span>
                                      </span>

                                    <!-- Clear all -->
                                    <button
                                        v-if="materialSetForm.items.length"
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium text-rose-700 ring-1 ring-inset ring-rose-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"
                                        @click="clearAll"
                                    >
                                        <!-- Trash-Icon -->
                                        <component :is="IconTrash" class="size-4" />
                                        <span class="whitespace-nowrap">{{ $t('Clear all') }}</span>
                                    </button>
                                </div>
                            </div>
                        </div>


                        <div class="mt-4">
                            <!-- Listendarstellung im Kartenstil -->
                            <div v-if="materialSetForm.items.length" class="p-5">
                                <div class="divide-y divide-zinc-200/80">
                                    <div
                                        v-for="(item, index) in materialSetForm.items"
                                        :key="item.id"
                                        :data-materialset-row="index"
                                        class="flex flex-col gap-3 py-3 md:flex-row md:items-center md:justify-between"
                                    >
                                        <div class="flex w-full items-start gap-4">
                                            <!-- NEU: nutzt erstes Bild aus item.images -->
                                            <!-- NEU: zeigt immer etwas – echte Preview oder Placeholder -->
                                            <div class="shrink-0" style="max-width: 120px">
                                                <div
                                                    class="group relative overflow-hidden rounded-lg"
                                                    :class="item?.images?.length ? 'cursor-zoom-in' : ''"
                                                    @click="item?.images?.length && openLightbox(0, item.images)"
                                                >
                                                    <img
                                                        :src="previewSrcFromItem(item)"
                                                        :alt="item.name || ''"
                                                        class="block h-auto w-full object-cover"
                                                        @error="onImgError"
                                                    />
                                                    <!-- Optionales Zoom-Overlay nur bei echten Bildern -->
                                                    <div
                                                        v-if="item?.images?.length"
                                                        class="pointer-events-none absolute inset-0 grid place-items-center bg-black/0 transition group-hover:bg-black/30"
                                                    >
                                                        <component :is="IconWindowMaximize" class="h-4 w-4 text-white opacity-0 transition group-hover:opacity-100" />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Textbereich -->
                                            <div class="min-w-0">
                                                <h4 class="flex items-center gap-1 text-sm font-semibold text-zinc-900">
                                                    {{ item.name }}
                                                    <!-- Optional: Detail-Icon, falls du später Details anzeigen willst
                                                    <component is="IconListDetails" class="h-4 w-4 text-zinc-400 hover:text-zinc-600" @click="openItemDetail(item)" />
                                                    -->
                                                </h4>
                                            </div>
                                        </div>

                                        <!-- Aktionen rechts: Menge + Entfernen -->
                                        <div class="flex items-center gap-4 md:gap-6">
                                            <div class="w-28">
                                                <BaseInput
                                                    :id="`materialset-qty-` + index"
                                                    type="number"
                                                    inputmode="numeric"
                                                    min="1"
                                                    step="1"
                                                    v-model.number="item.quantity"
                                                    :label="$t('Quantity')"
                                                    @change="normalizeQuantity(item)"
                                                />
                                            </div>

                                            <button
                                                type="button"
                                                class="rounded-md p-2 text-zinc-400 hover:bg-zinc-100 hover:text-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500"
                                                @click="removeItem(item.id)"
                                                :aria-label="$t('Remove')"
                                            >
                                                <component :is="IconTrash" class="h-5 w-5" stroke-width="1.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State im selben Stil wie in der Artikel-Ansicht -->
                            <div v-else class="p-5">
                                <BaseAlertComponent :message="$t('No items selected')" type="info" class="text-center" />
                            </div>
                        </div>

                    </div>
                </div>
            </section>


            <!-- Single global Galleria for lightbox -->
            <Galleria
                v-model:activeIndex="activeIndex"
                v-model:visible="displayCustom"
                :value="lightboxImages"
                :responsiveOptions="responsiveOptions"
                :numVisible="7"
                :pt="{ mask: { onClick: onMaskClick } }"
                :circular="true"
                :fullScreen="true"
                :showItemNavigators="true"
                :showThumbnails="true"
            >
                <template #item="slotProps">
                    <img :src="'/storage/' + slotProps.item.image" :alt="slotProps.item.alt || ''" style="width: 100%; display: block" @error="(e) => e.target.src = usePage().props.big_logo" />
                </template>
                <template #thumbnail="slotProps">
                    <img :src="'/storage/' + slotProps.item.image" :alt="slotProps.item.alt || ''" class="w-20 max-w-20" style="display: block" @error="(e) => e.target.src = usePage().props.big_logo" />
                </template>
            </Galleria>

            <!-- Sticky Actions -->
            <div class="sticky bottom-2 z-10 flex justify-end">
                <div>
                    <BaseUIButton is-add-button :label="materialSet?.id ? $t('Update Material Set') : $t('Create Material Set')" type="submit" :disabled="!canSubmit"/>
                </div>
            </div>
        </form>
    </ArtworkBaseModal>
</template>

<script setup lang="ts">
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import { useForm, usePage } from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArticleSearch from "@/Components/SearchBars/ArticleSearch.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import InventoryFunctionBarFilter from "@/Artwork/Filter/InventoryFunctionBarFilter.vue";
import { computed, onMounted, onBeforeUnmount, ref } from "vue";
import axios from "axios";
import Galleria from "primevue/galleria";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import { IconTrash, IconStackForward, IconList, IconWindowMaximize } from "@tabler/icons-vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

type Article = {
    id: number | string;
    name: string;
    description?: string | null;
    images?: ArticleImage[];
    category?: { name?: string | null };
    sub_category?: { name?: string | null } | null;
};

type ArticleImage = { image?: string; alt?: string | null };

type MaterialSetItem = {
    id: number | string;
    name: string;
    quantity: number;
    images?: ArticleImage[]; // <— NEU
};

type MaterialSetProp = {
    id: number | string | null;
    name: string;
    description?: string;
    items: Array<{ article: { id: number | string; name: string }, quantity?: number }> | Array<MaterialSetItem>;
};

const props = defineProps<{ materialSet?: MaterialSetProp }>();
const emit = defineEmits<{ (e: 'close'): void }>();

const materialSetForm = useForm<{ id: number | string | null; name: string; description: string; items: MaterialSetItem[]; }>({
    id: props.materialSet?.id ?? null,
    name: props.materialSet?.name ?? "",
    description: props.materialSet?.description ?? "",
    items: Array.isArray(props.materialSet?.items)
        ? (props.materialSet!.items as any[]).map((raw) =>
            raw?.article
                ? {
                    id: raw.article.id,
                    name: raw.article.name,
                    quantity: raw.quantity ?? 1,
                    images: raw.article.images ?? [], // <— NEU
                }
                : {
                    id: raw.id,
                    name: raw.name,
                    quantity: raw.quantity ?? 1,
                    images: raw.images ?? [], // <— NEU (falls flach geliefert)
                }
        )
        : [],
});

// Derived
const itemsCount = computed(() => materialSetForm.items.length);
const totalQuantity = computed(() => materialSetForm.items.reduce((s, i) => s + (Number(i.quantity) || 0), 0));
const canSubmit = computed(() => materialSetForm.name.trim().length > 0 && materialSetForm.items.length > 0);

// Artikel-Liste & Infinite Scroll
const articles = ref<Article[]>([]);
const initialLoading = ref(true);
const loadingMore = ref(false);
const hasMoreArticles = ref(true);
const paginationPage = ref(1);
const scrollContainer = ref<HTMLElement | null>(null);
const sentinel = ref<HTMLElement | null>(null);
let io: IntersectionObserver | null = null;

const activeIndex = ref(0);
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
]);
const displayCustom = ref(false);

const imageClick = (index) => {
    activeIndex.value = index;
    displayCustom.value = true;
};

const lightboxImages = ref([])

function openLightbox(startIndex, images) {
    lightboxImages.value = images || []
    // @ts-ignore
    activeIndex.value = startIndex || 0
    // @ts-ignore
    displayCustom.value = true
}



const onMaskClick = (e) => {
    if (e.target === e.currentTarget) {
        displayCustom.value = false
    }
}

onMounted(async () => {
    await loadMoreArticles(); // initial
    initialLoading.value = false;

    // IntersectionObserver für Infinite Scroll
    if (sentinel.value) {
        io = new IntersectionObserver(async (entries) => {
            const [entry] = entries;
            if (entry.isIntersecting && hasMoreArticles.value && !loadingMore.value) {
                await loadMoreArticles();
            }
        }, { root: scrollContainer.value ?? undefined, rootMargin: "0px 0px 200px 0px", threshold: 0 });
        io.observe(sentinel.value);
    }
});

onBeforeUnmount(() => { io?.disconnect(); });

async function loadMoreArticles() {
    loadingMore.value = true;
    try {
        const res = await axios.get(route('inventory.articles.api', { page: paginationPage.value }));
        const newArticles: Article[] = (res.data?.articles?.data ?? []).reverse();

        for (const a of newArticles) {
            if (!articles.value.find((x) => x.id === a.id)) articles.value.push(a);
        }

        if (!res.data?.articles?.next_page_url) {
            hasMoreArticles.value = false;
        } else {
            paginationPage.value += 1;
        }
    } catch (e) {
        console.error('Error loading articles:', e);
        hasMoreArticles.value = false;
    } finally {
        loadingMore.value = false;
    }
}

async function reloadArticlesWithNewFilter() {
    articles.value = [];
    paginationPage.value = 1;
    hasMoreArticles.value = true;
    initialLoading.value = true;
    await loadMoreArticles();
    initialLoading.value = false;
}

// Bild-Helper
const page = usePage();

function firstImage(x: { images?: ArticleImage[] } | undefined) {
    return x?.images?.[0]?.image || '';
}

function imageSrc(path?: string) {
    const fallback = (usePage().props as any)?.big_logo ?? '';
    if (!path) return fallback;
    return path.startsWith('/storage/') ? path : `/storage/${path}`;
}

/** Daten-URL Placeholder (SVG), fallback falls kein big_logo existiert */
function placeholderSrc(label?: string) {
    const text = (label || 'No image').slice(0, 24);
    const svg =
        `<svg xmlns='http://www.w3.org/2000/svg' width='400' height='300'>
       <rect width='100%' height='100%' fill='#EEEFF4'/>
       <text x='50%' y='50%' dominant-baseline='middle' text-anchor='middle'
             font-family='Inter, system-ui, sans-serif' font-size='20' fill='#9AA3AF'>${text}</text>
     </svg>`;
    return 'data:image/svg+xml;utf8,' + encodeURIComponent(svg);
}

function previewSrcFromArticle(article: Article) {
    const src = article?.images?.[0]?.image;
    if (src) return imageSrc(src);
    const logo = (usePage().props as any)?.big_logo;
    return logo || placeholderSrc(article?.name);
}

function previewSrcFromItem(item: MaterialSetItem) {
    const src = firstImage(item as any);
    if (src) return imageSrc(src);
    const logo = (usePage().props as any)?.big_logo;
    return logo || placeholderSrc(item?.name);
}

/** onError -> immer auf Placeholder zurückfallen */
function onImgError(ev: Event) {
    (ev.target as HTMLImageElement).src =
        (usePage().props as any)?.big_logo || placeholderSrc('No image');
}

// Auswahl
function addItemToSet(article: Article) {
    const existing = materialSetForm.items.find(i => i.id === article.id);
    if (existing) {
        existing.quantity = clampInt(existing.quantity + 1, 1);
        // falls vorher ohne Bilder hinzugefügt wurde, jetzt nachziehen
        if (!existing.images?.length && article.images?.length) existing.images = article.images;
    } else {
        materialSetForm.items.push({
            id: article.id,
            name: article.name,
            quantity: 1,
            images: article.images ?? [], // <— NEU
        });
    }
}
// in der Trefferliste einfach den Artikel durchreichen:
function onArticleClick(article: Article) {
    addItemToSet(article);
}
function incrementFromList(id: number | string) {
    const item = materialSetForm.items.find(i => i.id === id);
    if (item) item.quantity = clampInt(item.quantity + 1, 1);
}

function removeItem(id: number | string) {
    materialSetForm.items = materialSetForm.items.filter(i => i.id !== id);
}
function clearAll() {
    materialSetForm.items = [];
}
function increment(item: MaterialSetItem) {
    item.quantity = clampInt((Number(item.quantity) || 0) + 1, 1);
}
function decrement(item: MaterialSetItem) {
    item.quantity = clampInt((Number(item.quantity) || 0) - 1, 1);
}
function normalizeQuantity(item: MaterialSetItem) {
    item.quantity = clampInt(Number(item.quantity) || 1, 1);
}
function clampInt(v: number, min = 1) {
    return Math.max(min, Math.round(v));
}

// Already selected?
function isSelected(id: number | string) {
    return materialSetForm.items.some(i => i.id === id);
}
function qtyOf(id: number | string) {
    return materialSetForm.items.find(i => i.id === id)?.quantity ?? 0;
}

// Submit
function submit() {
    if (props.materialSet?.id && materialSetForm.id) {
        materialSetForm.patch(route('material-sets.update', props.materialSet.id), { onSuccess: () => emit('close') });
    } else {
        materialSetForm.post(route('material-sets.store'), { onSuccess: () => emit('close') });
    }
}
</script>

