<!-- eslint-disable vue/html-indent -->
<template>
  <div>
    <el-row :gutter="32">
      <el-col :md="24" :sm="24">
        <h4 align="center">UNDANGAN RAPAT</h4>
        <div style="text-align: right; margin-bottom: 10px">
          <el-button
            :loading="loadingSubmit"
            type="primary"
            :disabled="isSecretary ? true:false"
            @click="handleSubmit"
          >
            Simpan Perubahan
          </el-button>
          <el-button
            :loading="loadingInvitationDocx"
            type="primary"
            :disabled="isSecretary ? true:false"
            @click="handleDownloadInvitation"
          >
            Undangan Rapat
          </el-button>
          <el-button
            v-if="meetings.invitation_file"
            style="margin-top: 10px"
            :loading="loadingSendInvitation"
            type="primary"
            :disabled="isSecretary ? true:false"
            @click="sendInvitation"
          >
            Kirim Undangan Rapat
          </el-button>
        </div>
        <el-form
          v-loading="loading"
          label-position="top"
          label-width="200px"
          style="max-width: 100%"
        >
          <el-row :gutter="32">
            <el-col :sm="24" :md="12">
              <el-form-item label="Tanggal Rapat">
                <el-date-picker
                  v-model="meetings.meeting_date"
                  type="date"
                  value-format="yyyy-MM-dd"
                  placeholder="Pilih tanggal"
                  style="width: 100%"
                  :disabled="isSecretary ? true:false"
                />
              </el-form-item>
            </el-col>
            <el-col :sm="12" :md="6">
              <el-form-item label="Waktu Rapat">
                <el-time-picker
                  v-model="meetings.meeting_time"
                  placeholder="Waktu Rapat"
                  format="HH:mm"
                  value-format="HH:mm"
                  style="width: 100%"
                  :disabled="isSecretary ? true:false"
                />
              </el-form-item>
            </el-col>
            <el-col :sm="12" :md="6">
              <el-form-item label="Zona Waktu">
                <el-select
                  v-model="meetings.zone"
                  placeholder="Pilih Zona Waktu"
                  style="width: 100%"
                  :disabled="isSecretary ? true:false"
                >
                  <el-option
                    v-for="item in zone"
                    :key="item.value"
                    :label="item.label"
                    :value="item.value"
                  />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :sm="24" :md="12">
              <el-form-item label="Tempat Rapat">
                <el-input v-model="meetings.location" :disabled="isSecretary ? true:false" />
              </el-form-item>
            </el-col>
            <el-col :sm="24" :md="12">
              <el-form-item label="Unggah Undangan Rapat Final">
                <el-button
                  v-if="meetings.invitation_file"
                  type="text"
                  size="medium"
                  icon="el-icon-download"
                  style="color: blue"
                  @click.prevent="download(meetings.invitation_file)"
                >
                  Download Undangan Rapat Final
                </el-button>
                <el-upload
                  action="#"
                  :auto-upload="false"
                  :on-change="handleUploadInvitationFile"
                  :show-file-list="false"
                  style="display: inline"
                  :disabled="isSecretary ? true:false"
                >
                  <el-tooltip
                    class="item"
                    effect="dark"
                    content="Unggah Undangan Rapat Final yang sudah ditandatangani"
                    placement="top-start"
                  >
                    <el-button size="small" type="warning" :disabled="isSecretary ? true:false"> Upload </el-button>
                  </el-tooltip>
                  <div slot="tip" class="el-upload__tip">
                    {{ invitation_file_name }}
                  </div>
                </el-upload>
              </el-form-item>
            </el-col>
            <el-col :sm="24" :md="12">
              <el-form-item label="Apakah Rapat Akan diadakan Online?">
                <el-checkbox v-model="status" :disabled="isSecretary ? true:false"><span style="font-size: 90%;">Checklist Jika 'Ya'</span></el-checkbox>
              </el-form-item>
            </el-col>
            <el-col v-if="status === true" :sm="24" :md="12">
              <el-form-item label="Link Meeting (Online)">
                <el-input v-model="meetings.link" />
              </el-form-item>
            </el-col>
          </el-row>
        </el-form>
      </el-col>
      <el-col :md="24" :sm="24">
        <h5>Daftar Undangan</h5>
        <el-table
          v-loading="loadingTuk"
          :data="meetings.invitations"
          fit
          highlight-current-row
          :header-cell-style="{ background: '#3AB06F', color: 'white' }"
        >
          <el-table-column width="30px">
            <template slot-scope="scope">
              <span>{{ scope.$index + 1 }}</span>
            </template>
          </el-table-column>

          <el-table-column label="Peran">
            <template slot-scope="scope">
              <el-select
                v-model="scope.row.role"
                placeholder="Pilih Peran"
                style="width: 100%"
                :disabled="isSecretary ? true:false"
                @change="handleChangeRole($event, scope.$index)"
              >
                <el-option
                  v-for="item in peran"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                />
              </el-select>
            </template>
          </el-table-column>

          <el-table-column label="Nama Anggota">
            <template slot-scope="scope">
              <span v-if="scope.row.type == 'Ketua TUK'">{{
                scope.row.name
              }}</span>
              <el-select
                v-else-if="
                  scope.row.type === 'Anggota TUK' ||
                  scope.row.type === 'Anggota Sekretariat TUK'
                "
                v-model="scope.row.tuk_member_id"
                placeholder="Pilih Anggota"
                style="width: 100%"
                filterable
                :disabled="isSecretary ? true:false"
                @change="handleChangeName($event, scope.row.type, scope.$index)"
              >
                <el-option
                  v-for="item in scope.row.tuk_options"
                  :key="item.id"
                  :label="item.name"
                  :value="item.id"
                />
              </el-select>
              <el-input v-else v-model="scope.row.name" />
            </template>
          </el-table-column>

          <el-table-column label="Instansi">
            <template slot-scope="scope">
              <span
                v-if="
                  scope.row.type == 'Ketua TUK' ||
                  scope.row.type == 'Anggota TUK' ||
                  scope.row.type == 'Anggota Sekretariat TUK'
                "
              >
                {{ scope.row.institution }}
              </span>
              <el-select
                v-else-if="isGovernmentInstitution(scope.row.role)"
                v-model="scope.row.id_government_institution"
                placeholder="Pilih Instansi"
                style="width: 100%"
                filterable
                :disabled="isSecretary ? true:false"
              >
                <el-option
                  v-for="item in scope.row.institution_options"
                  :key="item.id"
                  :label="item.name"
                  :value="item.id"
                />
              </el-select>
              <el-input v-else v-model="scope.row.institution" />
            </template>
          </el-table-column>

          <el-table-column label="E-mail">
            <template slot-scope="scope">
              <div>
                <div
                  v-if="
                    scope.row.type == 'Ketua TUK' ||
                    scope.row.type === 'Anggota TUK' ||
                    scope.row.type === 'Anggota Sekretariat TUK'
                  "
                  class="email-column"
                >
                  <span>
                    {{ scope.row.email }}
                  </span>
                  <el-button
                    type="text"
                    icon="el-icon-close"
                    style="display: block"
                    :disabled="isSecretary ? true:false"
                    @click.prevent="deleteRow(scope.$index, scope.row.id)"
                  />
                </div>
                <div v-else class="email-column">
                  <el-input v-model="scope.row.email" />
                  <el-button
                    type="text"
                    icon="el-icon-close"
                    style="display: block"
                    :disabled="isSecretary ? true:false"
                    @click.prevent="deleteRow(scope.$index, scope.row.id)"
                  />
                </div>
              </div>
            </template>
          </el-table-column>
        </el-table>
        <div style="margin-top: 10px">
          <el-button icon="el-icon-plus" circle :disabled="isSecretary ? true:false" @click.prevent="addTableRow" />
        </div>
        <div v-if="meetings.type === 'update'" style="text-align: right">
          <el-button
            :loading="loadingDocx"
            type="primary"
            style="margin-top: 10px"
            @click="handleDownload"
          >
            Unduh Hasil Pemeriksaan Berkas Administrasi
          </el-button>
          <el-upload
            :auto-upload="false"
            :on-change="checkHandleUploadChange"
            :show-file-list="false"
            action=""
          >
            <el-button
              :loading="loadingUpload"
              type="primary"
              style="margin-top: 10px"
              :disabled="isSecretary ? true:false"
            >
              Unggah Validasi Hasil Pemeriksaan Berkas Administrasi
            </el-button>
          </el-upload>
          <small v-if="errors.dokumen_file" style="color: #f56c6c">
            <span v-for="(error, index) in errors.dokumen_file" :key="index">
              {{ error }}
            </span>
          </small>
          <div v-if="meetings.file" style="text-align: right">
            <el-button
              type="text"
              size="medium"
              icon="el-icon-download"
              style="color: blue"
              @click.prevent="download(meetings.file)"
            >
              Unduh Validasi Hasil Pemeriksaan Berkas Administrasi
            </el-button>
          </div>
        </div>
      </el-col>
    </el-row>
  </div>
