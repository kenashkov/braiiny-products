<template>
    <div class="products_admin">
        <h3>Products Admin</h3>

        <CrudAdminC></CrudAdminC>
    </div>
</template>

<script>
    import CrudAdminC from '@GuzabaPlatform.Crud/CrudAdmin.vue'

    export default {
        name: "ProductsAdmin",
        components: {
            CrudAdminC
        },
        mounted() {
            let CrudAdminC = this.get_child_component_by_name('CrudAdmin')
            //console.log(CrudAdminC)
            //CrudAdminC.selectedClassName = 'Kenashkov\\Braiiny\\Products\\Models\\Product'
            CrudAdminC.selectedClassName = 'Kenashkov-Braiiny-Products-Models-Product' //url safe
            CrudAdminC.getClassObjects(CrudAdminC.selectedClassName);
        }
    }
</script>

<style scoped>

</style>