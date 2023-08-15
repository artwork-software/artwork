<template>
    <div class="mt-6 p-5  bg-lightBackgroundGray">
        <div
            class="mx-5 mt-6 p-5 max-w-screen-xl bg-lightBackgroundGray">
            <div
                v-if="$role('artwork admin') || $can('write projects') || projectWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id) || isMemberOfADepartment"
                class="relative border-2 hover:border-gray-400 w-full bg-white border-gray-300">
                        <textarea
                            placeholder="Was sollten die anderen Projektmitglieder über das Projekt wissen?"
                            v-model="commentForm.text" rows="4"
                            class="resize-none focus:outline-none focus:ring-0  pt-3 mb-8 placeholder-secondary border-0  w-full"/>
                <div class="absolute bottom-0 right-0 flex bg-white">
                    <div v-if="$page.props.can.show_hints" class="flex mt-1">
                                <span
                                    class="hind text-secondary tracking-tight ml-1 my-auto text-xl">Information veröffentlichen</span>
                        <SvgCollection svgName="smallArrowRight" class="ml-2 mt-1"/>
                    </div>
                    <button
                        :class="[commentForm.text === '' ? 'bg-secondary': 'bg-primary hover:bg-primaryHover focus:outline-none', ' mr-1 mb-1 rounded-full mt-2 ml-1 text-sm p-1 border border-transparent uppercase shadow-sm text-secondaryHover']"
                        @click="addCommentToProject" :disabled="commentForm.text === ''">
                        <CheckIcon class="h-4 w-4"></CheckIcon>
                    </button>
                </div>
            </div>
            <div>
                <div v-if="sortedComments?.length > 0" class="my-6" v-for="comment in sortedComments"
                     @mouseover="commentHovered = comment.id"
                     @mouseout="commentHovered = null">
                    <div class="flex justify-between">
                        <div class="flex items-center">
                            <img v-if="comment.user" :data-tooltip-target="comment.user"
                                 :src="comment.user.profile_photo_url" :alt="comment.user.name"
                                 class="rounded-full h-7 w-7 object-cover"/>
                            <UserTooltip v-if="comment.user" :user="comment.user"/>
                            <div class="ml-2 text-secondary"
                                 :class="commentHovered === comment.id ? 'text-primary':'text-secondary'">
                                {{ comment.created_at }}
                            </div>
                        </div>
                        <button v-show="commentHovered === comment.id" type="button"
                                @click="deleteCommentFromProject(comment)">
                            <span class="sr-only">Kommentar von Projekt entfernen</span>
                            <XCircleIcon class="ml-2 h-7 w-7 hover:text-error"/>
                        </button>
                    </div>
                    <div class="mt-2 mr-14 subpixel-antialiased text-primary font-semibold">
                        {{ comment.text }}
                    </div>
                </div>
                <div v-else class="xsDark mt-6">
                    Noch keine Kommentare vorhanden
                </div>
            </div>
        </div>
    </div>
</template>

<script>


import JetInputError from "@/Jetstream/InputError.vue";
import {DocumentTextIcon, PencilAltIcon, XIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {CheckIcon, XCircleIcon} from "@heroicons/vue/solid";
import UserTooltip from "@/Layouts/Components/UserTooltip.vue";
import Permissions from "@/mixins/Permissions.vue";
import {useForm} from "@inertiajs/inertia-vue3";

export default {
    components: {
        CheckIcon,
        UserTooltip,
        PencilAltIcon, XCircleIcon, DocumentTextIcon, SvgCollection, XIcon, JetInputError
    },
    mixins: [Permissions],
    props: [
        'project',
        'isMemberOfADepartment',
        'projectWriteIds',
        'projectManagerIds',
    ],
    computed:{
        sortedComments: function () {
            let commentCopy = this.project.comments.slice();

            function compare(a, b) {
                if (b.created_at === null) {
                    return -1;
                }
                if (a.created_at === null) {
                    return 1;
                }
                if (a.created_at < b.created_at)
                    return 1;
                if (a.created_at > b.created_at)
                    return -1;
                return 0;
            }

            return commentCopy.sort(compare);
        },
    },
    data() {
        return{
            commentForm: useForm({
                text: "",
                user_id: this.$page.props.user.id,
                project_id: this.project.id
            }),
            commentHovered: null,
        }
    },
    methods: {
        addCommentToProject() {
            this.commentForm.post(route('comments.store'), {preserveState: true, preserveScroll: true});
            this.commentForm.text = "";
        },
        deleteCommentFromProject(comment) {
            this.$inertia.delete(`/comments/${comment.id}`, {preserveState: true, preserveScroll: true});
        },
    }
}
</script>

<style scoped>

</style>
