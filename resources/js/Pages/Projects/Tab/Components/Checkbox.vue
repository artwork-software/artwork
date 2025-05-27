<script>
import {useProjectDataListener} from "@/Composeables/Listener/useProjectDataListener.js";
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";

export default {
    name: "Checkbox",
    components: {InfoButtonComponent},
    props: [
        'data',
        'projectId',
        'inSidebar',
        'canEditComponent',
        'component',
    ],
    data() {
        return {
            checkedData: {
                checked: this.data.project_value ? this.data.project_value.data.checked : this.data.data.checked
            },
            projectData: this.data,
            checked: this.data.project_value?.data?.checked ?? false
        }
    },
    mounted() {
        useProjectDataListener(this.projectData, this.projectId).init();
    },
    methods: {
        updateCheckedData() {
            this.$inertia.patch(route('project.tab.component.update', {project: this.projectId, component: this.data.id}), {
                data: {
                    checked: this.checked
                }
            }, {
                preserveScroll: true,
                preserveState: false
            })
        }
    },
    watch: {
        // if the data changes, update the text
        projectData: {
            handler: function (newVal, oldVal) {
                this.checked = newVal.project_value ? newVal.project_value.data.checked : newVal.data.checked
            },
            deep: true
        }
    }
}
</script>

<template>
    <div class="flex my-2 items-start gap-x-4">
        <div class="relative flex items-start">
            <div class="flex h-6 items-center">
                <input :disabled="!this.canEditComponent"
                       id="comments"
                       aria-describedby="comments-description"
                       v-model="checked"
                       @change="updateCheckedData"
                       :checked="checked"
                       name="comments"
                       type="checkbox"
                       class="input-checklist"
                />
            </div>
            <div class="ml-3 text-sm leading-6">
                <label for="comments" class="font-medium " :class="inSidebar ? 'text-white' : 'text-gray-900'">
                    {{ data.data.label }}
                </label>
            </div>
        </div>

        <InfoButtonComponent :component="component" />
    </div>
</template>

<style scoped>

</style>
