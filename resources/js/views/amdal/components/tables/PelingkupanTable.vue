<template>
  <el-row :gutter="4">
    <el-col :span="6" :xs="24">
      <el-table
        v-loading="loadingSubProjects"
        :data="subProjects.utama"
        fit
        highlight-current-row
        :header-cell-style="{ background: '#def5cf' }"
        style="width: 100%"
      >
        <el-table-column label="No." width="54px">
          <template slot-scope="scope">
            {{ scope.$index + 1 }}
          </template>
        </el-table-column>
        <el-table-column label="Kegiatan Utama">
          <template slot-scope="scope">
            <span>{{ scope.row.name }}</span>
            <el-button
              type="success"
              size="mini"
              class="pull-right"
              icon="el-icon-caret-right"
              @click="handleViewComponents(scope.row)"
            />
          </template>
        </el-table-column>
      </el-table>
      <el-table
        v-loading="loadingSubProjects"
        :data="subProjects.pendukung"
        fit
        highlight-current-row
        :header-cell-style="{ background: '#def5cf' }"
        style="width: 100%"
      >
        <el-table-column label="No." width="54px">
          <template slot-scope="scope">
            {{ scope.$index + 1 }}
          </template>
        </el-table-column>
        <el-table-column label="Kegiatan Pendukung">
          <template slot-scope="scope">
            <span>{{ scope.row.name }}</span>
            <el-button
              type="success"
              size="mini"
              class="pull-right"
              icon="el-icon-caret-right"
              @click="handleViewComponents(scope.row)"
            />
          </template>
        </el-table-column>
      </el-table>
    </el-col>
    <el-col v-loading="loadingKomponen" :span="18" :xs="24">
      <div style="overflow: auto">
        <table :key="tableKey" class="title" style="border-collapse: collapse; width:100%;">
          <thead>
            <tr>
              <th rowspan="2">Komponen Kegiatan</th>
              <th colspan="6">Komponen Lingkungan</th>
            </tr>
            <tr>
              <th>Geofisika Kimia</th>
              <th>Biologi</th>
              <th>Sosial Ekonomi Budaya</th>
              <th>Kesehatan Masyarakat</th>
              <th>Kegiatan Lain Sekitar</th>
              <th>Lainnya</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                <div v-for="comp in subProjectComponents" :key="comp.id" style="margin:.5em 0;">
                  <el-row>
                    <el-tooltip class="item" effect="dark" placement="top-start">
                      <div slot="content">
                        {{ comp.description_specific }}
                      </div>
                      <el-button
                        type="default"
                        size="medium"
                        :style="componentButtonStyle(comp.id)"
                        @click="handleViewRonaAwals(comp)"
                      >
                        <el-button
                          v-if="!isAndal && isFormulator"
                          type="danger"
                          size="mini"
                          icon="el-icon-close"
                          style="margin-left: 0px; margin-right: 10px;"
                          class="button-action-mini"
                          @click="handleDeleteComponent(comp.id)"
                        />
                        <span>{{ comp.name }}</span>
                        <el-button
                          type="default"
                          size="mini"
                          class="pull-right button-action-mini"
                          icon="el-icon-edit"
                          @click="handleEditComponent(comp.id)"
                        />
                      </el-button>
                    </el-tooltip>
                  </el-row>
                </div>

                <el-button
                  v-if="!isAndal && isFormulator"
                  icon="el-icon-plus"
                  circle
                  style="margin-top:3em;display:block;"
                  round
                  :disabled="!(currentIdSubProject > 0)"
                  @click="handleAddComponent()"
                />
              </td>
              <td v-for="i in 6" :key="i">
                <div v-for="ra in subProjectRonaAwals[i-1].rona_awals" :key="ra.id" style="margin:.5em 0;">
                  <el-tooltip class="item" effect="dark" placement="top-start">
                    <div slot="content">
                      {{ ra.description_specific }}
                    </div>
                    <el-tag key="ra.id" type="info" :closable="closable && !isAndal && isFormulator" @close="handleDeleteRonaAwal(ra.id)">{{ ra.name }}</el-tag>
                  </el-tooltip>
                </div>

                <el-button
                  v-if="!isAndal && isFormulator"
                  icon="el-icon-plus"
                  circle
                  style="margin-top:3em;display:block;"
                  round
                  :disabled="!(currentIdSubProjectComponent > 0)"
                  @click="handleAddRonaAwal(i)"
                />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </el-col>
    <add-component-dialog
      v-if="!isAndal && isFormulator"
      :key="componentDialogKey"
      :show="kKDialogueVisible"
      :sub-projects="subProjects"
      :id-project-stage="idProjectStage"
      :project-stages="projectStages"
      :project-stage="projectStages.find(p => p.id === idProjectStage)"
      :sub-project-component="editSubProjectComponent"
      :is-edit="isEditComponent"
      :current-id-sub-project="currentIdSubProject"
      :selected-id-sub-project-component="currentIdSubProjectComponent"
      :master-components="masterComponents.filter(c => c.id_project_stage === idProjectStage)"
      @handleCloseAddComponent="handleCloseAddComponent"
      @handleSetCurrentIdSubProjectComponent="handleSetCurrentIdSubProjectComponent"
      @currentSubProject="onCurrentSubProject"
    />
    <add-rona-awal-dialog
      v-if="!isAndal && isFormulator"
      :key="ronaAwalDialogKey"
      :show="kLDialogueVisible"
      :component-types="componentTypes"
      :sub-projects="subProjects"
      :id-project="idProject"
      :id-project-stage="idProjectStage"
      :project-stage="projectStages.find(p => p.id === idProjectStage)"
      :current-id-sub-project="currentSubProject.id"
      :current-id-sub-project-component="currentIdSubProjectComponent"
      :current-id-component-type="currentIdComponentType"
      :sub-project-components="subProjectComponents"
      :current-sub-project-name="currentSubProject.name"
      :current-component="currentComponent"
      :master-hues="masterHues.filter(h => h.id_component_type === currentIdComponentType)"
      @handleCloseAddRonaAwal="handleCloseAddRonaAwal"
    />
  </el-row>
