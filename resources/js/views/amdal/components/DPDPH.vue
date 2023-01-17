<template>
  <div v-loading="isLoading">
    <div style="text-align:right;margin-top:1em;">
      <el-popconfirm
        v-if="totalChanges > 0"
        confirm-button-text="Iya, refresh!"
        cancel-button-text="Tidak"
        title="Ada perubahan yang belum disimpan. Lanjutkan muat ulang data?"
        icon-color="red"
        @confirm="refresh()"
      >
        <el-button
          slot="reference"
          icon="el-icon-refresh"
        > Refresh data
        </el-button>
      </el-popconfirm>

      <el-button
        v-if="totalChanges <= 0"
        icon="el-icon-refresh"
        @click="refresh()"
      > Refresh data
      </el-button>
    </div>
    <div v-if="isReadOnly && !isUrlAndal">
      <dampak-hipotetik-master-table
        :impacts="impacts"
        :id-project="id_project"
        :change-types="changeTypes"
        :stages="stages"
        :components="components"
        :sub-projects="subProjects"
        :hues="hues"
        :pie-params="pieParams"
        :total-changes="totalChanges"
        :loading="isLoading"
        :mode="mode"
        :disabled="isReadOnly & !isUrlAndal"
        @dataSelected="!isReadOnly && isUrlAndal, onDataSelected"
        @saveChanges="!isReadOnly && isUrlAndal, saveChanges"
      />
    </div>
    <div v-else>
      <dampak-hipotetik-master-table
        :impacts="impacts"
        :id-project="id_project"
        :change-types="changeTypes"
        :stages="stages"
        :components="components"
        :sub-projects="subProjects"
        :hues="hues"
        :pie-params="pieParams"
        :total-changes="totalChanges"
        :loading="isLoading"
        :mode="mode"
        @dataSelected="onDataSelected"
        @saveChanges="saveChanges"
      />
    </div>
    <div v-if="isReadOnly & !isUrlAndal">
      <dampak-hipotetik-detail-form
        :data="selectedData"
        :change-types="changeTypes"
        :pie-params="pieParams"
        :master="activities"
        :mode="mode"
        :disabled="isReadOnly & !isUrlAndal"
        @hasChanges="!isReadOnly && isUrlAndal, hasChanges"
        @saveData="!isReadOnly && isUrlAndal, onSaveData"
      />
    </div>
    <div v-else>
      <dampak-hipotetik-detail-form
        :data="selectedData"
        :change-types="changeTypes"
        :pie-params="pieParams"
        :master="activities"
        :mode="mode"
        @hasChanges="hasChanges"
        @saveData="onSaveData"
      />
    </div>
  </div>
</template>
<script>
import { mapGetters } from 'vuex';
import DampakHipotetikMasterTable from './tables/DPHMasterTable.vue';
import DampakHipotetikDetailForm from './forms/DPHDetailForm.vue';

import Resource from '@/api/resource';
const impactsResource = new Resource('impacts');
const projectStagesResource = new Resource('project-stages');
const changeTypeResource = new Resource('change-types');
const pieParamsResource = new Resource('pie-params');
const projectActivityResource = new Resource('project-kegiatan-lain-sekitar');

