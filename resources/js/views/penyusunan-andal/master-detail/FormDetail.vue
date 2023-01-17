<template>
  <el-row v-if="isAndalNotEmpty" :gutter="24">
    <el-col :md="12" :sm="24">
      <div class="form-group">
        <label>Tahap</label>
        <el-input v-model="andal.stage" :disabled="isReadOnly" :readonly="true" />
      </div>
      <div class="form-group">
        <label>Dampak Penting Hipotetik</label>
        <el-input v-model="andal.name" :disabled="isReadOnly" :readonly="true" />
      </div>
      <div class="form-group">
        <label>Komponen Rona Awal Lingkungan</label>
        <el-input v-model="andal.ronaAwal" :disabled="isReadOnly" :readonly="true" />
      </div>
      <!-- <div class="form-group">
        <label>Besaran Dampak</label>
        <el-input v-model="andal.impact_size" :readonly="!isFormulator" />
      </div> -->
      <div class="form-group">
        <label>Perubahan Kondisi Dengan dan Tanpa Rencana Kegiatan</label>
        <div class="wrapper-form">
          <div class="form-group">
            <label>1. Kondisi Saat Studi Dilakukan</label>]
            <div v-if="isReadOnly">
              <span v-html="andal.studies_condition" />
            </div>
            <div v-else>
              <Tinymce
                v-if="isFormulator"
                v-model="andal.studies_condition"
                output-format="html"
                :menubar="''"
                :image="false"
                :toolbar="[
                  'bold italic underline bullist numlist  preview undo redo fullscreen',
                ]"
                :height="50"
              />
              <div v-else v-html="andal.studies_condition" />
            </div>
            <small v-if="errors.studies_condition" style="color: #f56c6c">
              Kondisi Wajib Diisi
            </small>
          </div>
          <div class="form-group">
            <label>
              2.A. Perkembangan Kondisi TANPA Adanya Rencana Kegiatan
            </label>
            <div v-if="isReadOnly">
              <span v-html="andal.condition_dev_no_plan" />
            </div>
            <div v-else>
              <Tinymce
                v-if="isFormulator"
                v-model="andal.condition_dev_no_plan"
                output-format="html"
                :menubar="''"
                :image="false"
                :toolbar="[
                  'bold italic underline bullist numlist  preview undo redo fullscreen',
                ]"
                :height="50"
              />
              <div v-else v-html="andal.condition_dev_no_plan" />
            </div>
            <small v-if="errors.condition_dev_no_plan" style="color: #f56c6c">
              Kondisi Wajib Diisi
            </small>
          </div>
          <div class="form-group">
            <label>
              B. Perkembangan Kondisi DENGAN Adanya Rencana Kegiatan
            </label>
            <div v-if="isReadOnly">
              <span v-html="andal.condition_dev_with_plan" />
            </div>
            <div v-else>
              <Tinymce
                v-if="isFormulator"
                v-model="andal.condition_dev_with_plan"
                output-format="html"
                :menubar="''"
                :image="false"
                :toolbar="[
                  'bold italic underline bullist numlist  preview undo redo fullscreen',
                ]"
                :height="50"
              />
              <div v-else v-html="andal.condition_dev_with_plan" />
            </div>
            <small v-if="errors.condition_dev_with_plan" style="color: #f56c6c">
              Kondisi Wajib Diisi
            </small>
          </div>
          <div class="form-group">
            <label> 3. Selisih Besaran Dampak</label>
            <div v-if="isReadOnly">
              <span v-html="andal.impact_size_difference" />
            </div>
            <div v-else>
              <Tinymce
                v-if="isFormulator"
                v-model="andal.impact_size_difference"
                output-format="html"
                :menubar="''"
                :image="false"
                :toolbar="[
                  'bold italic underline bullist numlist  preview undo redo fullscreen',
                ]"
                :height="50"
              />
              <div v-else v-html="andal.impact_size_difference" />
            </div>
            <small v-if="errors.impact_size_difference" style="color: #f56c6c">
              Selisih Besaran Dampak Wajib Diisi
            </small>
          </div>
        </div>
      </div>
      <el-row :gutter="24">
        <el-col :md="12" :sm="24">
          <label>Kategori Dampak</label>
          <el-select
            v-model="andal.impact_type"
            placeholder="Pilih Jenis Dampak"
            :disabled="!isFormulator || isReadOnly"
            :class="{ 'is-error': errors.impact_type }"
          >
            <el-option
              v-for="item in jenisDampak"
              :key="item.value"
              :label="item.label"
              :value="item.value"
              :disabled="isReadOnly"
            />
          </el-select>
          <small v-if="errors.impact_type" style="color: #f56c6c">
            Jenis Dampak Wajib Dipilih
          </small>
        </el-col>
        <el-col :md="12" :sm="24">
          <label>Hasil Evaluasi Dampak</label>
          <span style="display: block">
            {{ dampakPentingConclusion(andal.important_trait) }}
          </span>
          <el-select
            v-model="andal.impact_eval_result"
            placeholder="Pilih Hasil Evaluasi Dampak"
            :disabled="!isFormulator || isReadOnly"
            :class="{ 'is-error': errors.impact_eval_result }"
          >
            <el-option
              v-for="item in hasilEvaluasiDampak"
              :key="item.value"
              :label="item.label"
              :value="item.value"
              :disabled="isReadOnly"
            />
          </el-select>
          <small v-if="errors.impact_eval_result" style="color: #f56c6c">
            Hasil Evaluasi Dampak Wajib Dipilih
          </small>
        </el-col>
      </el-row>
    </el-col>
    <el-col :md="12" :sm="24">
      <label>Sifat Penting</label>
      <div class="wrapper-form">
        <div
          v-for="(it, index) in andal.important_trait"
          :key="it.id"
          class="form-group"
        >
          <el-row :gutter="24">
            <el-col :md="16" :sm="24">
              <label> {{ index + 1 }}. {{ it.description }} </label>
            </el-col>
            <el-col :md="8" :sm="24">
              <label>{{ index === 0 ? 'Pilih Sifat Penting' : ' ' }}</label>
            </el-col>
          </el-row>
          <el-row :gutter="24" style="display: flex; align-items: center">
            <el-col :md="16" :sm="24">
              <div v-if="isReadOnly">
                <span v-html="andal.important_trait[index].desc" />
              </div>
              <div v-else>
                <Tinymce
                  v-if="isFormulator"
                  v-model="andal.important_trait[index].desc"
                  output-format="html"
                  :menubar="''"
                  :image="false"
                  :toolbar="[
                    'bold italic underline bullist numlist  preview undo redo fullscreen',
                  ]"
                  :height="50"
                />
                <div v-else v-html="andal.important_trait[index].desc" />
              </div>
              <small
                v-if="importantTraitError(index, 'desc')"
                style="color: #f56c6c"
              >
                Wajib Diisi
              </small>
            </el-col>
            <el-col :md="8" :sm="24">
              <el-radio-group
                v-model="andal.important_trait[index].important_trait"
                :disabled="isReadOnly"
                style="display: grid; grid-template-columns: 0.5fr 0.5fr"
              >
                <el-radio
                  label="+P"
                  :disabled="!isFormulator || isReadOnly"
                  :class="{ 'is-error': errors.status }"
                >
                  +P
                </el-radio>
                <el-radio
                  label="-P"
                  :disabled="!isFormulator || isReadOnly"
                  :class="{ 'is-error': errors.status }"
                >
                  -P
                </el-radio>
                <el-radio
                  label="+TP"
                  :disabled="!isFormulator || isReadOnly"
                  :class="{ 'is-error': errors.status }"
                >
                  +TP
                </el-radio>
                <el-radio
                  label="-TP"
                  :disabled="!isFormulator || isReadOnly"
                  :class="{ 'is-error': errors.status }"
                >
                  -TP
                </el-radio>
              </el-radio-group>
              <small
                v-if="importantTraitError(index, 'important_trait')"
                style="color: #f56c6c"
              >
                Sifat Penting Wajib Dipilih
              </small>
            </el-col>
          </el-row>
        </div>
      </div>
    </el-col>
    <el-col
      v-if="isFormulator"
      :md="24"
      :sm="24"
      style="text-align: right; margin-top: 10px"
    >
      <el-button :disabled="isReadOnly" @click="!isReadOnly && reset()">Reset</el-button>
      <el-button type="primary" :loading="loadingsubmit" :disabled="isReadOnly" @click="!isReadOnly && checkSubmit()">
        Simpan
      </el-button>
    </el-col>
    <el-col :md="24" :sm="24">
      <Comment
        :impactidentification="andal.id"
        commenttype="andal"
        :kolom="kolom"
      />
    </el-col>
  </el-row>
