<!-- eslint-disable vue/html-indent -->
<template>
  <section id="lpjp-table-section" class="lpjp-table-section section">
    <div class="container">
      <h2 class="section__title announce__title lpjp-table-section__title">
        Daftar Instansi LH Yang Telah Teregistrasi Di Amdalnet
      </h2>
      <div style="margin-bottom:15px">
        <el-row :gutter="32">
          <el-col :sm="24" :md="12">
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
      <div class="list-of-projects">
        <el-table
            v-loading="loading"
            :data="list"
            fit
            highlight-current-row
            :header-cell-style="{ background: '#3AB06F', color: 'white' }"
          >
            <el-table-column
            type="index"
            width="50"
            label="No"
            />
            <el-table-column align="center" label="Instansi LH" sortable prop="authority" />
            <el-table-column align="center" label="Jumlah Validator" sortable prop="count" />
        </el-table>
        <div class="block" style="text-align: right">
          <pagination
            v-if="total > 0"
            :auto-scroll="false"
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
import Pagination from '@/components/Pagination';
import Resource from '@/api/resource';
const LandingTukResource = new Resource('tuk');

export default {
  name: 'Employee',
  components: {
    Pagination,
  },
  data() {
    return {
      list: [],
      loading: false,
      listQuery: {
        page: 1,
        limit: 10,
        type: 'list',
        search: null,
      },
      total: 0,
      timeoutId: null,
    };
  },
  created() {
    this.getData();
  },
  methods: {
    checkPermission,
    checkRole,
    handleFilter() {
      this.getData();
    },
    async getData() {
      this.loading = true;
      const { data, total } = await LandingTukResource.list(this.listQuery);
      // this.list = data.map((x) => {
      //   let team = '-';
      //   if (x.authority !== null) {
      //     team = this.tukName(
      //       x.authority
      //     );
      //   }
      //   x.team = team;
      //   return x;
      // });
      this.list = data;
      this.total = total;
      this.loading = false;
    },
    async handleSearch() {
      this.listQuery.page = 1;
      this.listQuery.limit = 10;
      await this.getData();
      this.listQuery.search = null;
    },
    // tukName(data) {
    //   return `Tim Uji Kelayakan ${data}`;
    // },
    inputSearch(val) {
      if (this.timeoutId) {
        clearTimeout(this.timeoutId);
      }
      this.timeoutId = setTimeout(() => {
        this.listQuery.page = 1;
        this.listQuery.limit = 10;
        this.getData();
      }, 500);
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
