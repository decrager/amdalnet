<template>
  <div>
    <el-dialog
      :title="'Master Data Komponen Kegiatan Lain Sekitar'"
      :visible.sync="show"
      width="40%"
      height="450"
      :destroy-on-close="true"
      :before-close="handleClose"
      :close-on-click-modal="false"
      @open="onOpen"
    >
      <div v-loading="isSaving">
        <el-form label-position="top">
          <el-form-item v-if="formMode === 0" label="Nama Kegiatan">
            <el-select
              v-model="data.id"
              style="width:100%"
              clearable
              filterable
              :disabled="noMaster"
              :loading="loadingMaster"
              loading-text="Memuat data..."
              @change="onSelectActivity"
              @clear="onClearActivity"
            >
              <el-option
                v-for="item in master"
                :key="item.value"
                :label="item.name"
                :value="item.id"
              >
                <span>{{ item.name }} &nbsp;<i v-if="item.is_master" class="el-icon-success" style="color:#2e6b2e;" /></span>
              </el-option>
            </el-select>

            <el-checkbox v-model="noMaster" @change="onChangeInput"><span style="font-size: 90%;">Menambahkan Komponen Kegiatan Lain Sekitar</span></el-checkbox>

            <el-input
              v-if="noMaster"
              v-model="data.name"
              placeholder="Nama Komponen Kegiatan Lain Sekitar..."
            />
          </el-form-item>
          <div v-else>
            <div><strong>Kegiatan Lain Sekitar</strong></div>
            <div style="font-size:120%; color:#202020;">
              {{ data.name }} &nbsp;<span><i v-if="data.is_master" class="el-icon-success" style="color:#2e6b2e;" /></span>
            </div>
          </div>
          <div style="font-weight:bold;margin:1.5em 0 0.5em;">Lokasi</div>
          <div style="padding: 1em 1.5em; border:1px solid #e0e0e0; border-radius: 0.3em; margin-bottom:1em;">
            <el-row :gutter="20">
              <el-col :span="12">
                <el-form-item label="Provinsi">
                  <el-select
                    v-model="data.province_id"
                    style="width:100%"
                    clearable
                    filterable
                    :loading="loadingProvinces"
                    loading-text="Memuat data..."
                    placeholder="Pilih Provinsi"
                    @change="onSelectProvince"
                  >
                    <el-option
                      v-for="item in provinces"
                      :key="item.value"
                      :label="item.name"
                      :value="item.id"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Kabupaten/Kota">
                  <el-select
                    v-model="data.district_id"
                    style="width:100%"
                    clearable
                    filterable
                    :loading="loadingDistricts"
                    loading-text="Memuat data..."
                    placeholder="Pilih Kabupaten/Kota"
                  >
                    <el-option
                      v-for="item in districts"
                      :key="item.value"
                      :label="item.name"
                      :value="item.id"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            <el-form-item label="Alamat" prop="address">
              <el-input
                v-model="data.address"
                type="textarea"
                :autosize="{ minRows: 3, maxRows: 5}"
                maxlength="256"
                show-word-limit
              />
            </el-form-item>
          </div>
          <el-form-item label="Deskripsi" prop="description">
            <div v-if="isReadOnly && !isUrlAndal">
              <span v-html="master_kegiatan_lain_scoping_113" />
            </div>
            <div v-else>
              <klseditor
                :key="'master_kegiatan_lain_scoping_113'"
                v-model="data.description"
                output-format="html"
                :menubar="''"
                :image="false"
                :height="100"
                :toolbar="['bold italic underline bullist numlist  preview undo redo fullscreen']"
                style="width:100%"
              />
            </div>
          </el-form-item>
          <el-form-item label="Besaran" prop="measurement">
            <el-input
              v-model="data.measurement"
              type="textarea"
              :disabled="isReadOnly && !isUrlAndal"
              :autosize="{ minRows: 3, maxRows: 5}"
            />
          </el-form-item>
        </el-form>
      </div>
      <span slot="footer" class="dialog-footer">
        <el-button type="default" @click="handleClose">Batal</el-button>
        <el-button type="primary" :disabled="disableSave() || isReadOnly && !isUrlAndal" @click="!isReadOnly && isUrlAndal, handleSaveForm()">Simpan</el-button>
      </span>
    </el-dialog>
  </div>
