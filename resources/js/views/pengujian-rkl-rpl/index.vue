<template>
  <div class="app-container">
    <el-card>
      <WorkFlow />
      <el-tabs v-model="activeName" type="card">
        <el-tab-pane
          label="Pemeriksaan Berkas Administrasi Formulir Andal RKL RPL"
          name="verifikasi"
        >
          <Verifikasi
            v-if="activeName === 'verifikasi'"
            @changeIsComplete="changeIsComplete($event)"
          />
        </el-tab-pane>
        <el-tab-pane
          v-if="isComplete"
          label="Undangan Rapat"
          name="undanganrapat"
        >
          <UndanganRapat v-if="activeName === 'undanganrapat'" />
        </el-tab-pane>
      </el-tabs>
    </el-card>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import Verifikasi from '@/views/pengujian-rkl-rpl/components/verifikasi/index';
import UndanganRapat from '@/views/pengujian-rkl-rpl/components/undanganRapat/index';
import WorkFlow from '@/components/Workflow';
import Resource from '@/api/resource';
const verifikasiRapatResource = new Resource('test-verif-rkl-rpl');

export default {
  name: 'PengujianRKLRPL',
  components: {
    Verifikasi,
    UndanganRapat,
    WorkFlow,
  },
  data() {
    return {
      activeName: 'verifikasi',
      // userInfo: {
      //   roles: [],
      // },
      isComplete: false,
    };
  },
  computed: {
    ...mapGetters({
      'userInfo': 'user',
      'userId': 'userId',
    }),
  },
  async created() {
    // this.userInfo = await this.$store.dispatch('user/getInfo');
    this.isComplete = await verifikasiRapatResource.list({
      checkComplete: 'true',
      idProject: this.$route.params.id,
    });
    this.$store.dispatch('getStep', 6);
  },
  methods: {
    changeIsComplete({ isComplete }) {
      this.isComplete = isComplete;
    },
  },
};
</script>
