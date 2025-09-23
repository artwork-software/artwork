<template>
    <div class="relative">
        <div class="my-auto w-full">
            <BaseInput
                :id="id"
                v-model="article_search_query"
                :label="label || 'Artikel suchen...'"
            />
        </div>
        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="articles.length > 0" class="absolute rounded-lg z-10 w-full max-h-60 bg-white shadow-lg text-base border border-gray-300 overflow-auto focus:outline-none sm:text-sm">
                <div class="border-gray-200 py-4">
                    <div v-for="(article, index) in articles" :key="index" class="flex items-center cursor-pointer">
                        <div class="flex-1 text-sm" @click="selectArticle(article)">
                            <div class="px-4 py-3 relative border-l-4 border-l-transparent hover:border-l-transparent before:absolute before:left-0 before:top-0 before:bottom-0 before:w-1 hover:before:bg-artwork-buttons-create before:transition-colors">
                                <p class="truncate font-medium font-lexend">{{ article.name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5 font-lexend font-light">
                                    {{ $t('Dieser Artikel stammt aus der Kategorie {0}.', [article.category?.name])}}
                                    <span v-if="article.sub_category" class="text-gray-400"> | {{$t('Unterkategorie')}} {{ article.sub_category?.name }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script setup lang="ts">
import { ref, watch, getCurrentInstance } from 'vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import debounce from 'lodash.debounce';
import axios from 'axios';

type Article = {
    name: string;
    category?: { name: string };
    sub_category?: { name: string };
};

const props = defineProps<{
    label?: string;
    id?: string;
    startDate?: string | null;
    endDate?: string | null;
}>();
const emit = defineEmits(['article-selected']);

const article_search_query = ref<string>('');
const articles = ref<Article[]>([]);
const showLoading = ref<boolean>(false);

const { proxy } = getCurrentInstance()!;
const $t = proxy.$t as (key: string, args?: any[]) => string;

function selectArticle(selectedArticle: Article) {
    emit('article-selected', selectedArticle);
    article_search_query.value = '';
    articles.value = [];
    showLoading.value = false;
}

const searchArticles = debounce(async () => {
    if (article_search_query.value.length >= 2) {
        try {
            const response = await axios.post(route('inventory.articles.search'), {
                article_search: article_search_query.value,
                start_date: props.startDate,
                end_date: props.endDate,
                wantsJson: true,
            });
            showLoading.value = false;
            articles.value = response.data;
        } catch {
            articles.value = [];
            showLoading.value = false;
        }
    } else {
        articles.value = [];
        showLoading.value = false;
    }
}, 300);

watch([article_search_query, () => props.startDate, () => props.endDate], () => {
    showLoading.value = article_search_query.value.length >= 2;
    searchArticles();
});
</script>

