<script>
export default class {
    eventId = null;
    timelinesAndShifts = null;
    eventContainerIdentifier = null;
    timelineIdentifier = null;
    shiftIdentifier = null;
    elementsHeightInPixelsPerMinute = null;
    elementsHeaderHeight = null;
    defaultMarginToAdd = 16;

    constructor(
        eventId,
        timelinesAndShifts,
        eventContainerIdentifier,
        timelineIdentifier,
        shiftIdentifier,
        elementsHeightInPixelsPerMinute,
        elementsHeaderHeight
    ) {
        if (
            !timelinesAndShifts ||
            !eventContainerIdentifier ||
            !timelineIdentifier ||
            !shiftIdentifier ||
            !elementsHeightInPixelsPerMinute ||
            !elementsHeaderHeight
        ) {
            throw new Error('Not all parameters provided!');
        }

        this.eventId = eventId;
        this.timelinesAndShifts = timelinesAndShifts;
        this.eventContainerIdentifier = eventContainerIdentifier;
        this.timelineIdentifier = timelineIdentifier;
        this.shiftIdentifier = shiftIdentifier;
        this.elementsHeightInPixelsPerMinute = elementsHeightInPixelsPerMinute;
        this.elementsHeaderHeight = elementsHeaderHeight;
    }

    initialize() {
        if (this.timelinesAndShifts.length === 0) {
            return;
        }

        let overlayDiv = this.addOverlayDiv()

        this.calculateHeights();
        this.calculateMargins();

        this.removeOverlayDiv(overlayDiv);
    }

    reinitialize() {
        const eventContainer = this.getEventContainer(),
            overlayDiv = this.addOverlayDiv();

        //remove height styles from containers causing the browser to recalculate the elements height
        eventContainer.querySelectorAll('[id^=' + this.shiftIdentifier + ']')
            .forEach((shiftContainer) => { shiftContainer.style.height = null; });
        eventContainer.querySelectorAll('[id^=' + this.timelineIdentifier + ']')
            .forEach((timelineContainer) => { timelineContainer.style.height = null; });

        this.removeOverlayDiv(overlayDiv);
        this.initialize();
    }

    addOverlayDiv() {
        //just in case the operation takes some time
        const overlayDiv = document.createElement('div'),
            overlayText = document.createElement('p');

        overlayDiv.id = 'overlayDiv';
        overlayDiv.className = 'fixed inset-0 bg-black bg-opacity-20 flex items-center justify-center z-50';
        overlayText.className = 'text-white text-center';
        overlayText.innerText = 'Ansicht wird aktualisiert...'; //translation

        overlayDiv.appendChild(overlayText);
        document.body.appendChild(overlayDiv);

        return overlayDiv;
    }

    removeOverlayDiv(overlayDiv) {
        document.body.removeChild(overlayDiv);
    }

    getSortedTimelinesAndShifts() {
        return this.timelinesAndShifts.sort(
            (a, b) => {
                let dateA = Object.keys(a).includes('shift_uuid') ?
                        this.parseShiftDateFromDateAndTime(a.start_date, a.start) :
                        this.parseTimelineDateFromDateAndTime(a.start_date, a.start),
                    dateB = Object.keys(b).includes('shift_uuid') ?
                        this.parseShiftDateFromDateAndTime(b.start_date, b.start) :
                        this.parseTimelineDateFromDateAndTime(b.start_date, b.start);

                return dateA - dateB;
            }
        );
    }

    calculateHeights() {
        this.getSortedTimelinesAndShifts().forEach((element) => {
            let isShiftObject = this.isShiftObject(element),
                domElement = this.getEventContainer().querySelector(
                    isShiftObject ?
                        '#' + this.shiftIdentifier + this.eventId + '-' + element.id :
                        '#' + this.timelineIdentifier + this.eventId + '-' + element.id
                ),
                startDate = isShiftObject ?
                    this.parseShiftDateFromDateAndTime(element.start_date, element.start) :
                    this.parseTimelineDateFromDateAndTime(element.start_date, element.start),
                endDate = isShiftObject ?
                    this.parseShiftDateFromDateAndTime(element.end_date, element.end) :
                    this.parseTimelineDateFromDateAndTime(element.end_date, element.end);

            isShiftObject ?
                this.setShiftContainerHeight(domElement, startDate, endDate) :
                this.setTimelineContainerHeight(domElement, startDate, endDate);
        });
    }

