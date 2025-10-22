<template>
    <ArtworkBaseModal
        title="Product Basket"
        description="Product basket for quick use in material issue"
        modal-size="max-w-4xl"
        @close="closeModal(false)"
    >
        <!-- Tabs -->
        <BaseTabs
            :tabs="basketFormatedTabs"
            navigation-mode="buttons"
            @tab-select="selectTab"
        />

        <!-- Suche -->
        <div class="my-3">
            <BaseInput
                v-model="searchQuery"
                type="search"
                label="Search"
                id="search-input"
            />
        </div>

        <!-- Loading Skeleton -->
        <div v-if="isLoadingBaskets">
            <div v-for="i in 5" :key="i" class="mb-4">
                <div class="mx-auto w-full rounded-md border border-gray-200 p-4">
                    <div class="flex items-center animate-pulse space-x-4 w-full">
                        <div class="size-12 min-w-12 rounded-lg bg-gray-200"></div>
                        <div class="flex items-center justify-between w-full">
                            <div>
                                <div class="h-4 w-48 rounded bg-gray-200 mb-2"></div>
                                <div class="h-4 w-32 rounded bg-gray-200"></div>
                            </div>
                            <div class="flex items-center justify-end space-x-4">
                                <div class="h-8 w-20 rounded bg-gray-200"></div>
                                <div class="h-8 w-12 rounded bg-gray-200"></div>
                                <div class="h-8 w-20 rounded bg-gray-200"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leerer Zustand -->
        <div v-else-if="!articlesFlat.length" class="text-sm text-gray-500 py-6 text-center">
            {{ $t ? $t('No articles found') : 'No articles found' }}
        </div>

        <!-- Artikelliste -->
        <div v-else>
            <div
                v-for="(article, index) in articlesFlat"
                :key="article.basketArticleId"
                class="mb-4"
            >
                <div class="mx-auto w-full rounded-md border border-gray-200 p-4 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <img
                            :src="getMainImageInImage(article).image"
                            alt=""
                            class="size-12 min-w-12 rounded-lg object-cover"
                            @error="handleImageError"
                        />
                        <div>
                            <div class="font-semibold text-lg">
                                {{ article.name }}
                            </div>
                            <div class="text-sm text-gray-500">
                                {{ article.description || ($t ? $t('No description') : 'No description') }}
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end space-x-4">
                        <!-- button - -->
                        <button
                            class="ui-button-delete"
                            :disabled="article.quantity <= 0 || isSaving(article.basketArticleId)"
                            @click="changeQuantity(article.basketArticleId, 'decrease')"
                            :aria-disabled="String(article.quantity <= 0 || isSaving(article.basketArticleId))"
                        >
                            –
                        </button>

                        <!-- quantity -->
                        <BaseInput
                            :id="'article' + index"
                            label="Quantity"
                            is-small
                            type="number"
                            min="0"
                            v-model="article.quantity"
                            @focusout="onQuantityInputCommit(article)"
                        />

                        <!-- button + -->
                        <button
                            class="ui-button-add"
                            :disabled="isSaving(article.basketArticleId)"
                            @click="changeQuantity(article.basketArticleId, 'increase')"
                            :aria-disabled="String(isSaving(article.basketArticleId))"
                        >
                            +
                        </button>
                        <BaseUIButton
                            class="ui-button-delete"
                            :disabled="isSaving(article.basketArticleId)"
                            @click="removeArticle(article.basketArticleId)"
                            :aria-disabled="String(isSaving(article.basketArticleId))"
                            icon="IconTrash"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-between space-x-4 mt-4">
            <SwitchDualLabel
                v-model="internOrExternIssue"
                :left-label="$t('Intern Material Issue')"
                :right-label="$t('External Material Issue')"
                size="md"
                icon="IconGeometry"
                :disabled="false"
            />

            <BaseUIButton @click="closeModal(true)" label="Create Material Issue" use-translation type="submit" />
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseTabs from "@/Artwork/Tabs/BaseTabs.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {computed, onMounted, reactive, ref} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import SwitchDualLabel from "@/Artwork/Toggles/SwitchDualLabel.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
// axios + route (Ziggy) vorausgesetzt

const baskets = ref([]);
const isLoadingBaskets = ref(true);
const internOrExternIssue = ref(false);
const currentBasket = ref(1);
const searchQuery = ref("");
const emit = defineEmits(['close']);
const handleImageError = (e) => {
    e.target.src = usePage().props.big_logo;
};

const loadBaskets = async () => {
    try {
        const response = await axios
            .get(route("inventory.product_basket.get_baskets"))
            .then((res) => res.data);
        baskets.value = response?.baskets ?? [];
        if (!baskets.value.find(b => b.id === currentBasket.value) && baskets.value.length) {
            currentBasket.value = baskets.value[0].id;
        }
    } catch (e) {
        console.error(e);
        baskets.value = [];
    } finally {
        isLoadingBaskets.value = false;
    }
};

const basketFormatedTabs = computed(() => {
    return (baskets.value || []).map((basket) => ({
        id: basket.id,
        name: basket.name,
        permission: true,
        current: basket.id === currentBasket.value,
    }));
});