</template>

<script>
import Resource from '@/api/resource';
import Docxtemplater from 'docxtemplater';
import PizZip from 'pizzip';
import PizZipUtils from 'pizzip/utils/index.js';
import { saveAs } from 'file-saver';
const undanganRapatResource = new Resource('testing-meeting');
const institutionResource = new Resource('government-institution');

export default {
  name: 'UndanganRapat',
  data() {
    return {
      timUjiKelayakan: [],
      zone: [
        {
          label: 'WIB',
          value: 'WIB',
        },
        {
          label: 'WIT',
          value: 'WIT',
        },
        {
          label: 'WITA',
          value: 'WITA',
        },
      ],
      peran: [
        {
          label: 'Ketua TUK',
          value: 'Ketua TUK',
        },
        {
          label: 'Anggota TUK',
          value: 'Anggota TUK',
        },
        {
          label: 'Anggota Sekretariat TUK',
          value: 'Anggota Sekretariat TUK',
        },
        {
          label: 'Kementerian',
          value: 'Kementerian',
        },
        {
          label: 'Lembaga',
          value: 'Lembaga',
        },
        {
          label: 'Pemerintah Provinsi',
          value: 'Pemerintah Provinsi',
        },
        {
          label: 'Pemerintah Kabupaten/Kota',
          value: 'Pemerintah Kabupaten/Kota',
        },
        {
          label: 'Tenaga Ahli',
          value: 'Tenaga Ahli',
        },
        {
          label: 'Lainnya',
          value: 'Lainnya',
        },
      ],
      loadingTuk: false,
      meetings: {},
      role: null,
      status: false,
      idProject: this.$route.params.id,
      loading: false,
      loadingSubmit: false,
      loadingDocx: false,
      loadingUpload: false,
      errors: {},
      loadingSendInvitation: false,
      docxData: {},
      invitationDocxData: {},
      loadingInvitationDocx: false,
      invitation_file: null,
      invitation_file_name: null,
      out: '',
      outInvitation: '',
      governmentInstitutions: [],
      members: [],
    };
  },
  computed: {
    baFileName() {
      const arrName = this.meetings.file.split('/');
      return arrName[arrName.length - 1];
    },
    isSecretary() {
      return this.$store.getters.user.roles.includes('examiner-secretary');
    },
  },
  async created() {
    this.loading = true;
    await this.getTukMembers();
    await this.getGovernmentInstitutions();
    await this.getMeetings();
  },
  methods: {
    async getMeetings() {
      this.loading = true;
      this.loadingTuk = true;
      const data = await undanganRapatResource.list({
        idProject: this.idProject,
      });
      const invitations = data.invitations.map((x) => {
        let institution_options = [];
        let tuk_options = [];

        if (this.isGovernmentInstitution(x.role)) {
          institution_options = this.governmentInstitutions.filter(
            (y) => y.institution_type === x.role
          );
        } else if (x.role === 'Anggota TUK') {
          tuk_options = this.members.filter(
            (x) => x.role === 'Anggota' || x.role === 'Kepala Sekretariat'
          );
        } else if (x.role === 'Anggota Sekretariat TUK') {
          tuk_options = this.members.filter(
            (x) => x.role === 'Anggota Sekretariat'
          );
        }

        return {
          id: x.id,
          role: x.role,
          name: x.name,
          email: x.email,
          type: x.type,
          type_member: x.type_member,
          id_government_institution: x.id_government_institution,
          institution_options,
          institution: x.institution,
          tuk_options,
          tuk_member_id: x.tuk_member_id,
        };
      });
      data.invitations = invitations;
      this.meetings = data;
      this.loading = false;
      this.loadingTuk = false;
    },
    async getTukMembers() {
      this.members = await undanganRapatResource.list({
        tukMember: 'true',
        idProject: this.$route.params.id,
      });
    },
    async handleSubmit() {
      this.loadingSubmit = true;
      const invitations = this.meetings.invitations.map((x) => {
        const newX = x;
        newX.institution_options = [];
        newX.tuk_options = [];
        return newX;
      });
      this.meetings.invitations = invitations;

      const formData = new FormData();
      formData.append('idProject', this.idProject);
      formData.append('meetings', JSON.stringify(this.meetings));

      if (this.invitation_file) {
        const file = await this.convertBase64(this.invitation_file);
        formData.append('invitation_file', file);
      }

      await undanganRapatResource.store(formData);
      this.$message({
        message: 'Data sukses tersimpan',
        type: 'success',
        duration: 5 * 1000,
      });
      this.invitation_file = null;
      this.invitation_file_name = null;
      this.getMeetings();
      this.loadingSubmit = false;
    },
    async getGovernmentInstitutions() {
      this.governmentInstitutions = await institutionResource.list({
        meeting: 'true',
      });
    },
    async handleChangeTimUji(val) {
      this.loadingTuk = true;
      const data = await undanganRapatResource.list({
        tuk_member: 'true',
        tuk_id: val,
      });
      this.meetings.invitations = data;
      this.loadingTuk = false;
    },
    addTableRow() {
      this.meetings.invitations.push({
        id: null,
        role: null,
        name: null,
        email: null,
        type: 'other',
        type_member: 'other',
        id_government_institution: null,
        institution_options: [],
        institution: null,
        tuk_options: [],
        tuk_member_id: null,
      });
    },
    isGovernmentInstitution(role) {
      return (
        role === 'Kementerian' ||
        role === 'Lembaga' ||
        role === 'Pemerintah Provinsi' ||
        role === 'Pemerintah Kabupaten/Kota'
      );
    },
    handleChangeRole(val, idx) {
      this.meetings.invitations[idx].tuk_member_id = null;

      if (this.isGovernmentInstitution(val)) {
        const institution = this.governmentInstitutions.filter(
          (x) => x.institution_type === val
        );
        this.meetings.invitations[idx].institution_options = institution;
        this.meetings.invitations[idx].name = null;
        this.meetings.invitations[idx].email = null;
        this.meetings.invitations[idx].institution = null;
        this.meetings.invitations[idx].type = 'institution';
        this.meetings.invitations[idx].type_member = 'institution';
      } else {
        if (val === 'Ketua TUK') {
          const ketua = this.members.find((x) => x.role === 'Ketua');
          if (ketua) {
            const checkKetua = this.meetings.invitations.find(
              (x) => x.role === 'Ketua TUK' && x.tuk_member_id === ketua.id
            );

            if (checkKetua) {
              this.$alert('Ketua TUK hanya bisa ditambahkan satu kali', {
                confirmButtonText: 'OK',
              });
              this.meetings.invitations[idx].role = null;
            } else {
              this.meetings.invitations[idx] = {
                id: null,
                role: 'Ketua TUK',
                name: ketua.name,
                email: ketua.email,
                type: 'Ketua TUK',
                type_member: 'Ketua TUK',
                institution: ketua.institution,
                tuk_options: [],
                tuk_member_id: ketua.id,
              };
            }
          }
        } else if (val === 'Anggota TUK' || val === 'Anggota Sekretariat TUK') {
          this.meetings.invitations[idx].role = val;
          this.meetings.invitations[idx].type = val;
          this.meetings.invitations[idx].type_member = val;
          this.meetings.invitations[idx].name = null;
          this.meetings.invitations[idx].email = null;
          this.meetings.invitations[idx].institution = null;

          if (val === 'Anggota TUK') {
            this.meetings.invitations[idx].tuk_options = this.members.filter(
              (x) => x.role === 'Anggota' || x.role === 'Kepala Sekretariat'
            );
          } else {
            this.meetings.invitations[idx].tuk_options = this.members.filter(
              (x) => x.role === 'Anggota Sekretariat'
            );
          }
        }
      }
    },
    handleChangeName(val, type, idx) {
      let tuk = null;
      const checkTuk = this.meetings.invitations.filter(
        (x) => x.role === type && x.tuk_member_id === val
      );

      if (checkTuk.length > 1) {
        this.$alert(
          'Anggota/Anggota Sekretariat TUK hanya bisa ditambahkan satu kali',
          {
            confirmButtonText: 'OK',
          }
        );
        this.meetings.invitations[idx].tuk_member_id = null;
        this.meetings.invitations[idx].institution = null;
        this.meetings.invitations[idx].email = null;
      } else {
        if (type === 'Anggota TUK') {
          tuk = this.meetings.invitations[idx].tuk_options.find(
            (x) => x.id === val && x.type === 'tuk'
          );
        } else {
          tuk = this.meetings.invitations[idx].tuk_options.find(
            (x) => x.id === val && x.type === 'tuk_secretary'
          );
        }

        if (tuk) {
          this.meetings.invitations[idx].institution = tuk.institution;
          this.meetings.invitations[idx].email = tuk.email;
        }
      }
    },
    capitalize(mySentence) {
      const words = mySentence.split(' ');

      const newWords = words
        .map((word) => {
          return (
            word.toLowerCase()[0].toUpperCase() +
            word.toLowerCase().substring(1)
          );
        })
        .join(' ');
      return newWords;
    },
    async handleDownload() {
      this.loadingDocx = true;
      const data = await undanganRapatResource.list({
        dokumen: 'true',
        idProject: this.idProject,
      });
      this.docxData = data.title;
      const a = document.createElement('a');
      a.href = data.url;
      a.setAttribute('download', `berkas-adm-${data.title}.docx`);
      a.click();
      this.loadingDocx = false;
    },
    async handleDownloadInvitation() {
      if (this.meetings.type === 'update') {
        this.loadingInvitationDocx = true;
        const data = await undanganRapatResource.list({
          meetingInvitation: 'true',
          idProject: this.idProject,
        });
        this.invitationDocxData = data.title;
        const a = document.createElement('a');
        a.href = data.url;
        a.setAttribute('download', `ka-${data.title}.docx`);
        a.click();
        this.loadingInvitationDocx = false;
      } else {
        this.$alert('Mohon Isi dan Simpan Data terlebih dahulu', {
          confirmButtonText: 'OK',
        });
      }
    },
    exportDocx() {
      PizZipUtils.getBinaryContent(
        '/template_berkas_adm_yes.docx',
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
            project_title: this.docxData.project_title,
            pemrakarsa: this.docxData.pemrakarsa,
            project_description: this.docxData.project_description,
            project_location: this.docxData.project_location,
            meeting_time: this.docxData.meeting_time,
            docs_date: this.docxData.docs_date,
            ketua_tuk: this.docxData.ketua_tuk,
            notes: this.docxData.notes,
            tata_ruang_yes: this.docxData.forms.tata_ruang_yes,
            tata_ruang_no: this.docxData.forms.tata_ruang_no,
            tata_ruang_ket: this.docxData.forms.tata_ruang_ket,
            persetujuan_awal_yes: this.docxData.forms.persetujuan_awal_yes,
            persetujuan_awal_no: this.docxData.forms.persetujuan_awal_no,
            persetujuan_awal_ket: this.docxData.forms.persetujuan_awal_ket,
            hasil_penapisan_yes: this.docxData.forms.hasil_penapisan_yes,
            hasil_penapisan_no: this.docxData.forms.hasil_penapisan_no,
            hasil_penapisan_ket: this.docxData.forms.hasil_penapisan_ket,
            surat_penyusun_yes: this.docxData.forms.surat_penyusun_yes,
            surat_penyusun_no: this.docxData.forms.surat_penyusun_no,
            surat_penyusun_ket: this.docxData.forms.surat_penyusun_ket,
            sertifikasi_penyusun_yes:
              this.docxData.forms.sertifikasi_penyusun_yes,
            sertifikasi_penyusun_no:
              this.docxData.forms.sertifikasi_penyusun_no,
            sertifikasi_penyusun_ket:
              this.docxData.forms.sertifikasi_penyusun_ket,
            peta_yes: this.docxData.forms.peta_yes,
            peta_no: this.docxData.forms.peta_no,
            peta_ket: this.docxData.forms.peta_ket,
            konsul_publik_yes: this.docxData.forms.konsul_publik_yes,
            konsul_publik_no: this.docxData.forms.konsul_publik_no,
            konsul_publik_ket: this.docxData.forms.konsul_publik_ket,
            cv_penyusun_yes: this.docxData.forms.cv_penyusun_yes,
            cv_penyusun_no: this.docxData.forms.cv_penyusun_no,
            cv_penyusun_ket: this.docxData.forms.cv_penyusun_ket,
            sistematika_penyusunan_yes:
              this.docxData.forms.sistematika_penyusunan_yes,
            sistematika_penyusunan_no:
              this.docxData.forms.sistematika_penyusunan_no,
            sistematika_penyusunan_ket:
              this.docxData.forms.sistematika_penyusunan_ket,
            tim_penyusun: this.docxData.tim_penyusun,
            anggota_penyusun: this.docxData.anggota_penyusun,
            meeting_invitations: this.docxData.meeting_invitations,
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
    exportInvitationDocx() {
      PizZipUtils.getBinaryContent(
        '/template_undangan_rapat_ka.docx',
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
            project_title: this.invitationDocxData.project_title,
            project_address: this.invitationDocxData.project_address,
            pemrakarsa: this.invitationDocxData.pemrakarsa,
            pemrakarsa_address: this.invitationDocxData.pemrakarsa_address,
            docs_date: this.invitationDocxData.docs_date,
            meeting_date: this.invitationDocxData.meeting_date,
            meeting_time: this.invitationDocxData.meeting_time,
            meeting_location: this.invitationDocxData.meeting_location,
            authority_big_check: this.invitationDocxData.authority_big_check,
            authority_big: this.invitationDocxData.authority_big,
            tuk_address: this.invitationDocxData.tuk_address,
            authority: this.invitationDocxData.authority,
            ketua_tuk_name: this.invitationDocxData.ketua_tuk_name,
            ketua_tuk_nip: this.invitationDocxData.ketua_tuk_nip,
            tuk_member: this.invitationDocxData.tuk_member,
            pakar: this.invitationDocxData.pakar,
          });

          const out = doc.getZip().generate({
            type: 'blob',
            mimeType:
              'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          });

          this.outInvitation = out;
          saveAs(
            this.outInvitation,
            `${this.invitationDocxData.project_title.toLowerCase()}.docx`
          );
          this.loadingInvitationDocx = false;
        }
      );
    },
    checkHandleUploadChange(file, fileList) {
      if (file.raw.size > 5242880) {
        this.showFileAlert();
        return;
      } else {
        this.handleUploadChange(file);
      }
    },
    async handleUploadChange(file) {
      this.loadingUpload = true;
      const formData = new FormData();
      formData.append('idProject', this.idProject);
      formData.append('file', 'true');
      const fileUpload = await this.convertBase64(file.raw);
      formData.append('dokumen_file', fileUpload);
      const data = await undanganRapatResource.store(formData);
      this.errors = data.errors === null ? {} : data.errors;
      this.loadingUpload = false;
      if (data.errors === null) {
        this.meetings.file = data.name;
        this.$message({
          message: 'Dokumen sukses diupload',
          type: 'success',
          duration: 5 * 1000,
        });
      }
    },
    async sendInvitation() {
      this.loadingSendInvitation = true;
      try {
        const response = await undanganRapatResource.store({
          invitation: 'true',
          idProject: this.idProject,
          role: this.$store.getters.roles,
        });
        if (response.error === 0) {
          this.$message({
            message: 'Undangan Berhasil Dikirim',
            type: 'success',
            duration: 5 * 1000,
          });
        } else if (response.error === 1) {
          this.$message({
            message: 'Undangan Gagal Dikirim',
            type: 'error',
            duration: 5 * 1000,
          });
        }
      } catch (error) {
        this.$message({
          message: 'Undangan Gagal Dikirim',
          type: 'error',
          duration: 5 * 1000,
        });
      }
      this.loadingSendInvitation = false;
    },
    handleUploadInvitationFile(file, fileList) {
      if (file.raw.size > 5242880) {
        this.showFileAlert();
        return;
      }

      const extension = file.name.split('.');
      if (extension[extension.length - 1].toLowerCase() !== 'pdf') {
        this.$alert('File harus berformat PDF', '', {
          center: true,
        });
        return;
      }

      this.invitation_file = file.raw;
      this.invitation_file_name = file.name;
    },
    download(url) {
      window.open(url, '_blank').focus();
    },
    deleteRow(idx, id) {
      if (id) {
        this.meetings.deleted_invitations.push(id);
      }

      this.meetings.invitations.splice(idx, 1);
    },
    showFileAlert() {
      this.$alert('Ukuran file tidak boleh lebih dari 5 MB', '', {
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

<style scoped>
.email-column {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>

<style>
.upload-demo {
  font-size: 0.8rem;
  background: #e2cd39;
  padding: 8px;
  color: white;
  border-radius: 3px;
  margin-top: 10px;
  width: fit-content;
}
</style>
