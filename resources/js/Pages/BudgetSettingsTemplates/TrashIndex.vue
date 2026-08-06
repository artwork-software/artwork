<template>
    <div class="flex justify-between">
        <div></div>
        <div class="flex items-center">
            <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                 class="cursor-pointer inset-y-0 mr-3">
                <IconSearch class="h-5 w-5" aria-hidden="true"/>
            </div>
            <div v-else class="flex items-center w-64 mr-2">
                <inputComponent v-model="template_search" :placeholder="$t('Search for templates')"/>
                <IconX class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
            </div>
        </div>
    </div>
    <div v-for="table in filteredTemplates" class="flex my-6 border-t-2 border-b-2 w-full border-border">
        <button class="bg-accent-600 w-6"
                @click="table.closed = !table.closed">
            <IconChevronUp v-if="table.closed"
                           class="h-6 w-6 text-white my-auto"></IconChevronUp>
            <IconChevronDown v-else
                             class="h-6 w-6 text-white my-auto"></IconChevronDown>
        </button>
        <div class="ml-4 my-4">
            <BudgetComponent v-if="!table.closed"
                             :table="table"
                             :selectedCell="budget.selectedCell"
                             :selectedRow="budget.selectedRow"
                             :templates="budget.templates"
                             :is-budget-template-management="true"
                             :isInTrash="true"
            />
            <div v-else>
                <div class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text ">
                    {{ table.name }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {IconChevronDown, IconChevronUp, IconSearch, IconX} from "@tabler/icons-vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import BudgetComponent from "@/Layouts/Components/BudgetComponent.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import BudgetSettingsHeader from "@/Pages/BudgetSettings/BudgetSettingsHeader.vue";
import TrashLayout from "@/Layouts/TrashLayout.vue";


export default {
    mixins: [Permissions],
    layout: [AppLayout, TrashLayout],
    name: "BudgetTemplateManagement",
    components: {
        BudgetSettingsHeader,
        BudgetComponent, AppLayout, IconChevronUp, IconChevronDown,InputComponent, IconX, IconSearch},
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
