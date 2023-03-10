<template>
  <el-dialog
    :visible.sync="show"
    width="40%"
    :before-close="handleClose"
  >
    <business-form
      :is-edit="true"
      :id-business="idBusiness"
      @handleClose="handleClose"
      @handleDelete="handleDelete"
    />
  </el-dialog>
</template>
<script>
import BusinessForm from './forms/BusinessForm.vue';

export default {
  name: 'EditBusiness',
  components: { BusinessForm },
  props: {
    show: Boolean,
    idBusiness: {
      type: Number,
      default: () => 0,
    },
  },
  methods: {
    handleClose() {
      this.$emit('handleClose');
    },
    handleDelete(business) {
      this.$emit('handleDelete', business);
    },
  },
};
</script>
