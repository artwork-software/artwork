<template>
    <BaseModal @closed="closeModal" v-if="true">
        <div class="mx-4">
            <!--   Heading   -->
            <div>
                <ModalHeader
                    :title="this.issueOfMaterial?.id ?  $t('Edit issue of material') : $t('New issue of material')"
                    :description="$t('Please make sure that you allow for preparation and follow-up time.')"
                />
            </div>


            <!--    Form    -->
            <div class="grid gird-cols-1 md:grid-cols-2 gap-x-4 mb-4">

            </div>
                <div class="flex justify-center w-full py-4">
                    <FormButton
                        @click="updateOrCreateIssueOfMaterialModal()"
                        :text="$t('Save')"
                    />
                </div>
        </div>
    </BaseModal>
</template>
<script setup>
import IconLib from "@/Mixins/IconLib.vue";
import Permissions from "@/Mixins/Permissions.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";


const props = defineProps({
    issueOfMaterial: Object,
})

const openModal = () => {
    if (!this.props.issueOfMaterial) {
        return;
    }

    this.startDate = this.props.issueOfMaterial.startDate.format('YYYY-MM-DD');
    this.startTime = this.props.issueOfMaterial.startTime.format('HH:mm');
    this.endDate = this.props.issueOfMaterial.endDate.format('YYYY-MM-DD');
    this.endTime = this.props.issueOfMaterial.endTime.format('HH:mm');
    this.oldStartDate = this.startDate;
    this.oldStartTime = this.startTime;
    this.oldEndDate = this.endDate;
    this.oldEndTime = this.endTime;
    this.name = this.props.issueOfMaterial.name;
};
</script>
