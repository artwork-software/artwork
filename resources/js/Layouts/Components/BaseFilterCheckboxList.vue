<template>
    <div v-if="list.length > 0"
         v-for="item in list"
         class="flex w-full mb-2">
        <input type="checkbox"
               :id="getIdentifier(item)"
               v-model="item.checked"
               @change="$emit('change-filter-items', { list: list, item: item, filterName: filterName})"
               class="cursor-pointer h-4 w-4 text-success border-1 border-darkGray bg-darkGrayBg focus:border-none"/>
        <label :for="getIdentifier(item)"
               :class="[item.checked ? 'text-white' : 'text-secondary', 'ml-1.5 text-xs subpixel-antialiased align-text-middle cursor-pointer']">
            {{ item.name }}</label>
    </div>
    <div v-else class="text-secondary">{{ textIfEmpty }}</div>
</template>

<script>
import Permissions from "@/Mixins/Permissions.vue";

export default {
    name: "BaseFilterCheckboxList",
    mixins: [Permissions],
    props: {
        list: Array,
        textIfEmpty: String,
        filterName: String
    },
    emits: ['change-filter-items'],
    methods: {
        getIdentifier(item) {
            if (!item.inputId) {
                item.inputId = this.filterName + '-cb-' + Math.floor(Math.random() * 1000000);
            }
            console.debug(item.inputId);
            return item.inputId;
        }
    }
}
</script>