    calculateMargins() {
        this.getSortedTimelinesAndShifts().forEach((currentTimelineOrShift, index) => {
            if (index === 0) {
                //no margin for first item
                return;
            }

            let currentIsShiftObject = this.isShiftObject(currentTimelineOrShift);
            let element = this.getTimelineOrShiftElement(currentTimelineOrShift);
            let previousTimelineOrShift = this.timelinesAndShifts[(index - 1)];
            let previousTimelineOrShiftElement = this.getTimelineOrShiftElement(previousTimelineOrShift);
            let previousIsShiftObject = this.isShiftObject(previousTimelineOrShift);

            //handle timeline after timeline placement
            if (!currentIsShiftObject && !previousIsShiftObject) {
                //if timeline after timeline is placed we just need to add 2 pixel margin top and calculate hint
                let diff = this.getDiffToPreviousElementsEndDate(currentTimelineOrShift, previousTimelineOrShift);

                if (diff <= 0) {
                    //set margin top to 2px if there is no diff to previous
                    this.setElementMarginTop(element, 2);
                    return;
                }

                this.handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift)
                return;
            }

            //handle timeline after shift placement
            if (!currentIsShiftObject && previousIsShiftObject) {
                let marginTop = this.getElementMarginTop(previousTimelineOrShiftElement);

                if (this.startsIdentical(previousTimelineOrShift, currentTimelineOrShift)) {
                    this.getPreviousTimelines(index).forEach(
                        (previousTimeline) => {
                            let tmp = this.getTimelineOrShiftElement(previousTimeline);
                            marginTop -= this.getElementHeight(tmp);
                            marginTop -= this.getElementMarginTop(tmp);
                        }
                    );

                    if (marginTop > 0) {
                        marginTop -= this.elementsHeaderHeight;
                    }

                    this.setElementMarginTop(element, marginTop);
                    return;
                }

                //find same starting elements as previous element
                let previousSameStarting = this.getPreviousTimelinesOrShiftsStartingIdentical(
                    previousTimelineOrShift,
                    //provide index of previous element
                    (index - 1)
                );

                let desiredHeightForCalculation = this.getElementHeight(previousTimelineOrShiftElement) +
                    this.defaultMarginToAdd;

                if (previousSameStarting.length > 1) {
                    //the previousTimelineOrShift is included in the result set, if we have more
                    //than one entry we need to determine the biggest container for proper placement
                    previousSameStarting.forEach(
                        (element) => {
                            let elementHeight = this.getElementHeight(this.getTimelineOrShiftElement(element));

                            if (elementHeight > (desiredHeightForCalculation - this.defaultMarginToAdd)) {
                                previousTimelineOrShift = element;
                                previousTimelineOrShiftElement = this.getTimelineOrShiftElement(element);
                                desiredHeightForCalculation = elementHeight + this.defaultMarginToAdd;
                            }
                        }
                    )
                }

                let diffToPreviousHandling = false;
                if (this.startsInBetween(previousTimelineOrShift, currentTimelineOrShift)) {
                    //placement depending on start dates difference (current starts before previous ends)
                    marginTop += (
                        this.getDiffToPreviousElementsStartDate(
                            currentTimelineOrShift,
                            previousTimelineOrShift
                        ) * this.getElementsHeightInPixelPerMinuteDependantOnShift(previousTimelineOrShift)
                    );
                } else {
                    //placement depending on previous (biggest) container height as timeline is starting after previous
                    //shift
                    marginTop += desiredHeightForCalculation;
                    diffToPreviousHandling = true;
                }

                marginTop -= this.elementsHeaderHeight;

                this.getPreviousTimelines(index).forEach(
                    (previousTimeline) => {
                        let tmp = this.getTimelineOrShiftElement(previousTimeline);
                        marginTop -= this.getElementHeight(tmp);
                        marginTop -= this.getElementMarginTop(tmp);
                    }
                );

                this.setElementMarginTop(element, marginTop);
                if (diffToPreviousHandling) {
                    this.handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift);
                }
                return;
            }

            //handle shift after timeline placement
            if (currentIsShiftObject && !previousIsShiftObject) {
                if ((index - 1) === 0) {
                    //previous element also was first element
                    let desiredHeightForCalculation = 0;
                    let diffToPreviousHandling = false;

                    if (this.startsInBetween(previousTimelineOrShift, currentTimelineOrShift)) {
                        //placement depending on start dates difference (current starts before previous ends)
                        desiredHeightForCalculation = (
                            this.getDiffToPreviousElementsStartDate(
                                currentTimelineOrShift,
                                previousTimelineOrShift
                            ) * this.getElementsHeightInPixelPerMinuteDependantOnTimeline(previousTimelineOrShift)
                        ) + 8; //(+8px margin from event heading bar)
                    } else {
                        desiredHeightForCalculation = this.getElementHeight(previousTimelineOrShiftElement) +
                            this.defaultMarginToAdd;
                        diffToPreviousHandling = true;
                    }

                    this.setElementMarginTop(
                        element,
                        desiredHeightForCalculation +
                        this.getElementMarginTop(previousTimelineOrShiftElement) +
                        this.elementsHeaderHeight
                    );
                    if (diffToPreviousHandling) {
                        this.handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift);
                    }
                    return;
                }

                //if start same as previous timeline placed on same height
                //margin = (margins of all previous timelines + heights)
                if (this.startsIdentical(previousTimelineOrShift, currentTimelineOrShift)) {
                    let marginTop = this.elementsHeaderHeight +
                        this.getElementMarginTop(previousTimelineOrShiftElement);

                    this.getPreviousTimelines(index).forEach(
                        (previousTimeline) => {
                            let tmp = this.getTimelineOrShiftElement(previousTimeline);
                            marginTop -= this.getElementMarginTop(tmp);
                            marginTop -= this.getElementHeight(tmp);
                        }
                    )

                    this.setElementMarginTop(element, marginTop);
                    return;
                }

                //find same starting elements as previous element
                let previousSameStarting = this.getPreviousTimelinesOrShiftsStartingIdentical(
                    previousTimelineOrShift,
                    //provide index of previous element
                    (index - 1)
                );

                if (previousSameStarting.length > 1) {
                    //determine biggest container
                    let marginTop = 0;
                    let replacedByShift = false;

                    previousSameStarting.forEach(
                        (element) => {
                            let elementHeight = this.getElementHeight(this.getTimelineOrShiftElement(element));

                            if (elementHeight > (marginTop - this.defaultMarginToAdd)) {
                                replacedByShift = this.isShiftObject(element);
                                previousTimelineOrShift = element;
                                previousTimelineOrShiftElement = this.getTimelineOrShiftElement(element);
                                marginTop = elementHeight + this.defaultMarginToAdd;
                            }
                        }
                    );

                    if (replacedByShift) {
                        if (this.startsInBetween(previousTimelineOrShift, currentTimelineOrShift)) {
                            //placement depending on start dates difference (current starts before previous ends)
                            marginTop = (
                                this.getDiffToPreviousElementsStartDate(
                                    currentTimelineOrShift,
                                    previousTimelineOrShift
                                ) * this.getElementsHeightInPixelPerMinuteDependantOnShift(previousTimelineOrShift)
                            );
                        }
                        this.setElementMarginTop(
                            element,
                            this.getElementMarginTop(previousTimelineOrShiftElement) + marginTop
                        );

                        return;
                    }

                    if (this.startsInBetween(previousTimelineOrShift, currentTimelineOrShift)) {
                        this.getPreviousTimelines((index - 1)).forEach(
                            (previousTimeline) => {
                                let tmp = this.getTimelineOrShiftElement(previousTimeline);
                                marginTop += this.getElementMarginTop(tmp);
                                marginTop += this.getElementHeight(tmp);
                            }
                        );
                        marginTop += this.elementsHeaderHeight;
                        marginTop -= this.getElementHeight(previousTimelineOrShiftElement);
                        marginTop -= this.defaultMarginToAdd;
                        marginTop += (
                            this.getDiffToPreviousElementsStartDate(
                                currentTimelineOrShift,
                                previousTimelineOrShift
                            ) * this.getElementsHeightInPixelPerMinuteDependantOnTimeline(previousTimelineOrShift)
                        ) + 8; //(+8px margin from event heading bar)

                        this.setElementMarginTop(element, marginTop);
                        return;
                    }

                    //now we have the desired height, we will now get all timelines except the one
                    //which is the previous object of the current shift to be placed
                    marginTop += this.elementsHeaderHeight;
                    this.getPreviousTimelines((index - 1)).forEach(
                        (previousTimeline) => {
                            let tmp = this.getTimelineOrShiftElement(previousTimeline);
                            marginTop += this.getElementMarginTop(tmp);
                            marginTop += this.getElementHeight(tmp);
                        }
                    );

                    this.setElementMarginTop(element, marginTop);
                    this.handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift);

                    return;
                }

