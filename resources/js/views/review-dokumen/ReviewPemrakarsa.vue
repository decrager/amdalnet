<template>
  <div v-loading="loading">
    <div style="margin-bottom: 8px">
      <label>Pesan dari Penyusun:</label>
      <div v-html="formulatorNotes" />
    </div>
    <div style="margin-bottom: 8px">
      <el-radio v-model="status" label="submit" :disabled="isDisabled">
        Submit
      </el-radio>
      <el-radio v-model="status" label="revisi" :disabled="isDisabled">
        Revisi
      </el-radio>
    </div>
    <div v-if="status === 'submit'">
      <div v-if="!isDisabled">
        <p>Unggah Surat Permohonan<span style="color: red">*</span></p>
        <el-upload
          action="#"
          :auto-upload="false"
          :on-change="handleUpload"
          :show-file-list="false"
        >
          <el-button size="small" type="warning"> Upload </el-button>
          <div slot="tip" class="el-upload__tip">
            {{ fileName }}
          </div>
        </el-upload>
        <span v-if="errors.file" style="color: red">{{ errors.file }}</span>
      </div>
      <div v-else>
        <a :href="applicationLetter" :download="applicationLetterName">
          <i class="el-icon-download" /> Download Surat Permohonan
        </a>
      </div>
    </div>
    <div v-if="status === 'revisi'">
      <div v-if="!isDisabled">
        <p style="margin-bottom: 0px; padding-bottom: 0px">
          Catatan<span style="color: red">*</span>
        </p>
        <Tinymce v-model="notes" />
        <span v-if="errors.notes" style="color: red">{{ errors.notes }}</span>
      </div>
      <div v-else>
        <p style="margin-bottom: 0px; padding-bottom: 0px">
          Catatan perbaikan dari pemrakarsa
        </p>
        <div v-html="notesShow" />
      </div>
    </div>
    <div v-if="showButton" style="text-align: right; margin-top: 8px">
      <el-button :loading="loadingSubmit" type="primary" @click="checkSubmit">
        Kirim
      </el-button>
    </div>
    <el-alert
      v-if="statusShow === 'revisi' || statusShow === 'submit'"
      :title="alertTitle"
      type="success"
      :description="alertDescription"
      show-icon
      center
      :closable="false"
    />
  </div>
</template>

<script>
import Tinymce from '@/components/Tinymce';
import Resource from '@/api/resource';
import axios from 'axios';
const kaReviewsResource = new Resource('ka-reviews');

export default {
  name: 'ReviewPemrakarsa',
  components: {
    Tinymce,
  },
  props: {
    documenttype: {
      type: String,
      default: () => '',
    },
  },
  data() {
    return {
      status: null,
      statusShow: null,
      file: null,
      fileName: null,
      notes: null,
      notesShow: null,
      loading: false,
      loadingSubmit: false,
      formulatorNotes: null,
      applicationLetter: null,
      applicationLetterName: '',
      errors: {},
    };
  },
  computed: {
    isShow() {
      if (this.statusShow === null) {
        return false;
      }

      return !(this.statusShow === 'revisi' || this.statusShow === 'submit');
    },
    showButton() {
      if (this.statusShow === 'revisi' || this.statusShow === 'submit') {
        return false;
      }

      if (this.status === 'revisi' || this.status === 'submit') {
        return true;
      }

      return false;
    },
    isDisabled() {
      return this.statusShow === 'revisi' || this.statusShow === 'submit';
    },
    alertTitle() {
      if (this.statusShow === 'revisi') {
        return `${this.formulirOrDokumen} ${this.documenttype} telah Dikembalikan ke Penyusun untuk Diperbaiki`;
      }

      return `${this.formulirOrDokumen} ${this.documenttype} telah Dikirim untuk Dinilai`;
    },
    alertDescription() {
      if (this.statusShow === 'revisi') {
        return 'Terimakasih atas Tanggapan Anda';
      }

      return `Terimakasih sudah Mengirimkan ${this.formulirOrDokumen} ${this.documenttype}`;
    },
    getDocumentType() {
      if (this.documenttype === 'Kerangka Acuan') {
        return 'ka';
      } else if (this.documenttype === 'ANDAL RKL RPL') {
        return 'andal-rkl-rpl';
      } else if (this.documenttype === 'UKL UPL') {
        return 'ukl-upl';
      }

      return '';
    },
    formulirOrDokumen() {
      if (
        this.documenttype === 'Kerangka Acuan' ||
        this.documenttype === 'UKL UPL'
      ) {
        return 'Formulir';
      } else {
        return 'Dokumen';
      }
    },
  },
  created() {
    this.getData();
  },
  methods: {
    async getData() {
      this.loading = true;
      const data = await kaReviewsResource.list({
        idProject: this.$route.params.id,
        documentType: this.getDocumentType,
      });
      if (data) {
        this.statusShow = data.status;
        this.notesShow = data.notes;
        this.formulatorNotes = data.formulator_notes;
        this.applicationLetter = data.application_letter;
        this.applicationLetterName = data.project.project_title;
        if (data.status !== 'submit-to-pemrakarsa') {
          this.status = data.status;
        }
      }
      this.loading = false;
    },
    handleUpload(file, fileList) {
      if (file.raw.size > 1048576) {
        this.showFileAlert();
        return;
      }

      this.file = file.raw;
      this.fileName = file.name;
    },
    checkSubmit() {
      this.errors = {};

      if (this.status === 'revisi') {
        if (!this.notes) {
          this.errors.notes = 'Catatan Wajib Diisi';
          return;
        }
      } else if (this.status === 'submit') {
        if (!this.file) {
          this.errors.file = 'Surat Permohonan Wajib Diunggah';
          return;
        }
      }

      this.$confirm('Apakah anda yakin ? Data yang sudah disimpan, tidak dapat diubah lagi.', 'Warning', {
        confirmButtonText: 'Ya',
        cancelButtonText: 'Tidak',
        type: 'warning',
      })
        .then(() => {
          if (this.status === 'revisi') {
            this.handleRevision();
          } else if (this.status === 'submit') {
            this.handleSubmit();
          }
        })
        .catch(() => {
          this.$message({
            type: 'info',
            message: 'Kirim data dibatalkan',
          });
        });
    },
    async handleRevision() {
      this.loadingSubmit = true;
      await kaReviewsResource.store({
        type: 'pemrakarsa',
        idProject: this.$route.params.id,
        notes: this.notes,
        status: 'revisi',
        documentType: this.getDocumentType,
      });
      this.getData();
      this.loadingSubmit = false;
    },
    async handleSubmit() {
      this.loadingSubmit = true;
      const reader = new FileReader();
      const formData = new FormData();
      formData.append('type', 'pemrakarsa');
      formData.append('idProject', this.$route.params.id);
      formData.append('status', 'submit');
      formData.append('file', reader.readAsText(this.file));
      formData.append('documentType', this.getDocumentType);
      const file = await this.convertBase64(this.file);
      formData.append('file', file);
      await kaReviewsResource.store(formData);
      this.printDocVersion();
      this.getData();
      this.$emit('submitPemrakarsa', true);
      this.file = null;
      this.fileName = null;
      this.loadingSubmit = false;
    },
    async printDocVersion() {
      await axios.get(`/api/dokumen-ukl-upl/${this.$route.params.id}?pdfVersi=true`);
    },
    showFileAlert() {
      this.$alert('Ukuran file tidak boleh lebih dari 1 MB', '', {
        center: true,
      });
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
