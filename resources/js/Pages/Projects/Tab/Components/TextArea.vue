<template>
    <div class="my-2 flex items-start gap-x-4 w-full">
        <div>
            <label for="email" class="xsDark font-bold" :class="inSidebar ? 'xsLight' : 'xsDark'">
                {{ data.data.label }}
            </label>
            <div v-if="descriptionClicked === false"
                  class="mt-2 subpixel-antialiased xsDark"
                  @click="handleDescriptionClick()"
                  v-html="projectData.project_value?.data?.text ? projectData.project_value.data.text : (this.canEditComponent ? $t('Click here to add text') : '')">
        </div>

            <TextareaComponent
                v-else
                :disabled="!this.canEditComponent"
                :label="data.data.placeholder"
                :ref="`description-${this.projectId}`"
                :class="inSidebar ? 'bg-primary text-white' : ''"
                :id="data.id"
                :show-label="false"
                no-margin-top
                @focusout="updateTextData()"
                v-model="text"
                :maxlength="2000"
            >
            </TextareaComponent>
        </div>
        <InfoButtonComponent :component="component" />
    </div>
</template>

<script>
import {nextTick, ref} from "vue";
import Permissions from "@/Mixins/Permissions.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import { useProjectDataListener } from "@/Composeables/Listener/useProjectDataListener.js";
import {Popover, PopoverButton, PopoverPanel} from "@headlessui/vue";
import InfoButtonComponent from "@/Pages/Projects/Tab/Components/InfoButtonComponent.vue";

export default {
    name: "TextArea",
    components: {InfoButtonComponent, PopoverPanel, PopoverButton, Popover, TextareaComponent},
    mixins: [Permissions],
    props: ['data', 'projectId', 'inSidebar', 'canEditComponent', 'projectWriteIds', 'project', 'projectManagerIds', 'component'],
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

            if (this.canEditComponent || this.$can('write projects') || this.$role('artwork admin') || this.$can('admin projects') || this.projectWriteIds.includes(this.$page.props.auth.user.id) || this.projectManagerIds.includes(this.$page.props.auth.user.id) || this.project.isMemberOfADepartment){
                this.descriptionClicked = true;

                nextTick(() => {
                    //this.$refs[`description-${this.projectId}`].select();
                })

            }
        },
    },
}
</script>
