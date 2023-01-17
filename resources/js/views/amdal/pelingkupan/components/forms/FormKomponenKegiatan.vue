<template>
  <div>
    <el-dialog
      :title="'Master Data Komponen Kegiatan'"
      :visible.sync="show"
      width="40%"
      height="450"
      :destroy-on-close="true"
      :before-close="handleClose"
      :close-on-click-modal="false"
      @open="onOpen"
    >
      <div v-loading="saving">
        <el-form label-position="top">

          <el-form-item
            v-if="formMode === 0"
            label="Tahap"
            required="true"
          >
            <el-select
              v-model="data.id_project_stage"
              placeholder="Pilih Tahap"
              style="width:100%"
              @change="onChangeStage"
            >
              <el-option
                v-for="stage in stages"
                :key="'scoping_stage_'+ stage.id"
                :label="stage.name"
                :value="stage.id"
              />
            </el-select>

          </el-form-item>
          <div v-else>
            <div><strong>Tahap</strong></div>
            <div style="font-size:120%; color:#202020;">
              {{ data.project_stage_name }}
            </div>
          </div>

          <el-form-item
            v-if="formMode === 0"
            required="true"
            label="Komponen Kegiatan"
            :loading="loading"
          >
            <el-select
              v-model="name"
              style="width:100%"
              clearable
              filterable
              :disabled="noMaster"
              :loading="loading"
              loading-text="Memuat data..."
              @change="handleSelect"
            >
              <el-option
                v-for="item in master"
                :key="item.value"
                :label="item.name"
                :value="item.id"
              >
                <span>{{ item.name }} &nbsp;<i v-if="item.is_master" class="el-icon-success" style="color:#2e6b2e;" /></span>
              </el-option>
            </el-select>
            <el-checkbox v-model="noMaster" @change="onChangeInput"><span style="font-size: 90%;">Menambahkan Komponen Kegiatan</span></el-checkbox>
            <el-input
              v-if="noMaster"
              v-model="data.name"
              placeholder="Nama Komponen Kegiatan..."
            />

          </el-form-item>

          <div v-else-if="data.is_master" style="margin: 2em 0;">
            <div><strong>Komponen Kegiatan</strong></div>
            <div style="font-size:120%; color:#202020;">
              {{ data.name }} <i class="el-icon-success" style="color:#2e6b2e;" />
            </div>
          </div>

          <el-form-item v-else label="Komponen Kegiatan">
            <el-input
              v-model="data.name"
              placeholder="Nama Komponen Kegiatan..."
            />
          </el-form-item>

          <!-- -->

          <el-form-item required="true" label="Deskripsi">
            <div v-if="isReadOnly && !isUrlAndal">
              <span v-html="data.description" />
            </div>
            <div v-else>
              <desceditor
                v-model="data.description"
                output-format="html"
                :menubar="''"
                :image="false"
                :height="100"
                :toolbar="['bold italic underline bullist numlist  preview undo redo fullscreen']"
                style="width:100%"
              />
            </div>
          </el-form-item>

          <el-form-item required="true" label="Besaran/Skala Usaha/Kegiatan">
            <el-input
              v-model="data.measurement"
              type="textarea"
              :disabled="isReadOnly && !isUrlAndal"
              :autosize="{ minRows: 3, maxRows: 5}"
            />
          </el-form-item>
        </el-form>
      </div>
      <span slot="footer" class="dialog-footer">
        <el-button type="danger" @click="handleClose">Batal</el-button>
        <el-button type="primary" :disabled="disableSave() || isReadOnly && !isUrlAndal" @click="!isReadOnly && isUrlAndal, handleSaveForm()">Simpan</el-button>
      </span>
    </el-dialog>
  </div>
</template>
<script>
import { mapGetters } from 'vuex';
import Resource from '@/api/resource';
import DescEditor from '@/components/Tinymce';

const componentResource = new Resource('components');
const projectComponentResource = new Resource('project-components');

