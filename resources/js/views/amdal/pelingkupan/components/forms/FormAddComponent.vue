<template>
  <el-dialog
    :title=" title[formMode] + ' Komponen Kegiatan'"
    :visible.sync="show"
    width="50%"
    :before-close="handleClose"
    @open="onOpen"
  >
    <div v-loading="isSaving">
      <el-form label-position="top" :model="data" style="padding: 0 2em;">
        <!-- tahap -->
        <div style="line-height: 150% !important;">
          <div><strong>Tahap Kegiatan</strong></div>
          <div style="font-size:110%">{{ data.project_stage.name || '' }}</div>
        </div>

        <div style="line-height: 150% !important; margin-top: 1em;">
          <div><strong>Kegiatan Utama/Pendukung</strong></div>
          <div style="font-size:110%">{{ data.sub_projects.name || '' }}</div>
        </div>

        <el-form-item
          label="Komponen Kegiatan"
          required="true"
          style="padding: 0.5em 0.8em; border: 1px solid #cccccc; border-radius: 0.5em; margin-top: 1em;"
        >

          <el-select
            v-if="master === null"
            v-model="component.id_component"
            style="width:100%"
            clearable
            filterable
            @change="handleSelectComponent"
          >
            <el-option
              v-for="item in masterComponents"
              :key="item.value"
              :label="item.name"
              :value="item.id"
            >
              <span>{{ item.name }} &nbsp;<i v-if="item.is_master" class="el-icon-success" style="color:#2e6b2e;" /></span>
            </el-option>
          </el-select>
          <div v-else>
            <div style="font-size:110%">{{ master.name || '' }} &nbsp;<i v-if="master.is_master" class="el-icon-success" style="color:#2e6b2e;" /></div>
          </div>
          <div style="margin: 1em 0;">
            <deskripsi v-if="selected !== null" :description="selected.description" :measurement="selected.measurement" />
          </div>
        </el-form-item>
        <el-form-item
          v-if="selected !== null"
          label="Deskripsi (Optional)"
        >
          <div v-if="isReadOnly && !isUrlAndal">
            <span v-html="component.description" />
          </div>
          <div v-else>
            <scopingceditor
              :id="'comp_scoping-editor_334'"
              :key="'comp_editor_scoping_3'"
              v-model="component.description"
              output-format="html"
              :menubar="''"
              :image="false"
              :height="100"
              :toolbar="['bold italic underline bullist numlist  preview undo redo fullscreen']"
              style="width:100%"
            />
          </div>
        </el-form-item>
        <el-form-item v-if="selected !== null" label="Besaran/Skala Komponen Kegiatan Utama/Pendukung (Optional)">
          <el-input
            v-model="component.measurement"
            type="textarea"
            :disabled="isReadOnly && !isUrlAndal"
            :autosize="{ minRows: 3, maxRows: 5}"
          />
        </el-form-item>

      </el-form>
    </div>
    <span slot="footer" class="dialog-footer">
      <el-button type="danger" :disabled="isSaving" @click="handleClose">Batal</el-button>
      <el-button type="primary" :disabled="isSaving || disableSave() || isReadOnly && !isUrlAndal" @click="!isReadOnly && isUrlAndal, submitComponent()">Simpan</el-button>
    </span>
  </el-dialog>
</template>
<script>
import { mapGetters } from 'vuex';
import Resource from '@/api/resource';
import SCEditor from '@/components/Tinymce';
import Deskripsi from '../helpers/Deskripsi.vue';
const subProjectComponentResource = new Resource('sub-project-components');

export default {
  name: 'FormAddComponent',
  components: { 'scopingceditor': SCEditor, Deskripsi },
  props: {
    data: {
      type: Object,
      default: null,
    },
    show: {
      type: Boolean,
      default: false,
    },
    master: {
      type: Object,
      default: () => null,
    },
    masterComponents: {
      type: Array,
      default: function(){
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
      component: {
        id: null,
        id_component: null,
        id_sub_project: null,
        name: '',
        value: '',
        description: '',
        measurement: '',
        id_sub_project_component: null,
      },
      isSaving: false,
      title: ['Tambah', 'Edit'],
      selected: null,
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
    handleClose(){
      // this.initData();
      this.$emit('onClose', true);
    },
    onOpen(){
      this.isSaving = false;
      if ((this.master === null)){
        this.formMode = 0;
        this.initData();
      } else {
        this.formMode = 1;
        this.selected = this.master;
        this.component = {
          id: this.master.id,
          id_component: this.master.id,
          name: this.master.name,
          value: this.master.name,
          id_sub_project: this.data.sub_projects.id,
          description: this.data.component.description,
          measurement: this.data.component.measurement,
          id_sub_project_component: this.data.component.id_sub_project_component,
          mode: this.mode,
        };
      }

      const ed = window.tinymce.get('comp_scoping-editor_334');
      if (ed) {
        ed.setContent((this.component.description === null) ? '' : this.component.description);
      }
      console.log(this.component);
    },
    handleSelectComponent(val){
      if (val === '') {
        console.log("it's empty!");
        this.component.id_component = null;
      } else {
        this.selected = this.masterComponents.find(e => e.id === val);
      }
      // this.component.name = this.selected.name;
    },
    async submitComponent() {
      this.isSaving = true;
      this.component.id = this.component.id_component;
      this.component.id_sub_project = this.data.sub_projects.id;
      await subProjectComponentResource.store(
        this.component
      ).then((res) => {
        this.component.id_sub_project_component = res.id;
        this.$message({
          message: 'Komponen kegiatan berhasil disimpan',
          type: 'success',
          duration: 5 * 1000,
        });
        this.$emit('onSave', {
          id: this.component.id,
          name: this.component.name,
          value: this.component.value,
          id_sub_project: this.component.id_sub_project,
          id_sub_project_component: res.id,
          description: this.component.description,
          measurement: this.component.measurement,
          id_project_stage: this.data.project_stage.id,
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
    initData(){
      this.formMode = 0;
      this.selected = null;
      this.component.id = null;
      this.component.id_component = null;
      this.component.name = '';
      this.component.description = '';
      this.component.measurement = '';
      this.component.id_sub_project = null;
      this.component.id_sub_project_component = null;
      this.component.value = '';
      this.component.mode = this.mode;
    },

    disableSave(){
      const emptyTexts = (this.component.description === null) ||
        ((this.component.description).trim() === '') ||
        (this.component.measurement === null) ||
        ((this.component.measurement).trim() === '');
      if (this.component.id_component !== null) {
        console.log(this.name);
        return false;
      } else {
        return (this.component.id_component === null) || (this.component.id_component <= 0) || emptyTexts;
      }
    },
  },

};
</script>
