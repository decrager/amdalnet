<template>
  <el-steps
    :active="getStep"
    finish-status="success"
    align-center
    style="margin-bottom: 20px"
  >
    <el-step title="Rencana Usaha Kegiatan" />
    <el-step title="Hasil Penapisan" />
    <el-step title="SPT dari Masyarakat" />
    <el-step title="Formulir UKL UPL" />
    <el-step title="Matriks UKL UPL" />
    <el-step title="Dokumen UKL UPL" />
    <el-step title="Surat Keputusan PKPLH" />
  </el-steps>
</template>

<script>
export default {
  name: 'WorkflowUkl',
  computed: {
    getStep() {
      return this.$store.getters.step;
    },
  },
};
</script>
