<template>
  <div v-loading="loadingGetObject" class="app-container">
    <span slot="title" style="font-size: 14pt; font-weight: bold;">{{ createOrEdit }} KBLI</span>
    <span v-if="isEdit" class="pull-right">
      <el-button
        type="danger"
        icon="el-icon-delete"
        @click="handleDelete"
      >
        Hapus
      </el-button>
    </span>
    <el-form label-position="top" :model="business">
      <el-form-item label="Nomor KBLI">
        <el-autocomplete
          v-model="business.kbli_code"
          class="inline-input"
          :fetch-suggestions="searchKbliCode"
          placeholder="No. KBLI"
          :trigger-on-focus="false"
          @select="handleSelectKbliCode"
        />
      </el-form-item>
      <el-form-item label="Sektor">
        <el-autocomplete
          v-model="business.sector"
          class="inline-input"
          :fetch-suggestions="searchSector"
          placeholder="Sektor"
          :trigger-on-focus="false"
          @select="handleSelectSector"
        />
      </el-form-item>
      <el-form-item label="Deskripsi">
        <el-input v-model="business.value" type="textarea" :rows="2" />
      </el-form-item>
    </el-form>
    <span slot="footer" class="dialog-footer">
      <el-button type="danger" @click="handleClose">Batal</el-button>
      <el-button type="primary" @click="handleSubmit">Simpan</el-button>
    </span>
  </div>
</template>
<script>
import Resource from '@/api/resource';
const businessResource = new Resource('business');

export default {
  name: 'BusinessForm',
  components: {},
  props: {
    isEdit: Boolean,
    idBusiness: {
      type: Number,
      default: () => 0,
    },
  },
  data() {
    return {
      business: {},
      kbliList: [],
      sectorList: [],
      loadingGetObject: true,
    };
  },
  computed: {
    createOrEdit() {
      return this.isEdit ? 'Edit' : 'Tambah';
    },
  },
  async created() {
    this.getAutoCompleteData();
    if (this.isEdit) {
      this.getObject(this.idBusiness);
    } else {
      this.loadingGetObject = false;
      this.business = {};
    }
  },
  methods: {
    searchKbliCode(queryString, cb) {
      var kbliList = this.kbliList;
      var results = queryString ? kbliList
        .filter(this.createFilter(queryString)) : kbliList;
      cb(results);
    },
    createFilter(queryString) {
      return (k) => {
        return (k.value.toLowerCase().indexOf(queryString.toLowerCase()) === 0);
      };
    },
    handleSelectKbliCode(kbliCode) {
      const selected = this.kbliList.filter(k => k.value === kbliCode.value);
      if (selected.length > 0) {
        this.business.kbli_code = selected[0].value;
      }
    },
    searchSector(queryString, cb) {
      var sectorList = this.sectorList;
      var results = queryString ? sectorList
        .filter(this.createFilter(queryString)) : sectorList;
      cb(results);
    },
    handleSelectSector(sector) {
      const selected = this.sectorList.filter(s => s.value === sector.value);
      if (selected.length > 0) {
        this.business.sector = selected[0].value;
      }
    },
    async getObject(id) {
      this.loadingGetObject = true;
      const kbli = await businessResource.get(id);
      this.business = kbli;
      this.loadingGetObject = false;
    },
    async getAutoCompleteData() {
      const kbliResults = await businessResource.list({
        kblis: true,
      });
      const sectorResults = await businessResource.list({
        sectors: true,
      });
      kbliResults.data.forEach(k => {
        this.kbliList.push({
          id: k.id,
          value: k.value,
        });
      });
      sectorResults.data.forEach(s => {
        this.sectorList.push({
          id: s.id,
          value: s.value,
        });
      });
    },
    handleClose() {
      this.$emit('handleClose');
      this.business = {};
    },
    handleDelete() {
      this.$emit('handleDelete', this.business);
    },
    handleSubmit() {
      this.business['description'] = 'field';
      if (this.isEdit) {
        // edit
        businessResource
          .update(this.idBusiness, {
            business: this.business,
          })
          .then((response) => {
            // console.log(response.data);
            if (response.status === 200) {
              this.handleClose();
              this.$message({
                message: 'KBLI berhasil disimpan',
                type: 'success',
                duration: 5 * 1000,
              });
            }
          })
          .catch((error) => {
            this.$message({
              message: 'Gagal menyimpan KBLI',
              type: 'error',
              duration: 5 * 1000,
            });
            console.log(error);
          });
      } else {
        // create
        businessResource
          .store({
            business: this.business,
          })
          .then((response) => {
            // console.log(response.data);
            if (response.status === 200) {
              this.handleClose();
              this.$message({
                message: 'KBLI berhasil disimpan',
                type: 'success',
                duration: 5 * 1000,
              });
            }
          })
          .catch((error) => {
            this.$message({
              message: 'Gagal menyimpan KBLI',
              type: 'error',
              duration: 5 * 1000,
            });
            console.log(error);
          });
      }
    },
  },
};
</script>
