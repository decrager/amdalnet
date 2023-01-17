<template>
  <div v-if="data !== null" class="project-detail">
    <el-row :gutter="20">
      <el-col :span="4">
        <div class="block" style="text-align: center;">
          <el-image
            style="width: 100%; height: 150px"
            :src="data.initiator.logo"
            fit="contain"
          >
            <div slot="error" class="image-slot">
              <i class="el-icon-picture-outline" style="font-size: 500%; line-height: 230%;" />
            </div>
          </el-image>
        </div>
      </el-col>
      <el-col :span="14">
        <div class="context-bar-item project">
          <p>{{ data.project_title }}</p>
          <p>{{ data.project_address }}</p>
        </div>
        <div class="context-bar-item project-initiator">
          <p>{{ data.initiator.name || '' }}</p>
          <p>{{ data.initiator.address }}</p>
        </div>
      </el-col>
      <el-col :span="6">
        <div class="context-bar-item highlight">
          <p><span :class="'doc-type '+ data.required_doc">{{ data.required_doc }}</span></p>
          <p style="margin-top: 1.5em !important;"><span style=" font-weight:normal;">Registration No:</span><br>
            <span style="font-size:1.1em;">{{ (data.registration_no || '').toUpperCase() }}</span></p>
        </div>
      </el-col>
    </el-row>
    <hr style="border-color: #ccc; ">
    <el-row :gutter="30">
      <el-col :sm="24" :md="14">
        <p class="header">Deskripsi Kegiatan</p>
        <Tinymce
          v-model="data.description"
          output-format="html"
          :menubar="''"
          :image="false"
          :toolbar="[
            'bold italic underline bullist numlist  preview undo redo fullscreen',
          ]"
          :height="150"
        />
      </el-col>
      <el-col :sm="24" :md="10" align="right">
        <el-button :loading="loading" type="primary" style="margin-top: 16px;" @click="handleSave">Simpan</el-button>
      </el-col>
      <el-col :span="20">
        <p class="header">Dampak Potensial</p>
        <p class="data">{{ data.potential_impact }}</p>

        <p class="header">Sifat Kegiatan</p>
        <!-- <p class="data"> {{ data.project_type || '-' }} </p> -->
        <p class="data"> {{ [ (data.project_type || '-'), ('Kewenangan ' + (data.authority || '-')), (data.status || '-'), (data.study_approach || '-')].join(', ') }} </p>

        <p class="header">Kegiatan Utama</p>
        <project-activities :data="utama" />

        <p class="header">Kegiatan Pendukung</p>
        <project-activities :data="pendukung" />

      </el-col>
      <el-col :span="4">
        <!--
        <template v-for="link in linkData">
          <p :key="data.id+ '_link_'+ link.label">
            <el-button
              :class="'button-' + data.required_doc+ ' cb-ph-link'"
              round
              style=" display: block !important; width: 100% !important; padding: 1em !important; color: white !important; font-weight:bold !important;"
            >
              {{ link.label }}
            </el-button>
          </p>
        </template>
        -->
        <p v-if="isHome !== true"><el-button
          round
          plain
          style="display: block !important; width: 100% !important; padding: 1em !important; font-weight: bold !important"
          @click="$router.push('/project/home/' + data.id)"
        >Ringkasan Kegiatan</el-button></p>
      </el-col>
    </el-row>
  </div>
</template>
<script>
import Tinymce from '@/components/Tinymce';
import ProjectActivities from './sections/Activities.vue';
import Resource from '@/api/resource';
import { mapGetters } from 'vuex';
const projectResource = new Resource('projects');
export default {
  name: 'ProjectDetail',
  components: { ProjectActivities, Tinymce },
  props: {
    data: {
      type: Object,
      default: null,
    },
    isHome: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      loading: false,
      utama: [],
      pendukung: [],
      linkData: [
        {
          label: 'Penapisan',
          route: '',
        },
        {
          label: 'Pengumuman',
          route: '',
        },
        {
          label: 'Konsultasi Publik',
          route: '',
        },
        {
          label: 'Pelingkupan',
          route: '',
        },
        {
          label: 'Workspace',
          route: '',
        },
      ],
    };
  },
  computed: {
    ...mapGetters({
      userInfo: 'user',
      userId: 'userId',
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
  mounted() {
    this.utama = (this.data.list_sub_project).filter((e) => e.type === 'utama');
    this.pendukung = (this.data.list_sub_project).filter((e) => e.type === 'pendukung');
  },
  methods: {
    async handleSave() {
      this.loading = true;
      await projectResource.update(this.$route.params.id, {
        projectHome: 'true',
        type: 'description',
        description: this.data.description,
      });
      this.$message({
        message: 'Data berhasil disimpan',
        type: 'success',
        duration: 5 * 1000,
      });
      this.loading = false;
    },
  },
};
</script>
<style lang="scss" scoped>
.project-detail{
  padding: 2em;
  .context-bar-item {
    margin: 0.5em 0 1em;

    p {
    margin: 0 0 0 0 !important;
    }

    &.project {font-weight: 500;}
    &.project-initiator { font-weight: bold; margin-top: 2em !important;}

    &.project, &.project-initiator {
       p{line-height: 1.25em !important;}
       p:first-child { margin-bottom: 0.5em !important;}
    }
    &.highlight{
      p{
        font-weight: bold;
        text-align: right;
        margin-bottom: 2em;
      }
      .doc-type {
        display: inline-block; padding: 0.5em 1.5em; border-radius: 1em;
        color: white !important;
      }
    }

    .el-button.cb-ph-links {

      span {
        color: white !important;
        font-weight: bold !important;
      }
    }
  }
}
</style>
