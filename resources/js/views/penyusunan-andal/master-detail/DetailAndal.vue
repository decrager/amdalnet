<template>
  <div style="margin-top: 30px">
    <div v-if="activeName === 'pra-konstruksi'" v-loading="loading">
      <div class="pagination-andal">
        <el-button
          icon="el-icon-arrow-left"
          circle
          :disabled="currentPagePraKonstruksi === 1"
          @click="handlePaginate('left', 'pra konstruksi')"
        />
        <el-input v-model="currentPagePraKonstruksi" class="input-page" />
        <span> / {{ totalPraKonstruksi }}</span>
        <el-button
          icon="el-icon-arrow-right"
          circle
          :disabled="currentPagePraKonstruksi === totalPraKonstruksi"
          @click="handlePaginate('right', 'pra konstruksi')"
        />
      </div>
      <div
        v-loading="loadingDestroy"
        :class="{ 'loading-div': loadingDestroy }"
      >
        <FormDetail
          v-if="showDetail"
          ref="formdetail"
          :andal="list[indexPraKonstruksi[currentPagePraKonstruksi - 1]]"
          :loadingsubmit="loadingsubmit"
          @handleSubmit="handleSubmit"
        />
      </div>
    </div>
    <div v-if="activeName === 'konstruksi'" v-loading="loading">
      <div class="pagination-andal">
        <el-button
          icon="el-icon-arrow-left"
          circle
          :disabled="currentPageKonstruksi === 1"
          @click="handlePaginate('left', 'konstruksi')"
        />
        <el-input v-model="currentPageKonstruksi" class="input-page" />
        <span> / {{ totalKonstruksi }}</span>
        <el-button
          icon="el-icon-arrow-right"
          circle
          :disabled="currentPageKonstruksi === totalKonstruksi"
          @click="handlePaginate('right', 'konstruksi')"
        />
      </div>
      <div
        v-loading="loadingDestroy"
        :class="{ 'loading-div': loadingDestroy }"
      >
        <FormDetail
          v-if="showDetail"
          ref="formdetail"
          :andal="list[indexKonstruksi[currentPageKonstruksi - 1]]"
          :loadingsubmit="loadingsubmit"
          @handleSubmit="handleSubmit"
        />
      </div>
    </div>
    <div v-if="activeName === 'operasi'" v-loading="loading">
      <div class="pagination-andal">
        <el-button
          icon="el-icon-arrow-left"
          circle
          :disabled="currentPageOperasi === 1"
          @click="handlePaginate('left', 'operasi')"
        />
        <el-input v-model="currentPageOperasi" class="input-page" />
        <span> / {{ totalOperasi }}</span>
        <el-button
          icon="el-icon-arrow-right"
          circle
          :disabled="currentPageOperasi === totalOperasi"
          @click="handlePaginate('right', 'operasi')"
        />
      </div>
      <div
        v-loading="loadingDestroy"
        :class="{ 'loading-div': loadingDestroy }"
      >
        <FormDetail
          v-if="showDetail"
          ref="formdetail"
          :andal="list[indexOperasi[currentPageOperasi - 1]]"
          :loadingsubmit="loadingsubmit"
          @handleSubmit="handleSubmit"
        />
      </div>
    </div>
    <div v-if="activeName === 'pasca-operasi'" v-loading="loading">
      <div class="pagination-andal">
        <el-button
          icon="el-icon-arrow-left"
          circle
          :disabled="currentPagePascaOperasi === 1"
          @click="handlePaginate('left', 'pasca operasi')"
        />
        <el-input v-model="currentPagePascaOperasi" class="input-page" />
        <span> / {{ totalPascaOperasi }}</span>
        <el-button
          icon="el-icon-arrow-right"
          circle
          :disabled="currentPagePascaOperasi === totalPascaOperasi"
          @click="handlePaginate('right', 'pasca operasi')"
        />
      </div>
      <div
        v-loading="loadingDestroy"
        :class="{ 'loading-div': loadingDestroy }"
      >
        <FormDetail
          v-if="showDetail"
          ref="formdetail"
          :andal="list[indexPascaOperasi[currentPagePascaOperasi - 1]]"
          :loadingsubmit="loadingsubmit"
          @handleSubmit="handleSubmit"
        />
      </div>
    </div>
  </div>
</template>

<script>
import FormDetail from '@/views/penyusunan-andal/master-detail/FormDetail.vue';