</template>

<script>
import Resource from '@/api/resource';
import AddComponentDialog from '../dialogs/AddComponentDialog.vue';
import AddRonaAwalDialog from '../dialogs/AddRonaAwalDialog.vue';
const scopingResource = new Resource('scoping');
const subProjectComponentResource = new Resource('sub-project-components');
const subProjectRonaAwalResource = new Resource('sub-project-rona-awals');

export default {
  name: 'PelingkupanTable',
  components: { AddComponentDialog, AddRonaAwalDialog },
  props: {
    idProject: {
      type: Number,
      default: () => 0,
    },
    idProjectStage: {
      type: Number,
      default: () => 0,
    },
    currentIdSubProject: {
      type: Number,
      default: () => 0,
    },
    masterComponents: {
      type: Array,
      default: () => [],
    },
    masterHues: {
      type: Array,
      default: () => [],
    },
    projectStages: {
      type: Array,
      default: () => [],
    },
    componentTypes: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      tableKey: 0,
      componentDialogKey: 1,
      ronaAwalDialogKey: 1,
      kKDialogueVisible: false,
      kLDialogueVisible: false,
      closable: true,
      subProjects: {
        utama: [],
        pendukung: [],
      },
      subProjectComponents: [],
      subProjectRonaAwals: [
        { rona_awals: [] },
        { rona_awals: [] },
        { rona_awals: [] },
        { rona_awals: [] },
        { rona_awals: [] },
        { rona_awals: [] },
      ],
      currentIdSubProjectComponent: 0,
      currentIdComponentType: 0,
      editSubProjectComponent: {},
      isEditComponent: false,
      loadingSubProjects: true,
      loadingKomponen: true,
      currentSubProject: { id: 0, name: '' },
      currentComponentName: '',
      currentComponent: null,
    };
  },
  computed: {
    isAndal() {
      return this.$route.name === 'penyusunanAndal';
    },
    isFormulator() {
      return this.$store.getters.roles.includes('formulator');
    },
  },
  mounted() {
    this.getData();
  },
  methods: {
    reloadData() {
      this.getComponents(this.currentIdSubProject);
      this.getRonaAwals(this.currentIdSubProjectComponent);
      // reload dialogs
      this.componentDialogKey = this.componentDialogKey + 1;
      this.ronaAwalDialogKey = this.ronaAwalDialogKey + 1;
    },
    handleAddComponent() {
      if (this.isEditComponent) {
        this.editSubProjectComponent = {};
        this.componentDialogKey = this.componentDialogKey + 1;
      }
      this.isEditComponent = false;
      this.kKDialogueVisible = true;
    },
    handleAddRonaAwal(idComponentType) {
      this.currentIdComponentType = idComponentType;
      console.log(this.currentComponent);
      this.kLDialogueVisible = true;
    },
    handleCloseAddComponent(idSubProjectComponent) {
      this.kKDialogueVisible = false;
      this.currentIdSubProjectComponent = idSubProjectComponent;
      this.reloadData();
    },
    handleSetCurrentIdSubProjectComponent(idSubProjectComponent) {
      this.currentIdSubProjectComponent = idSubProjectComponent;
      this.ronaAwalDialogKey = this.ronaAwalDialogKey + 1;
    },
    handleCloseAddRonaAwal(idSubProjectComponent) {
      this.kLDialogueVisible = false;
      this.currentIdSubProjectComponent = idSubProjectComponent;
      this.reloadData();
    },
    handleDeleteComponent(id) {
      subProjectComponentResource
        .destroy(id)
        .then((response) => {
          this.$message({
            message: 'Komponen Kegiatan berhasil dihapus',
            type: 'success',
            duration: 5 * 1000,
          });
          // reload PelingkupanTable
          this.kKDialogueVisible = false;
          this.reloadData();
        })
        .catch((error) => {
          console.log(error);
        });
    },
    handleDeleteRonaAwal(id) {
      subProjectRonaAwalResource
        .destroy(id)
        .then((response) => {
          this.$message({
            message: 'Komponen Lingkungan berhasil dihapus',
            type: 'success',
            duration: 5 * 1000,
          });
          // reload PelingkupanTable
          this.kLDialogueVisible = false;
          this.reloadData();
        })
        .catch((error) => {
          console.log(error);
        });
    },
    handleEditComponent(id) {
      // show dialog
      this.isEditComponent = true;
      this.editSubProjectComponent = this.subProjectComponents.filter(spc => spc.id === id)[0];
      if (this.isEditComponent) {
        this.kKDialogueVisible = true;
      }
    },
    async handleViewComponents(subP) {
      const idSubProject = subP.id;
      this.currentIdSubProject = idSubProject;

      /* let subP = this.subProjects.utama.find(spu => spu.id === idSubProject);
      if (!subP){
        subP = this.subProjects.pendukung.find(spp => spp.id === idSubProject);
      }*/
      this.currentSubProject = {
        id: idSubProject,
        name: subP.name,
      };

      console.log('handleViewCompoents: ', this.currentSubProject);
      this.$emit('handleCurrentIdSubProject', idSubProject);
      this.getComponents(idSubProject);
      this.subProjectComponents = [];
      const sp = await subProjectComponentResource.list({
        id_sub_project: idSubProject,
        id_project_stage: this.idProjectStage,
      });
      this.subProjectRonaAwals = [
        { rona_awals: [] },
        { rona_awals: [] },
        { rona_awals: [] },
        { rona_awals: [] },
        { rona_awals: [] },
        { rona_awals: [] },
      ];
      if (sp.data.length > 0){
        this.getRonaAwals(sp.data[0].id);
      }
      // reload table
      this.tableKey = this.tableKey + 1;
      // reload dialogs
      this.componentDialogKey = this.componentDialogKey + 1;
      this.ronaAwalDialogKey = this.ronaAwalDialogKey + 1;
    },
    componentButtonStyle(id) {
      if (id === this.currentIdSubProjectComponent) {
        return { backgroundColor: '#facd7a', color: '#000000' };
      } else {
        return { backgroundColor: '#ffffff', color: '#099C4B' };
      }
    },
    async handleViewRonaAwals(comp) {
      const idSubProjectComponent = comp.id;
      this.currentIdSubProjectComponent = comp.id;
      this.currentComponent = this.masterComponents.find(c => c.id === comp.component.id);
      if (!this.currentComponent){
        this.currentComponent = comp.component;
      }
      console.log('viewRonaAwals', this.currentComponent, comp);
      this.componentButtonStyle(idSubProjectComponent);
      this.getRonaAwals(idSubProjectComponent);
      // this.componentButtonStyle = { backgroundColor: '#facd7a' };
      // reload dialogs
      this.componentDialogKey = this.componentDialogKey + 1;
      this.ronaAwalDialogKey = this.ronaAwalDialogKey + 1;
    },
    async getData() {
      const subProjectUtama = await scopingResource.list({
        id_project: this.idProject,
        sub_project_type: 'utama',
      });
      this.subProjects.utama = subProjectUtama.data;

      const subProjectPendukung = await scopingResource.list({
        id_project: this.idProject,
        sub_project_type: 'pendukung',
      });
      this.subProjects.pendukung = subProjectPendukung.data;
      this.loadingSubProjects = false;
      // init rona awals array
      for (let i = 0; i < 6; i++){
        this.subProjectRonaAwals.push({
          rona_awals: [],
        });
      }
      // get first sub project's components & rona_awals
      if (this.subProjects.utama.length > 0) {
        const firstSubProject = this.subProjects.utama[0];
        this.getComponents(firstSubProject.id);
        const sp = await subProjectComponentResource.list({
          id_sub_project: firstSubProject.id,
          id_project_stage: this.idProjectStage,
        });
        if (sp.data.length > 0){
          this.getRonaAwals(sp.data[0].id);
        }
        this.loadingKomponen = false;
        this.$emit('handleCurrentIdSubProject', firstSubProject.id);
        this.componentDialogKey = this.componentDialogKey + 1;
      }
    },
    async getComponents(idSubProject) {
      this.componentDialogKey = this.componentDialogKey + 1;
      const components = await scopingResource.list({
        id_sub_project: idSubProject,
        id_project_stage: this.idProjectStage,
        sub_project_components: true,
      });
      components.data.map((comp) => {
        if (comp.name === null) {
          comp.name = comp.component.name;
        }
      });
      this.subProjectComponents = components.data;
      if (this.subProjectComponents.length > 0 && this.currentIdSubProjectComponent === 0) {
        this.currentIdSubProjectComponent = this.subProjectComponents[0].id;
      }
    },
    async getRonaAwals(idSubProjectComponent) {
      if (parseInt(idSubProjectComponent) > 0) {
        const ronaAwals = await scopingResource.list({
          sub_project_rona_awals: true,
          id_sub_project_component: idSubProjectComponent,
        });
        ronaAwals.data.map((ra) => {
          ra.rona_awals.map((r) => {
            if (r.name === null) {
              r.name = r.rona_awal.name;
            }
          });
        });
        this.subProjectRonaAwals = ronaAwals.data;
      }
      this.loadingKomponen = false;
    },
    onCurrentSubProject(val){
      // this.currentSubProject = val;
    },
  },
};
</script>

<style scoped>
table.el-table__header {
  background-color: #6cc26f !important;
}

table th, table td {word-break: normal !important; padding:.5em; line-height:1.2em; border: 1px solid #eee;}
table td { vertical-align: top !important;}
table thead  {background-color:#6cc26f !important; color: white !important;}
table td.title, table tr.title td, table.title td { text-align:left;}

.button-action-mini {
  margin-left: 20px;
  padding: 1px 1px;
}

.button-medium {
  padding: 5px 5px 5px 5px;
}

.button-comp-active {
  background-color: #facd7a;
}

.button-comp-inactive {
  background-color: white;
}

</style>
