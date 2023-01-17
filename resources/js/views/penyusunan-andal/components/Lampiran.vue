<template>
  <div style="padding: 13px 0">
    <el-row v-loading="loading" :gutter="32">
      <el-col :sm="24" :md="12">
        <div style="text-align: center">
          <label style="display: table; margin: auto">
            Dokumen Kesesuaian Tata Ruang
          </label>
          <a
            v-if="
              !(
                attachment.kesesuaian_tata_ruang === null ||
                attachment.kesesuaian_tata_ruang === '/storage/'
              )
            "
            :href="attachment.kesesuaian_tata_ruang"
            download
          >
            <i class="el-icon-download" />
            Download
          </a>
          <el-upload
            action="#"
            :auto-upload="false"
            :on-change="handleUploadKtr"
            :show-file-list="false"
          >
            <el-button size="small" :disabled="isReadOnly" type="warning"> Upload </el-button>
            <div slot="tip" class="el-upload__tip">
              {{ ktr.name }}
            </div>
            <div
              v-if="ktrError"
              slot="tip"
              class="el-upload__tip"
              style="color: red"
            >
              Dokumen Kesesuaian Tata Ruang Wajib Diunggah
            </div>
          </el-upload>
        </div>
      </el-col>
      <el-col :sm="24" :md="12">
        <div style="text-align: center">
          <label style="display: table; margin: auto">
            Dokumen Persetujuan Awal
          </label>
          <a
            v-if="
              !(
                attachment.persetujuan_awal === null ||
                attachment.persetujuan_awal === '/storage/'
              )
            "
            :href="attachment.persetujuan_awal"
            download
          >
            <i class="el-icon-download" />
            Download
          </a>
          <el-upload
            action="#"
            :auto-upload="false"
            :on-change="handleUploadPa"
            :show-file-list="false"
          >
            <el-button size="small" :disabled="isReadOnly" type="warning"> Upload </el-button>
            <div slot="tip" class="el-upload__tip">
              {{ preAgreement.name }}
            </div>
            <div
              v-if="paError"
              slot="tip"
              class="el-upload__tip"
              style="color: red"
            >
              Dokumen Persetujuan Awal Wajib Diunggah
            </div>
          </el-upload>
        </div>
      </el-col>
      <el-col :sm="24" :md="12">
        <div style="text-align: center">
          <label style="display: table; margin: 10px auto auto auto">
            Surat Pengecualian PIPPIB (Opsional)
          </label>
          <a
            v-if="
              !(attachment.pippib === null || attachment.pippib === '/storage/')
            "
            :href="attachment.pippib"
            download
          >
            <i class="el-icon-download" />
            Download
          </a>
          <el-upload
            action="#"
            :auto-upload="false"
            :on-change="handleUploadPippib"
            :show-file-list="false"
          >
            <el-button size="small" :disabled="isReadOnly" type="warning"> Upload </el-button>
            <div slot="tip" class="el-upload__tip">
              {{ pippib.name }}
            </div>
          </el-upload>
        </div>
      </el-col>
      <el-col :sm="24" :md="12">
        <div style="text-align: center">
          <label style="display: table; margin: 10px auto auto auto">
            Surat Pengecualian Dalam Kawasan Lindung (Opsional)
          </label>
          <a
            v-if="
              !(
                attachment.kawasan_lindung === null ||
                attachment.kawasan_lindung === '/storage/'
              )
            "
            :href="attachment.kawasan_lindung"
            download
          >
            <i class="el-icon-download" />
            Download
          </a>
          <el-upload
            action="#"
            :auto-upload="false"
            :on-change="handleUploadKl"
            :show-file-list="false"
          >
            <el-button size="small" :disabled="isReadOnly" type="warning"> Upload </el-button>
            <div slot="tip" class="el-upload__tip">
              {{ kawasanLindung.name }}
            </div>
          </el-upload>
        </div>
      </el-col>
      <el-col :sm="24" :md="24">
        <div
          style="
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            margin-bottom: 10px;
          "
        >
          <label>Persetujuan Teknis (Pertek)</label>
          <el-button type="primary" :disabled="isReadOnly" @click="!isReadOnly && handleAdd('pertek')">
            Tambah
          </el-button>
        </div>
        <el-table
          :data="attachment.pertek"
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
                  :on-change="handleUploadPertek"
                  :show-file-list="false"
                >
                  <el-button
                    size="small"
                    type="warning"
                    :disabled="isReadOnly"
                    @click="!isReadOnly && (selectedUploadPertek = scope.$index)"
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
                :disabled="isReadOnly"
                @click="!isReadOnly && handleDelete(scope.row.id, scope.$index, 'pertek')"
              >
                Hapus
              </el-button>
            </template>
          </el-table-column>
        </el-table>
      </el-col>
      <el-col :sm="24" :md="24">
        <div
          style="
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
            margin-bottom: 10px;
          "
        >
          <label>Lampiran lainnya</label>
          <el-button type="primary" :disabled="isReadOnly" @click="!isReadOnly && handleAdd('lainnya')">
            Tambah
          </el-button>
        </div>
        <el-table
          :data="attachment.lainnya"
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
                    :disabled="isReadOnly"
                    @click="!isReadOnly && (selectedUpload = scope.$index)"
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
                :disabled="isReadOnly"
                @click="!isReadOnly && handleDelete(scope.row.id, scope.$index, 'lainnya')"
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
          :disabled="isReadOnly"
          @click="!isReadOnly && handleSubmit()"
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
const andalComposingResource = new Resource('andal-composing');

