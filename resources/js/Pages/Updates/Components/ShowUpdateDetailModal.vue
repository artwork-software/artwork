<template>
    <ArtworkBaseModal
        :title="updateTitle"
        :description="updateDateText"
        modal-size="sm:max-w-3xl"
        @close="$emit('close')"
    >
        <div v-if="statusProperties.length > 0 || author" class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <span
                    v-for="property in statusProperties"
                    :key="property.id"
                    class="inline-block rounded-full px-3 py-1 text-xs font-medium text-white"
                    :class="{
                        'bg-text-subtle': property.rawContent?.color === 'gray',
                        'bg-danger': property.rawContent?.color === 'red',
                        'bg-success': property.rawContent?.color === 'green',
                        'bg-accent-600': property.rawContent?.color === 'blue',
                        'bg-warning': property.rawContent?.color === 'yellow',
                        'bg-special-violet': property.rawContent?.color === 'purple',
                        'bg-special-pink': property.rawContent?.color === 'pink',
                        'bg-special-orange': property.rawContent?.color === 'orange',
                        'bg-brown-500': property.rawContent?.color === 'brown',
                    }"
                >
                    {{ property.rawContent?.name }}
                </span>
            </div>
            <div v-if="author" class="flex items-center gap-x-2 rounded-full border border-border-subtle bg-surface py-1 pl-1 pr-3">
                <img
                    v-if="author.avatar_url"
                    class="size-7 rounded-full object-cover"
                    :src="author.avatar_url"
                    alt=""
                />
                <span class="text-sm font-semibold text-text">{{ author.name }}</span>
            </div>
        </div>

        <div class="space-y-1.5 text-sm text-text" :class="statusProperties.length > 0 || author ? 'mt-5 border-t border-border-subtle pt-5' : ''">
            <template v-for="(pageContent, index) in contentAsCollection" :key="index">
                <p v-if="pageContent.responseData.type === 'paragraph'">
                    <span
                        v-for="(textContent, textIndex) in pageContent.responseData.paragraph.text"
                        :key="textIndex"
                        :class="textClasses(textContent)"
                    >{{ textContent?.plain_text }}</span>
                </p>
                <ul v-if="pageContent.responseData.type === 'bulleted_list_item'" class="list-inside list-disc">
                    <li>
                        <span
                            v-for="(textContent, textIndex) in pageContent.responseData.bulleted_list_item.text"
                            :key="textIndex"
                            :class="textClasses(textContent)"
                        >{{ textContent?.plain_text }}</span>
                    </li>
                </ul>
            </template>
        </div>

        <template #footer>
            <BaseUIButton
                label="Close"
                is-cancel-button
                hide-icon
                @click="$emit('close')"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed} from "vue";
import {useI18n} from "vue-i18n";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

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

defineEmits(['close'])

const {t} = useI18n()

const updateTitle = computed(() => props.item.properties.find(property => property.id === 'title')?.plainText ?? '')

const statusProperties = computed(() => props.item.properties.filter(property => property.title === 'Status'))

const author = computed(() => props.item.properties.find(property => property.title === 'Author')?.rawContent?.[0] ?? null)

const convertDate = (date) => {
    return new Date(date).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    })
}

// Zeitraum aus der Notion-Eigenschaft „Update Datum“ für die Kopfzeile
const updateDateText = computed(() => {
    const updateDate = props.item.properties.find(property => property.title === 'Update Datum')?.rawContent
    if (!updateDate?.start) {
        return ''
    }
    let text = t('Update date') + ': ' + convertDate(updateDate.start)
    if (updateDate.end) {
        text += ' - ' + convertDate(updateDate.end)
    }
    return text
})

const textClasses = (textContent) => ({
    'font-bold': textContent?.annotations?.bold,
    'italic': textContent?.annotations?.italic,
    'underline': textContent?.annotations?.underline,
    'line-through': textContent?.annotations?.strikethrough,
})
</script>