</template>
<script>
import { mapGetters } from 'vuex';
import KLSEditor from '@/components/Tinymce';
import Resource from '@/api/resource';
const klsResource = new Resource('kegiatan-lain-sekitar');
const provinceResource = new Resource('provinces');
const districtResource = new Resource('districts');
const projectKSLResource = new Resource('project-kegiatan-lain-sekitar');

export default {
  name: 'FormKomponenKegiatanLainSekitar',
  components: {
    'klseditor': KLSEditor,
  },
  props: {
    show: {
      type: Boolean,
      default: true,
    },
    mode: {
      type: Number,
      default: 0,
    },
    input: {
      type: Object,
      default: () => null,
    },
    formMode: {
      type: Number,
      default: 0,
    },
  },
  data(){
    return {
      isSaving: false,
      data: null,
      noMaster: false,
      loadingMaster: false,
      loadingProvinces: false,
      loadingDistricts: false,
      provinces: [],
      districts: [],
      master: [],
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
  mounted(){
    this.initData();
    this.getMaster();
    this.getProvinces();
  },
  methods: {
    initData(){
      this.data = {
        id: null,
        name: '',
        value: '',
        description: '',
        measurement: '',
        is_master: false,
        province_id: null,
        district_id: null,
        address: '',
        originator_id: null,
        id_project: parseInt(this.$route.params && this.$route.params.id),
        id_project_kegiatan_lain_sekitar: null,
      };
      this.noMaster = false;
    },
    async getMaster(){
      this.loadingMaster = false;
      this.master = [];
      await klsResource.list({
        id_project: parseInt(this.$route.params && this.$route.params.id),
      }).then((res) => {
        this.master = res.data;
      }).finally(() => {
        this.loadingMaster = false;
      });
    },
    async getProvinces(){
      this.loadingProvinces = true;
      await provinceResource.list()
        .then((res) => {
          this.provinces = res.data;
        }).finally(() => {
          this.loadingProvinces = false;
        });
    },
    async getDistricts(){
      this.loadingDistricts = true;
      await districtResource.list({
        idProv: this.data.province_id,
      })
        .then((res) => {
          this.districts = res.data;
        })
        .finally(() => {
          this.loadingDistricts = false;
        });
    },
    async handleSaveForm(){
      this.isSaving = true;
      this.data.mode = this.mode;
      await projectKSLResource.store(this.data)
        .then((res) => {
          this.data.id_project_kegiatan_lain_sekitar = res.id;
          this.data.id = res.kegiatan_lain_sekitar_id;
          this.$emit('onSave', this.data);
        })
        .finally(() => {
          this.isSaving = false;
          this.handleClose();
        });
    },
    onOpen(){
      switch (this.formMode){
        case 0:
          this.initData();
          this.getMaster();
          break;
        case 1:
          // edit mode
          this.data = this.input;
          this.data.id_project = parseInt(this.$route.params && this.$route.params.id);
          this.getDistricts();
          break;
      }

      console.log(this.data);
    },
    handleClose(){
      this.$emit('onClose', true);
    },
    onSelectActivity(val){
      const activity = this.master.find(a => a.id === val);
      if (activity){
        this.data.name = activity.name;
        this.data.value = activity.name;
        this.data.is_master = activity.is_master;
        this.data.originator_id = activity.originator_id;
      }
    },
    onClearActivity(){
      this.data.name = '';
      this.data.value = '';
      this.data.is_master = false;
      this.data.originator_id = null;
      this.data.id = null;
    },
    onSelectProvince(sel){
      this.data.district_id = null;
      this.districts = [];
      if (sel !== ''){
        this.getDistricts();
      }
    },
    onChangeDistrict(val){

    },
    disableSave(){
      const emptyTexts = (this.data.description === null) ||
        ((this.data.description).trim() === '') ||
        (this.data.measurement === null) ||
        ((this.data.measurement).trim() === '');
      const emptyAddress = (this.data.address === null) ||
        ((this.data.address).trim() === '') ||
        (this.data.province_id === null) ||
        (this.data.district_id === null);

      if (this.noMaster){
        return ((this.data.name).trim() === '') || emptyAddress || emptyTexts;
      }
      return (this.data.id === null) || (this.data.id <= 0) || emptyAddress || emptyTexts;
    },
  },
};
</script>
<style scoped>
  ::v-deep p {
  text-align: left !important;
}
</style>