const selectTab = (tab) => {
    // robust: erst id, dann name
    const selected = baskets.value.find((b) => b.id === tab.id) || baskets.value.find((b) => b.name === tab.name);
    if (selected) currentBasket.value = selected.id;
};

const currentBasketObj = computed(() =>
    baskets.value.find((b) => b.id === currentBasket.value) || null
);

const getMainImageInImage = (article) => {
    const images = article.images || [];
    const mainImage = images.find(image => image.is_main_image);
    if (mainImage) return { image: '/storage/' + mainImage.image };
    if (images.length > 0) return { image: '/storage/' + images[0].image };
    return { image: usePage().props.big_logo };
};

/**
 * Flatten pro aktivem Basket
 */
const articlesFlat = computed(() => {
    const b = currentBasketObj.value;
    if (!b) return [];
    const list = (b.basket_articles ?? []).map((ba) => ({
        basketArticleId: ba.id,
        articleId: ba.article_id,
        name: ba.article?.name ?? "—",
        description: ba.article?.description ?? "",
        quantity: Number(ba.quantity ?? 0),
        images: ba.article?.images || [],
    }));
    const q = (searchQuery.value || "").trim().toLowerCase();
    if (!q) return list;
    return list.filter((a) =>
        (a.name || "").toLowerCase().includes(q) ||
        (a.description || "").toLowerCase().includes(q)
    );
});

/** ======= Optimistische Update-Logik mit Sequenzschutz ======= */
const savingIds = ref(new Set());                              // UI-Disable
const isSaving = (id) => savingIds.value.has(id);
const setSaving = (id, on) => {
    const next = new Set(savingIds.value);
    on ? next.add(id) : next.delete(id);
    savingIds.value = next;
};
const seq = reactive({});                                      // basketArticleId -> counter
const findArticleById = (id) => articlesFlat.value.find(a => a.basketArticleId === id);

/**
 * Gemeinsamer Pfad: numerisches Delta bevorzugt,
 * fallback auf "increase"/"decrease" (+ steps) wenn nötig.
 */
const updateQuantityDelta = async (basketArticleId, deltaNum) => {
    if (!deltaNum) return;
    const article = findArticleById(basketArticleId);
    if (!article) return;

    seq[basketArticleId] = (seq[basketArticleId] ?? 0) + 1;
    const mySeq = seq[basketArticleId];

    const oldQty = article.quantity;
    // Optimistisches Update
    article.quantity = Math.max(0, oldQty + deltaNum);
    setSaving(basketArticleId, true);

    try {
        // bevorzugt: numerisches Delta
        await axios.post(route("inventory.product_basket.update_quantity", { basketArticle: basketArticleId }), {
            basketArticle: basketArticleId,
            delta: deltaNum, // << numeric preferred
        });
    } catch (e1) {
        // fallback: strings
        try {
            const payload = {
                basketArticle: basketArticleId,
                delta: deltaNum > 0 ? "increase" : "decrease",
                steps: Math.abs(deltaNum),
            };
            await axios.post(route("inventory.product_basket.update_quantity", { basketArticle: basketArticleId }), payload);
        } catch (e2) {
            // Rollback
            article.quantity = oldQty;
            throw e2;
        }
    } finally {
        // nur letzte Antwort entsperrt
        if (seq[basketArticleId] === mySeq) {
            setSaving(basketArticleId, false);
        }

        router.reload({
            only: ['productBaskets']
        })
    }
};

const changeQuantity = async (basketArticleId, delta /* 'increase' | 'decrease' */) => {
    const deltaNum = delta === "increase" ? 1 : -1;
    await updateQuantityDelta(basketArticleId, deltaNum);
};

// Menge aus Input als ABSOLUTE Zielmenge setzen (on blur/enter)
const onQuantityInputCommit = async (article) => {
    console.log(article);

    await axios.post(route("inventory.product_basket.update_quantity.single", { basketArticle: article.basketArticleId }), {
        basketArticle: article.basketArticleId,
        quantity: article.quantity, // << numeric preferred
    }).finally(() => {
        router.reload({
            only: ['productBaskets']
        })
    });
};

const removeArticle = async (basketArticleId) => {
    if (isSaving(basketArticleId)) return;
    setSaving(basketArticleId, true);
    try {
        await axios.delete(route('inventory.product_basket.remove', { basketArticle: basketArticleId }));
        await loadBaskets();
    } finally {
        setSaving(basketArticleId, false);
        router.reload({
            only: ['productBaskets']
        })
    }
};

const updateQuantityDirectWithInput = async (basketArticleId, targetQty) => {
    const article = findArticleById(basketArticleId);
    if (!article) return;
    const currentQty = article.quantity;
    const deltaNum = targetQty - currentQty;
    if (deltaNum === 0) return;
    await updateQuantityDelta(basketArticleId, deltaNum);
};

onMounted(loadBaskets);

const  closeModal = (createIssue) => {
    const payload = {
        createIssue,
        internOrExternIssue: internOrExternIssue.value,
        basketId: currentBasket.value,
    };
    emit('close', payload);
};
</script>

<style scoped>
.min-w-12 { min-width: 3rem; }
</style>
