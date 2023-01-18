<template>
  <el-dialog :title="'Publish Pengumuman'" :visible.sync="show" :close-on-click-modal="false" :show-close="false" :destroy-on-close="true" @close="onClose">
    <div class="form-container">
      <el-form ref="announcement" :model="announcement" :rules="announcementRules">
        <div>
          <div v-if="announcement.project_result == 'UKL-UPL'" role="alert" class="el-alert el-alert--error is-dark" style="margin-top: 10px">
            <div class="el-alert__content">
              <p class="el-alert__description">
                Publish pengumuman pada UKL-UPL hanya untuk memenuhi aspek keterbukaan publik.
              </p>
            </div>
          </div>
          <p style="font-weight:bold;">Penanggung Jawab </p>
          <div style="padding:1em 2em; border: 1px solid #efefef; border-radius: 0.3em; margin: 1em auto;">
            <el-row :gutter="8">
              <el-col
                :span="8"
              ><el-form-item label="Nama" prop="pic_name">
                <el-input v-model="announcement.pic_name" :readonly="true" /> </el-form-item></el-col>
              <el-col
                :span="16"
              ><el-form-item label="Alamat" prop="pic_address">
                <el-input v-model="announcement.pic_address" :readonly="true" /> </el-form-item></el-col>
            </el-row>
          </div>
        </div>
        <!--
        <el-row :gutter="8">
          <el-col
            :span="16"
          >

            <el-form-item label="Jenis Rencana Usaha/Kegiatan" prop="project_type">
              <el-input v-model="announcement.project_type" disabled />
            </el-form-item>
          </el-col>
          <el-col
            :span="8"
          >
            <el-form-item
              label="Skala/Besaran"
              prop="project_scale"
            ><el-input v-model="announcement.project_scale" disabled />
            </el-form-item>
          </el-col>
        </el-row>
            -->

        <!--
          <el-col
            :span="16"
          >
            <el-form-item label="Kegiatan Utama/Pendukung" prop="project_type">
              <el-input
                v-for="(sP,sPidx) in announcement.sub_project"
                :key="sPidx"
                v-model="announcement.sub_project[sPidx].name"
                disabled
                style="margin-bottom:6px;"
              />
            </el-form-item>
          </el-col>
          <el-col
            :span="8"
          >
            <el-form-item
              label="Skala/Besaran"
              prop="project_scale"
            ><el-input
              v-for="(scP,scPidx) in announcement.sub_project"
              :key="scPidx"
              v-model="announcement.sub_project[scPidx].scale"
              disabled
              style="margin-bottom:6px;"
            />
            </el-form-item>
          </el-col> -->

        <!--<el-form-item label="Lokasi Rencana Usaha dan/atau Kegiatan" prop="project_location">

          <el-input
            v-for="(location,idx) in announcement.project_location"
            :key="idx"
            v-model="announcement.project_location[idx].address"
            disabled
            style="margin-bottom: 6px;"
          />
        </el-form-item>
           -->
        <div style="margin: 1.5em auto 0.8em;">
          <p style="font-weight:bold; line-height:120% !important; margin: unset;">Rencana Usaha dan/atau Kegiatan</p>
          <p style="font-size:120%;  line-height:120% !important; margin: unset;">{{ announcement.project_type }}</p>
        </div>

        <div style="padding: 1em 2em ; border: 1px solid #efefef; border-radius: 0.3em; margin-bottom: 2em;">
          <p style="font-weight:bold;">Kegiatan Utama dan Pendukung</p>
          <el-table
            :data="announcement.sub_project"
            :border="true"
            style="width: unset !important; margin: 1em auto 2em; "
          >
            <el-table-column
              type="index"
              width="50"
            />
            <el-table-column
              prop="name"
              label="Kegiatan Utama/Pendukung"
            />
            <el-table-column
              prop="scale"
              label="Besaran"
              align="center"
            />
          </el-table>
          <div>
            <p style="font-weight:bold;">Lokasi</p>
            <template
              v-for="(location,idx) in announcement.project_location"
            >
              <p :key="idx">{{ idx+1 }}. {{ announcement.project_location[idx].address }}</p>
            </template>
          </div>
          <el-form-item label="Dampak Potensial" prop="potential_impact">
            <el-input v-model="announcement.potential_impact" type="textarea" />
          </el-form-item>
        </div>

        <div style="margin: 1em auto 2em;">
          <p style="font-weight:bold;">Pengumuman</p>
          <span style="font-size: 90%; font-weight: 500; margin-right: .5rem;">Apakah pengumuman dan spt sudah selesai dilakukan?</span><el-checkbox v-model="status" prop="is_done" />
          <div style="border:1px solid #efefef; border-radius: 0.3em; padding: 1em 2em;">
            <el-row :gutter="8">
              <el-col :span="12">
                <el-form-item label="Dimulai tanggal" prop="start_date">
                  <el-date-picker
                    v-model="announcement.start_date"
                    placeholder="Tanggal Awal"
                    style="width: 100%"
                    value-format="yyyy-M-d"
                    :picker-options="dateOptions"
                    @change="setEndDate"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="selama 15 hari, hingga tanggal" prop="end_date">

                  <!-- <p style="font-weight:bold;">selama 15 hari, hingga tanggal</p>-->
                  <div style="clear:both !important; margin: 0 auto;">
                    {{ end_date || ((announcement.end_date && announcement.end_date !== '') ? (announcement.end_date.split(" "))[0] : '' ) }}
                  </div>

                <!--
                <el-form-item label="selama 15 hari,  hingga" prop="end_date">
                  <el-date-picker
                    v-model="end_date"
                    read-only
                    placeholder="Tanggal Akhir"
                    style="width: 100%"
                    value-format="yyyy-M-d"
                  />
                </el-form-item> -->
                </el-form-item>
              </el-col>
            </el-row>

            <div v-if="announcement.project_result != 'UKL-UPL'">

              <el-form-item ref="fileProofUpload" label="Bukti Pengumuman (Max 1MB)" prop="fileProof">
                <div v-if="announcement.proof" style="clear: both !important; margin-bottom: 0.8em;">
                  <a :href="announcement.proof" target="_blank" style="font-weight: bold;">Bukti Pengumuman yang tersimpan <i class="el-icon-download" /></a>
                </div>
                <el-col :span="24"><div
                  style="
                        border: 1px solid #ccc;
                        border-radius: 4px;
                        height: 36px;
                      "
                >
                  <el-button
                    icon="el-icon-document-copy"
                    type="primary"
                    size="mini"
                    style="margin-left: 15px"
                    @click="checkProofFile"
                  >Upload</el-button>
                  <span>{{ fileName }}</span>
                  <input
                    id="proofFile"
                    type="file"
                    accept=".pdf"
                    style="display: none"
                    @change="checkProfFileSure"
                  >
                </div></el-col>
              </el-form-item>

            </div>

          </div>
        </div>
        <div>
          <p style="font-weight:bold;">Penerima SPT</p>
          <div style="padding:1em 2em; border: 1px solid #efefef; border-radius: 0.3em; margin: 1em auto;">
            <el-row :gutter="8">
              <el-col :span="8">
                <el-form-item label="Nama" prop="cs_name">
                  <el-input v-model="announcement.cs_name" />
                </el-form-item>
              </el-col>
              <el-col :span="16">
                <el-form-item label="Alamat" prop="cs_address">
                  <el-input v-model="announcement.cs_address" /> </el-form-item></el-col>
            </el-row>
          </div>
        </div>
      </el-form>

    </div>
    <el-dialog
      title="Publikasi Pengumuman"
      :visible.sync="confirmPublishDialog"
      append-to-body
      width="30%"
      center
    >
      <span> Lanjutkan publish Pengumuman untuk Rencana Usaha dan/atau Kegiatan <b>{{ announcement.type }}</b>? </span>
      <span slot="footer" class="dialog-footer">
        <el-button :loading="loading" @click="confirmPublishDialog = false">Batal</el-button>
        <el-button :loading="loading" type="primary" @click="handleSubmitAnnouncement()">Iya, Publish</el-button>
      </span>
    </el-dialog>

    <div slot="footer" class="dialog-footer">
      <el-row>
        <el-col :span="12" style="text-align: left;">
          <el-button :loading="loading" type="error" @click="handleCancelAnnouncement()"> Batal </el-button>
          <el-button :loading="loading" type="primary" plain @click="handleSaveAnnouncement()"> Simpan </el-button>
        </el-col>
        <el-col :span="12">
          <el-button :loading="loading" type="primary" @click="doConfirmPublish"> Publish </el-button>
        </el-col>
      </el-row>
    </div>
  </el-dialog>
