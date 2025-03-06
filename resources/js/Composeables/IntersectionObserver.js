import {ref} from "vue";

export function useDaysAndEventsIntersectionObserver(rootMargin = '100px') {
    const composedCurrentDaysInViewRef = ref(new Set()),
        composedEventsByDaysContainerClassRef = ref('.events-by-days-container'),
        composedDayContainerClassRef = ref('.day-container'),
        composedStartDaysAndEventsIntersectionObserving = () => {
            let composedObserver = new IntersectionObserver(
                (intersectionObserverEntry) => {
                    intersectionObserverEntry.forEach((entry) => {
                        const day = entry.target.dataset.day;

                        if (entry.isIntersecting) {
                            composedCurrentDaysInViewRef.value.add(day);
                        } else {
                            composedCurrentDaysInViewRef.value.delete(day);
                        }
                    });
                },
                {
                    root: document.getElementsByClassName(composedEventsByDaysContainerClassRef.value)[0],
                    rootMargin: rootMargin
                }
            );

            document.querySelectorAll(composedDayContainerClassRef.value)
                .forEach(
                    (container) =>
                    {
                        composedObserver.observe(container);
                    }
                );
        };

    return {
        composedEventsByDaysContainerClassRef,
        composedDayContainerClassRef,
        composedCurrentDaysInViewRef,
        composedStartDaysAndEventsIntersectionObserving,
    };
}
