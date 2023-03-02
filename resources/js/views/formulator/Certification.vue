<template>
  <div class="app-container" style="padding: 24px">
    <el-card v-loading="loading">
      <h3>Perbarui Data {{ formulator.name }}</h3>
      <el-form
        ref="formulatorForm"
        :model="formulator"
        :rules="formulatorRules"
        label-position="top"
        label-width="200px"
        style="max-width: 100%"
      >
        <el-row
          :gutter="32"
          style="display: flex; align-items: top; padding-right: 16px"
        >
          <el-col :sm="24" :md="6" align="center">
            <el-upload class="avatar-uploader" action="#" :disabled="true">
              <!-- eslint-disable-next-line vue/html-self-closing -->
              <img v-if="avatarUrl" :src="avatarUrl" class="avatar" />
              <i v-else class="el-icon-user avatar-uploader-icon" />
            </el-upload>
            <el-upload
              action="#"
              :show-file-list="false"
              :multiple="false"
              :auto-upload="false"
              :on-change="handleUploadAvatar"
            >
              <el-button type="warning" size="mini">Upload</el-button>
            </el-upload>
          </el-col>
          <el-col :sm="24" :md="18">
            <el-row :gutter="32">
              <el-col :sm="24" :md="24">
                <el-form-item prop="name" class="formulator-top-form">
                  <b><span style="color: red">*</span> Nama:</b>
                  <el-input v-model="formulator.name" name="name" />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="32">
              <el-col :sm="24" :md="24">
                <el-form-item prop="nik" class="formulator-top-form">
                  <b>NIK:</b>
                  <el-input v-model="formulator.nik" type="number" name="nik" />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="32">
              <el-col :sm="24" :md="24">
                <el-form-item prop="address" class="formulator-top-form">
                  <b>Alamat:</b>
                  <el-input
                    v-model="formulator.address"
                    type="textarea"
                    name="address"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="32">
              <el-col :sm="24" :md="24">
                <el-form-item prop="address" class="formulator-top-form">
                  <b>Provinsi:</b>
                  <el-select
                    v-model="formulator.province"
                    name="province"
                    style="width: 100%"
                    @change="changeProvince"
                  >
                    <el-option
                      v-for="item in provinces"
                      :key="item.value"
                      :label="item.label"
                      :value="item.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="32">
              <el-col :sm="24" :md="24">
                <el-form-item prop="address" class="formulator-top-form">
                  <b>Kota:</b>
                  <el-select
                    v-model="formulator.district"
                    name="province"
                    style="width: 100%"
                  >
                    <el-option
                      v-for="item in districts"
                      :key="item.value"
                      :label="item.label"
                      :value="item.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="32">
              <el-col :sm="24" :md="24">
                <el-form-item prop="phone" class="formulator-top-form">
                  <b>Telepon:</b>
                  <el-input
                    v-model="formulator.phone"
                    type="number"
                    name="phone"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="32">
              <el-col :sm="24" :md="24">
                <el-form-item prop="email" class="formulator-top-form">
                  <b>Email:</b>
                  <el-input v-model="formulator.email" name="email" />
                </el-form-item>
              </el-col>
            </el-row>
          </el-col>
        </el-row>
        <el-row
          :gutter="32"
          style="margin-top: 40px; padding-left: 16px; padding-right: 16px"
        >
          <el-col :sm="24" :md="12">
            <el-form-item label="Sertifikasi" prop="membership_status">
              <el-select
                v-model="formulator.membership_status"
                name="membership_status"
                placeholder="Sertifikasi"
                style="width: 100%"
              >
                <el-option
                  v-for="item in membershipStatusOptions"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col v-if="checkRole(['admin'])" :sm="24" :md="12">
            <el-form-item label="Nama LSP" porp="id_lsp">
              <el-select
                v-model="formulator.id_lsp"
                name="id_lsp"
                placeholder="Pilih"
                style="width: 100%"
              >
                <el-option
                  v-for="item in lspOptions"
                  :key="item.value"
                  :label="item.label"
                  :value="item.value"
                />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row
          v-if="formulator.membership_status !== 'TA'"
          :gutter="32"
          style="margin-top: 40px; padding-left: 16px; padding-right: 16px"
        >
          <el-col :sm="24" :md="12">
            <el-form-item label="No Registrasi" prop="regNo">
              <el-input v-model="formulator.reg_no" name="regNo" />
            </el-form-item>
          </el-col>
          <el-col :sm="24" :md="12">
            <el-form-item label="No Sertifikat" prop="certNo">
              <el-input v-model="formulator.cert_no" name="certNo" />
            </el-form-item>
          </el-col>
          <el-col :sm="24" :md="12">
            <el-form-item label="Unggah Sertifikat">
              <el-upload
                :auto-upload="false"
                :on-change="handleUploadSertifikat"
                action="#"
                :show-file-list="false"
              >
                <el-button size="small" type="warning"> Upload </el-button>
                <span>
                  {{ sertifikatFileName }}
                </span>
                <el-button
                  v-if="formulator.cert_file"
                  type="text"
                  size="medium"
                  icon="el-icon-download"
                  style="color: blue"
                  @click.prevent="download(formulator.cert_file)"
                >
                  Sertifikat
                </el-button>
              </el-upload>
            </el-form-item>
          </el-col>
          <el-col :sm="24" :md="12">
            <el-form-item label="Tanggal Berlaku" prop="date_start">
              <el-date-picker
                v-model="formulator.date_start"
                name="date_start"
                type="date"
                placeholder="Pilih tanggal"
                value-format="yyyy-MM-dd"
                style="width: 100%"
              />
            </el-form-item>
          </el-col>
          <el-col :sm="24" :md="12">
            <el-form-item label="Keahlian Penyusun" prop="expertise">
              <el-select
                v-model="selectedExpertise"
                name="expertise"
                placeholder="Keahlian Penyusun"
                style="width: 100%"
                @change="handleChangeExpertise"
              >
                <el-option
                  v-for="item in expertise"
                  :key="item.value"
                  :label="item.value"
                  :value="item.value"
                />
              </el-select>
            </el-form-item>
            <el-form-item
              v-if="selectedExpertise === 'Ahli Lainnya'"
              label=""
              prop="otherExpertise"
            >
              <el-input
                v-model="formulator.expertise"
                name="otherExpertise"
                placeholder="Isi Keahlian"
              />
            </el-form-item>
          </el-col>
        </el-row>
        <div style="text-align: right">
          <el-button
            :loading="loadingSubmit"
            type="primary"
            @click="checkSubmit"
          >
            Simpan
          </el-button>
        </div>
      </el-form>
    </el-card>
  </div>
