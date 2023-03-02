<!-- eslint-disable vue/html-indent -->
<template>
  <section id="lpjp-table-section" class="lpjp-table-section section">
    <div class="container">
      <h2 class="section__title announce__title lpjp-table-section__title">
        Daftar Penyusun
      </h2>
      <div class="list-of-projects">
        <div style="width: 100%;">
          <el-input
            v-model="listQuery.search"
            suffix-icon="el-icon search"
            style="width: 30%;  margin-left: 70%; margin-bottom: 10px;"
            placeholder="Pencarian..."
            @input="inputSearch"
          >
            <el-button
              slot="append"
              icon="el-icon-search"
              @click="handleSearch"
            />
          </el-input>
        </div>
        <el-tabs
          v-if="!updateCertificate"
          v-model="activeName"
          style="border: #3AB06F;
          border-color:#f6993f;"
          type="card"
          @tab-click="handleClickTab"
        >
          <el-tab-pane label="PENYUSUN BERSERTIFIKAT" name="penyusunBersertifikat">
            <formulator-table
              :loading="loading"
              :list="list"
              @handleEditForm="handleEditForm($event)"
              @handleDelete="handleDelete($event)"
            />
          </el-tab-pane>
          <el-tab-pane label="TENAGA AHLI" name="penyusunTidakBersertifikat">
            <formulator-table
              :loading="loading"
              :list="list"
              @handleEditForm="handleEditForm($event)"
              @handleDelete="handleDelete($event)"
            />
          </el-tab-pane>
          <!-- <el-tab-pane
            label="Penyusun Bersertifikat"
            name="penyusunBersertifikat"
          >
            <formulator-table
              :loading="loading"
              :list="list"
              @handleEditForm="handleEditForm($event)"
              @handleDelete="handleDelete($event)"
            />
          </el-tab-pane> -->
        </el-tabs>
        <el-table
          v-loading="loading"
          :data="list"
          fit
          highlight-current-row
          :header-cell-style="{ background: '#3AB06F', color: 'white' }"
        >
          <el-table-column align="center" label="Nama" prop="name" sortable />
          <el-table-column
            align="center"
            label="Sertifikasi"
            prop="membership_status"
            sortable
          />
          <el-table-column
            align="center"
            label="Status Registrasi Amdalnet"
            prop="user"
            sortable
          />
        </el-table>
        <div class="block" style="text-align: right">
          <pagination
            v-show="total > 0"
            :total="total"
            :page.sync="listQuery.page"
            :limit.sync="listQuery.limit"
            @pagination="handleFilter"
          />
        </div>
      </div>
    </div>
   </section>
</template>

<script>
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';
import Resource from '@/api/resource';
import Pagination from '@/components/Pagination';
const formulatorResource = new Resource('formulators');

export default {
  name: 'FormulatorList',
  components: {
    Pagination,
  },
  data() {
    return {
      list: [],
      loading: true,
      activeName: 'penyusunAktif',
      listQuery: {
        page: 1,
        limit: 10,
        active: 'true',
        search: null,
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
      if (tab.name === 'penyusunAktif') {
        this.listQuery.active = 'true';
      } else if (tab.name === 'penyusunBersertifikat') {
        this.listQuery.active = 'bersertifikat';
      } else if (tab.name === 'penyusunTidakBersertifikat') {
        this.listQuery.active = 'tidakBersertifikat';
      } else {
        this.listQuery.active = '';
      }
      this.getList();
    },
    async getList() {
      this.loading = true;
      console.log({ data: this.listQuery });
      const { data, meta } = await formulatorResource.list(this.listQuery);
      this.list = data.map((x) => {
        x.membership_status = x.membership_status ? x.membership_status : '-';
        x.status = this.calculateStatus(x.date_start, x.date_end);
        x.cert_no = this.noCertificate(x.cert_no);
        x.user = x.user === null || x.user.active === 0 ? 'Tidak Aktif' : 'Aktif';

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
      this.activeName = 'penyusunAktif';
      this.listQuery.active = 'true';
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
.pagination-container {
  background: #041608;
  padding: 32px 16px;
}
</style>
