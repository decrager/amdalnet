<template>
  <div class="scoupingModule" style="margin-top:2em;">
    <div style="text-align: right;">
      <el-button
        :icon="(loadingSubProjects || loadingComponents || loadingHues) ? 'el-icon-loading' : 'el-icon-refresh'"
        :disabled="loadingSubProjects || loadingComponents || loadingHues"
        size="small"
        @click="refresh()"
      > Refresh Data Pelingkupan
      </el-button>
    </div>
    <el-tabs v-if="projectStages.length > 0" v-model="activeName" @tab-click="handleTabClick">
      <el-tab-pane
        v-for="(stage, index) in projectStages"
        :key="'stage'+ index +'_tab'"
        :label="stage.name"
        :name="'stage'+stage.id"
      >
        <el-row
          :gutter="1"
          style="margin-top: 1em;"
        >
          <el-col :span="4">
            <el-card v-if="subProjects.length > 0" v-loading="loadingSubProjects" shadow="never">
              <div slot="header" class="clearfix card-header" style="text-align:center; font-weight:bold;">
                <span>Kegiatan Utama</span>
              </div>
              <components-list
                v-if="subProjects.length > 0"
                :id="'main_'+stage.id"
                :components="subProjects.filter(s => s.type === 'utama')"
                :active-component="activeScoping.sub_projects"
                :show-delete="false"
                :show-edit="false"
                :class-name="'scoping'"
                :selectable="true"
                :de-select-all="deSelectAllSPUtama"
                @onSelect="onSelectSubProjects"
              />
            </el-card>
            <el-card v-if="subProjects.length > 0" v-loading="loadingSubProjects" shadow="never" style="margin-top:1em;">
              <div slot="header" class="clearfix card-header" style="text-align:center; font-weight:bold;">
                <span>Kegiatan Pendukung</span>
              </div>
              <components-list
                :id="'supporting_'+stage.id"
                :components="subProjects.filter(s => s.type === 'pendukung')"
                :show-delete="false"
                :show-edit="false"
                :active-component="activeScoping.sub_projects"
                :class-name="'scoping'"
                :selectable="true"
                :de-select-all="deSelectAllSPPendukung"
                @onSelect="onSelectSubProjects"
              />
            </el-card>
          </el-col>
          <el-col :span="4">
            <el-card v-loading="loadingComponents" shadow="never">
              <div slot="header" class="clearfix card-header" style="text-align:center; font-weight:bold;">
                <span>Komponen Kegiatan</span>
              </div>
              <div style="margin-bottom:1em; ">
                <components-list
                  v-if="components && components.length > 0"
                  :id="'scopingComp_'+'stage_'+stage.id+'_'+index"
                  :key="'comp_'+ stage.id +'_'+index"
                  :components="(activeScoping.sub_projects === null) ? bevComponent : components.filter(c => (c.id_project_stage === stage.id) && (c.id_sub_project === activeScoping.sub_projects.id))"
                  :selectable="activeScoping.sub_projects !== null"
                  :class-name="'scoping'"
                  :active-component="activeComponent"
                  :show-edit="isFormulator"
                  :show-delete="isFormulator"
                  :de-select-all="activeComponent === null"
                  @edit="editComponent"
                  @delete="removeComponent"
                  @onSelect="onSelectComponents"
                  @onDeselect="deselectComponents"
                />
              </div>
              <el-button
                v-if="isFormulator && masterComponents && (masterComponents.filter(c => c.id_project_stage === stage.id ).length > 0) && (activeScoping.sub_projects !== null)"
                icon="el-icon-plus"
                size="mini"
                circle
                type="primary"
                plain
                :disabled="isReadOnly && !isUrlAndal"
                @click="!isReadOnly && isUrlAndal, addComponent()"
              />
            </el-card>
          </el-col>
          <el-col :span="16">
            <table v-if="(componentTypes.length > 0)" v-loading="loadingHues" class="scoupingTable">
              <thead style=" background: #216221 !important; ">
                <tr><th colspan="6">Komponen Lingkungan</th></tr>
                <tr>
                  <th
                    v-for="ct in componentTypes"
                    :key="'stage_'+ index +'header_ct_'+ct.id"
                    class="th-hues"
                  >
                    {{ ct.name }}
                  </th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td
                    v-for="ct in componentTypes"
                    :key="'stage_'+ stage.id +'_body_ct_'+ct.id"
                  >
                    <components-list
                      :id="'scopingHue_s'+stage.id+'_hue_'+ct.id"
                      :key="'scopingHue_key_'+stage.id+'_hue_'+ct.id"
                      :components="(activeScoping.sub_projects === null) ? bevHue.filter(h => h.id_component_type === ct.id ) : ((activeComponent === null ) ? [] : hues.filter(h => (h.id_component_type === ct.id) && (h.id_project_stage === id_project_stage) && (h.id_sub_project_component === activeComponent.id_sub_project_component)))"
                      :selectable="(activeScoping.sub_projects !== null) && (activeScoping.component !== null )"
                      :class-name="'scoping'"
                      :show-edit="isFormulator"
                      :show-delete="isFormulator"
                      :active-component="activeHue"
                      :de-select-all="activeHue === null"
                      @edit="editHue"
                      @delete="removeHue"
                      @onSave="onSaveHue"
                      @onSelect="onSelectHues"
                    />
                    <el-button
                      v-if="(activeScoping.component !== null) && (((getHueOptions()).filter(h => h.id_component_type === ct.id)).length > 0)"
                      icon="el-icon-plus"
                      size="mini"
                      circle
                      type="primary"
                      plain
                      :disabled="isReadOnly && !isUrlAndal"
                      @click="!isReadOnly && isUrlAndal, addHue(ct.id)"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </el-col>
        </el-row>
      </el-tab-pane>
    </el-tabs>
    <!-- "masterComponents.filter(c => c.id_project_stage === id_project_stage)" -->
    <form-add-component
      :show="showForm"
      :data="activeScoping"
      :master="(activeScoping.component !== null) ? masterComponents.find(c => c.id === activeScoping.component.id ) : null"
      :master-components="getComponentOptions()"
      :mode="mode"
      @onClose="showForm = false"
      @onSave="onSaveComponent"
    />
    <form-add-hue
      v-if="(activeScoping.component !== null) && (activeScoping.id_component_type !== null)"
      :show="showAddHue"
      :data="activeScoping"
      :master-component="(activeScoping.component !== null) ? masterComponents.find(c => c.id === activeScoping.component.id) : null"
      :master="(activeScoping.rona_awal !== null) ? masterHues.find(h => h.id === activeScoping.rona_awal.id) : null"
      :master-hues="(getHueOptions()).filter(c => c.id_component_type === activeScoping.id_component_type)"
      :mode="mode"
      @onClose="showAddHue = false"
      @onSave="onSaveHue"
    />

    <form-delete-component
      v-if="deleted !== null"
      :id="'scoping_delete_form'+ 1"
      :key="'scoping_delete_form'"
      :component="deleted"
      :show="showDelete"
      :mode="mode"
      :resource="delResource"
      @close="showDelete = false"
      @delete="afterDeleteComponent"
    />
  </div>
