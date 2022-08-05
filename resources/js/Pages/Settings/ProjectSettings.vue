<template>
    <app-layout title="Projekteinstellungen">
        <div class="max-w-screen-lg my-8 ml-20 mr-40">
            <div class="">
                <h2 class="font-bold font-lexend text-3xl my-2">Projekteinstellungen</h2>
                <div class="text-secondary tracking-tight leading-6 sub">
                    Definiere globale Einstellungen für Projekte.
                </div>
            </div>
            <div class="flex flex-wrap">
                <!-- Genres -->
                <div class="mt-16 max-w-2xl">
                    <h2 class="font-bold font-lexend text-xl my-2">Genres</h2>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Lege Genres fest, denen Projekte später zugeordnet werden können.
                    </div>
                </div>
                <div class="mt-8 flex w-full flex-wrap">
                    <div class="relative flex max-w-lg w-full">
                        <input id="genre" v-model="genreInput" type="text"
                               class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="genre"
                               class="absolute left-0 -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Genre
                            eingeben</label>
                        <div class="m-2 -ml-8 -mt-1">
                            <button
                                :class="[genreInput === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-secondaryHover']"
                                @click="addGenre" :disabled="!genreInput">
                                <CheckIcon class="h-5 w-5"></CheckIcon>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap w-full max-w-xl">
                        <span v-for="genre in genres.data"
                              class=" mt-4 mr-4 rounded-full font-bold text-primary">
                            <div class="flex">
                                <span class="">
                                {{ genre.name }}
                                    </span>
                                <button type="button" @click="openDeleteGenreModal(genre)">
                                <span class="sr-only">Genre entfernen</span>
                                <XCircleIcon class="ml-2 my-auto h-5 w-5 hover:text-error "/>
                            </button>
                            </div>

                        </span>
                    </div>
                </div>
                <!-- Kategorien -->
                <div class="mt-16 max-w-2xl">
                    <h2 class="font-bold font-lexend text-xl my-2">Kategorien</h2>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Lege Kategorien fest, denen Projekte später zugeordnet werden können.
                    </div>
                </div>
                <div class="mt-8 flex w-full flex-wrap">
                    <div class="relative flex max-w-lg w-full">
                        <input id="category" v-model="categoryInput" type="text"
                               class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="genre"
                               class="absolute left-0 -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Kategorie
                            eingeben</label>
                        <div class="m-2 -ml-8 -mt-1">
                            <button
                                :class="[categoryInput === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-secondaryHover']"
                                @click="addCategory" :disabled="!categoryInput">
                                <CheckIcon class="h-5 w-5"></CheckIcon>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap w-full max-w-xl">
                        <span v-for="category in categories.data"
                              class=" mt-4 mr-4 rounded-full font-bold text-primary">
                            <div class="flex">
                                <span class="">
                                {{ category.name }}
                                    </span>
                                <button type="button" @click="openDeleteCategoryModal(category)">
                                <span class="sr-only">Bereich entfernen</span>
                                <XCircleIcon class="ml-2 my-auto h-5 w-5 hover:text-error "/>
                            </button>
                            </div>

                        </span>
                    </div>
                </div>
                <!-- Bereiche -->
                <div class="mt-16 max-w-2xl">
                    <h2 class="font-bold font-lexend text-xl my-2">Bereiche</h2>
                    <div class="text-secondary tracking-tight leading-6 sub">
                        Lege Bereiche fest, denen Projekte später zugeordnet werden können.
                    </div>
                </div>
                <div class="mt-8 flex w-full flex-wrap">
                    <div class="relative flex max-w-lg w-full">
                        <input id="sector" v-model="sectorInput" type="text"
                               class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                               placeholder="placeholder"/>
                        <label for="genre"
                               class="absolute left-0 -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Bereich
                            eingeben</label>
                        <div class="m-2 -ml-8 -mt-1">
                            <button
                                :class="[sectorInput === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-secondaryHover']"
                                @click="addSector" :disabled="!sectorInput">
                                <CheckIcon class="h-5 w-5"></CheckIcon>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-wrap w-full max-w-xl">
                        <span v-for="sector in sectors.data"
                              class=" mt-4 mr-4 rounded-full font-bold text-primary">
                            <div class="flex">
                                <span class="">
                                {{ sector.name }}
                                    </span>
                                <button type="button" @click="openDeleteSectorModal(sector)">
                                <span class="sr-only">Bereich entfernen</span>
                                <XCircleIcon class="ml-2 my-auto h-5 w-5 hover:text-error "/>
                            </button>
                            </div>

                        </span>
                    </div>
                </div>
            </div>
        </div>
        <!-- Bereich löschen Modal -->
        <jet-dialog-modal :show="deletingSector" @close="closeDeleteSectorModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-primary text-2xl my-2">
                        Bereich löschen
                    </div>
                    <XIcon @click="closeDeleteSectorModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error">
                        Bist du sicher, dass du den Bereich {{ sectorToDelete.name }} aus dem System löschen willst?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteSector">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteSectorModal"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Genre löschen Modal -->
        <jet-dialog-modal :show="deletingGenre" @close="closeDeleteGenreModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-primary text-2xl my-2">
                        Genre löschen
                    </div>
                    <XIcon @click="closeDeleteGenreModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error">
                        Bist du sicher, dass du das Genre {{ genreToDelete.name }} aus dem System löschen willst?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteGenre">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteGenreModal"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Kategorie löschen Modal -->
        <jet-dialog-modal :show="deletingCategory" @close="closeDeleteCategoryModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold text-primary text-2xl my-2">
                        Kategorie löschen
                    </div>
                    <XIcon @click="closeDeleteCategoryModal"
                           class="h-5 w-5 right-0 top-0 mr-5 mt-8 flex text-secondary absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-error">
                        Bist du sicher, dass du die Kategorie {{ categoryToDelete.name }} aus dem System löschen willst?
                    </div>
                    <div class="flex justify-between mt-6">
                        <button class="bg-primary focus:outline-none my-auto inline-flex items-center px-20 py-3 border border-transparent
                            text-base font-bold uppercase shadow-sm text-secondaryHover"
                                @click="deleteCategory">
                            Löschen
                        </button>
                        <div class="flex my-auto">
                            <span @click="closeDeleteCategoryModal"
                                  class="text-secondary subpixel-antialiased cursor-pointer">Nein, doch nicht</span>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
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

