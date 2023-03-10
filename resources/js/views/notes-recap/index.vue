<template>
  <div>
    <h3>Ringkasan Catatan Anggota Tim Uji Kelayakan</h3>
    <el-row :gutter="32">
      <label style="display: block; margin-left: 1.4%">Komentar</label>
      <el-col :md="10" :sm="24">
        <el-select
          v-model="selectedColumn"
          placeholder="Pilihan"
          style="width: 100%"
          @change="handleChangeDocumentType"
        >
          <el-option
            v-for="item in getColumn"
            :key="item.value"
            class="sel-hover"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </el-col>
      <el-col :md="14" :sm="24">
        <el-button :loading="loadingDownload" type="primary" @click="download">
          Unduh Rekap Catatan
        </el-button>
      </el-col>
    </el-row>
    <el-row :gutter="32">
      <el-col :md="24" :sm="24" style="margin-top: 30px">
        <label style="display: block">Daftar Catatan</label>
        <el-table
          v-loading="loading"
          :data="list"
          fit
          :header-cell-style="{ background: '#3AB06F', color: 'white' }"
        >
          <el-table-column label="No." width="54px" align="center">
            <template slot-scope="scope">
              <span>{{ scope.$index + 1 }}</span>
            </template>
          </el-table-column>

          <el-table-column v-if="isShowStage" label="Tahap" align="center">
            <template slot-scope="scope">
              <span>{{ scope.row.stage }}</span>
            </template>
          </el-table-column>

          <el-table-column v-if="isShowImpact" label="Dampak" align="center">
            <template slot-scope="scope">
              <span>{{ scope.row.impact }}</span>
            </template>
          </el-table-column>

          <el-table-column label="Kolom" align="center">
            <template slot-scope="scope">
              <span>{{ scope.row.column }}</span>
            </template>
          </el-table-column>

          <el-table-column label="Catatan" align="center">
            <template slot-scope="scope">
              <span v-html="scope.row.notes" />
            </template>
          </el-table-column>

          <el-table-column label="Nama Anggota TUK" align="center">
            <template slot-scope="scope">
              <span>{{ scope.row.tuk }}</span>
            </template>
          </el-table-column>
        </el-table>
      </el-col>
    </el-row>
  </div>
</template>

<script>
import Resource from '@/api/resource';
import axios from 'axios';
const commentResource = new Resource('ka-comment');

export default {
  name: 'NotesRecap',
  props: {
    flow: {
      type: String,
      default: () => '',
    },
  },
  data() {
    return {
      list: [],
      columns: [],
      selectedColumn: null,
      loading: false,
      loadingDownload: false,
      uklUplColumn: [
        {
          label: 'Identifikasi Komponen Kegiatan dan Komponen Lingkungan',
          value: 'pelingkupan',
        },
      ],
      kaColumn: [
        {
          label: 'Pelingkupan',
          value: 'pelingkupan',
        },
        {
          label: 'Evaluasi Dampak Potensial & Dampak Penting Hipotetik',
          value: 'dpdph-ka',
        },
        {
          label: 'Peta Batas Wilayah Studi & Peta Pendukung',
          value: 'peta-batas-ka',
        },
        {
          label: 'Metode Studi',
          value: 'metode-studi-ka',
        },
      ],
      andalColumn: [
        {
          label: 'Peta Batas Wilayah Studi & Peta Lainnya',
          value: 'peta-batas-andal',
        },
        {
          label: 'Pelingkupan',
          value: 'pelingkupan-andal',
        },
        {
          label: 'Evaluasi Dampak Potensial & Dampak Penting Hipotetik',
          value: 'dpdph-andal',
        },
        {
          label: 'Metode Studi',
          value: 'metode-studi-andal',
        },
        {
          label: 'Analisa Dampak Lingkungan',
          value: 'andal',
        },
      ],
      rklRplColumn: [
        {
          label: 'Matriks Rencana Pengelolaan Lingkungan Hidup',
          value: 'rkl',
        },
        {
          label: 'Matriks Rencana Pemantauan Lingkungan Hidup',
          value: 'rpl',
        },
      ],
    };
  },
  computed: {
    getColumn() {
      if (this.flow === 'ka') {
        return this.kaColumn;
      } else if (this.flow === 'andal') {
        return this.andalColumn;
      } else if (this.flow === 'rkl-rpl') {
        return this.rklRplColumn;
      } else if (this.flow === 'ukl-upl') {
        return this.uklUplColumn;
      }

      return [];
    },
    isShowStage() {
      if (this.selectedColumn === 'pelingkupan') {
        return true;
      }

      return false;
    },
    isShowImpact() {
      if (
        this.selectedColumn === 'dpdph-ka' ||
        this.selectedColumn === 'dpdph-andal' ||
        this.selectedColumn === 'metode-studi-ka' ||
        this.selectedColumn === 'metode-studi-andal' ||
        this.selectedColumn === 'andal' ||
        this.selectedColumn === 'rkl' ||
        this.selectedColumn === 'rpl'
      ) {
        return true;
      }

      return false;
    },
  },
  methods: {
    async getData() {
      this.loading = true;
      this.list = await commentResource.list({
        recap: 'true',
        documentType: this.selectedColumn,
        idProject: this.$route.params.id,
      });
      this.loading = false;
    },
    handleChangeDocumentType() {
      this.getData();
    },
    download() {
      this.loadingDownload = true;
      axios
        .get(
          `/api/ka-comment?download=true&idProject=${this.$route.params.id}&documentType=${this.flow}`,
          {
            responseType: 'blob',
          }
        )
        .then((response) => {
          const fileUrl = window.URL.createObjectURL(response.data);
          const fileLink = document.createElement('a');

          fileLink.href = fileUrl;
          fileLink.setAttribute('download', 'rekap.docx');
          document.body.appendChild(fileLink);

          fileLink.click();
          this.loadingDownload = false;
        })
        .catch((error) => {
          console.log(error.response.data);
          this.loadingDownload = false;
        });
    },
  },
};
</script>

<style scoped>
  .sel-hover:hover {
  background-color: #c1c1c1;
}
</style>
