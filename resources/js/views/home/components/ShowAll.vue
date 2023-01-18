<template>
  <div>
    <div v-if="showFromAll">
      <el-row :gutter="20">
        <el-col :span="8" :offset="16">
          <div class="customFilter">
            <div class="customFilterLeft">
              <span style="font-weight:bold; display:inline-block; margin-right:0.5rem;">Search</span>
              <el-input v-model="keyword" placeholder="Please input" @keyup.native.enter="handleSearch()" />
            </div>
            <div :class="toggle ? 'customFilterRight filter bgNoActive' : 'customFilterRight filter bgActive'">
              <template v-if="toggle">
                <div style="display:flex; align-items:center;" @click="toggle = !toggle">
                  <img alt="" src="/images/filter.png">
                  <span class="textFilter textFilterNoAvtive">Filter</span>
                </div>
              </template>
              <template v-else>
                <div style="display:flex; align-items:center;" @click="handleShowFilter()">
                  <img alt="" src="/images/filter-white.png">
                  <span class="textFilter textFilterAvtive">Filter</span>
                </div>
              </template>
              <div v-if="toggle" class="customFilterRightWrap">
                <i class="el-icon-circle-close" style="position: absolute;left: 4px;top: 4px;font-size: 22px;cursor: pointer;" @click="handleHideFilter()" />
                <div class="cardCustom">
                  <span>Pusat <el-radio v-model="radio" label="1" /></span>
                </div>
                <div class="cardCustom">
                  <span>Provinsi <el-radio v-model="radio" label="2" /></span>
                  <el-select v-model="provinsiValue" placeholder="Select" @change="getCity($event)">
                    <el-option
                      v-for="item in provinsi"
                      :key="item.value"
                      :label="item.name"
                      :value="item.name"
                    />
                  </el-select>
                </div>
                <div class="cardCustom">
                  <span>Kab. / Kota <el-radio v-model="radio" label="3" /></span>
                  <el-select v-model="kotaValue" placeholder="Select">
                    <el-option
                      v-for="item in kota"
                      :key="item.value"
                      :label="item.name"
                      :value="item.name"
                    />
                  </el-select>
                </div>
                <div class="cardCustom">
                  <span>Urut Berdasarkan <el-radio v-model="radio" label="4" /></span>
                  <el-select v-model="urutValue" placeholder="Select">
                    <el-option
                      v-for="item in urut"
                      :key="item.value"
                      :label="item.label"
                      :value="item.value"
                    />
                  </el-select>
                </div>
                <div class="cardCustom">
                  <button style="padding:0.5rem 2rem; background:#f38c13; color:#fff; margin:auto; display:block;border-radius: 0.7rem; margin-top:0.5rem;" @click="handleFilter()">Simpan<br>Filter</button>
                </div>
              </div>
            </div>
          </div>
        </el-col>
      </el-row>
      <template v-if="allData.length > 0">
        <div v-loading="loading">
          <el-row v-for="amdal in allData" :key="amdal.id" :gutter="20" class="wrapOutside">
            <el-col :xs="24" :sm="2" style="padding-top:0.5rem;padding-bottom:0.5rem">
              <el-image
                style="width: 50px; height: 50px;"
                :src="checkApplicantLogo(amdal.project.initiator)"
                fit="contain"
              >
                <div slot="error" class="image-slot">
                  <i class="el-icon-picture-outline" style="font-size: 200%; line-height: 130%;" />
                </div>
              </el-image>
              <!-- <img alt="" class="customImage" src="amdal.project.logo"> -->
            </el-col>
            <el-col :xs="24" :sm="6" style="padding-top:1rem">
              <p class="tw fz11 fw">{{ amdal.project_type }}, {{ amdal.project && amdal.project.province ? amdal.project.province.name : '' }} {{ amdal.pic_name }}</p>
            </el-col>
            <el-col :xs="24" :sm="8" style="padding-top:1rem">
              <p class="tw fz11">Dampak Potensial: {{ amdal.potential_impact }}</p>
            </el-col>
            <el-col :xs="24" :sm="5" style="padding-top:1rem">
              <p class="tw fz11">{{ formatDateStr(amdal.start_date) }} - {{ formatDateStr(amdal.end_date) }}</p>
            </el-col>
            <el-col :xs="24" :sm="3">
              <el-button :type="amdal.expired? 'info' : 'warning'" :disabled="amdal.expired || amdal.is_done" size="medium" :style="'margin-top: 0.5em; ' + (amdal.expired?'color: #666;opacity:0.5;':'')" @click="openDetails(amdal.id)">Berikan <br>Tanggapan</el-button>
              <!-- <button :class="'el-button'+ (amdal.expired? 'el-button--default' : ' el-button--warning ') +' el-button--medium is-plain fz11  el-button--medium is-plain'" :disabled="amdal.expired" @click="openDetails(amdal.id)">Berikan<br>Tanggapan</button>-->
            </el-col>
          </el-row>
        </div>
        <div class="block" style="text-align:right">
          <pagination
            v-if="paginationNoFilter"
            v-show="total > 0"
            :auto-scroll="false"
            :total="total"
            :page.sync="listQuery.page"
            :limit.sync="listQuery.limit"
            @pagination="getAll"
          />
          <pagination
            v-if="paginationFilter"
            v-show="total > 0"
            :auto-scroll="false"
            :total="total"
            :page.sync="listQuery.page"
            :limit.sync="listQuery.limit"
            @pagination="getAll"
          />
        </div>
      </template>
      <template v-else-if="!loading">
        <el-row :gutter="20">
          <el-col :xs="24" style="padding-top:0.5rem;padding-bottom:0.5rem">
            <el-alert
              title="Warning"
              type="warning"
              description="No Record Found"
            />
          </el-col>
        </el-row>
      </template>
    </div>
    <div v-if="showDetailFromAll">
      <Details
        :selected-announcement="selectedAnnouncement"
        :selected-project="selectedProject"
        @handleCancelComponent="handleCancelComponent"
        @handleSetTabs="handleSetTabs"
      />
    </div>
  </div>
