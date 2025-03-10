<template>
    <div class="my-2">
        <label for="email" class="block text-sm font-medium leading-6" :class="inSidebar ? 'text-white' : 'text-gray-900'">
            {{ data.data.label }}
        </label>
        <span v-if="descriptionClicked === false"
              class="mt-2 subpixel-antialiased text-secondary"
              @click="handleDescriptionClick()"
              v-html="projectData.project_value?.data?.text ? projectData.project_value.data.text : (this.canEditComponent ? $t('Click here to add text') : '')">
        </span>
        <TextareaComponent
            v-else
            :disabled="!this.canEditComponent"
            :label="data.data.placeholder"
            :ref="`description-${this.projectId}`"
            :class="inSidebar ? 'bg-primary text-white' : ''"
            id="placeholder"
            :show-label="false"
            no-margin-top
            @focusout="updateTextData()"
            v-model="text"
            :maxlength="2000"
        >
        </TextareaComponent>
    </div>
</template>

<script>
import {nextTick, ref} from "vue";
import Permissions from "@/Mixins/Permissions.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import { useProjectDataListener } from "@/Composeables/Listener/useProjectDataListener.js";

export default {
    name: "TextArea",
    components: {TextareaComponent},
    mixins: [Permissions],
    props: ['data', 'projectId', 'inSidebar', 'canEditComponent', 'projectWriteIds', 'project', 'projectManagerIds'],
    data() {
        return {
            textData: {
                text: this.data.project_value ? this.data.project_value.text_without_html : this.data.data.text,
            },
            descriptionClicked: false,
            projectData: this.data,
            text: this.data.project_value?.text_without_html ? this.data.project_value.text_without_html : this.data.data.text,
        }
    },
    mounted() {
        useProjectDataListener(this.projectData, this.projectId).init();
    },
    watch: {
        // if the data changes, update the text
        projectData: {
            handler: function (newVal, oldVal) {
                this.text = newVal.project_value.text_without_html ? newVal.project_value.text_without_html : newVal.data.text
            },
            deep: true
        }
    },
    methods: {
        updateTextData() {
            this.$inertia.patch(route('project.tab.component.update', {project: this.projectId, component: this.data.id}), {
                data: {
                    text: this.text
                }
            }, {
                preserveScroll: true,
                preserveState: true,
                onFinish: () => this.descriptionClicked = false
            })
        },
        handleDescriptionClick() {
            if (!this.canEditComponent) {
                return;
            }

            if (this.$can('write projects') || this.$role('artwork admin') || this.$can('admin projects') || this.projectWriteIds.includes(this.$page.props.user.id) || this.projectManagerIds.includes(this.$page.props.user.id) || this.project.isMemberOfADepartment){
                this.descriptionClicked = true;

                nextTick(() => {
                    //this.$refs[`description-${this.projectId}`].select();
                })

            }
        },
    },
}
</script>
