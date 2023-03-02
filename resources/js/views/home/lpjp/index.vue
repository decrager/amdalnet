<template>
  <section id="lpjp-table-section" class="lpjp-table-section section">
    <div class="container">
      <h2 class="section__title announce__title lpjp-table-section__title">
        Daftar Lembaga Penyedia Jasa Penyusun (LPJP)
      </h2>
      <div class="list-of-projects">
        <div
          style="
            margin-bottom: 1em;
            display: flex;
            justify-content: space-between;
            align-items: center;
          "
        >
          <el-radio-group v-model="status" @change="onChangeStatus">
            <el-radio-button label="AKTIF" />
            <el-radio-button label="TIDAK AKTIF" />
          </el-radio-group>
          <el-input
            v-model="query.search"
            placeholder="Pencarian"
            style="width: 26%"
            @input="inputSearch"
          />
        </div>
        <el-table
          v-loading="loading"
          :data="list"
          stripe
          style="width: 100%; background-color: #041608 !important"
        >
          <el-table-column
            type="index"
            :index="indexMethod"
            width="50"
            label="No"
          />
          <el-table-column prop="name" label="Nama LPJP" />
          <el-table-column prop="reg_no" label="Nomor Registrasi" />
          <el-table-column prop="address" label="Alamat" />

          <!-- <el-table-column label="Sertifikat" align="center">
            <template slot-scope="scope">
              <el-button
                type="primary"
                style="color: white !important"
                @click="download(scope.row.cert_file)"
              >
                Unduh
              </el-button>
            </template>
          </el-table-column> -->

        </el-table>
        <div class="block" style="text-align: right">
          <pagination
            v-if="total > 0"
            :auto-scroll="false"
            :total="total"
            :page.sync="query.page"
            :limit.sync="query.limit"
            @pagination="getList"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<script>
import Resource from '@/api/resource';
import Pagination from '@/components/Pagination';
const lpjpResource = new Resource('lpjp');

export default {
  name: 'LPJP',
  components: {
    Pagination,
  },
  data() {
    return {
      loading: false,
      list: [],
      options: [
        {
          value: 5,
          label: 5,
        },
        {
          value: 25,
          label: 25,
        },
        {
          value: 50,
          label: 50,
        },
        {
          value: 100,
          label: 100,
        },
      ],
      search: '',
      id: 0,
      query: {
        page: 1,
        limit: 10,
        type: 'list',
        search: null,
      },
      total: 0,
      status: 'AKTIF',
      timeoutId: null,
    };
  },
  created() {
    this.getList();
  },
  methods: {
    async getList() {
      this.loading = true;
      const { data, meta } = await lpjpResource.list({
        ...this.query,
        status: this.status === 'AKTIF' ? '1' : '0',
      });
      this.list = data;
      this.total = meta.total;
      this.loading = false;
    },
    onChangeStatus() {
      this.total = 0;
      this.query = {
        page: 1,
        limit: 10,
        type: 'list',
        search: null,
      };
      this.getList();
    },
    inputSearch(val) {
      if (this.timeoutId) {
        clearTimeout(this.timeoutId);
      }
      this.timeoutId = setTimeout(() => {
        this.query.page = 1;
        this.query.limit = 10;
        this.getList();
      }, 500);
    },
    handleSearch() {
      this.getList(this.keyword, null);
    },
    indexMethod(index) {
      return (this.query.page - 1) * this.query.limit + index + 1;
    },
    handleFilter() {},
    handleSort() {
      if (this.sort === 'ASC') {
        this.sort = 'DESC';
      } else {
        this.sort = 'ASC';
      }
      this.getList(null, this.sort);
    },
    handleLimit() {
      this.getList(null, null, this.limit);
    },
    download(url) {
      if (url) {
        const a = document.createElement('a');
        a.href = url;
        a.setAttribute('download', '');
        a.click();
      }

      return false;
    },
  },
};
</script>

<style>
#lpjp-table-section .el-radio-button .el-radio-button__inner {
  background-color: #041608;
  border-color: #041608;
  font-weight: bold;
  color: #fff;
}

#lpjp-table-section
  .el-radio-button__orig-radio:checked
  + .el-radio-button__inner {
  background-color: #f6993f;
  border-color: #f6993f;
}

#lpjp-table-section .el-table th.el-table__cell {
  background-color: #041608 !important;
}
#lpjp-table-section .el-table td.el-table__cell {
  background-color: #041608 !important;
  border-color: #333 !important;
  color: #fff;
  padding: 0.5em;
}
.pagination-container {
  background: #041608 !important;
  padding: 32px 16px !important;
}
</style>

