<template>
  <el-table
    v-loading="loading"
    :data="list"
    fit
    highlight-current-row
    :header-cell-style="{ background: '#3AB06F', color: 'white', border: '0' }"
  >
    <el-table-column label="No." width="54px">
      <template slot-scope="scope">
        <span>{{ scope.$index + 1 }}</span>
      </template>
    </el-table-column>
    <el-table-column label="Jenis Template" prop="template_type" sortable />
    <el-table-column label="Deskripsi" prop="description" sortable />
    <el-table-column label="Download/File">
      <template slot-scope="scope">
        <span>{{ scope.row.file }}</span>
      </template>
    </el-table-column>
    <el-table-column
      v-if="checkPermission(['manage env approve']) || checkRole(['admin'])"
      label="Aksi"
    >
      <template slot-scope="scope">
        <el-button
          type="text"
          href="#"
          icon="el-icon-edit"
          @click="handleEditForm(scope.row)"
        >
          Ubah
        </el-button>
        <el-button
          type="text"
          href="#"
          icon="el-icon-delete"
          @click="handleDelete(scope.row)"
        >
          Hapus
        </el-button>
      </template>
    </el-table-column>
  </el-table>
</template>

<script>
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';

export default {
  name: 'ComponentTable',
  props: {
    list: {
      type: Array,
      default: () => [],
    },
    loading: Boolean,
  },
  methods: {
    checkPermission,
    checkRole,
    handleEditForm(row) {
      const currentParam = row;
      this.$router.push({
        name: 'EditDaftarPersetujuan',
        params: { row, appParams: currentParam },
      });
    },
    handleDelete(rows) {
      this.$emit('handleDelete', { rows });
    },
    formatDateStr(date) {
      const today = new Date(date);
      var bulan = [
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember',
      ];
      const year = today.getFullYear();
      const month = today.getMonth();
      const day = today.getDate();
      const monthID = bulan[month];
      const finalDate = `${day} ${monthID} ${year}`;
      return finalDate;
    },
  },
};
</script>
<style rel="stylesheet/scss" lang="scss" scoped>
.el-table-column {
  border: none;
}
</style>
