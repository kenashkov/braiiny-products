<template>
    <div class="products_admin">
        <h3>Products Admin</h3>

        <ButtonC v-bind:ButtonData="ImportButton" v-b-modal.generic-modal></ButtonC>
        <GenericModalC v-bind:ModalData="ImportModalData"></GenericModalC>
        <CrudAdminC></CrudAdminC>
    </div>
</template>

<script>
    import CrudAdminC from '@GuzabaPlatform.Crud/CrudAdmin.vue'
    import ButtonC from '@GuzabaPlatform.Platform/components/Button.vue'
    import GenericModalC from '@GuzabaPlatform.Platform/components/GenericModal.vue'

    import ToastMixin from '@GuzabaPlatform.Platform/ToastMixin.js'

    export default {
        name: "ProductsAdmin",
        mixins: [
            ToastMixin,
        ],
        components: {
            CrudAdminC,
            ButtonC,
            GenericModalC,
        },
        data() {
            return {
                ImportModalData: {
                    title : 'Import products from ERP',
                    text : 'Confirm importing all products from the ERP?',
                    action_url : '/admin/products/import-from-erp',
                    method: 'post',
                    AdditionalData: {}
                },
                ImportButton: {
                    label: 'Import from ERP',
                    is_active: true,
                    handler: this.blank_button_handler,
                },
            }
        },
        methods: {
            blank_button_handler() {

            },
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