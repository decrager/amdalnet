<template>
  <div class="app-container" style="padding: 24px">
    <el-card>
      <div class="filter-container">
        <el-row type="flex" class="row-bg" justify="space-between">
          <el-col>
            <el-button
              v-if="checkPermission(['manage params']) || checkRole(['admin'])"
              class="filter-item"
              type="primary"
              icon="el-icon-plus"
              @click="handleCreateVue()"
            >
              Tambah Gibran
            </el-button>
          </el-col>
          <el-col :span="6">
            <!-- <el-input v-model="listQuery.searchKbliCode" placeholder="No. KBLI" @change="handleFilter">
              <el-button slot="append" icon="el-icon-search" @click="handleFilter" />
            </el-input> -->
            <el-input
              v-model="listQuery.searchField"
              placeholder="Nama KBLI"
              @change="handleFilter"
            >
              <el-button
                slot="append"
                icon="el-icon-search"
                @click="handleFilter"
              />
            </el-input>
          </el-col>
        </el-row>
      </div>
      <el-table
        v-loading="loading"
        :data="filtered"
        fit
        highlight-current-row
        :header-cell-style="{ background: '#216221', color: 'white' }"
        style="width: 100%"
      >
        <el-table-column align="left" label="No. KBLI" width="100">
          <template slot-scope="scope">
            <span>{{ scope.row.kbli_code_value }}</span>
          </template>
        </el-table-column>
        <el-table-column align="left" label="Sector" width="200">
          <template slot-scope="scope">
            <span>{{ scope.row.sector_value }}</span>
          </template>
        </el-table-column>
        <el-table-column align="left" label="Nama KBLI">
          <template slot-scope="scope">
            <span>{{ scope.row.field_value }}</span>
          </template>
        </el-table-column>
        <el-table-column align="left" label="Parameter" width="100">
          <template slot-scope="scope">
            <span>
              <el-button
                type="text"
                href="#"
                icon="el-icon-view"
                @click="handleDetail(scope.row.id)"
              >
                Detail
              </el-button>
            </span>
          </template>
        </el-table-column>
        <el-table-column
          v-if="checkPermission(['manage tuk management']) || checkRole(['admin'])"
          align="right"
          label="Aksi"
          width="150"
        >
          <template slot-scope="scope">
            <span style="margin-left: 10px">
              <el-button
                type="success"
                size="mini"
                href="#"
                icon="el-icon-edit"
                @click="handleEdit(scope.row.id)"
              />
              <el-button
                class="pull-right"
                type="danger"
                size="mini"
                href="#"
                icon="el-icon-delete"
                @click="
                  handleDelete(
                    scope.row.id,
                    scope.row.kbli_code_value,
                    scope.row.sector_value,
                    scope.row.field_value
                  )
                "
              />
            </span>
          </template>
        </el-table-column>
      </el-table>
      <pagination
        v-show="total > 0"
        :total="total"
        :page.sync="listQuery.page"
        :limit.sync="listQuery.limit"
        @pagination="handleFilter"
      />
      <confirmation-dialog
        :show="showConfirmation"
        :id-object="selectedId"
        :message="confirmMessage"
        :warning-message="warningMessage"
        @handleSubmitConfirmation="handleSubmitConfirmation"
        @handleCloseConfirmation="handleCloseConfirmation"
      />
      <create-business
        :show="showCreateBusiness"
        @handleClose="handleCloseCreateBusiness"
      />
      <edit-business
        :key="editBusinessKey"
        :show="showEditBusiness"
        :id-business="selectedId"
        @handleClose="handleCloseEditBusiness"
        @handleDelete="handleDeleteBusiness"
      />
    </el-card>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import checkPermission from '@/utils/permission';
import checkRole from '@/utils/role';
import Resource from '@/api/resource';
import Pagination from '@/components/Pagination';
import ConfirmationDialog from '@/components/ConfirmationDialog';
import CreateBusiness from './Create.vue';
import EditBusiness from './Edit.vue';
const businessResource = new Resource('business');

