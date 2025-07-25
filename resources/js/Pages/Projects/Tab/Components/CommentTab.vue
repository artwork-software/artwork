<template>
    <div class="mt-6 p-5 bg-lightBackgroundGray">
        <div class="mx-5 mt-6 p-5 max-w-screen-xl bg-lightBackgroundGray">
            <div v-if="this.canEditComponent || ($role('artwork admin') || $can('write projects') || projectWriteIds?.includes(this.$page.props.auth.user.id) || projectManagerIds?.includes(this.$page.props.auth.user.id) || isMemberOfADepartment)" class="relative">
                        <TextareaComponent
                            :label="$t('What should the other project members know about the project?')"
                            v-model="commentForm.text"
                            id="text"
                            :rows="4"
                            :maxlength="5000"
                        />
                <div class="absolute bottom-6 right-2 flex">
                    <div v-if="this.$page.props.show_hints" class="flex mt-1">
                        <span class="hind text-secondary tracking-tight ml-1 my-auto text-xl">{{ $t('Publish information') }}</span>
                        <SvgCollection svgName="smallArrowRight" class="ml-2 mt-1"/>
                    </div>
                    <button @click="addCommentToProject" :disabled="commentForm.text === ''">
                        <IconCircleCheckFilled class="h-8 w-8" :class="[commentForm.text === '' ?
                                '!text-secondary' :
                                '!text-primary hover:!text-primaryHover focus:outline-none',
                            'mr-1 mb-1 rounded-full ml-1 text-sm border border-transparent uppercase shadow-sm text-secondaryHover']"></IconCircleCheckFilled>
                    </button>
                </div>
                <div class="text-xs text-end mt-1 text-artwork-buttons-context">{{ commentForm.text?.length ?? 0 }} / 5000</div>
            </div>

            <div>

                <!-- new comment layout -->

                <div v-if="newCommentList?.length > 0" class="my-6" v-for="comment in newCommentList">
                    <div class="group flex items-center justify-between">
                        <div class="">
                            <div class="flex items-center gap-x-2">
                                <UserPopoverTooltip :user="comment.user" height="9" width="9" :id="comment.id"/>
                                <div class="xsDark">
                                    <div class="xxsLight">
                                        {{ comment.created_at }}
                                    </div>
                                    <div>
                                        {{ comment.user.full_name }} {{ $t('wrote') }}:
                                    </div>
                                </div>
                            </div>
                            <p class="mt-2 mr-14 subpixel-antialiased xsDark font-semibold" v-html="comment.text"></p>
                        </div>
                        <div class="invisible group-hover:visible">
                            <button v-if="$role('artwork admin') || $can('write projects') || projectWriteIds?.includes(this.$page.props.auth.user.id) || projectManagerIds?.includes(this.$page.props.auth.user.id) || isMemberOfADepartment || comment.user?.id === this.$page.props.auth.user.id || canEditComponent" type="button"
                                    @click="deleteCommentFromProject(comment)">
                                <span class="sr-only">{{ $t('Remove comment from project') }}</span>
                                <IconCircleXFilled class="ml-2 h-7 w-7 hover:text-error"/>
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="xsDark mt-6">
                    {{ $t('No comments yet') }}
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
import Permissions from "@/Mixins/Permissions.vue";
import {useForm} from "@inertiajs/vue3";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import IconLib from "@/Mixins/IconLib.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import Button from "@/Jetstream/Button.vue";
import {useCommentListener} from "@/Composeables/Listener/useCommentListener.js";
import {ref} from "vue";

export default {
    components: {
        Button,
        TextareaComponent,
        UserPopoverTooltip,
        CheckIcon,
        UserTooltip,
        PencilAltIcon, XCircleIcon, DocumentTextIcon, SvgCollection, XIcon, JetInputError
    },
    mixins: [Permissions, IconLib],
    props: [
        'project',
        'isMemberOfADepartment',
        'projectWriteIds',
        'projectManagerIds',
        'tab_id',
        'canEditComponent'
    ],
    mounted() {
        const useCommentListener1 = useCommentListener(this.newCommentList, this.project.id);
        useCommentListener1.init();
    },
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
                user_id: this.$page.props.auth.user.id,
                project_id: this.project.id,
                tab_id: this.tab_id ? this.tab_id : null,
            }),
            commentHovered: null,
            newCommentList: ref(this.project.comments)
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
