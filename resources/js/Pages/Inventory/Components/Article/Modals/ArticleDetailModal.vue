<template>
    <BaseModal @closed="$emit('close')" modal-size="max-w-5xl">
        <div class="mt-10">
            <!-- Product -->
            <div class="lg:grid lg:grid-cols-2 lg:items-start lg:gap-x-8">
                <!-- Image gallery -->
                <TabGroup as="div" class="flex flex-col-reverse">
                    <!-- Image selector -->
                    <div class="mx-auto mt-6 hidden w-full max-w-2xl sm:block lg:max-w-none">
                        <TabList class="grid grid-cols-4 gap-6">
                            <Tab v-for="image in article.images" :key="image.id" class="relative flex h-24 cursor-pointer items-center justify-center rounded-md bg-white text-sm font-medium text-gray-900 uppercase hover:bg-gray-50 focus:ring-3 focus:ring-indigo-500/50 focus:ring-offset-4 focus:outline-hidden" v-slot="{ selected }">
                                <span class="sr-only">{{ image.image }}</span>
                                <span class="absolute inset-0 overflow-hidden rounded-md">
                                    <img :src="'/storage/' + image.image" alt="" class="size-full object-cover" />
                                </span>
                                <span :class="[selected ? 'ring-artwork-buttons-create' : 'ring-transparent', 'pointer-events-none absolute inset-0 rounded-md ring-2 ring-offset-2']" aria-hidden="true" />
                            </Tab>
                        </TabList>
                    </div>

                    <TabPanels>
                        <TabPanel v-for="image in article.images" :key="image.id">
                            <img :src="'/storage/' + image.image" :alt="image.alt" class="aspect-square w-full object-contain sm:rounded-lg" />
                        </TabPanel>
                    </TabPanels>
                </TabGroup>

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
                                    <dl class="divide-y divide-gray-100">
                                        <div class="px-2 py-4 flex items-center justify-between" v-for="property in article.properties" :key="property.id">
                                            <dt class="text-xs font-bold text-gray-900 font-lexend">{{ property.name }}</dt>
                                            <dd class="font-lexend text-xs text-artwork-buttons-create">{{ property.pivot.value }}</dd>
                                        </div>
                                    </dl>
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

const props = defineProps({
    article: {
        type: Object,
        required: true
    }
})

const emit = defineEmits([
    'close'
])

</script>

<style scoped>

</style>