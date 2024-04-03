<script>
import IconLib from "@/mixins/IconLib.vue";

export default {
    name: "ComponentIcons",
    mixins: [IconLib],
    props: ['type'],
}
</script>

<template>
    <!-- IconTxt, IconCheckbox, IconArticle, IconHeading, IconSelect -->
    <IconTxt class="w-6 h-6" v-if="type === 'TextField'"/>
    <IconCheckbox class="w-6 h-6" v-if="type === 'Checkbox'"/>
    <IconArticle class="w-6 h-6" v-if="type === 'TextArea'"/>
    <IconHeading class="w-6 h-6" v-if="type === 'Title'"/>
    <IconSelect class="w-6 h-6" v-if="type === 'DropDown'"/>

    <!-- TextField, Checkbox, TextArea, Title, DropDown -->
</template>

<style scoped>

</style>
