<template>
  <div class="app-container" style="padding: 24px">

    <el-card>
      <div class="filter-container">
        <el-row type="flex" class="row-bg" justify="space-between">
          <el-col>
            <el-button
              v-if="couldCreateProject && !isScoping && !isDigiWork && !isLoginFromOSS"
              :disabled="addLoading"
              class="filter-item"
              type="primary"
              icon="el-icon-plus"
              @click="handleCreate"
            >
              {{ ' Kegiatan' }}
            </el-button>
            <el-button
              v-if="isLoginFromOSS"
              :disabled="addLoading"
              class="filter-item"
              type="primary"
              icon="el-icon-plus"
              @click="handleCreateOSS"
            >
              {{ ' Kegiatan' }}
            </el-button>
          </el-col>
          <el-col :span="10">
            <el-input v-model="listQuery.search" prefix-icon="el-icon-search" placeholder="Pencarian" :readonly="loading" @keyup.enter.native="handleFilter">
              <el-button slot="append" icon="el-icon-close" :disabled="loading" @click="resetSearch" />
            </el-input>

            <!-- <el-button
              class="filter-item"
              type="primary"
              icon="el-icon-search"
              @click="handleFilter"
            >
              {{ $t('table.search') }}
            </el-button> -->
          </el-col>
        </el-row>
      </div>
      <el-table
        v-loading="loading"
        :data="filtered"
        fit
        :clear-filter="onClearFilter"
        highlight-current-row
        :header-cell-style="{ background: '#216221', color: 'white' }"
        style="width: 100%"
        @sort-change="onTableSort"
      >
        <el-table-column type="expand" class="row-detail">
          <template slot-scope="scope">
            <div class="post">
              <div class="entity-block">
                <img
                  class="img-circle"
                  :src="checkAvatar(scope.row.initiator) || 'no-image.png'"
                  @error="$event.target.src='no-image.png'"
                >
                <span class="name text-muted">
                  <a href="#">{{ scope.row.applicant }}</a>
                  <a
                    href="#"
                    class="pull-right btn-box-tool"
                  >
                    <i class="fa fa-times" />
                  </a>
                </span>
                <span class="description">{{ scope.row.address.length > 0 ? scope.row.address[0].district : scope.row.district }}
                </span>
              </div>
              <span class="action pull-right">
                <el-button
                  v-if="isInitiator && !isScoping && !isDigiWork && (scope.row.required_doc !== 'SPPL') && checkInitiatorAndRisk(scope.row)"
                  type="text"
                  href="#"
                  icon="el-icon-user"
                  @click="handleTimPenyusunForm(scope.row.id)"
                >
                  Tim Penyusun
                </el-button>
                <el-button
                  v-if="!scope.row.published && isInitiator && scope.row.required_doc !== 'SPPL' && checkInitiatorAndRisk(scope.row)"
                  type="text"
                  href="#"
                  icon="el-icon-tickets"
                  @click="handlePublishForm(scope.row.id)"
                >
                  Publish
                </el-button>
                <!-- <el-button
                  v-if="!scope.row.published && isInitiator"
                  type="text"
                  href="#"
                  icon="el-icon-edit"
                  @click="handleEditForm(scope.row.id)"
                >
                  Edit
                </el-button> -->
                <!-- <el-button
                  v-if="!scope.row.published && isInitiator"
                  type="text"
                  href="#"
                  icon="el-icon-delete"
                  @click="handleDelete(scope.row.id, scope.row.project_title)"
                >
                  Delete
                </el-button> -->
                <el-button
                  v-if="(couldViewProject || isExaminer || isChief || isSecretary || isSubtance || isAdmin) && !isScoping && !isDigiWork"
                  href="#"
                  type="text"
                  icon="el-icon-view"
                  @click="handleViewForm(scope.row.id)"
                >
                  Lihat Detil Penapisan
                </el-button>
                <el-button
                  v-if="(scope.row.published && (isInitiator || tukAccess(scope.row, 'valsub') || isPJM(scope.row)) && !isScoping && !isDigiWork && scope.row.required_doc !== 'SPPL')"
                  href="#"
                  type="text"
                  icon="el-icon-view"
                  @click="handleViewSpt(scope.row.announcementId, scope.row.id)"
                >
                  Lihat SPT
                </el-button>
                <el-button
                  v-if="isLpjp"
                  href="#"
                  type="text"
                  icon="el-icon-user"
                  @click="handleLpjpTeam(scope.row)"
                >
                  Tim LPJP
                </el-button>
                <el-button
                  v-if="isAmdal(scope.row) && (isFormulator || (tukAccess(scope.row, 'valsub') && isInvitationSent(scope.row, 'ka')) || testInvited(scope.row, 'ka')) && !isScreening && !isDigiWork && !isInitiator && !isLpjp"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleKerangkaAcuan(scope.row, scope.row.marking)"
                >
                  Formulir Kerangka Acuan
                </el-button>
                <el-button
                  v-if="isUklUpl(scope.row) && (isFormulator || (tukAccess(scope.row, 'valsub') && isInvitationSent(scope.row, 'ukl-upl')) || testInvited(scope.row, 'ukl-upl')) && !isScreening && !isDigiWork && !isLpjp && !isInitiator"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleKerangkaUklUpl(scope.row, scope.row.perbaikan)"
                >
                  Formulir UKL UPL
                </el-button>
                <el-button
                  v-if="isAmdal(scope.row) && (isDocumentSubmitted(scope.row, 'ka') && (isInitiator || isFormulator)) && !isScreening && !isDigiWork"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleDokumenKA(scope.row)"
                >
                  Dokumen Kerangka Acuan
                </el-button>
                <el-button
                  v-if="isUklUpl(scope.row) && ((isFormulator && scope.row.ukl_upl_document) || (isDocumentSubmitted(scope.row, 'ukl-upl') && isInitiator)) && !isScreening && !isDigiWork"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleDokumenUklUpl(scope.row, scope.row.marking)"
                >
                  Dokumen UKL UPL
                </el-button>
                <el-button
                  v-if="isAmdal(scope.row) && ((isFormulator && isMeetReportKaCreated(scope.row)) || (tukAccess(scope.row, 'valsub') && isInvitationSent(scope.row, 'rkl-rpl')) || testInvited(scope.row, 'rkl-rpl')) && !isScreening && !isDigiWork && !isLpjp && !isInitiator"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleAndal(scope.row, scope.row.perbaikan)"
                >
                  Andal
                </el-button>
                <el-button
                  v-if="isAmdal(scope.row) && ((isFormulator && isMeetReportKaCreated(scope.row)) || (tukAccess(scope.row, 'valsub') && isInvitationSent(scope.row, 'rkl-rpl')) || testInvited(scope.row, 'rkl-rpl')) && !isScreening && !isDigiWork && !isLpjp && !isInitiator"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleRklRpl(scope.row, scope.row.perbaikan)"
                >
                  RKL/RPL
                </el-button>
                <el-button
                  v-if="isAmdal(scope.row) && ((isDocumentSubmitted(scope.row, 'andal-rkl-rpl') && isInitiator) || (scope.row.rkl_rpl_document && isFormulator)) && !isScreening && !isDigiWork"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleDokumenAndalRklRpl(scope.row, scope.row.marking)"
                >
                  Dokumen ANDAL RKL RPL
                </el-button>
                <el-button
                  v-if="isUklUpl(scope.row) && (isFormulator || (tukAccess(scope.row, 'valsub') && isInvitationSent(scope.row, 'ukl-upl')) || testInvited(scope.row, 'ukl-upl')) && !isScreening && !isDigiWork && !isLpjp && !isInitiator"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleMatUklUpl(scope.row, scope.row.perbaikan)"
                >
                  Matriks UKL/UPL
                </el-button>
                <el-button
                  v-if="isAmdal(scope.row) && (tukAccess(scope.row, 'valadm') && isDocumentReviewed(scope.row, 'ka'))"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleUjiAdmKa(scope.row)"
                >
                  Uji Berkas Administrasi KA
                </el-button>
                <el-button
                  v-if="isAmdal(scope.row) && (tukAccess(scope.row, 'valsub') && isInvitationSent(scope.row, 'ka'))"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleBeritaAcaraKa(scope.row)"
                >
                  Berita Acara KA
                </el-button>
                <el-button
                  v-if="isAmdal(scope.row) && (tukAccess(scope.row, 'valadm') && isDocumentReviewed(scope.row, 'andal-rkl-rpl'))"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleUjiAdmAndalRklRpl(scope.row)"
                >
                  Uji Berkas Administrasi Andal RKL RPL
                </el-button>
                <el-button
                  v-if="isAmdal(scope.row) && (tukAccess(scope.row, 'valsub') && isInvitationSent(scope.row, 'rkl-rpl'))"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleBeritaAcaraAndalRklRpl(scope.row)"
                >
                  Berita Acara Andal RKL RPL
                </el-button>
                <el-button
                  v-if="isUklUpl(scope.row) && (tukAccess(scope.row, 'valadm') && isDocumentReviewed(scope.row, 'ukl-upl'))"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleUjiAdmUKLUPL(scope.row)"
                >
                  Uji Berkas Administrasi
                </el-button>
                <el-button
                  v-if="isUklUpl(scope.row) && (tukAccess(scope.row, 'valsub') && isInvitationSent(scope.row, 'ukl-upl'))"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleBeritaAcaraUKLUPL(scope.row)"
                >
                  Berita Acara
                </el-button>
                <el-button
                  v-if="isAmdal(scope.row) && isValAdmOrAddByAdmin(scope.row) && isMeetReportAccepted(scope.row)"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleUjiKelayakan(scope.row)"
                >
                  Uji Kelayakan
                </el-button>
                <!-- <el-button
                  v-if="isFormulator"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleFlowChart(scope.row)"
                >
                  Bagan Alir
                </el-button> -->
                <el-button
                  v-if="isAmdal(scope.row) && ((isFormulator && isFormKaComplete(scope.row)) || (tukAccess(scope.row, 'valsub') && isInvitationSent(scope.row, 'ka')) || testInvited(scope.row, 'ka')) && !isScreening && !isScoping && !isLpjp"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleWorkspaceKa(scope.row, scope.row.marking)"
                >
                  Workspace KA
                </el-button>
                <el-button
                  v-if="isAmdal(scope.row) && ((isFormulator && isAndalFormComplete(scope.row)) || (tukAccess(scope.row, 'valsub') && isInvitationSent(scope.row, 'rkl-rpl')) || testInvited(scope.row, 'rkl-rpl')) && !isScreening && !isScoping && !isLpjp"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleWorkspaceAndal(scope.row.id, scope.row.marking)"
                >
                  Workspace Andal
                </el-button>
                <el-button
                  v-if="isAmdal(scope.row) && ((isFormulator && isRklRplFormComplete(scope.row)) || (tukAccess(scope.row, 'valsub') && isInvitationSent(scope.row, 'rkl-rpl')) || testInvited(scope.row, 'rkl-rpl')) && !isScreening && !isScoping && !isLpjp"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleWorkspaceRKLRPL(scope.row.id, scope.row.marking)"
                >
                  Workspace RKL RPL
                </el-button>
                <el-button
                  v-if="isUklUpl(scope.row) && scope.row.version_workspace !== null && ((isFormulator && scope.row.ukl_upl_document) || (tukAccess(scope.row, 'valsub') && isInvitationSent(scope.row, 'ukl-upl')) || testInvited(scope.row, 'ukl-upl')) && !isScreening && !isScoping && !isLpjp"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleWorkspaceUKLUPL(scope.row.id, scope.row.marking)"
                >
                  Workspace UKL UPL
                </el-button>
                <el-button
                  v-if="isInitiator && !isScoping && !isDigiWork && isPemerintah"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleGenerateSPPLPemerintahNew(scope.row)"
                >
                  Unduh SPPL
                </el-button>
                <el-button
                  v-if="isInitiator && !isScoping && !isDigiWork && !isPemerintah && scope.row.required_doc === 'SPPL'"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleDownloadSPPLFromOSS(scope.row)"
                >
                  Unduh SPPL
                </el-button>
                <!-- <el-button
                  v-if="isInitiator && !isScoping && !isDigiWork && scope.row.required_doc === 'UKL-UPL' && scope.row.result_risk === 'Menengah Rendah' && scope.row.oss_risk === null || scope.row.oss_risk !== 'Tinggi') && !isPemerintah"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleDownloadPKPLHFromOSS(scope.row)"
                >
                  Unduh PKPLH Otomatis
                </el-button> -->
                <el-button
                  v-if="isAmdal(scope.row) && isPJM(scope.row) && isMeetReportAccepted(scope.row)"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleRekomendasiUjiKelayakan(scope.row)"
                >
                  Surat Rekomendasi Uji Kelayakan
                </el-button>
                <el-button
                  v-if="isAmdal(scope.row) && isFeasib(scope.row) && (isInitiator || tukAccess(scope.row, 'valadm') || tukAccess(scope.row, 'valsub'))"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handleFeasibilityTest(scope.row.id)"
                >
                  SKKL
                </el-button>
                <el-button
                  v-if="isUklUpl(scope.row) && isMeetReportAccepted(scope.row) && (isInitiator || tukAccess(scope.row, 'valadm') || tukAccess(scope.row, 'valsub'))"
                  href="#"
                  type="text"
                  icon="el-icon-document"
                  @click="handlePKPLH(scope.row)"
                >
                  PKPLH
                </el-button>
              </span>
              <p class="title"><b>{{ scope.row.project_title }} ({{ scope.row.required_doc }})</b></p>
              <span v-html="scope.row.description" />
            </div>
          </template>
        </el-table-column>
        <el-table-column label="No." width="54px">
          <template slot-scope="scope">
            <span>{{ ((listQuery.page-1) * listQuery.limit) + (scope.$index + 1) }}</span>
          </template>
        </el-table-column>
        <el-table-column prop="registration_no" align="left" label="No. Registrasi" width="150" sortable="custom">
          <template slot-scope="scope">
            <span>{{ scope.row.registration_no ? scope.row.registration_no.toUpperCase() : '' }}</span>
          <!-- <span>{{
              scope.row.created_at | parseTime('{y}-{m}-{d} {h}:{i}')
            }}</span> -->
          </template>
        </el-table-column>
        <el-table-column
          prop="project_title"
          align="left"
          label="Nama Kegiatan"
          sortable="custom"
          min-width="200"
        >
          <template slot-scope="scope">
            <span>{{ scope.row.project_title }}</span>
          </template>
        </el-table-column>
        <el-table-column
          width="150px"

          column-key="doc_type"
          align="center"
          label="Dokumen"
        >

          <template slot="header">
            <el-select
              v-model="listQuery.filters"
              class="filter-header"
              clearable
              placeholder="Dokumen"
              @change="onDocTypeFilter"
            >
              <el-option
                v-for="item in [{text: 'AMDAL', value: 'AMDAL'}, {text: 'UKL-UPL', value: 'UKL-UPL'}, {text: 'SPPL', value: 'SPPL'}]"
                :key="item.value"
                :label="item.text"
                :value="item.value"
              />
            </el-select>
          </template>
          <template slot-scope="scope">
            <span>{{ scope.row.required_doc }}</span>
          </template>
        </el-table-column>
        <el-table-column align="left" label="Lokasi" width="200">
          <template slot-scope="scope">
            <span>{{ scope.row.address.length > 0 ? scope.row.address[0].district+'/ '+scope.row.address[0].prov : '' }}</span>
          </template>
        </el-table-column>
        <el-table-column v-if="isExaminer || isAdmin || isSubtance " label="Penugasan" width="200px" align="center">
          <template slot="header">
            <el-select
              v-model="listQuery.filterTUK"
              class="filter-header"
              clearable
              placeholder="Penugasan"
              @change="onTukFilter"
            >
              <el-option
                v-for="item in [{text: 'PJM', value: 'pjm'}, {text: 'Substansi', value: 'valsub'}, {text: 'Administrasi', value: 'valadm'}, {text: 'Pengujian', value: 'pengujian'}]"
                :key="item.value"
                :label="item.text"
                :value="item.value"
              />
            </el-select>
          </template>
          <template slot-scope="scope">
            <div class="badge-penugasan">
              {{ tukRole(scope.row) }}
            </div>
          </template>
        </el-table-column>
        <el-table-column label="Tahap" class-name="status-col">
          <template slot-scope="scope">
            <div v-if="scope.row.marking === 'uklupl-mt.pkplh-published'">
              <span>
                Penerbitan PKPLH
              </span>
            </div>
            {{ scope.row.marking_label || projectStep }}
          </template>
        </el-table-column>
        <el-table-column
          prop="updated_at"
          align="center"
          label="Tanggal"
          sortable="custom"
          width="150px"
        >
          <template slot-scope="scope">
            <div style="line-height: 1.1em;">
              <span>{{ scope.row.updated_at | parseTime('{y}-{m}-{d}') }}</span>
              <!-- <br>
              <span style="font-size:86%">{{ scope.row.created_at | parseTime('{h}:{i}') }}</span> -->
            </div>
          </template>
        </el-table-column>
      </el-table>
      <pagination
        v-show="total > 0"
        :total="total"
        :page.sync="listQuery.page"
        :limit.sync="listQuery.limit"
        @pagination="handleFilter"
      />
      <AnnouncementDialog
        :announcement="announcement"
        :show="show"
        :loading="loadingAnnouncement"
        @handleSubmitAnnouncement="handleSubmitAnnouncement($event)"
        @handleCancelAnnouncement="show = false"
      />
    </el-card>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import Resource from '@/api/resource';
