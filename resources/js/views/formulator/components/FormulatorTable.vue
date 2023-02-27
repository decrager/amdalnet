<!-- eslint-disable vue/html-indent -->
<template>
  <el-table
    v-loading="loading"
    :data="list"
    fit
    highlight-current-row
    :header-cell-style="{ background: '#3AB06F', color: 'white' }"
  >

<!-- KOLOM NAMA -->
    <el-table-column
      align="center"
      label="Nama"
      prop="name"
      sortable
    />

<!-- KOLOM NO REGISTRASI -->
    <el-table-column
      align="center"
      label="No. Registrasi"
      prop="reg_no"
    />

<!-- KOLOM NO SERTIFIKAT -->
    <el-table-column
      align="center"
      label="No. Sertifikat"
      prop="cert_no"
      sortable
    />

<!-- KOLOM SERTIFIKASI -->
    <el-table-column
      align="center"
      width="144px"
      prop="membership_status"
      column-key="membership_status"
    >
      <template slot="header">
        <el-select
          v-model="membershipStatusFilter "
          class="filter-header"
          clearable
          placeholder="Sertifikasi"
          @change="onMembershipStatusFilter"
        >
          <el-option
            v-for="item in options"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </template>
    </el-table-column>

<!-- KOLOM LSP -->
    <el-table-column v-if="checkRole(['admin'])" align="center" prop="data_lsp.lsp_name" column-key="data_lsp.lsp_name">
      <template slot="header">
        <el-select
          v-model="lspFilter "
          class="filter-header"
          clearable
          placeholder="LSP"
          @change="onLspFilter"
        >
          <el-option
            v-for="item in lspOptions"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </template>
    </el-table-column>

<!-- KOLOM STATUS SERTIFIKAT -->
    <el-table-column
      align="center"
      label="Status Sertifikat"
      prop="status"
      width="144px"
    >
      <template slot-scope="scope">
        <el-tag :type="scope.row.status | statusFilter">
          {{ scope.row.status }}
        </el-tag>
      </template>
    </el-table-column>

<!-- KOLOM STATUS AKUN -->
    <el-table-column
      width="144px"
      align="center"
      label="Status Akun"
    >
      <template slot-scope="scope">
        <span v-if="scope.row.user && scope.row.user.email">Terdaftar</span>
        <span v-else>Tidak Terdaftar</span>
      </template>

      <!-- <template slot="header">
        <el-select
          v-model="emailStatusFilter"
          class="filter-header"
          clearable
          placeholder="Status Akun"
          @change="onEmailStatusFilter"
        >
          <el-option
            v-for="item in emailStatusFilterOptions"
            :key="item.value"
            :label="item.label"
            :value="item.value"
          />
        </el-select>
      </template> -->

    </el-table-column>

<!-- KOLOM AKUN -->
    <el-table-column
      width="144px"
      align="center"
      label="Akun"
    >
    <template slot-scope="scope">
      <span v-if="scope.row.user && scope.row.user.active === 1">Aktif</span>
      <span v-else-if="scope.row.user && scope.row.user.active === 0">Tidak Aktif</span>
      <span v-else>Tidak Aktif</span>
    </template>
    </el-table-column>

<!-- KOLOM FILE -->
    <el-table-column width="144px" label="File">
      <template slot-scope="scope">
        <el-button
          v-if="scope.row.cert_file"
          type="text"
          size="medium"
          icon="el-icon-download"
          style="color: blue"
          @click.prevent="download(scope.row.cert_file)"
        >
          Sertifikat
        </el-button>
        <el-button
          v-if="scope.row.cv_file"
          type="text"
          size="medium"
          icon="el-icon-download"
          style="color: blue"
          @click.prevent="download(scope.row.cv_file)"
        >
          CV
        </el-button>
      </template>
    </el-table-column>

<!-- KOLOM AKSI -->
    <el-table-column
      v-if="
        !certificate &&
        (checkPermission(['manage formulator']) || checkRole(['admin']))
      "
      align="center"
      label="Aksi"
    >
      <template slot-scope="scope">
        <el-button
          v-if="!scope.row.user || scope.row.user.active === 0"
          type="danger"
          size="mini"
          href="#"
          icon="el-icon-delete"
          @click="handleDelete(scope.row.id, scope.row.name)"
        >
          Hapus
        </el-button>
        <el-button
          type="warning"
          size="mini"
          href="#"
          @click="handleUpdateCertificate(scope.row.id, scope.row.name)"
        >
          Update
        </el-button>
      </template>
    </el-table-column>
  </el-table>
</template>

<script>
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';
import Resource from '@/api/resource';
const lspResource = new Resource('lsp');

export default {
  name: 'FormulatorTable',
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
    emailStatusFilterOptions: {
      type: Array,
      default: () => [
        {
          value: !'',
          label: 'Terdaftar',
        },
        {
          value: '',
          label: 'Tidak Terdaftar',
        },
      ],
    },
    options: {
      type: Array,
      default: () => [
        {
          value: 'KTPA',
          label: 'KTPA',
        },
        {
          value: 'ATPA',
          label: 'ATPA',
        },
        {
          value: 'TA',
          label: 'Tenaga Ahli',
        },
      ],
    },
    loading: Boolean,
    certificate: Boolean,
  },
  data() {
    return {
      listQuery: [],
      lspOptions: [],
      membershipStatusFilter: '',
      lspFilter: '',
      emailStatusFilter: '',
    };
  },
  mounted() {
    this.getLsp();
  },
  methods: {
    checkPermission,
    checkRole,
    async getLsp() {
      const { data } = await lspResource.list({
        options: 'true',
      });
      this.lspOptions = data.map((i) => {
        return { value: i.id, label: i.lsp_name };
      });
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
    handleCertificate(id) {
      // eslint-disable-next-line object-curly-spacing
      this.$router.push({ name: 'certificateFormulator', params: { id } });
    },
    onMembershipStatusFilter(val) {
      // console.log('membershipStatus: ', val);
      this.$emit('membershipStatusFilter', val);
    },
    onLspFilter(val) {
      // console.log('membershipStatus: ', val);
      this.$emit('lspFilter', val);
    },
    onEmailStatusFilter(val) {
      // console.log('membershipStatus: ', val);
      this.$emit('emailStatusFilter', val);
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
