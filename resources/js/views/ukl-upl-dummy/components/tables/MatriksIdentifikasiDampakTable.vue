<template>
  <div class="container">
    <el-table
      :data="data"
      :span-method="arraySpanMethod"
    >
      <el-table-column prop="component_name" label="Komponen Kegiatan" align="left">
        <template slot-scope="scope">
          <template v-if="scope.row.type == 'stage'">
            <b>{{ scope.row.component_name }}</b>
          </template>
          <template v-if="scope.row.type == 'component'">
            {{ scope.row.component_name }}
          </template>
        </template>
      </el-table-column>
      <el-table-column v-for="ct in component_types" :key="ct" :label="ct" prop="component_types" align="center">
        <el-table-column v-for="r in ronaAwalListFiltered(ct)" :key="r.key" :label="r.name" :prop="JSON.stringify({ct, property: r.key})" align="center">
          <template slot-scope="scope">
            <template v-if="cellFormatter(scope.row, scope.column) === 'v'">
              <img src="images/check-green.jpeg" class="icon-check">
            </template>
          </template>
        </el-table-column>
      </el-table-column>
    </el-table>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'MatriksIdentifikasiDampakTable',
  data() {
    return {
      idProject: 0,
      component_types: [],
      data: [],
      ronaMapping: {},
      maxColspan: 1,
    };
  },
  mounted() {
    this.getData();
  },
  methods: {
    ronaAwalListFiltered(component_type) {
      return this.ronaMapping[component_type];
    },
    cellFormatter(row, col) {
      const key = JSON.parse(col.property);
      const d = row.component_types.find(r => r.name === key.ct);
      if (d && d[key.property]) {
        return d[key.property];
      }
      return ' ';
    },
    arraySpanMethod({ row, column, rowIndex, columnIndex }) {
      if (row.type === 'stage') {
        return [1, this.maxColspan];
      }
      if (row.type === 'component') {
        return [1, 1];
      }
    },
    setMaxColspan(){
      let length = 0;
      Object.keys(this.ronaMapping).forEach(key => {
        length += this.ronaMapping[key].length;
      });
      this.maxColspan = length + 1;
    },
    async getData() {
      this.idProject = parseInt(this.$route.params && this.$route.params.id);
      await axios.get('api/matriks-dampak/rona-mapping/' + this.idProject)
        .then(response => {
          this.ronaMapping = response.data;
        });
      await axios.get('api/matriks-dampak/table/' + this.idProject)
        .then(response => {
          this.data = response.data;
          const ctypes = {};
          this.data.forEach(row => {
            row.component_types.forEach(c => {
              ctypes[c.name] = 1;
            });
          });
          this.component_types = Object.keys(ctypes);
          this.setMaxColspan();
        });
    },
  },
};
</script>
<style scoped>
.icon-check {
  width: 25px;
  height: 25px;
}
</style>
