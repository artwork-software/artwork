<template>
    <BudgetSettingsHeader :title="$t('Budget templates')" :description="$t('Define settings for your budget.')">
        <SettingsGuideBanner
            variant="banner"
            storage-key="settings-guide.budget.templates"
            title="How budget templates work"
            :paragraphs="guideParagraphs"
            footnote="Caution: applying a template to a project irreversibly replaces the project's existing budget table."
            class="mb-6"
        />
        <div v-if="!budget.table.length" class="text-center text-text-subtle py-16">
            {{ $t('No budget templates yet. Create one by choosing Save as template in a project budget.') }}
        </div>
        <div v-else class="flex justify-between">
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
                />
                <div v-else>
                    <div class="font-lexend font-semibold text-[clamp(18px,2.5vw,20px)]/[25px] text-text ">
                        {{ table.name }}
                    </div>
                </div>
            </div>
        </div>
    </BudgetSettingsHeader>
</template>

<script>
import {IconChevronDown, IconChevronUp, IconSearch, IconX} from "@tabler/icons-vue";
import AppLayout from "@/Layouts/AppLayout.vue";
import BudgetComponent from "@/Layouts/Components/BudgetComponent.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import BudgetSettingsHeader from "@/Pages/BudgetSettings/BudgetSettingsHeader.vue";
import SettingsGuideBanner from "@/Artwork/Guide/SettingsGuideBanner.vue";


export default {
    mixins: [Permissions],
    name: "BudgetTemplateManagement",
    components: {
        SettingsGuideBanner,
        BudgetSettingsHeader,
        BudgetComponent, AppLayout, IconChevronUp, IconChevronDown,InputComponent, IconX, IconSearch},
    props: ['budget'],
    data(){
        return{
            showSearchbar: false,
            template_search: '',
            guideParagraphs: [
                'Templates are not created here: open a project budget and choose Save as template — the template then appears on this page.',
                'This page is only for maintaining existing templates.',
            ],
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