export default {
  name: 'DampakHipotetikMD',
  components: { DampakHipotetikDetailForm, DampakHipotetikMasterTable },
  props: {
    mode: {
      type: Number,
      default: 0,
    },
  },
  data() {
    return {
      id_project: 0,
      impacts: [],
      masters: [],
      isLoading: false,
      totalChanges: 0,
      stages: [],
      selectedData: null,
      pieParams: [],
      changeTypes: [],
      impact_res_name: 'impacts',
      components: [],
      subProjects: [],
      activities: [],
      hues: [],
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
  },
  mounted(){
    this.id_project = parseInt(this.$route.params && this.$route.params.id);
    this.impacts = [];
    this.getParams();
    this.getImpacts();
    this.getActivities();
  },
  methods: {
    async getImpacts(){
      this.impacts = null;
      this.masters = null;
      this.isLoading = true;
      impactsResource.list({
        id_project: this.id_project,
        mode: this.mode,
      }).then((res) => {
        this.components = [];
        this.subProjects = [];
        this.hues = [];
        // const data = res;
        res.map((e) => {
          console.log(e.sub_projects);

          // e.kegiatan = sp_names;
          e.hasChanges = false;
          console.log('each impact: ', e);

          if (this.components.findIndex(c => c.value === e.komponen) < 0) {
            this.components.push({ text: e.komponen, value: e.komponen });
          }

          /* if (this.subProjects.findIndex(s => s.value === e.kegiatan) < 0) {
            const temp = data.filter(d => d.kegiatan === e.kegiatan);
            this.subProjects.push({
              text: e.kegiatan,
              label: (e.kegiatan)
                .replace(/\w\S*//* g, (w) => (w.replace(/^\w/, (c) => c.toUpperCase()))),
              value: e.kegiatan,
              type: e.type,
              count: temp.length,
            });
          }*/

          if (this.hues.findIndex(c => (c.value).toLowerCase() === (e.rona_awal).toLowerCase()) < 0) {
            this.hues.push({
              text: (e.rona_awal)
                .replace(/\w\S*/g, (w) => (w.replace(/^\w/, (c) => c.toUpperCase()))),
              value: e.rona_awal });
          }
          console.log('hues', this.hues);
          console.log('komp', this.components);
        });
        const order = [4, 1, 2, 3]; // stage's order
        this.impacts = res.sort((a, b) => order.indexOf(a.project_stage) - order.indexOf(b.project_stage));
        this.hues.sort((a, b) => a.value.localeCompare(b.value));
        this.subProjects.sort((a, b) => a.value.localeCompare(b.value));
        this.components.sort((a, b) => a.value.localeCompare(b.value));
      }).finally(() => {
        this.isLoading = false;
      });
    },
    async getParams(){
      // console.log('getting params!');
      await projectStagesResource.list({ ordered: true }).then((res) => {
        this.stages = res.data;
        console.log('get param stages', this.stages);
      });
      await pieParamsResource.list({}).then((res) => {
        this.pieParams = res.data;
      });
      await changeTypeResource.list({}).then((res) => {
        this.changeTypes = res.data;
        this.changeTypes.push({
          name: 'Lainnya',
          id: 0,
        });
      });
      await projectActivityResource.list({
        id_project: parseInt(this.$route.params && this.$route.params.id),
        mode: this.mode,
      }).then((res) => {
        this.activities = res;
        this.activities.forEach((e, index) => {
          this.activities[index].is_selected = false;
        });
      }).finally(() => {});
    },

    handlePie(obj){
      this.selectedData.hasChanges = true;
    },
    onDataSelected(obj) {
      this.selectedData = obj;
      // console.log('onSelectedData', this.selectedData.stage);
    },
    hasChanges(obj) {
      // this.handlePie(obj);
      const hc = this.impacts.filter(e => (e.hasChanges === true));
      this.totalChanges = hc.length;
      // console.log('calling totaller!', this.totalChanges);
    },
    onSaveData(obj){
      const temp = this.impacts;
      this.impacts = null;
      this.impacts = temp;
    },
    saveChanges(evt){
      const impstosave = this.impacts.filter(e => e.hasChanges === true);
      this.isLoading = true;
      impactsResource.store({ mode: this.mode, data: impstosave }).then((res) => {
        this.$message({
          message: 'Dampak Hipotetik berhasil disimpan',
          type: 'success',
          duration: 5 * 1000,
        });
      })
        .finally(() => {
          this.refresh();
          this.isLoading = false;
        });
    },
    refresh(){
      this.totalChanges = 0;
      this.getImpacts();
    },
    sortImpacts(imps){
      return imps.sort((a, b) => {
        const ia = this.stages.findIndex(sa => a.project_stage === sa.id);
        const ib = this.stages.findIndex(sb => b.project_stage === sb.id);
        return ia - ib;
      });
    },
  },
};
</script>
<style lang="scss" scoped>

</style>
