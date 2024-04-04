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
    <IconPlaystationCircle class="w-6 h-6" v-if="type === 'ProjectStateComponent'"/>
    <IconUsersGroup class="w-6 h-6" v-if="type === 'ProjectGroupComponent'" />
    <IconUserShield class="w-6 h-6" v-if="type === 'ProjectTeamComponent'" />
    <IconFolderPlus class="w-6 h-6" v-if="type === 'ProjectAttributesComponent'" />
    <IconCalendar class="w-6 h-6" v-if="type === 'CalendarTab'" />
    <IconCalendarUser class="w-6 h-6" v-if="type === 'ShiftTab'" />
    <IconListCheck class="w-6 h-6" v-if="type === 'ChecklistComponent'" />
    <IconCalendarStats class="w-6 h-6" v-if="type === 'RelevantDatesForShiftPlanningComponent'" />
    <IconHeading class="w-6 h-6" v-if="type === 'ProjectTitleComponent'" />
    <IconFiles class="w-6 h-6" v-if="type === 'ProjectDocumentsComponent'" />
    <IconMessageDots class="w-6 h-6" v-if="type === 'CommentTab'" />

    <IconCalendarDue class="w-6 h-6" v-if="type === 'ProjectBudgetDeadlineComponent'" />
    <IconMoneybag class="w-6 h-6" v-if="type === 'BudgetComponent'" />
    <IconAddressBook class="w-6 h-6" v-if="type === 'ShiftContactPersonsComponent'" />
    <IconInfoSquare class="w-6 h-6" v-if="type === 'GeneralShiftInformationComponent'" />

    <!-- TextField, Checkbox, TextArea, Title, DropDown -->
</template>

<style scoped>

</style>
