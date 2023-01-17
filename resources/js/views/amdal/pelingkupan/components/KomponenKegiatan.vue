<template>
  <div class="master-komponen">
    <el-card shadow="never">
      <div slot="header" class="clearfix card-header" style="text-align:center; font-weight:bold; text-transform: uppercase;">
        <span>Komponen Kegiatan ({{ components.length }})</span>
      </div>
      <div>
        <components-list
          :id="'kegiatan'"
          :components="components"
          :is-master-component="true"
          @edit="editComponent"
          @delete="removeComponent"
        />
      </div>
      <!-- form -->
      <el-button icon="el-icon-plus" size="mini" circle type="primary" :disabled="isReadOnly && !isUrlAndal" @click="!isReadOnly && isUrlAndal, addComponent()" />
      <form-komponen-kegiatan
        :show="showForm"
        :form-mode="formMode"
        :mode="mode"
        :stages="projectStages"
        :input="selectedData"
        @onSave="onSaveData"
        @onClose="showForm = false"
      />
      <form-delete-component
        v-if="deleted !== null"
        :component="deleted"
        :show="showDelete"
        :resource="'project-components'"
        :mode="mode"
        @close="showDelete = false"
        @delete="afterDeleteComponent"
      />

    </el-card>
  </div>
</template>
<script>
import FormKomponenKegiatan from './forms/FormKomponenKegiatan.vue';
import FormDeleteComponent from './forms/FormDeleteComponent.vue';
import ComponentsList from './tables/ComponentsList.vue';
import { mapGetters } from 'vuex';

export default {
  name: 'KomponenKegiatan',
  components: { FormKomponenKegiatan, ComponentsList, FormDeleteComponent },
  props: {
    components: {
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
    mode: {
      type: Number,
      default: 0,
    },
  },
  data(){
    return {
      selectedData: null,
      // components: [],
      formMode: 0,
      showForm: false,
      showDelete: false,
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
  },
  methods: {
    onSaveData(obj){
      console.log('obj:', obj);
      console.log('selectedData', this.selectedData);

      const index = this.components.findIndex(e => e.id === obj.id);
      if (index < 0){
        this.components.push(obj);
      }
      this.selectedData = null;
    },
    addComponent() {
      this.showForm = true;
      this.formMode = 0;
    },

    editComponent(id) {
      this.selectedData = this.components.find(e => e.id === id);
      this.formMode = 1;
      this.showForm = true;
    },
    removeComponent(id) {
      if (this.components.length === 0) {
        return;
      }
      const co = this.components.find(c => c.id === id);
      this.deleted = co;
      this.showDelete = true;
    },
    afterDeleteComponent(val){
      const idx = this.components.findIndex(e => e.id === val);
      if (idx >= 0){
        this.components.splice(idx, 1);
      }
      this.deleted = null;
      this.showDelete = false;
      this.$emit('delete', true);
    },
  },
};
</script>
<style>

</style>