export default {
  name: 'BusinessList',
  components: { Pagination, ConfirmationDialog, CreateBusiness, EditBusiness },
  data() {
    return {
      show: false,
      showConfirmation: false,
      showCreateBusiness: false,
      showEditBusiness: false,
      loading: false,
      // userInfo: {
      //   roles: [],
      // },
      adminRoles: [
        'admin',
        'admin-standard',
        // 'admin-system',
        // 'admin-regional',
        // 'admin-central',
      ],
      filtered: [],
      total: 0,
      listQuery: {
        page: 1,
        limit: 10,
      },
      selectedId: 0,
      confirmMessage: '',
      warningMessage: '',
      editBusinessKey: 1,
    };
  },
  computed: {
    ...mapGetters({
      userInfo: 'user',
      userId: 'userId',
    }),
    isAdmin() {
      return this.userInfo.roles.some((r) => this.adminRoles.includes(r));
    },
  },
  async created() {
    // this.getUserInfo();
    this.getFilteredData(this.listQuery);
  },
  methods: {
    // async getUserInfo() {
    //   this.userInfo = await this.$store.dispatch('user/getInfo');
    // },
    checkPermission,
    checkRole,
    async getFilteredData(query) {
      this.loading = true;
      const { data, total } = await businessResource.list(query);
      this.filtered = data;
      this.total = total;
      this.loading = false;
    },
    handleFilter() {
      this.getFilteredData(this.listQuery);
    },
    handleDetail(id) {
      this.$router.push({ path: '/business/' + id });
    },
    handleCreate() {
      this.showCreateBusiness = true;
    },
    handleCreateVue() {
      this.$router.push({
        path: '/create-test',
      });
    },
    handleEdit(id) {
      this.selectedId = id;
      this.showEditBusiness = true;
      this.editBusinessKey = this.editBusinessKey + 1;
    },
    handleDelete(id, code, sector, field) {
      // delete from LIST
      this.showConfirmation = true;
      this.selectedId = id;
      this.confirmMessage =
        'ingin menghapus KBLI: ' + code + ' ' + sector + ' ' + field;
      this.warningMessage =
        'Menghapus KBLI akan berdampak luas pada sistem, khususnya terkait Kegiatan.';
    },
    handleDeleteBusiness(business) {
      // delete from EDIT form
      this.showEditBusiness = false;
      this.showConfirmation = true;
      this.selectedId = business.id;
      this.confirmMessage =
        'ingin menghapus KBLI: ' +
        business.kbli_code +
        ' ' +
        business.sector +
        ' ' +
        business.value;
      this.warningMessage =
        'Menghapus KBLI akan berdampak luas pada sistem, khususnya terkait Kegiatan.';
    },
    handleSubmitConfirmation(id) {
      // delete
      businessResource
        .destroy(id)
        .then((response) => {
          this.$message({
            message: 'KBLI berhasil dihapus',
            type: 'success',
            duration: 5 * 1000,
          });
          this.showConfirmation = false;
          this.getFilteredData(this.listQuery);
        })
        .catch((error) => {
          console.log(error);
        });
    },
    handleCloseConfirmation() {
      this.showConfirmation = false;
    },
    handleCloseCreateBusiness() {
      this.showCreateBusiness = false;
      this.getFilteredData(this.listQuery);
    },
    handleCloseEditBusiness() {
      this.showEditBusiness = false;
      this.getFilteredData(this.listQuery);
    },
  },
};
</script>

<style lang="scss" scoped>
.entity-block {
  display: inline-block;

  .name,
  .description {
    display: block;
    margin-left: 50px;
    padding: 2px 0;
  }
  .action {
    display: inline-block;
    padding-right: 5%;
  }
  img {
    width: 40px;
    height: 40px;
    float: left;
  }
  :after {
    clear: both;
  }
  .img-circle {
    border-radius: 50%;
    border: 2px solid #d2d6de;
    padding: 2px;
  }
  span {
    font-weight: 500;
    font-size: 12px;
  }
}
.post {
  font-size: 14px;
  margin-bottom: 15px;
  padding-right: 20px;
  padding-left: 7%;
  color: #666;
  .image {
    width: 100%;
  }
  .user-images {
    padding-top: 20px;
  }
  .title {
    display: block;
    padding: 2px 0;
  }
}
.list-inline {
  padding-left: 0;
  margin-left: -5px;
  list-style: none;
  li {
    display: inline-block;
    padding-right: 5px;
    padding-left: 5px;
    font-size: 13px;
  }
  .link-black {
    &:hover,
    &:focus {
      color: #999;
    }
  }
}
</style>

