<!-- eslint-disable vue/html-indent -->
<template>
  <div class="app-container">
    <el-card>
      <h2 v-if="updateCertificate">Perbarui Penyusun</h2>
      <div class="filter-container">
        <el-button
          v-if="
            !updateCertificate &&
            (checkPermission(['manage formulator']) || checkRole(['admin']))
          "
          class="filter-item"
          type="primary"
          icon="el-icon-plus"
          @click="handleCreate"
        >
          {{ 'Tambah Penyusun' }}
        </el-button>
        <!-- <el-button
          v-if="
            !updateCertificate &&
            (checkPermission(['manage formulator']) || checkRole(['admin']))
          "
          class="filter-item"
          type="primary"
          @click="handleUpdateCertificate"
        >
          {{ 'Update Sertifikasi' }}
        </el-button> -->
        <el-row v-if="!updateCertificate" :gutter="32">
          <el-col :sm="24" :md="10">
            <el-input
              v-model="listQuery.search"
              suffix-icon="el-icon search"
              placeholder="Pencarian..."
              @input="inputSearch"
            >
              <el-button
                slot="append"
                icon="el-icon-search"
                @click="handleSearch"
              />
            </el-input>
          </el-col>
        </el-row>
        <el-row v-if="updateCertificate" :gutter="32">
          <el-col :sm="24" :md="10">
            <el-input
              v-model="listQuery.search"
              suffix-icon="el-icon search"
              placeholder="Pencarian..."
              @input="inputSearch"
            >
              <el-button
                slot="append"
                icon="el-icon-search"
                @click="handleSearch"
              />
            </el-input>
          </el-col>
        </el-row>
      </div>
      <el-tabs
        v-if="!updateCertificate"
        v-model="activeName"
        type="card"
        @tab-click="handleClickTab"
      >
        <el-tab-pane
          label="Penyusun Bersertifikat"
          name="penyusunBersertifikat"
        >
          <formulator-table
            :loading="loading"
            :list="list"
            @emailStatusFilter="onEmailStatusFilter"
            @lspFilter="onLspFilter"
            @membershipStatusFilter="onMembershipStatusFilter"
            @handleEditForm="handleEditForm($event)"
            @handleDelete="handleDelete($event)"
          />
        </el-tab-pane>
        <el-tab-pane
          label="Penyusun Tenaga Ahli"
          name="penyusunTidakBersertifikat"
        >
          <formulator-table
            :loading="loading"
            :list="list"
            @emailStatusFilter="onEmailStatusFilter"
            @lspFilter="onLspFilter"
            @membershipStatusFilter="onMembershipStatusFilter"
            @handleEditForm="handleEditForm($event)"
            @handleDelete="handleDelete($event)"
          />
        </el-tab-pane>
        <el-tab-pane
          label="Semua Penyusun"
          name="semua"
        >
          <formulator-table
            :loading="loading"
            :list="list"
            @emailStatusFilter="onEmailStatusFilter"
            @lspFilter="onLspFilter"
            @membershipStatusFilter="onMembershipStatusFilter"
            @handleEditForm="handleEditForm($event)"
            @handleDelete="handleDelete($event)"
          />
        </el-tab-pane>
      </el-tabs>
      <formulator-table
        v-else
        :loading="loading"
        :list="list"
        :certificate="true"
        @emailStatusFilter="onEmailStatusFilter"
        @lspFilter="onLspFilter"
        @membershipStatusFilter="onMembershipStatusFilter"
        @handleEditForm="handleEditForm($event)"
        @handleDelete="handleDelete($event)"
      />
      <pagination
        v-show="total > 0"
        :total="total"
        :page.sync="listQuery.page"
        :limit.sync="listQuery.limit"
        @pagination="handleFilter"
      />
      <div v-if="updateCertificate" style="text-align: right">
        <el-button type="danger" @click="cancelUpdateCertificate">
          Batal
        </el-button>
      </div>
    </el-card>
  </div>
</template>

<script>
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';
import Resource from '@/api/resource';
import FormulatorTable from '@/views/formulator/components/FormulatorTable.vue';
import Pagination from '@/components/Pagination';
const formulatorResource = new Resource('formulators');

