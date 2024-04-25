<template>
    <app-layout>
        <div class="max-w-screen-lg ml-14 mr-40">
            <div class="flex flex-1 flex-wrap">
                <div class="flex justify-between w-full">
                    <div class="flex gap-x-4">
                        <h2 class="headline1 flex">{{ $t('Checklist templates')}}</h2>
                        <Link class="-mt-1" :href="route('checklist_templates.create')">
                            <AddButtonSmall :text="$t('New template')" />
                        </Link>
                        <div v-if="this.$page.props.show_hints" class="flex mt-1">
                            <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                            <span
                                class="ml-1 mt-2 hind">{{$t('Create new checklist templates')}}</span>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <div v-if="!showSearchbar" @click="this.showSearchbar = !this.showSearchbar"
                             class="cursor-pointer inset-y-0 mr-12">
                            <IconSearch stroke-width="1.5" class="h-5 w-5" aria-hidden="true"/>
                        </div>
                        <div v-else class="flex items-center w-full w-64 mr-12">
                            <inputComponent v-model="template_query" :placeholder="$t('Search for projects')" />
                            <IconX stroke-width="1.5" class="ml-2 cursor-pointer h-5 w-5" @click="closeSearchbar()"/>
                        </div>
                    </div>
                </div>
            </div>
            <ul role="list" class="mt-6 mb-32 w-full">
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
                            <Menu as="div" class="my-auto relative">
                                <div class="flex">
                                    <MenuButton
                                        class="flex">
                                        <IconDotsVertical stroke-width="1.5"
                                            class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                            aria-hidden="true"/>
                                    </MenuButton>
                                    <div v-if="this.$page.props.show_hints && index === 0"
                                         class="absolute flex w-40 ml-6">
                                        <div>
                                            <SvgCollection svgName="arrowLeft" class="mt-1 ml-1"/>
                                        </div>
                                        <div class="flex">
                                                    <span
                                                        class="ml-2 hind mt-2">{{ $t('Edit a template')}}</span>
                                        </div>
                                    </div>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                        <div class="py-1">
                                            <MenuItem v-slot="{ active }">
                                                <a :href="getEditHref(template)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased capitalize']">
                                                    <IconEdit  stroke-width="1.5"
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    {{ $t('edit')}}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a href="#" @click="duplicateTemplate(template)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <IconCopy  stroke-width="1.5"
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    {{$t('Duplicate')}}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a href="#" @click="openDeleteTemplateModal(template)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <IconTrash stroke-width="1.5"
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    {{ $t('Delete') }}
                                                </a>
                                            </MenuItem>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>
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
                            <Menu as="div" class="my-auto relative">
                                <div class="flex">
                                    <MenuButton
                                        class="flex">
                                        <IconDotsVertical stroke-width="1.5"
                                            class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                            aria-hidden="true"/>
                                    </MenuButton>
                                    <div v-if="this.$page.props.show_hints && index === 0"
                                         class="absolute flex w-40 ml-6">
                                        <div>
                                            <SvgCollection svgName="arrowLeft" class="mt-1 ml-1"/>
                                        </div>
                                        <div class="flex">
                                                    <span
                                                        class="ml-2 hind mt-2">{{ $t('Edit a template')}}</span>
                                        </div>
                                    </div>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="origin-top-right absolute right-0 mr-4 mt-2 w-72 shadow-lg bg-zinc-800 ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                        <div class="py-1">
                                            <MenuItem v-slot="{ active }">
                                                <a :href="getEditHref(template)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased capitalize']">
                                                    <IconEdit stroke-width="1.5"
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    {{ $t('edit')}}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a href="#" @click="duplicateTemplate(template)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <IconCopy stroke-width="1.5"
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    {{$t('Duplicate')}}
                                                </a>
                                            </MenuItem>
                                            <MenuItem v-slot="{ active }">
                                                <a href="#" @click="openDeleteTemplateModal(template)"
                                                   :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                    <IconTrash stroke-width="1.5"
                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                        aria-hidden="true"/>
                                                    {{ $t('Delete') }}
                                                </a>
                                            </MenuItem>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div>
                    </div>
                </li>
            </ul>
        </div>
        <!-- Delete Project Modal -->
        <jet-dialog-modal :show="showDeleteTemplateModal" @close="closeDeleteTemplateModal">
            <template #content>
                <img src="/Svgs/Overlays/illu_warning.svg" class="-ml-6 -mt-8 mb-4"/>
                <div class="mx-4">
                    <div class="headline1 my-2">
                        {{$t('Delete checklist template')}}
                    </div>
                    <XIcon @click="closeDeleteTemplateModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="errorText">
                        {{ $t('Are you sure you want to delete the checklist template {0}?', [templateToDelete.name])}}
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
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

            </template>

        </jet-dialog-modal>
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

import  {Inertia} from "@inertiajs/inertia";
import {SearchIcon, DotsVerticalIcon, PencilAltIcon, TrashIcon, DuplicateIcon, XIcon} from "@heroicons/vue/outline";
import {CheckIcon, PlusSmIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import AppLayout from '@/Layouts/AppLayout.vue'
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {Link} from "@inertiajs/inertia-vue3";
import JetDialogModal from "@/Jetstream/DialogModal";
import UserTooltip from "@/Layouts/Components/UserTooltip";
import InputComponent from "@/Layouts/Components/InputComponent";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import SuccessModal from "@/Layouts/Components/General/SuccessModal.vue";
import AddButtonSmall from "@/Layouts/Components/General/Buttons/AddButtonSmall.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default {
    mixins: [Permissions, IconLib],
    name: "Checklist Management",
    props: ['checklist_templates'],
    components: {
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
            Inertia.delete(`/checklist_templates/${this.templateToDelete.id}`);
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
