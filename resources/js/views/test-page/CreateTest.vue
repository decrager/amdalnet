<template>
  <div class="app-container">
    <el-form ref="form" :model="form" label-width="120px">
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
    onSubmit() {
      businessResource.store({
        business: this.business,
      }).then((response) => {
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
    },
    onCancel() {
      this.$message({
        message: 'cancel!',
        type: 'warning',
      });
    },
  },
};
</script>

<style scoped>
.line{
  text-align: center;
}
</style>