</template>

<script>
import { mapGetters } from 'vuex';
import Comment from '@/views/amdal/components/Comment.vue';
import Tinymce from '@/components/Tinymce';

export default {
  name: 'FormDetail',
  components: {
    Comment,
    Tinymce,
  },
  props: {
    andal: {
      type: Object,
      default: () => {},
    },
    loadingsubmit: Boolean,
  },
  data() {
    return {
      hasilEvaluasiDampak: [
        {
          label: 'Positif',
          value: 'Positif',
        },
        {
          label: 'Negatif',
          value: 'Negatif',
        },
      ],
      jenisDampak: [
        {
          label: 'Primer',
          value: 'Primer',
        },
        {
          label: 'Sekunder',
          value: 'Sekunder',
        },
        {
          label: 'Tersier',
          value: 'Tersier',
        },
      ],
      kolom: [
        {
          label: 'Dampak Penting Hipotetik',
          value: 'Dampak Penting Hipotetik',
        },
        {
          label: 'Komponen Rona Awal Lingkungan',
          value: 'Komponen Rona Awal Lingkungan',
        },
        {
          label: 'Sifat Penting',
          value: 'Sifat Penting',
        },
        {
          label: 'Perubahan Kondisi Dengan dan Tanpa Rencana Kegiatan',
          value: 'Perubahan Kondisi Dengan dan Tanpa Rencana Kegitan',
        },
        {
          label: 'Jenis Dampak',
          value: 'Jenis Dampak',
        },
        {
          label: 'Hasil Evaluasi Dampak',
          value: 'Hasil Evaluasi Dampak',
        },
      ],
      errors: {
        impact_size: false,
        impact_type: false,
        impact_eval_result: false,
        studies_condition: false,
        condition_dev_no_plan: false,
        condition_dev_with_plan: false,
        impact_size_difference: false,
        important_trait: [],
      },
    };
  },
  computed: {
    ...mapGetters({
      markingStatus: 'markingStatus',
    }),

    isReadOnly() {
      const data = [
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

    isAndalNotEmpty() {
      if (this.andal) {
        return true;
      }

      return false;
    },
    isFormulator() {
      return this.$store.getters.roles.includes('formulator');
    },
  },
  methods: {
    reset() {
      this.andal.impact_size = null;
      this.andal.studies_condition = null;
      this.andal.condition_dev_no_plan = null;
      this.andal.condition_dev_with_plan = null;
      this.andal.impact_size_difference = null;
      this.andal.impact_type = null;
      this.andal.impact_eval_result = null;

      for (let i = 0; i < this.andal.important_trait.length; i++) {
        this.andal.important_trait[i].desc = null;
        this.andal.important_trait[i].important_trait = null;
      }
    },
    resetError() {
      // eslint-disable-next-line no-undef
      _.each(this.errors, (value, key) => {
        if (key === 'important_trait') {
          this.errors[key] = [];
        } else {
          this.errors[key] = false;
        }
      });
    },
    checkSubmit() {
      this.resetError();
      let errors = 0;

      // eslint-disable-next-line no-undef
      _.each(this.andal.important_trait, (value, key) => {
        if (!value.desc) {
          errors++;
        }

        if (!value.important_trait) {
          errors++;
        }

        this.errors.important_trait.push({
          desc: !value.desc,
          important_trait: !value.important_trait,
        });
      });
      // eslint-disable-next-line no-undef
      _.each(this.andal, (value, key) => {
        if (key !== 'important_trait') {
          if (!value) {
            errors++;
            this.errors[key] = true;
          }
        }
      });

      if (errors === 0) {
        this.handleSubmit();
      }
    },
    handleSubmit() {
      this.$emit('handleSubmit');
    },
    dampakPentingConclusion(importantTrait) {
      if (importantTrait.length > 0) {
        const dampakPenting = importantTrait.filter((im) => {
          return im.important_trait === '+P' || im.important_trait === '-P';
        });
        if (dampakPenting.length > 0) {
          return 'Dampak Penting';
        } else {
          return 'Dampak Tidak Penting';
        }
      }

      return '';
    },
    importantTraitError(idx, name) {
      if (this.errors.important_trait.length < 1) {
        return false;
      }

      return this.errors.important_trait[idx][name];
    },
  },
};
</script>

<style scoped>
.form-group {
  margin-bottom: 10px;
}
.wrapper-form {
  border: 1px solid black;
  padding: 8px;
}
</style>

<style>
.is-error .el-input__inner,
.is-error .el-radio__inner,
.is-error .el-textarea__inner {
  border-color: #f56c6c;
}
</style>
