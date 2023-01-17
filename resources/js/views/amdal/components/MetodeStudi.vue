<template>
  <div class="app-container">
    <identifikasi-dampak-table
      :id-project="idProject"
      :table="'metode-studi'"
      @handleSetData="handleSetData"
    />
    <div style="text-align: right; margin: 2em 0 1em 0;">
      <el-button
        v-if="isFormulator"
        type="success"
        size="small"
        icon="el-icon-check"
        :disabled="isReadOnly && !isUrlAndal"
        @click="!isReadOnly && isUrlAndal, handleSaveForm()"
      >
        Simpan Perubahan
      </el-button>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import Resource from '@/api/resource';
import IdentifikasiDampakTable from './tables/IdentifikasiDampakTable.vue';

export default {
  name: 'MetodeStudi',
  components: { IdentifikasiDampakTable },
  data() {
    return {
      data: [],
      idProject: 0,
    };
  },
  computed: {
    ...mapGetters({
      markingStatus: 'markingStatus',
    }),

    isReadOnly() {
      const data = [
        'uklupl-mt.sent',
        'uklupl-mt.adm-review',
        'uklupl-mt.examination-invitation-drafting',
        'uklupl-mt.examination-invitation-sent',
        'uklupl-mt.examination',
        'uklupl-mt.examination-meeting',
        'uklupl-mt.ba-drafting',
        'uklupl-mt.ba-signed',
        'uklupl-mt.recommendation-drafting',
        'uklupl-mt.recommendation-signed',
        'uklupl-mr.pkplh-published',
        'uklupl-mt.pkplh-published',
        'amdal.form-ka-submitted',
        'amdal.form-ka-adm-review',
        'amdal.form-ka-adm-returned',
        'amdal.form-ka-adm-approved',
        'amdal.form-ka-examination-invitation-drafting',
        'amdal.form-ka-examination-invitation-sent',
        'amdal.form-ka-examination',
        'amdal.form-ka-examination-meeting',
        'amdal.form-ka-returned',
        'amdal.form-ka-approved',
        'amdal.form-ka-ba-drafting',
        'amdal.form-ka-ba-signed',
        'amdal.andal-drafting',
        'amdal.rklrpl-drafting',
        'amdal.submitted',
        'amdal.adm-review',
        'amdal.adm-approved',
        'amdal.examination',
        'amdal.feasibility-invitation-drafting',
        'amdal.feasibility-invitation-sent',
        'amdal.feasibility-review',
        'amdal.feasibility-review-meeting',
        'amdal.feasibility-ba-drafting',
        'amdal.feasibility-ba-signed',
        'amdal.recommendation-drafting',
        'amdal.recommendation-signed',
        'amdal.skkl-published',
      ];

      console.log({ workflow: this.markingStatus });

      return data.includes(this.markingStatus);
    },
    isUrlAndal() {
      const data = [
        'amdal.form-ka-submitted',
        'amdal.form-ka-adm-review',
        'amdal.form-ka-adm-returned',
        'amdal.form-ka-adm-approved',
        'amdal.form-ka-examination-invitation-drafting',
        'amdal.form-ka-examination-invitation-sent',
        'amdal.form-ka-examination',
        'amdal.form-ka-examination-meeting',
        'amdal.form-ka-returned',
        'amdal.form-ka-approved',
        'amdal.form-ka-ba-drafting',
        'amdal.form-ka-ba-signed',
        'amdal.andal-drafting',
        'amdal.rklrpl-drafting',
        'amdal.submitted',
      ];
      return this.$route.name === 'penyusunanAndal' && data.includes(this.markingStatus);
    },
    isAndal() {
      return this.$route.name === 'penyusunanAndal';
    },
    isFormulator() {
      return this.$store.getters.roles.includes('formulator');
    },
  },
  mounted() {
    this.idProject = parseInt(this.$route.params && this.$route.params.id);
  },
  methods: {
    handleSetData(data) {
      this.data = data;
    },
    handleSaveForm() {
      const impactIdtResource = this.isAndal ? new Resource('andal-clone') : new Resource('impact-identifications');
      impactIdtResource
        .store({
          study_data: this.data,
        })
        .then((response) => {
          var message = (response.code === 200) ? 'Metode Studi berhasil disimpan' : 'Terjadi kesalahan pada server';
          var message_type = (response.code === 200) ? 'success' : 'error';
          this.$message({
            message: message,
            type: message_type,
            duration: 5 * 1000,
          });
          if (response.code === 200) {
            this.$emit('handleEnableSubmitForm');
          }
        })
        .catch((error) => {
          console.log(error);
        });
    },
  },
};
</script>

