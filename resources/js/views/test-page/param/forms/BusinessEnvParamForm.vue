<template>
  <div v-loading="loadingGetObject" class="app-container">
    <span slot="title" style="font-size: 14pt; font-weight: bold;">{{ createOrEdit }} Parameter KBLI</span>
    <span v-if="isEdit" class="pull-right">
      <el-button
        type="danger"
        icon="el-icon-delete"
        @click="handleDelete"
      >
        Hapus
      </el-button>
    </span>
    <el-form label-position="top" :model="businessEnvParam">
      <el-form-item label="Nama Parameter">
        <el-autocomplete
          v-model="businessEnvParam.param_name"
          class="inline-input"
          autosize
          :fetch-suggestions="searchParam"
          placeholder="Parameter"
          :trigger-on-focus="false"
          type="textarea"
          style="width: 100%"
          @select="handleSelectParam"
        />
        <el-input
          v-model="businessEnvParam.id_param"
          type="hidden"
        />
      </el-form-item>
      <el-form-item label="Kondisi">
        <el-input v-model="businessEnvParam.condition_string" />
      </el-form-item>
      <el-form-item label="Unit">
        <el-autocomplete
          v-model="businessEnvParam.unit_name"
          class="inline-input"
          autosize
          :fetch-suggestions="searchUnit"
          placeholder="Unit"
          :trigger-on-focus="false"
          type="textarea"
          style="width: 100%"
          @select="handleSelectUnit"
        />
        <el-input
          v-model="businessEnvParam.id_unit"
          type="hidden"
        />
      </el-form-item>
      <el-form-item label="Jenis Dokumen">
        <el-select
          v-model="businessEnvParam.doc_req"
          placeholder="Pilih Jenis Dokumen"
        >
          <el-option
            v-for="item of docReqs"
            :key="item.id"
            :label="item.name"
            :value="item.name"
          />
        </el-select>
      </el-form-item>
      <el-form-item v-if="businessEnvParam.doc_req === 'AMDAL'" label="Tipe AMDAL">
        <el-select
          v-model="businessEnvParam.amdal_type"
          placeholder="Pilih Tipe AMDAL"
        >
          <el-option
            v-for="item of amdalTypes"
            :key="item.id"
            :label="item.name"
            :value="item.name"
          />
        </el-select>
      </el-form-item>
      <el-form-item v-if="businessEnvParam.doc_req === 'UKL-UPL'" label="Tingkat Risiko">
        <el-select
          v-model="businessEnvParam.risk_level"
          placeholder="Pilih Tingkat Risiko"
        >
          <el-option
            v-for="item of riskLevels"
            :key="item.id"
            :label="item.name"
            :value="item.name"
          />
        </el-select>
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
const bepResource = new Resource('business-env-params');
const paramResource = new Resource('params');
const unitResource = new Resource('units');

export default {
  name: 'BusinessEnvParamForm',
  components: {},
  props: {
    isEdit: Boolean,
    idBusinessEnvParam: {
      type: Number,
      default: () => 0,
    },
  },
  data() {
    return {
      idBusiness: 0,
      businessEnvParam: {},
      paramList: [],
      unitList: [],
      docReqs: [
        {
          id: 1,
          name: 'AMDAL',
        },
        {
          id: 2,
          name: 'UKL-UPL',
        },
        {
          id: 3,
          name: 'SPPL',
        },
        {
          id: 4,
          name: 'Mengikuti Dokling Induk',
        },
      ],
      amdalTypes: [
        {
          id: 1,
          name: 'A',
        },
        {
          id: 2,
          name: 'B',
        },
        {
          id: 3,
          name: 'C',
        },
      ],
      riskLevels: [
        {
          id: 1,
          name: 'Menengah Rendah',
        },
        {
          id: 2,
          name: 'Menengah Tinggi',
        },
      ],
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
    this.idBusiness = this.$route.params && this.$route.params.id;
    if (this.isEdit) {
      this.getObject(this.idBusinessEnvParam);
    } else {
      this.loadingGetObject = false;
      this.businessEnvParam = {};
    }
  },
  methods: {
    searchParam(queryString, cb) {
      var paramList = this.paramList;
      var results = queryString ? paramList
        .filter(this.createFilter(queryString)) : paramList;
      cb(results);
    },
    createFilter(queryString) {
      return (k) => {
        return (k.value.toLowerCase().indexOf(queryString.toLowerCase()) === 0);
      };
    },
    handleSelectParam(param) {
      const selected = this.paramList.filter(k => k.value === param.value);
      if (selected.length > 0) {
        this.businessEnvParam.id_param = selected[0].id;
        this.businessEnvParam.param_name = selected[0].value;
      }
    },
    searchUnit(queryString, cb) {
      var unitList = this.unitList;
      var results = queryString ? unitList
        .filter(this.createFilter(queryString)) : unitList;
      cb(results);
    },
    handleSelectUnit(unit) {
      const selected = this.unitList.filter(s => s.value === unit.value);
      if (selected.length > 0) {
        this.businessEnvParam.id_unit = selected[0].id;
        this.businessEnvParam.unit_name = selected[0].value;
      }
    },
    async getObject(id) {
      this.loadingGetObject = true;
      const param = await bepResource.get(id);
      this.businessEnvParam = param;
      this.loadingGetObject = false;
    },
    async getAutoCompleteData() {
      const paramResults = await paramResource.list({});
      const unitResults = await unitResource.list({});
      paramResults.data.forEach(p => {
        this.paramList.push({
          id: p.id,
          value: p.name,
        });
      });
      unitResults.data.forEach(u => {
        this.unitList.push({
          id: u.id,
          value: u.name,
        });
      });
    },
    handleClose() {
      this.$emit('handleClose');
      this.businessEnvParam = {};
    },
    handleDelete() {
      this.$emit('handleDelete', this.businessEnvParam);
    },
    handleSubmit() {
      if (this.isEdit) {
        // edit
        bepResource
          .update(this.idBusinessEnvParam, {
            businessEnvParam: this.businessEnvParam,
          })
          .then((response) => {
            if (response.status === 200) {
              this.handleClose();
              this.$message({
                message: 'Parameter KBLI berhasil disimpan',
                type: 'success',
                duration: 5 * 1000,
              });
              this.$emit('handleReloadList');
            }
          })
          .catch((error) => {
            this.$message({
              message: 'Gagal menyimpan Parameter KBLI',
              type: 'error',
              duration: 5 * 1000,
            });
            console.log(error);
          });
      } else {
        // create
        this.businessEnvParam.business_id = this.idBusiness;
        bepResource
          .store({
            businessEnvParam: this.businessEnvParam,
          })
          .then((response) => {
            if (response.status === 200) {
              this.handleClose();
              this.$message({
                message: 'Parameter KBLI berhasil disimpan',
                type: 'success',
                duration: 5 * 1000,
              });
              this.$emit('handleReloadList');
            }
          })
          .catch((error) => {
            this.$message({
              message: 'Gagal menyimpan Parameter KBLI',
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
