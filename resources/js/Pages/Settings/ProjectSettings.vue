<template>
    <app-layout :title="$t('Project Settings')">
        <div class="artwork-container">
            <div class="">
                <h2 class="headline1 my-6">{{$t('Project Settings')}}</h2>
                <div class="xsLight">
                    {{$t('Define global settings for projects.')}}
                </div>
            </div>

            <ProjectTabs />

            <div class="space-y-10 pb-8 ">
                <ProjectSettingsItem
                    :title="$t('Genres')"
                    :description="$t('Define genres that projects can be assigned to later.')"
                    :input-label="$t('Enter Genre')"
                    :items="genres"
                    @add="addGenre"
                    @openDeleteModal="openDeleteGenreModal"
                    @openEditModal="openEditGenreModal"
                />

                <ProjectSettingsItem
                    :title="$t('Categories')"
                    :description="$t('Define categories that projects can be assigned to later.')"
                    :input-label="$t('Enter Category')"
                    :items="categories"
                    @add="addCategory"
                    @openDeleteModal="openDeleteCategoryModal"
                    @openEditModal="openEditCategoryModal"
                />

                <ProjectSettingsItem
                    :title="$t('Sectors')"
                    :description="$t('Define sectors that projects can be assigned to later.')"
                    :input-label="$t('Enter Sector')"
                    :items="sectors"
                    @add="addSector"
                    @openDeleteModal="openDeleteSectorModal"
                    @openEditModal="openEditSectorModal"
                />

                <div>
                    <div class="">
                        <h2 class="headline2 my-2">{{ $t('Project Status') }}</h2>
                        <div class="xsLight">
                            {{ $t('Define project statuses to indicate the progress of a project. Users can then adjust their notifications based on these statuses.') }}
                        </div>
                    </div>
                    <div class="mt-8 flex">
                        <BaseCardButton text="Add Status" @click="openAddStateModal">
                        </BaseCardButton>

                    </div>
                    <div class="flex flex-wrap w-full max-w-xl mt-2">
                        <div class="flex flex-wrap w-full">
                            <ProjectStateTagComponent
                                v-for="item in states"
                                :key="item.id"
                                :item="item"
                                @update="updateState"
                                @delete="deleteState"
                            />
                        </div>
                    </div>
                </div>

                <ProjectSettingsItem
                    :title="$t('Contract Types')"
                    :description="$t('Define contract types that can be assigned to contracts later.')"
                    :input-label="$t('Enter Contract Type')"
                    :items="contractTypes"
                    @add="addContractType"
                    @openDeleteModal="openDeleteContractTypeModal"
                    @openEditModal="openEditContractTypeModal"
                />

                <ProjectSettingsItem
                    :title="$t('Company Types')"
                    :description="$t('Define company types that can be assigned to companies later.')"
                    input-label="Unternehmensform eingeben"
                    :items="companyTypes"
                    @add="addCompanyType"
                    @openDeleteModal="openDeleteCompanyTypeModal"
                    @openEditModal="openEditCompanyTypeModal"
                />

                <ProjectSettingsItem
                    :title="$t('Collecting Societies')"
                    :description="$t('Define collecting societies that can be assigned to projects later.')"
                    :input-label="$t('Enter Collecting Society')"
                    :items="collectingSocieties"
                    @add="addCollectingSociety"
                    @openDeleteModal="openDeleteCollectingSocietyModal"
                    @openEditModal="openEditCollectingSocietyModal"
                />

                <ProjectSettingsItem
                    :title="$t('Currencies')"
                    :description="$t('Define currencies that can be assigned to contracts later.')"
                    :input-label="$t('Enter Currency')"
                    :items="currencies"
                    @add="addCurrency"
                    @openDeleteModal="openDeleteCurrencyModal"
                    @openEditModal="openEditCurrencyModal"
                />
            </div>


            <TinyPageHeadline
                :title="$t('Settings for project creation')"
                :description="$t('Here you have the option of making settings for the creation of projects.')"
            />
            <transition
                enter-active-class="duration-300 ease-out"
                enter-from-class="transform opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="transform opacity-0"
            >
                <div class="my-3 text-xs bg-green-600 px-3 py-1.5 text-white rounded-lg" v-show="showSaveSuccess">
                    {{ $t('Saved. The changes have been successfully applied.') }}
                </div>
            </transition>

            <div class="grid gird-cols-1 md:grid-cols-3 gap-6 my-4 card white p-5">
                <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                        <input @change="updateCreateSettings" v-model="createSettingsForm.attributes" id="attributes" aria-describedby="attributes-description" name="attributes" type="checkbox" class="input-checklist" />
                    </div>
                    <div class="ml-3 text-sm leading-6">
                        <label for="attributes" class="font-medium text-gray-900">
                            {{ $t('Project Attributes') }}
                        </label>
                        <p id="attributes-description" class="text-gray-500 text-xs">
                            {{ $t('Should it be possible to add project attributes when creating a project?') }}
                        </p>
                    </div>
                </div>
                <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                        <input @change="updateCreateSettings" v-model="createSettingsForm.state" id="state" aria-describedby="state-description" name="state" type="checkbox" class="input-checklist" />
                    </div>
                    <div class="ml-3 text-sm leading-6">
                        <label for="state" class="font-medium text-gray-900">
                            {{ $t('Project status') }}
                        </label>
                        <p id="state-description" class="text-gray-500 text-xs">
                            {{ $t('Should it be possible to add a project status when creating a project?') }}
                        </p>
                    </div>
                </div>
                <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                        <input @change="updateCreateSettings" v-model="createSettingsForm.managers" id="managers" aria-describedby="managers-description" name="managers" type="checkbox" class="input-checklist" />
                    </div>
                    <div class="ml-3 text-sm leading-6">
                        <label for="managers" class="font-medium text-gray-900">
                            {{ $t('Project management') }}
                        </label>
                        <p id="managers-description" class="text-gray-500 text-xs">
                            {{ $t('Should it be possible to add the project management when creating a project?') }}
                        </p>
                    </div>
                </div>
                <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                        <input @change="updateCreateSettings" v-model="createSettingsForm.cost_center" id="cost_center" aria-describedby="cost_center-description" name="cost_center" type="checkbox" class="input-checklist" />
                    </div>
                    <div class="ml-3 text-sm leading-6">
                        <label for="cost_center" class="font-medium text-gray-900">
                            {{ $t('Cost bearer') }}
                        </label>
                        <p id="cost_center-description" class="text-gray-500 text-xs">
                           {{ $t('Do you want to add the cost unit of the project when creating it?') }}
                        </p>
                    </div>
                </div>
                <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                        <input @change="updateCreateSettings" v-model="createSettingsForm.budget_deadline" id="budget_deadline" aria-describedby="budget_deadline-description" name="budget_deadline" type="checkbox" class="input-checklist" />
                    </div>
                    <div class="ml-3 text-sm leading-6">
                        <label for="budget_deadline" class="font-medium text-gray-900">
                            {{ $t('Project Budget Deadline') }}
                        </label>
                        <p id="budget_deadline-description" class="text-gray-500 text-xs">
                            {{ $t('Would you like to enter the project budget deadline when you create a project?') }}
                        </p>
                    </div>
                </div>
                <div class="relative flex items-start">
                    <div class="flex h-6 items-center">
                        <input @change="updateCreateSettings" v-model="createSettingsForm.show_artists" id="show_artists" aria-describedby="show_artists-description" name="show_artists" type="checkbox" class="input-checklist"/>
                    </div>
                    <div class="ml-3 text-sm leading-6">
                        <label for="budget_deadline" class="font-medium text-gray-900">
                            {{ $t('Artists') }}
                        </label>
                        <p id="budget_deadline-description" class="text-gray-500 text-xs">
                            {{ $t('Would you like to add artists on project creation?') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <ProjectSettingsDeleteModal
            :show="deletingGenre"
            :title="$t('Delete Genre')"
            :description="$t(`Are you sure you want to delete the genre {genre} from the system?`,{ genre: genreToDelete?.name})"
            @delete="deleteGenre"
            @closeModal="closeDeleteGenreModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingState"
            :title="$t('Delete Status')"
            :description="$t('Are you sure you want to delete the status {status} from the system?',{ status: stateToDelete?.name})"
            @delete="deleteState"
            @closeModal="closeDeleteStateModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingCategory"
            :title="$t('Delete Category')"
            :description="$t('Are you sure you want to delete the category {category} from the system?',{ category: categoryToDelete?.name})"
            @delete="deleteCategory"
            @closeModal="closeDeleteCategoryModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingSector"
            :title="$t('Delete Sector')"
            :description="$t('Are you sure you want to delete the sector {sector} from the system?',{ sector: sectorToDelete?.name})"
            @delete="deleteSector"
            @closeModal="closeDeleteSectorModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingContractType"
            :title="$t('Delete Contract Type')"
            :description="$t('Are you sure you want to delete the contract type {contractType} from the system?',{ contractType: contractTypeToDelete?.name})"
            @delete="deleteContractType"
            @closeModal="closeDeleteContractTypeModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingCompanyType"
            :title="$t('Delete Company Type')"
            :description="$t('Are you sure you want to delete the company type {companyType} from the system?',{ companyType: companyTypeToDelete?.name})"
            @delete="deleteCompanyType"
            @closeModal="closeDeleteCompanyTypeModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingCollectingSociety"
            :title="$t('Delete collecting society')"
            :description="$t('Are you sure you want to delete the collecting society {collectingSociety} from the system?',{ collectingSociety: collectingSocietyToDelete?.name})"
            @delete="deleteCollectingSociety"
            @closeModal="closeDeleteCollectingSocietyModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingCurrency"
            :title="$t('Delete Currency')"
            :description="$t('Are you sure you want to delete the currency {currency} from the system?',{ currency: currencyToDelete?.name})"
            @delete="deleteCurrency"
            @closeModal="closeDeleteCurrencyModal"
        />

        <ProjectStateModal
            :show="showStateModal"
            :title="stateToEdit ? $t('Edit Status') : $t('Add Status')"
            :state="stateToEdit"
            @close="closeStateModal"
            @submit="submitState"
        />
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import {DotsVerticalIcon, PencilAltIcon, TrashIcon, XIcon} from "@heroicons/vue/outline"
import {CheckIcon, ChevronDownIcon, PlusSmIcon, XCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import ProjectSettingsItem from "@/Layouts/Components/ProjectSettingsItem.vue";
import ProjectSettingsDeleteModal from "@/Layouts/Components/ProjectSettingsDeleteModal.vue";
import ProjectStateModal from "@/Layouts/Components/ProjectStateModal.vue";
import ProjectStateTagComponent from "@/Layouts/Components/ProjectStateTagComponent.vue";
import Permissions from "@/Mixins/Permissions.vue";
import ProjectTabs from "@/Pages/Settings/Components/ProjectTabs.vue";
import TinyPageHeadline from "@/Components/Headlines/TinyPageHeadline.vue";
import {useForm} from "@inertiajs/vue3";
import BaseCardButton from "@/Artwork/Buttons/BaseCardButton.vue";

export default {
    mixins: [Permissions],
    components: {
        BaseCardButton,
        TinyPageHeadline,
        ProjectTabs,
        ProjectStateModal,
        ProjectStateTagComponent,
        ProjectSettingsDeleteModal,
        ProjectSettingsItem,
        AppLayout,
        XCircleIcon,
        PlusSmIcon,
        SvgCollection,
        CheckIcon,
        Menu,
        MenuButton,
        MenuItem,
        MenuItems,
        JetDialogModal,
        ChevronDownIcon,
        DotsVerticalIcon,
        TrashIcon,
        PencilAltIcon,
        XIcon
    },
    props: [
        'genres',
        'categories',
        'sectors',
        'contractTypes',
        'companyTypes',
        'collectingSocieties',
        'currencies',
        'states',
        'createSettings'
    ],
    data() {
        return {
            genreToDelete: null,
            deletingGenre: false,
            categoryToDelete: null,
            deletingCategory: false,
            sectorToDelete: null,
            deletingSector: false,
            deletingContractType: false,
            contractTypeToDelete: null,
            deletingCompanyType: false,
            companyTypeToDelete: null,
            deletingCollectingSociety: false,
            collectingSocietyToDelete: null,
            deletingCurrency: false,
            currencyToDelete: null,
            deletingState: false,
            stateToDelete: null,
            showStateModal: false,
            stateToEdit: null,
            createSettingsForm: useForm({
                attributes: this.createSettings.attributes,
                state: this.createSettings.state,
                managers: this.createSettings.managers,
                cost_center: this.createSettings.cost_center,
                budget_deadline: this.createSettings.budget_deadline,
                show_artists: this.createSettings.show_artists
            }),
            showSaveSuccess: false
        }
    },
    methods: {
        openEditGenreModal(genre) {
            this.$inertia.patch(route('genres.update', genre.id), {name: genre.name, color: genre.color}, { preserveScroll: true});
        },
        openEditCategoryModal(category) {
            this.$inertia.patch(route('categories.update', category.id), {name: category.name, color: category.color}, { preserveScroll: true});
        },
        openEditSectorModal(sector) {
            this.$inertia.patch(route('sectors.update', sector.id), {name: sector.name, color: sector.color}, { preserveScroll: true});
        },
        openEditContractTypeModal(contractType) {
            this.$inertia.patch(route('contract_types.update', contractType.id), {name: contractType.name, color: contractType.color}, { preserveScroll: true});
        },
        openEditCompanyTypeModal(companyType) {
            this.$inertia.patch(route('company_types.update', companyType.id), {name: companyType.name, color: companyType.color}, { preserveScroll: true});
        },
        openEditCollectingSocietyModal(collectingSociety) {
            this.$inertia.patch(route('collecting_societies.update', collectingSociety.id), {name: collectingSociety.name, color: collectingSociety.color}, { preserveScroll: true});
        },
        openEditCurrencyModal(currency) {
            this.$inertia.patch(route('currencies.update', currency.id), {name: currency.name, color: currency.color}, { preserveScroll: true});
        },
        openAddStateModal() {
            this.stateToEdit = null;
            this.showStateModal = true;
        },

        openEditStateModal(state) {
            this.stateToEdit = state;
            this.showStateModal = true;
        },

        closeStateModal() {
            this.showStateModal = false;
            this.stateToEdit = null;
        },

        submitState(formData) {
            if (this.stateToEdit) {
                // Update existing state
                this.$inertia.patch(
                    route('state.update', {projectStates: this.stateToEdit.id}),
                    {
                        name: formData.name,
                        color: formData.color,
                        is_planning: formData.is_planning
                    },
                    { preserveScroll: true }
                );
            } else {
                // Create new state
                this.$inertia.post(
                    route('state.store'),
                    {
                        name: formData.name,
                        color: formData.color,
                        is_planning: formData.is_planning
                    },
                    { preserveScroll: true }
                );
            }
        },

        updateState(updatedState) {
            this.$inertia.patch(
                route('state.update', {projectStates: updatedState.id}),
                {
                    name: updatedState.name,
                    color: updatedState.color,
                    is_planning: updatedState.is_planning
                },
                { preserveScroll: true }
            );
        },

        addGenre(genreInput, color) {
            if (genreInput !== '') {
                this.$inertia.post(route('genres.store'), {name: genreInput, color: color}, { preserveScroll: true});
            }
        },
        deleteGenre() {
            this.$inertia.delete(`/genres/${this.genreToDelete.id}`, { preserveScroll: true});
            this.closeDeleteGenreModal();
        },
        openDeleteGenreModal(genre) {
            this.genreToDelete = genre;
            this.deletingGenre = true;
        },
        closeDeleteGenreModal() {
            this.deletingGenre = false;
            this.genreToDelete = null;
        },

        addCategory(categoryInput, color) {
            if (categoryInput !== '') {
                this.$inertia.post(route('categories.store'), {name: categoryInput, color: color}, { preserveScroll: true});
            }
        },
        deleteCategory() {
            this.$inertia.delete(`../categories/${this.categoryToDelete.id}`, { preserveScroll: true});
            this.closeDeleteCategoryModal();
        },
        openDeleteCategoryModal(category) {
            this.categoryToDelete = category;
            this.deletingCategory = true;
        },
        closeDeleteCategoryModal() {
            this.deletingCategory = false;
            this.categoryToDelete = null;
        },

        addSector(sectorInput, color) {
            if (sectorInput !== '') {
                this.$inertia.post(route('sectors.store'), {name: sectorInput, color: color}, { preserveScroll: true});
            }
        },
        deleteSector() {
            this.$inertia.delete(`/sectors/${this.sectorToDelete.id}`, { preserveScroll: true});
            this.closeDeleteSectorModal();
        },
        openDeleteSectorModal(sector) {
            this.sectorToDelete = sector;
            this.deletingSector = true;
        },
        closeDeleteSectorModal() {
            this.deletingSector = false;
            this.sectorToDelete = null;
        },

        addContractType(contractTypeInput,color) {
            if (contractTypeInput !== '') {
                this.$inertia.post(route('contract_types.store'), {name: contractTypeInput, color: color}, { preserveScroll: true});
            }
        },
        deleteContractType() {
            this.$inertia.delete(`/contract_types/${this.contractTypeToDelete.id}`, { preserveScroll: true});
            this.closeDeleteContractTypeModal();
        },
        openDeleteContractTypeModal(contractType) {
            this.contractTypeToDelete = contractType;
            this.deletingContractType = true
        },
        closeDeleteContractTypeModal() {
            this.deletingContractType = false
            this.contractTypeToDelete = null
        },

        addCompanyType(companyTypeInput, color) {
            if (companyTypeInput !== '') {
                this.$inertia.post(route('company_types.store'), {name: companyTypeInput, color: color}, { preserveScroll: true});
            }
        },
        deleteCompanyType() {
            this.$inertia.delete(`/company_types/${this.companyTypeToDelete.id}`, { preserveScroll: true});
            this.closeDeleteCompanyTypeModal();
        },
        openDeleteCompanyTypeModal(companyType) {
            this.companyTypeToDelete = companyType;
            this.deletingCompanyType = true
        },
        closeDeleteCompanyTypeModal() {
            this.deletingCompanyType = false
            this.companyTypeToDelete = null
        },

        addCollectingSociety(collectingSocietyInput, color) {
            if (collectingSocietyInput !== '') {
                this.$inertia.post(route('collecting_societies.store'), {name: collectingSocietyInput, color: color}, { preserveScroll: true});
            }
        },
        deleteCollectingSociety() {
            this.$inertia.delete(`/collecting_societies/${this.collectingSocietyToDelete.id}`, { preserveScroll: true});
            this.closeDeleteCollectingSocietyModal();
        },
        openDeleteCollectingSocietyModal(collectingSociety) {
            this.collectingSocietyToDelete = collectingSociety;
            this.deletingCollectingSociety = true
        },
        closeDeleteCollectingSocietyModal() {
            this.deletingCollectingSociety = false
            this.collectingSocietyToDelete = null
        },
        addCurrency(currencyInput, color){
          if(currencyInput !== ''){
              this.$inertia.post(route('currencies.store'), {name: currencyInput, color: color}, { preserveScroll: true});
          }
        },
        openDeleteCurrencyModal(currency){
            this.currencyToDelete = currency;
            this.deletingCurrency = true;
        },
        closeDeleteCurrencyModal(){
          this.deletingCurrency = false;
          this.currencyToDelete = null;
        },
        deleteCurrency() {
            this.$inertia.delete(`/currencies/${this.currencyToDelete.id}`, { preserveScroll: true});
            this.closeDeleteCurrencyModal();
        },
        deleteState(state){
            this.stateToDelete = state;
            this.$inertia.delete(route('state.delete', this.stateToDelete.id), { preserveScroll: true})
            this.closeDeleteStateModal()
        },
        closeDeleteStateModal(){
            this.stateToDelete = null;
            this.deletingState = false
        },
        updateCreateSettings(){
            if (this.createSettingsForm.isDirty) {
                this.createSettingsForm.patch(route('project_settings.update'), {
                    preserveScroll: true,
                    onFinish: () => {
                        this.showSaveSuccess = true
                        setTimeout(() => {
                            this.showSaveSuccess = false
                        }, 2500)
                    }
                })
            }
        }
    },
    setup() {
        return {}
    },

}
</script>
