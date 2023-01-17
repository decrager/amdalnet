<template>
  <el-table
    v-loading="loading"
    :data="list"
    fit
    border
    highlight-current-row
    :header-cell-style="{
      background: 'rgb(58, 176, 111)',
      color: 'white',
      border: '0px',
    }"
    style="font-size: 12px"
  >
    <el-table-column
      label="No"
      width="50px"
      header-align="center"
      align="center"
    >
      <template slot-scope="scope">
        <span>
          {{ scope.$index + 1 }}
        </span>
      </template>
    </el-table-column>

    <el-table-column label="Jenis Dampak yang Timbul">
      <template slot-scope="scope">
        <span>
          {{ scope.row.name }}
        </span>
      </template>
    </el-table-column>

    <el-table-column label="" align="center" width="150px">
      <template slot-scope="scope">
        <el-button
          type="text"
          @click="showDetail(scope.row.stage, scope.row.id)"
        >
          {{ isFormulator ? 'Edit' : 'Lihat' }}
        </el-button>
      </template>
    </el-table-column>
  </el-table>
</template>

<script>
import { mapGetters } from 'vuex';

export default {
  name: 'TableMaster',
  props: {
    list: {
      type: Array,
      default: () => [],
    },
    loading: Boolean,
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

    isFormulator() {
      return this.$store.getters.roles.includes('formulator');
    },
  },
  methods: {
    showDetail(stage, id) {
      this.$emit('showDetail', { stage, id });
    },
  },
};
</script>
