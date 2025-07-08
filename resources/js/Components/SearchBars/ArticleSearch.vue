<template>
    <div class="relative">
        <div class="my-auto w-full">
            <BaseInput
                :id="id"
                v-model="article_search_query"
                :label="label"
            />
        </div>
        <transition leave-active-class="transition ease-in duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="articles.length > 0" class="absolute rounded-lg z-10 w-full max-h-60 bg-white shadow-lg text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                <div class="border-gray-200 py-4">
                    <div v-for="(article, index) in articles" :key="index" class="flex items-center cursor-pointer">
                        <div class="flex-1 text-sm" @click="selectArticle(article)">
                            <div class="px-4 py-3 relative border-l-4 border-l-transparent hover:border-l-transparent before:absolute before:left-0 before:top-0 before:bottom-0 before:w-1 hover:before:bg-artwork-buttons-create before:transition-colors">
                                <p class="truncate font-medium font-lexend">{{ article.name }}</p>
                                <p class="text-xs text-gray-400 mt-0.5 font-lexend font-light">
                                    {{ $t('This article comes from the category {0}.', [article.category.name])}}
                                    <span v-if="article.sub_category" class="text-gray-400"> | {{$t('Sub-Category')}} {{ article.sub_category.name }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>

<script>
import TeamIconCollection from "@/Layouts/Components/TeamIconCollection.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import IconLib from "@/Mixins/IconLib.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import debounce from "lodash.debounce";

export default {
    name: "ArticleSearch",
    mixins: [IconLib],
    components: {BaseInput, AlertComponent, TextInputComponent, TeamIconCollection},
    data() {
        return {
            article_search_query: '',
            articles: [],
            showLoading: false,
        }
    },
    props: {
        label: {
            type: String,
            default: 'Search for Articles'
        },
        id: {
            type: String,
            default: 'article_search_input'
        }
    },
    emits: ['article-selected'],
    methods: {
        selectArticle(selectedArticle) {
            this.$emit('article-selected', selectedArticle);
            this.article_search_query = '';
            this.articles = [];
            this.showLoading = false;
        },
        searchArticles() {
            if (this.article_search_query.length > 2) {
                axios.post(route('inventory.articles.search'),{
                    article_search: this.article_search_query,
                    wantsJson: true,
                }).then(response => {
                    this.showLoading = false;
                    this.articles = response.data;
                });
            } else {
                this.articles = [];
            }
        }
    },
    watch: {
        article_search_query: {
            handler() {
                this.showLoading = true;
                debounce(() => {
                    this.searchArticles();
                }, 300)();
            },
            deep: true
        }
    }
}
</script>