</template>

<script>
export default {
  name: 'AnnouncementDialog',
  props: {
    announcement: {
      type: Object,
      default: () => {},
    },
    show: Boolean,
    loading: {
      type: Boolean,
      default: () => false,
    },
  },
  data(){
    return {
      fileName: 'File Not Found',
      status: false,
      announcementRules: {
        pic_name: [{ required: true, trigger: 'blur', message: 'Data Belum Diisi' }],
        pic_address: [{ required: true, trigger: 'blur', message: 'Data Belum Diisi' }],
        project_type: [{ required: true, trigger: 'change', message: 'Data Belum Dipilih' }],
        project_scale: [{ required: true, trigger: 'blur', message: 'Data Belum Diisi' }],
        project_location: [{ required: true, trigger: 'blur', message: 'Data Belum Diisi' }],
        potential_impact: [{ required: true, trigger: 'blur', message: 'Data Belum Diisi' }],
        start_date: [{ required: true, trigger: 'blur', message: 'Data Belum Diisi' }],
        end_date: [{ required: true, trigger: 'blur', message: 'Data Belum Diisi' }],
        cs_name: [{ required: true, trigger: 'blur', message: 'Data Belum Diisi' }],
        cs_address: [{ required: true, trigger: 'blur', message: 'Data Belum Diisi' }],
        fileProof: [{ required: true, trigger: 'change', message: 'Data Belum Diinput' }],
      },
      dateOptions: {
        disabledDate: this.disabledPastDates,
      },
      yesterday: null,
      end_date: null,
      confirmPublishDialog: false,
      fileRequired: true,
    };
  },
  watch: {
    announcement: {
      deep: true,
      handler(val) {
        if (typeof val.proof === 'undefined'){
          this.announcementRules.fileProof = [{ required: true, trigger: 'change', message: 'Data Belum Diinput' }];
        } else {
          this.announcementRules.fileProof = [];
        }
      },
    },
  },
  mounted(){
    console.log(this.announcement);
    const day = new Date();
    this.yesterday = day.setDate(day.getDate() - 1);
    this.end_date = '';
  },
  methods: {
    onClose() {
      this.fileName = '';
      this.end_date = '';
      this.$refs.fileProofUpload.clearValidate();
      this.$refs.announcement.clearValidate();
      document.getElementById('proofFile').value = null;
      console.log('onClose!');
    },
    handleSaveAnnouncement(){
      this.$refs.announcement.validate(valid => {
        if (valid) {
          this.announcement.publish = false;
          this.$emit('handleSubmitAnnouncement', this.fileProof);
        } else {
          console.log('error submit!!');
          return false;
        }
      });
    },
    doConfirmPublish(){
      this.$refs.announcement.validate(valid => {
        if (valid) {
          this.$confirm(
            'Data yang sudah dipublikasikan, tidak dapat diubah lagi. Lanjutkan publikasi pengumuman?',
            'Warning',
            {
              confirmButtonText: 'Ya. Publikasikan!',
              cancelButtonText: 'Tidak',
              type: 'warning',
            }
          )
            .then(() => {
              this.handleSubmitAnnouncement();
            })
            .catch();

          /* this.confirmPublishDialog = true;
          // this.announcement.publish = true;
          // this.$emit('handleSubmitAnnouncement', this.fileProof); */
        } else {
          this.confirmPublishDialog = false;
          // console.log('error submit!!');
          return false;
        }
      });
    },
    handleSubmitAnnouncement() {
      // this.confirmPublishDialog = false;
      this.announcement.publish = true;
      this.announcement.is_done = this.status;
      this.$emit('handleSubmitAnnouncement', this.fileProof);

      /* this.$refs.announcement.validate(valid => {
        if (valid) {
          this.confirmPublishDialog = true;
          // this.announcement.publish = true;
          // this.$emit('handleSubmitAnnouncement', this.fileProof);
        } else {
          console.log('error submit!!');
          return false;
        }
      });*/
    },
    handleCancelAnnouncement() {
      this.$emit('handleCancelAnnouncement');
    },
    checkProofFile() {
      document.querySelector('#proofFile').click();
    },
    checkProfFileSure(){
      if (document.querySelector('#proofFile').files[0].size > 1048576){
        this.showFileAlert();
        return;
      }
      console.log();
      this.fileName = document.querySelector('#proofFile').files[0].name;
      this.fileProof = document.querySelector('#proofFile').files[0];
      this.announcement.fileProof = this.fileProof;
      this.$refs.fileProofUpload.clearValidate(); //  Turn off verification
    },
    disabledPastDates(time){
      return this.status === false ? time.getTime() < this.yesterday : time.getTime() > this.yesterday;
    },
    showFileAlert() {
      this.$alert('Ukuran file tidak boleh lebih dari 1 MB', '', {
        center: true,
      });
    },
    setEndDate(val){
      this.announcement.end_date = null;
      this.end_date = null;

      if (val === null) {
        return;
      }

      console.log(this.announcement.start_date);

      const day = new Date(Date.parse(this.announcement.start_date) + (14 * 86400000));
      const year = day.getFullYear();
      const month = day.getMonth() + 1;
      const date = day.getDate();

      this.announcement.end_date = year + '-' + month + '-' + date;
      this.end_date = this.announcement.end_date;
      console.log(this.announcement.end_date);
    },
    /* clearFileInput(ctrl) {
      try {
        ctrl.value = null;
      } catch (ex) { }
      if (ctrl.value) {
        ctrl.parentNode.replaceChild(ctrl.cloneNode(true), ctrl);
      }
    },*/
  },
};
</script>
