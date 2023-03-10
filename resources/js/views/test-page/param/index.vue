<template>
  <div class="app-container" style="padding: 24px">
    <el-card>
      <div class="filter-container">
        <div>
          <h3>List Parameter untuk KBLI {{ business.kbli_code }} {{ business.value }}</h3>
        </div>
        <el-row type="flex" class="row-bg" justify="space-between">
          <el-col>
            <el-button
              v-if="isAdmin"
              class="filter-item"
              type="primary"
              icon="el-icon-plus"
              @click="handleCreate"
            >
              Tambah
            </el-button>
          </el-col>
          <el-col :span="6">
            <el-input v-model="listQuery.search" placeholder="Nama Parameter" @change="handleFilter">
              <el-button slot="append" icon="el-icon-search" @click="handleFilter" />
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
        <el-table-column align="left" label="Nama Parameter">
          <template slot-scope="scope">
            <span>{{ scope.row.param_name }}</span>
          </template>
        </el-table-column>
        <el-table-column align="left" label="Kondisi" width="200">
          <template slot-scope="scope">
            <span>{{ scope.row.condition_string }}</span>
          </template>
        </el-table-column>
        <el-table-column align="left" label="Unit" width="100">
          <template slot-scope="scope">
            <span>{{ scope.row.unit_name }}</span>
          </template>
        </el-table-column>
        <el-table-column align="left" label="Jenis Dokumen" width="100">
          <template slot-scope="scope">
            <span>{{ scope.row.doc_req }}</span>
          </template>
        </el-table-column>
        <el-table-column align="left" label="Tipe AMDAL" width="100">
          <template slot-scope="scope">
            <span>{{ scope.row.amdal_type }}</span>
          </template>
        </el-table-column>
        <el-table-column align="left" label="Tingkat Risiko" width="250">
          <template slot-scope="scope">
            <span>{{ scope.row.risk_level }}</span>
          </template>
        </el-table-column>
        <el-table-column v-if="isAdmin" align="right" label="Aksi" width="150">
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
                @click="handleDelete(scope.row)"
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
      <create-business-env-param
        :show="showCreate"
        @handleClose="handleCloseCreate"
        @handleReloadList="handleReloadList"
      />
      <edit-business-env-param
        :key="editKey"
        :show="showEdit"
        :id-business-env-param="selectedId"
        @handleClose="handleCloseEdit"
        @handleReloadList="handleReloadList"
        @handleDelete="handleDeleteParam"
      />
    </el-card>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import Resource from '@/api/resource';
import Pagination from '@/components/Pagination';
import ConfirmationDialog from '@/components/ConfirmationDialog';
import CreateBusinessEnvParam from './Create.vue';
import EditBusinessEnvParam from './Edit.vue';
const businessResource = new Resource('business');
const bepResource = new Resource('business-env-params');

export default {
  name: 'BusinessEnvList',
  components: { Pagination, ConfirmationDialog, CreateBusinessEnvParam, EditBusinessEnvParam },
  data() {
    return {
      idBusiness: 0,
      show: false,
      showConfirmation: false,
      showCreate: false,
      showEdit: false,
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
      business: {},
      filtered: [],
      total: 0,
      listQuery: {
        page: 1,
        limit: 10,
      },
      selectedId: 0,
      confirmMessage: '',
      warningMessage: '',
      editKey: 1,
    };
  },
  computed: {
    ...mapGetters({
      'userInfo': 'user',
      'userId': 'userId',
    }),
    isAdmin() {
      return this.userInfo.roles.some(r => this.adminRoles.includes(r));
    },
  },
  async created() {
    this.idBusiness = this.$route.params && this.$route.params.id;
    // this.getUserInfo();
    this.getBusiness();
    this.getFilteredData(this.listQuery);
  },
  methods: {
    // async getUserInfo() {
    //   this.userInfo = await this.$store.dispatch('user/getInfo');
    // },
    async getFilteredData(query) {
      query['business_id'] = this.idBusiness;
      this.loading = true;
      const { data, total } = await bepResource.list(query);
      this.filtered = data;
      this.total = total;
      this.loading = false;
    },
    async getBusiness() {
      const kbli = await businessResource.get(this.idBusiness);
      this.business = kbli;
    },
    handleFilter() {
      this.getFilteredData(this.listQuery);
    },
    handleCreate() {
      this.showCreate = true;
    },
    handleEdit(id) {
      this.selectedId = id;
      this.showEdit = true;
      this.editKey = this.editKey + 1;
    },
    handleDelete(param) {
      // show confirmation popup
      this.showConfirmation = true;
      this.selectedId = param.id;
      this.confirmMessage = 'ingin menghapus parameter KBLI: ' + param.param_name;
      this.warningMessage = 'Menghapus parameter KBLI akan berdampak luas pada sistem, khususnya terkait Kegiatan.';
    },
    handleDeleteParam(param) {
      // delete from EDIT form
      this.showEdit = false;
      this.showConfirmation = true;
      this.selectedId = param.id;
      this.confirmMessage = 'ingin menghapus parameter KBLI: ' + param.param_name;
      this.warningMessage = 'Menghapus parameter KBLI akan berdampak luas pada sistem, khususnya terkait Kegiatan.';
    },
    handleSubmitConfirmation(id) {
      // delete
      bepResource
        .destroy(id)
        .then((response) => {
          this.$message({
            message: 'Parameter KBLI berhasil dihapus',
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
    handleCloseCreate() {
      this.showCreate = false;
    },
    handleCloseEdit() {
      this.showEdit = false;
    },
    handleReloadList() {
      this.getFilteredData({
        page: 1,
        limit: 10,
      });
    },
  },
};
</script>

<style lang="scss" scoped>
.entity-block {
  display: inline-block;

  .name, .description {
    display: block;
    margin-left: 50px;
    padding: 2px 0;
  }
  .action {
    display:  inline-block;
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
    &:hover, &:focus {
      color: #999;
    }
  }
}
</style>