export default {
    components: {
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
    props: ['genres', 'categories', 'sectors'],
    data() {
        return {
            genreInput: '',
            sectorInput: '',
            categoryInput: '',
            addingCategory: false,
            editingCategory: false,
            deletingSector: false,
            deletingCategory: false,
            deletingGenre: false,
            categoryToDelete: null,
            genreToDelete: null,
            sectorToDelete: null,
        }
    },
    methods: {
        addCategory() {
            this.$inertia.post(route('categories.store'), {name: this.categoryInput});
            this.categoryInput = "";
        },
        openDeleteGenreModal(genre) {
            this.genreToDelete = genre;
            this.deletingGenre = true;
        },
        closeDeleteGenreModal() {
            this.deletingGenre = false;
            this.genreToDelete = null;
        },
        deleteGenre() {
            this.$inertia.delete(`/genres/${this.genreToDelete.id}`);
            this.closeDeleteGenreModal();
        },
        addGenre() {
            this.$inertia.post(route('genres.store'), {name: this.genreInput});
            this.genreInput = '';
        },
        openDeleteSectorModal(sector) {
            this.sectorToDelete = sector;
            this.deletingSector = true;
        },
        closeDeleteSectorModal() {
            this.deletingSector = false;
            this.sectorToDelete = null;
        },
        deleteSector() {
            this.$inertia.delete(`/sectors/${this.sectorToDelete.id}`);
            this.closeDeleteSectorModal();
        },
        addSector() {
            this.$inertia.post(route('sectors.store'), {name: this.sectorInput});
            this.sectorInput = '';
        },
        openDeleteCategoryModal(category) {
            this.categoryToDelete = category;
            this.deletingCategory = true;
        },
        closeDeleteCategoryModal() {
            this.deletingCategory = false;
            this.categoryToDelete = null;
        },
        deleteCategory() {
            this.$inertia.delete(`../categories/${this.categoryToDelete.id}`);
            this.closeDeleteCategoryModal();
        }
    },
    setup() {
        return {}
    }
}
</script>
