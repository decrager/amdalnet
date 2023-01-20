<template>
  <div v-if="isProjectUklUpl" class="app-container" style="padding: 24px">
    <el-card>
      <workflow-ukl />
      <h2>Formulir UKL UPL</h2>
      <span>
        <el-button
          v-if="isFormulator && isPerbaikan"
          class="pull-right"
          type="danger"
          size="small"
          icon="el-icon-close"
          style="margin-left: .5rem;"
          @click="handleNotSave()"
        >
          Tidak
        </el-button>
        <el-button
          v-if="isFormulator && !isPerbaikan"
          class="pull-right"
          type="success"
          size="small"
          icon="el-icon-check"
          :disabled="!isSubmitEnabled"
          @click="handleSaveForm()"
        >
          Simpan & Lanjutkan
        </el-button>
        <el-button
          v-if="isFormulator && isPerbaikan"
          class="pull-right"
          type="success"
          size="small"
          icon="el-icon-check"
          @click="handleSaveForm(isPerbaikan)"
        >
          Ya
        </el-button>
        <span v-if="isPerbaikan" class="pull-right" style="font-weight: bold; margin-top: .5rem;">Apakah Ada Perbaikan pada Formulir UKL UPL ini?</span>
      </span>
      <el-collapse :key="accordionKey" v-model="activeName" :accordion="true">
        <el-collapse-item name="1" title="IDENTIFIKASI KOMPONEN KEGIATAN DAN KOMPONEN LINGKUNGAN">
          <!-- <pelingkupan
            v-if="activeName === '1'"
            @handleReloadVsaList="handleReloadVsaList"
          /> -->
          <modul-pelingkupan v-if="activeName === '1'" />
        </el-collapse-item>
        <el-collapse-item name="2" title="MATRIKS IDENTIFIKASI DAMPAK">
          <matriks-identifikasi-dampak-table v-if="activeName === '2'" />
        </el-collapse-item>
        <el-collapse-item name="3" title="JENIS DAN BESARAN DAMPAK">
          <jenis-besaran-dampak-table
            v-if="activeName === '3'"
            :perbaikan="isPerbaikan"
            @handleEnableSimpanLanjutkan="handleEnableSimpanLanjutkan"
          />
        </el-collapse-item>
      </el-collapse>
    </el-card>
  </div>
</template>

<script>
// import Pelingkupan from '@/views/amdal/components/Pelingkupan.vue';
import ModulPelingkupan from '@/views/amdal/pelingkupan/index.vue';
import MatriksIdentifikasiDampakTable from '@/views/amdal/components/tables/MatriksIdentifikasiDampakTable.vue';
import JenisBesaranDampakTable from './components/tables/JenisBesaranDampakTable.vue';
import WorkflowUkl from '@/components/WorkflowUkl';
import Resource from '@/api/resource';
const projectResource = new Resource('projects');
const saveStatus = new Resource('save-status');

