<template>
  <div class="app-container">
    <el-card>
      <div class="filter-container">
        <el-row :gutter="32">
          <el-col :sm="24" :md="12">
            <h3>
              <!-- eslint-disable-next-line vue/html-self-closing -->
              Penanggung Jawab dan Validator <br />
              {{ title }}
            </h3>
          </el-col>
          <el-col :sm="24" :md="12" align="right">
            <div>
              <el-button type="warning" @click="handleAddMember">
                {{ 'Tambah Anggota' }}
              </el-button>
              <el-button
                :loading="loadingSubmit"
                type="primary"
                @click="checkSubmit"
              >
                {{ 'Simpan Perubahan' }}
              </el-button>
            </div>
          </el-col>
        </el-row>
      </div>
      <AssignmentTable
        :list="members"
        :members="masterMembers"
        :loading="loading"
        @changeName="changeName($event)"
        @deleteMember="handleDelete($event)"
      />
    </el-card>
  </div>
</template>

<script>
import Resource from '@/api/resource';
import AssignmentTable from '@/views/tuk-project/components/AssignmentTable';
const tukProjectResource = new Resource('tuk-project');

export default {
  name: 'AssignMember',
  components: {
    AssignmentTable,
  },
  data() {
    return {
      title: '',
      masterMembers: [],
      members: [],
      deletedMembers: [],
      loading: false,
      loadingSubmit: false,
    };
  },
  async created() {
    this.loading = true;
    await this.getData();
    await this.getMembers();
    this.loading = false;
  },
  methods: {
    async getMembers() {
      const members = await tukProjectResource.list({
        projectMembers: 'true',
        idProject: this.$route.params.id,
      });

      let num = 0;
      this.members = members.map((x) => {
        const idType = `${
          x.id_feasibility_test_team_member
            ? x.id_feasibility_test_team_member
            : x.id_tuk_secretary_member
        }-${x.id_feasibility_test_team_member ? 'tuk' : 'secretary'}`;

        const member = this.masterMembers.find((y) => y.idType === idType);
        num++;

        return {
          id: x.id,
          idType,
          num,
          nik: member.nik,
          email: member.email,
          position: x.id_feasibility_test_team_member
            ? 'Anggota Tim Uji Kelayakan'
            : 'Anggota Sekretariat Uji Kelayakan',
          institution: member.institution,
          role: x.role,
          type: x.id_feasibility_test_team_member ? 'tuk' : 'secretary',
          error: {},
        };
      });
    },
    async getData() {
      const { secretary_members, tuk_members, title } =
        await tukProjectResource.list({
          members: 'true',
          projectId: this.$route.params.id,
        });
      this.masterMembers = secretary_members.map((x) => {
        x.type = 'secretary';
        x.idType = x.id + '-secretary';
        return x;
      });
      const tukMembers = tuk_members.map((x) => {
        if (x.luk_member !== null) {
          return {
            id: x.id,
            type: 'tuk',
            idType: x.id + '-tuk',
            name: x.luk_member.name,
            nik: x.luk_member.nik,
            institution: x.luk_member.institution,
            email: x.luk_member.email,
          };
        } else {
          return {
            id: x.id,
            type: 'tuk',
            idType: x.id + '-tuk',
            name: x.expert_bank.name,
            nik: x.expert_bank.nik,
            institution: x.expert_bank.institution,
            email: x.expert_bank.email,
          };
        }
      });
      const emailtuk = this.$store.getters.user.email;
      const newMasterMembers = tukMembers.filter(data => data.email !== emailtuk);
      console.log(this.masterMembers);
      console.log(newMasterMembers);
      this.masterMembers = [...this.masterMembers, ...newMasterMembers];
      // this.masterMembers = [...this.masterMembers, ...tukMembers];
      this.masterMembers.sort((a, b) => {
        const nameA = a.name.toUpperCase();
        const nameB = b.name.toUpperCase();
        if (nameA < nameB) {
          return -1;
        }
        if (nameA > nameB) {
          return 1;
        }
        return 0;
      });
      this.title = title;
    },
    checkSubmit() {
      let errors = 0;
      this.members = this.members.map((x) => {
        if (x.idType) {
          x.error.name = false;

          if (x.role) {
            x.error.role = false;
          } else {
            errors++;
            x.error.role = true;
          }
        } else {
          errors++;
          x.error.name = true;
        }

        return x;
      });

      if (errors === 0) {
        this.handleSubmit();
      }
    },
    async handleSubmit() {
      this.loadingSubmit = true;
      await tukProjectResource.store({
        idProject: this.$route.params.id,
        members: this.members,
        deletedMembers: this.deletedMembers,
      });
      this.$message({
        message: 'Data berhasil disimpan',
        type: 'success',
        duration: 5 * 1000,
      });
      this.getMembers();
      this.loadingSubmit = false;
      this.deletedMembers = [];
    },
    handleAddMember() {
      this.members.push({
        id: null,
        idType: null,
        num:
          this.members.length === 0
            ? 1
            : this.members[this.members.length - 1].num + 1,
        nik: null,
        position: null,
        institution: null,
        role: null,
        type: null,
        email: null,
        error: {},
      });
    },
    changeName({ event, idx }) {
      const [id, typeMember] = event.split('-');

      // === CHECX IF EXIST === //
      const is_exist = this.members.find((x) => {
        return x.idType === event && x.type === typeMember;
      });

      if (is_exist) {
        this.$alert('Anggota tersebut sudah ditambahkan sebelumnya', '', {
          center: true,
        });

        this.members[idx].idType = null;
        this.members[idx].type = null;
        this.members[idx].nik = null;
        this.members[idx].email = null;
        this.members[idx].institution = null;
        this.members[idx].position = null;
        this.members[idx].role = null;
      } else {
        this.members[idx].type = typeMember;

        const master = this.masterMembers.find((x) => {
          return x.id === +id && x.type === typeMember;
        });

        this.members[idx].nik = master.nik;
        this.members[idx].email = master.email;
        this.members[idx].institution = master.institution;
        this.members[idx].position =
          typeMember === 'tuk'
            ? 'Anggota Tim Uji Kelayakan'
            : 'Anggota Sekretariat Uji Kelayakan';
        this.members[idx].role = null;
      }
    },
    handleDelete({ num, id }) {
      this.members = this.members.filter((x) => {
        return x.num !== num;
      });
      if (id) {
        this.deletedMembers.push(id);
      }
    },
  },
};
</script>