export default {
  name: 'FormKomponenKegiatan',
  components: { 'desceditor': DescEditor },
  props: {
    mode: {
      type: Number,
      default: 0,
    },
    show: {
      type: Boolean,
      default: false,
    },
    input: {
      type: Object,
      default: function(){
        return null;
      },
    },
    stages: {
      type: Array,
      default: function() {
        return [];
      },
    },
    formMode: {
      type: Number,
      default: 0,
    },
  },
  data(){
    return {
      project_id: 0,
      loading: false,
      operations: ['Tambah', 'Edit'],
      options: [],
      name: '',
      showForm: false,
      data: null,
      master: [],
      noMaster: false,
      saving: false,
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
  /* watch: {
    show: function(val){
      console.log(val);
    },
  },*/
  mounted() {
    this.project_id = parseInt(this.$route.params && this.$route.params.id);
    this.options = [];
    this.initData();
  },
  methods: {
    initData(){
    /**
     * Big Notes:
     * data = {
     *   id, name, is_master, id_project_stage, project_stage_name,  // from components table
     *     description, measurement // to go to projects_component
     * }
     **/
      this.master = [];
      this.is_master = false;
      this.name = '';
      this.data = {
        id: null,
        name: '',
        is_master: false,
        id_project_stage: null,
        project_stage_name: '',
        description: '',
        measurement: '',
        value: '',
        is_selected: false,
        id_project_component: 0,
      };
    },
    onOpen(){
      console.log('opening!');
      switch (this.formMode){
        case 0:
          this.initData();
          break;
        case 1:
          // edit mode
          this.data = this.input;
          this.noMaster = this.data.is_master === false;
          break;
      }
      // console.log(this.data);
    },
    onChangeStage(val){
      // console.log(val);
      this.master = [];
      this.name = '';
      this.data.id = null;
      const stage = this.stages.find(s => s.id === parseInt(val));
      this.data.project_stage_name = stage.name;
      this.getComponents();
    },
    async handleSaveForm(){
      // save data in this form!
      this.saving = true;
      if ((this.noMaster === true) && (this.formMode === 0)) {
        this.data.id = null;
      }
      await projectComponentResource.store({
        id_project: this.project_id,
        mode: this.mode,
        component: this.data,
      }).then((res) => {
        this.data.id_project_component = res.data.id;
        this.data.id = res.data.id_component;
      }).catch((err) => {
        console.log(err);
      })
        .finally(() => {
          this.saving = false;
        });

      console.log(this.data);
      this.$emit('onSave', this.data);
      this.handleClose();
    },
    async getComponents(){
      console.log('yeah!');
      // if (data.id_project_stage === 0) return [];
      this.loading = true;
      this.master = [];
      const project_id = parseInt(this.$route.params && this.$route.params.id);
      await componentResource.list({
        stage_id: this.data.id_project_stage,
        project_id: project_id,
      }).then((res) => {
        this.master = res;
      }).finally(() => {
        this.loading = false;
      });
    },
    handleSelect(val){
      if (val === ''){
        console.log("it's empty!");
        this.data.id = null;
        this.data.name = '';
        this.data.is_master = false;
        this.data.value = '';
      } else {
        const item = this.master.find(i => i.id === parseInt(val));
        console.log(val, item);
        this.data.id = item.id;
        this.data.name = item.name;
        this.data.is_master = item.is_master;
        this.data.value = item.name;
      }
    },
    handleClose(){
      this.initData();
      this.close();
    },
    onChangeInput(val){
      if (val === true){
        this.data.name = this.name;
      }
    },
    close(){
      this.$emit('onClose', true);
    },
    clear(){
      this.initData();
    },
    disableSave(){
      const emptyTexts = (this.data.description === null) ||
        ((this.data.description).trim() === '') ||
        (this.data.measurement === null) ||
        ((this.data.measurement).trim() === '');

      if (this.noMaster){
        return ((this.data.name).trim() === '') || emptyTexts;
      }
      return (this.data.id === null) || (this.data.id <= 0) || emptyTexts;
    },
  },
};
</script>
<style scoped>
.header{
  font-weight:bold;
  text-transform: uppercase;
  font-size:110%;
  color:#202020;
}

::v-deep p {
  text-align: left !important;
}

</style>
