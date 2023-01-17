<template>
  <div>
    <el-dialog
      :title="'Master Komponen Lingkungan'"
      :visible.sync="show"
      width="40%"
      height="450"
      :destroy-on-close="true"
      :before-close="handleClose"
      :close-on-click-modal="false"
      @open="onOpen"
    >
      <el-form v-loading="saving" label-position="top">

        <el-form-item
          v-if="formMode === 0"
          :required="true"
          label="Kategori Komponen Lingkungan"
        >

          <el-select
            v-model="data.id_component_type"
            placeholder="Pilih Kategori Komponen Lingkungan"
            @change="onChangeType"
          >
            <el-option
              v-for="item in componentTypes"
              :key="'master_hue_option_' + item.id"
              :label="item.name"
              :value="item.id"
            />
          </el-select>

        </el-form-item>
        <div v-else>
          <div><strong>Kategori Komponen Lingkungan</strong></div>
          <div style="font-size:120%; color:#202020;">
            {{ getComponentTypeName(data.id_component_type) }}
          </div>
        </div>
        <el-form-item
          v-if="formMode === 0"
          :required="true"
          label="Rona Lingkungan"
        >
          <el-select
            v-model="name"
            style="width:100%"
            clearable
            filterable
            :disabled="noMaster"
            placeholder="Pilih Rona Lingkungan"
            :loading="loading"
            loading-text="Memuat data..."
            @change="handleSelect"
          >
            <el-option
              v-for="item in master"
              :key="item.value"
              :label="item.name"
              :value="item.name"
            >
              <span>{{ item.name }} &nbsp;<i v-if="item.is_master" class="el-icon-success" style="color:#2e6b2e;" /></span>
            </el-option>
          </el-select>
          <el-checkbox v-model="noMaster"><span style="font-size: 90%;">Menambahkan Rona Lingkungan</span></el-checkbox>
          <el-input
            v-if="noMaster"
            v-model="data.name"
            placeholder="Nama Rona Lingkungan..."
          />
        </el-form-item>
        <div v-else-if="data.is_master === true" style="margin: 2em 0;">
          <div><strong>Rona Lingkungan</strong></div>
          <div style="font-size:120%; color:#202020;">
            {{ data.name }} <i class="el-icon-success" style="color:#2e6b2e;" />
          </div>
        </div>
        <el-form-item v-else label="Rona Lingkungan">
          <el-input
            v-model="data.name"
            placeholder="Nama Rona Lingkungan..."
          />
        </el-form-item>
        <el-form-item v-if="(isUklUpl() === true)" label="Deskripsi (Optional)">
          <div v-if="isReadOnly && !isUrlAndal">
            <span v-html="data.description" />
          </div>
          <div v-else>
            <huedesceditor
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

        <el-form-item v-else :required="true" label="Deskripsi">
          <div v-if="isReadOnly && !isUrlAndal">
            <span v-html="data.description" />
          </div>
          <div v-else>
            <huedesceditor
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

        <el-form-item v-if="(isUklUpl() === true)" label="Besaran/Skala Rona Lingkungan (Optional)">
          <el-input
            v-model="data.measurement"
            type="textarea"
            :disabled="isReadOnly && !isUrlAndal"
            :autosize="{ minRows: 3, maxRows: 5}"
            placeholder="Besaran Rona Lingkungan..."
          />
        </el-form-item>

        <el-form-item v-else label="Besaran/Skala Rona Lingkungan">
          <el-input
            v-model="data.measurement"
            type="textarea"
            :disabled="isReadOnly && !isUrlAndal"
            :autosize="{ minRows: 3, maxRows: 5}"
            placeholder="Besaran Rona Lingkungan..."
          />
        </el-form-item>

      </el-form>
      <div v-if="data.project_rona_awal[0].file && showFile">
        <a :href="data.project_rona_awal[0].file" target="_blank">File PDF</a>
        <a href="#" @click="deleteFilePdf">Hapus</a>
      </div>

      <span slot="footer" class="dialog-footer">
        <div v-if="pdfError || pdfName" style="display: inline-block;">
          <small v-if="pdfError" style="color:red;">{{ pdfError }}</small>
          <small v-if="pdfName">{{ pdfName }}</small>
        </div>
        <el-upload
          action="#"
          :auto-upload="false"
          :disabled="isReadOnly && !isUrlAndal"
          :on-change="!isReadOnly && isUrlAndal && handleUploadPDF"
          :show-file-list="false"
          style="display: inline-block; margin-right: 11px;"
        >
          <el-button :disabled="isReadOnly && !isUrlAndal" type="warning"> Upload PDF </el-button>
        </el-upload>
        <el-button type="danger" @click="handleClose">Batal</el-button>
        <el-button type="primary" :disabled="optional() || disableSave() || isReadOnly && !isUrlAndal" @click="!isReadOnly && isUrlAndal, handleSaveForm()">Simpan</el-button>
      </span>
    </el-dialog>
  </div>
</template>
<script>
import { mapGetters } from 'vuex';
import Resource from '@/api/resource';
import HueDescEditor from '@/components/Tinymce';
const ronaAwalResource = new Resource('rona-awals');
const projectRonaAwalResource = new Resource('project-rona-awals');

