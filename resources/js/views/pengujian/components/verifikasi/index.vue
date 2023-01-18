<!-- eslint-disable vue/html-indent -->
<template>
  <div>
    <div class="filter-container" align="right">
      <el-button
        v-if="verifications.old_notes === null"
        :loading="loadingSubmit"
        type="primary"
        style="font-size: 0.8rem"
        @click="handleSubmit"
      >
        {{ 'Simpan Perubahan' }}
      </el-button>
    </div>
    <el-row :gutter="32">
      <el-col :sm="24" :md="12">
        <h4 align="center">FORMULIR KELENGKAPAN ADMINISTRASI KERANGKA ACUAN</h4>
        <el-table
          v-loading="loadingverification"
          :data="verifications.ka_forms"
          fit
          highlight-current-row
          :header-cell-style="{ background: '#3AB06F', color: 'white' }"
        >
          <el-table-column label="No." width="54px">
            <template slot-scope="scope">
              <span>{{ scope.$index + 1 }}</span>
            </template>
          </el-table-column>

          <el-table-column label="Data">
            <template slot-scope="scope">
              <a
                v-if="scope.row.type === 'download'"
                href="#"
                target="_blank"
                class="click"
                @click.prevent="downloadFile(scope.row.link)"
              >
                {{ formLabel[scope.row.name] }}
              </a>
              <span v-else>
                <span v-if="isRedirect(scope.row.name)">
                  <a
                    href="#"
                    class="click"
                    @click.prevent="handleRedirect(scope.row.name)"
                  >
                    {{ formLabel[scope.row.name] }}
                  </a>
                </span>
                <span v-else>
                  {{ formLabel[scope.row.name] }}
                  <span v-if="scope.row.name === 'peta'">
                    <li v-for="(link, index) in scope.row.link" :key="index">
                      <a
                        v-if="link.name === 'Webgis'"
                        href="#"
                        class="click"
                        @click.prevent="showWebgisDialog = true"
                      >
                        Webgis
                      </a>
                      <a
                        v-else
                        href="#"
                        class="click"
                        @click.prevent="
                          download(
                            scope.row.link[index].link,
                            scope.row.link[index].pdf
                          )
                        "
                      >
                        {{ scope.row.link[index].name }}
                      </a>
                    </li>
                  </span>
                </span>
              </span>
            </template>
          </el-table-column>

          <el-table-column label="Kesesuaian">
            <template slot-scope="scope">
              <el-select
                v-if="isShow(scope.row.name)"
                v-model="scope.row.suitability"
                placeholder="Pilih Kesesuaian"
                style="width: 100%"
                :disabled="verifications.is_disabled || isSecretary ? true:false"
              >
                <el-option
                  v-for="sesuai in kesesuaian"
                  :key="sesuai.value"
                  :label="sesuai.label"
                  :value="sesuai.value"
                />
              </el-select>
              <span v-else>-</span>
            </template>
          </el-table-column>

          <el-table-column label="Keterangan">
            <template slot-scope="scope">
              <el-input
                v-if="isShow(scope.row.name)"
                v-model="scope.row.description"
                type="textarea"
                :placeholder="getPlaceholder(scope.row.suitability)"
                :readonly="verifications.is_disabled || isSecretary ? true:false"
                :class="{ 'is-error': errors[`form-${scope.$index}`] }"
              />
              <span v-else>-</span>
            </template>
          </el-table-column>
        </el-table>
      </el-col>
      <el-col :sm="24" :md="12">
        <h4 v-if="verifications.is_complete !== null">
          Catatan<span style="color: red">*</span>
        </h4>
        <small
          v-if="verifications.is_complete !== null && errors.notes"
          style="color: #f56c6c"
        >
          Catatan Wajib Diisi
        </small>
        <Tinymce
          v-if="
            verifications.is_complete !== null &&
            verifications.old_notes === null
          "
          v-model="verifications.notes"
          output-format="html"
          :menubar="''"
          :image="false"
          :toolbar="[
            'bold italic underline bullist numlist  preview undo redo fullscreen',
          ]"
          :height="200"
        />
        <div v-else v-html="verifications.old_notes" />
        <div v-if="verifications.is_complete === null">
          <h4>Apakah Berkas Administrasi Lengkap dan Benar ?</h4>
          <el-button
            :loading="loadingComplete"
            type="primary"
            @click="handleComplete('ya')"
          >
            Ya
          </el-button>
          <el-button
            :loading="loadingComplete"
            type="danger"
            @click="handleComplete('tidak')"
          >
            Tidak
          </el-button>
        </div>
        <div
          v-if="verifications.is_complete === false"
          style="text-align: right; margin-top: 20px"
        >
          <el-button
            :loading="loadingDocx"
            type="primary"
            @click="handleDownload"
          >
            Unduh Hasil Pemeriksaan Berkas Administrasi
          </el-button>
        </div>
        <div v-if="verifications.is_complete === null" style="margin-top: 30px">
          <div style="display: flex">
            <el-checkbox v-model="setAuthority" />
            <p style="margin-top: 0; padding-top: 0; margin-left: 10px">
              Rencana usaha dan/atau kegiatan yang diajukan tidak sesuai
              kewenangannya
            </p>
          </div>
          <change-authority v-if="setAuthority" />
        </div>
      </el-col>
    </el-row>
    <el-dialog :visible.sync="lpjpPenyusunDialog">
      <div v-if="lpjp !== null">
        <p><b>Nama:</b> {{ lpjp.name }}</p>
        <p><b>No Registrasi:</b> {{ lpjp.reg_no }}</p>
        <p>
          <b>Dokumen Sertifikat:</b>
          <a :href="lpjp.cert_file" style="color: #216221" target="_blank">
            Lihat
          </a>
        </p>
      </div>
      <div v-else-if="penyusunMandiri !== null">
        <p><b>Nama:</b> {{ penyusunMandiri.name }}</p>
        <p>
          <b>SK Penunjukan Tim Penyusun:</b>
          <a
            :href="penyusunMandiri.sk_letter"
            style="color: #216221"
            target="_blank"
          >
            Lihat
          </a>
        </p>
      </div>
    </el-dialog>
    <el-dialog :visible.sync="formulatorTeamDialog">
      <FormulatorTeamTable />
    </el-dialog>
    <el-dialog
      title="Hasil Konsultasi Publik"
      :visible.sync="publicConsultationDialog"
    >
      <div v-if="publicConsultation !== null">
        <p><b>Tanggal:</b> {{ publicConsultation.event_date }}</p>
        <p><b>Jumlah Peserta:</b> {{ publicConsultation.participant }} Orang</p>
        <p><b>Lokasi:</b> {{ publicConsultation.location }}</p>
        <p><b>Alamat:</b> {{ publicConsultation.address }}</p>
        <div v-if="publicConsultationDocs !== null">
          <div v-for="(docs, index) in publicConsultationDocs" :key="index">
            <p>
              <b>{{ docs.doc_type }}:</b>
              <a :href="docs.url" style="color: #216221" target="_blank">
                Lihat
              </a>
            </p>
          </div>
        </div>
        <p>
          <b>Daftar Saran, Pendapat, dan Tanggapan Masyarakat</b>
          <a
            v-if="!loadingPDFSPT"
            href="#"
            style="color: #216221"
            @click.prevent="downloadPDFSPT"
          >
            Lihat
          </a>
          <a
            v-else
            href="#"
            style="color: #216221; cursor: default"
            @click.prevent
          >
            Loading...
          </a>
        </p>
        <p><b>Rangkuman Deskriptif atas Harapan Masyarakat:</b></p>
        <div v-html="publicConsultation.positive_feedback_summary" />
        <p><b>Rangkuman Deskriptif atas Kekhawatiran Masyarakat:</b></p>
        <div v-html="publicConsultation.negative_feedback_summary" />
      </div>
    </el-dialog>
    <el-dialog title="" class="map-dialog" :visible.sync="showWebgisDialog">
      <WebgisVerifikasi />
    </el-dialog>
  </div>
