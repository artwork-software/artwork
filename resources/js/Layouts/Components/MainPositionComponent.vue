<template>
    <th class="p-0"
        :class="mainPosition.verified?.requested === this.$page.props.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED' ? 'bg-buttonBlue' : 'bg-primary'">
        <div class="flex" @mouseover="showMenu = 'MainPosition' + mainPosition.id"
             @mouseout="showMenu = null">
            <div class="pl-2 xsWhiteBold flex w-full items-center h-10"
                 v-if="!mainPosition.clicked">
                <div @click="mainPosition.clicked = !mainPosition.clicked">
                    {{ mainPosition.name }}
                </div>
                <button class="my-auto w-6 ml-3"
                        @click="mainPosition.closed = !mainPosition.closed">
                    <ChevronUpIcon v-if="!mainPosition.closed"
                                   class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                    <ChevronDownIcon v-else
                                     class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                </button>
            </div>
            <div v-else class="flex items-center w-full">
                <input
                    class="my-2 ml-1 xsDark" type="text"
                    v-model="mainPosition.name"
                    @focusout="updateMainPositionName(mainPosition); mainPosition.clicked = !mainPosition.clicked">
                <button class="my-auto w-6 ml-3"
                        @click="mainPosition.closed = !mainPosition.closed">
                    <ChevronUpIcon v-if="!mainPosition.closed"
                                   class="h-6 w-6 text-white my-auto"></ChevronUpIcon>
                    <ChevronDownIcon v-else
                                     class="h-6 w-6 text-white my-auto"></ChevronDownIcon>
                </button>
            </div>
            <div class="flex items-center justify-end">
                <div class="text-white w-28 flex items-center"
                     v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && mainPosition.verified?.requested !== this.$page.props.user.id">
                    <p class="xxsLight">wird verifiziert </p>
                    <!-- TODO: SVG ersetzen mit IMG TAG -->
                    <svg xmlns="http://www.w3.org/2000/svg"
                         xmlns:xlink="http://www.w3.org/1999/xlink" class="ml-1" width="19"
                         height="14.292" viewBox="0 0 19 14.292">
                        <defs>
                            <clipPath id="clip-path">
                                <rect id="Rechteck_458" data-name="Rechteck 458" width="5.138"
                                      height="3.634" fill="#fcfcfb"/>
                            </clipPath>
                        </defs>
                        <path id="Icon_awesome-lock" data-name="Icon awesome-lock"
                              d="M10.692,5.987H10.05V4.063a4.063,4.063,0,1,0-8.126,0V5.987H1.283A1.283,1.283,0,0,0,0,7.27V12.4a1.283,1.283,0,0,0,1.283,1.283h9.409A1.283,1.283,0,0,0,11.975,12.4V7.27A1.283,1.283,0,0,0,10.692,5.987Zm-2.78,0H4.063V4.063a1.925,1.925,0,0,1,3.849,0Z"
                              transform="translate(0 0.607)" fill="#fcfcfb"/>
                        <g id="Gruppe_962" data-name="Gruppe 962"
                           transform="translate(-412 -311)">
                            <g id="Ellipse_147" data-name="Ellipse 147"
                               transform="translate(418 311)" fill="#27233c" stroke="#fcfcfb"
                               stroke-width="1">
                                <circle cx="6.5" cy="6.5" r="6.5" stroke="none"/>
                                <circle cx="6.5" cy="6.5" r="6" fill="none"/>
                            </g>
                            <g id="Gruppe_962-2" data-name="Gruppe 962"
                               transform="translate(423 314.945)" clip-path="url(#clip-path)">
                                <path id="Pfad_1344" data-name="Pfad 1344"
                                      d="M5.1,1.418a.534.534,0,0,0-.7-.286L1.775,2.23,1.029.337a.533.533,0,1,0-.992.39L1.183,3.633,4.811,2.115a.533.533,0,0,0,.286-.7"
                                      transform="translate(0 0)" fill="#fcfcfb"/>
                            </g>
                        </g>
                    </svg>

                </div>
                <div class="text-white w-44 flex items-center text-center cursor-pointer"
                     @click="verifiedMainPosition(mainPosition.verified?.main_position_id)"
                     v-if="mainPosition.verified?.requested === this.$page.props.user.id  && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED'">
                    <p class="xxsLight">Als verifiziert markieren</p>
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" class="ml-1" height="20"
                         viewBox="0 0 20 20">
                        <g id="check_btn" transform="translate(-1234 -671.05)">
                            <g id="Pfad_1370" data-name="Pfad 1370"
                               transform="translate(1234 671.05)" fill="none">
                                <path d="M10,0A10,10,0,1,1,0,10,10,10,0,0,1,10,0Z"
                                      stroke="none"/>
                                <path
                                    d="M 10 1 C 5.037380218505859 1 1 5.037380218505859 1 10 C 1 14.96261978149414 5.037380218505859 19 10 19 C 14.96261978149414 19 19 14.96261978149414 19 10 C 19 5.037380218505859 14.96261978149414 1 10 1 M 10 0 C 15.52285003662109 0 20 4.477149963378906 20 10 C 20 15.52285003662109 15.52285003662109 20 10 20 C 4.477149963378906 20 0 15.52285003662109 0 10 C 0 4.477149963378906 4.477149963378906 0 10 0 Z"
                                    stroke="none" fill="#fcfcfb"/>
                            </g>
                            <path id="Pfad_157" data-name="Pfad 157"
                                  d="M-1151.25,4789.252l3.142,3.142,6.013-6.013"
                                  transform="translate(2390.673 -4108.337)" fill="none"
                                  stroke="#fcfcfb" stroke-width="1.5"/>
                        </g>
                    </svg>
                </div>
                <div class="text-white w-44 flex items-center text-center justify-end mr-2"
                     v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED'">
                    <p class="xxsLight">verifiziert</p>
                    <svg xmlns="http://www.w3.org/2000/svg" width="11.975" height="13.686"
                         class="ml-1" viewBox="0 0 11.975 13.686">
                        <path id="Icon_awesome-lock" data-name="Icon awesome-lock"
                              d="M10.692,5.987H10.05V4.063a4.063,4.063,0,1,0-8.126,0V5.987H1.283A1.283,1.283,0,0,0,0,7.27V12.4a1.283,1.283,0,0,0,1.283,1.283h9.409A1.283,1.283,0,0,0,11.975,12.4V7.27A1.283,1.283,0,0,0,10.692,5.987Zm-2.78,0H4.063V4.063a1.925,1.925,0,0,1,3.849,0Z"
                              fill="#fcfcfb"/>
                    </svg>
                </div>
                <div class="flex flex-wrap w-8">
                    <div class="flex w-full">
                        <Menu as="div" class="my-auto relative"
                              v-show="showMenu === 'MainPosition' + mainPosition.id">
                            <div class="flex">
                                <MenuButton
                                    class="flex">
                                    <DotsVerticalIcon
                                        class="mr-3 flex-shrink-0 h-6 w-6 text-secondaryHover my-auto"
                                        aria-hidden="true"/>
                                </MenuButton>
                            </div>
                            <transition
                                enter-active-class="transition ease-out duration-100"
                                enter-from-class="transform opacity-0 scale-95"
                                enter-to-class="transform opacity-100 scale-100"
                                leave-active-class="transition ease-in duration-75"
                                leave-from-class="transform opacity-100 scale-100"
                                leave-to-class="transform opacity-0 scale-95">
                                <MenuItems
                                    class="origin-top-right absolute right-0 w-80 shadow-lg bg-primary ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none">
                                    <div class="py-1">
                                        <MenuItem v-slot="{ active }"
                                                  v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED'">
                                                                                <span
                                                                                    @click="openVerifiedModal(true, false, mainPosition.id, mainPosition)"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Von User verifizieren lassen
                                                                                </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }"
                                                  v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_CLOSED' && mainPosition.verified?.requested === this.$page.props.user.id">
                                                                                <span
                                                                                    @click="removeVerification(mainPosition, 'main')"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Verifizierung aufheben
                                                                                </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }"
                                                  v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_REQUESTED' && mainPosition.verified?.requested_by === this.$page.props.user.id">
                                                                                <span
                                                                                    @click="requestRemove(mainPosition, 'main')"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Verifizierungsanfrage zurücknehmen
                                                                                </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }"
                                                  v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && !mainPosition.is_fixed">
                                                                                <span
                                                                                    @click="fixMainPosition(mainPosition.id)"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Festschreiben
                                                                                </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }"
                                                  v-if="mainPosition.is_verified === 'BUDGET_VERIFIED_TYPE_NOT_VERIFIED' && mainPosition.is_fixed">
                                                                                <span
                                                                                    @click="unfixMainPosition(mainPosition.id)"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Festschreibung aufheben
                                                                                </span>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                                                                <span
                                                                                    @click="openDeleteMainPositionModal(mainPosition)"
                                                                                    :class="[active ? 'bg-primaryHover text-white' : 'text-secondary', 'cursor-pointer group flex items-center px-4 py-2 text-sm subpixel-antialiased']">
                                                                                    <TrashIcon
                                                                                        class="mr-3 h-5 w-5 text-primaryText group-hover:text-white"
                                                                                        aria-hidden="true"/>
                                                                                    Löschen
                                                                                </span>
                                        </MenuItem>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>
                    </div>
                </div>
            </div>
        </div>
        <div @click="addSubPosition(mainPosition.id)"
             class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
            <div class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                Unterposition
                <PlusCircleIcon
                    class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
            </div>
        </div>
        <table v-if="!mainPosition.closed" class="w-full ">
            <thead class="">
            <tr class="" v-for="(subPosition,subIndex) in mainPosition.sub_positions">
                <SubPositionComponent @openRowDetailModal="openRowDetailModal" @openVerifiedModal="openVerifiedModal" @openCellDetailModal="openCellDetailModal"  @openDeleteModal="openDeleteModal" :main-position="mainPosition" :sub-position="subPosition" :columns="table.columns" :project="project" :table="table"></SubPositionComponent>
            </tr>

            <tr class=" xsWhiteBold flex h-10 w-full text-right"
                :class="mainPosition.verified?.requested === this.$page.props.user.id && mainPosition.is_verified !== 'BUDGET_VERIFIED_TYPE_CLOSED' ? 'bg-buttonBlue' : 'bg-primary'">
                <td class="w-28"></td>
                <td class="w-28"></td>
                <td class="w-72 my-2">SUM</td>
                <div v-if="mainPosition.sub_positions.length > 0" class="w-48 flex items-center"
                     v-for="column in table.columns.slice(3)">
                    <td class="w-48 my-4 p-1"
                        :class="mainPosition.columnSums[column.id] < 0 ? 'text-red-500' : ''">
                        {{
                            mainPosition.columnSums[column.id]?.toLocaleString()
                        }}
                    </td>
                </div>
            </tr>
            </thead>
            <div @click="addMainPosition('BUDGET_TYPE_COST', mainPosition)"
                 class="group bg-secondaryHover cursor-pointer h-1 flex justify-center border-dashed hover:border-t-2 hover:border-buttonBlue">
                <div
                    class="group-hover:block hidden uppercase text-secondaryHover text-sm -mt-8">
                    Hauptposition
                    <PlusCircleIcon
                        class="h-6 w-6 ml-12 text-secondaryHover bg-buttonBlue rounded-full"></PlusCircleIcon>
                </div>
            </div>

        </table>

    </th>
