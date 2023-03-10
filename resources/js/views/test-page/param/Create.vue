<template>
  <el-dialog
    :visible.sync="show"
    width="40%"
    :before-close="handleClose"
  >
    <business-env-param-form
      :is-edit="false"
      @handleClose="handleClose"
      @handleReloadList="handleReloadList"
    />
  </el-dialog>
</template>
<script>
import BusinessEnvParamForm from './forms/BusinessEnvParamForm.vue';

export default {
  name: 'CreateBusinessEnvParam',
  components: { BusinessEnvParamForm },
  props: {
    show: Boolean,
  },
  methods: {
    handleClose() {
      this.$emit('handleClose');
    },
    handleReloadList() {
      this.$emit('handleReloadList');
    },
  },
};
</script>
