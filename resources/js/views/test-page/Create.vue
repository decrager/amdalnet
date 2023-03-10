<template>
  <el-dialog
    :visible.sync="show"
    width="40%"
    :before-close="handleClose"
  >
    <business-form
      :is-edit="false"
      @handleClose="handleClose"
    />
  </el-dialog>
</template>
<script>
import BusinessForm from './forms/BusinessForm.vue';

export default {
  name: 'CreateBusiness',
  components: { BusinessForm },
  props: {
    show: Boolean,
  },
  methods: {
    handleClose() {
      this.$emit('handleClose');
    },
  },
};
</script>

