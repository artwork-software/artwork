<template>
    <Menu as="span" class="relative">
        <MenuButton @click="attributesOpened = true" class="w-full">
            <div class="border-2 border-gray-300 w-full cursor-pointer truncate flex p-4 mt-4">
                <div class="flex-grow xsLight text-left subpixel-antialiased">
                    Projekteigenschaften wählen
                </div>
                <ChevronDownIcon class="h-5 w-5 text-primary" aria-hidden="true"/>
            </div>
        </MenuButton>

        <transition
            enter-active-class="transition duration-50 ease-out"
            enter-from-class="transform scale-100 opacity-100"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <MenuItems
                class="absolute right-0 mt-2 w-full origin-top-right divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                <div class="rounded-2xl bg-primary border-none mt-2">

                    <!-- Project Categories Section -->
                    <BaseFilterDisclosure title="Kategorie">

                        <div v-if="categories.length > 0"
                             v-for="category in categories"
                             :key="category.id"
                             class="flex w-full mb-2">
                            <input type="checkbox"
                                   v-model="category.included"
                                   @change="$emit('updateProjectCategory', category)"
                                   :value="category.id"
                                   class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                            <p :class="[projectCategories.includes(category.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                {{ category.name }}
                            </p>
                        </div>
                        <div v-else class="text-secondary">Noch keine Projektkategorien angelegt</div>
                    </BaseFilterDisclosure>

                    <hr class="border-gray-500 rounded-full mt-2 mb-2">

                    <!-- Project Genres Section -->
                    <BaseFilterDisclosure title="Genre">

                        <div v-if="genres.length > 0"
                             v-for="genre in genres"
                             :key="genre.id"
                             class="flex w-full mb-2">
                            <input type="checkbox"
                                   v-model="genre.included"
                                   @change="$emit('updateProjectGenre', genre)"
                                   :value="genre.id"
                                   class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                            <p :class="[projectGenres.includes(genre.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                {{ genre.name }}
                            </p>
                        </div>
                        <div v-else class="text-secondary">Noch keine Genres angelegt</div>
                    </BaseFilterDisclosure>

                    <hr class="border-gray-500 rounded-full mt-2 mb-2">

                    <!-- Project sectors Section -->
                    <BaseFilterDisclosure title="Bereiche">

                        <div v-if="sectors.length > 0"
                             v-for="sector in sectors"
                             :key="sector.id"
                             class="flex w-full mb-2">
                            <input type="checkbox"
                                   v-model="sector.included"
                                   @change="$emit('updateProjectSector', sector)"
                                   :value="sector.id"
                                   class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
                            <p :class="[projectSectors.includes(sector.id)
                                                        ? 'text-white' : 'text-secondary', 'subpixel-antialiased']"
                               class="ml-1.5 text-xs subpixel-antialiased align-text-middle">
                                {{ sector.name }}
                            </p>
                        </div>
                        <div v-else class="text-secondary">Noch keine Bereiche angelegt</div>
                    </BaseFilterDisclosure>
                </div>
            </MenuItems>
        </transition>

    </Menu>
</template>

<script>
import {ChevronDownIcon, DocumentTextIcon} from "@heroicons/vue/outline";
import {
    Menu,
    MenuButton,
    MenuItems,
    Disclosure,
    DisclosureButton,
    DisclosurePanel
} from "@headlessui/vue";
import BaseFilterDisclosure from "@/Layouts/Components/BaseFilterDisclosure";

export default {
    name: "ProjectAttributesMenu",
    components: {
        BaseFilterDisclosure,
        ChevronDownIcon,
        Menu,
        MenuItems,
        Disclosure,
        DisclosurePanel,
        DisclosureButton,
        MenuButton,
        DocumentTextIcon
    },
    props: {
        categories: Object,
        genres: Object,
        sectors: Object,
        projectCategories: Array,
        projectGenres: Array,
        projectSectors: Array
    },
    data() {
        return {
            attributesOpened: false,
        }
    }
}
</script>

<style scoped>

</style>