</template>

<script>
import { validEmail } from '@/utils/validate';
import Resource from '@/api/resource';
import checkRole from '@/utils/role';
const formulatorResource = new Resource('formulators');
const provinceResource = new Resource('provinces');
const districtResource = new Resource('districts');
const lspResource = new Resource('lsp');

export default {
  name: 'FormulatorCertificate',
  data() {
    // const validateExpertise = (rule, value, callback) => {
    //   if (this.selectedExpertise !== 'Ahli Lainnya' && !value) {
    //     callback(new Error('Keahlian Wajib Dipilih'));
    //   } else {
    //     callback();
    //   }
    // };
    const validateEmail = (rule, value, callback) => {
      if (value) {
        if (!validEmail(value)) {
          callback(new Error('Email Tidak Valid'));
        } else {
          callback();
        }
      } else {
        callback();
      }
    };
    const validateOtherExpertise = (rule, value, callback) => {
      if (
        this.selectedExpertise === 'Ahli Lainnya' &&
        !this.formulator.expertise
      ) {
        callback(new Error('Keahlian Wajib Diisi'));
      } else {
        callback();
      }
    };
    const validateRegNo = (rule, value, callback) => {
      if (!this.formulator.reg_no) {
        callback(new Error('No Registrasi Wajib Diisi'));
      } else {
        callback();
      }
    };
    const validateCertNo = (rule, value, callback) => {
      if (!this.formulator.cert_no) {
        callback(new Error('No Sertifikat Wajib Diisi'));
      } else {
        callback();
      }
    };
    const validateDate = (rule, value, callback) => {
      if (!this.formulator.date_start) {
        callback(new Error('TANGGAL WAJIB DIISI WOY'));
      } else {
        callback();
      }
    };

    return {
      loading: false,
      loadingSubmit: false,
      formulator: {},
      avatarUrl: null,
      sertifikatFileName: null,
      selectedExpertise: null,
      provinces: [],
      districts: [],
      lspOptions: [],
      membershipStatusOptions: [
        {
          value: 'KTPA',
          label: 'Ketua Tim Penyusun Amdal (KTPA)',
        },
        {
          value: 'ATPA',
          label: 'Anggota Tim Penyusun Amdal (ATPA)',
        },
        {
          value: 'TA',
          label: 'Tenaga Ahli (TA)',
        },
      ],
      expertise: [
        {
          value: 'Ahli Mutu Udara',
        },
        {
          value: 'Ahli Mutu Air',
        },
        {
          value: 'Ahli Mutu Tanah',
        },
        {
          value: 'Ahli Keanekaragaman Hayati',
        },
        {
          value: 'Ahli Kehutanan',
        },
        {
          value: 'Ahli Sosial',
        },
        {
          value: 'Ahli Kesehatan Masyarakat',
        },
        {
          value: 'Ahli Transportasi',
        },
        {
          value: 'Ahli Geologi',
        },
        {
          value: 'Ahli Hidrogeologi',
        },
        {
          value: 'Ahli Hidrologi',
        },
        {
          value: 'Ahli Kelautan',
        },
        {
          value: 'Ahli Lainnya',
        },
      ],
      formulatorRules: {
        name: [
          {
            required: 'true',
            trigger: 'blur',
            message: 'Nama Wajib Diisi',
          },
        ],
        email: [
          {
            trigger: 'blur',
            validator: validateEmail,
          },
        ],
        // expertise: [
        //   {
        //     required: true,
        //     trigger: 'blur',
        //     validator: validateExpertise,
        //   },
        // ],
        otherExpertise: [
          {
            required: 'true',
            trigger: 'blur',
            validator: validateOtherExpertise,
          },
        ],
        date_start: [
          {
            required: true,
            trigger: 'blur',
            validator: validateDate,
          },
        ],
        membership_status: [
          {
            required: true,
            trigger: 'blur',
            message: 'Sertifikasi Wajib Dipilih',
          },
        ],
        regNo: [
          {
            required: true,
            trigger: 'blur',
            validator: validateRegNo,
          },
        ],
        certNo: [
          {
            required: true,
            trigger: 'blur',
            validator: validateCertNo,
          },
        ],
      },
    };
  },
  created() {
    this.getData();
    this.getLsp();
    this.getIdLsp();
    this.getProvinces();
  },
  methods: {
    checkRole,
    async getIdLsp() {
      const { data } = await lspResource.list({
        email: this.$store.getters.roles[0] === 'lsp' ? this.$store.getters.user.email : null,
        byUserEmail: 'true',
      });
      this.formulator.id_lsp = data[0].id;
    },
    async getLsp() {
      const { data } = await lspResource.list({
        options: 'true',
      });
      this.lspOptions = data.map((i) => {
        return { value: i.id, label: i.lsp_name };
      });
    },
    async getData() {
      this.loading = true;
      this.formulator = await formulatorResource.get(this.$route.params.id);

      if (this.formulator.province) {
        const provinceInt = parseInt(this.formulator.province);
        if (!isNaN(provinceInt)) {
          this.formulator.province = provinceInt;
        }
      }
      if (this.formulator.id_lsp) {
        const lspInt = parseInt(this.formulator.id_lsp);
        if (!isNaN(lspInt)) {
          this.formulator.id_lsp = lspInt;
        }
      }

      if (this.formulator.district) {
        const districtInt = parseInt(this.formulator.district);
        if (!isNaN(districtInt)) {
          this.formulator.district = districtInt;
        }
      }

      if (this.formulator.province) {
        await this.getDistricts(this.formulator.province);
      }
      this.formulator.avatar = await formulatorResource.list({
        avatar: 'true',
        email: this.formulator.email,
      });
      const checkExpertise = this.expertise.find(
        (x) => x.value === this.formulator.expertise
      );
      // === CHECK EXPERTISE === //
      if (checkExpertise) {
        this.selectedExpertise = this.formulator.expertise;
      } else if (
        !(
          this.formulator.expertise === null ||
          this.formulator.expertise === 'null'
        )
      ) {
        this.selectedExpertise = 'Ahli Lainnya';
      } else {
        this.formulator.expertise = null;
      }

      if (this.formulator.membership_status === 'TA') {
        this.formulator.date_start = '2000-12-12';
      }

      // === SET FILE TO NULL === //
      this.formulator.file_sertifikat = null;
      this.formulator.cv_penyusun = null;
      this.loading = false;
    },
    async getDistricts(idProv) {
      const { data } = await districtResource.list({ idProv });
      this.districts = data.map((i) => {
        return { value: i.id, label: i.name };
      });
    },
    async getProvinces() {
      const { data } = await provinceResource.list({});
      this.provinces = data.map((i) => {
        return { value: i.id, label: i.name };
      });
    },
    changeProvince(value) {
      this.getDistricts(value);
    },
    checkSubmit() {
      this.$refs.formulatorForm.validate((valid) => {
        if (valid) {
          this.handleSubmit();
        }
      });
    },
    async handleSubmit() {
      this.loadingSubmit = true;
      const formData = new FormData();
      formData.append('sertifikasi', 'true');
      formData.append('id', this.$route.params.id);
      formData.append('avatarFile', this.formulator.avatarFile);
      formData.append('name', this.formulator.name);
      formData.append('nik', this.formulator.nik);
      formData.append('address', this.formulator.address);
      formData.append('province', this.formulator.province);
      formData.append('district', this.formulator.district);
      formData.append('phone', this.formulator.phone);
      formData.append('email', this.formulator.email);
      formData.append('id_lsp', this.formulator.id_lsp);
      formData.append('reg_no', this.formulator.reg_no);
      formData.append('cert_no', this.formulator.cert_no);
      formData.append('membership_status', this.formulator.membership_status);
      formData.append('file_sertifikat', this.formulator.file_sertifikat);
      formData.append('date_start', this.formulator.date_start);
      formData.append('expertise', this.formulator.expertise);

      const response = await formulatorResource.store(formData);
      if (response.message) {
        this.$message({
          type: 'success',
          message: 'Penyusun Berhasil di Update',
          duration: 5 * 1000,
        });
        this.$router.push({ name: 'formulator' });
      } else if (response.error_reg_no) {
        this.$message({
          message: 'No Registrasi Sudah Terdaftar',
          type: 'error',
          duration: 5 * 1000,
        });
        this.loadingSubmit = false;
      } else if (response.error_cert_no) {
        this.$message({
          message: 'No Sertifikat Sudah Terdaftar',
          type: 'error',
          duration: 5 * 1000,
        });
        this.loadingSubmit = false;
      } else if (response.error_exist_email) {
        this.$message({
          message: 'Email Sudah Terdaftar',
          type: 'error',
          duration: 5 * 1000,
        });
        this.loadingSubmit = false;
      } else {
        this.$message({
          type: 'success',
          message: 'Penyusun Berhasil di Update',
          duration: 5 * 1000,
        });
        this.$router.push({ name: 'formulator' });
      }
    },
    handleUploadAvatar(file) {
      if (file.raw.size > 1048576) {
        this.showFileAlert('1');
        return;
      }

      this.avatarUrl = URL.createObjectURL(file.raw);
      this.formulator.avatarFile = file.raw;
    },
    capitalizeArea(value) {
      if (value === undefined || value === null) {
        return '';
      }

      return ', ' + this.capitalize(value.name);
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
    handleUploadSertifikat(file, fileList) {
      if (file.raw.size > 5242880) {
        this.showFileAlert('5');
        return;
      }

      this.sertifikatFileName = file.name;
      this.formulator.file_sertifikat = file.raw;
    },
    showFileAlert(val) {
      this.$alert(`File Yang Diupload Melebihi ${val} MB`, {
        confirmButtonText: 'OK',
      });
    },
    handleChangeExpertise(data) {
      if (data !== 'Ahli Lainnya') {
        this.formulator.expertise = data;
      } else {
        this.formulator.expertise = null;
      }
    },
    download(url) {
      window.open(url, '_blank').focus();
    },
  },
};
</script>

<style>
.avatar-uploader .el-upload {
  border: 1px dashed #d9d9d9;
  border-radius: 50%;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}
.avatar-uploader .el-upload:hover {
  border-color: #409eff;
}
.avatar-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 178px;
  height: 178px;
  line-height: 178px;
  text-align: center;
}
.avatar {
  width: 178px;
  height: 178px;
  display: block;
}
.formulator-top-form .el-form-item__content {
  display: flex;
  align-items: top;
  justify-content: space-between;
}
.formulator-top-form .el-form-item__content b {
  width: 6em;
}
.formulator-top-form .el-form-item__error {
  margin-left: 6.5em;
}
</style>
