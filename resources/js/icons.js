import * as TablerIcons from "@tabler/icons-vue";

export default {
    install(app) {
        // Iteriere durch alle Icons und registriere sie global
        Object.keys(TablerIcons).forEach((iconName) => {
            app.component(iconName, TablerIcons[iconName]);
        });
    },
};
