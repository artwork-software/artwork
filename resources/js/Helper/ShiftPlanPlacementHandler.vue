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

    calculateHeights() {
        this.timelinesAndShifts.forEach((element) => {
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
        this.timelinesAndShifts.forEach((currentTimelineOrShift, index) => {
            const currentIsShiftObject = this.isShiftObject(currentTimelineOrShift),
                element = this.getTimelineOrShiftElement(currentTimelineOrShift);

            if (index === 0) {
                //no margin for first item
                return;
            }

            const previousTimelineOrShift = this.timelinesAndShifts[(index - 1)],
                previousTimelineOrShiftElement = this.getTimelineOrShiftElement(previousTimelineOrShift),
                previousIsShiftObject = this.isShiftObject(previousTimelineOrShift);

            //handle timeline after timeline placement
            if (!currentIsShiftObject && !previousIsShiftObject) {
                //if timeline after timeline is placed we just need to add 2 pixel margin top and calculate hint

                if (!this.handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift)) {
                    //set margin top to 2px if there is no diff to previous
                    this.setElementMarginTop(element, 2);
                }

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
                    )

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

                let desiredHeightForCalculation = this.getElementHeight(previousTimelineOrShiftElement);

                if (previousSameStarting.length > 1) {
                    //the previousTimelineOrShift is included in the result set, if we have more
                    //than one entry we need to determine the biggest container for proper placement
                    previousSameStarting.forEach(
                        (element) => {
                            let elementHeight = this.getElementHeight(this.getTimelineOrShiftElement(element));

                            if (elementHeight > desiredHeightForCalculation) {
                                desiredHeightForCalculation = elementHeight;
                            }
                        }
                    )
                }

                marginTop += desiredHeightForCalculation;
                marginTop -= this.elementsHeaderHeight;

                this.getPreviousTimelines(index).forEach(
                    (previousTimeline) => {
                        let tmp = this.getTimelineOrShiftElement(previousTimeline);
                        marginTop -= this.getElementHeight(tmp);
                        marginTop -= this.getElementMarginTop(tmp);
                    }
                );

                marginTop += this.defaultMarginToAdd;

                this.setElementMarginTop(element, marginTop);
                this.handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift);
                return;
            }

            //handle shift after timeline placement
            if (currentIsShiftObject && !previousIsShiftObject) {
                if ((index - 1) === 0) {
                    //previous element also was first element
                    this.setElementMarginTop(
                        element,
                        this.getElementHeight(previousTimelineOrShiftElement) +
                        this.getElementMarginTop(previousTimelineOrShiftElement) +
                        this.elementsHeaderHeight +
                        this.defaultMarginToAdd
                    );
                    this.handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift);
                    return;
                }

                //if start same as previous shift placed on same height
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

                    previousSameStarting.forEach(
                        (element) => {
                            let elementHeight = this.getElementHeight(this.getTimelineOrShiftElement(element));

                            if (elementHeight > marginTop) {
                                marginTop = elementHeight;
                            }
                        }
                    );

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
                    marginTop += this.defaultMarginToAdd;
                    //just add the marginTop of the previous element, height not desired as its already determined
                    marginTop += this.getElementMarginTop(previousTimelineOrShiftElement);

                    this.setElementMarginTop(element, marginTop);
                    this.handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift);

                    return;
                }

                //determine all previous margins and container heights + elementsHeaderHeight
                let marginTop = this.elementsHeaderHeight;

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
                //placement shift after shift
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
                let desiredElementHeight = this.getElementHeight(previousTimelineOrShiftElement);

                //check if any of the previous containers is bigger
                if (previousSameStarting.length > 1) {
                    previousSameStarting.forEach(
                        (element) => {
                            let elementHeight = this.getElementHeight(this.getTimelineOrShiftElement(element));

                            if (elementHeight > desiredElementHeight) {
                                desiredElementHeight = elementHeight;
                            }
                        }
                    );
                }

                this.setElementMarginTop(
                    element,
                    this.getElementMarginTop(previousTimelineOrShiftElement) +
                    desiredElementHeight +
                    this.defaultMarginToAdd
                );
                this.handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift);
            }
        });
    }

    handleDiffToPreviousElement(element, currentTimelineOrShift, previousTimelineOrShift) {
        let diff = this.getDiffToPreviousElement(currentTimelineOrShift, previousTimelineOrShift);

        if (diff <= 0) {
            return false;
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

        hintElement.innerText = '--- Ungenutzt: ' + innerTextTime + ' ---';

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

    getDiffToPreviousElement(timelineOrShift, previousTimelineOrShift) {
        let previousEndDate = this.isShiftObject(previousTimelineOrShift) ?
                this.parseShiftDateFromDateAndTime(previousTimelineOrShift.end_date, previousTimelineOrShift.end) :
                this.parseTimelineDateFromDateAndTime(previousTimelineOrShift.end_date, previousTimelineOrShift.end),
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
