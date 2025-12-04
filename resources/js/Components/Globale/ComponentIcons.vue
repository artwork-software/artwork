<script>
import IconLib from "@/Mixins/IconLib.vue";
import {
    IconCornerDownRightDouble,
    IconDeviceProjector,
    IconHomeStar,
    IconLayoutNavbarCollapse, IconLink
} from "@tabler/icons-vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";

export default {
    name: "ComponentIcons",
    components: {PropertyIcon},
    methods: {IconLink, IconLayoutNavbarCollapse, IconDeviceProjector, IconCornerDownRightDouble, IconHomeStar},
    mixins: [IconLib],
    props: ['type'],
}
</script>

<template>
    <!-- IconTxt, IconCheckbox, IconArticle, IconHeading, IconSelect -->
    <PropertyIcon name="IconTxt" class="w-6 h-6" v-if="type === 'TextField'"/>
    <PropertyIcon name="IconCheckbox" class="w-6 h-6" v-if="type === 'Checkbox'"/>
    <PropertyIcon name="IconArticle" class="w-6 h-6" v-if="type === 'TextArea'"/>
    <PropertyIcon name="IconHeading" class="w-6 h-6" v-if="type === 'Title'"/>
    <PropertyIcon name="IconSelect" class="w-6 h-6" v-if="type === 'DropDown'"/>
    <PropertyIcon name="IconPlaystationCircle" class="w-6 h-6" v-if="type === 'ProjectStateComponent'"/>
    <PropertyIcon name="IconUsersGroup" class="w-6 h-6" v-if="type === 'ProjectGroupComponent'" />
    <PropertyIcon name="IconUserShield" class="w-6 h-6" v-if="type === 'ProjectTeamComponent'" />
    <PropertyIcon name="IconFolderPlus" class="w-6 h-6" v-if="type === 'ProjectAttributesComponent'" />
    <PropertyIcon name="IconCalendar" class="w-6 h-6" v-if="type === 'CalendarTab'" />
    <PropertyIcon name="IconCalendarUser" class="w-6 h-6" v-if="type === 'ShiftTab'" />
    <PropertyIcon name="IconListCheck" class="w-6 h-6" v-if="type === 'ChecklistComponent'" />
    <PropertyIcon name="IconListCheck" class="w-6 h-6" v-if="type === 'ChecklistAllComponent'" />
    <PropertyIcon name="IconCalendarStats" class="w-6 h-6" v-if="type === 'RelevantDatesForShiftPlanningComponent'" />
    <PropertyIcon name="IconHeading" class="w-6 h-6" v-if="type === 'ProjectTitleComponent'" />
    <PropertyIcon name="IconFiles" class="w-6 h-6" v-if="type === 'ProjectDocumentsComponent'" />
    <PropertyIcon name="IconFiles" class="w-6 h-6" v-if="type === 'ProjectAllDocumentsComponent'" />
    <PropertyIcon name="IconMessageDots" class="w-6 h-6" v-if="type === 'CommentTab'" />
    <PropertyIcon name="IconMessageDots" class="w-6 h-6" v-if="type === 'CommentAllTab'" />

    <PropertyIcon name="IconCalendarDue" class="w-6 h-6" v-if="type === 'ProjectBudgetDeadlineComponent'" />
    <PropertyIcon name="IconMoneybag" class="w-6 h-6" v-if="type === 'BudgetTab'" />
    <PropertyIcon name="IconAddressBook" class="w-6 h-6" v-if="type === 'ShiftContactPersonsComponent'" />
    <PropertyIcon name="IconInfoSquare" class="w-6 h-6" v-if="type === 'GeneralShiftInformationComponent'" />
    <PropertyIcon name="IconSeparator" class="w-6 h-6" v-if="type === 'SeparatorComponent'" />
    <PropertyIcon name="IconCurrencyEuro" class="w-6 h-6" v-if="type === 'BudgetInformations'" />
    <PropertyIcon name="IconApps" class="w-6 h-6" v-if="type === 'BulkBody'" />
    <PropertyIcon name="IconHomeStar" class="w-6 h-6" v-if="type === 'ArtistResidenciesComponent'" />
    <PropertyIcon name="IconCornerDownRightDouble" class="w-6 h-6" v-if="type === 'GroupProjectDisplayComponent'" />
    <PropertyIcon name="IconDeviceProjector" class="w-6 h-6" v-if="type === 'ProjectGroupDisplayComponent'" />
    <PropertyIcon name="IconLayoutNavbarCollapse" class="w-6 h-6" v-if="type === 'DisclosureComponent'" />
    <PropertyIcon name="IconLink" class="w-6 h-6" v-if="type === 'Link'" />

    <!-- TextField, Checkbox, TextArea, Title, DropDown -->
</template>

<style scoped>

</style>
