<template>
    <BudgetSettingsHeader>
        <div class="flex justify-between">
            <div></div>
            <div class="flex items-center">
                <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                     class="cursor-pointer inset-y-0 mr-3">
                    <SearchIcon class="h-5 w-5" aria-hidden="true"/>
                </div>
                <div v-else class="flex items-center w-64 mr-2">
                    <inputComponent v-model="template_search" :placeholder="$t('Search for templates')"/>
                    <XIcon class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                </div>
            </div>
        </div>
        <div v-for="table in filteredTemplates" class="flex my-6 border-t-2 border-b-2 w-full border-gray-300">
            <button class="bg-artwork-buttons-create w-6"
                    @click="table.closed = !table.closed">
                <ChevronUpIcon v-if="table.closed"
                               class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                <ChevronDownIcon v-else
                                 class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
            </button>
            <div class="ml-4 my-4">
                <BudgetComponent v-if="!table.closed"
                                 :table="table"
                                 :project="project"
                                 :selectedCell="budget.selectedCell"
                                 :selectedRow="budget.selectedRow"
                                 :templates="budget.templates"
                                 :money-sources="moneySources"
                                 :is-budget-template-management="true"
                />
                <div v-else>
                    <div class="headline2 ">
                        {{ table.name }}
                    </div>
                </div>
            </div>
        </div>
    </BudgetSettingsHeader>
</template>

<script>
import AppLayout from "@/Layouts/AppLayout.vue";
import BudgetComponent from "@/Layouts/Components/BudgetComponent.vue";
import {ChevronDownIcon, ChevronUpIcon, SearchIcon, XIcon} from "@heroicons/vue/solid";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import BudgetSettingsHeader from "@/Pages/BudgetSettings/BudgetSettingsHeader.vue";


export default {
    mixins: [Permissions],
    name: "BudgetTemplateManagement",
    components: {
        BudgetSettingsHeader,
        BudgetComponent, AppLayout, ChevronUpIcon, ChevronDownIcon,InputComponent, XIcon, SearchIcon},
    props: ['budget'],
    data(){
        return{
            showSearchbar: false,
            template_search: '',
        }
    },
    methods:{
        closeSearchbar() {
            this.showSearchbar = !this.showSearchbar;
            this.template_search = ''
        },
    },
    computed:{
        filteredTemplates() {
            return this.budget.table.filter(table => {
                return table.name.toLowerCase().includes(this.template_search.toLowerCase());
            });
        }
    }
}
</script>

<style scoped>

</style>