                let marginTop = this.elementsHeaderHeight;

                if (this.startsInBetween(previousTimelineOrShift, currentTimelineOrShift)) {
                    marginTop += this.getElementMarginTop(previousTimelineOrShiftElement);
                    marginTop += (
                        this.getDiffToPreviousElementsStartDate(
                            currentTimelineOrShift,
                            previousTimelineOrShift
                        ) * this.getElementsHeightInPixelPerMinuteDependantOnTimeline(previousTimelineOrShift)
                    ) + 8; //(+8px margin from event heading bar)

                    this.setElementMarginTop(element, marginTop);
                    return;
                }

                //determine all previous margins and container heights + elementsHeaderHeight
                this.getPreviousTimelines(index).forEach(
                    (previousTimeline) => {
                        let tmp = this.getTimelineOrShiftElement(previousTimeline);
                        marginTop += this.getElementMarginTop(tmp);
                        marginTop += this.getElementHeight(tmp);
                    }
                );
                marginTop += this.defaultMarginToAdd;

                this.setElementMarginTop(element, marginTop);
                this.handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift);
                return;
            }

            //handle shift after shift placement
            if (currentIsShiftObject && previousIsShiftObject) {
                if (this.startsIdentical(previousTimelineOrShift, currentTimelineOrShift)) {
                    this.setElementMarginTop(element, this.getElementMarginTop(previousTimelineOrShiftElement));

                    return;
                }

                //find same starting elements as previous element
                let previousSameStarting = this.getPreviousTimelinesOrShiftsStartingIdentical(
                    previousTimelineOrShift,
                    //provide index of previous element
                    (index - 1)
                );

                //get height of previous container
                let desiredElementHeight = this.getElementHeight(previousTimelineOrShiftElement) +
                    this.defaultMarginToAdd;

                //if previous shift is starting in between its previous timeline element
                //placement is depending on previous shift previous timeline element
                let previousTimelineOrShiftOfPreviousIndex = index - 2;
                if (previousTimelineOrShiftOfPreviousIndex >= 0) {
                    let previousTimelineOrShiftOfPrevious = this.timelinesAndShifts[(index - 2)];

                    if (
                        !this.isShiftObject(previousTimelineOrShiftOfPrevious) &&
                        this.startsInBetween(previousTimelineOrShiftOfPrevious, previousTimelineOrShift)
                    ) {
                        previousTimelineOrShift = previousTimelineOrShiftOfPrevious;
                        previousTimelineOrShiftElement = this.getTimelineOrShiftElement(previousTimelineOrShiftOfPrevious);
                        let marginTop = this.getElementHeight(
                            this.getTimelineOrShiftElement(previousTimelineOrShiftOfPrevious)
                        ) + this.defaultMarginToAdd;

                        marginTop += this.elementsHeaderHeight;
                        this.getPreviousTimelines((previousTimelineOrShiftOfPrevious - 1)).forEach(
                            (previousTimeline) => {
                                let tmp = this.getTimelineOrShiftElement(previousTimeline);
                                marginTop += this.getElementMarginTop(tmp);
                                marginTop += this.getElementHeight(tmp);
                            }
                        );

                        this.setElementMarginTop(element, marginTop);
                        this.handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift);

                        return;
                    }
                }

                //check if any of the previous containers is bigger
                if (previousSameStarting.length > 1) {
                    previousSameStarting.forEach(
                        (element) => {
                            let elementHeight = this.getElementHeight(this.getTimelineOrShiftElement(element));

                            if (elementHeight > (desiredElementHeight - this.defaultMarginToAdd)) {
                                previousTimelineOrShift = element;
                                previousTimelineOrShiftElement = this.getTimelineOrShiftElement(element);
                                desiredElementHeight = elementHeight + this.defaultMarginToAdd;
                            }
                        }
                    );
                }

                if (this.startsInBetween(previousTimelineOrShift, currentTimelineOrShift)) {
                    //placement depending on start dates difference (current starts before previous ends)
                    desiredElementHeight = (
                        this.getDiffToPreviousElementsStartDate(
                            currentTimelineOrShift,
                            previousTimelineOrShift
                        ) * this.getElementsHeightInPixelPerMinuteDependantOnShift(previousTimelineOrShift)
                    );
                    this.setElementMarginTop(
                        element,
                        this.getElementMarginTop(previousTimelineOrShiftElement) +
                        desiredElementHeight
                    );
                    return;
                }

                this.setElementMarginTop(
                    element,
                    this.getElementMarginTop(previousTimelineOrShiftElement) +
                    desiredElementHeight
                );
                this.handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift);
            }
        });
    }

    handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift) {
        let diff = this.getDiffToPreviousElementsEndDate(currentTimelineOrShift, previousTimelineOrShift);

        if (diff <= 0) {
            return;
        }

        let existingHintElement = element.querySelector('.shiftPlanTimeHint'),
            hintElement,
            innerTextTime = diff < 60 ?
                diff + ' Min.' :
                '~' + Math.round(diff / 60) + ' Std.';

        if (!existingHintElement) {
            hintElement = document.createElement('span');
            hintElement.classList.add('shiftPlanTimeHint', 'w-full', 'text-gray-500', 'text-center', 'absolute');
            hintElement.style.fontSize = '10px';
            hintElement.style.top = '-16px';
        } else {
            hintElement = existingHintElement;
        }

        /*
        hintElement.innerText = '--- Ungenutzt: ' + innerTextTime + ' ---';
        */

        if (!existingHintElement) {
            //if previous is a timeline and current too we need to add a margin top for diff hint placement
            if (!this.isShiftObject(currentTimelineOrShift) && !this.isShiftObject(previousTimelineOrShift)) {
                element.style.marginTop = '16px';
            }

            //hint element was just created, we need to insert it
            element.insertBefore(hintElement, element.firstChild);
        }

        //if there is a diff we return true
        return true;
    }

    getElementHeight(element) {
        return element.getBoundingClientRect().height;
    }

    setElementHeight(element, height) {
        element.style.height = height + 'px';
    }

    setElementMarginTop(element, marginTop) {
        element.style.marginTop = marginTop + 'px';
    }

    getElementMarginTop(element) {
        return Number(element.style.marginTop.replace('px', ''));
    }

    getElementsHeightInPixelPerMinuteDependantOnShift(shift) {
        let startDate = this.parseShiftDateFromDateAndTime(shift.start_date, shift.start);
        let endDate = this.parseShiftDateFromDateAndTime(shift.end_date, shift.end);
        let element = this.getTimelineOrShiftElement(shift);

        let recalculateHeightInPixelPerMinute = (
            this.getDifferenceInMinutes(startDate, endDate) *
            this.elementsHeightInPixelsPerMinute +
            this.elementsHeaderHeight
        ) < this.getElementHeight(element);

        if (!recalculateHeightInPixelPerMinute) {
            return this.elementsHeightInPixelsPerMinute;
        }

        return this.getElementHeight(element) / this.getDifferenceInMinutes(startDate, endDate);
    }

    getElementsHeightInPixelPerMinuteDependantOnTimeline(timeline) {
        let startDate = this.parseShiftDateFromDateAndTime(timeline.start_date, timeline.start);
        let endDate = this.parseShiftDateFromDateAndTime(timeline.end_date, timeline.end);
        let element = this.getTimelineOrShiftElement(timeline);

        let recalculateHeightInPixelPerMinute = (
            this.getDifferenceInMinutes(startDate, endDate) *
            this.elementsHeightInPixelsPerMinute +
            this.elementsHeaderHeight
        ) < this.getElementHeight(element);

        if (!recalculateHeightInPixelPerMinute) {
            return this.elementsHeightInPixelsPerMinute;
        }

        return this.getElementHeight(element) / this.getDifferenceInMinutes(startDate, endDate);
    }

    getDiffToPreviousElementsEndDate(timelineOrShift, previousTimelineOrShift) {
        let previousEndDate = this.isShiftObject(previousTimelineOrShift) ?
                this.parseShiftDateFromDateAndTime(previousTimelineOrShift.end_date, previousTimelineOrShift.end) :
                this.parseTimelineDateFromDateAndTime(previousTimelineOrShift.end_date, previousTimelineOrShift.end),
            startDate = this.isShiftObject(timelineOrShift) ?
                this.parseShiftDateFromDateAndTime(timelineOrShift.start_date, timelineOrShift.start) :
                this.parseTimelineDateFromDateAndTime(timelineOrShift.start_date, timelineOrShift.start);
        //@todo: diff in days und dann 24h pro tag abziehen
        return this.getDifferenceInMinutes(previousEndDate, startDate);
    }

    getDiffToPreviousElementsStartDate(timelineOrShift, previousTimelineOrShift) {
        let previousEndDate = this.isShiftObject(previousTimelineOrShift) ?
                this.parseShiftDateFromDateAndTime(previousTimelineOrShift.start_date, previousTimelineOrShift.start) :
                this.parseTimelineDateFromDateAndTime(previousTimelineOrShift.start_date, previousTimelineOrShift.start),
            startDate = this.isShiftObject(timelineOrShift) ?
                this.parseShiftDateFromDateAndTime(timelineOrShift.start_date, timelineOrShift.start) :
                this.parseTimelineDateFromDateAndTime(timelineOrShift.start_date, timelineOrShift.start);

        return this.getDifferenceInMinutes(previousEndDate, startDate);
    }

    startsIdentical(previous, current) {
        const previousStartDate = this.isShiftObject(previous) ?
                this.parseShiftDateFromDateAndTime(previous.start_date, previous.start) :
                this.parseTimelineDateFromDateAndTime(previous.start_date, previous.start),
            currentStartDate = this.isShiftObject(current) ?
                this.parseShiftDateFromDateAndTime(current.start_date, current.start) :
                this.parseTimelineDateFromDateAndTime(current.start_date, current.start);

        return previousStartDate.getTime() === currentStartDate.getTime();
    }

    startsInBetween(previous, current) {
        const previousEndDate = this.isShiftObject(previous) ?
                this.parseShiftDateFromDateAndTime(previous.end_date, previous.end) :
                this.parseTimelineDateFromDateAndTime(previous.end_date, previous.end),
            currentStartDate = this.isShiftObject(current) ?
                this.parseShiftDateFromDateAndTime(current.start_date, current.start) :
                this.parseTimelineDateFromDateAndTime(current.start_date, current.start);

        return currentStartDate.getTime() < previousEndDate.getTime();
    }

    getPreviousTimelines(index) {
        return this.timelinesAndShifts.filter(
            (element, elementIndex) => !this.isShiftObject(element) && elementIndex < index
        );
    }

    getPreviousTimelinesOrShiftsStartingIdentical(timelineOrShift, index) {
        return this.timelinesAndShifts.filter(
            (element, elementIndex) => elementIndex <= index && this.startsIdentical(element, timelineOrShift)
        );
    }

    getTimelineOrShiftElement(timelineOrShift) {
        return this.isShiftObject(timelineOrShift) ?
            document.getElementById(this.shiftIdentifier + this.eventId + '-' + timelineOrShift.id) :
            document.getElementById(this.timelineIdentifier + this.eventId + '-' + timelineOrShift.id);
    }

    getEventContainer() {
        return document.getElementById(this.eventContainerIdentifier + this.eventId);
    }

    isShiftObject(object) {
        return Object.keys(object).includes('shift_uuid');
    }

    parseShiftDateFromDateAndTime(dateString, timeString) {
        const dateParts = dateString.split(' '),
            day = parseInt(dateParts[0]),
            curYear = new Date().getFullYear(),
            month = new Date(Date.parse(dateParts[1] + " 1, " + curYear)).getMonth(),
            year = parseInt(dateParts[2]),
            timeParts = timeString.split(':'),
            hours = parseInt(timeParts[0]),
            minutes = parseInt(timeParts[1]);

        return new Date(year, month, day, hours, minutes);
    }

    parseTimelineDateFromDateAndTime(dateString, timeString) {
        const dateParts = dateString.split('-'),
            year = parseInt(dateParts[0]),
            month = parseInt(dateParts[1]) - 1,
            day = parseInt(dateParts[2]),
            timeParts = timeString.split(':'),
            hours = parseInt(timeParts[0]),
            minutes = parseInt(timeParts[1]);

        return new Date(year, month, day, hours, minutes)
    }

    getDifferenceInMinutes(startDate, endDate) {
        return (endDate.getTime() - startDate.getTime()) / (1000 * 60);
    }

    setShiftContainerHeight(element, startDate, endDate) {
        //if calculated height is lower than current height we will use current height
        //(example: shift has 6 worker (bigger height) but only is 1 hour long
        this.setElementHeight(
            element,
            Math.max(
                (
                    this.getDifferenceInMinutes(startDate, endDate) * this.elementsHeightInPixelsPerMinute
                ) + this.elementsHeaderHeight,
                this.getElementHeight(element)
            )
        );
    }

    setTimelineContainerHeight(element, startDate, endDate) {
        //if calculated height is lower than current height we will use current height
        //(example: shift has 6 worker (bigger height) but only is 1 hour long
        this.setElementHeight(
            element,
            Math.max(
                this.getDifferenceInMinutes(startDate, endDate) * this.elementsHeightInPixelsPerMinute,
                this.getElementHeight(element)
            )
        );
    }
};
</script>
