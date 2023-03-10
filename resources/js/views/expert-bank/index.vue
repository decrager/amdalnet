<template>
  <div class="app-container" style="padding: 24px">
    <el-card>
      <div class="filter-container">
        <el-button
          v-if="checkPermission(['manage expert']) || checkRole(['admin'])"
          class="filter-item"
          type="primary"
          icon="el-icon-plus"
          @click="handleCreate"
        >
          {{ 'Tambah Bank Ahli' }}
        </el-button>
        <el-row :gutter="32">
          <el-col :sm="24" :md="10">
            <el-input
              v-model="listQuery.search"
              suffix-icon="el-icon search"
              placeholder="Pencarian anggota..."
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
      <expert-bank-table
        :loading="loading"
        :list="list"
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
    </el-card>
  </div>
</template>

<script>
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';
import Resource from '@/api/resource';
import ExpertBankTable from '@/views/expert-bank/components/ExpertBankTable.vue';
import Pagination from '@/components/Pagination';
const expertBankResource = new Resource('expert-banks');

export default {
  name: 'FormulatorList',
  components: {
    ExpertBankTable,
    Pagination,
  },
  data() {
    return {
      list: [],
      loading: true,
      listQuery: {
        page: 1,
        limit: 10,
        search: null,
      },
      total: 0,
      timeoutId: null,
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
    async getList() {
      this.loading = true;
      const { data, meta } = await expertBankResource.list(this.listQuery);
      this.list = data.map((x) => {
        x.status = this.calculateStatus(x.status);
        return x;
      });
      this.total = meta.total;
      this.loading = false;
    },
    handleCreate() {
      this.$router.push({
        name: 'createExpertBank',
        // eslint-disable-next-line object-curly-spacing
        params: { expertBank: {} },
      });
    },
    handleEditForm(id) {
      const currentExpertBank = this.list.find((element) => element.id === id);
      console.log(currentExpertBank);
      this.$router.push({
        name: 'editExpertBank',
        params: { id, expertBank: currentExpertBank },
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
          expertBankResource
            .destroy(id)
            .then((response) => {
              this.$message({
                type: 'success',
                message: 'Hapus Selesai',
              });
              this.getList();
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
    calculateStatus(status) {
      if (status) {
        return 'Aktif';
      }

      return 'Tidak Aktif';
    },
  },
};
</script>
