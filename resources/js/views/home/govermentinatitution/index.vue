<template>
  <section id="lpjp-table-section" class="lpjp-table-section section">
    <div class="container">
      <h2 class="section__title announce__title lpjp-table-section__title">
        Daftar Instansi Pemerintah
      </h2>
      <div class="list-of-projects">
        <el-input
          v-model="listQuery.search"
          suffix-icon="el-icon search"
          placeholder="Pencarian institusi pemerintah..."
          style="width:30%; margin-bottom:20px;"
          @input="inputSearch"
        >
          <el-button
            slot="append"
            icon="el-icon-search"
            @click="handleSearch"
          />
        </el-input>
        <div
          style="
                margin-bottom: 1em;
                display: flex;
                justify-content: space-between;
                align-items: center;
            "
        >
          <el-table
            v-loading="loading"
            :data="list"
            fit
            highlight-current-row
            :header-cell-style="{ background: '#3AB06F', color: 'white' }"
          >
            <el-table-column align="center" label="No" width="50px">
              <template slot-scope="scope">
                <span>{{ scope.$index + 1 }}</span>
              </template>
            </el-table-column>

            <!-- <el-table-column
              align="center"
              label="Jenis Instansi"
              sortable
              prop="institution_type"
            /> -->
            <el-table-column
              align="center"
              label="Nama Instansi"
              sortable
              prop="name"
            />
            <!-- <el-table-column
              align="center"
              label="Email Instansi"
              sortable
              prop="email"
            /> -->

            <el-table-column
              align="center"
              label="Alamat kantor"
              sortable
              prop="office"
            />

            <el-table-column
              align="center"
              label="Alamat Website"
              sortable
              prop="website"
            />
          </el-table>
        </div>
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
// import GovernmentInstitutionTable from '@/views/government-institution/components/GovernmentInstitutionTable.vue';
import Pagination from '@/components/Pagination';
import Resource from '@/api/resource';
const provinceResource = new Resource('provinces');
const districtResource = new Resource('districts');
const governmentInstitutionResource = new Resource('government-institution');

export default {
  name: 'GovernmentInstitution',
  components: {
    Pagination,
  },
  data() {
    return {
      list: [],
      loading: false,
      loadingSubmit: false,
      listQuery: {
        page: 1,
        limit: 10,
        search: null,
      },
      timeoutId: null,
      total: 0,
      dialogForm: {
        show: false,
        type: '',
        title: '',
      },
      provinces: [],
      districts: [],
      filteredDistricts: [],
      currentData: {},
      errors: {
        institutionType: null,
        id_province: null,
        id_district: null,
        name: null,
        email: null,
        office: null,
        website: null,
      },
      institutionType: [
        {
          name: 'Kementerian',
        },
        {
          name: 'Lembaga',
        },
        {
          name: 'Pemerintah Provinsi',
        },
        {
          name: 'Pemerintah Kabupaten/Kota',
        },
        {
          name: 'Lainnya',
        },
      ],
    };
  },
  created() {
    this.getData();
    this.getProvince();
    this.getDistrict();
  },
  methods: {
    checkPermission,
    checkRole,
    handleFilter() {
      this.getData();
    },
    async getData() {
      this.loading = true;
      const { data, total } = await governmentInstitutionResource.list(
        this.listQuery
      );
      this.list = data;
      this.total = total;
      this.loading = false;
    },
    async getProvince() {
      const provinces = await provinceResource.list({});
      this.provinces = provinces.data;
    },
    async getDistrict() {
      const districts = await districtResource.list({});
      this.districts = districts.data;
    },
    checkSubmit() {
      this.resetErrors();

      let errors = 0;

      if (!this.currentData.institutionType) {
        errors++;
        this.errors.institutionType = true;
      }

      if (!this.currentData.name) {
        errors++;
        this.errors.name = true;
      }

      // if (!this.currentData.office) {
      //   errors++;
      //   this.errors.office = true;
      // }

      // if (!this.currentData.website) {
      //   errors++;
      //   this.errors.website = true;
      // }

      if (!this.currentData.email) {
        errors++;
        this.errors.email = 'Email Wajib Diisi';
      } else {
        if (!this.validateEmail(this.currentData.email)) {
          errors++;
          this.errors.email = 'Email Tidak Valid';
        }
      }

      if (
        this.currentData.institutionType === 'Pemerintah Provinsi' ||
        this.currentData.institutionType === 'Pemerintah Kabupaten/Kota'
      ) {
        if (!this.currentData.id_province) {
          errors++;
          this.errors.id_province = true;
        }
      }

      if (this.currentData.institutionType === 'Pemerintah Kabupaten/Kota') {
        if (!this.currentData.id_district) {
          errors++;
          this.errors.id_district = true;
        }
      }

      if (errors === 0) {
        this.handleSubmit();
      }
    },
    async handleSubmit() {
      this.loadingSubmit = true;
      await governmentInstitutionResource.store({
        ...this.currentData,
        type: this.dialogForm.type,
      });
      this.$message({
        message: 'Data berhasil disimpan',
        type: 'success',
        duration: 5 * 1000,
      });
      this.getData();
      this.dialogForm.show = false;
      this.loadingSubmit = false;
    },
    handleShowForm(act) {
      this.resetErrors();

      if (act === 'create') {
        this.currentData = {};
        this.filteredDistricts = [];
        this.dialogForm.title = 'Tambah Instansi Pemerintah';
      } else {
        this.dialogForm.title = 'Ubah Instansi Pemerintah';
        if (this.currentData.institutionType === 'Pemerintah Kabupaten/Kota') {
          this.setDistricts(this.currentData.id_province);
        }
      }

      this.dialogForm.type = act;
      this.dialogForm.show = true;
    },
    setDistricts(id) {
      const filteredDistricts = this.districts.filter((x) => x.id_prov === id);
      this.filteredDistricts = filteredDistricts;
    },
    handleEdit({ id }) {
      const institution = this.list.find((x) => x.id === id);
      this.currentData = {
        id: institution.id,
        name: institution.name,
        institutionType: institution.institution_type,
        id_province: institution.id_province,
        id_district: institution.id_district,
        email: institution.email,
        old_email: institution.email,
        office: institution.office,
        website: institution.website,
      };
      this.handleShowForm('update');
    },
    handleDelete({ id, name }) {
      this.$confirm(
        'apakah anda yakin akan menghapus ' + name + '. ?',
        'Peringatan',
        {
          confirmButtonText: 'OK',
          cancelButtonText: 'Batal',
          type: 'warning',
        }
      )
        .then(() => {
          governmentInstitutionResource
            .destroy(id)
            .then((response) => {
              this.$message({
                type: 'success',
                message: 'Hapus Selesai',
              });
              this.getData();
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
    resetErrors() {
      this.errors = {
        institutionType: null,
        id_province: null,
        id_district: null,
        name: null,
        email: null,
        office: null,
        website: null,
      };
    },
    validateEmail(mail) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(mail);
    },
    async handleSearch() {
      this.listQuery.page = 1;
      this.listQuery.limit = 10;
      await this.getData();
      this.listQuery.search = null;
    },
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
.pagination-container {
  background: #041608;
  padding: 32px 16px;
}
</style>