export default {
  name: 'DetailAndal',
  components: {
    FormDetail,
  },
  props: {
    list: {
      type: Array,
      default: () => [],
    },
    listBackup: {
      type: Array,
      default: () => [],
    },
    loading: Boolean,
    loadingsubmit: Boolean,
  },
  data() {
    return {
      activeName: 'pra-konstruksi',
      currentPagePraKonstruksi: 1,
      currentPageKonstruksi: 1,
      currentPageOperasi: 1,
      currentPagePascaOperasi: 1,
      totalPage: 10,
      showDetail: true,
      timeoutId: null,
      loadingDestroy: false,
    };
  },
  computed: {
    totalPraKonstruksi() {
      const total = this.list.filter((li) => {
        return li.type === 'subtitle' && li.stage === 'Pra Konstruksi';
      });
      return total.length;
    },
    totalKonstruksi() {
      const total = this.list.filter((li) => {
        return li.type === 'subtitle' && li.stage === 'Konstruksi';
      });
      return total.length;
    },
    totalOperasi() {
      const total = this.list.filter((li) => {
        return li.type === 'subtitle' && li.stage === 'Operasi';
      });
      return total.length;
    },
    totalPascaOperasi() {
      const total = this.list.filter((li) => {
        return li.type === 'subtitle' && li.stage === 'Pasca Operasi';
      });
      return total.length;
    },
    indexPraKonstruksi() {
      const idx = [];
      this.list.map((li) => {
        if (li.type === 'subtitle' && li.stage === 'Pra Konstruksi') {
          const indx = this.list.findIndex((lis) => {
            return (
              lis.type === 'subtitle' &&
              lis.stage === 'Pra Konstruksi' &&
              lis.id === li.id
            );
          });
          idx.push(indx);
        }
      });
      return idx;
    },
    indexKonstruksi() {
      const idx = [];
      this.list.map((li) => {
        if (li.type === 'subtitle' && li.stage === 'Konstruksi') {
          const indx = this.list.findIndex((lis) => {
            return (
              lis.type === 'subtitle' &&
              lis.stage === 'Konstruksi' &&
              lis.id === li.id
            );
          });
          idx.push(indx);
        }
      });
      return idx;
    },
    indexOperasi() {
      const idx = [];
      this.list.map((li) => {
        if (li.type === 'subtitle' && li.stage === 'Operasi') {
          const indx = this.list.findIndex((lis) => {
            return (
              lis.type === 'subtitle' &&
              lis.stage === 'Operasi' &&
              lis.id === li.id
            );
          });
          idx.push(indx);
        }
      });
      return idx;
    },
    indexPascaOperasi() {
      const idx = [];
      this.list.map((li) => {
        if (li.type === 'subtitle' && li.stage === 'Pasca Operasi') {
          const indx = this.list.findIndex((lis) => {
            return (
              lis.type === 'subtitle' &&
              lis.stage === 'Pasca Operasi' &&
              lis.id === li.id
            );
          });
          idx.push(indx);
        }
      });
      return idx;
    },
  },
  watch: {
    activeName() {
      this.$refs.formdetail.resetError();
    },
    currentPagePraKonstruksi() {
      this.$refs.formdetail.resetError();
    },
    currentPageKonstruksi() {
      this.$refs.formdetail.resetError();
    },
    currentPageOperasi() {
      this.$refs.formdetail.resetError();
    },
    currentPagePascaOperasi() {
      this.$refs.formdetail.resetError();
    },
  },
  methods: {
    handlePaginate(direction, stage) {
      if (direction === 'left') {
        if (stage === 'pra konstruksi') {
          this.currentPagePraKonstruksi--;
        } else if (stage === 'konstruksi') {
          this.currentPageKonstruksi--;
        } else if (stage === 'operasi') {
          this.currentPageOperasi--;
        } else if (stage === 'pasca operasi') {
          this.currentPagePascaOperasi--;
        }
      } else if (direction === 'right') {
        if (stage === 'pra konstruksi') {
          this.currentPagePraKonstruksi++;
        } else if (stage === 'konstruksi') {
          this.currentPageKonstruksi++;
        } else if (stage === 'operasi') {
          this.currentPageOperasi++;
        } else if (stage === 'pasca operasi') {
          this.currentPagePascaOperasi++;
        }
      }
      this.$emit('backuplist');
      this.destroyForAWhile();
    },
    destroyForAWhile() {
      if (this.timeoutId) {
        clearTimeout(this.timeoutId);
      }

      this.showDetail = false;
      this.loadingDestroy = true;

      this.timeoutId = setTimeout((e) => {
        this.showDetail = true;
        this.loadingDestroy = false;
      }, 1000);
    },
    setActiveAndal(stage, id) {
      const activeName = stage.toLowerCase().replace(' ', '-');
      this.activeName = activeName;
      let index = 0;
      this.list.map((li) => {
        if (li.type === 'subtitle' && li.stage === stage) {
          const indx = this.list.findIndex((lis) => {
            return (
              lis.type === 'subtitle' && lis.stage === stage && lis.id === id
            );
          });
          index = indx;
        }
      });

      if (stage === 'Pra Konstruksi') {
        const page = this.indexPraKonstruksi.findIndex((idx) => idx === index);
        this.currentPagePraKonstruksi = page + 1;
      }

      if (stage === 'Konstruksi') {
        const page = this.indexKonstruksi.findIndex((idx) => idx === index);
        this.currentPageKonstruksi = page + 1;
      }

      if (stage === 'Operasi') {
        const page = this.indexOperasi.findIndex((idx) => idx === index);
        this.currentPageOperasi = page + 1;
      }

      if (stage === 'Pasca Operasi') {
        const page = this.indexPascaOperasi.findIndex((idx) => idx === index);
        this.currentPagePascaOperasi = page + 1;
      }
    },
    handleSubmit() {
      this.$emit('handleSubmit');
    },
  },
};
</script>

<style>
.el-table .cell {
  word-break: break-word;
}
.pagination-andal {
  text-align: right;
}
.input-page {
  width: 60px;
}
</style>

<style scoped>
.loading-div {
  padding: 15px 0;
}
</style>
