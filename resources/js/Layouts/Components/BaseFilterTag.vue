<template>
    <span class="flex rounded-full items-center font-medium text-tagText
              border bg-tagBg border-tag px-2 py-1 mt-1 text-sm mr-1 mb-1">
        <div v-if="filter.name">
            {{ filter.name }}
        </div>
        <div v-else>
            {{filter}}
        </div>
            <button
                @click="$emit('removeFilter', filter)"
                type="button">
                <XIcon v-if="type !== 'calendar'" class="ml-1 h-4 w-4 hover:text-error "/>
            </button>
    </span>
</template>

<script>
import {
    XIcon
} from '@heroicons/vue/outline';
import Permissions from "@/mixins/Permissions.vue";

export default {
    name: "BaseFilterTag",
    mixins: [Permissions],
    props: {
        filter: Object,
        type: {
            String
        }
    },
    emits: ['removeFilter'],
    components: {
        XIcon
    },
}
</script>

<style scoped>

</style>