export default {
  name: 'FormulirUklUpl',
  components: {
    // Pelingkupan,
    ModulPelingkupan,
    MatriksIdentifikasiDampakTable,
    JenisBesaranDampakTable,
    WorkflowUkl,
  },
  props: {
    data: {
      type: Object,
      default: () => {},
    },
  },
  data() {
    return {
      accordionKey: 1,
      idProject: 0,
      isSubmitEnabled: false,
      scopingActive: true,
      matriksActive: false,
      dampakPotensialActive: false,
      dampakPentingHipotetikActive: false,
      metodeStudiActive: false,
      activeName: '1',
      isProjectUklUpl: false,
    };
  },
  computed: {
    isFormulator() {
      return this.$store.getters.roles.includes('formulator');
    },
    isPerbaikan() {
      return this.$route.query.perbaikan;
    },
  },
  mounted() {
    this.setProjectId();
    this.$store.dispatch('getStep', 3);
    this.checkifUklUpl();
    this.getMarking();
    this.data;
  },
  async beforeCreate() {
    if (this.$route.query.refresh) { // Sementara sampai ketemu solusi page blank setelah access dari workspace
      await this.$router.push({
        path: `/uklupl/${this.$route.params.id}/formulir`,
        query: {
          perbaikan: true,
        },
      });
      location.reload();
    }
  },
  methods: {
    setProjectId(){
      const id = this.$route.params && this.$route.params.id;
      this.idProject = id;
    },
    async getMarking() {
      await this.$store.dispatch('getMarking', this.idProject);
    },
    async checkifUklUpl() {
      const project = await projectResource.get(this.idProject);
      if (project.required_doc === 'AMDAL') {
        this.$message({
          message: 'Kegiatan membutuhkan dokumen AMDAL',
          type: 'error',
          duration: 5 * 1000,
        });
        this.$router.push({
          name: 'FormulirAmdal',
          params: this.idProject,
        });
      } else {
        this.isProjectUklUpl = true;
      }
    },
    async handleNotSave() {
      this.$confirm(
        'Apakah anda yakin tidak ada perbaikan di <b> Formulir UKL UPL </b> ini dan akan melanjutkan ke <b> Matriks UKL UPL </b> ?',
        'Peringatan',
        {
          confirmButtonText: 'OK',
          cancelButtonText: 'Batal',
          type: 'warning',
          dangerouslyUseHTMLString: true,
        }).then(() => {
        this.$router.push({
          name: 'MatriksUklUpl',
          params: this.$route.params && this.$route.params.id,
          query: {
            perbaikan: true,
            isFixFormulirUklUpl: false,
          },
        });
      });
    },
    async handleSaveForm(perbaikan) {
      const projectId = this.$route.params && this.$route.params.id;
      const sections = ['formulir.uklupl.jenis.besaran.dampak'];
      const result = await saveStatus.list({
        sections,
        project_id: projectId,
      });
      const errors = [];
      sections.map((value, index) => {
        result.map((val, ind) => {
          if (value === val.section && val.temporary) {
            errors.push(value);
          }
        });
      });
      if (errors.length >= 1) {
        this.$alert(
          'Silahkan lengkapi data Jenis dan Besaran Dampak kemudian pilih tombol Simpan Perubahan pada masing-masing matriks',
          {
            confirmButtonText: 'OK',
          }
        );
      } else {
        if (perbaikan) {
          this.$confirm(
            'Pastikan data perbaikan anda sudah terisi semua pada <b> Formulir UKL UPL </b>. Apakah anda yakin akan melanjutkan ke <b> Matriks UKL UPL </b> ?',
            'Peringatan',
            {
              confirmButtonText: 'OK',
              cancelButtonText: 'Batal',
              type: 'warning',
              dangerouslyUseHTMLString: true,
            }).then(() => {
            this.$router.push({
              name: 'MatriksUklUpl',
              params: projectId,
              query: {
                perbaikan: true,
                isFixFormulirUklUpl: true,
              },
            });
          });
        } else {
          this.$router.push({
            name: 'MatriksUklUpl',
            params: projectId,
          });
        }
      }
    },
    handleEnableSimpanLanjutkan() {
      this.isSubmitEnabled = true;
    },
    handleReloadVsaList(tab) {
      this.accordionKey = this.accordionKey + 1;
      if (tab === 'pelingkupan') {
        this.activeName = '1';
      } else if (tab === 'matriks-identifikasi-dampak') {
        this.activeName = '2';
      } else if (tab === 'jenis-besaran-dampak') {
        this.activeName = '3';
      }
    },
  },
};
</script>

<style>
.bagan__alir {
  display: none;
}

h2 {
  display:inline-block;
  margin-block-start: 0em;
}

.el-collapse-item__header {
  /* background-color: #296d36; */
  background-color: #1E5128;
  padding-left: 10px;
  font-size: large;
  font-weight: bold;
  color: rgb(196, 196, 196);
}
.el-collapse-item__content {
  padding-top: 10px;
}

table th, table td {word-break: normal !important; padding:.5em; line-height:1.2em; border: 1px solid #eee;}
table td { vertical-align: top !important;}
table thead  {background-color:#6cc26f !important; color: white !important;}
table td.title, table tr.title td, table.title td { text-align:left;}
div.div-fka {
  padding: 0.5em;
  margin-bottom: 0.6em;
  background-color: #fafafa;
}
</style>
