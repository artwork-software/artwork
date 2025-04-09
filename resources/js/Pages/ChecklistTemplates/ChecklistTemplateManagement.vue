<template>
    <app-layout :title="$t('Checklist templates')">
        <div class="ml-14 mt-5">

            <div>
                <ChecklistFunctionBar :title="$t('Checklist templates')">
                    <template v-slot:buttons>
                        <Link class="-mt-1" :href="route('checklist_templates.create')">
                            <AddButtonSmall :text="$t('New template')" />
                        </Link>
                    </template>
                </ChecklistFunctionBar>
            </div>

            <div class="my-10">
                <div v-if="$page.props.auth.user.checklist_style === 'list'">
                    <div class="bg-gray-100 px-5 py-2 rounded-lg xxsLight mb-5">
                        <div class="grid grid-cols-12 grid-rows-1 gap-4">
                            <div class="col-span-8">{{ $t('Name') }}</div>
                            <div class="col-span-4 col-start-9">{{ $t('Creator') }}</div>
                        </div>
                    </div>
                    <SingleChecklistTemplateListView
                        v-for="(template,index) in checklist_templates"
                        :checklist_template="template"
                    />
                </div>
                <div v-else>
                    <div class="grid grid-cols-1 md:grid-cols-8 gap-4">
                        <SingleChecklistTemplateGridView
                            v-for="(template,index) in checklist_templates"
                            :checklist_template="template"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Modal -->
        <SuccessModal
            :show="showSuccessModal"
            @closed="closeSuccessModal"
            :title="this.successHeading"
            :description="this.successText"
            button="Schließen"
        />
    </app-layout>
</template>

<script>

import {Link, router, usePage} from "@inertiajs/vue3";
import {DotsVerticalIcon, DuplicateIcon, PencilAltIcon, SearchIcon, TrashIcon, XIcon} from "@heroicons/vue/outline";
import {CheckIcon, PlusSmIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import AppLayout from '@/Layouts/AppLayout.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import IconLib from "@/Mixins/IconLib.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ChecklistFunctionBar from "@/Components/Checklist/ChecklistFunctionBar.vue";
import SingleChecklistTemplateListView from "@/Pages/ChecklistTemplates/Components/SingleChecklistTemplateListView.vue";
import SingleChecklistTemplateGridView from "@/Pages/ChecklistTemplates/Components/SingleChecklistTemplateGridView.vue";

export default {
    mixins: [Permissions, IconLib],
    name: "Checklist Management",
    props: ['checklist_templates'],
    components: {
        SingleChecklistTemplateGridView,
        SingleChecklistTemplateListView,
        ChecklistFunctionBar,
        BaseModal,
        BaseMenu,
        AddButtonSmall,
        SuccessModal,
        PlusSmIcon,
        SvgCollection,
        AppLayout,
        SearchIcon,
        Menu,
        MenuButton,
        MenuItems,
        MenuItem,
        TrashIcon,
        DotsVerticalIcon,
        PencilAltIcon,
        DuplicateIcon,
        Link,
        JetDialogModal,
        XIcon,
        UserTooltip,
        CheckIcon,
        InputComponent
    },
    data() {
        return {
            templateToDelete: null,
            showDeleteTemplateModal: false,
            showSuccessModal: false,
            successHeading: '',
            successText:'',
            template_query: '',
            template_search_results:[],
            showSearchbar: false,
            duplicateForm: this.$inertia.form({
                _method: 'POST',
                name: "",
                //user who created the template
                user_id: this.$page.props.auth.user.id,
                task_templates: [],
                users: []
            }),
        }
    },
    methods: {
        usePage,
        closeSearchbar() {
            this.showSearchbar = !this.showSearchbar;
            this.template_query = ''
        },
        getEditHref(template) {
            return route('checklist_templates.edit', {checklist_template: template.id});
        },
        openDeleteTemplateModal(template){
            this.templateToDelete = template;
            this.showDeleteTemplateModal = true;
        },
        closeDeleteTemplateModal(){
            this.showDeleteTemplateModal = false;
            this.templateToDelete = null;
        },
        deleteTemplate(){
            router.delete(`/checklist_templates/${this.templateToDelete.id}`);
            this.closeDeleteTemplateModal();
            this.openSuccessModal('delete')
        },
        duplicateTemplate(templateToDuplicate){
            this.duplicateForm.name = templateToDuplicate.name + ' (Kopie)';
            this.duplicateForm.task_templates = templateToDuplicate.task_templates;
            this.duplicateForm.users = templateToDuplicate.users
            this.duplicateForm.post(route('checklist_templates.store'));
        },
        openSuccessModal(type){
            if(type === 'delete'){
                this.successHeading = this.$t('Delete successful');
                this.successText = this.$t('The checklist template has been successfully deleted.')
            }else if(type === 'edit'){
                this.successHeading = this.$t('Checklist template successfully edited');
                this.successText = this.$t('The changes have been saved successfully.');
            }
            this.showSuccessModal = true;
            setTimeout(() => this.closeSuccessModal(), 2000)
        },
        closeSuccessModal(){
            this.showSuccessModal = false;
        },
    },
    setup() {
        return {}
    },
    watch: {
        template_query: {
            handler() {
                if (this.template_query.length > 0) {
                    axios.get('/checklist_templates/search', {
                        params: {query: this.template_query}
                    }).then(response => {
                        this.template_search_results = response.data
                    })
                }
            },
            deep: true
        }
    },
}
</script>