</template>
<script>
import { mapGetters } from 'vuex';
import Resource from '@/api/resource';
import ComponentsList from './components/tables/ComponentsList.vue';
import FormAddComponent from './components/forms/FormAddComponent.vue';
import FormAddHue from './components/forms/FormAddHue.vue';
import FormDeleteComponent from './components/forms/FormDeleteComponent.vue';
const projectResource = new Resource('projects');
const subProjectComponent = new Resource('subproject-components');
const subProjectHue = new Resource('sub-project-rona-awals');

export default {
  name: 'TabelPelingkupan',
  components: { ComponentsList, FormAddComponent, FormAddHue, FormDeleteComponent },
  props: {
    masterComponents: {
      type: Array,
      default: function() {
        return [];
      },
    },
    masterHues: {
      type: Array,
      default: function() {
        return [];
      },
    },
    componentTypes: {
      type: Array,
      default: function() {
        return [];
      },
    },
    projectStages: {
      type: Array,
      default: function() {
        return [];
      },
    },
    refreshComponents: {
      type: Boolean,
      default: false,
    },
    mode: {
      type: Number,
      default: 0,
    },
  },
  data(){
    return {
      id_project: 0,
      id_project_stage: null,
      cOptions: [],
      mapping: [],
      subProjects: [],
      activeName: 'stage4',
      activeComponent: null,
      activeHue: null,
      activeScoping: {
        id_project: 0,
        project_stage: null,
        component: null,
        rona_awal: null,
        sub_projects: null,
        id_component_type: null,
      },
      bevComponent: [],
      bevHue: [],
      components: [],
      hues: [],
      showForm: false,
      showAddHue: false,
      showDelete: false,
      deSelectAllSPUtama: false,
      deSelectAllSPPendukung: false,
      deSelectAllComponents: false,
      deSelectAllHues: false,
      current_component_type: null,
      loadingComponents: false,
      loadingHues: false,
      loadingSubProjects: false,
      delResource: '',
      deleted: null,
    };
  },
  computed: {
    ...mapGetters({
      markingStatus: 'markingStatus',
    }),

    isReadOnly() {
      const data = [
        'uklupl-mt.sent',
        'uklupl-mt.adm-review',
        'uklupl-mt.examination-invitation-drafting',
        'uklupl-mt.examination-invitation-sent',
        'uklupl-mt.examination',
        'uklupl-mt.examination-meeting',
        'uklupl-mt.ba-drafting',
        'uklupl-mt.ba-signed',
        'uklupl-mt.recommendation-drafting',
        'uklupl-mt.recommendation-signed',
        'uklupl-mr.pkplh-published',
        'uklupl-mt.pkplh-published',
        'amdal.form-ka-submitted',
        'amdal.form-ka-adm-review',
        'amdal.form-ka-adm-returned',
        'amdal.form-ka-adm-approved',
        'amdal.form-ka-examination-invitation-drafting',
        'amdal.form-ka-examination-invitation-sent',
        'amdal.form-ka-examination',
        'amdal.form-ka-examination-meeting',
        'amdal.form-ka-returned',
        'amdal.form-ka-approved',
        'amdal.form-ka-ba-drafting',
        'amdal.form-ka-ba-signed',
        'amdal.andal-drafting',
        'amdal.rklrpl-drafting',
        'amdal.submitted',
        'amdal.adm-review',
        'amdal.adm-approved',
        'amdal.examination',
        'amdal.feasibility-invitation-drafting',
        'amdal.feasibility-invitation-sent',
        'amdal.feasibility-review',
        'amdal.feasibility-review-meeting',
        'amdal.feasibility-ba-drafting',
        'amdal.feasibility-ba-signed',
        'amdal.recommendation-drafting',
        'amdal.recommendation-signed',
        'amdal.skkl-published',
      ];

      console.log({ workflow: this.markingStatus });

      return data.includes(this.markingStatus);
    },
    isUrlAndal() {
      const data = [
        'amdal.form-ka-submitted',
        'amdal.form-ka-adm-review',
        'amdal.form-ka-adm-returned',
        'amdal.form-ka-adm-approved',
        'amdal.form-ka-examination-invitation-drafting',
        'amdal.form-ka-examination-invitation-sent',
        'amdal.form-ka-examination',
        'amdal.form-ka-examination-meeting',
        'amdal.form-ka-returned',
        'amdal.form-ka-approved',
        'amdal.form-ka-ba-drafting',
        'amdal.form-ka-ba-signed',
        'amdal.andal-drafting',
        'amdal.rklrpl-drafting',
        'amdal.submitted',
      ];
      return this.$route.name === 'penyusunanAndal' && data.includes(this.markingStatus);
    },

    isAndal() {
      return this.$route.name === 'penyusunanAndal';
    },
    isFormulator() {
      return this.$store.getters.roles.includes('formulator');
    },
  },
  watch: {
    refreshComponents: {
      deep: true,
      handler(val, oldVal){
        if (val === true){
          this.getSubProjectComponents();
          this.getSubProjectsHues();
          this.$emit('refreshed', true);
        }
      },
    },
    /* function(val, old){
      console.log(val, refreshComponents);
      if (val === true){
        this.getSubProjectComponents();
        this.getSubProjectsHues();
      }
    },*/
  },
  mounted(){
    this.id_project = this.$route.params && this.$route.params.id;
    this.initActiveScoping();
    this.initMapping();
  },
  methods: {
    refresh(){
      this.initActiveScoping();
      this.initMapping();
    },
    initActiveScoping() {
      const ps = this.projectStages.find(ps => ps.id === parseInt(this.activeName.charAt(this.activeName.length - 1)));
      this.current_component_type = null;
      this.id_project_stage = parseInt(this.activeName.charAt(this.activeName.length - 1));
      this.activeScoping.id_project = this.$route.params && this.$route.params.id;
      this.activeScoping.project_stage = ps;
      this.activeScoping.component = null;
      this.activeScoping.rona_awal = null;
      this.activeScoping.sub_projects = null;
      this.activeScoping.id_component_type = null;
      this.activeComponent = null;
      this.activeHue = null;
    },
    initBEVComponent(){
      this.bevComponent = [];
      this.components.filter(c => c.id_project_stage === this.id_project_stage).forEach((c) => {
        const id = this.bevComponent.findIndex(b => (b.id === c.id));
        if (id < 0){
          this.bevComponent.push(c);
        }
      });
    },
    initBEVHue(){
      this.bevHue = [];
      this.hues.filter(h => h.id_project_stage === this.id_project_stage).forEach((h) => {
        const id = this.bevHue.findIndex(b => (b.id === h.id));
        if (id < 0){
          this.bevHue.push(h);
        }
      });
    },
    initMapping() {
      this.getSubProjects();
      this.getSubProjectComponents();
      this.getSubProjectsHues();
    },
    async getSubProjects(){
      this.loadingSubProjects = true;
      const id = this.$route.params && this.$route.params.id;
      await projectResource.list({ id: id })
        .then((res) => {
          if (res.list_sub_project) {
            this.subProjects = res.list_sub_project;
            // console.log('ada sbproject!', this.subProjects);
          }
        })
        .finally(() => {
          this.loadingSubProjects = false;
        });
    },
    async getSubProjectComponents(){
      this.loadingComponents = true;
      this.components = [];
      await subProjectComponent.list({ id_project: this.id_project, scoping: true, mode: this.isAndal })
        .then((res) => {
          this.components = res;
          this.initBEVComponent();
        })
        .finally(() => {
          this.loadingComponents = false;
        });
    },
    async getSubProjectsHues(){
      this.loadingHues = true;
      this.hues = [];
      await subProjectHue.list({ id_project: this.id_project, scoping: true, mode: this.isAndal })
        .then((res) => {
          this.hues = res;
          this.initBEVHue();
        })
        .finally(() => {
          this.loadingHues = false;
        });
    },
    // components and hues
    onSelectComponents(sel){
      this.activeScoping.component = null;
      this.activeScoping.rona_awal = null;
      this.activeComponent = null;
      this.activeHue = null;
      this.deSelectAllComponents = false;

      if (sel.length > 0){
        this.activeScoping.component = this.components.find(c => (c.id === sel[0]) && (c.id_sub_project === this.activeScoping.sub_projects.id));
        this.activeComponent = this.activeScoping.component;
      }
      // console.log('components:', this.activeComponent);
    },
    onSelectHues(sel){
      this.activeScoping.rona_awal = null;
      this.activeHue = null;
      if (sel.length > 0){
        const hue = this.hues.find(h => (h.id === sel[0]) && (h.id_sub_project_component === this.activeScoping.component.id_sub_project_component));
        this.activeScoping.rona_awal = hue;
        this.activeHue = hue;
      }
    },
    onSelectSubProjects(sel) {
      // console.log('selectSubProject', sel.length);
      this.deSelectAllComponents = true;
      this.initActiveScoping();
      if (sel.length > 0){
        var sp = this.subProjects.find(sub => sub.id === sel[0]);
        // console.log('selectedSubProject:', sp);
        if (sp.type === 'pendukung') {
          this.deSelectAllSPUtama = true;
          this.deSelectAllSPPendukung = false;
        } else {
          this.deSelectAllSPPendukung = true;
          this.deSelectAllSPUtama = false;
        }

        this.activeScoping.sub_projects = sp;
        // get sub components
      } else {
        // clear board? or show all?

      }
      // console.log('subproject', this.activeScoping);
    },
    onSaveComponent(obj){
      /* console.log('save como', obj);
      const idx = this.components.findIndex(c => c.id_sub_project_component === obj.id_sub_project_component);
      if (idx >= 0){
        this.components[idx].description = obj.description;
        this.components[idx].measurement = obj.measurement;
      } else {
        this.components.push(obj);
      }*/
      this.activeComponent = obj;
      this.activeScoping.component = obj;
      this.getSubProjectComponents();
      // console.log(this.activeComponent);
    },
    onSaveHue(obj){
      this.hues.push(obj);
      this.activeHue = obj;
      this.activeScoping.rona_awal = obj;
      this.getSubProjectsHues();
      // console.log('hues ', obj);
    },
    addComponent(){
      this.activeScoping.component = null;
      this.activeComponent = null;
      this.showForm = true;
    },
    editComponent(val){
      if (this.activeScoping.component === null){
        this.activeScoping.component = this.components.find(c => (c.id === val) &&
          (c.id_project_stage === this.id_project_stage) &&
          (c.id_sub_project === this.activeScoping.sub_projects.id));
      }
      this.showForm = true;
    },
    deleteComponent(val){
    },
    addHue(id){
      this.activeScoping.id_component_type = id;
      this.current_component_type = id;
      this.activeScoping.rona_awal = null;
      this.activeHue = null;
      this.showAddHue = true;
    },
    editHue(val){
      console.log(val);
      const hue = this.hues.find(h => (h.id === val) && (h.id_sub_project_component === this.activeScoping.component.id_sub_project_component));
      this.activeScoping.id_component_type = hue.id_component_type;
      this.current_component_type = hue.id_component_type;
      this.activeScoping.rona_awal = hue;
      this.activeHue = hue;
      this.showAddHue = true;
    },
    handleTabClick(tab, event){
      this.initActiveScoping();
      this.initBEVComponent();
      this.initBEVHue();
    },
    handleSaveForm(val){
      if (!this.mapping.find(c => c.component.id === val.component.id) && !this.mapping.find(c => c.rona_awal.id === val.rona_awal.id)) {
        this.mapping.push({
        });
      }
    },
    getIds(arr){
      const ids = [];
      arr.forEach((a) => {
        if (!ids.includes(a.id)){
          ids.push(a.id);
        }
      });
      return ids;
    },
    getComponentOptions(){
      const ids = this.getIds(this.components.filter(c => (c.id_project_stage === this.id_project_stage) &&
        ((this.activeScoping.sub_projects !== null) ? (c.id_sub_project === this.activeScoping.sub_projects.id) : true)));
      // console.log('co ids:', ids);
      return this.masterComponents.filter(c => (!ids.includes(c.id)) && (c.id_project_stage === this.id_project_stage));
    },
    getHueOptions(){
      // console.log(this.activeScoping.component);
      const ids = this.getIds(this.hues.filter(h => (h.id_sub_project_component === this.activeScoping.component.id_sub_project_component)));
      return this.masterHues.filter(c => (!ids.includes(c.id)));
    },
    deselectComponents(val){
      if (val.length === 0){
        this.activeScoping.component = null;
        this.activeComponent = null;
        this.deSelectAllComponents = true;
      }
    },
    spToString(arr){
      const str = [];
      arr.forEach((e) => {
        str.push(e.name);
      });
      return str.join(', ');
    },
    // deletes
    removeComponent(id){
      this.deleted = this.components.find(c => (c.id === id) && (c.id_sub_project === this.activeScoping.sub_projects.id));
      this.delResource = 'sub-project-components';
      this.showDelete = true;
    },
    afterDeleteComponent(){
      this.deleted = null;

      if (this.delResource === 'sub-project-components'){
        this.getSubProjectComponents();
        this.getSubProjectsHues();
      }
      if (this.delResource === 'sub-project-rona-awals'){
        this.getSubProjectsHues();
      }
      this.delResource = '';
    },
    removeHue(id){
      this.deleted = this.hues.find(h => (h.id === id) && (h.id_sub_project_component === this.activeScoping.component.id_sub_project_component));
      this.delResource = 'sub-project-rona-awals';
      this.showDelete = true;
    },
  },
};
</script>
<style>
.scoping  p { font-size: 96%; }

table.scoupingTable{
  width:100%;
}
table.scoupingTable th {color: #ffffff !important; }
table.scoupingTable th.th-hues  {width: 16.67%;  line-height: 1.25em;}
table.scoupingTable td { vertical-align: top; font-weight: normal; }
</style>
