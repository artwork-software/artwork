<template>
    <BaseModal @closed="$emit('close')" modal-size="sm:max-w-3xl">
        <div class="flex items-center justify-between mt-4">
            <div>
                <div class="headline1">
                    {{ item.properties.find(property => property.id === 'title')?.plainText }}
                </div>
            </div>
            <div v-if="item.properties.some(property => property.title === 'Status')">
                <div
                    v-for="property in item.properties.filter(p => p.title === 'Status')"
                    :key="property.id"
                    class="inline-block px-3 py-1 text-white text-xs rounded-full"
                    :class="{
                                        'bg-gray-500': property.rawContent.color === 'gray',
                                        'bg-red-500': property.rawContent.color === 'red',
                                        'bg-green-500': property.rawContent.color === 'green',
                                        'bg-blue-500': property.rawContent.color === 'blue',
                                        'bg-yellow-500': property.rawContent.color === 'yellow',
                                        'bg-purple-500': property.rawContent.color === 'purple',
                                        'bg-pink-500': property.rawContent.color === 'pink',
                                        'bg-orange-500': property.rawContent.color === 'orange',
                                        'bg-brown-500': property.rawContent.color === 'brown',
                                    }">
                    {{ property.rawContent?.name }}
                </div>
            </div>
            <div v-if="item.properties.some(property => property.title === 'Author')" class="group block shrink-0 bg-white w-fit pr-3 shadow-md rounded-full border border-gray-100">
                <div class="flex items-center">
                    <div>
                        <img
                            class="inline-block size-9 rounded-full object-cover"
                            v-if="item.properties.find(property => property.title === 'Author')?.rawContent[0]?.avatar_url"
                            :src="item.properties.find(property => property.title === 'Author')?.rawContent[0]?.avatar_url"
                            alt=""
                        />
                    </div>
                    <div class="mx-2">
                        <p class="xsDark group-hover:text-gray-900"> {{ item.properties.find(property => property.title === 'Author')?.rawContent[0]?.name }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-10 border-y border-dashed py-5 border-gray-300">
            <div v-for="pageContent in contentAsCollection">
                <div v-if="pageContent.responseData.type === 'paragraph'" class="mt-1.5">
                    <span v-for="textContent in pageContent.responseData.paragraph.text">
                        <span :class="{
                                'font-bold' : textContent?.annotations?.bold,
                                'italic' : textContent?.annotations?.italic,
                                'underline': textContent?.annotations?.underline,
                                'line-through': textContent?.annotations?.strikethrough,
                            }">
                            {{ textContent?.plain_text }}
                        </span>
                    </span>
                </div>

                <ul v-if="pageContent.responseData.type === 'bulleted_list_item'"  class="list-disc list-inside">
                    <li>
                        <span v-for="textContent in pageContent.responseData.bulleted_list_item.text">
                        <span :class="{
                                'font-bold' : textContent?.annotations?.bold,
                                'italic' : textContent?.annotations?.italic,
                                'underline': textContent?.annotations?.underline,
                                'line-through': textContent?.annotations?.strikethrough,
                            }">
                            {{ textContent?.plain_text }}
                        </span>
                    </span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="mt-3">
            <div v-if="item.properties.some(property => property.title === 'Update Datum')">
                <div class="xsDark">
                    <span>Update Datum: </span>
                    <span>{{ convertDate(item.properties.find(property => property.title === 'Update Datum')?.rawContent?.start) }} </span>
                    <span v-if="item.properties.find(property => property.title === 'Update Datum')?.rawContent?.end"> - {{ convertDate(item.properties.find(property => property.title === 'Update Datum')?.rawContent?.end) }} </span>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    content: {
        type: Object,
        required: true
    },
    contentAsCollection: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close'])


const convertDate = (date) => {
    return new Date(date).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}
</script>

<style scoped>

</style>