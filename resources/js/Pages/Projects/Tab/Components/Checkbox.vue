<script>
import axios from 'axios';
import {useProjectDataListener} from "@/Composeables/Listener/useProjectDataListener.js";
import {createSerializedSaver} from "@/Helper/serializedSave.js";
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
            checked: this.data.project_value?.data?.checked ?? false
        }
    },
    mounted() {
        // Bewusst nicht in data(): Listener und Speicher brauchen keine Reaktivität. Getter: Inertia-Besuche
        // mit preserveState ersetzen das data-Prop, die Komponente bleibt gemountet.
        this.dataListener = useProjectDataListener(() => this.data, this.projectId);
        this.dataListener.init();
        // Nacheinander speichern: bei schnellem Doppelklick gewinnt sonst ggf. eine ältere Antwort
        this.checkedSaver = createSerializedSaver({
            send: (checked) => axios.patch(
                route('project.tab.component.update', {
                    project: this.projectId,
                    component: this.data.id
                }),
                { data: { checked } }
            ),
            onSaved: (response) => {
                // Broadcast geht an die anderen – hier und in weiteren Instanzen den gespeicherten Wert übernehmen
                this.dataListener?.saved(response?.data?.project_value);
                // Angezeigt wird, was gespeichert ist (ggf. ein neuerer fremder Stand)
                this.checked = this.storedChecked();
            },
            // Zwischenstand steht schon in der DB: übernehmen, Häkchen (neuerer Wert folgt) nicht anfassen
            onIntermediateSaved: (response) => {
                this.dataListener?.saved(response?.data?.project_value);
            },
            onFailed: (error) => {
                console.error('Fehler beim Aktualisieren:', error);
                // Auf den zuletzt ERFOLGREICH gespeicherten Stand zurück (Zwischenerfolge sind übernommen)
                this.checked = this.storedChecked();
            },
        });
    },
    beforeUnmount() {
        this.dataListener?.stop();
    },
    methods: {
        storedChecked() {
            return Boolean(this.data.project_value?.data?.checked ?? this.data.data?.checked ?? false);
        },
        updateCheckedData() {
            this.checkedSaver.save(this.checked);
        }
    },
    watch: {
        // Auf das Prop selbst hören (nicht auf eine data()-Kopie): ein ersetztes Objekt kommt sonst nicht an
        data: {
            handler: function () {
                // Laufende eigene Speicherung: Häkchen nicht durch ein Live-Update zurücksetzen
                // (danach gleicht onSaved/onFailed mit dem gespeicherten Stand ab)
                if (this.checkedSaver?.isSaving()) return;
                this.checked = this.storedChecked();
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
                <label for="comments" class="componentLabel" :class="{'!text-white': inSidebar}">
                    {{ data.data.label }}
                </label>
            </div>
        </div>

        <InfoButtonComponent :component="component" />
    </div>
</template>

<style scoped>

</style>
