<template>
    <BaseModal @closed="$emit('close')" modal-size="max-w-5xl">
        <div class="mt-10">
            <!-- Product -->
            <div class="lg:grid lg:grid-cols-2 lg:items-start lg:gap-x-8">
                <!-- Image gallery -->
                <TabGroup as="div" class="flex flex-col-reverse gap-4" v-if="article.images.length > 0">
                <!-- Image Selector -->
                    <div class="mx-auto w-full max-w-4xl">
                        <TabList class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <Tab v-for="image in article.images"
                                :key="image.id"
                                v-slot="{ selected }"
                                class="relative group rounded-lg overflow-hidden border border-gray-200 bg-white shadow-sm transition hover:shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <span class="sr-only">{{ image.image }}</span>
                                <span class="block aspect-square">
                                    <img :src="'/storage/' + image.image" alt="" class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-125" @error="(e) => e.target.src = usePage().props.big_logo"/>
                                </span>
                                <span :class="[selected ? 'ring-2 ring-indigo-500 ring-offset-2' : '', 'pointer-events-none absolute inset-0 rounded-lg']" aria-hidden="true"/>
                            </Tab>
                        </TabList>
                    </div>

                    <!-- Main Image Viewer -->
                    <TabPanels class="mx-auto w-full max-w-4xl">
                        <TabPanel v-for="image in article.images" :key="image.id" class="flex justify-center items-center rounded-lg p-4 sm:p-6">
                            <img :src="'/storage/' + image.image"
                                :alt="image.alt"
                                class="max-h-[70vh] w-full object-contain rounded-lg transition"
                                @error="(e) => e.target.src = usePage().props.big_logo"
                            />
                        </TabPanel>
                    </TabPanels>
                </TabGroup>

                <div v-else class="flex flex-col-reverse p-10">
                    <img :src="usePage().props.big_logo" alt="" class="aspect-square w-full object-contain sm:rounded-lg" />
                </div>

                <!-- Product info -->
                <div class="px-4 sm:mt-16 sm:px-0 lg:mt-0">
                    <h1 class="text-3xl font-lexend font-bold tracking-tight text-gray-900">{{ article.name }}</h1>
                    <div class="mt-4">
                        <h2 class="font-lexend font-semibold text-xs">{{ $t('Quantity') }}</h2>
                        <p class="text-3xl font-bold tracking-tight text-artwork-buttons-create">{{ article.quantity }}</p>
                    </div>

                    <!-- Reviews -->
                    <!--<div class="mt-3">
                        <h3 class="sr-only">Reviews</h3>
                        <div class="flex items-center">
                            <div class="flex items-center">
                                <StarIcon v-for="rating in [0, 1, 2, 3, 4]" :key="rating" :class="[product.rating > rating ? 'text-indigo-500' : 'text-gray-300', 'size-5 shrink-0']" aria-hidden="true" />
                            </div>
                            <p class="sr-only">{{ product.rating }} out of 5 stars</p>
                        </div>
                    </div>-->

                    <div class="mt-4">
                        <div class="space-y-6 text-sm italic text-gray-500 font-lexend font-extralight" v-html="article.description" />
                    </div>

                    <section aria-labelledby="details-heading" class="mt-12">

                        <div class="divide-y divide-gray-200 border-t">
                            <Disclosure as="div" v-slot="{ open }">
                                <h3>
                                    <DisclosureButton class="group relative flex w-full items-center justify-between py-6 text-left">
                                    <span :class="[open ? 'text-artwork-buttons-default' : 'text-gray-900', 'text-sm font-medium font-lexend']">
                                        {{ $t('Article properties')}}
                                    </span>
                                        <span class="ml-6 flex items-center">
                                        <component is="IconPlus" v-if="!open" class="block size-6 text-gray-400 group-hover:text-gray-500" aria-hidden="true" />
                                        <component is="IconMinus" v-else class="block size-6 text-artwork-buttons-default group-hover:text-artwork-buttons-hover" aria-hidden="true" />
                                    </span>
                                    </DisclosureButton>
                                </h3>
                                <DisclosurePanel as="div" class="">
                                    <dl class="divide-y divide-gray-100" v-if="article.properties.length > 0">
                                        <div class="px-2 py-4 flex items-center justify-between" v-for="property in article.properties" :key="property.id">
                                            <dt class="text-xs font-bold text-gray-900 font-lexend">{{ property.name }}</dt>
                                            <dd class="font-lexend text-xs text-artwork-buttons-create">
                                                <span>{{ formatProperty(property) }}</span>
                                            </dd>
                                        </div>
                                    </dl>
                                    <div v-else>
                                        <div class="rounded-md bg-red-50 p-4">
                                            <div class="flex">
                                                <div class="shrink-0">
                                                    <component is="IconAlertSquareRoundedFilled" class="size-5 text-red-400" aria-hidden="true" />
                                                </div>
                                                <div class="ml-3">
                                                    <p class="text-sm font-medium text-red-800">{{ $t('No properties were specified for this article') }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </DisclosurePanel>
                            </Disclosure>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import {
    Disclosure,
    DisclosureButton, DisclosurePanel,
    RadioGroup,
    RadioGroupOption,
    Tab,
    TabGroup,
    TabList,
    TabPanel,
    TabPanels
} from "@headlessui/vue";
import {usePage} from "@inertiajs/vue3";
import {useTranslation} from "@/Composeables/Translation.js";
const $t = useTranslation()


const props = defineProps({
    article: {
        type: Object,
        required: true
    }
})

const emit = defineEmits([
    'close'
])

const formatProperty = (property) => {
    if (property.type === 'room') {
        return props.article.room?.name === 'Room not found' ? $t(props.article.room?.name) : props.article.room?.name;
    }

    if (property.type === 'date') {
        return new Date(property.pivot.value).toLocaleDateString();
    }

    if (property.type === 'time') {
        return new Date(property.pivot.value).toLocaleTimeString();
    }

    if (property.type === 'datetime') {
        return new Date(property.pivot.value).toLocaleString();
    }

    if (property.type === 'checkbox') {
        return property.pivot.value ? $t('Yes') : $t('No');
    }

    return property.pivot.value;
}

</script>

<style scoped>

</style>