import Pagination from '@/components/Pagination';
import AnnouncementDialog from './components/AnnouncementDialog.vue';
import Docxtemplater from 'docxtemplater';
import PizZip from 'pizzip';
import PizZipUtils from 'pizzip/utils/index.js';
import { saveAs } from 'file-saver';
import axios from 'axios';
import { isOSS } from '@/utils/auth';
const initiatorResource = new Resource('initiatorsByEmail');
const provinceResource = new Resource('provinces');
const districtResource = new Resource('districts');
const projectResource = new Resource('projects');
const announcementResource = new Resource('announcements');
const lpjpResource = new Resource('lpjpsByEmail');
const formulatorResource = new Resource('formulatorsByEmail');
const andalComposingResource = new Resource('andal-composing');
const rklResource = new Resource('matriks-rkl');
const skklResource = new Resource('skkl');
const authoritiesResource = new Resource('project-authorities');
const pdfResource = new Resource('generatepdf');

export default {
  name: 'Project',
  components: { Pagination, AnnouncementDialog },
  filters: {
    projectStep: function(value) {
      if (value === 'screening-completed'){
        return 'Selesai Penapisan';
      } else if (value === 'formulator-assignment'){
        return 'Penetapan Penyusun';
      } else if (value === 'announcement-completed'){
        return 'Sudah Diumumkan';
      } else {
        return value;
      }
    },
  },
  data() {
    return {
      loading: true,
      loadingAnnouncement: false,
      addLoading: false,
      searchString: '',
      sectionHeader: 'list-penapisan',
      // userInfo: {
      //   roles: [],
      // },
      filtered: [],
      initiator: {},
      announcement: {},
      show: false,
      total: 0,
      listQuery: {
        page: 1,
        limit: 10,
        orderBy: 'id',
        order: 'DESC',
        filters: '',
        filterTUK: '',
      },
      provinceOptions: [],
      cityOptions: [],
      documentTypeOptions: [
        { value: 'Tinggi', label: 'Amdal' },
        { value: 'Menengah Tinggi', label: 'UKL-UPL MT' },
        { value: 'Menengah Rendah', label: 'UKL-UPL MR' },
        { value: 'Rendah', label: 'SPPL' },
      ],
      isPemerintah: false,
    };
  },
  computed: {
    ...mapGetters({
      'userInfo': 'user',
      'userId': 'userId',
      'markingStatus': 'markingStatus',
    }),
    couldCreateProject(){
      return this.$store.getters.permissions.includes('create project');
    },
    couldViewProject(){
      return this.$store.getters.permissions.includes('read project');
    },
    isLpjp() {
      return this.userInfo.roles.includes('lpjp');
    },
    isInitiator() {
      return this.userInfo.roles.includes('initiator');
    },
    isFormulator() {
      return this.userInfo.roles.includes('formulator');
    },
    isSubtance() {
      return this.userInfo.roles.includes('examiner-substance');
    },
    isAdmin() {
      return this.userInfo.roles.includes('examiner-administration');
    },
    isSecretary() {
      return this.userInfo.roles.includes('examiner-secretary');
    },
    isChief() {
      return this.userInfo.roles.includes('examiner-chief');
    },
    isExaminer() {
      return this.userInfo.roles.includes('examiner');
    },
    isScoping(){
      return this.$route.name === 'scopingProject';
    },
    isDigiWork(){
      return this.$route.name === 'digWorkProject';
    },
    isScreening(){
      return this.$route.name === 'screeningProject';
    },
    isLoginFromOSS() {
      return isOSS();
    },
  },
  async created() {
    // console.log('aaa', this.userInfo);
    // this.addLoading = true;
    this.getProvinces();
    // this.userInfo = await this.$store.dispatch('user/getInfo');
    if (this.userInfo.roles.includes('lpjp')){
      const lpjp = await lpjpResource.list({ email: this.userInfo.email });
      this.listQuery.lpjpId = lpjp.id;
    } else if (this.userInfo.roles.includes('initiator')) {
      const initiator = await initiatorResource.list({ email: this.userInfo.email });
      this.listQuery.initiatorId = initiator.id;
      this.initiator = initiator;
      await this.$store.dispatch('getInitiator', this.userInfo.email);
      if (this.$store.getters.isPemerintah){
        this.isPemerintah = true;
      }
    } else if (this.userInfo.roles.includes('formulator')) {
      const formulator = await formulatorResource.list({ email: this.userInfo.email });
      this.listQuery.formulatorId = formulator.id;
    } else if (
      this.userInfo.roles.includes('examiner-substance') ||
      this.userInfo.roles.includes('examiner-administration') ||
      this.userInfo.roles.includes('examiner-community') ||
      this.userInfo.roles.includes('examiner') ||
      this.userInfo.roles.includes('examiner-institution') ||
      this.userInfo.roles.includes('examiner-secretary')
    ) {
      this.listQuery.tuk = 'true';
      if (this.userInfo.roles.includes('examiner-secretary')) {
        this.listQuery.tukActivityProcess = 'true';
      }
    }
    // else if (this.userInfo.roles.includes('examiner-substance')) {
    //   const formulator = await formulatorResource.list({ email: this.userInfo.email });
    //   this.listQuery.formulatorId = formulator.id;
    // }

    this.getFiltered(this.listQuery);

    // load info project from oss
    console.log(this.$store);
    await this.loadInfoFromOss();
  },
  methods: {
    async loadInfoFromOss(){
      // await this.$store.dispatch('getOssByKbli', this.$store.getters.pemrakarsa[0].nib);
      // this.addLoading = false;
    },
    isUklUpl(project){
      return project.required_doc === 'UKL-UPL';
    },
    isAmdal(project){
      return project.required_doc === 'AMDAL';
    },
    isDocumentSubmitted(project, document) {
      if (project.ka_reviews) {
        if (project.ka_reviews.length > 0) {
          const reviews = project.ka_reviews.filter(x => {
            if (document === 'ka' || document === 'ukl-upl') {
              return x.document_type === document || x.document_type === null;
            } else {
              return x.document_type === document;
            }
          });
          if (reviews.length > 0) {
            return true;
          }
        }
      }

      return false;
    },
    isDocumentReviewed(project, document) {
      if (this.isDocumentSubmitted(project, document)) {
        const reviews = project.ka_reviews.filter(x => {
          if (document === 'ka' || document === 'ukl-upl') {
            return x.document_type === document || x.document_type === null;
          } else {
            return x.document_type === document;
          }
        });
        const status = reviews[reviews.length - 1].status;
        if (status === 'submit') {
          return true;
        }
      }

      return false;
    },
    isInvitationSent(project, document) {
      if (project.testing_meeting) {
        if (project.testing_meeting.length > 0) {
          const invitation = project.testing_meeting.find(x => {
            return x.document_type === document && Boolean(x.is_invitation_sent);
          });
          if (invitation) {
            return true;
          }
        }
      }
      return false;
    },
    isMeetReportKaCreated(project) {
      if (project.meeting_reports) {
        if (project.meeting_reports.length > 0) {
          return true;
        }
      }

      return false;
    },
    isMeetReportAccepted(project) {
      if (project.meeting_reports) {
        if (project.meeting_reports.length > 0) {
          const document = this.isAmdal(project) ? 'rkl-rpl' : 'ukl-upl';
          const is_accepted = project.meeting_reports.find(x => {
            return x.document_type === document && Boolean(x.is_accepted);
          });
          if (is_accepted) {
            return true;
          }
        }
      }

      return false;
    },
    isAndalFormComplete(project) {
      if (project.impact_identifications_clone) {
        if (project.impact_identifications_clone.length > 0) {
          const withEnvImpactAnalysis = project.impact_identifications_clone.filter(x => {
            return x.env_impact_analysis !== null;
          });
          if (withEnvImpactAnalysis.length > 0) {
            const completes = withEnvImpactAnalysis.filter(y => {
              return y.env_impact_analysis.condition_dev_no_plan !== null;
            });
            return withEnvImpactAnalysis.length === completes.length;
          }
        }
      }

      return false;
    },
    isRklRplFormComplete(project) {
      let envManagePlan = false;
      let envMonitorPlan = false;

      if (project.impact_identifications_clone) {
        if (project.impact_identifications_clone.length > 0) {
          const withEnvManagePlan = project.impact_identifications_clone.filter(x => {
            return x.env_manage_plan !== null;
          });
          if (withEnvManagePlan.length > 0) {
            const completes = withEnvManagePlan.filter(y => {
              return y.env_manage_plan.period !== null;
            });
            envManagePlan = Boolean(withEnvManagePlan.length === completes.length);
          }

          const withEnvMonitorPlan = project.impact_identifications_clone.filter(x => {
            return x.env_monitor_plan !== null;
          });
          if (withEnvMonitorPlan.length > 0) {
            const completes = withEnvMonitorPlan.filter(y => {
              return y.env_monitor_plan.time_frequent !== null;
            });
            envMonitorPlan = Boolean(withEnvMonitorPlan.length === completes.length);
          }
        }
      }

      return envManagePlan && envMonitorPlan;
    },
    tukAccess(project, roleAccount) {
      if (this.isSubtance || this.isAdmin || this.isSecretary || this.isExaminer) {
        if (project.tuk_project) {
          if (project.tuk_project.length > 0) {
            const role = project.tuk_project.find(x => {
              return x.id_user === this.userInfo.id;
            });

            if (role) {
              if (role.role === roleAccount || role.role === 'pjm') {
                return true;
              }
            }
          }
        }
      }

      return false;
    },
    isPJM(project) {
      if (this.isSubtance || this.isAdmin || this.isSecretary || this.isExaminer) {
        if (project.tuk_project) {
          if (project.tuk_project.length > 0) {
            const role = project.tuk_project.find(x => {
              return x.id_user === this.userInfo.id;
            });

            if (role) {
              if (role.role === 'pjm') {
                return true;
              }
            }
          }
        }
      }

      return false;
    },
    tukRole(project) {
      let roleByAdmin = null;

      if (project.tuk_project) {
        if (project.tuk_project.length > 0) {
          const role = project.tuk_project.find(x => {
            return x.id_user === this.userInfo.id;
          });

          if (role) {
            if (role.role === 'pjm') {
              roleByAdmin = 'PJM';
            } else if (role.role === 'valsub') {
              roleByAdmin = 'Substansi';
            } else if (role.role === 'valadm') {
              roleByAdmin = 'Administrasi';
            }
          }
        }
      }

      if (roleByAdmin !== null) {
        return roleByAdmin;
      } else {
        return 'Pengujian';
      }
    },
    isFeasib(project) {
      if (project.feasibility_test_recap) {
        if (project.feasibility_test_recap.is_feasib) {
          return true;
        }
      }

      return false;
    },
    testInvited(project, documentType) {
      if (project.testing_meeting) {
        if (project.testing_meeting.length > 0) {
          const meeting = project.testing_meeting.find(x => x.document_type === documentType);
          if (meeting) {
            return true;
          }
        }
      }

      return false;
    },
    isValAdmOrAddByAdmin(project) {
      if (this.isSubtance || this.isAdmin || this.isSecretary || this.isExaminer) {
        if (project.tuk_project) {
          if (project.tuk_project.length > 0) {
            const role = project.tuk_project.find(x => {
              return x.id_user === this.userInfo.id;
            });

            if (role) {
              if (
                role.id_feasibility_test_team_member !== null ||
                role.role === 'valadm' ||
                role.role === 'pjm'
              ) {
                return true;
              }
            }
          }
        }
      }

      return false;
    },
    toTitleCase(str) {
      return str.replace(
        /\w\S*/g,
        function(txt) {
          return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
        }
      );
    },
    handleSubmitAnnouncement(fileProof){
      this.loadingAnnouncement = true;
      // make form data because we got file
      const formData = new FormData();

      // eslint-disable-next-line no-undef
      _.each(this.announcement, (value, key) => {
        formData.append(key, value);
      });

      const message = (this.announcement.publish ? '' : 'Rancangan Pengumuman untuk ') +
        'Rencana Kegiatan ' + this.announcement.project_type +
        (this.announcement.publish ? ' Telah Dipublikasikan Di Daftar Pengumuman & Informasi Pada Halaman Utama Amdalnet' : ' Berhasil Disimpan');

      announcementResource
        .store(formData)
        .then((response) => {
          this.$message({
            message: message,
            type: 'success',
            duration: 5 * 1000,
          });
          this.getFiltered(this.listQuery);
          this.announcement = {};
          this.loadingAnnouncement = false;
          this.show = false;
        })
        .catch((error) => {
          console.log(error);
          this.loadingAnnouncement = false;
        });
    },
    handleCancelAnnouncement(){
      this.announcement = {};
      this.show = false;
    },
    handleFilter() {
      this.getFiltered(this.listQuery);
    },
    async getProvinces() {
      const { data } = await provinceResource.list({});
      this.provinceOptions = data.map((i) => {
        return { value: i.id, label: i.name };
      });
    },
    async getFiltered(query) {
      this.loading = true;
      this.filtered = [];
      // console.log('getFiltered', query);
      await projectResource.list(query)
        .then((res) => {
          const { data, total } = res;
          const mapped = data.map(e => {
            e.listSubProject = e.list_sub_project;
            return e;
          });
          this.filtered = mapped;
          this.total = total;
          this.loading = false;
          // console.log(data, this.filtered);
        }).finally(() => {
          this.loading = false;
        });
    },
    handleCreate() {
      // this.$router.push({
      //   name: 'createProjectOss',
      //   params: {},
      // });
      this.$router.push({
        name: 'createProject',
        params: {},
      });
      // if (this.isOSS) {
      //   this.$router.push({
      //     name: 'createProjectOss',
      //     params: {},
      //   });
      // } else {
      //   this.$router.push({
      //     name: 'createProject',
      //     params: {},
      //   });
      // }
    },
    handleCreateOSS() {
      this.$router.push({
        name: 'createProjectOss',
        params: {},
      });
    },
    // handleCreate2() {
    //   this.$router.push({
    //     name: 'createProject2',
    //     params: {},
    //   });
    // },
    download(url) {
      const a = document.createElement('a');
      a.href = url;
      a.click();
      // window.open(url, '_blank').focus();
    },
    handleEditForm(id) {
      // const currentProject = this.filtered.find((item) => item.id === id);

      // // change project_year to string
      // currentProject.project_year = currentProject.project_year.toString();

      // // change field to number and formulator team
      // currentProject.field = Number(currentProject.field);
      // currentProject.id_formulator_team = Number(currentProject.id_formulator_team);
      this.$router.push({
        name: 'editProject',
        params: { id },
      });
    },
    handleViewForm(id) {
      const currentProject = this.filtered.find((item) => item.id === id);

      // change project_year to string
      currentProject.project_year = currentProject.project_year.toString();
      // change field to number and formulator team
      currentProject.field = Number(currentProject.field);
      currentProject.id_formulator_team = Number(currentProject.id_formulator_team);

      // this.$router.push({a
      //   name: 'createProject',
      //   params: { project: currentProject },
      // });
      this.$router.push({
        name: 'publishProject',
        params: { project: currentProject, readonly: true },
      });
    },
    handleTimPenyusunForm(id) {
      const currentProject = this.filtered.find((item) => item.id === id);
      this.$router.push({
        name: 'timPenyusun',
        params: { project: currentProject, readonly: true, id: currentProject.id },
      });
      // this.$router.push('penyusun/' + currentProject.id);
    },
    handlePublishForm(id) {
      this.announcement = {};
      const currentProject = this.filtered.find((item) => item.id === id);
      const subProject = currentProject.list_sub_project.map(curr => {
        return {
          name: curr.name,
          scale: curr.scale + ' ' + curr.scale_unit,
        };
      });

      if (currentProject.announcement === null){
        this.announcement.pic_name = this.initiator.pic;
        this.announcement.pic_address = this.initiator.address;
        this.announcement.project_id = currentProject.id;
        this.announcement.project_result = currentProject.required_doc;
        this.announcement.project_type = currentProject.project_title;
        this.announcement.project_scale =
          currentProject.scale + ' ' + currentProject.scale_unit;

        this.announcement.id_applicant = currentProject.id_applicant;
      } else {
        this.announcement = currentProject.announcement;
      }
      this.announcement.project_location = currentProject.address;
      this.announcement.is_done = currentProject.is_done;
      this.announcement.sub_project = subProject;
      // console.log(this.announcement);
      this.show = true;
    },
    handleDelete(id, nama) {
      this.$confirm(
        'apakah anda yakin akan menghapus ' + nama + '. ?', 'Peringatan',
        {
          confirmButtonText: 'OK',
          cancelButtonText: 'Batal',
          type: 'warning',
        }
      )
        .then(() => {
          projectResource
            .destroy(id)
            .then((response) => {
              this.$message({
                type: 'success',
                message: 'Hapus Selesai',
              });
              this.getFiltered(this.listQuery);
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
    handleKerangkaAcuan(project, marking) {
      if (marking === 'amdal.feasibility-returned') {
        this.$alert('Menu <b> Formulir Kerangka Acuan </b> Terkunci, Silahkan Klik Tombol <b> Workspace ANDAL </b> Untuk Melakukan Perbaikan Pada Menu ini.', 'Peringatan', {
          confirmButtonText: 'Confirm',
          center: true,
          dangerouslyUseHTMLString: true,
        });
      } else {
        this.$router.push({
          path: `/amdal/${project.id}/formulir`,
        });
      }
    },
    handleKerangkaUklUpl(project, perbaikan) {
      if (perbaikan === true) {
        this.$alert('Menu <b> Formulir UKL UPL </b> Terkunci, Silahkan Klik Tombol <b> Workspace UKL UPL </b> Untuk Melakukan Perbaikan Pada Menu ini.', 'Peringatan', {
          confirmButtonText: 'Confirm',
          center: true,
          dangerouslyUseHTMLString: true,
        });
      } else {
        this.$router.push({
          path: `/uklupl/${project.id}/formulir`,
          // path: `/ukluplstatic/form`,
        });
      }
    },
    handleDokumenKA(project) {
      this.$router.push({
        path: `/amdal/${project.id}/dokumen`,
      });
    },
    handleDokumenAndalRklRpl(project, marking) {
      if (marking === 'amdal.feasibility-returned') {
        this.$alert('Menu <b> Dokumen ANDAL RKL RPL</b> Terkunci, Silahkan Klik Tombol <b> Workspace ANDAL / RKL RPL </b>', 'Peringatan', {
          confirmButtonText: 'Confirm',
          center: true,
          dangerouslyUseHTMLString: true,
        });
      } else {
        this.$router.push({
          path: `/amdal/${project.id}/dokumen-andal-rkl-rpl`,
        });
      }
    },
    handleDokumenUklUpl(project, marking) {
      if (marking === 'uklupl-mt.returned-examination') {
        this.$alert('Menu <b> Dokumen UKL UPL </b> Terkunci, Silahkan Klik Tombol <b> Workspace UKL UPL </b>', 'Peringatan', {
          confirmButtonText: 'Confirm',
          center: true,
          dangerouslyUseHTMLString: true,
        });
      } else {
        this.$router.push({
          path: `/uklupl/${project.id}/dokumen`,
        });
      }
    },
    handleUjiAdmKa(project) {
      this.$router.push({
        path: `/amdal/${project.id}/uji-berkas-administrasi-ka`,
      });
    },
    handleUjiAdmUKLUPL(project) {
      this.$router.push({
        path: `/uklupl/${project.id}/uji-berkas-administrasi`,
      });
    },
    handleUjiAdmAndalRklRpl(project) {
      this.$router.push({
        path: `/amdal/${project.id}/uji-berkas-administrasi-andal-rkl-rpl`,
      });
    },
    handleBeritaAcaraKa(project) {
      this.$router.push({
        path: `/amdal/${project.id}/berita-acara-ka`,
      });
    },
    handleBeritaAcaraAndalRklRpl(project) {
      this.$router.push({
        path: `/amdal/${project.id}/berita-acara-andal-rkl-rpl`,
      });
    },
    handleBeritaAcaraUKLUPL(project) {
      this.$router.push({
        path: `/uklupl/${project.id}/berita-acara`,
      });
    },
    handleUjiKelayakan(project) {
      if (this.isAmdal(project)) {
        this.$router.push({
          path: `/amdal/${project.id}/uji-kelayakan`,
        });
      } else {
        this.$router.push({
          path: `/uklupl/${project.id}/uji-kelayakan`,
        });
      }
    },
    handleRekomendasiUjiKelayakan(project) {
      if (this.isAmdal(project)) {
        this.$router.push({
          path: `/amdal/${project.id}/rekomendasi-uji-kelayakan`,
        });
      } else {
        this.$router.push({
          path: `/uklupl/${project.id}/rekomendasi-uji-kelayakan`,
        });
      }
    },
    handleFeasibilityTest(id) {
      this.$router.push({
        path: `/amdal/${id}/skkl`,
      });
    },
    handlePKPLH(project) {
      this.$router.push({
        path: `/uklupl/${project.id}/pkplh`,
      });
    },
    async handleDownloadSPPLFromOSS(project) {
      const data = await skklResource.list({
        idProject: project.id,
        skklOss: 'true',
        type: 'sppl',
      });
      if ('file_url' in data && 'user_key' in data) {
        if (data.file_url === null) {
          this.$message({
            message: 'URL file SPPL tidak ada.',
            type: 'error',
            duration: 5 * 1000,
          });
          return;
        }
        axios({
          url: `api/skkl`,
          method: 'GET',
          responseType: 'blob',
          params: {
            url: data.file_url,
            spplOss: 'true',
          },
          headers: {
            user_key: data.user_key,
          },
        }).then((response) => {
          response.headers['content-type'] = 'application/pdf';
          const cd = response.headers['content-disposition'];
          const fileName = cd.split('filename*=UTF-8\'\'')[1].replaceAll('"', '');
          var fileURL = window.URL.createObjectURL(new Blob([response.data]));
          var fileLink = document.createElement('a');
          fileLink.href = fileURL;
          fileLink.setAttribute(
            'download',
            `${fileName}`
          );
          document.body.appendChild(fileLink);
          fileLink.click();
        });
      } else {
        this.$message({
          message: 'URL file tidak ada.',
          type: 'error',
          duration: 5 * 1000,
        });
      }
    },
    async handleDownloadPKPLHFromOSS(project) {
      const data = await skklResource.list({
        idProject: project.id,
        skklOss: 'true',
        type: 'pkplh',
      });
      if ('file_url' in data && 'user_key' in data) {
        if (data.file_url === null) {
          this.$message({
            message: 'URL file PKPLH tidak ada.',
            type: 'error',
            duration: 5 * 1000,
          });
          return;
        }
        axios({
          url: `api/skkl`,
          method: 'GET',
          responseType: 'blob',
          params: {
            url: data.file_url,
            pkplhOss: 'true',
          },
          headers: {
            user_key: data.user_key,
          },
        }).then((response) => {
          response.headers['content-type'] = 'application/pdf';
          const cd = response.headers['content-disposition'];
          const fileName = cd.split('filename*=UTF-8\'\'')[1].replaceAll('"', '');
          var fileURL = window.URL.createObjectURL(new Blob([response.data]));
          var fileLink = document.createElement('a');
          fileLink.href = fileURL;
          fileLink.setAttribute(
            'download',
            `${fileName}`
          );
          document.body.appendChild(fileLink);
          fileLink.click();
        });
      } else {
        this.$message({
          message: 'URL file tidak ada.',
          type: 'error',
          duration: 5 * 1000,
        });
      }
    },
    handleGenerateSPPLPemerintah(project){
      console.log(`/project/sppl/${project.id}`);
      this.$router.push({
        path: `/project/sppl/${project.id}`,
      });
    },
    async handleGenerateSPPLPemerintahNew(project) {
      this.authorities = await authoritiesResource.list({
        idProject: project.id,
      });

      const bidArr = Array.prototype.map
        .call(this.authorities, function(item) {
          return item.project;
        }).join(',');

      const districtData = await districtResource.get(this.initiator.district);
      const uriUnduhSppl = await skklResource.list({
        sppl1: 'true',
      });
      const options = { year: 'numeric', month: 'long', day: 'numeric' };

      PizZipUtils.getBinaryContent(
        uriUnduhSppl.url,
        (error, content) => {
          if (error) {
            throw error;
          }
          const zip = new PizZip(content);
          const doc = new Docxtemplater(zip, {
            paragraphLoop: true,
            linebreaks: true,
          });
          doc.render({
            name: `${this.initiator.user_type} ${this.initiator.name}`,
            pname: `${project.project_title}`,
            pnoreg: `${project.registration_no}`,
            address: this.initiator.address,
            paddress: `${project.address[0].address}, ${project.address[0].district}, ${project.address[0].prov}`,
            phone: this.initiator.phone,
            pic: this.initiator.pic,
            pic_role: this.initiator.pic_role,
            bidang: bidArr,
            tempat: districtData.name,
            tanggal: new Date(project.created_at.substring(0, 10)).toLocaleDateString('id-ID', options),
          });

          const out = doc.getZip().generate({
            type: 'blob',
            mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          });

          // saveAs(out, 'SPPL-' + project.project_title + '.docx');
          console.log(this.blobToFile(out, 'SPPL-' + project.project_title + '.docx'));
          // this.docOutput = out;
          const formData = new FormData();
          formData.append('docFile', this.blobToFile(out, 'SPPL-' + project.project_title + '.docx'));

          pdfResource.store(formData).then(value => {
            this.download(value);
          });
        }
      );
    },
    blobToFile(theBlob, fileName){
      return new File([theBlob], fileName, { lastModified: new Date().getTime(), type: theBlob.type });
    },
    async handleGenerateSPPL(project) {
      project.listSubProject = project.listSubProject.map((e, i) => {
        e.address = project.address[i] ? this.toTitleCase(project.address[i].address + ' ' + project.address[i].district + ' ' + project.address[i].prov) : '';
        e.number = i + 1;
        return e;
      });

      PizZipUtils.getBinaryContent(
        '/template_sppl.docx',
        (error, content) => {
          if (error) {
            throw error;
          }
          const zip = new PizZip(content);
          const doc = new Docxtemplater(zip, {
            paragraphLoop: true,
            linebreaks: true,
          });
          doc.render({
            initator_name: this.initiator.name,
            nib: this.initiator.nib ? this.initiator.nib : 'Belum Terdaftar',
            initiator_address: this.initiator.address,
            phone: this.initiator.phone,
            sub_projects: project.listSubProject,
            pic: this.initiator.pic,
            city: project.address[0].district,
            publish_date: project.created_at.substring(0, 10),
          });

          const out = doc.getZip().generate({
            type: 'blob',
            mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
          });

          saveAs(out, 'SPPL-' + project.project_title + '.docx');
          // this.docOutput = out;
        }
      );
    },
    handleAndal(project, isAndal) {
      if (isAndal === true) {
        this.$alert('Menu <b> ANDAL </b> Terkunci, Silahkan Klik Tombol <b> Workspace ANDAL </b> Untuk Melakukan Perbaikan Pada Menu ini.', 'Peringatan', {
          confirmButtonText: 'Confirm',
          center: true,
          dangerouslyUseHTMLString: true,
        });
      } else {
        this.$router.push({
          path: `/amdal/${project.id}/penyusunan-andal`,
        });
      }
    },
    handleMatUklUpl(project, perbaikan) {
      if (perbaikan === true) {
        this.$alert('Menu <b> Matriks UKL UPL </b> Terkunci, Silahkan Klik Tombol <b> Workspace UKL UPL </b> Untuk Melakukan Perbaikan Pada Menu ini.', 'Peringatan', {
          confirmButtonText: 'Confirm',
          center: true,
          dangerouslyUseHTMLString: true,
        });
      } else {
        this.$router.push({
          path: `/uklupl/${project.id}/matriks`,
        });
      }
    },
    isFormKaComplete(project) {
      if (project.form_ka_doc === true) {
        return true;
      }
      return false;
    },
    handleRklRpl(project, perbaikan) {
      if (perbaikan === true) {
        this.$alert('Menu <b> RKL RPL </b> Terkunci, Silahkan Klik Tombol <b> Workspace RKL RPL </b> Untuk Melakukan Perbaikan Pada Menu ini.', 'Peringatan', {
          confirmButtonText: 'Confirm',
          center: true,
          dangerouslyUseHTMLString: true,
        });
      } else {
        this.$router.push({
          path: `/amdal/${project.id}/penyusunan-rkl-rpl`,
        });
      }
    },
    handleLpjpTeam(project) {
      this.$router.push({
        path: `/lpjp-team/${project.id}/daftar-anggota`,
      });
    },
    handleViewSpt(id, projectId) {
      this.$router.push({
        path: `/announcement/view/${id}`,
        query: {
          idProject: projectId,
        },
      });
    },
    handleFlowChart(project) {
      this.$router.push({
        name: 'flowchart',
        params: { id: project.id, project: project },
      });
    },
    handleWorkspace(project) {
      this.$router.push({
        name: 'editWorkspace',
        params: { id: project.id, project: project },
      });
    },
    async changeProvince(value) {
      // change all district by province
      this.getDistricts(value);
    },
    async getDistricts(idProv) {
      const { data } = await districtResource.list({ idProv });
      this.cityOptions = data.map((i) => {
        return { value: i.id, label: i.name };
      });
    },
    handleWorkspaceKa(project, marking) {
      if (marking === 'amdal.feasibility-returned') {
        this.$alert('Menu <b> Workspace KA </b> Terkunci, Silahkan Klik Tombol <b> Workspace ANDAL </b> Untuk Melakukan Perbaikan Pada Menu ibi.', 'Peringatan', {
          confirmButtonText: 'Confirm',
          center: true,
          dangerouslyUseHTMLString: true,
        });
      } else {
        this.$router.push({
          name: 'projectWorkspace',
          params: {
            id: project.id,
            filename: `ka-${project.id}-${project.project_title.toLowerCase()}.docx`,
            workspaceType: 'ka',
          },
        });
      }
    },
    async handleWorkspaceAndal(idProject, marking) {
      if (marking === 'amdal.feasibility-returned') {
        // const that = this;
        this.$alert('Silahkan Lakukan Perbaiki Pada Webform, anda akan dialihkan ke menu <b> ANDAL </b>', 'Peringatan', {
          confirmButtonText: 'Confirm',
          center: true,
          dangerouslyUseHTMLString: true,
        }).then(res => {
          this.$router.push({
            path: `/amdal/${idProject}/penyusunan-andal`,
            query: {
              perbaikan: true,
            },
          });
        });
      } else {
        await andalComposingResource.list({
          docs: 'true',
          idProject: idProject,
        });

        this.$router.push({
          name: 'projectWorkspace',
          params: {
            id: idProject,
            filename: `${idProject}-andal.docx`,
            workspaceType: 'andal',
          },
        });
      }
    },
    async handleWorkspaceRKLRPL(idProject, marking) {
      if (marking === 'amdal.feasibility-returned') {
        // const that = this;
        this.$alert('Silahkan Lakukan Perbaiki Pada Webform, anda akan dialihkan ke menu <b> RKL RPL </b>', 'Peringatan', {
          confirmButtonText: 'Confirm',
          center: true,
          dangerouslyUseHTMLString: true,
        }).then(res => {
          this.$router.push({
            path: `/amdal/${idProject}/penyusunan-rkl-rpl`,
          });
        });
      } else {
        await rklResource.list({
          docs: 'true',
          idProject: idProject,
        });

        this.$router.push({
          name: 'projectWorkspace',
          params: {
            id: idProject,
            filename: `${idProject}-rkl-rpl.docx`,
            workspaceType: 'rkl-rpl',
          },
        });
      }
    },
    async handleWorkspaceUKLUPL(idProject, marking) {
      if (marking === 'uklupl-mt.returned-examination') {
        const that = this;
        this.$alert('Silahkan Lakukan Perbaiki Pada Webform, anda akan dialihkan ke menu <b> Formulir UKL UPL </b>', 'Peringatan', {
          confirmButtonText: 'Confirm',
          center: true,
          dangerouslyUseHTMLString: true,
        }).then(res => {
          that.$router.push({
            path: `/uklupl/${idProject}/formulir`,
            query: {
              perbaikan: true,
            },
          });
        });
      } else {
        const data = await axios.get(
          `/api/dokumen-ukl-upl/${idProject}`
        );
        this.$router.push({
          name: 'projectWorkspace',
          params: {
            id: idProject,
            versi: data.data.versi_doc,
            filename: data.data.file_name,
            createTime: data.data.create_time,
            workspaceType: 'ukl-upl',
          },
        });
      }
    },
    // sorting, filtering
    onTableSort(sort) {
      /* switch (sort.prop) {
        case 'date':
          this.listQuery.orderBy = 'created_at';
          console.log(this.listQuery);
          break;
        case 'title':
          this.listQuery.orderBy = 'project_title';
        default:
      }*/
      this.listQuery.orderBy = sort.prop;
      this.listQuery.order = (sort.order === 'ascending') ? 'ASC' : 'DESC';
      this.handleFilter();
    },
    onDocTypeFilter(val, col, row){
      console.log('filtering doctype!', val);

      this.listQuery.filters = val;
      this.listQuery.page = 1;

      this.handleFilter();
    },
    onTukFilter(val, col, row){
      this.listQuery.filterTUK = val;
      this.listQuery.page = 1;

      this.handleFilter();
    },
    onClearFilter(key){
      console.log('clearing filter!', key);
    },
    doSearch(){
      this.listQuery.page = 1;
      this.getFiltered(this.listQuery);
    },
    resetSearch(){
      this.listQuery.search = '';
      this.listQuery.page = 1;
      this.handleFilter();
    },
    checkAvatar(initiator) {
      if (initiator.user) {
        if (initiator.user.avatar) {
          return initiator.user.avatar;
        }
      }

      return null;
    },
    checkInitiatorAndRisk(project) {
      if (this.isAmdal(project)) {
        return true;
      } else if (this.isUklUpl(project)) {
        if (this.initiator.user_type === 'Pemerintah') {
          return true;
        } else {
          if (project.oss_risk !== null) {
            return true;
          }
        }
      }

      return false;
    },
  },
};
</script>

<style lang="scss" scoped>
.entity-block {
  display: inline-block;

  .name, .description {
    display: block;
    margin-left: 50px;
    padding: 2px 0;
  }
  .action {
    display:  inline-block;
    padding-right: 5%;
  }
  img {
    width: 40px;
    height: 40px;
    float: left;
  }
  :after {
    clear: both;
  }
  .img-circle {
    border-radius: 50%;
    border: 2px solid #d2d6de;
    padding: 2px;
  }
  span {
    font-weight: 500;
    font-size: 12px;
  }

}
.post {
  font-size: 14px;
  margin-bottom: 15px;
  padding-right: 20px;
  padding-left: 7%;
  color: #666;
  .image {
    width: 100%;
  }
  .user-images {
    padding-top: 20px;
  }
  .title {
    display: block;
    padding: 2px 0;
  }
}
.list-inline {
  padding-left: 0;
  margin-left: -5px;
  list-style: none;
  li {
    display: inline-block;
    padding-right: 5px;
    padding-left: 5px;
    font-size: 13px;
  }
  .link-black {
    &:hover, &:focus {
      color: #999;
    }
  }
}
.badge-penugasan {
    background-color: rgb(33, 98, 33);
    color: white;
    border-radius: 23%;
    width: 56%;
    text-align: center;
    margin: auto;
}
</style>