export default {
  name: 'FormulatorList',
  components: {
    FormulatorTable,
    Pagination,
  },
  data() {
    return {
      list: [],
      loading: true,
      activeName: 'penyusunBersertifikat',
      listQuery: {
        page: 1,
        limit: 10,
        active: 'bersertifikat',
        search: null,
        email: this.$store.getters.user.email,
      },
      total: 0,
      timeoutId: null,
      updateCertificate: false,
    };
  },
  created() {
    this.getList();
  },
  methods: {
    checkPermission,
    checkRole,
    handleFilter() {
      this.getList();
    },
    handleClickTab(tab, event) {
      this.listQuery.page = 1;
      this.listQuery.limit = 10;
      this.listQuery.search = null;
      if (tab.name === 'penyusunBersertifikat') {
        this.listQuery.active = 'bersertifikat';
      } else if (tab.name === 'penyusunTidakBersertifikat') {
        this.listQuery.active = 'tidakBersertifikat';
      } else {
        this.listQuery.active = '';
      }
      this.getList();
    },
    async getList() {
      // console.log({ ok: this.$store.getters.user.email });
      this.loading = true;
      const { data, meta } = await formulatorResource.list(this.listQuery);
      this.list = data.map((x) => {
        x.membership_status = x.membership_status ? x.membership_status : '-';
        x.status = this.calculateStatus(x.date_start, x.date_end);
        x.cert_no = this.noCertificate(x.cert_no);
        return x;
      });
      this.total = meta.total;
      this.loading = false;
    },
    handleCreate() {
      this.$router.push({
        name: 'createFormulator',
        // eslint-disable-next-line object-curly-spacing
        params: { formulator: {} },
      });
    },
    handleUpdateCertificate() {
      this.updateCertificate = true;
      this.listQuery.active = '';
      this.listQuery.page = 1;
      this.listQuery.limit = 10;
      this.listQuery.search = null;
      this.getList();
    },
    cancelUpdateCertificate() {
      this.updateCertificate = false;
      this.activeName = 'penyusunBersertifikat';
      this.listQuery.active = 'bersertifikat';
      this.listQuery.search = null;
      this.getList();
    },
    handleEditForm(id) {
      this.$router.push({
        name: 'editFormulator',
        params: { id },
      });
    },
    handleDelete({ id, nama }) {
      this.$confirm(
        'apakah anda yakin akan menghapus ' + nama + '. ?',
        'Peringatan',
        {
          confirmButtonText: 'OK',
          cancelButtonText: 'Batal',
          type: 'warning',
        }
      )
        .then(() => {
          formulatorResource
            .destroy(id)
            .then((response) => {
              if (response.message === 'success') {
                this.$message({
                  type: 'success',
                  message: 'Hapus Selesai',
                });
                this.getList();
              } else {
                this.$message({
                  message: 'Data Penyusun Gagal Dihapus',
                  type: 'error',
                  duration: 5 * 1000,
                });
              }
            })
            .catch((error) => {
              console.log(error);
            });
        })
        .catch(() => {
          this.$message({
            type: 'info',
            message: 'Hapus Digagalkan',
          });
        });
    },
    async handleSearch() {
      this.listQuery.page = 1;
      this.listQuery.limit = 10;
      await this.getList();
      this.listQuery.search = null;
    },
    inputSearch(val) {
      if (this.timeoutId) {
        clearTimeout(this.timeoutId);
      }
      this.timeoutId = setTimeout(() => {
        this.listQuery.page = 1;
        this.listQuery.limit = 10;
        this.getList();
      }, 500);
    },
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
    noCertificate(no) {
      if (
        no === null ||
        no === undefined ||
        no === 'null' ||
        no === 'undefined'
      ) {
        return '-';
      } else {
        return no;
      }
    },
    onMembershipStatusFilter(val) {
      // console.log('membershipStatusIndex: ', val);
      this.listQuery.membershipStatus = val;
      this.handleFilter();
    },
    onLspFilter(val) {
      // console.log('membershipStatusIndex: ', val);
      this.listQuery.lspFilter = val;
      this.handleFilter();
    },
    onEmailStatusFilter(val) {
      this.listQuery.emailStatusFilter = val;
      this.handleFilter();
    },
  },
};
</script>
