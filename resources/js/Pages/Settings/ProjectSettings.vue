<template>
    <app-layout title="Projekteinstellungen">
        <div class="max-w-screen-lg my-8 ml-20 mr-40">
            <div class="">
                <h2 class="font-bold font-lexend text-3xl my-2">Projekteinstellungen</h2>
                <div class="text-secondary tracking-tight leading-6 sub">
                    Definiere globale Einstellungen für Projekte.
                </div>
            </div>
            <div class="mt-16 max-w-2xl">
                <h2 class="font-bold font-lexend text-xl my-2">Genres & Bereiche</h2>
                <div class="text-secondary tracking-tight leading-6 sub">
                    Lege Genres und Bereiche fest. Projekte können diesen anschließend zugeordnet werden.
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <!-- Genres -->
                <div>
                    <div class="mt-8">
                        <div class="relative flex mr-4">
                            <input id="genre" v-model="genreInput" type="text"
                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                   placeholder="placeholder"/>
                            <label for="genre"
                                   class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Genre
                                eingeben</label>
                            <div class="m-2">
                                <button
                                    :class="[genreInput === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-secondaryHover']"
                                    @click="addGenre" :disabled="!genreInput">
                                    <CheckIcon class="h-5 w-5"></CheckIcon>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-wrap">
                        <span v-for="genre in genres.data"
                              class=" mt-4 mr-4 rounded-full font-bold text-primary">
                            <div class="flex">
                                <span class="">
                                {{ genre.name }}
                                    </span>
                                <button type="button" @click="deleteGenre(genre)">
                                <span class="sr-only">Genre entfernen</span>
                                <XCircleIcon class="ml-2 my-auto h-5 w-5 hover:text-error "/>
                            </button>
                            </div>

                        </span>
                        </div>
                    </div>
                </div>
                <!-- Sectors -->
                <div>
                    <div class="mt-8">
                        <div class="relative flex mr-4">
                            <input id="sector" v-model="sectorInput" type="text"
                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                   placeholder="placeholder"/>
                            <label for="genre"
                                   class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Bereich
                                eingeben</label>
                            <div class="m-2">
                                <button
                                    :class="[sectorInput === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-secondaryHover']"
                                    @click="addSector" :disabled="!sectorInput">
                                    <CheckIcon class="h-5 w-5"></CheckIcon>
                                </button>
                            </div>
                        </div>
                        <div class="flex flex-wrap">
                        <span v-for="sector in sectors.data"
                              class=" mt-4 mr-4 rounded-full font-bold text-primary">
                            <div class="flex">
                                <span class="">
                                {{ sector.name }}
                                    </span>
                                <button type="button" @click="deleteSector(sector)">
                                <span class="sr-only">Bereich entfernen</span>
                                <XCircleIcon class="ml-2 my-auto h-5 w-5 hover:text-error "/>
                            </button>
                            </div>

                        </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-16 max-w-2xl">
                <div class="flex">
                <h2 class="font-bold font-lexend text-xl my-2">Projektkategorien</h2>
                    <button @click="openAddCategoryModal" type="button"
                            class="flex my-auto ml-6 items-center border border-transparent rounded-full shadow-sm text-white bg-primary hover:bg-primaryHover focus:outline-none">
                        <PlusSmIcon class="h-5 w-5" aria-hidden="true"/>
                    </button>
                    <div v-if="$page.props.can.show_hints" class="flex mt-1">
                        <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                        <span class="font-nanum tracking-tight text-lg text-secondary ml-1 my-auto">Erstelle neue Kategorien</span>
                    </div>
                </div>
                <div class="text-secondary tracking-tight leading-6 sub">
                    Lege bis zu 10 Projektkategorien fest. Projekte können diesen anschließend zugeordnet werden.
                </div>
            </div>
            <ul role="list" class="mt-5 w-full">
                <li v-for="(category,index) in categories.data" :key="category.id"
                    class="flex justify-between">
                    <div class="flex">
                        <CategoryIconCollection :height="88" :width="88" :iconName="category.svg_name" />
                        <div class="ml-5 my-auto w-full justify-start mr-6">
                            <div class="flex my-auto">
                                <p class="text-lg subpixel-antialiased text-gray-900">{{ category.name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex">
                        <Menu as="div" class="my-auto relative">
                            <div class="flex">
                                <MenuButton
                                    class="flex">
                                    <DotsVerticalIcon class="mr-3 flex-shrink-0 h-6 w-6 text-gray-600 my-auto"
                                                      aria-hidden="true"/>
                                </MenuButton>
                                <div v-if="$page.props.can.show_hints && index === 0" class="absolute flex w-40 ml-6">
                                    <div>
                                        <SvgCollection svgName="arrowLeft" class="mt-1 ml-2"/>
                                    </div>
                                    <div class="flex">
                                        <span class="font-nanum ml-2 text-secondary tracking-tight tracking-tight text-lg">Bearbeite eine Kategorie</span>
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
                                            <a href="#" @click="openEditCategoryModal(category)"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <PencilAltIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                Kategorie bearbeiten
                                            </a>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <a href="#" @click="deleteCategory(category)"
                                               :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <TrashIcon
                                                    class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                    aria-hidden="true"/>
                                                Kategorie entfernen
                                            </a>
                                        </MenuItem>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>

                    </div>
                </li>
            </ul>


        </div>
        <!-- Kategorie erstellen Modal-->
        <jet-dialog-modal :show="addingCategory" @close="closeAddCategoryModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Neue Kategorie erstellen
                    </div>
                    <XIcon @click="closeAddCategoryModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="text-secondary subpixel-antialiased">
                        Erstelle eine neue Kategorie
                    </div>
                    <div class="mt-4">
                        <div class="flex">
                            <Menu as="div" class=" relative">
                                <div>
                                    <MenuButton class="flex items-center rounded-full focus:outline-none">
                                        <ChevronDownIcon v-if="categoryForm.svg_name === ''"
                                                         class="ml-1 flex-shrink-0 mt-1 h-16 w-16 flex my-auto items-center rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                                        <CategoryIconCollection v-else :height="64" :width="64" :iconName="categoryForm.svg_name" />
                                    </MenuButton>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="z-40 origin-top-right h-40 w-24 absolute right-0 mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none overflow-y-auto">
                                        <MenuItem v-for="item in iconMenuItems"  v-slot="{ active }">
                                            <div class="" @click="categoryForm.svg_name = item.iconName"
                                                  :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group relative flex  items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <CategoryIconCollection :height="64" :width="64" :iconName="item.iconName" />
                                            </div>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                            <div class="relative my-auto w-full ml-8 mr-12">
                                <input id="name" v-model="categoryForm.name" type="text" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                <label for="name" class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                            </div>
                        </div>
                        <div class="mt-12">
                        <button
                            :class="[this.categoryForm.name === '' || this.categoryForm.svg_name === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                            class="mt-8 inline-flex items-center px-20 py-3 border focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                            @click="addCategory"
                            :disabled="this.categoryForm.name === '' || this.categoryForm.svg_name === ''">
                            Kategorie erstellen
                        </button>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
        <!-- Kategorie bearbeiten Modal-->
        <jet-dialog-modal :show="editingCategory" @close="closeEditCategoryModal">
            <template #content>
                <div class="mx-4">
                    <div class="font-bold font-lexend text-primary tracking-wide text-2xl my-2">
                        Kategorie bearbeiten
                    </div>
                    <XIcon @click="closeEditCategoryModal" class="h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                           aria-hidden="true"/>
                    <div class="mt-12">
                        <div class="flex">
                            <Menu as="div" class=" relative">
                                <div>
                                    <MenuButton class="flex items-center rounded-full focus:outline-none">
                                        <ChevronDownIcon v-if="editCategoryForm.svg_name === ''"
                                                         class="ml-1 flex-shrink-0 mt-1 h-16 w-16 flex my-auto items-center rounded-full shadow-sm text-white bg-black"></ChevronDownIcon>
                                        <CategoryIconCollection :height="64" :width="64" v-else :iconName="editCategoryForm.svg_name" />
                                    </MenuButton>
                                </div>
                                <transition enter-active-class="transition ease-out duration-100"
                                            enter-from-class="transform opacity-0 scale-95"
                                            enter-to-class="transform opacity-100 scale-100"
                                            leave-active-class="transition ease-in duration-75"
                                            leave-from-class="transform opacity-100 scale-100"
                                            leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems
                                        class="z-40 origin-top-right h-40 w-24 absolute right-0 mt-2 shadow-lg py-1 bg-primary ring-1 ring-black ring-opacity-5 focus:outline-none overflow-y-auto">
                                        <MenuItem v-for="item in iconMenuItems"  v-slot="{ active }">
                                            <Link href="#" @click="editCategoryForm.svg_name = item.iconName"
                                                  :class="[active ? 'bg-primaryHover text-secondaryHover' : 'text-secondary', 'group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                <CategoryIconCollection :height="64" :width="64" :iconName="item.iconName" />
                                            </Link>
                                        </MenuItem>
                                    </MenuItems>
                                </transition>
                            </Menu>
                            <div class="relative my-auto w-full ml-8 mr-12">
                                <input id="editCategoryName" v-model="editCategoryForm.name" type="text" class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent" placeholder="placeholder" />
                                <label for="editCategoryName" class="absolute left-0 text-base -top-5 text-gray-600 text-sm -top-3.5 transition-all subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5 peer-focus:text-sm ">Name</label>
                            </div>
                        </div>
                        <div class="mt-12">
                            <button
                                :class="[this.editCategoryForm.name === '' || this.editCategoryForm.svg_name === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none']"
                                class="mt-8 inline-flex items-center px-20 py-3 border focus:outline-none border-transparent text-base font-bold text-xl uppercase shadow-sm text-secondaryHover"
                                @click="editCategory"
                                :disabled="this.editCategoryForm.name === '' || this.editCategoryForm.svg_name === ''">
                                Kategorie ändern
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </jet-dialog-modal>
    </app-layout>
</template>

<script>
import AppLayout from '@/Layouts/AppLayout.vue';
import {DotsVerticalIcon, TrashIcon, PencilAltIcon,XIcon} from "@heroicons/vue/outline"
import {CheckIcon, ChevronDownIcon, PlusSmIcon, XCircleIcon} from "@heroicons/vue/solid";
import SvgCollection from "@/Layouts/Components/SvgCollection";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import JetDialogModal from "@/Jetstream/DialogModal";
import CategoryIconCollection from "@/Layouts/Components/CategoryIconCollection";

const iconMenuItems = [
    {iconName: 'orange'},
    {iconName: 'turquoise'},
    {iconName: 'green'},
]

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
            sectorInput:'',
            addingCategory: false,
            editingCategory: false,
            categoryForm: this.$inertia.form({
                _method: 'POST',
                svg_name: '',
                name: ''
            }),
            editCategoryForm: this.$inertia.form({
                _method: 'PATCH',
                svg_name: '',
                name: '',
                id: null
            })
        }
    },
    methods: {
        openAddCategoryModal() {
            this.addingCategory = true;
        },
        openEditCategoryModal(category){
            this.editCategoryForm.svg_name = category.svg_name;
            this.editCategoryForm.name = category.name;
            this.editCategoryForm.id = category.id;
            this.editingCategory = true;
        },
        closeEditCategoryModal(){
            this.editCategoryForm.svg_name = "";
            this.editCategoryForm.name = "";
            this.editCategoryForm.id = null;
            this.editingCategory = false;
        },
        closeAddCategoryModal() {
            this.addingCategory = false;
            this.categoryForm.name = "";
            this.categoryForm.svg_name = "";
        },
        addCategory() {
            this.categoryForm.post(route('categories.store'), {})
            this.categoryForm.name = "";
            this.closeAddCategoryModal();
        },
        editCategory(){
          this.editCategoryForm.patch(route('categories.update',{category: this.editCategoryForm.id}));
          this.closeEditCategoryModal();
        },
        deleteGenre(genre) {
            this.$inertia.delete(`/genres/${genre.id}`);
        },
        addGenre() {
            this.$inertia.post(route('genres.store'), {name: this.genreInput});
            this.genreInput = '';
        },
        deleteSector(sector) {
            this.$inertia.delete(`/sectors/${sector.id}`);
        },
        addSector() {
            this.$inertia.post(route('sectors.store'), {name: this.sectorInput});
            this.sectorInput = '';
        },
        deleteCategory(category){
            this.$inertia.delete(`../categories/${category.id}`);
        }
    },
    setup(){
        return{
            iconMenuItems,
        }
    }
}
</script>
