<template>
    <jet-dialog-modal :show="true" @close="closeModal()">
        <template #content>
            <img alt="Neue Spalte" src="/Svgs/Overlays/illu_budget_edit.svg" class="-ml-6 -mt-8 mb-4"/>
            <XIcon @click="closeModal()" class="text-secondary h-5 w-5 right-0 top-0 mt-8 mr-5 absolute cursor-pointer"
                   aria-hidden="true"/>
            <div class="mx-4">
                <!--   Heading   -->
                <div>
                    <h1 class="my-1 flex">
                        <div class="flex-grow headline1">
                            Neue Spalte
                        </div>
                    </h1>
                    <h2 class="xsLight mb-2 mt-8">
                        Lege eine neue, leere Spalte an. Alternativ kannst du auch eine Funktionsspalte (Summe/Differenz) anlegen.
                    </h2>
                </div>
            </div>
        </template>
    </jet-dialog-modal>

</template>

<script>

import JetDialogModal from "@/Jetstream/DialogModal";
import {XIcon} from '@heroicons/vue/outline';

export default {
    name: 'AddColumnComponent',

    components: {
        JetDialogModal,
        XIcon
    },

    data() {
        return {

        }
    },

    props: [],

    emits: ['closed'],

    watch: {
    },

    methods: {
        openModal() {
        },

        closeModal(bool) {
            this.$emit('closed', bool);
        },
    },
}
</script>

<style scoped></style>
