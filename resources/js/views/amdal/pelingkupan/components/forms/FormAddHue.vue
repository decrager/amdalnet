<template>
  <div v-loading="isSaving">
    <el-dialog
      :title="title[formMode] + ' Komponen Lingkungan'"
      :visible.sync="show"
      width="50%"
      height="450"
      :destroy-on-close="true"
      :before-close="handleClose"
      :close-on-click-modal="false"
      @open="onOpen"
    >
      <div v-loading="isSaving">
        <el-form v-if="(data !== null) && (data.sub_projects != null) && (data.component !== null) " style="padding: 0 2em;">
          <div style="line-height: 140%;">
            <div><strong>Tahap</strong></div>
            <div>{{ data.project_stage.name }}</div>
          </div>

          <div style="line-height: 140%; margin-top: 1em; ">
            <div><strong>Kegiatan Utama/Pendukung</strong></div>
            <div>{{ data.sub_projects.name }}</div>
          </div>

          <div v-if="masterComponent" style="line-height: 140%; margin-top: 1.5em; padding: 1em; border:1px solid #cccccc; border-radius: 0.5em;">
            <div><strong>Komponen Kegiatan</strong></div>
            <div style="margin:1em 0;">{{ masterComponent.name }} &nbsp;<i v-if="masterComponent.is_master" class="el-icon-success" style="color:#2e6b2e;" /></div>
            <deskripsi :description="masterComponent.description" :measurement="masterComponent.measurement" />
          </div>
          <div style="line-height: 140%; margin-top: 1.5em; padding: 1em; border:1px solid #cccccc; border-radius: 0.5em;">
            <div><strong><u>Komponen {{ masterComponent.name }}</u> pada <u>Kegiatan {{ data.sub_projects.name }}</u></strong></div>
            <div style="margin-top:1em;">
              <deskripsi :description="data.component.description" :measurement="data.component.measurement" />
            </div>
          </div>

          <el-form-item required="true" label="Rona Lingkungan" style="line-height: 140%; margin-top:1.5em; padding:1em; border:1px solid #cccccc; border-radius: 0.5em;">
            <el-select
              v-if="master === null"
              v-model="hue.id"
              placeholder="Pilih Rona Lingkungan"
              style="width:100%"
              @change="onChangeHue"
            >
              <el-option
                v-for="item in masterHues"
                :key="'scoping_opt_hue_'+ item.id"
                :label="item.name"
                :value="item.id"
              />
            </el-select>
            <div v-else style="font-size:110%: float:none; clear: both;">{{ master.name || '' }} &nbsp;<i v-if="master.is_master" class="el-icon-success" style="color:#2e6b2e;" /></div>
            <div style="margin-top:1em;">
              <deskripsi v-if="selected !== null" :description="selected.description" :measurement="selected.measurement" />
            </div>
          </el-form-item>
          <div v-if="selected !== null" style="margin: 2em 0 1em; font-weight:bold;">
            {{ '* Deskripsi '+ hue.name +' terkait '+ masterComponent.name + ' pada Kegiatan '+ data.sub_projects.name }}
          </div>
          <el-form-item v-if="selected !== null" style="margin: 1em 0;">
            <div v-if="isReadOnly && !isUrlAndal">
              <span v-html="hue.description" />
            </div>
            <div v-else>
              <hueeditor
                :key="'hue_scoping_editor_3'"
                v-model="hue.description"
                output-format="html"
                :menubar="''"
                :image="false"
                :height="100"
                :toolbar="['bold italic underline bullist numlist  preview undo redo fullscreen']"
                style="width:100%"
              />
            </div>
          </el-form-item>
          <el-form-item v-if="selected !== null " required="true" label="Besaran/Skala Dampak">

            <el-input
              v-model="hue.measurement"
              type="textarea"
              :disabled="isReadOnly && !isUrlAndal"
              :autosize="{ minRows: 3, maxRows: 5}"
            />

          </el-form-item>
        </el-form>
      </div>
      <span slot="footer" class="dialog-footer">
        <el-button type="danger" :disabled="isSaving" @click="handleClose">Batal</el-button>
        <el-button type="primary" :disabled="isSaving || disableSave() || isReadOnly && !isUrlAndal" @click="!isReadOnly && isUrlAndal, handleSaveForm()">Simpan</el-button>
      </span>
    </el-dialog>
  </div>
