<script>
import {usePage} from "@inertiajs/inertia-vue3";

export default {
    data() {
        return {
            permissions: this.$page.props.permissions,
            rolesArray: this.$page.props.roles
        };
    },
    methods: {
        $can(permissionName) {
            return this.permissions.includes(permissionName);
        },
        $role(roleName) {
            if(!roleName){ return false;}
            return this.rolesArray.includes(roleName);
        },
        $canAny(permissionNames) {
            for (const permission of permissionNames) {
                if (this.$can(permission)) {
                    return true;
                }
            }
            return false;
        },
        $roleAny(roleNames) {
            for (const role of roleNames) {
                if (this.$role(role)) {
                    return true;
                }
            }
            return false;
        },
        hasAdminRole(){
            return this.$role('artwork admin');
        }
    }
};

</script>