</template>

<script>
import Tinymce from '@/components/Tinymce';
import Resource from '@/api/resource';
import Docxtemplater from 'docxtemplater';
import PizZip from 'pizzip';
import PizZipUtils from 'pizzip/utils/index.js';
import FormulatorTeamTable from '@/views/pengujian/components/FormulatorTeamDialog';
import WebgisVerifikasi from '@/views/pengujian/components/verifikasi/Webgis';
import ChangeAuthority from './ChangeAuthority';
import { saveAs } from 'file-saver';
import axios from 'axios';
const verifikasiRapatResource = new Resource('testing-verification');

export default {
  name: 'Verifikasi',
  components: {
    Tinymce,
    WebgisVerifikasi,
    FormulatorTeamTable,
    ChangeAuthority,
  },
  data() {
    return {
      idProject: this.$route.params.id,
      verifications: {},
      description: null,
      is_validate: false,
      loadingverification: false,
      loadingSubmit: false,
      lpjpPenyusunDialog: false,
      lpjp: null,
      penyusunMandiri: null,
      publicConsultationDialog: false,
      formulatorTeamDialog: false,
      publicConsultation: null,
      publicConsultationDocs: [],
      docxData: {},
      loadingComplete: false,
      loadingDocx: false,
      loadingPDFSPT: false,
      showWebgisDialog: false,
      setAuthority: false,
      out: '',
      errors: {},
      kesesuaian: [
        {
          label: 'Sesuai',
          value: 'Sesuai',
        },
        {
          label: 'Tidak Sesuai',
          value: 'Tidak Sesuai',
        },
      ],
      formLabel: {
        tata_ruang:
          'Justifikasi/bukti kesesuaian lokasi rencana usaha dan/atau kegiatan dengan rencana tata ruang yang berlaku',
        pippib:
          'Justifikasi/bukti kesesuaian lokasi rencana usaha dan/atau kegiatan dengan PIPPIB',
        persetujuan_awal:
          'Justifikasi/bukti persetujuan awal rencana usaha dan/atau kegiatan',
        surat_penyusun:
          'Bukti Tanda Registrasi LPJP atau Surat pembentukan Tim Penyusun Amdal dari pihak pemrakarsa',
        sertifikasi_penyusun:
          'Bukti Tanda Sertifikasi Kompetensi penyusunan Amdal (minimal 1 orang KTPA dan 2 orang ATPA)',
        peta: 'Kesesuaian peta-peta yang disampaikan berdasarkan kaidah kartografi:',
        konsul_publik:
          'Bukti pengumuman di media massa dan konsultasi publik yang telah dilakukan beserta penunjukkan wakil masyarakat yang akan dilibatkan dalam rapat komisi',
        cv_penyusun: 'CV Penyusun Amdal',
        sistematika_penyusunan:
          'Sistematika penyusunan dokumen sesuai dengan PP 22/2021',
        hasil_penapisan:
          'Hasil Penapisan OSS Rencana Usaha dan/atau Kegiatan di OSS',
        dokumen_nib: 'Dokumen NIB Pemrakarsa',
        dokumen_sppl_oss: 'Dokumen SPPL dari oss',
      },
    };
  },
  computed: {
    isSecretary() {
      return this.$store.getters.user.roles.includes('examiner-secretary');
    },
  },
  created() {
    this.getVerifications();
  },
  methods: {
    async getVerifications() {
      this.loadingverification = true;
      const data = await verifikasiRapatResource.list({
        verification: 'true',
        idProject: this.idProject,
      });

      this.verifications = data;
      this.lpjp = data.lpjp;
      this.penyusunMandiri = data.penyusun_mandiri;
      if (data.public_consultation) {
        this.publicConsultation = data.public_consultation;
        if (data.public_consultation.docs) {
          this.publicConsultationDocs = data.public_consultation.docs.map(
            (x) => {
              return { ...JSON.parse(x.doc_json), url: x.file };
            }
          );
        }
      }
      this.loadingverification = false;
    },
    async handleSubmit() {
      if (this.idProject == null) {
        return;
      }

      if (this.verifications.is_complete !== null) {
        this.errors = {};
        let error = this.validationErrors();

        if (!this.verifications.notes) {
          error++;
          this.errors.notes = true;
        }

        if (error > 0) {
          return;
        }
      }

      this.loadingSubmit = true;
      await verifikasiRapatResource.store({
        idProject: this.idProject,
        verifications: this.verifications,
      });
      await this.getVerifications();
      this.$message({
        message: 'Data sukses tersimpan',
        type: 'success',
        duration: 5 * 1000,
      });
      this.loadingSubmit = false;
    },
    getPlaceholder(suitability) {
      if (suitability === 'Sesuai') {
        return 'Opsional';
      } else if (suitability === 'Tidak Sesuai') {
        return 'Wajib Diisi';
      }

      return '';
    },
    handleHasilPenapisan() {
      const currentProject = this.verifications.project;
      // change project_year to string
      currentProject.project_year = currentProject.project_year.toString();
      // change field to number and formulator team
      currentProject.field = Number(currentProject.field);
      currentProject.id_formulator_team = Number(
        currentProject.id_formulator_team
      );

      this.$router.push({
        name: 'publishProject',
        params: { project: currentProject, readonly: true },
      });
    },
    isRedirect(name) {
      return (
        name === 'sertifikasi_penyusun' ||
        name === 'cv_penyusun' ||
        name === 'surat_penyusun' ||
        name === 'konsul_publik'
      );
    },
    handleRedirect(name) {
      if (name === 'sertifikasi_penyusun' || name === 'cv_penyusun') {
        this.formulatorTeamDialog = true;
      } else if (name === 'surat_penyusun') {
        this.lpjpPenyusunDialog = true;
      } else if (name === 'konsul_publik') {
        this.publicConsultationDialog = true;
      }
    },
    async handleDownload() {
      this.loadingDocx = true;
      const data = await verifikasiRapatResource.list({
        exportNoDocx: 'true',
        idProject: this.idProject,
      });
      this.docxData = data.title;
      const a = document.createElement('a');
      a.href = data.url;
      a.setAttribute('download', `hasil-adm-${data.title}.docx`);
      a.click();
      this.loadingDocx = false;
    },
    async downloadPDFSPT() {
      this.loadingPDFSPT = true;

      axios({
        url: `api/testing-verification`,
        method: 'GET',
        responseType: 'blob',
        params: {
          idProject: this.$route.params.id,
          spt: 'true',
        },
      })
        .then((response) => {
          this.loadingPDFSPT = false;
          var fileURL = window.URL.createObjectURL(new Blob([response.data]));
          var fileLink = document.createElement('a');
          fileLink.href = fileURL;
          fileLink.setAttribute('download', 'Rekap SPT Masyarakat.pdf');
          document.body.appendChild(fileLink);
          fileLink.click();
        })
        .catch((err) => {
          err = '';
          this.loadingPDFSPT = false;
        });
    },
    exportDocx() {
      PizZipUtils.getBinaryContent(
        '/template_berkas_adm_no.docx',
        (error, content) => {
          if (error) {
            throw error;
          }
          const zip = new PizZip(content);
          const doc = new Docxtemplater(zip, {
            paragraphLoop: true,
            linebreaks: true,
          });
          doc.render({
            docs_date: this.docxData.docs_date,
            pemrakarsa: this.docxData.pemrakarsa,
            pemrakarsa_address: this.docxData.pemrakarsa_address,
            project_title: this.docxData.project_title,
            project_address: this.docxData.project_address,
            notes: this.docxData.notes,
            ketua_tuk_name: this.docxData.ketua_tuk_name,
            ketua_tuk_nip: this.docxData.ketua_tuk_nip,
            document_type: 'KA',
          });

          const out = doc.getZip().generate({
            type: 'blob',
            mimeType:
              'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          });

          this.out = out;
          saveAs(this.out, `${this.docxData.project_title.toLowerCase()}.docx`);
          this.loadingDocx = false;
        }
      );
    },
    handleComplete(decision) {
      this.$confirm(
        'Apakah anda yakin ? Data yang sudah disimpan, tidak dapat diubah lagi.',
        'Warning',
        {
          confirmButtonText: 'Ya',
          cancelButtonText: 'Tidak',
          type: 'warning',
        }
      )
        .then(() => {
          this.errors = {};
          const error = this.validationErrors();
          if (error === 0) {
            this.handleSaveComplete(decision);
          }
        })
        .catch(() => {
          this.$message({
            type: 'info',
            message: 'Simpan data dibatalkan',
          });
        });
    },
    async handleSaveComplete(decision) {
      this.loadingComplete = true;
      await verifikasiRapatResource.store({
        complete: 'true',
        idProject: this.idProject,
        verifications: this.verifications,
        decision,
      });
      this.getVerifications();
      this.$emit('changeIsComplete', { isComplete: decision === 'ya' });
      this.loadingComplete = false;
    },
    validationErrors() {
      const forms = this.verifications.ka_forms;
      let error = 0;
      for (let i = 0; i < forms.length; i++) {
        if (
          (forms[i].name === 'tata_ruang' ||
            forms[i].nane === 'persetujuan_awal') &&
          !this.verifications.pre_agreement
        ) {
          continue;
        }

        if (forms[i].suitability === 'Tidak Sesuai' && !forms[i].description) {
          error++;
          this.errors[`form-${i}`] = true;
        }
      }
      return error;
    },
    isShow(name) {
      if (name === 'tata_ruang' && !this.verifications.ktr) {
        return false;
      }

      if (name === 'persetujuan_awal' && !this.verifications.pre_agreement) {
        return false;
      }

      if (name === 'pippib' && !this.verifications.pippib) {
        return false;
      }

      return true;
    },
    download(url, urlPdf) {
      if (url || urlPdf) {
        if (
          (url === '/storage/' || url === null) &&
          (urlPdf === '/storage/' || urlPdf === null)
        ) {
          this.$alert('Dokumen belum Diunggah oleh Pemrakarsa', {
            confirmButtonText: 'OK',
          });
        } else {
          if (url) {
            if (url !== '/storage/') {
              var a = document.createElement('a');
              a.href = url;
              a.setAttribute('download', url);
              a.click();
            }
          }

          if (urlPdf) {
            if (urlPdf !== '/storage/') {
              window.open(urlPdf);
            }
          }
        }
      } else {
        this.$alert('Dokumen belum Diunggah oleh Pemrakarsa', {
          confirmButtonText: 'OK',
        });
      }
    },
    downloadFile(data) {
      if (data === null || data === '/storage/') {
        this.$alert('Dokumen belum Diunggah oleh Pemrakarsa', {
          confirmButtonText: 'OK',
        });
      } else {
        window.open(data);
      }
    },
  },
};
</script>

<style scoped>
.click {
  color: #5688e4;
}
</style>

<style>
.is-error .el-input__inner,
.is-error .el-radio__inner,
.is-error .el-textarea__inner {
  border-color: #f56c6c;
}
.map-dialog .el-dialog__body {
  height: 600px;
}
</style>
