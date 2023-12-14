<template>
    <app-layout>
        <div class="">
            <div class="max-w-screen-2xl mb-40 flex flex-row ml-14 mr-40">
                <div class="flex flex-1 flex-wrap">
                    <div class="w-full flex my-auto justify-between">
                        <div class="flex flex-wrap w-full">
                            <div class="flex flex-wrap w-full">
                                <h2 class="headline1 flex w-full">Finanzierungsquellen</h2>
                                <h2 class="mt-10 headline2 w-full">Quellenkategorien</h2>
                                <div class="xsLight flex mt-4 w-full">
                                    Lege Kategorien fest. Nach diesen kann anschließend in der
                                    Übersicht gefiltert werden.
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
                                                Kategorie eingeben
                                            </label>
                                        </div>

                                        <div class="m-2">
                                            <button
                                                :class="[moneySourceCategoryInput === '' ? 'bg-secondary': 'bg-buttonBlue hover:bg-buttonHover focus:outline-none', 'rounded-full mt-2 ml-1 items-center text-sm p-1 border border-transparent uppercase shadow-sm text-secondaryHover']"
                                                @click="addMoneySourceCategory" :disabled="!moneySourceCategoryInput">
                                                <CheckIcon class="h-5 w-5"></CheckIcon>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mt-2 mr-10 flex flex-wrap">
                                        <span v-for="(category,index) in moneySourceCategories"
                                              class="rounded-full items-center font-medium text-tagText
                                            border bg-tagBg border-tag px-3 text-sm mr-1 mb-1 h-8 inline-flex">
                                            {{ category.name }}
                                            <button type="button" @click="deleteMoneySourceCategory(category)">
                                                <!--<span class="sr-only">Email aus Einladung entfernen</span>-->
                                                <XIcon class="ml-1 h-4 w-4 hover:text-error "/>
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
    </app-layout>
</template>

<script>

import AppLayout from '@/Layouts/AppLayout.vue'
import Button from "@/Jetstream/Button";
import AddButton from "@/Layouts/Components/AddButton";
import {
    XIcon
} from "@heroicons/vue/outline";
import {CheckIcon} from "@heroicons/vue/solid";
import {defineComponent} from 'vue'
import {Inertia} from "@inertiajs/inertia";
import Permissions from "@/mixins/Permissions.vue";

export default defineComponent({
    mixins: [Permissions],
    components: {
        AddButton,
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

        }
    },
    methods: {
        deleteMoneySourceCategory(category) {
            Inertia.delete(`/money_source/categories/${category.id}`, {
                preserveState: true,
                preserveScroll: true
            });
        },
        addMoneySourceCategory() {
            if (this.moneySourceCategoryInput.indexOf(' ') === -1) {
                Inertia.post(`/money_source/categories/`, {name: this.moneySourceCategoryInput});
            }
            this.moneySourceCategoryInput = "";

        },
    },
})
</script>

<style scoped>

</style>
