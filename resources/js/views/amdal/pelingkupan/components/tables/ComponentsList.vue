<template>
  <div
    v-if="components.length > 0"
    :class="className + ' component-list'"
  >
    <div
      v-for="(comp) in components"
      :key="comp.id+'_'+id"
    >
      <p
        v-if="isMasterComponent"
        :data-id="comp.id"
        @click.self="select"
      >
        <el-button v-if="showDelete" type="danger" icon="el-icon-close" size="mini" plain square style="float:left; width:20px; padding: 3px 0;" :disabled="isReadOnly && !isUrlAndal" @click="!isReadOnly && isUrlAndal, removeComponent(comp.id)" />
        <i v-if="showEdit" class="el-icon-edit" size="mini" style="float:right; padding:5px; cursor:pointer;" @click="editComponent(comp.id)" />
        {{ comp.name }}  <i v-if="comp.is_master" class="el-icon-success" style="color:#2e6b2e;" />
      </p>
      <p
        v-else-if="(activeComponent !== null) && (activeComponent.id === comp.id )"
        :data-id="comp.id"
        class="selected"
        @click.self="deSelect"
      >
        <el-button v-if="showDelete" type="danger" icon="el-icon-close" size="mini" plain square style="float:left; width:20px; padding: 3px 0;" :disabled="isReadOnly && !isUrlAndal" @click="!isReadOnly && isUrlAndal, removeComponent(comp.id)" />
        <i v-if="showEdit" class="el-icon-edit" size="mini" style="float:right; padding:5px; cursor:pointer;" @click="editComponent(comp.id)" />
        <!-- <el-button type="primary" icon="el-icon-close" size="mini" plain circle style="float:right; width:20px; padding: 3px 0;" @click="deSelect(comp.id)" />-->
        {{ comp.name }} <i v-if="comp.is_master" class="el-icon-success" style="color:#2e6b2e;" />
      </p>
      <p
        v-else
        :data-id="comp.id"
        @click.self="select"
      >
        <!-- <el-button v-if="showDelete" type="danger" icon="el-icon-close" size="mini" plain square style="float:left; width:20px; padding: 3px 0;" @click="removeComponent(comp.id)" />
        <i v-if="showEdit" class="el-icon-edit" size="mini" style="float:right; padding:5px; cursor:pointer;" @click="editComponent(comp.id)" /> -->
        {{ comp.name }}  <i v-if="comp.is_master" class="el-icon-success" style="color:#2e6b2e;" />
      </p>
    </div>
  </div>
</template>
<script>
import { mapGetters } from 'vuex';

export default {
  name: 'ComponentsList',
  props: {
    components: {
      type: Array,
      default: function(){
        return [];
      },
    },
    id: { type: String, default: 'Ssa' },
    showDelete: { type: Boolean, default: true },
    showEdit: { type: Boolean, default: true },
    className: { type: String, default: 'components-container' },
    // selectable & clickable
    selectable: { type: Boolean, default: false },
    multipleSelect: { type: Boolean, default: false },
    activeComponent: {
      type: Object,
      default: null,
    },
    highlighted: {
      type: Array,
      default: function() {
        return [];
      },
    },
    deSelectAll: {
      type: Boolean,
      default: false,
    },
    isMasterComponent: {
      type: Boolean,
      default: false,
    },
  },
  data(){
    return {
      selected: [],
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
  watch: {
    deSelectAll: function(val) {
      // console.log('deselect? ' + this.id, val);
      if (val === true){
        this.doDeselectAll();
      }
    },
  },
  methods: {
    removeComponent(id){
      // const e = this.components.find( c => c.id === id);
      this.$emit('delete', id);
    },
    editComponent(id){
      // const e = this.components.find( c => c.id === id);
      this.$emit('edit', id);
    },
    select(e){
      console.log('ComponentList: ', e);
      if (this.selectable){
        const id = parseInt(e.currentTarget.getAttribute('data-id'));
        if (this.multipleSelect){
          this.selected.push(id);
        } else {
          this.selected = [];
          this.selected.push(id);
        }
        this.$emit('onSelect', this.selected);
      }
    },
    deSelect(e){
      const id = parseInt(e.currentTarget.getAttribute('data-id'));
      if (this.selected.length > 0) {
        const idx = this.selected.findIndex(s => s === id);
        console.log(idx);
        if (idx >= 0){
          this.selected.splice(idx, 1);
        }
      }
      this.$emit('onSelect', this.selected);
    },
    doDeselectAll(){
      this.selected = [];
      this.$emit('onDeselect', true);
    },
    /* deSelectAll(){
      this.selected = [];
    },*/
  },
};
</script>
<style>

</style>
