<!-- eslint-disable vue/html-indent -->
<template>
  <el-table
    v-loading="loading"
    :data="list"
    fit
    highlight-current-row
    :header-cell-style="{ background: '#3AB06F', color: 'white', border: '0' }"
  >
    <el-table-column
      v-if="
        checkPermission(['manage materials and policies']) ||
        checkRole(['admin'])
      "
      type="expand"
    >
      <template slot-scope="scope">
        <!-- <el-button
          type="text"
          href="#"
          icon="el-icon-view"
          @click="handleView(scope.row)"
        >
          View
        </el-button> -->
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
    <el-table-column label="No." width="54px">
      <template slot-scope="scope">
        <span>{{ scope.$index + 1 }}</span>
      </template>
    </el-table-column>
    <el-table-column label="Judul Materi" prop="name" sortable />
    <el-table-column label="Deskripsi" prop="description" sortable />
    <el-table-column label="Tanggal Terbit" align="center" prop="raise_date" sortable />
    <el-table-column label="Link" align="center" sortable>
      <template slot-scope="scope">
        <el-button
          type="text"
          icon="el-icon-download"
          @click="download(scope.row.link)"
        >
          link
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
    handleView(row) {
      this.$emit('handleView', row);
    },
    download(url) {
      window.open(url, '_blank').focus();
    },
    handleEditForm(row) {
      const currentParam = row;
      this.$router.push({
        name: 'editMateri',
        params: { row, appParams: currentParam },
      });
    },
    handleDelete(rows) {
      this.$emit('handleDelete', { rows });
    },
  },
};
</script>
<style rel="stylesheet/scss" lang="scss" scoped>
.el-table-column {
  border: none;
}
</style>
