<!-- eslint-disable vue/html-indent -->
<template>
  <div>
    <h5>Daftar Hadir</h5>
    <el-table
      v-loading="loadingtuk"
      :data="invitations"
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
          <span v-if="scope.row.type == 'Ketua TUK'">{{ scope.row.name }}</span>
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
    <div style="margin-top: 13px">
      <h5>Ringkasan Masukan dan Komentar</h5>
      <Tinymce
        v-model="reports.notes"
        v-loading="loadingtuk"
        output-format="html"
        :readonly="isSecretary ? '1':'0'"
        :menubar="''"
        :image="false"
        :toolbar="isSecretary ? ['fullscreen']:[
          'bold italic underline bullist numlist  preview undo redo fullscreen',
        ]"
        :height="200"
      />
    </div>
    <el-upload
      :auto-upload="false"
      :on-change="checkHandleUploadChange"
      :show-file-list="false"
      action=""
    >
      <el-button
        :loading="loadingUpload"
        type="warning"
        style="margin-top: 10px"
        :disabled="isSecretary ? true:false"
      >
        Unggah BA Final
      </el-button>
    </el-upload>
    <div v-if="reports.file">
      <el-button
        type="text"
        size="medium"
        icon="el-icon-download"
        style="color: blue"
        @click.prevent="download(reports.file)"
      >
        <!-- {{ baFileName }} -->
        Unduh Berita Acara Final
      </el-button>
    </div>
    <el-upload
      :auto-upload="false"
      :on-change="checkHandleUploadChangeRapatTimTeknis"
      :show-file-list="false"
      action=""
    >
      <el-button
        :loading="loadingUpload"
        type="warning"
        style="margin-top: 10px"
        :disabled="isSecretary ? true:false"
      >
        Unggah BA Final Rapat Tim Teknis
      </el-button>
    </el-upload>
    <div v-if="reportsrapat.file">
      <el-button
        type="text"
        size="medium"
        icon="el-icon-download"
        style="color: blue"
        @click.prevent="download(reportsrapat.file)"
      >
        <!-- {{ baFileName }} -->
        Unduh Berita Acara Final Tim Teknis
      </el-button>
    </div>
    <small
      v-if="errors.dokumen_file"
      style="color: #f56c6c; display: block; margin-top: 5px"
    >
      <span v-for="(error, index) in errors.dokumen_file" :key="index">
        {{ error }}
      </span>
    </small>
  </div>
</template>

<script>
import Tinymce from '@/components/Tinymce';
import Resource from '@/api/resource';
const meetingReportResource = new Resource('meet-report-rkl-rpl');

export default {
  name: 'DaftarHadir',
  components: {
    Tinymce,
  },
  props: {
    invitations: {
      type: Array,
      default: () => [],
    },
    reports: {
      type: Object,
      default: () => {},
    },
    reportsrapat: {
      type: Object,
      default: () => {},
    },
    loadingtuk: Boolean,
    governmentinstitutions: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
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
      errors: {},
      loadingUpload: false,
    };
  },
  computed: {
    baFileName() {
      const arrName = this.reports.file.split('/');
      return arrName[arrName.length - 1];
    },
    isSecretary() {
      return this.$store.getters.user.roles.includes('examiner-secretary');
    },
  },
  methods: {
    addTableRow() {
      this.invitations.push({
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
      this.$emit('handleChangeRole', { val, idx });
    },
    handleChangeName(val, type, idx) {
      this.$emit('handleChangeName', { val, type, idx });
    },
    deleteRow(idx, id) {
      this.$emit('deleteinvitation', { idx, id });
    },
    checkHandleUploadChange(file, fileList) {
      if (file.raw.size > 5242880) {
        this.showFileAlert();
        return;
      } else {
        this.handleUploadChange(file);
      }
    },
    checkHandleUploadChangeRapatTimTeknis(file, fileList) {
      if (file.raw.size > 5242880) {
        this.showFileAlert();
        return;
      } else {
        this.handleUploadChangeRapatTimTeknis(file);
      }
    },
    async handleUploadChange(file) {
      if (this.reports.type === 'update') {
        this.loadingUpload = true;
        const formData = new FormData();
        formData.append('idProject', this.$route.params.id);
        formData.append('file', 'true');
        const fileUpload = await this.convertBase64(file.raw);
        formData.append('dokumen_file', fileUpload);
        const data = await meetingReportResource.store(formData);
        this.errors = data.errors === null ? {} : data.errors;
        this.loadingUpload = false;
        if (data.errors === null) {
          this.$emit('updateuploadfile', { name: data.name });
          this.$message({
            message: 'BA Final sukses diupload',
            type: 'success',
            duration: 5 * 1000,
          });
        }
      } else {
        this.$alert(
          'Mohon Simpan dan Unduh Draft Berita Acara terlebih dahulu',
          {
            confirmButtonText: 'OK',
          }
        );
      }
    },
    async handleUploadChangeRapatTimTeknis(file) {
      if (this.reports.type === 'update') {
        this.loadingUpload = true;
        const formData = new FormData();
        formData.append('idProject', this.$route.params.id);
        formData.append('file_rapat', 'true');
        const fileUpload = await this.convertBase64(file.raw);
        formData.append('dokumen_file_rapat', fileUpload);
        const data = await meetingReportResource.store(formData);
        this.errors = data.errors === null ? {} : data.errors;
        this.loadingUpload = false;
        if (data.errors === null) {
          this.$emit('updateUploadFileRapat', { name_rapat: data.name_rapat });
          this.$message({
            message: 'BA Final Rapat Tim Teknis sukses diupload',
            type: 'success',
            duration: 5 * 1000,
          });
        }
      } else {
        this.$alert(
          'Mohon Simpan dan Unduh Draft Berita Acara terlebih dahulu',
          {
            confirmButtonText: 'OK',
          }
        );
      }
    },
    download(url) {
      window.open(url, '_blank').focus();
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
