<template>
  <div class="user-dashboard">
    <div v-if="isFormulator || isInitiator || isLPJP">
      <el-row :gutter="20">
        <el-col :span="12">
          <formulator-information
            v-if="isFormulator"
            :user="user"
            :is-loading="isLoading"
            :avatar="avatar"
          />
          <initiator-information
            v-if="isInitiator"
            :user="user"
            :is-loading="isLoading"
            :avatar="avatar"
          />
          <lpjp-information
            v-if="isLPJP"
            :user="user"
            :is-loading="isLoading"
            :avatar="avatar"
          />
        </el-col>
        <el-col :span="12">
          <user-activities :user="user" />
          <lpjp-summary v-if="isLPJP" :user="user" />
          <div v-else>
            <user-summary :user="user" />
          </div>
        </el-col>
      </el-row>
      <el-row v-if="!isPemerintah">
        <project-table v-if="isInitiator && isLoginFromOSS" />
      </el-row>
      <el-row v-if="isLPJP && user">
        <lpjp-formulators :user="user" />
      </el-row>
    </div>
    <div v-if="isExaminer">
      <el-row :gutter="20">
        <el-col :span="12">
          <user-information :user="user" :is-loading="isLoading" />
        </el-col>
        <el-col :span="12">
          <user-summary :user="user" />
        </el-col>
      </el-row>
      <examiner-activities />
    </div>
    <!-- <div v-if="isLPJP">
      <el-row :gutter="20">
        <el-col :span="12">
          <lpjp-information :user="user" :is-loading="isLoading" />
        </el-col>
        <el-col :span="12" />
          <user-activities :user="user" />
          <user-summary :user="user" />
      </el-row>
    </div> -->
  </div>
</template>
<script>
import { mapGetters } from 'vuex';
import UserActivities from './components/activities';
import InitiatorInformation from './components/initiator';
import FormulatorInformation from './components/formulator';
import UserInformation from './components/UserInformation';
import UserSummary from './components/summary';
import ProjectTable from './components/ProjectTable.vue';

import Resource from '@/api/resource';
import ExaminerActivities from './components/ExaminerActivities.vue';
import LpjpInformation from './components/LpjpInformation.vue';
import LpjpFormulators from './components/LpjpFormulators.vue';
import LpjpSummary from './components/LpjpSummary.vue';
import { isOSS } from '@/utils/auth';

export default {
  name: 'UserDashboard',
  components: {
    InitiatorInformation,
    FormulatorInformation,
    UserActivities,
    UserSummary,
    UserInformation,
    ExaminerActivities,
    ProjectTable,
    LpjpInformation,
    LpjpFormulators,
    LpjpSummary,
  },
  data() {
    return {
      user: null,
      avatar: '',
      isLoading: true,
      isPemerintah: false,
    };
  },
  computed: {
    ...mapGetters({
      userLogged: 'user',
    }),
    isFormulator() {
      return this.$store.getters.roles.includes('formulator');
    },
    isInitiator() {
      return this.$store.getters.roles.includes('initiator');
    },
    isExaminer() {
      return this.$store.getters.roles[0].split('-')[0] === 'examiner';
    },
    isLPJP() {
      return this.$store.getters.roles.includes('lpjp');
    },
    isLoginFromOSS() {
      return isOSS();
    },
  },
  mounted() {
    if (this.isAllowed) {
      console.log('entering user dashboard...');
      this.getUser();
    }
  },
  methods: {
    async getUser() {
      // this.$store.dispatch('user/getInfo').then((response) => {

      if (this.userLogged) {
        if (this.isExaminer) {
          // this.user = response;
          this.isLoading = false;
          return;
        }

        // this.avatar = response.avatar;
        this.avatar = this.userLogged.avatar;
        let resource = null;
        if (this.isFormulator) {
          resource = new Resource('formulatorsByEmail');
        } else if (this.isInitiator) {
          resource = new Resource('initiatorsByEmail');
        } else if (this.isLPJP) {
          resource = new Resource('lpjpsByEmail');
        }
        if (!resource) {
          this.isLoading = false;
          return;
        }
        resource.list({ email: this.userLogged.email }).then((res) => {
          this.user = res;
          this.isPemerintah = this.user?.user_type === 'Pemerintah';
        });
        /* get */

        this.isLoading = false;
      }
      // ).catch((error) => {
      //   this.$message({
      //     message: error.message,
      //     type: 'error',
      //     duration: 5 * 1000,
      //   });
      //   console.log(error);
      //   this.isLoading = false;
      // });
      // console.log('getUser: ', this.user);
    },
    isAllowed() {
      console.log('isAllowed?', this.$store.getters.roles);
      return !!(this.isFormulator || this.isInitiator || this.isExaminer);
    },
  },
};
</script>
<style lang="scss">
div.user-dashboard {
  padding: 2em;
}

div.user-dashboard div.el-card {
  margin-bottom: 1.5em;
}

div.user-dashboard div.el-card__header {
  background: #afc7af;
  font-weight: 700;
  font-size: 90%;
}
.user-image {
  padding: 1em 2em;
}
.user-detail {
  padding: 1em 2em;

  .el-row {
    margin: 1em 0 2em;
  }

  span.label,
  span.value {
    display: block;
    line-height: 150%;
  }

  span.label {
    font-weight: bold;
    text-decoration: underline;
  }
}
</style>
