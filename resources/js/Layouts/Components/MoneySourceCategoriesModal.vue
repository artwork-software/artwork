<template>
    <BaseModal @closed="closeModal" v-if="true" modal-image="/Svgs/Overlays/illu_money_source_create.svg">
            <div class="mx-4">
                <div class="headline1 my-2">
                    {{ $t('Manage source categories')}}
                </div>
            </div>
            <div class="mx-4">
                <Menu class="relative">
                    <div>
                        <MenuButton class="w-full">
                            <div class="border-2 border-gray-300 w-full cursor-pointer truncate flex p-4 mt-4">
                                <div class="flex-grow xsLight text-left subpixel-antialiased">
                                    {{ $t('Select source categories')}}
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
                            <MenuItems class="absolute right-0 w-full origin-top-right divide-y divide-gray-200 rounded-sm bg-primary ring-1 ring-black p-2 text-white opacity-100 z-50">
                                <div v-if="moneySourceCategories.length > 0" class="grid grid-cols-1 gap-2">
                                    <div v-for="category in moneySourceCategories"
                                         :key="category.id"
                                         class="w-full flex items-center">
                                        <input type="checkbox"
                                               v-model="selectedCategoryIds"
                                               :value="category.id"
                                               class="checkBoxOnDark"
                                        />
                                        <p :class="[selectedCategoryIds.includes(category.id) ? 'text-white' : 'text-secondary', 'ml-2 text-xs subpixel-antialiased align-text-middle']">
                                            {{ category.name }}
                                        </p>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-gray-300">
                                    {{ $t('No source categories have been created yet')}}
                                </div>
                            </MenuItems>
                        </transition>
                    </div>
                </Menu>
            </div>
            <div class="mx-4 mt-2 flex flex-wrap">
                <TagComponent v-if="moneySourceSelectedCategories.length > 0"
                              v-for="category in moneySourceSelectedCategories"
                              :key="category.id"
                              :displayed-text="category.name"
                              hide-x="true"/>
            </div>
            <div class="justify-center flex w-full my-6">
                <FormButton :disabled="moneySourceCategories.length === 0" :text="$t('Save')" @click="attachCategories"/>
            </div>
    </BaseModal>
</template>

<script>
import Permissions from "@/Mixins/Permissions.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import {ChevronDownIcon, XIcon} from "@heroicons/vue/outline";
import BaseFilterDisclosure from "@/Layouts/Components/BaseFilterDisclosure.vue";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import {router} from "@inertiajs/vue3";
import TagComponent from "@/Layouts/Components/TagComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";

export default {
    mixins: [Permissions],
    name: "MoneySourceCategoriesModal",
    components: {
        BaseModal,
        FormButton,
        TagComponent,
        MenuItem,
        MenuItems,
        MenuButton,
        Menu,
        BaseFilterTag,
        ChevronDownIcon,
        BaseFilterDisclosure,
        JetDialogModal,
        XIcon
    },
    props: ['show', 'closeModal', 'moneySourceId', 'moneySourceCategories', 'moneySourceCurrentCategories'],
    data() {
        return {
            selectedCategoryIds: []
        }
    },
    computed: {
        moneySourceSelectedCategories() {
            let moneySourceSelectedCategories = [];

            this.selectedCategoryIds.forEach((moneySourceCategoryId) => {
                moneySourceSelectedCategories.push(
                    this.moneySourceCategories.find(
                        (moneySourceCategory) => moneySourceCategory.id === moneySourceCategoryId
                    )
                );
            });

            return moneySourceSelectedCategories;
        }
    },
    mounted: function() {
        this.moneySourceCurrentCategories.forEach((moneySourceCurrentCategory) => {
            this.selectedCategoryIds.push(moneySourceCurrentCategory.id)
        });
    },
    methods: {
        attachCategories() {
            router.post(
                route('money_sources.categories.sync', {moneySource: this.moneySourceId}),
                {
                    categoryIds: this.selectedCategoryIds
                },
                {
                    onSuccess: () => this.closeModal()
                }
            )
        }
    }
}
</script>
