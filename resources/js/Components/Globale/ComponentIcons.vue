<script>
import IconLib from "@/Mixins/IconLib.vue";

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
    <IconListCheck class="w-6 h-6" v-if="type === 'ChecklistAllComponent'" />
    <IconCalendarStats class="w-6 h-6" v-if="type === 'RelevantDatesForShiftPlanningComponent'" />
    <IconHeading class="w-6 h-6" v-if="type === 'ProjectTitleComponent'" />
    <IconFiles class="w-6 h-6" v-if="type === 'ProjectDocumentsComponent'" />
    <IconFiles class="w-6 h-6" v-if="type === 'ProjectAllDocumentsComponent'" />
    <IconMessageDots class="w-6 h-6" v-if="type === 'CommentTab'" />
    <IconMessageDots class="w-6 h-6" v-if="type === 'CommentAllTab'" />

    <IconCalendarDue class="w-6 h-6" v-if="type === 'ProjectBudgetDeadlineComponent'" />
    <IconMoneybag class="w-6 h-6" v-if="type === 'BudgetTab'" />
    <IconAddressBook class="w-6 h-6" v-if="type === 'ShiftContactPersonsComponent'" />
    <IconInfoSquare class="w-6 h-6" v-if="type === 'GeneralShiftInformationComponent'" />
    <IconSeparator class="w-6 h-6" v-if="type === 'SeparatorComponent'" />
    <IconCurrencyEuro class="w-6 h-6" v-if="type === 'BudgetInformations'" />

    <!-- TextField, Checkbox, TextArea, Title, DropDown -->
</template>

<style scoped>

</style>