export default {
  name: 'FormKomponenLingkungan',
  components: { 'huedesceditor': HueDescEditor },
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
    componentTypes: {
      type: Array,
      default: function(){
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
      pdfName: null,
      pdfFile: null,
      pdfError: null,
      deletePdfId: null,
      showFile: false,
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
    this.isUklUpl();
  },
  methods: {
    initData(){
    /**
     * Big Notes:
     * data = {
     *   id, name, id_component_type, is_master, // from rona_awal table
     *     description, measurement // to go to projects_rona_awals
     * }
     **/
      this.master = [];
      this.name = '';
      this.data = {
        id: null,
        name: '',
        id_component_type: null,
        is_master: false,
        description: '',
        measurement: '',
        value: '',
        component_type_name: '',
        id_project: parseInt(this.$route.params && this.$route.params.id),
        id_project_rona_awal: null,
        project_rona_awal: [{}],
      };
      this.pdfName = null;
      this.pdfFile = null;
      this.pdfError = null;
      this.deletePdfId = null;
      this.showFile = true;
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
          break;
      }
      console.log(this.data);
    },
    async handleSaveForm(){
      // save data in this form!
      this.saving = true;
      const formData = new FormData();
      formData.append('id_project', this.project_id);
      formData.append('component', JSON.stringify(this.data));
      formData.append('mode', this.mode);

      if (this.deletePdfId) {
        formData.append('deletePdfId', this.deletePdfId);
      }

      if (this.pdfFile) {
        formData.append('file', await this.convertBase64(this.pdfFile));
      }

      await projectRonaAwalResource.store(formData)
        .then((res) => {
          this.data.id_project_rona_awal = res.data.id;
          this.data.project_rona_awal = [res.data];
          if (this.data.id === null) {
            this.data.id = res.data.id_rona_awal;
          }
        })
        .catch((err) => {
          console.log(err);
        })
        .finally(() => {
          this.saving = false;
        });
      this.$emit('onSave', this.data);
      this.handleClose();
    },
    async getHues(){
      this.master = [];
      this.loading = true;
      const project_id = parseInt(this.$route.params && this.$route.params.id);
      await ronaAwalResource.list({
        id_component_type: this.data.id_component_type,
        project_id: project_id,
      })
        .then((res) => {
          this.master = res.data;
        })
        .finally(() => {
          this.loading = false;
        });
    },
    isUklUpl(){
      if (this.$route.name === 'FormulirUklUpl') {
        return true;
      }
    },
    handleSelect(val){
      if (val === ''){
        console.log("it's empty!");
        this.data.id = null;
        this.data.name = '';
        this.data.is_master = false;
        this.data.value = '';
      } else {
        const item = this.master.find(i => i.name === val);
        this.data.id = item.id;
        this.data.name = item.name;
        this.data.value = item.name;
        this.data.is_master = item.is_master;
      }
    },
    handleClose(){
      this.initData();
      this.close();
    },
    close(){
      this.$emit('onClose', true);
    },
    clear(){
      this.initData();
    },
    onChangeType(val){
      this.master = [];
      this.data.id = null;
      this.name = '';

      const ctype = this.componentTypes.find(c => c.id === parseInt(val));
      this.data.component_type_name = ctype.name;
      this.getHues();
    },
    getComponentTypeName(id){
      const ctype = this.componentTypes.find(c => c.id === id);
      if (ctype){
        return ctype.name;
      }
      return '';
    },
    disableSave(){
      const emptyTexts = (this.data.description === null) ||
        ((this.data.description).trim() === '') ||
        (this.data.measurement === null) ||
        ((this.data.measurement).trim() === '');

      if (this.noMaster){
        if (this.$route.name === 'FormulirUklUpl') {
          if (this.noMaster && this.data.name.trim() !== '') {
            return;
          }
        }
        return ((this.data.name).trim() === '') || (this.data.id_component_type === null) || emptyTexts;
      }

      if (this.$route.name === 'FormulirUklUpl') {
        return (this.data.name === '') || (this.data.component_type_name === '');
      } else {
        return (this.data.id === null) || (this.data.id_component_type === null) || (this.data.id <= 0) || emptyTexts;
      }
    },
    optional(){
      if (this.$route.name === 'FormulirUklUpl') {
        return (this.data.id_component_type === null);
      }
    },
    handleUploadPDF(file, filelist) {
      this.pdfError = null;
      this.pdfFile = null;
      this.pdfName = null;
      if (file.raw.size > 5242880) {
        this.pdfError = 'Ukuran File Tidak Boleh Melebihi 5 MB';
        return;
      }

      const extension = file.name.split('.');
      if (extension[extension.length - 1].toLowerCase() !== 'pdf') {
        this.pdfError = 'File Harus Berformat PDF';
        return;
      }

      this.pdfFile = file.raw;
      this.pdfName = file.name;
    },
    deleteFilePdf() {
      this.showFile = false;
      this.deletePdfId = this.data.id_project_rona_awal;
    },
    convertBase64(file) {
      return new Promise((resolve, reject) => {
        const fileReader = new FileReader();
        fileReader.readAsDataURL(file);

        fileReader.onload = () => {
          resolve(fileReader.result);
        };

        fileReader.onerror = (error) => {
          reject(error);
        };
      });
    },
  },
};
</script>
<style scoped>
  ::v-deep p {
  text-align: left !important;
}
</style>
