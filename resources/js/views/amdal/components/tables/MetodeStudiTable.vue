<template>
  <el-table
    v-loading="loading"
    :data="data"
    :span-method="arraySpanMethod"
    fit
    highlight-current-row
    :header-cell-style="{ background: '#6cc26f', color: 'white' }"
    style="width: 100%"
  >
    <el-table-column label="Dampak Penting Hipotetik">
      <template slot-scope="scope">
        <div v-if="scope.row.is_stage">
          <b>{{ scope.row.index }}. {{ scope.row.project_stage_name }}</b>
        </div>
        <div v-if="!scope.row.is_stage && !scope.row.is_comment">
          {{ scope.row.index }}. {{ scope.row.change_type_name }}
          {{ scope.row.rona_awal_name }} akibat {{ scope.row.component_name }}
        </div>
        <div v-if="scope.row.is_comment">
          <Comment
            :impactidentification="scope.row.id_impact_identification"
            :commenttype="isAndal ? 'metode-studi-andal' : 'metode-studi-ka'"
            :kolom="kolom"
          />
        </div>
      </template>
    </el-table-column>
    <el-table-column
      label="Data dan Informasi yang Relevan dan Dibutuhkan"
      align="left"
    >
      <template slot-scope="scope">
        <div v-if="!scope.row.is_stage">
          <div v-if="isReadOnly && !isUrlAndal">
            <span style="font-weight: 600;" v-html="scope.row.impact_study.forecast_method" />
          </div>
          <div v-else>
            <Tinymce
              v-if="isFormulator"
              v-model="scope.row.impact_study.required_information"
              output-format="html"
              :menubar="''"
              :image="false"
              :toolbar="[
                'bold italic underline bullist numlist  preview undo redo fullscreen',
              ]"
              :height="50"
            />
            <div v-else v-html="scope.row.impact_study.required_information" />
          </div>
        </div>
      </template>
    </el-table-column>
    <el-table-column
      label="Metode Pengumpulan Data untuk Prakiraan"
      align="left"
    >
      <template slot-scope="scope">
        <div v-if="!scope.row.is_stage">
          <div v-if="isReadOnly && !isUrlAndal">
            <span style="font-weight: 600;" v-html="scope.row.impact_study.forecast_method" />
          </div>
          <div v-else>
            <Tinymce
              v-if="isFormulator"
              v-model="scope.row.impact_study.data_gathering_method"
              output-format="html"
              :menubar="''"
              :image="false"
              :toolbar="[
                'bold italic underline bullist numlist  preview undo redo fullscreen',
              ]"
              :height="50"
            />
            <div v-else v-html="scope.row.impact_study.data_gathering_method" />
          </div>
        </div>
      </template>
    </el-table-column>
    <el-table-column label="Metode Analisis Data untuk Prakiraan" align="left">
      <template slot-scope="scope">
        <div v-if="!scope.row.is_stage">
          <div v-if="isReadOnly && !isUrlAndal">
            <span style="font-weight: 600;" v-html="scope.row.impact_study.forecast_method" />
          </div>
          <div v-else>
            <Tinymce
              v-if="isFormulator"
              v-model="scope.row.impact_study.analysis_method"
              output-format="html"
              :menubar="''"
              :image="false"
              :toolbar="[
                'bold italic underline bullist numlist  preview undo redo fullscreen',
              ]"
              :height="50"
            />
            <div v-else v-html="scope.row.impact_study.analysis_method" />
          </div>
        </div>
      </template>
    </el-table-column>
    <el-table-column label="Metode Prakiraan Dampak Penting" align="left">
      <template slot-scope="scope">
        <div v-if="!scope.row.is_stage">
          <div v-if="isReadOnly && !isUrlAndal">
            <span style="font-weight: 600;" v-html="scope.row.impact_study.forecast_method" />
          </div>
          <div v-else>
            <Tinymce
              v-if="isFormulator"
              v-model="scope.row.impact_study.forecast_method"
              output-format="html"
              :menubar="''"
              :image="false"
              :toolbar="[
                'bold italic underline bullist numlist  preview undo redo fullscreen',
              ]"
              :height="50"
            />
            <div v-else v-html="scope.row.impact_study.forecast_method" />
          </div>
        </div>
      </template>
    </el-table-column>
    <el-table-column label="Metode Evaluasi Dampak Penting" align="left">
      <template slot-scope="scope">
        <div v-if="!scope.row.is_stage">
          <div v-if="isReadOnly && !isUrlAndal">
            <span style="font-weight: 600;" v-html="scope.row.impact_study.forecast_method" />
          </div>
          <div v-else>
            <Tinymce
              v-if="isFormulator"
              v-model="scope.row.impact_study.evaluation_method"
              output-format="html"
              :menubar="''"
              :image="false"
              :toolbar="[
                'bold italic underline bullist numlist  preview undo redo fullscreen',
              ]"
              :height="50"
            />
            <div v-else v-html="scope.row.impact_study.evaluation_method" />
          </div>
        </div>
      </template>
    </el-table-column>
  </el-table>
</template>

<script>
import { mapGetters } from 'vuex';
import Comment from '@/views/amdal/components/Comment.vue';
import Tinymce from '@/components/Tinymce';

export default {
  name: 'MetodeStudiTable',
  components: {
    Comment,
    Tinymce,
  },
  props: {
    data: {
      type: Array,
      default: () => [],
    },
    loading: {
      type: Boolean,
      default: () => [],
    },
  },
  data() {
    return {
      kolom: [
        {
          label: 'Data dan Informasi yang Relevan dan Dibutuhkan',
          value: 'Data dan Informasi yang Relevan dan Dibutuhkan',
        },
        {
          label: 'Metode Pengumpulan Data untuk Prakiraan',
          value: 'Metode Pengumpulan Data untuk Prakiraan',
        },
        {
          label: 'Metode Analisis Data untuk Prakiraan',
          value: 'Metode Analisis Data untuk Prakiraan',
        },
        {
          label: 'Metode Prakiraan Dampak Penting',
          value: 'Metode Prakiraan Dampak Penting',
        },
        {
          label: 'Metode Evaluasi Dampak Penting',
          value: 'Metode Evaluasi Dampak Penting',
        },
      ],
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
    // this.getData();
  },
  methods: {
    getData() {
      this.loading = true;
    },
    arraySpanMethod({ row, column, rowIndex, columnIndex }) {
      if (row.is_comment && columnIndex === 0) {
        return [1, 6];
      }
    },
  },
};
</script>
