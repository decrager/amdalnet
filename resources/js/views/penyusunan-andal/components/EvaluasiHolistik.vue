<template>
  <el-row v-loading="loading">
    <el-col :md="24" :sm="24">
      <div v-if="isReadOnly">
        <span v-html="evaluation" />
      </div>
      <div v-else>
        <Tinymce
          v-model="evaluation"
          output-format="html"
          :menubar="''"
          :image="false"
          :toolbar="[
            'bold italic underline bullist numlist  preview undo redo fullscreen',
          ]"
          :height="300"
        />
      </div>
    </el-col>
    <el-col :md="24" :sm="24" style="text-align: right">
      <el-button
        :loading="loadingSubmit"
        type="primary"
        style="margin-top: 20px"
        :disabled="isReadOnly"
        @click="!isReadOnly && checkSubmit()"
      >
        Simpan
      </el-button>
    </el-col>
  </el-row>
</template>

<script>
import { mapGetters } from 'vuex';
import Tinymce from '@/components/Tinymce';
import Resource from '@/api/resource';
const andalComposingResource = new Resource('andal-composing');

export default {
  name: 'EvaluasiHolistik',
  components: {
    Tinymce,
  },
  data() {
    return {
      evaluation: null,
      loading: false,
      loadingSubmit: false,
    };
  },
  computed: {
    ...mapGetters({
      markingStatus: 'markingStatus',
    }),

    isReadOnly() {
      const data = [
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
  },
  created() {
    this.getData();
  },
  methods: {
    async getData() {
      this.loading = true;
      this.evaluation = await andalComposingResource.list({
        holisticEvaluation: 'true',
        idProject: this.$route.params.id,
      });
      this.loading = false;
    },
    checkSubmit() {
      if (this.evaluation) {
        this.handleSubmit();
      } else {
        this.$message({
          message: 'Evaluasi Holistik Wajib Diisi',
          type: 'error',
          duration: 5 * 1000,
        });
      }
    },
    async handleSubmit() {
      this.loadingSubmit = true;
      await andalComposingResource.store({
        type: 'holisticEvaluation',
        description: this.evaluation,
        idProject: this.$route.params.id,
      });
      this.loadingSubmit = false;
      this.$message({
        message: 'Evaluasi holistik berhasil disimpan',
        type: 'success',
        duration: 5 * 1000,
      });
      this.getData();
    },
  },
};
</script>
