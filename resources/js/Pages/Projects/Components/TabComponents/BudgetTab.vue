<template>
    <div class="mx-5 mt-6 p-5  bg-lightBackgroundGray">
        <div class="grid grid-cols-6 mr-8">
            <div class="col-span-4">
                <!-- Description -->
                <div class="mt-4">
                    <div> Kurzbeschreibung</div>
                    <div v-if="descriptionClicked === false"
                         class="mt-2 subpixel-antialiased text-secondary"
                         @click="handleDescriptionClick()">
                        {{
                            project.description ? project.description : 'Hier klicken um Text hinzuzufügen'
                        }}
                    </div>
                    <textarea v-else v-model="project.description" type="text"
                              @focusout="updateDescription()"
                              :ref="`description-${this.project.id}`"
                              class="w-full border-gray-300 text-primary h-40"
                              :placeholder="project.description || 'Hier klicken um Text hinzuzufügen'"/>
                </div>
                <!-- Individual Projectinformation -->
                <div v-for="headline in project.project_headlines" class="mt-4">
                    <div>{{ headline.name }}</div>
                    <div v-if="!headline.clicked" class="mt-2 subpixel-antialiased text-secondary"
                         @click="handleTextClick(headline)">
                        {{ headline.text ? headline.text : 'Hier klicken um Text hinzuzufügen' }}
                    </div>
                    <textarea v-else v-model="headline.text" type="text" :ref="`text-${headline.id}`"
                              @focusout="changeHeadlineText(headline)"
                              class="w-full border-gray-300 text-primary h-40"
                              :placeholder="headline.text || 'Hier klicken um Text hinzuzufügen'"/>
                </div>
            </div>
            <div
                v-if="$can('write projects') || $role('artwork admin') || $can('admin projects') || projectCanWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id) || isMemberOfADepartment"
                class="col-span-2">
                <div class="ml-10 group">
                    <label class="block my-4 sDark">
                        Key Visual </label>
                    <div
                        class="flex col-span-2 w-full justify-center border-2 bg-stone-50 border-gray-300 cursor-pointer border-dashed rounded-md p-2"
                        @dragover.prevent
                        @drop.stop.prevent="uploadDraggedKeyVisual($event)"
                        @click="selectNewKeyVisual"
                        v-if="this.project.key_visual_path === null">
                        <div class="space-y-1 text-center">
                            <div class="xsLight flex my-auto h-40 items-center"
                                 v-if="this.project.key_visual_path === null">
                                Ziehe hier dein <br/> Key Visual hin
                                <input id="keyVisual-upload" ref="keyVisual"
                                       name="file-upload" type="file" class="sr-only"
                                       @change="updateKeyVisual"/>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex items-center justify-center relative w-full">
                        <div
                            class="absolute !gap-4 w-full text-center flex items-center justify-center hidden group-hover:block">
                            <button @click="downloadKeyVisual" type="button"
                                    class="mr-3 inline-flex rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                <SvgCollection svg-name="ArrowDownTray" class="h-5 w-5" aria-hidden="true"/>
                            </button>
                            <button @click="selectNewKeyVisual" type="button"
                                    class="mr-3 inline-flex rounded-full bg-indigo-600 p-1 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                <PencilAltIcon
                                    class="h-5 w-5 text-primaryText group-hover:text-white"
                                    aria-hidden="true"/>
                            </button>
                            <button @click="deleteKeyVisual" type="button"
                                    class="inline-flex rounded-full bg-red-600 p-1 text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                                <XIcon class="h-5 w-5 text-primaryText group-hover:text-white"
                                       aria-hidden="true"/>
                            </button>
                        </div>
                        <div class="text-center">
                            <div class="cursor-pointer">
                                <img src="">
                                <img :src="'/storage/keyVisual/' + this.project.key_visual_path"
                                     alt="Aktuelles Key-Visual"
                                     class="rounded-md w-full h-48">
                                <input id="keyVisual-upload" ref="keyVisual"
                                       name="file-upload" type="file" class="sr-only"
                                       @change="updateKeyVisual"/>
                            </div>
                        </div>
                    </div>
                    <div class="flex w-full items-center my-4">
                        <h3 class="sDark"> Dokumente </h3>
                    </div>
                    <div
                        v-if="$role('artwork admin') || projectCanWriteIds.includes(this.$page.props.user.id)">
                        <input
                            @change="uploadChosenDocuments"
                            class="hidden"
                            ref="project_files"
                            id="file"
                            type="file"
                            multiple
                        />
                        <div @click="selectNewFiles" @dragover.prevent
                             @drop.stop.prevent="uploadDraggedDocuments($event)" class="mb-4 w-full flex justify-center items-center
                        border-buttonBlue border-dotted border-2 h-40 bg-colorOfAction p-2 cursor-pointer">
                            <p class="text-buttonBlue font-bold text-center">Dokument zum Upload hierher
                                ziehen
                                <br>oder ins Feld klicken
                            </p>
                        </div>
                        <jet-input-error :message="uploadDocumentFeedback"/>
                    </div>
                    <div>
                        <div class="space-y-1"
                             v-if="$role('artwork admin') || projectCanWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)">
                            <div v-for="project_file in project.project_files"
                                 class="cursor-pointer group flex items-center">
                                <div :data-tooltip-target="project_file.name" class="flex truncate">
                                    <DocumentTextIcon class="h-5 w-5 flex-shrink-0" aria-hidden="true"/>
                                    <p @click="downloadFile(project_file)" class="ml-2 truncate">
                                        {{ project_file.name }}</p>

                                    <XCircleIcon
                                        v-if="$role('artwork admin') || projectCanWriteIds.includes(this.$page.props.user.id) || projectManagerIds.includes(this.$page.props.user.id)"
                                        @click="openConfirmDeleteModal(project_file)"
                                        class="ml-2 my-auto hidden group-hover:block h-5 w-5 flex-shrink-0 text-error"
                                        aria-hidden="true"/>
                                </div>
                                <div :id="project_file.name" role="tooltip"
                                     class="max-w-md inline-block flex flex-wrap absolute invisible z-10 py-3 px-3 text-sm font-medium text-secondary bg-primary shadow-sm opacity-0 transition-opacity duration-300 tooltip">
                                    <div class="flex flex-wrap">
                                        Um die Datei herunterzuladen, klicke auf den Dateinamen
                                    </div>
                                    <div class="tooltip-arrow" data-popper-arrow></div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="xsDark">
                            Keine Dateien vorhanden
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</template>

<script>


import JetInputError from "@/Jetstream/InputError.vue";
import {DocumentTextIcon, PencilAltIcon, XIcon} from "@heroicons/vue/outline";
import SvgCollection from "@/Layouts/Components/SvgCollection.vue";
import {XCircleIcon} from "@heroicons/vue/solid";

export default{
    components: {PencilAltIcon, XCircleIcon, DocumentTextIcon, SvgCollection, XIcon, JetInputError},
    props: [
        'project',
    ],
    data() {

    },
    methods:{

    }
}
</script>

<style scoped>

</style>
