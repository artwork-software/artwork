<template>
    <app-layout :title="$t('Sources of funding')">
        <div class="">
            <div class="max-w-screen-2xl mb-40 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex flex-wrap w-full">
                            <div class="flex flex-wrap w-full">
                                <h2 class="headline1 flex w-full">{{  $t('Sources of funding') }}</h2>
                                <h2 class="mt-10 headline2 w-full">{{$t('Source categories')}}</h2>
                                <div class="xsLight flex mt-4 w-full">
                                    {{$t('Define categories. These can then be filtered in the overview.')}}
                                </div>
                                <div v-if="showInvalidCategoryNameErrorText" class="text-red-600 text-sm mt-4">
                                    {{ $t('You have entered an invalid name. No spaces are allowed at the beginning or end. It is also not permitted to enter only spaces.')}}
                                </div>
                                <div class=" w-full grid grid-cols-2 grid-flow-col grid-rows-2">
                                    <!-- Finanzierungsquellenkategorien -->
                                    <div class="mt-8 mr-10 flex">
                                        <div class="relative w-72">
                                            <input v-on:keyup.enter=addMoneySourceCategory id="moneySourceCategory"
                                                   v-model="moneySourceCategoryInput"
                                                   type="text"
                                                   class="peer pl-0 h-12 w-full focus:border-t-transparent focus:border-primary focus:ring-0 border-l-0 border-t-0 border-r-0 border-b-2 border-gray-300 text-primary placeholder-secondary placeholder-transparent"
                                                   placeholder="placeholder"/>
                                            <label for="roomCategory"
                                                   class="absolute left-0 text-sm -top-5 text-gray-600 text-sm -top-3.5 transition-all
                                                subpixel-antialiased focus:outline-none text-secondary peer-placeholder-shown:text-base
                                                 peer-placeholder-shown:text-gray-400 peer-placeholder-shown:top-2 peer-focus:-top-3.5
                                                  peer-focus:text-sm ">
                                                {{$t('Enter Category')}}
                                            </label>
                                        </div>

                                        <div class="m-2">
                                            <button
                                                :class="[moneySourceCategoryInput === '' ? 'bg-secondary': 'bg-artwork-buttons-create hover:bg-artwork-buttons-hover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-white']"
                                                @click="addMoneySourceCategory" :disabled="!moneySourceCategoryInput">
                                                <IconCheck stroke-width="1.5" class="h-5 w-5"></IconCheck>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-2 mr-10 flex flex-wrap">
                                        <span v-for="(category,index) in moneySourceCategories"
                                              class="rounded-full items-center font-medium text-tagText
                                            border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                            {{ category.name }}
                                            <button type="button" @click="this.showCategoryDeleteModal(category)">
                                                <IconX  stroke-width="1.5" class="ml-1 h-4 w-4 hover:text-error "/>
                                            </button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Delete Category Modal -->
        <ConfirmationComponent v-if="categoryDeleteModalVisible"
                               :confirm="$t('Delete source category')"
                               :titel="$t('Delete source category')"
                               :description="$t('Are you sure you want to delete the source category? This irrevocably deletes all assignments of funding sources to this category.')"
                               @closed="afterDeleteCategoryConfirm"/>
    </app-layout>
</template>

<script>

import AppLayout from '@/Layouts/AppLayout.vue'
import Button from "@/Jetstream/Button.vue";
import {
    XIcon
} from "@heroicons/vue/outline";
import {CheckIcon} from "@heroicons/vue/solid";
import {defineComponent} from 'vue'
import {router} from "@inertiajs/vue3";
import Permissions from "@/Mixins/Permissions.vue";
import JetDialogModal from "@/Jetstream/DialogModal.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import IconLib from "@/Mixins/IconLib.vue";

export default defineComponent({
    mixins: [Permissions, IconLib],
    components: {
        ConfirmationComponent,
        JetDialogModal,
        Button,
        AppLayout,
        CheckIcon,
        XIcon
    },
    name: 'MoneySourceSettings',
    props: ['moneySourceCategories'],
    data() {
        return {
            moneySourceCategoryInput: '',
            categoryDeleteModalVisible: false,
            categoryToDelete: null,
            showInvalidCategoryNameErrorText: false,
        }
    },
    methods: {
        showCategoryDeleteModal(category) {
            this.categoryDeleteModalVisible = true;
            this.categoryToDelete = category;
        },
        afterDeleteCategoryConfirm(confirmed) {
            if (confirmed) {
                router.delete(
                    route('money_source_categories.destroy', {moneySourceCategory: this.categoryToDelete.id}),
                    {
                        preserveScroll: true
                    }
                );
            }
            this.categoryDeleteModalVisible = false;
            this.categoryToDelete = null;
        },
        addMoneySourceCategory() {
            //Leerzeichen am Anfang und am Ende des Strings sind nicht erlaubt, aber innerhalb des Strings
            const regex = /^(?!\s)(?:(?!\s+$)\s|\S+\s*\S*)*(?<!\s)$/;
            if (regex.test(this.moneySourceCategoryInput)) {
                this.showInvalidCategoryNameErrorText = false;
                router.post(
                    route('money_source_categories.store'),
                    {
                        name: this.moneySourceCategoryInput
                    },
                    {
                        onSuccess: () => this.moneySourceCategoryInput = ''
                    }
                );
            } else {
                this.showInvalidCategoryNameErrorText = true;
            }
        },
    },
})
</script>

<style scoped>

</style>
