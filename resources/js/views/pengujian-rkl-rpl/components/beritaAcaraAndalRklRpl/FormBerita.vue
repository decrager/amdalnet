<template>
  <el-form label-position="top" label-width="200px" style="max-width: 100%">
    <el-row v-loading="loadingtuk" :gutter="32">
      <el-col :sm="24" :md="12">
        <el-form-item label="Tanggal Rapat">
          <el-date-picker
            v-model="reports.meeting_date"
            type="date"
            value-format="yyyy-MM-dd"
            placeholder="Pilih tanggal"
            style="width: 100%"
            :disabled="isSecretary ? true:false"
          />
        </el-form-item>
      </el-col>
      <el-col :sm="24" :md="12">
        <el-form-item label="Waktu Rapat">
          <el-time-picker
            v-model="reports.meeting_time"
            placeholder="Waktu Rapat"
            format="HH:mm"
            value-format="HH:mm"
            style="width: 100%"
            :disabled="isSecretary ? true:false"
          />
        </el-form-item>
      </el-col>
      <el-col :sm="24" :md="24">
        <el-form-item label="Tempat Rapat">
          <el-input v-model="reports.location" :disabled="isSecretary ? true:false" />
        </el-form-item>
      </el-col>
    </el-row>
  </el-form>
</template>

<script>
import Resource from '@/api/resource';
const meetingReportResource = new Resource('meet-report-rkl-rpl');

export default {
  name: 'FormBerita',
  props: {
    reports: {
      type: Object,
      default: () => {},
    },
    loadingtuk: Boolean,
  },
  data() {
    return {
      timUjiKelayakan: [],
      pemrakarsa: [],
    };
  },
  computed: {
    isSecretary() {
      return this.$store.getters.user.roles.includes('examiner-secretary');
    },
  },
  created() {
    this.getTimUjiKelayakan();
    this.getPemrakarsa();
  },
  methods: {
    async getTimUjiKelayakan() {
      const data = await meetingReportResource.list({
        expert_bank_team: 'true',
      });
      this.timUjiKelayakan = data.map((x) => {
        let name = '';
        const team_number = x.team_number ? x.team_number : '';

        if (x.authority === 'Pusat') {
          name = 'Tim Uji Kelayakan Pusat';
        } else if (x.authority === 'Provinsi') {
          if (x.province_authority) {
            name = `Tim Uji Kelayakan Provinsi ${this.capitalize(
              x.province_authority.name
            )} ${team_number}`;
          }
        } else if (x.authority === 'Kabupaten/Kota') {
          if (x.district_authority) {
            name = `Tim Uji Kelayakan ${this.capitalize(
              x.district_authority.name
            )} ${team_number}`;
          }
        }

        return {
          id: x.id,
          name,
        };
      });
    },
    async getPemrakarsa() {
      const data = await meetingReportResource.list({ pemrakarsa: 'true' });
      this.pemrakarsa = data;
    },
    async handleChangeTimUji(val) {
      this.loadingtuk = true;
      const data = await meetingReportResource.list({
        tuk_member: 'true',
        tuk_id: val,
      });
      this.reports.invitations = data;
      this.loadingtuk = false;
    },
    capitalize(mySentence) {
      const words = mySentence.split(' ');

      const newWords = words
        .map((word) => {
          return (
            word.toLowerCase()[0].toUpperCase() +
            word.toLowerCase().substring(1)
          );
        })
        .join(' ');
      return newWords;
    },
  },
};
</script>
