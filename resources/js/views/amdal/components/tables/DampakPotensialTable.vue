<template>
  <el-table
    v-loading="loading"
    :data="data"
    fit
    highlight-current-row
    :header-cell-style="{ background: '#def5cf' }"
    style="width: 100%"
  >
    <el-table-column label="Komponen Dampak">
      <template slot-scope="scope">
        <div v-if="scope.row.is_stage">
          <b>{{ scope.row.index }}. {{ scope.row.project_stage_name }}</b>
        </div>
        <div v-if="!scope.row.is_stage">
          <span>{{ scope.row.index }}.</span>
          <span>
            <el-select
              v-model="scope.row.id_change_type"
              placeholder="Perubahan"
              :disabled="isAndal || !isFormulator"
            >
              <el-option
                v-for="item of changeTypeOptions"
                :key="item.id"
                :label="item.name"
                :value="item.id"
              />
            </el-select>
          </span>
        </div>
      </template>
    </el-table-column>
    <el-table-column label="Komponen Rona Lingkungan Awal" align="center">
      <template slot-scope="scope">
        <div v-if="!scope.row.is_stage">
          {{ scope.row.rona_awal_name }}
        </div>
      </template>
    </el-table-column>
    <el-table-column label="" align="center">
      <template slot-scope="scope">
        <div v-if="!scope.row.is_stage">
          akibat
        </div>
      </template>
    </el-table-column>
    <el-table-column label="Sumber Dampak" align="center">
      <template slot-scope="scope">
        <div v-if="!scope.row.is_stage">
          {{ scope.row.component_name }}
        </div>
      </template>
    </el-table-column>
  </el-table>
</template>

<script>
import Resource from '@/api/resource';
const changeTypeResource = new Resource('change-types');
const unitResource = new Resource('units');

export default {
  name: 'DampakPotensialTable',
  props: {
    data: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      loading: true,
      changeTypeOptions: [],
      unitOptions: [],
    };
  },
  computed: {
    isAndal() {
      return this.$route.name === 'penyusunanAndal';
    },
    isFormulator() {
      return this.$store.getters.roles.includes('formulator');
    },
  },
  mounted() {
    this.getData();
  },
  methods: {
    async getData() {
      const changeTypeList = await changeTypeResource.list({});
      this.changeTypeOptions = changeTypeList.data;
      const unitList = await unitResource.list({});
      this.unitOptions = unitList.data;
      this.loading = false;
    },
  },
};
</script>
