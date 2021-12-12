<template>
  <el-row v-loading="loading" :gutter="32">
    <el-col :md="8" :sm="24">
      <el-card class="box-card">
        <div slot="header" class="clearfix card-header">
          <span>Pendekatan Teknologi</span>
        </div>
        <div
          v-for="t in technology"
          :key="t.id"
          class="text item container-approach"
        >
          {{ t.description }}
        </div>
        <el-input
          v-if="technologyInput.show"
          v-model="technologyInput.value"
          type="textarea"
          :rows="2"
          style="margin-top: 5px"
        />
        <div v-if="technologyInput.show" class="send-btn-container">
          <el-button
            :loading="loadingSubmitTechnology"
            type="primary"
            size="mini"
            @click="handleSubmit('technology')"
          >
            {{ 'Kirim' }}
          </el-button>
        </div>
      </el-card>
      <el-button
        icon="el-icon-plus"
        style="margin-top: 5px"
        circle
        @click="technologyInput.show = !technologyInput.show"
      />
    </el-col>
    <el-col :md="8" :sm="24">
      <el-card class="box-card">
        <div slot="header" class="clearfix card-header">
          <span>Pendekatan Sosial Ekonomi</span>
        </div>
        <div
          v-for="s in socialEconomy"
          :key="s.id"
          class="text item container-approach"
        >
          {{ s.description }}
        </div>
        <el-input
          v-if="socialEconomyInput.show"
          v-model="socialEconomyInput.value"
          type="textarea"
          :rows="2"
          style="margin-top: 5px"
        />
        <div v-if="socialEconomyInput.show" class="send-btn-container">
          <el-button
            :loading="loadingSubmitSocial"
            type="primary"
            size="mini"
            @click="handleSubmit('social_economy')"
          >
            {{ 'Kirim' }}
          </el-button>
        </div>
      </el-card>
      <el-button
        icon="el-icon-plus"
        style="margin-top: 5px"
        circle
        @click="socialEconomyInput.show = !socialEconomyInput.show"
      />
    </el-col>
    <el-col :md="8" :sm="24">
      <el-card class="box-card">
        <div slot="header" class="clearfix card-header">
          <span>Pendekatan Institusi (Kelembagaan)</span>
        </div>
        <div
          v-for="i in institution"
          :key="i.id"
          class="text item container-approach"
        >
          {{ i.description }}
        </div>
        <el-input
          v-if="institutionInput.show"
          v-model="institutionInput.value"
          type="textarea"
          :rows="2"
          style="margin-top: 5px"
        />
        <div v-if="institutionInput.show" class="send-btn-container">
          <el-button
            :loading="loadingSubmitInstitution"
            type="primary"
            size="mini"
            @click="handleSubmit('institution')"
          >
            {{ 'Kirim' }}
          </el-button>
        </div>
      </el-card>
      <el-button
        icon="el-icon-plus"
        style="margin-top: 5px"
        circle
        @click="institutionInput.show = !institutionInput.show"
      />
    </el-col>
  </el-row>
</template>

<script>
import Resource from '@/api/resource';
const manageApproachResource = new Resource('manage-approach');

export default {
  name: 'ManageApproach',
  data() {
    return {
      technology: [],
      socialEconomy: [],
      institution: [],
      idProject: this.$route.params.id,
      loadingSubmitTechnology: false,
      loadingSubmitSocial: false,
      loadingSubmitInstitution: false,
      loading: false,
      technologyInput: {
        show: false,
        value: null,
      },
      socialEconomyInput: {
        show: false,
        value: null,
      },
      institutionInput: {
        show: false,
        value: null,
      },
    };
  },
  created() {
    this.getData();
  },
  methods: {
    async getData() {
      this.loading = true;
      const data = await manageApproachResource.list({
        idProject: this.$route.params.id,
      });
      this.technology = data.technology;
      this.socialEconomy = data.social_economy;
      this.institution = data.institution;
      this.loading = false;
    },
    async handleSubmit(typeApp) {
      let description = null;

      if (typeApp === 'technology') {
        if (!this.technologyInput.value) {
          return;
        }

        description = this.technologyInput.value;
      } else if (typeApp === 'social_economy') {
        if (!this.socialEconomyInput.value) {
          return;
        }

        description = this.socialEconomyInput.value;
      } else if (typeApp === 'institution') {
        if (!this.institutionInput.value) {
          return;
        }

        description = this.institutionInput.value;
      } else {
        return;
      }

      this.loadingSubmitTechnology = true;
      this.loadingSubmitSocial = true;
      this.loadingSubmitInstitution = true;
      const data = await manageApproachResource.store({
        type: typeApp,
        idProject: this.idProject,
        description,
      });

      if (typeApp === 'technology') {
        this.technology.push(data);
        this.technologyInput.show = false;
        this.technologyInput.value = false;
      } else if (typeApp === 'social_economy') {
        this.socialEconomy.push(data);
        this.socialEconomyInput.show = false;
        this.socialEconomyInput.value = false;
      } else {
        this.institution.push(data);
        this.institutionInput.show = false;
        this.institutionInput.value = false;
      }

      this.loadingSubmitTechnology = false;
      this.loadingSubmitSocial = false;
      this.loadingSubmitInstitution = false;
    },
  },
};
</script>

<style>
.el-card__header {
  background: #6cc26f !important;
}
.card-header {
  text-align: center;
  color: #fff;
  font-size: 16px;
}
</style>

<style scoped>
.container-approach {
  border-bottom: 1px solid #ccc;
  padding-top: 15px;
  padding-bottom: 6px;
}
</style>