<template>
  <el-dialog
    :visible.sync="show"
    width="40%"
    :before-close="handleClose"
  >
    <business-env-param-form
      :is-edit="true"
      :id-business-env-param="idBusinessEnvParam"
      @handleClose="handleClose"
      @handleDelete="handleDelete"
      @handleReloadList="handleReloadList"
    />
  </el-dialog>
</template>
<script>
import BusinessEnvParamForm from './forms/BusinessEnvParamForm.vue';

export default {
  name: 'EditBusinessEnvParam',
  components: { BusinessEnvParamForm },
  props: {
    show: Boolean,
    idBusinessEnvParam: {
      type: Number,
      default: () => 0,
    },
  },
  methods: {
    handleClose() {
      this.$emit('handleClose');
    },
    handleDelete(param) {
      this.$emit('handleDelete', param);
    },
    handleReloadList() {
      this.$emit('handleReloadList');
    },
  },
};
</script>