</template>

<confirmation-component
    v-if="showDeleteModal"
    confirm="Löschen"
    :titel="this.confirmationTitle"
    :description="this.confirmationDescription"
    @closed="afterConfirm"/>

<script>



import {PencilAltIcon, PlusCircleIcon, TrashIcon, XCircleIcon, XIcon} from '@heroicons/vue/outline';
import {ChevronUpIcon, ChevronDownIcon, DotsVerticalIcon, CheckIcon} from "@heroicons/vue/solid";
import {Menu, MenuButton, MenuItem, MenuItems} from "@headlessui/vue";
import SubPositionComponent from "@/Layouts/Components/SubPositionComponent.vue";
import {useForm} from "@inertiajs/inertia-vue3";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import {Inertia} from "@inertiajs/inertia";

export default {
    name: "MainPositionComponent",
    components: {
        SubPositionComponent,
        PlusCircleIcon,
        ChevronUpIcon,
        ChevronDownIcon,
        PencilAltIcon,
        TrashIcon,
        XCircleIcon,
        XIcon,
        DotsVerticalIcon,
        CheckIcon,
        Menu,
        MenuItem,
        MenuItems,
        MenuButton,
        ConfirmationComponent
    },
    props: ['mainPosition','table','project'],
    emits:['openDeleteModal'],
    data(){
      return{
          showMenu: null,
          showDeleteModal: false,
          confirmationTitle:'',
          positionToDelete:'',
          mainPositionToDelete: null,
          showSuccessModal: false,
          confirmationDescription: '',
          successHeading: '',
          successDescription: '',
          showVerifiedModal: false,
          positionDefault: {
              position: 0
          },
          verifiedTexts: {
              title: 'Verifizierung',
              positionTitle: '',
              description: 'Sind alle Zahlen richtig kalkuliert? Ist die Kalkulation plausibel? Lasse deine Hauptposition durch eine Nutzer*in verifizieren. '
          },
          submitVerifiedModalData: useForm({
              is_main: false,
              is_sub: false,
              id: null,
              user: '',
              position: [],
              project_title: this.project?.name,
              project_id: this.project?.id,
              table_id: this.table.id,
          }),
          colors: {
              whiteColumn: 'whiteColumn',
              greenColumn: 'greenColumn',
              yellowColumn: 'yellowColumn',
              redColumn: 'redColumn',
              lightGreenColumn: 'lightGreenColumn'
          },

      }
    },
    emit:['openDeleteModal','openVerifiedModal','openRowDetailModal'],
    methods: {
        afterConfirm(bool) {
            if (!bool) return this.showDeleteModal = false;

            this.deletePosition();

        },
        openDeleteModal(title, description, position, type) {
            this.confirmationTitle = title;
            this.confirmationDescription = description
            this.positionToDelete = position;
            this.showDeleteModal = true;
            this.$emit('openDeleteModal', this.confirmationTitle, this.confirmationDescription, this.positionToDelete, type)
        },
        updateMainPositionName(mainPosition) {
            this.$inertia.patch(route('project.budget.main-position.update-name'), {
                mainPosition_id: mainPosition.id,
                mainPositionName: mainPosition.name
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        verifiedMainPosition(mainPositionId) {
            this.$inertia.patch(this.route('project.budget.verified.main-position'), {
                mainPositionId: mainPositionId,
                project_id: this.project?.id,
                table_id: this.table.id,
            })
        },
        openVerifiedModal(is_main,is_sub,id,position) {
            this.verifiedTexts.positionTitle = position.name
            this.submitVerifiedModalData.is_main = is_main
            this.submitVerifiedModalData.is_sub = is_sub
            this.submitVerifiedModalData.id = id
            this.submitVerifiedModalData.position = position
            this.showVerifiedModal = true
            this.$emit('openVerifiedModal',this.submitVerifiedModalData.is_main, this.submitVerifiedModalData.is_sub,this.submitVerifiedModalData.id,this.submitVerifiedModalData.position)
        },
        removeVerification(position, type){
            this.$inertia.post(this.route('project.budget.remove.verification'), {
                position: position,
                type: type
            })
        },
        requestRemove(position, type){
            this.$inertia.post(this.route('project.budget.take-back.verification'), {
                position: position,
                type: type
            })
        },
        openDeleteMainPositionModal(mainPosition) {
            this.confirmationTitle = 'Hauptposition löschen';
            this.confirmationDescription = 'Bist du sicher, dass du die Hauptposition ' + mainPosition.name + ' löschen möchtest?'
            this.mainPositionToDelete = mainPosition;
            this.showDeleteModal = true;
            this.$emit('openDeleteModal', this.confirmationTitle, this.confirmationDescription, this.mainPositionToDelete, 'main')
        },
        addSubPosition(mainPositionId, subPosition = null) {

            let subPositionBefore = subPosition

            if (!subPositionBefore) {
                subPositionBefore = {
                    position: 0
                }
            }

            this.$inertia.post(route('project.budget.sub-position.add'), {
                table_id: this.table.id,
                main_position_id: mainPositionId,
                positionBefore: subPositionBefore.position
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        addMainPosition(type, mainPosition) {
            this.$inertia.post(route('project.budget.main-position.add'), {
                table_id: this.table.id,
                type: type,
                positionBefore: mainPosition.position
            }, {
                preserveScroll: true,
                preserveState: true
            });
        },
        openRowDetailModal(row){
            this.$emit('openRowDetailModal',row)
        },
        openCellDetailModal(column) {
            this.$emit('openCellDetailModal',column)
        },
        fixMainPosition(mainPositionId){
            this.$inertia.patch(this.route('project.budget.fix.main-position'), {
                mainPositionId: mainPositionId
            })
        },
        unfixMainPosition(mainPositionId){
            this.$inertia.patch(this.route('project.budget.unfix.main-position'), {
                mainPositionId: mainPositionId
            })
        }
    },

}
</script>

<style scoped>

</style>
