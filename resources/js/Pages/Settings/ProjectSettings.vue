<template>
    <app-layout>
        <div class="max-w-screen-lg my-8 ml-14 mr-40">
            <div class="">
                <h2 class="headline1 my-6">{{$t('Project Settings')}}</h2>
                <div class="xsLight">
                    {{$t('Define global settings for projects.')}}
                </div>
            </div>

            <ProjectTabs />

            <div class="flex flex-wrap pb-8">

                <ProjectSettingsItem
                    :title="$t('Genres')"
                    :description="$t('Define genres that projects can be assigned to later.')"
                    :input-label="$t('Enter Genre')"
                    :items="genres"
                    @add="addGenre"
                    @openDeleteModal="openDeleteGenreModal"
                />

                <ProjectSettingsItem
                    :title="$t('Categories')"
                    :description="$t('Define categories that projects can be assigned to later.')"
                    :input-label="$t('Enter Category')"
                    :items="categories"
                    @add="addCategory"
                    @openDeleteModal="openDeleteCategoryModal"
                />

                <ProjectSettingsItem
                    :title="$t('Sectors')"
                    :description="$t('Define sectors that projects can be assigned to later.')"
                    :input-label="$t('Enter Sector')"
                    :items="sectors"
                    @add="addSector"
                    @openDeleteModal="openDeleteSectorModal"
                />

                <ProjectSettingState
                    :title="$t('Project Status')"
                    :description="$t('Define project statuses to indicate the progress of a project. Users can then adjust their notifications based on these statuses.')"
                    :input-label="$t('Enter Status')"
                    :items="states"
                    @add="addState"
                    @openDeleteModal="openDeleteStateModal"
                />

                <ProjectSettingsItem
                    :title="$t('Contract Types')"
                    :description="$t('Define contract types that can be assigned to contracts later.')"
                    :input-label="$t('Enter Contract Type')"
                    :items="contractTypes"
                    @add="addContractType"
                    @openDeleteModal="openDeleteContractTypeModal"
                />

                <ProjectSettingsItem
                    :title="$t('Company Types')"
                    :description="$t('Define company types that can be assigned to companies later.')"
                    input-label="Unternehmensform eingeben"
                    :items="companyTypes"
                    @add="addCompanyType"
                    @openDeleteModal="openDeleteCompanyTypeModal"
                />

                <ProjectSettingsItem
                    :title="$t('Collecting Societies')"
                    :description="$t('Define collecting societies that can be assigned to projects later.')"
                    :input-label="$t('Enter Collecting Society')"
                    :items="collectingSocieties"
                    @add="addCollectingSociety"
                    @openDeleteModal="openDeleteCollectingSocietyModal"
                />

                <ProjectSettingsItem
                    :title="$t('Currencies')"
                    :description="$t('Define currencies that can be assigned to contracts later.')"
                    :input-label="$t('Enter Currency')"
                    :items="currencies"
                    @add="addCurrency"
                    @openDeleteModal="openDeleteCurrencyModal"
                />
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
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import {DotsVerticalIcon, TrashIcon, PencilAltIcon, XIcon} from "@heroicons/vue/outline"
import {CheckIcon, ChevronDownIcon, PlusSmIcon, XCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal";
import ProjectSettingsItem from "@/Layouts/Components/ProjectSettingsItem.vue";
import ProjectSettingsDeleteModal from "@/Layouts/Components/ProjectSettingsDeleteModal.vue";
import ProjectSettingsEditModal from "@/Layouts/Components/ProjectSettingsEditModal.vue";
import ProjectSettingState from "@/Layouts/Components/ProjectSettingState.vue";
import Permissions from "@/mixins/Permissions.vue";
import ProjectTabs from "@/Pages/Settings/Components/ProjectTabs.vue";

export default {
    mixins: [Permissions],
    components: {
        ProjectTabs,
        ProjectSettingsEditModal,
        ProjectSettingState,
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
        'states'
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
            stateToDelete: null
        }
    },
    methods: {
        addGenre(genreInput) {
            if (genreInput !== '') {
                this.$inertia.post(route('genres.store'), {name: genreInput}, { preserveScroll: true});
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

        addCategory(categoryInput) {
            if (categoryInput !== '') {
                this.$inertia.post(route('categories.store'), {name: categoryInput}, { preserveScroll: true});
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

        addSector(sectorInput) {
            if (sectorInput !== '') {
                this.$inertia.post(route('sectors.store'), {name: sectorInput}, { preserveScroll: true});
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

        addContractType(contractTypeInput) {
            if (contractTypeInput !== '') {
                this.$inertia.post(route('contract_types.store'), {name: contractTypeInput}, { preserveScroll: true});
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

        addCompanyType(companyTypeInput) {
            if (companyTypeInput !== '') {
                this.$inertia.post(route('company_types.store'), {name: companyTypeInput}, { preserveScroll: true});
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

        addCollectingSociety(collectingSocietyInput) {
            if (collectingSocietyInput !== '') {
                this.$inertia.post(route('collecting_societies.store'), {name: collectingSocietyInput}, { preserveScroll: true});
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
        addCurrency(currencyInput){
          if(currencyInput !== ''){
              this.$inertia.post(route('currencies.store'), {name: currencyInput}, { preserveScroll: true});
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
        addState(stateInput, stateColor){
            this.$inertia.post(route('state.store'), {
                name: stateInput,
                color: stateColor
            }, { preserveScroll: true})
        },
        openDeleteStateModal(state){
            this.stateToDelete = state;
            this.deletingState = true
        },
        deleteState(){
            this.$inertia.delete(route('state.delete', this.stateToDelete.id), { preserveScroll: true})
            this.closeDeleteStateModal()
        },
        closeDeleteStateModal(){
            this.stateToDelete = null;
            this.deletingState = false
        }
    },
    setup() {
        return {}
    }
}
</script>
