<template>
  <div id="dpdph-master-table" v-loading="loading">
    <el-row :gutter="10" style="margin-bottom: 0.5em;">
      <el-col :span="12">
        <dampak-hipotetik-preview
          v-if="(impacts && impacts.length > 0)"
          :impacts="impacts"
          :stages="stages"
          :pie-params="pieParams"
          :mode="mode"
        />
        <p v-else>&nbsp;</p>
      </el-col>
      <el-col :span="12" style="text-align: right;">
        <span v-if="impacts">Total: <span style="font-weight: bold">{{ impacts.length }}</span></span>
      </el-col>
    </el-row>
    <!-- tableData.filter(data => !search || data.name.toLowerCase().includes(search.toLowerCase())) -->

    <!-- <div style="text-align:right;">
      <el-select
        v-if="subProjects"
        v-model="search"
        placeholder="Semua Rencana Usaha/Kegiatan"
        size="large"
        :clearable="true"
        class="filter-header"
        style="width: 100% !important;"
      >
        <el-option
          v-for="item in subProjects"
          :key="item.value"
          :label="item.label"
          :value="item.value"
        >
          <div>
            <span style="float:left;">{{ item.label }}</span>
            <span style="float:right; color: #8492a6; font-size: 13px">{{ 'Kegiatan '+ item.type }}, {{ item.count }}</span>
          </div>
        </el-option>
      </el-select>
    </div>-->
    <el-table
      :data="impacts.filter(data => !search || data.kegiatan === search)"
      max-height="400"
      highlight-current-row
      header-row-class-name="dpdph-table"
      style="width: 100%"
      @current-change="selection"
    >
      <!-- <el-table-column>
        <template slot="header">
          <div v-if="search !== ''">
            {{ search }}
          </div>
          <div v-else>
            Semua Rencana Usaha/Kegiatan
          </div>
        </template> -->
      <el-table-column
        label="No."
        width="60"
        align="center"
      >
        <template slot-scope="scope">
          {{ scope.$index + 1 }}
        </template>
      </el-table-column>
      <el-table-column
        prop="stage"
        label="Tahap"
        width="180"
        :filters="stageFilters"
        :filter-method="filterTag"
        filter-placement="bottom-end"
      />
      <el-table-column
        label="Kegiatan"
        prop="komponen"
        width="200"
        :filters="components"
        :filter-method="onActivityChange"
        filter-placement="bottom-end"
      />
      <el-table-column
        label="Rona Lingkungan"
        prop="rona_awal"
        width="200"
        :filters="hues"
        :filter-method="onHueChange"
        filter-placement="bottom-end"
      />
      <!-- <el-table-column
          label="tipe"
          width="80"
          :filters="activityTypeFilters"
          :filter-method="onATFChange"
          filter-placement="bottom-end"
          align="center"
        >
          <template slot-scope="s">
            <el-tooltip class="item" effect="dark" :content="s.row.type" placement="right">
              <el-tag style="text-transform: uppercase">{{ (s.row.type).charAt(0) }}</el-tag>
             </el-tooltip>
          </template>
        </el-table-column>
        <el-table-column
          prop="komponen"
          label="Nama"
          width="250"
        />
        -->
      <el-table-column
        v-if="changeFilters.length > 0"
        label="Dampak"
        :filters="changeFilters"
        :filter-method="onChangefilter"
        filter-placement="bottom-end"
      >
        <template slot-scope="scope">
          <div>
            <!--
          <span v-if="scope.row.id_change_type" :class=" (scope.row.change_type_name === null) ? 'empty':'change-type'" >{{ scope.row.change_type_name || '*belum terdefinisi*'  }}</span>
          -->
            <span class="empty"> {{ scope.row.change_type_name || '*belum terdefinisi*' }}</span>
            <!-- <span v-else >{{ changeTypes.find(e => e.id = scope.row.id_change_type).name }}</span> -->

            {{ scope.row.rona_awal }} akibat {{ scope.row.komponen }}

            <span style="font-weight: 500; margin-left: 1em;">
              <el-tag v-if="scope.row.is_hypothetical_significant" class="dph">DPH</el-tag>
              <el-tag v-else-if="scope.row.is_hypothetical_significant === false" type="info" class="dtph">DTPH</el-tag>
              <el-tag v-if="(scope.row.is_hypothetical_significant === false) && (scope.row.is_managed === true)" type="info" class="dph">Dikelola</el-tag>
              <el-tag v-if="scope.row.hasChanges === true" type="danger">~diubah</el-tag>
              <!--
  <el-button  v-if="scope.row.comment > 0" type="danger" icon="el-icon-s-comment" size="medium" plain circle> </el-button> -->
              <i v-if="scope.row.comment > 0" class="el-icon-s-comment" style="font-size: 150%; line-height:0.8em;" />
            </span>
          </div>

        </template>
      </el-table-column>
      <!-- </el-table-column> -->
    </el-table>
    <el-row>
      <el-col>
        <div style="text-align: right; margin-top: 1.5em;">
          <span v-if="totalChanges > 0" style="margin-right: 1em;">Diubah: <span style="font-weight:bold; color: red;">{{ totalChanges }}</span></span>
          <el-button
            type="success"
            icon="el-icon-check"
            :disabled="totalChanges <= 0"
            @click="saveChanges()"
          >
            Simpan Perubahan
          </el-button>
        </div>
      </el-col>
    </el-row>

  </div>
