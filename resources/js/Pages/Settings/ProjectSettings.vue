<template>
    <app-layout>
        <div class="max-w-screen-lg my-8 ml-14 mr-40">
            <div class="">
                <h2 class="headline1 my-6">Projekteinstellungen</h2>
                <div class="xsLight">
                    Definiere globale Einstellungen für Projekte.
                </div>
            </div>
            <div class="flex flex-wrap pb-8">

                <ProjectSettingsItem
                    title="Genres"
                    description="Lege Genres fest, denen Projekte später zugeordnet werden können."
                    input-label="Genre eingeben"
                    :items="genres"
                    @add="addGenre"
                    @openDeleteModal="openDeleteGenreModal"
                />

                <ProjectSettingsItem
                    title="Kategorien"
                    description="Lege Kategorien fest, denen Projekte später zugeordnet werden können."
                    input-label="Kategorie eingeben"
                    :items="categories"
                    @add="addCategory"
                    @openDeleteModal="openDeleteCategoryModal"
                />

                <ProjectSettingsItem
                    title="Bereiche"
                    description="Lege Bereiche fest, denen Projekte später zugeordnet werden können."
                    input-label="Bereich eingeben"
                    :items="sectors"
                    @add="addSector"
                    @openDeleteModal="openDeleteSectorModal"
                />

                <ProjectSettingsItem
                    title="Vertragsarten"
                    description="Lege Vertragsarten fest, denen Vertragsdokumente später zugeordnet werden können."
                    input-label="Vertragsart eingeben"
                    :items="contractTypes"
                    @add="addContractType"
                    @openDeleteModal="openDeleteContractTypeModal"
                />

                <ProjectSettingsItem
                    title="Unternehmensformen"
                    description="Lege Unternehmensformen fest, denen Vertragspartner später zugeordnet werden können."
                    input-label="Unternehmensform eingeben"
                    :items="companyTypes"
                    @add="addCompanyType"
                    @openDeleteModal="openDeleteCompanyTypeModal"
                />

                <ProjectSettingsItem
                    title="Verwertungsgesellschaften"
                    description="Lege Verwertungsgesellschaften fest, denen Verträge später zugeordnet werden können."
                    input-label="Verwertungsgesellschaft eingeben"
                    :items="collectingSocieties"
                    @add="addCollectingSociety"
                    @openDeleteModal="openDeleteCollectingSocietyModal"
                />

                <ProjectSettingsItem
                    title="Währungen"
                    description="Lege Währungen fest, denen Verträge später zugeordnet werden können."
                    input-label="Währung eingeben"
                    :items="currencies"
                    @add="addCurrency"
                    @openDeleteModal="openDeleteCurrencyModal"
                />

                <ProjectSettingsItem
                    title="Projektinformationen auf einen Blick"
                    description="Lege fest welche Daten für ein Projekt erhoben werden sollten. Die Tabelle kann anschließend in jedem Projekt ausgefüllt werden."
                    input-label="Überschrift eingeben"
                    :items="project_headlines"
                    item-style="list"
                    @add="addProjectHeadline"
                    @open-edit-modal="openEditProjectHeadlineModal"
                    @openDeleteModal="openDeleteProjectHeadlineModal"
                />

            </div>
        </div>

        <ProjectSettingsDeleteModal
            :show="deletingGenre"
            title="Genre löschen"
            :description="`Bist du sicher, dass du das Genre ${genreToDelete?.name} aus dem System löschen willst?`"
            @delete="deleteGenre"
            @closeModal="closeDeleteGenreModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingCategory"
            title="Kategorie löschen"
            :description="`Bist du sicher, dass du die Kategorie ${categoryToDelete?.name} aus dem System löschen willst?`"
            @delete="deleteCategory"
            @closeModal="closeDeleteCategoryModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingSector"
            title="Bereich löschen"
            :description="`Bist du sicher, dass du den Bereich ${sectorToDelete?.name} aus dem System löschen willst?`"
            @delete="deleteSector"
            @closeModal="closeDeleteSectorModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingContractType"
            title="Vertragsart löschen"
            :description="`Bist du sicher, dass du die Vertragsart ${contractTypeToDelete?.name} aus dem System löschen willst?`"
            @delete="deleteContractType"
            @closeModal="closeDeleteContractTypeModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingCompanyType"
            title="Unternehmensform löschen"
            :description="`Bist du sicher, dass du die Unternehmensform ${companyTypeToDelete?.name} aus dem System löschen willst?`"
            @delete="deleteCompanyType"
            @closeModal="closeDeleteCompanyTypeModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingCollectingSociety"
            title="Verwertungsgesellschaft löschen"
            :description="`Bist du sicher, dass du die Verwertungsgesellschaft ${collectingSocietyToDelete?.name} aus dem System löschen willst?`"
            @delete="deleteCollectingSociety"
            @closeModal="closeDeleteCollectingSocietyModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingCurrency"
            title="Währung löschen"
            :description="`Bist du sicher, dass du die Währung ${currencyToDelete?.name} aus dem System löschen willst?`"
            @delete="deleteCurrency"
            @closeModal="closeDeleteCurrencyModal"
        />

        <ProjectSettingsDeleteModal
            :show="deletingProjectHeadline"
            title="Überschrift löschen"
            :description="`Bist du sicher, dass du die Überschrift ${projectHeadlineToDelete?.name} aus dem System löschen willst?`"
            @delete="deleteProjectHeadline"
            @closeModal="closeDeleteProjectHeadlineModal"
        />

        <ProjectSettingsEditModal
            :show="editingProjectHeadline"
            title="Überschrift bearbeiten"
            :editedItem="projectHeadlineToEdit"
            :description="`Ändere den Titel der ausgewählten Überschrift`"
            @update="updateProjectHeadline"
            @closeModal="closeEditProjectHeadlineModal"
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
import CategoryIconCollection from "@/Layouts/Components/EventTypeIconCollection";
import ProjectSettingsItem from "@/Layouts/Components/ProjectSettingsItem.vue";
import ProjectSettingsDeleteModal from "@/Layouts/Components/ProjectSettingsDeleteModal.vue";
import ProjectSettingsEditModal from "@/Layouts/Components/ProjectSettingsEditModal.vue";