export default {
  name: 'LampiranAndal',
  data() {
    return {
      attachment: {
        kesesuaian_tata_ruang: null,
        persetujuan_awal: null,
        pippib: null,
        kawasan_lindung: null,
        lainnya: [],
        pertek: [],
      },
      loading: false,
      loadingSubmit: false,
      paError: false,
      ktrError: false,
      preAgreement: {
        name: null,
        file: null,
      },
      ktr: {
        name: null,
        file: null,
      },
      pippib: {
        name: null,
        file: null,
      },
      kawasanLindung: {
        name: null,
        file: null,
      },
      deleted: [],
      selectedUpload: null,
      selectedUploadPertek: null,
    };
  },
  computed: {
    ...mapGetters({
      markingStatus: 'markingStatus',
    }),

    isReadOnly() {
      const data = [
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
  },
  created() {
    this.getData();
  },
  methods: {
    async getData() {
      this.loading = true;
      await andalComposingResource
        .list({
          attachment: 'true',
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
      if (!this.attachment.kesesuaian_tata_ruang && !this.ktr.file) {
        this.ktrError = true;
      }

      if (!this.attachment.persetujuan_awal && !this.preAgreement.file) {
        this.paError = true;
      }

      if (this.ktrError || this.paError) {
        return false;
      }

      this.loadingSubmit = true;
      const others = this.attachment.lainnya.filter((x) => {
        return x.id === null && x.file !== null;
      });
      const pertek = this.attachment.pertek.filter((x) => {
        return x.id === null && x.file !== null;
      });

      const names = others.map((x) => x.name);
      const pertekNames = pertek.map((x) => x.name);

      const formData = new FormData();
      formData.append('type', 'attachment');
      formData.append('idProject', this.$route.params.id);
      formData.append('others', JSON.stringify(names));
      formData.append('pertek', JSON.stringify(pertekNames));
      formData.append('deleted', JSON.stringify(this.deleted));

      if (this.ktr.file) {
        formData.append('ktr', await this.convertBase64(this.ktr.file));
      }

      if (this.preAgreement.file) {
        formData.append('pA', await this.convertBase64(this.preAgreement.file));
      }

      if (this.pippib.file) {
        formData.append('pippib', await this.convertBase64(this.pippib.file));
      }

      if (this.kawasanLindung.file) {
        formData.append(
          'kL',
          await this.convertBase64(this.kawasanLindung.file)
        );
      }

      for (let i = 0; i < others.length; i++) {
        let otherFile = null;
        if (others[i].file) {
          otherFile = await this.convertBase64(others[i].file);
        }
        formData.append(`file-${i}`, otherFile);
      }

      for (let i = 0; i < pertek.length; i++) {
        let pertekFile = null;
        if (pertek[i].file) {
          pertekFile = await this.convertBase64(pertek[i].file);
        }
        formData.append(`file-pertek-${i}`, pertekFile);
      }

      await andalComposingResource.store(formData);

      this.preAgreement.name = null;
      this.preAgreement.file = null;
      this.ktr.name = null;
      this.ktr.file = null;
      this.pippib.name = null;
      this.pippib.file = null;
      this.kawasanLindung.name = null;
      this.kawasanLindung.file = null;

      this.$message({
        message: 'Data berhasil disimpan',
        type: 'success',
        duration: 5 * 1000,
      });
      this.getData();
      this.loadingSubmit = false;
    },
    handleAdd(documentType) {
      if (documentType === 'pertek') {
        this.attachment.pertek.push({
          id: null,
          name: null,
          file: null,
          fileName: null,
        });
      } else {
        this.attachment.lainnya.push({
          id: null,
          name: null,
          file: null,
          fileName: null,
        });
      }
    },
    handleDelete(id, idx, documentType) {
      if (documentType === 'pertek') {
        this.attachment.pertek.splice(idx, 1);
      } else {
        this.attachment.lainnya.splice(idx, 1);
      }

      if (id) {
        this.deleted.push(id);
      }
    },
    download(url) {
      window.open(url, '_blank').focus();
    },
    handleUploadKtr(file, fileList) {
      if (file.raw.size > 1048576) {
        this.showFileAlert();
        return;
      }

      this.ktr.file = file.raw;
      this.ktr.name = file.name;
      this.ktrError = false;
    },
    handleUploadPa(file, fileList) {
      if (file.raw.size > 1048576) {
        this.showFileAlert();
        return;
      }

      this.preAgreement.file = file.raw;
      this.preAgreement.name = file.name;
      this.paError = false;
    },
    handleUploadPippib(file, fileList) {
      if (file.raw.size > 1048576) {
        this.showFileAlert();
        return;
      }

      this.pippib.file = file.raw;
      this.pippib.name = file.name;
    },
    handleUploadKl(file, fileList) {
      if (file.raw.size > 1048576) {
        this.showFileAlert();
        return;
      }

      this.kawasanLindung.file = file.raw;
      this.kawasanLindung.name = file.name;
    },
    handleUpload(file, fileList) {
      if (file.raw.size > 1048576) {
        this.showFileAlert();
        return;
      }

      this.attachment.lainnya[this.selectedUpload].file = file.raw;
      this.attachment.lainnya[this.selectedUpload].fileName = file.name;
    },
    handleUploadPertek(file, fileList) {
      if (file.raw.size > 1048576) {
        this.showFileAlert();
        return;
      }

      this.attachment.pertek[this.selectedUploadPertek].file = file.raw;
      this.attachment.pertek[this.selectedUploadPertek].fileName = file.name;
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
