import {
    h,
    defineAsyncComponent,
    defineComponent,
    ref,
    onMounted,
    onBeforeUnmount,
} from 'vue';

export const lazyLoadComponentIfVisible = ({
                                               componentLoader,
                                               loadingComponent,
                                               errorComponent,
                                               delay,
                                               timeout
                                           }) => {
    let resolveComponent;

    return defineAsyncComponent({
        // der Loader
        loader: () => {
            return new Promise((resolve) => {
                // Wir speichern resolve, um es später aufzurufen
                resolveComponent = resolve;
            });
        },
        // Lade-Placeholder-Komponente
        loadingComponent: defineComponent({
            setup() {
                const elRef = ref();
                let observer = null;

                async function loadComponent() {
                    const component = await componentLoader();
                    resolveComponent(component);
                }

                onMounted(async () => {
                    // Fallback ohne IntersectionObserver
                    if (!('IntersectionObserver' in window)) {
                        await loadComponent();
                        return;
                    }

                    observer = new IntersectionObserver(async (entries) => {
                        if (!entries[0].isIntersecting) {
                            return;
                        }

                        observer?.disconnect();
                        observer = null;
                        await loadComponent();
                    });

                    observer.observe(elRef.value);
                });

                // Platzhalter verschwindet, bevor er sichtbar wurde: Observer sonst nie getrennt
                onBeforeUnmount(() => {
                    observer?.disconnect();
                    observer = null;
                });

                return () => {
                    return h('div', { ref: elRef }, loadingComponent);
                };
            },
        }),
        // Delay bis zum Anzeigen des Ladezustands
        delay,
        // Fehlerkomponente
        errorComponent,
        // Timeout bis Fehler
        timeout,
    });
};