export default {
    components: {
        ProjectSettingsEditModal,
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
        CategoryIconCollection,
        ChevronDownIcon,
        DotsVerticalIcon,
        TrashIcon,
        PencilAltIcon,
        XIcon
    },
    props: ['genres', 'categories', 'sectors', 'contractTypes', 'companyTypes', 'collectingSocieties','currencies', 'project_headlines'],
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
            deletingProjectHeadline: false,
            projectHeadlineToDelete: null,
            projectHeadlineToEdit: null,
            editingProjectHeadline: false

        }
    },
    methods: {
        addGenre(genreInput) {
            if (genreInput !== '') {
                this.$inertia.post(route('genres.store'), {name: genreInput});
            }
        },
        deleteGenre() {
            this.$inertia.delete(`/genres/${this.genreToDelete.id}`);
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
                this.$inertia.post(route('categories.store'), {name: categoryInput});
            }
        },
        deleteCategory() {
            this.$inertia.delete(`../categories/${this.categoryToDelete.id}`);
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
                this.$inertia.post(route('sectors.store'), {name: sectorInput});
            }
        },
        deleteSector() {
            this.$inertia.delete(`/sectors/${this.sectorToDelete.id}`);
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
                this.$inertia.post(route('contract_types.store'), {name: contractTypeInput});
            }
        },
        deleteContractType() {
            this.$inertia.delete(`/contract_types/${this.contractTypeToDelete.id}`);
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
                this.$inertia.post(route('company_types.store'), {name: companyTypeInput});
            }
        },
        deleteCompanyType() {
            this.$inertia.delete(`/company_types/${this.companyTypeToDelete.id}`);
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
                this.$inertia.post(route('collecting_societies.store'), {name: collectingSocietyInput});
            }
        },
        deleteCollectingSociety() {
            this.$inertia.delete(`/collecting_societies/${this.collectingSocietyToDelete.id}`);
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
              this.$inertia.post(route('currencies.store'), {name: currencyInput});
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
            this.$inertia.delete(`/currencies/${this.currencyToDelete.id}`);
            this.closeDeleteCurrencyModal();
        },
        addProjectHeadline(headlineInput) {
            if(headlineInput !== ''){
                this.$inertia.post(route('project_headlines.store'), {name: headlineInput});
            }
        },
        updateProjectHeadline(headlineInput) {
            if(headlineInput !== ''){
                this.$inertia.patch(route('project_headlines.update', this.projectHeadlineToEdit.id), {name: headlineInput});
            }
            this.closeEditProjectHeadlineModal()
        },
        openDeleteProjectHeadlineModal(headline) {
            this.projectHeadlineToDelete = headline;
            this.deletingProjectHeadline = true;
        },
        closeDeleteProjectHeadlineModal() {
            this.deletingProjectHeadline = false;
            this.projectHeadlineToDelete = null;
        },
        openEditProjectHeadlineModal(headline) {
            this.projectHeadlineToEdit = headline;
            this.editingProjectHeadline = true;
        },
        closeEditProjectHeadlineModal() {
            this.editingProjectHeadline = false;
            this.projectHeadlineToEdit = null;
        },
        deleteProjectHeadline() {
            this.$inertia.delete(`/project_headlines/${this.projectHeadlineToDelete.id}`);
            this.closeDeleteProjectHeadlineModal();
        }
    },
    setup() {
        return {}
    }
}
</script>
