<template>
    <div class="mt-6 bg-lightBackgroundGray">
        <div class="ml-14 pr-14 pt-6">
            <div class="flex flex-col">
                <!-- Description -->
                <div >
                    <div class="sDark">{{ $t('Short description') }}</div>
                    <div v-if="descriptionClicked === false"
                         class="mt-2 subpixel-antialiased xsDark"
                         @click="handleDescriptionClick()" v-html="project.description ? project.description : $t('Click here to add text')">
                    </div>
                    <TextareaComponent
                        v-else
                        v-model="project.description_without_html"
                        @focusout="updateDescription()"
                        :ref="`description-${this.project.id}`"
                        :label="project.description_without_html || $t('Click here to add text')"
                        id="description_without_html"
                        :maxlength="2000"
                    />
                </div>
                <!-- Individual Projectinformation -->
                <div v-for="headline in project.project_headlines" class="mt-7">
                    <div class="sDark" >{{ headline.name }}</div>
                    <div v-if="!headline.clicked" class="mt-2 subpixel-antialiased xsDark"
                         @click="handleTextClick(headline)">
                        <p v-if="headline.text" v-html="headline.text"></p>
                        <p v-else>{{ $t('Click here to add text') }}</p>
                    </div>
                    <TextareaComponent
                        v-else
                        id="text_without_html"
                        v-model="headline.text_without_html" :ref="`text-${headline.id}`"
                        @focusout="changeHeadlineText(headline)"
                        :label="headline.text || $t('Click here to add text')"
                        :max-length="2000"
                    />
                </div>
                <ProjectDocumentsComponent :project="this.project"
                                           :project-write-ids="this.projectWriteIds"
                                           :project-manager-ids="this.projectManagerIds"
                />
            </div>
        </div>
    </div>
</template>

<script>
import JetInputError from "@/Jetstream/InputError.vue";
import {DocumentTextIcon, PencilAltIcon, XIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {XCircleIcon} from "@heroicons/vue/solid";
import Permissions from "@/Mixins/Permissions.vue";
import {nextTick} from "vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import IconLib from "@/Mixins/IconLib.vue";
import ProjectDocumentsComponent from "@/Pages/Projects/Components/ProjectDocumentsComponent.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";

export default{
    components: {
        TextareaComponent,
        ProjectDocumentsComponent,
        ConfirmDeleteModal,
        PencilAltIcon,
        XCircleIcon,
        DocumentTextIcon,
        SvgCollection,
        XIcon,
        JetInputError
    },
    props: [
        'project',
        'projectWriteIds',
        'projectManagerIds',
    ],
    mixins: [Permissions, IconLib],
    data() {
        return{
            descriptionClicked: false,
            project_file: null,
        }
    },
    methods:{
        async handleDescriptionClick() {
            if (this.$can('write projects') || this.$role('artwork admin') || this.$can('admin projects') || this.projectWriteIds.includes(this.$page.props.user.id) || this.projectManagerIds.includes(this.$page.props.user.id) || this.project.isMemberOfADepartment){
                this.descriptionClicked = true;

                await nextTick()

                this.$refs[`description-${this.project.id}`].select();
            }
        },
        async handleTextClick(headline) {
            if (this.$can('write projects') || this.$role('artwork admin') || this.$can('admin projects') || this.projectWriteIds.includes(this.$page.props.user.id) || this.projectManagerIds.includes(this.$page.props.user.id) || this.project.isMemberOfADepartment) {
                headline.clicked = !headline.clicked

                if (headline.clicked) {
                    await nextTick()

                    this.$refs[`text-${headline.id}`][0].select();
                }
            }
        },
        changeHeadlineText(headline) {
            this.$inertia.patch(route('project_headlines.update.text', {
                project_headline: headline.id,
                project: this.project.id
            }), {text: headline.text_without_html}, {
                preserveScroll: true,
                preserveState: true
            })
        },
        updateDescription() {
            this.$inertia.patch(route('projects.update_description', this.project.id), {
                description: this.project.description_without_html
            }, {
                preserveScroll: true,
                preserveState: true
            });
            this.descriptionClicked = false;
        }
    }
}
</script>
