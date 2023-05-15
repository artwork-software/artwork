<script>
export default {
    methods: {
        $can(permissionName) {
            return Permissions.indexOf(permissionName) !== -1;
        },
        $role(roleName){
            return Roles.indexOf(roleName) !== -1;
        },
        $canAny(permissionNames){
            permissionNames.forEach((permission) => {
                return Permissions.indexOf(permission) !== -1;
            })
        },
        $roleAny(roleNames){
            roleNames.forEach((role) => {
                return Roles.indexOf(role) !== -1;
            })
        }
    },
};
</script>