</template>
<script>
import axios from 'axios';
import Pagination from '@/components/Pagination';
import Details from './Details';

export default {
  name: 'ShowAll',
  components: {
    Pagination,
    Details,
  },
  data() {
    return {
      form: {
        name: '',
        sort: '',
        delivery: false,
        type: [],
        resource: '',
        desc: '',
      },
      allData: [],
      total: 0,
      listQuery: {
        page: 1,
        limit: 10,
      },
      paginationNoFilter: true,
      paginationFilter: false,
      keyword: 'AMDAL',
      showDetailFromAll: false,
      showFromAll: true,
      selectedAnnouncement: {},
      selectedProject: {},
      radio: '1',
      value: '',
      provinsi: {},
      provinsiValue: null,
      kotaValue: null,
      urutValue: null,
      kota: {},
      urut: [
        {
          value: 'ASC',
          label: 'ASC',
        },
        {
          value: 'DESC',
          label: 'DESC',
        },
      ],
      toggle: false,
      loading: false,
    };
  },
  created() {
    this.getAll();
    this.getProvinces();
  },
  methods: {
    formatDateStr(date) {
      const today = new Date(date);
      var bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
      const year = today.getFullYear();
      const month = today.getMonth();
      const day = today.getDate();
      const monthID = bulan[month];
      const finalDate = `${day} ${monthID} ${year}`;
      return finalDate;
    },
    getAll(search, sort) {
      if (search === 'undefined'){
        search = '';
      } else {
        search = `${this.keyword}`;
      }
      if (sort === 'undefined'){
        sort = `&sort=DESC`;
      } else {
        sort = `&sort=${this.form.sort}`;
      }
      // this.allData = [];
      this.loading = true;
      axios.get(`/api/announcements?keyword=${this.keyword}&page=${this.listQuery.page}&active=1`)
        .then(response => {
          this.allData = response.data.data;
          this.total = response.data.total;
          this.paginationNoFilter = true;
          this.paginationFilter = false;
        }).finally(() => {
          this.loading = false;
        });
    },
    openDetails(id) {
      axios.get('/api/announcements/' + id)
        .then(response => {
          // this.$emit('handleToggleShow',true);
          this.selectedAnnouncement = response.data;
          this.selectedProject = response.data.project;
          this.showDetailFromAll = true;
          this.showFromAll = false;
          // console.log(this.showDetailFromAll)
        });
    },
    handleCancle(){
      this.$emit('handleCancle');
    },
    handleSearch(){
      this.getAll(this.keyword);
    },
    handleChange(){
      this.getAll(this.form.sort);
    },
    handleCancelComponent(){
      this.showDetailFromAll = false;
      this.showFromAll = true;
    },
    handleSetTabs(e){
      this.showDetailFromAll = false;
      this.showFromAll = true;
    },
    handleShowFilter(){
      this.toggle = true;
    },
    handleHideFilter(){
      this.toggle = false;
    },
    getProvinces() {
      axios.get('/api/provinces')
        .then(response => {
          this.provinsi = response.data.data;
        });
    },
    getCity(event) {
      axios.get(`/api/districts?provName=${event}`)
        .then(response => {
          this.kota = response.data.data;
        });
    },
    handleFilter() {
      if (this.provinsiValue !== null || this.provinsiValue !== 'undefined') {
        var provName = 'provName=' + this.provinsiValue;
      }

      if (this.kotaValue !== null || this.kotaValue !== 'undefined') {
        var kotaName = 'kotaName=' + this.kotaValue;
      }

      if (this.urutValue !== null || this.urutValue !== 'undefined') {
        var urutValue = 'sort=' + this.urutValue;
      }
      this.loading = true;
      axios.get(`/api/announcement-by-filter?${provName}&${kotaName}&page=${this.listQuery.page}&${urutValue}`)
        .then(response => {
          this.allData = response.data.data;
          this.total = response.data.total;
          this.paginationNoFilter = false;
          this.paginationFilter = true;
          this.toggle = false;
        }).finally(() => {
          this.loading = false;
        });
    },
    checkApplicantLogo(initiator) {
      if (initiator.logo) {
        return initiator.logo;
      } else if (initiator.user) {
        if (initiator.user.avatar) {
          return initiator.user.avatar;
        }
      }

      return null;
    },
  },
};
</script>
<style>
  .wrapOutside{padding:0 0.5rem; background:#062307; border-radius: 5px; align-items:end; border-bottom:1px solid #fff;}
  .wrapOutside.el-row:hover{background: #184d1a;}
  .wrapImage{width:100%; height:150px}
  .imgCompany{width:100%; height:100%; object-fit: contain; object-position: center;}
  .tw{color:#fff}
  .to{color:#f6993f}
  .fw-bold{font-weight:bold}
  .buttonCustom{background: transparent;font-weight: bold;}
  .mt-05{margin-top:1.5rem}
  .mt-2-5{margin-top:2.5rem}
  .wrapButton{display:flex; margin:6rem 0 2rem 0}
  .el-form-item.el-form-item--medium{margin-bottom:10px}
  .wrapSelect{width: 60%;margin-left: auto;}
  .el-pager li{background:transparent; color:#eb933c;}
  .el-pagination .btn-prev, .el-pagination .btn-next{color: #f6993f;background-color: transparent;}
  .el-pagination button:disabled {background-color: transparent;color: #f6993f;}
  .el-button--warning.is-plain {background:#F6993F;color: #000;background: #13ce66;border-color: #13ce66; margin-bottom:0.5rem; margin-top:3.5rem; margin-right: 0.5rem;}
  .pagination-container[data-v-5efa73f0] {background: transparent;padding: 0;}
  .el-pagination span:not([class*="suffix"]), .el-pagination button {color: #fff;}
  .el-button.el-button--info.fw-bold.el-button--medium {margin-bottom: 10px;}
  .customFilter{display:flex; align-items: center;}
  .customFilterLeft{width:75%; display:flex; align-items: center; background: #062307; padding: 0.51rem;font-size: 0.9rem;font-weight: 600;}
  .customFilterRight{cursor:pointer; padding: 0.6rem; width:25%; display:flex; align-items:center; position:relative;}
  .customFilterRightWrap{display: flex;position: absolute;top: 3rem;width: 50rem;right: 0;z-index: 99; background:#dff5cf; color:#35442f;padding: 1.5rem 1rem;border-radius: 4px 0 4px 4px;}
  .cardCustom span{font-size: 11px;font-weight: bold;}
  .bgNoActive{background:#dff5cf; color:#35442f;}
  .bgActive{background:#062307; color: #fff;}
  .textFilter{font-size: 0.9rem;font-weight: 600;display:inline-block; margin-right:0.5rem; }
  .textFilterNoAvtive{color:#ef8913;}
  .textFilterAvtive{color:#fff;}
  .customImage{width: 50px;height: 50px;border-radius: 50% !important;object-fit: contain;object-position: center;margin: auto; display: block;}
  .fz11{font-size: 11px;}
  .fw{font-weight: bold;}
  .el-button.el-button--warning.el-button--medium.is-plain{margin-left: 0; margin-top: 5px;}
</style>
