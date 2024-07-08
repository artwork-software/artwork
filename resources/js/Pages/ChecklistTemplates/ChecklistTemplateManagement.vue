<template>
    <app-layout :title="$t('Checklist templates')">
        <div class="ml-14">

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
                <div v-if="$page.props.user.checklist_style === 'list'">
                    <div class="bg-gray-100 px-5 py-2 rounded-lg xxsLight mb-5">
                        <div class="grid grid-cols-12 grid-rows-1 gap-4">
                            <div class="col-span-8">Name</div>
                            <div class="col-span-4 col-start-9">Creator</div>
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

            <!-- <ul role="list" class="mt-6 mb-32 w-full">
                <li v-if="template_query < 1" v-for="(template,index) in checklist_templates" :key="template.email"
                    class="py-3 flex justify-between">
                    <div class="flex">
                        <div class="my-auto w-full justify-start mr-6">
                            <div class="flex my-auto items-center">
                                <Link :href="getEditHref(template)" class="mr-3 sDark">
                                    {{ template.name }} </Link>
                                <p class="ml-1 xsLight my-auto">
                                    {{ $t('created on { created_at } by', { created_at: template.created_at })}}
                                </p>
                                <UserPopoverTooltip :height="6" :width="6" :user="template.user" :id="template.user.id" class="ml-2"/>
                            </div>
                        </div>
                    </div>
                    <div class="flex">
                        <div class="flex mr-8 items-center">
                            <BaseMenu>
                                <MenuItem v-slot="{ active }">
                                    <a :href="getEditHref(template)"
                                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased capitalize']">
                                        <IconEdit  stroke-width="1.5"
                                                   class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                   aria-hidden="true"/>
                                        {{ $t('edit')}}
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="duplicateTemplate(template)"
                                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <IconCopy  stroke-width="1.5"
                                                   class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                   aria-hidden="true"/>
                                        {{$t('Duplicate')}}
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="openDeleteTemplateModal(template)"
                                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <IconTrash stroke-width="1.5"
                                                   class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                   aria-hidden="true"/>
                                        {{ $t('Delete') }}
                                    </a>
                                </MenuItem>
                            </BaseMenu>
                        </div>
                    </div>
                </li>
                <li v-else v-for="(template,index) in template_search_results" :key="template.email"
                    class="py-3 flex justify-between">
                    <div class="flex">
                        <div class="my-auto w-full justify-start mr-6">
                            <div class="flex my-auto items-center">
                                <p class="mr-3 sDark">
                                    {{ template.name }} </p>
                                <p class="ml-1 xsLight my-auto">
                                    {{ $t('created on { created_at } by', { created_at: template.created_at })}}
                                </p>
                                <UserPopoverTooltip :height="6" :width="6" :user="template.user" :id="template.user.id" class="ml-2"/>
                            </div>
                        </div>
                    </div>
                    <div class="flex">
                        <div class="flex mr-8 items-center">
                            <BaseMenu>
                                <MenuItem v-slot="{ active }">
                                    <a :href="getEditHref(template)"
                                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased capitalize']">
                                        <IconEdit stroke-width="1.5"
                                                  class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                  aria-hidden="true"/>
                                        {{ $t('edit')}}
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="duplicateTemplate(template)"
                                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <IconCopy stroke-width="1.5"
                                                  class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                  aria-hidden="true"/>
                                        {{$t('Duplicate')}}
                                    </a>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <a href="#" @click="openDeleteTemplateModal(template)"
                                       :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                        <IconTrash stroke-width="1.5"
                                                   class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                   aria-hidden="true"/>
                                        {{ $t('Delete') }}
                                    </a>
                                </MenuItem>
                            </BaseMenu>
                        </div>
                    </div>
                </li>
            </ul>-->
        </div>
        <!-- Delete Project Modal -->
        <BaseModal @closed="closeDeleteTemplateModal" v-if="showDeleteTemplateModal" modal-image="/Svgs/Overlays/illu_warning.svg">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{$t('Delete checklist template')}}
                </div>
                <div class="errorText">
                    {{ $t('Are you sure you want to delete the checklist template {0}?', [templateToDelete.name])}}
                </div>
                <div class="flex justify-between mt-6">
                    <button class="bg-artwork-navigation-background focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                            @click="deleteTemplate">
                        {{  $t('Delete') }}
                    </button>
                    <div class="flex my-auto">
                            <span @click="closeDeleteTemplateModal()"
                                  class="xsLight cursor-pointer">{{ $t('No, not really')}}</span>
                    </div>
                </div>
            </div>
        </BaseModal>
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

import {router, usePage} from "@inertiajs/vue3";
import {SearchIcon, DotsVerticalIcon, PencilAltIcon, TrashIcon, DuplicateIcon, XIcon} from "@heroicons/vue/outline";
import {CheckIcon, PlusSmIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import AppLayout from '@/Layouts/AppLayout.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {Link} from "@inertiajs/vue3";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import InputComponent from "@/Layouts/Components/InputComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
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
        UserPopoverTooltip,
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
                user_id: this.$page.props.user.id,
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

<style scoped>

</style>
