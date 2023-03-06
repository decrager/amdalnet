<!-- eslint-disable vue/html-indent -->
<template>
  <div class="app-container" style="padding: 24px">
    <el-card v-loading="loading">
      <div role="alert" class="el-alert el-alert--error is-dark" style="margin-top: 10px">
        <div class="el-alert__content">
          <p class="el-alert__description">
            Pastikan Workspace ANDAL RKL RPL sudah dilengkapi
          </p>
        </div>
      </div>
      <h2>
        Submit Dokumen ANDAL RKL RPL
        <span v-if="isFormulator">ke Pemrakarsa</span>
      </h2>
      <el-row :gutter="20" style="margin-top: 20px">
        <el-col :sm="24" :md="14">
          <div v-if="!isPemerintah" role="alert" class="el-alert el-alert--info is-light space">
            <i class="el-alert__icon el-icon-warning is-big" />
            <div class="el-alert__content">
              <p class="el-alert__description">
                Dokumen yang ukuran file nya lebih dari <b> 100 MB </b>, Maka Preview dan tombol download PDF nya tidak akan muncul
              </p>
            </div>
            <br><br>
          </div>
          <div style="margin-bottom: 8px">
            <a
              v-if="showDocumentAndal"
              class="btn-docx"
              :href="andalDocxUrl"
              :download="`andal-${titleAndal}.docx`"
            >
              Export Dokumen ANDAL to .DOCX
            </a>
            <a v-if="showBukti" class="btn-submit" :href="urlBukti" :download="`bukti-submit-ukl-upl.pdf`">
              Unduh Bukti Pengiriman
            </a>
          </div>
          <iframe
            v-if="showDocumentAndal && andalPdfUrl"
            :src="`https://docs.google.com/gview?url=${encodeURIComponent(
              andalPdfUrl
            )}&embedded=true`"
            width="100%"
            height="723px"
            frameborder="0"
          />
          <div style="margin-top: 10px; margin-bottom: 8px">
            <a
              v-if="showDocumentRklRpl"
              class="btn-docx"
              :href="rklRplDocxUrl"
              :download="`rkl-rpl-${titleRklRpl}.docx`"
            >
              Export Dokumen RKL RPL to .DOCX
            </a>
          </div>
          <iframe
            v-if="showDocumentRklRpl"
            :src="`https://docs.google.com/gview?url=${encodeURIComponent(
              rklRplPdfUrl
            )}&embedded=true`"
            width="100%"
            height="723px"
            frameborder="0"
          />
        </el-col>
        <el-col :sm="24" :md="10">
          <ReviewPenyusun v-if="isFormulator" :documenttype="'ANDAL RKL RPL'" />
          <ReviewPemrakarsa
            v-if="isInitiator"
            :documenttype="'ANDAL RKL RPL'"
            @submitPemrakarsa="submitPemrakarsa"
          />
        </el-col>
      </el-row>
    </el-card>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import Resource from '@/api/resource';
import ReviewPenyusun from '@/views/review-dokumen/ReviewPenyusun';
import ReviewPemrakarsa from '@/views/review-dokumen/ReviewPemrakarsa';
const rklResource = new Resource('matriks-rkl');
const andalComposingResource = new Resource('andal-composing');
const projectsResource = new Resource('projects');
const kaReviewResource = new Resource('ka-reviews');

export default {
  components: {
    ReviewPenyusun,
    ReviewPemrakarsa,
  },
  data() {
    return {
      titleRklRpl: '',
      titleAndal: '',
      loading: false,
      showDocumentRklRpl: false,
      showDocumentAndal: false,
      andalPdfUrl: null,
      urlBukti: null,
      showBukti: false,
      andalDocxUrl: null,
      rklRplPdfUrl: null,
      rklRplDocxUrl: null,
    };
  },
  computed: {
    ...mapGetters({
      userInfo: 'user',
      userId: 'userId',
    }),
    isFormulator() {
      return this.userInfo.roles.includes('formulator');
    },
    isInitiator() {
      return this.userInfo.roles.includes('initiator');
    },
    documentName() {
      return `${this.$route.params.id}-rkl-rpl.docx`;
    },
    documentNameAndal() {
      return `${this.$route.params.id}-andal.docx`;
    },
  },
  async created() {
    this.loading = true;
    this.getBuktiDoc();
    this.getSubmit();
    await this.getDataAndal();
    await this.getData();
    this.loading = false;
  },
  methods: {
    async getData() {
      const data = await rklResource.list({
        idProject: this.$route.params.id,
        documentReview: 'true',
      });
      this.rklRplDocxUrl = data.docx_url;
      this.rklRplPdfUrl = data.pdf_url;
      this.titleRklRpl = data.project_title;
      this.showDocumentRklRpl = true;
    },
    async getDataAndal() {
      const data = await andalComposingResource.list({
        idProject: this.$route.params.id,
        documentReview: 'true',
      });
      this.andalDocxUrl = data.docx_url;
      this.andalPdfUrl = data.pdf_url;
      this.titleAndal = data.project_title;
      this.showDocumentAndal = true;
    },
    async getBuktiDoc() {
      const data = await projectsResource.list({
        project_id: this.$route.params.id,
        doc: 'ANDAL RKL - RPL ',
      });
      this.urlBukti = data;
    },
    async getSubmit() {
      const data = await kaReviewResource.list({
        idProject: this.$route.params.id,
        documentType: 'andal-rkl-rpl',
        status: 'submit-to-pemrakarsa',
      });
      if (data !== null) {
        this.showBukti = true;
      }
    },
    submitPemrakarsa(status) {
      if (status === true) {
        this.showBukti = true;
      }
    },
  },
};
</script>

<style scoped>
.body__section {
  display: flex;
  column-gap: 20px;
  margin-top: 20px;
}

.body__section.left__section {
  flex: 2;
}
.body__section.right__section {
  flex: 1;
}
.heading__comment {
  display: flex;
  column-gap: 15px;
}
.heading__comment.img__comment {
  flex: 0.5;
}
.heading__comment.name__comment {
  flex: 2;
}
.img__comment img {
  width: 32px;
  border-radius: 50%;
  border: 2px solid #099c4b;
}
.btn-docx,
.btn-submit {
  padding: 10px 20px;
  font-size: 14px;
  border-radius: 4px;
  color: #ffffff;
  background-color: #216221;
  display: inline-block;
  line-height: 1;
  white-space: nowrap;
  cursor: pointer;
  border: 1px solid #216221;
  -webkit-appearance: none;
  text-align: center;
  box-sizing: border-box;
  outline: none;
  margin: 0;
  transition: 0.1s;
  font-weight: 400;
}

.btn-submit {
  background-color: #0058ff !important;
  border: 1px solid #0058ff !important;
}
</style>
