<template>
  <el-table
    v-loading="loading"
    :data="list"
    fit
    highlight-current-row
    :header-cell-style="{ background: '#3AB06F', color: 'white' }"
  >
    <el-table-column type="expand">
      <template slot-scope="scope">
        <div class="expand-container">
          <div>
            <p>
              <b>Provinsi: </b>
              {{ scope.row.province ? scope.row.province.name : '' }}
            </p>
            <p>
              <b>Kota: </b>
              {{ scope.row.district ? scope.row.district.name : '' }}
            </p>
            <p><b>Email: </b>{{ scope.row.email }}</p>
            <p><b>Tgl Awal: </b>{{ scope.row.date_start }}</p>
            <p><b>Tgl Akhir: </b>{{ scope.row.date_end }}</p>
          </div>
          <div
            v-if="checkPermission(['manage lpjp']) || checkRole(['admin'])"
            class="expand-container__right"
          >
            <el-button
              type="primary"
              size="medium"
              @click="handleKelolaLPJP(scope.row.id)"
            >
              Kelola LPJP
            </el-button>
            <el-button
              type="warning"
              size="medium"
              @click="handleEditForm(scope.row.id)"
            >
              Ubah
            </el-button>
            <el-button
              type="danger"
              size="medium"
              @click="handleDelete(scope.row.id, scope.row.name)"
            >
              Hapus
            </el-button>
          </div>
        </div>
      </template>
    </el-table-column>
    <el-table-column
      align="center"
      label="No. Registrasi"
      sortable
      prop="reg_no"
    />
    <el-table-column
      align="center"
      label="Nama Perusahaan"
      sortable
      prop="name"
    />
    <el-table-column align="center" label="Alamat" sortable prop="address" />

    <el-table-column align="center" label="Sertifikat">
      <template slot-scope="scope">
        <el-button
          type="text"
          size="medium"
          icon="el-icon-download"
          style="color: blue"
          @click.prevent="download(scope.row.cert_file)"
        >
          Sertifikat
        </el-button>
        <!-- <div>
          <i class="el-icon-download" />
          <span>{{ 'Sertifikat' }}</span>
        </div> -->
      </template>
    </el-table-column>

    <el-table-column align="center" label="Status">
      <template slot-scope="scope">
        <el-tag
          :type="
            calculateStatus(scope.row.date_start, scope.row.date_end)
              | statusFilter
          "
        >
          {{ calculateStatus(scope.row.date_start, scope.row.date_end) }}
        </el-tag>
        <!-- <span>{{
          calculateStatus(scope.row.tgl_awal, scope.row.tgl_akhir)
        }}</span> -->
      </template>
    </el-table-column>
  </el-table>
</template>

<script>
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';

export default {
  name: 'LpjpTable',
  filters: {
    statusFilter(status) {
      const statusMap = {
        Aktif: 'success',
        NonAktif: 'danger',
      };
      return statusMap[status];
    },
  },
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
    calculateStatus(awal, akhir) {
      const tglAwal = new Date(awal);
      const tglAkhir = new Date(akhir);
      if (
        new Date().getTime() >= tglAwal.getTime() &&
        new Date().getTime() <= tglAkhir.getTime()
      ) {
        return 'Aktif';
      } else {
        return 'NonAktif';
      }
    },
    download(url) {
      window.open(url, '_blank').focus();
    },
    handleEditForm(id) {
      this.$emit('handleEditForm', id);
    },
    handleDelete(id, nama) {
      this.$emit('handleDelete', { id, nama });
    },
    handleKelolaLPJP(id) {
      // eslint-disable-next-line object-curly-spacing
      this.$router.push({ name: 'lpjpFormulatorMember', params: { id } });
    },
  },
};
</script>

<style scoped>
.expand-container {
  display: flex;
  justify-content: space-around;
}
.expand-container div {
  width: 50%;
  padding: 1rem 3rem;
}
.expand-container__right {
  text-align: right;
}
</style>