</template>
<script>
import DampakHipotetikPreview from './DPDPHPreview.vue';
export default {
  name: 'DampakHipotetikMasterTable',
  components: { DampakHipotetikPreview },
  props: {
    impacts: {
      type: Array,
      default: function(){
        return [];
      },
    },
    stages: {
      type: Array,
      default: null,
    },
    pieParams: {
      type: Array,
      default: null,
    },
    changeTypes: {
      type: Array,
      default: null,
    },
    subProjects: {
      type: Array,
      default: null,
    },
    components: {
      type: Array,
      default: null,
    },
    hues: {
      type: Array,
      default: null,
    },
    idProject: {
      type: Number,
      default: 0,
    },
    totalChanges: {
      type: Number,
      default: 0,
    },
    mode: {
      type: Number,
      default: 0,
    },
    loading: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      preview: false,
      search: '',
      stageFilters: [
        { text: 'Pra Konstruksi', value: 'Pra Konstruksi' },
        { text: 'Konstruksi', value: 'Konstruksi' },
        { text: 'Operasi', value: 'Operasi' },
        { text: 'Pasca Operasi', value: 'Pasca Operasi' },
      ],
      changeFilters: [
        { text: 'Data diubah', value: 1 },
        { text: 'DPH', value: 2 },
        { text: 'DTPH', value: 3 },
        { text: 'Belum terdefinisi', value: 4 },
        { text: 'Bercatatan', value: 5 },
      ],
      activityTypeFilters: [
        { text: 'Utama', value: 'utama' },
        { text: 'Pendukung', value: 'pendukung' },
      ],
      activitiesFilter: [],
    };
  },
  watch: {
    impacts: function(val){
      // console.log('master table:', val);
      if (val === null) {
        this.search = '';
      }
      if (val.length > 0){
        this.getActivities();
      } else {
        this.search = '';
      }
    },
  },
  methods: {
    saveChanges() {
      this.$emit('saveChanges', true);
    },
    filterTag(value, row){
      return row.stage === value;
    },
    getActivities(){
      console.log(this.components);
    },
    onChangefilter(value, row){
      // 1, 2, 3 => data diubah, dph, dtph
      if (value === 1) {
        if (row.hasChanges) {
          return row.hasChanges === true;
        }
      }
      if (value === 2) {
        return row.is_hypothetical_significant === true;
      }
      if (value === 3) {
        return row.is_hypothetical_significant === false;
      }

      if (value === 4) {
        if (row.change_type_name === null) {
          return true;
        }
        return (row.id_change_type === null) || ((row.change_type_name).trim() === '');
      }

      if (value === 5) {
        return (row.comment > 0);
      }
      return false;
    },
    onATFChange(value, row){
      return row.type === value;
    },
    onActivityChange(value, row){
      return row.komponen === value;
    },
    onHueChange(value, row){
      return (row.rona_awal).toLowerCase() === value.toLowerCase();
    },
    onFilterChange(e){
      console.log(e);
    },
    selection(e, r){
      // console.log('selection:', e, r);
      this.$emit('dataSelected', e);
    },
  },
};
</script>
<style lang="scss" >
#dpdph-master-table{
  overflow: auto;
  padding: 1em ;
  border: 1px solid #e0e0e0;
  border-radius: 1em;
  margin: 2em 0;
  padding: 1em 2em 2em;

  .el-table {
    .current-row { font-weight: bold; }
  }

    span.empty { color: red; }
    span.change-type { text-decoration: underline; }

  .dpdph-table{
    th.el-table__cell > .cell.highlight,
    th{
        /* background: none !important;*/
        color: white !important;

      }
      th.el-table__cell > .cell.highlight:before{
        content: '\2022';
        margin-right: 0.5em;
      }
  }
}

    span.el-table__column-filter-trigger {
      margin-left: 0.2em;
      color: white !important;
      .el-icon-arrow-down{
        font-size: 120%;
        color: white !important;
        font-weight: bold !important;
      }
    }

</style>
