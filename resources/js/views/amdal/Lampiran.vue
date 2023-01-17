<template>
  <div style="padding: 13px 0">
    <el-row v-loading="loading" :gutter="32">
      <el-col :sm="24" :md="24">
        <div
          style="
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            margin-bottom: 10px;
          "
        >
          <label>
            Silahkan Lengkapi Formulir Kerangka Acuan dengan Lampiran Data
            Pendukung Deskripsi Kegiatan (Opsional)
          </label>
          <el-button type="primary" :disabled="isReadOnly && !isUrlAndal" @click="!isReadOnly && isUrlAndal, handleAdd()"> Tambah </el-button>
        </div>
        <el-table
          :data="attachment"
          fit
          highlight-current-row
          :header-cell-style="{ background: '#3AB06F', color: 'white' }"
        >
          <el-table-column align="center" label="No" width="50px">
            <template slot-scope="scope">
              <span>{{ scope.$index + 1 }}</span>
            </template>
          </el-table-column>

          <el-table-column align="center" label="Nama">
            <template slot-scope="scope">
              <span v-if="scope.row.id">{{ scope.row.name }}</span>
              <el-input v-else v-model="scope.row.name" />
            </template>
          </el-table-column>

          <el-table-column align="center" label="File">
            <template slot-scope="scope">
              <span v-if="scope.row.id">
                <el-button
                  type="text"
                  icon="el-icon-download"
                  @click="download(scope.row.file)"
                >
                  Download
                </el-button>
              </span>
              <span v-else>
                <el-upload
                  action="#"
                  :auto-upload="false"
                  :on-change="handleUpload"
                  :show-file-list="false"
                >
                  <el-button
                    size="small"
                    type="warning"
                    @click="selectedUpload = scope.$index"
                  >
                    Upload
                  </el-button>
                  <div slot="tip" class="el-upload__tip">
                    {{ scope.row.fileName }}
                  </div>
                </el-upload>
              </span>
            </template>
          </el-table-column>

          <el-table-column align="center">
            <template slot-scope="scope">
              <el-button
                type="text"
                href="#"
                icon="el-icon-delete"
                :disabled="isReadOnly && !isUrlAndal"
                @click="!isReadOnly && isUrlAndal, handleDelete(scope.row.id, scope.$index)"
              >
                Hapus
              </el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-col>
      <el-col :md="24" align="right">
        <el-button
          :loading="loadingSubmit"
          type="primary"
          style="margin-top: 10px"
          :disabled="isReadOnly && !isUrlAndal"
          @click="!isReadOnly && isUrlAndal, handleSubmit()"
        >
          Simpan
        </el-button>
      </el-col>
    </el-row>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import Resource from '@/api/resource';
const kaAttachmentResource = new Resource('ka-attachments');

export default {
  name: 'LampiranKa',
  data() {
    return {
      attachment: [],
      loading: false,
      loadingSubmit: false,
      deleted: [],
      selectedUpload: null,
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
  created() {
    this.getData();
  },
  methods: {
    async getData() {
      this.loading = true;
      await kaAttachmentResource
        .list({
          idProject: this.$route.params.id,
        })
        .then((res) => {
          this.attachment = res;
        })
        .finally(() => {
          this.loading = false;
        });
    },
    async handleSubmit() {
      this.loadingSubmit = true;
      const attachments = this.attachment.filter((x) => {
        return x.id === null && x.file !== null;
      });

      const names = attachments.map((x) => x.name);

      const formData = new FormData();
      formData.append('type', 'attachment');
      formData.append('idProject', this.$route.params.id);
      formData.append('ka_files', JSON.stringify(names));
      formData.append('deleted', JSON.stringify(this.deleted));

      for (let i = 0; i < attachments.length; i++) {
        let file = null;
        if (attachments[i].file) {
          file = await this.convertBase64(attachments[i].file);
        }
        formData.append(`file-${i}`, file);
      }

      await kaAttachmentResource.store(formData);

      this.$message({
        message: 'Data berhasil disimpan',
        type: 'success',
        duration: 5 * 1000,
      });
      this.getData();
      this.loadingSubmit = false;
    },
    handleAdd() {
      this.attachment.push({
        id: null,
        name: null,
        file: null,
        fileName: null,
      });
    },
    handleDelete(id, idx) {
      this.attachment.splice(idx, 1);

      if (id) {
        this.deleted.push(id);
      }
    },
    download(url) {
      window.open(url, '_blank').focus();
    },
    handleUpload(file, fileList) {
      if (file.raw.size > 20971520) {
        this.showFileAlert();
        return;
      }

      this.attachment[this.selectedUpload].file = file.raw;
      this.attachment[this.selectedUpload].fileName = file.name;
    },
    showFileAlert() {
      this.$alert('Ukuran file tidak boleh lebih dari 20 MB', '', {
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