</template>
<script>
import { mapGetters } from 'vuex';
import Resource from '@/api/resource';
import Deskripsi from '../helpers/Deskripsi.vue';
import SCHEditor from '@/components/Tinymce';
const subProjectHueResource = new Resource('sub-project-rona-awals');

export default {
  name: 'FormAddHue',
  components: { Deskripsi, 'hueeditor': SCHEditor },
  props: {
    show: { type: Boolean, default: false },
    data: {
      type: Object,
      default: null,
    },
    masterComponent: {
      type: Object,
      default: null,
    },
    master: {
      type: Object,
      default: null,
    },
    masterHues: {
      type: Array,
      default: function() {
        return [];
      },
    },
    mode: {
      type: Number,
      default: 0,
    },
  },
  data(){
    return {
      hue: {
        id: null,
        name: '',
        value: '',
        description: '',
        measurement: '',
        id_sub_project_component: null,
        id_sub_project_rona_awal: null,
        id_project: null,
        id_component: null,
      },
      selected: null,
      isSaving: false,
      title: ['Tambah', 'Edit'],
      formMode: 0,
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
    this.onOpen();
  },
  methods: {
    async handleSaveForm(){
      this.isSaving = true;
      this.hue.id_sub_project_component = this.data.component.id_sub_project_component;
      this.hue.id_project = this.data.id_project;
      this.hue.id_project_component = this.masterComponent.id_project_component;
      this.hue.id_project_rona_awal = this.selected.id_project_rona_awal;
      this.hue.mode = this.mode;
      console.log(this.hue);
      await subProjectHueResource.store(this.hue)
        .then((res) => {
          this.hue.id_sub_project_rona_awal = res.id_sub_project_rona_awal;
          this.$message({
            message: 'Komponen lingkungan berhasil disimpan',
            type: 'success',
            duration: 5 * 1000,
          });

          this.$emit('onSave', {
            id: this.hue.id,
            name: this.hue.name,
            value: this.hue.value,
            id_sub_project_rona_awal: this.hue.id_sub_project_rona_awal,
            id_sub_project_component: this.hue.id_sub_project_component,
            description: this.hue.description,
            measurement: this.hue.measurement,
          });
        })
        .catch((err) => {
          this.$message({
            message: 'Gagal menyimpan komponen kegiatan',
            type: 'error',
            duration: 5 * 1000,
          });
          console.log(err);
        })
        .finally(() => {
          this.isSaving = false;
          this.handleClose();
        });
    },
    handleClose(){
      this.initData();
      this.$emit('onClose', true);
    },
    initData(){
      this.hue.id = null;
      this.hue.name = '';
      this.hue.description = '';
      this.hue.measurement = '';
      this.hue.id_sub_project_rona_awal = null;
      this.hue.id_project = null;
      this.hue.id_sub_project_component = null;
      this.hue.id_component = null;
      this.hue.mode = this.mode;
      this.selected = null;
    },
    onOpen(){
      this.isSaving = false;
      if (this.master === null) {
        this.formMode = 0;
        this.selected = null;
        this.hue = {
          id: null,
          name: '',
          description: '',
          measurement: '',
          id_sub_project_component: this.data.rona_awal.id_sub_project_component,
          id_sub_project_rona_awal: null,
          id_project: this.data.sub_projects.id_project,
          id_component: null,
          mode: this.mode,
        };
        console.log('add...', this.hue);
      } else {
        this.formMode = 1;
        this.hue = {
          id: this.master.id,
          name: this.master.name,
          description: this.data.rona_awal.description,
          measurement: this.data.rona_awal.measurement,
          id_sub_project_component: this.data.rona_awal.id_sub_project_component,
          id_sub_project_rona_awal: this.data.rona_awal.id_sub_project_rona_awal,
          id_project: this.data.sub_projects.id_project,
          id_component: this.master.id,
          mode: this.mode,
        };
        this.selected = this.master;
      }
      console.log('opening form hue: ', this.hue);
    },
    onChangeHue(val){
      this.selected = this.masterHues.find(h => h.id === val);
      this.hue.name = this.selected.name;
    },
    disableSave(){
      const emptyTexts = (this.hue.description === null) ||
        ((this.hue.description).trim() === '') ||
        (this.hue.measurement === null) ||
        ((this.hue.measurement).trim() === '');

      return (this.hue.id === null) || (this.hue.id <= 0) || emptyTexts;
    },
  },
};
</script>
