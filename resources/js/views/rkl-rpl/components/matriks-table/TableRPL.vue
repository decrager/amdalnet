<template>
  <div>
    <el-table
      v-loading="loading"
      :data="list"
      :span-method="arraySpanMethod"
      :row-class-name="tableRowClassName"
      fit
      border
      highlight-current-row
      :header-cell-style="{
        background: 'rgb(58, 176, 111)',
        color: 'white',
        textAlign: 'center',
        border: '0px',
      }"
      style="font-size: 12px"
    >
      <el-table-column label="No" width="50px">
        <template slot-scope="scope">
          <span
            v-if="
              scope.row.type === 'subtitle' || scope.row.type === 'master-title'
            "
          >
            {{ scope.row.no }}
          </span>
          <div
            v-if="scope.row.type === 'subtitle'"
            class="btn-comment-container"
          >
            <el-button
              type="text"
              size="medium"
              @click.stop="showOrHideComment(scope.row.id)"
            >
              <i class="el-icon-s-comment" />
            </el-button>
          </div>
          <div
            v-if="scope.row.type === 'comments'"
            class="comment-container"
            @click.stop
          >
            <h4>MASUKAN/SARAN PERBAIKAN</h4>
            <div class="comment-list">
              <div v-if="!isFormulator" class="comment-card">
                <el-card style="margin-bottom: 10px">
                  <div class="comment-body" style="padding-top: 20px">
                    <el-select
                      v-model="impactColumnType"
                      placeholder="Pilih Kolom"
                      style="width: 100%; margin-bottom: 10px"
                    >
                      <el-option
                        v-for="item in kolom"
                        :key="item.label"
                        :label="item.label"
                        :value="item.value"
                      />
                    </el-select>
                    <!-- <el-input
                      v-model="impactComment"
                      type="textarea"
                      :rows="2"
                      placeholder="Tulis Masukan..."
                    /> -->
                    <Tinymce
                      v-model="impactComment"
                      output-format="html"
                      :menubar="''"
                      :image="false"
                      :toolbar="[
                        'bold italic underline bullist numlist fullscreen',
                      ]"
                      :height="50"
                    />
                    <div class="send-btn-container">
                      <el-button
                        :loading="loadingSubmitComment"
                        type="primary"
                        size="mini"
                        @click="handleSubmitComment"
                      >
                        {{ 'Kirim' }}
                      </el-button>
                    </div>
                  </div>
                </el-card>
              </div>
              <div
                v-for="(com, index) in list[scope.$index - 1].comments"
                :key="com.id"
                class="comment-card"
              >
                <el-card style="margin-bottom: 10px">
                  <div class="comment-header">
                    <div>
                      <p>{{ com.user }}</p>
                      <p>{{ com.column_type }}</p>
                      <p>{{ com.created_at }}</p>
                    </div>
                    <el-checkbox
                      v-if="!isFormulator"
                      v-model="
                        list[scope.$index - 1].comments[index].is_checked
                      "
                      @change="
                        handleCheckedComment(
                          list[scope.$index - 1].comments[index].id
                        )
                      "
                    />
                  </div>
                  <div class="comment-body" v-html="com.description" />
                  <div
                    v-for="rep in list[scope.$index - 1].comments[index]
                      .replies"
                    :key="rep.id"
                  >
                    <div class="comment-header reply">
                      <div>
                        <p>{{ rep.role }}</p>
                        <p>
                          {{ rep.created_at }}
                        </p>
                      </div>
                    </div>
                    <div class="comment-body reply" v-html="rep.description" />
                  </div>
                  <div v-if="!comments[index].is_checked" class="comment-reply">
                    <Tinymce
                      v-model="
                        list[scope.$index - 1].comments[index].reply_desc
                      "
                      output-format="html"
                      :menubar="''"
                      :image="false"
                      :toolbar="[
                        'bold italic underline bullist numlist fullscreen',
                      ]"
                      :height="50"
                    />
                    <div class="send-btn-container">
                      <el-button
                        type="primary"
                        size="mini"
                        @click="
                          handleSubmitReply(
                            list[scope.$index - 1].comments[index].id,
                            list[scope.$index - 1].comments[index].reply_desc
                          )
                        "
                      >
                        {{ 'Kirim' }}
                      </el-button>
                    </div>
                  </div>
                </el-card>
              </div>
            </div>
          </div>
        </template>
      </el-table-column>

      <el-table-column label="Dampak Lingkungan yang Dipantau">
        <el-table-column label="Jenis Dampak yang Timbul">
          <template slot-scope="scope">
            <span
              :class="{
                'row-title': Boolean(scope.row.type == 'title'),
              }"
            >
              {{ scope.row.name }}
            </span>
          </template>
        </el-table-column>

        <el-table-column label="Sumber Dampak">
          <template slot-scope="scope">
            <div v-if="scope.row.type == 'subtitle'">
              <div
                v-for="(source, index) in scope.row.impact_source"
                :key="index"
                style="margin-bottom: 7px"
              >
                <div
                  v-if="isFormulator && source.show"
                  style="text-align: right; margin-bottom: 3px"
                >
                  <el-button
                    type="danger"
                    size="mini"
                    icon="el-icon-close"
                    :disabled="isReadOnly"
                    @click.prevent="
                      !isReadOnly &&
                        deleteEditor(
                          source.num,
                          index,
                          scope.$index,
                          'impact source'
                        )
                    "
                  />
                </div>
                <div v-if="isReadOnly">
                  <span v-html="scope.row.impact_source[index].description" />
                </div>
                <div v-else>
                  <Tinymce
                    v-if="isFormulator && source.show"
                    :id="`source-${source.num}-${scope.$index}`"
                    v-model="scope.row.impact_source[index].description"
                    output-format="html"
                    :menubar="''"
                    :image="false"
                    :toolbar="[
                      'bold italic underline bullist numlist  preview undo redo fullscreen',
                    ]"
                    :height="50"
                  />
                  <div
                    v-if="!isFormulator"
                    v-html="scope.row.impact_source[index].description"
                  />
                </div>
              </div>
            </div>
            <el-button
              v-if="scope.row.type == 'subtitle' && isFormulator"
              icon="el-icon-plus"
              circle
              :disabled="isReadOnly"
              @click="!isReadOnly && handleAddImpactSource(scope.$index)"
            />
            <span v-else>{{ '' }}</span>
            <small
              v-if="checkError(scope.row.type, scope.$index, 'impact_source')"
              style="color: #f56c6c; display: block"
            >
              Sumber Dampak Wajib Diisi
            </small>
          </template>
        </el-table-column>
      </el-table-column>

      <el-table-column label="Indikator / Parameter">
        <template slot-scope="scope">
          <div v-if="scope.row.type == 'subtitle'">
            <div
              v-for="(indi, index) in scope.row.indicator"
              :key="index"
              style="margin-bottom: 7px"
            >
              <div
                v-if="isFormulator && indi.show"
                style="text-align: right; margin-bottom: 3px"
              >
                <el-button
                  type="danger"
                  size="mini"
                  icon="el-icon-close"
                  :disabled="isReadOnly"
                  @click.prevent="
                    !isReadOnly &&
                      deleteEditor(indi.num, index, scope.$index, 'indicator')
                  "
                />
              </div>
              <div v-if="isReadOnly">
                <span v-html="scope.row.indicator[index].description" />
              </div>
              <div v-else>
                <Tinymce
                  v-if="isFormulator && indi.show"
                  :id="`indicator-${indi.num}-${scope.$index}`"
                  v-model="scope.row.indicator[index].description"
                  output-format="html"
                  :menubar="''"
                  :image="false"
                  :toolbar="[
                    'bold italic underline bullist numlist  preview undo redo fullscreen',
                  ]"
                  :height="50"
                />
                <div
                  v-if="!isFormulator"
                  v-html="scope.row.indicator[index].description"
                />
              </div>
            </div>
          </div>
          <el-button
            v-if="scope.row.type == 'subtitle' && isFormulator"
            icon="el-icon-plus"
            circle
            :disabled="isReadOnly"
            @click="!isReadOnly && handleAddIndicator(scope.$index)"
          />
          <span v-else>{{ '' }}</span>
          <small
            v-if="checkError(scope.row.type, scope.$index, 'indicator')"
            style="color: #f56c6c; display: block"
          >
            Indikator Wajib Diisi
          </small>
        </template>
      </el-table-column>

      <el-table-column label="Bentuk Pemantauan Lingkungan Hidup">
        <el-table-column label="Metode Pengumpulan & Analisis Data">
          <template slot-scope="scope">
            <div v-if="scope.row.type == 'subtitle'">
              <div
                v-for="(collection_method, index) in scope.row
                  .collection_method"
                :key="index"
                style="margin-bottom: 7px"
              >
                <div
                  v-if="isFormulator && collection_method.show"
                  style="text-align: right; margin-bottom: 3px"
                >
                  <el-button
                    type="danger"
                    size="mini"
                    icon="el-icon-close"
                    :disabled="isReadOnly"
                    @click.prevent="
                      !isReadOnly &&
                        deleteEditor(
                          collection_method.num,
                          index,
                          scope.$index,
                          'collection method'
                        )
                    "
                  />
                </div>
                <div v-if="isReadOnly">
                  <span v-html="scope.row.collection_method[index].description" />
                </div>
                <div v-else>
                  <Tinymce
                    v-if="isFormulator && collection_method.show"
                    :id="`collection-method-${collection_method.num}-${scope.$index}`"
                    v-model="scope.row.collection_method[index].description"
                    output-format="html"
                    :menubar="''"
                    :image="false"
                    :toolbar="[
                      'bold italic underline bullist numlist  preview undo redo fullscreen',
                    ]"
                    :height="50"
                  />
                  <div
                    v-if="!isFormulator"
                    v-html="scope.row.collection_method[index].description"
                  />
                </div>
              </div>
            </div>
            <el-button
              v-if="scope.row.type == 'subtitle' && isFormulator"
              icon="el-icon-plus"
              circle
              :disabled="isReadOnly"
              @click="!isReadOnly && handleAddCollectionMethod(scope.$index)"
            />
            <span v-else>{{ '' }}</span>
            <small
              v-if="
                checkError(scope.row.type, scope.$index, 'collection_method')
              "
              style="color: #f56c6c; display: block"
            >
              Metode Pengumpulan & Analisis Data Wajib Diisi
            </small>
          </template>
        </el-table-column>

        <el-table-column label="Lokasi Pemantauan Lingkungan Hidup">
          <template slot-scope="scope">
            <div v-if="scope.row.type == 'subtitle'">
              <div
                v-for="(location, index) in scope.row.location"
                :key="index"
                style="margin-bottom: 7px"
              >
                <div
                  v-if="isFormulator && location.show"
                  style="text-align: right; margin-bottom: 3px"
                >
                  <el-button
                    type="danger"
                    size="mini"
                    icon="el-icon-close"
                    :disabled="isReadOnly"
                    @click.prevent="
                      !isReadOnly &&
                        deleteEditor(
                          location.num,
                          index,
                          scope.$index,
                          'location'
                        )
                    "
                  />
                </div>
                <div v-if="isReadOnly">
                  <span v-html="scope.row.location[index].description" />
                </div>
                <div v-else>
                  <Tinymce
                    v-if="isFormulator && location.show"
                    :id="`location-${location.num}-${scope.$index}`"
                    v-model="scope.row.location[index].description"
                    output-format="html"
                    :menubar="''"
                    :image="false"
                    :toolbar="[
                      'bold italic underline bullist numlist  preview undo redo fullscreen',
                    ]"
                    :height="50"
                  />
                  <div
                    v-if="!isFormulator"
                    v-html="scope.row.location[index].description"
                  />
                </div>
              </div>
            </div>
            <el-button
              v-if="scope.row.type == 'subtitle' && isFormulator"
              icon="el-icon-plus"
              circle
              :disabled="isReadOnly"
              @click="!isReadOnly && handleAddLocation(scope.$index)"
            />
            <span v-else>{{ '' }}</span>
            <small
              v-if="checkError(scope.row.type, scope.$index, 'location')"
              style="color: #f56c6c; display: block"
            >
              Lokasi Pemantauan Wajib Diisi
            </small>
          </template>
        </el-table-column>

        <el-table-column label="Waktu dan Frekuensi Pemantauan" align="center">
          <template slot-scope="scope">
            <el-input-number
              v-if="scope.row.type == 'subtitle'"
              v-model="scope.row.period_number"
              :min="0"
              :disabled="!isFormulator || isReadOnly"
              style="width: 100%"
              :class="{
                'is-error': checkError(
                  scope.row.type,
                  scope.$index,
                  'period_number'
                ),
              }"
            />
            <small
              v-if="checkError(scope.row.type, scope.$index, 'period_number')"
              style="color: #f56c6c"
            >
              Waktu Wajib Diisi
            </small>
            <span v-if="scope.row.type == 'subtitle'">x</span>
            <el-select
              v-if="scope.row.type == 'subtitle'"
              v-model="scope.row.period_description"
              placeholder="Pilihan"
              :disabled="!isFormulator || isReadOnly"
              :class="{
                'is-error': checkError(
                  scope.row.type,
                  scope.$index,
                  'period_description'
                ),
              }"
            >
              <el-option
                v-for="item in periode"
                :key="item.value"
                :label="item.label"
                :value="item.value"
                :disabled="isReadOnly"
              />
            </el-select>
            <span v-else>{{ '' }}</span>
            <small
              v-if="
                checkError(scope.row.type, scope.$index, 'period_description')
              "
              style="color: #f56c6c"
            >
              Frekuensi Wajib Dipilih
            </small>
          </template>
        </el-table-column>
      </el-table-column>

      <el-table-column label="Institusi Pemantauan Lingkungan Hidup">
        <el-table-column label="Pelaksana">
          <template slot-scope="scope">
            <el-input
              v-if="scope.row.type == 'subtitle'"
              v-model="scope.row.executor"
              type="textarea"
              :rows="2"
              :readonly="!isFormulator"
              :disabled="isReadOnly"
              :class="{
                'is-error': checkError(
                  scope.row.type,
                  scope.$index,
                  'executor'
                ),
              }"
            />
            <span v-else>{{ '' }}</span>
            <small
              v-if="checkError(scope.row.type, scope.$index, 'executor')"
              style="color: #f56c6c"
            >
              Pelaksana Wajib Diisi
            </small>
          </template>
        </el-table-column>

        <el-table-column label="Pengawas">
          <template slot-scope="scope">
            <el-input
              v-if="scope.row.type == 'subtitle'"
              v-model="scope.row.supervisor"
              type="textarea"
              :rows="2"
              :readonly="!isFormulator"
              :disabled="isReadOnly"
              :class="{
                'is-error': checkError(
                  scope.row.type,
                  scope.$index,
                  'supervisor'
                ),
              }"
            />
            <span v-else>{{ '' }}</span>
            <small
              v-if="checkError(scope.row.type, scope.$index, 'supervisor')"
              style="color: #f56c6c"
            >
              Pengawas Wajib Diisi
            </small>
          </template>
        </el-table-column>

        <el-table-column label="Penerima Laporan">
          <template slot-scope="scope">
            <el-input
              v-if="scope.row.type == 'subtitle'"
              v-model="scope.row.report_recipient"
              type="textarea"
              :rows="2"
              :readonly="!isFormulator"
              :disabled="isReadOnly"
              :class="{
                'is-error': checkError(
                  scope.row.type,
                  scope.$index,
                  'report_recipient'
                ),
              }"
            />
            <span v-else>{{ '' }}</span>
            <small
              v-if="
                checkError(scope.row.type, scope.$index, 'report_recipient')
              "
              style="color: #f56c6c"
            >
              Penerima Laporan Wajib Diisi
            </small>
          </template>
        </el-table-column>
      </el-table-column>
    </el-table>
    <div style="text-align: right; margin: 2em 0 1em">
      <span style="font-size: 0.8rem; margin-right: 0.5em">
        <em>{{ lastTime }}</em>
      </span>
      <el-button
        v-if="isFormulator"
        :loading="loadingSubmit"
        class="filter-item"
        type="primary"
        style="font-size: 0.8rem"
        :disabled="isReadOnly"
        @click="!isReadOnly && checkSubmit()"
      >
        {{ 'Simpan Perubahan' }}
      </el-button>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import Tinymce from '@/components/Tinymce';
import Resource from '@/api/resource';
const rplResource = new Resource('matriks-rpl');

export default {
  name: 'TableRPL',
  components: {
    Tinymce,
  },
  data() {
    return {
      list: [],
      lastTime: null,
      loading: false,
      loadingMap: false,
      loadingSubmit: false,
      loadingSubmitComment: false,
      idProject: this.$route.params.id,
      selectedImpactCommentId: null,
      impactComment: null,
      impactColumnType: null,
      deletedImpactSource: [],
      deletedIndicator: [],
      deletedCollectionMethod: [],
      deletedLocation: [],
      // userInfo: {},
      errors: [],
      periode: [
        {
          label: 'per Hari',
          value: 'per Hari',
        },
        {
          label: 'per Minggu',
          value: 'per Minggu',
        },
        {
          label: 'per Bulan',
          value: 'per Bulan',
        },
        {
          label: 'per Tahun',
          value: 'per Tahun',
        },
      ],
      kolom: [
        {
          label: 'Jenis Dampak yang Timbul',
          value: 'Jenis Dampak yang Timbul',
        },
        {
          label: 'Indikator/Parameter',
          value: 'Indikator/Parameter',
        },
        {
          label: 'Sumber Dampak',
          value: 'Sumber Dampak',
        },
        {
          label: 'Metode Pengumpulan & Analisis Data',
          value: 'Metode Pengumpulan & Analisis Data',
        },
        {
          label: 'Lokasi Pemantauan Lingkungan Hidup',
          value: 'Lokasi Pemantauan Lingkungan Hidup',
        },
        {
          label: 'Waktu dan Frekuensi Pemantauan',
          value: 'Waktu dan Frekuensi Pemantauan',
        },
        {
          label: 'Pelaksana',
          value: 'Pelaksana',
        },
        {
          label: 'Pengawas',
          value: 'Pengawas',
        },
        {
          label: 'Penerima Laporan',
          value: 'Penerima Laporan',
        },
      ],
    };
  },
  computed: {
    ...mapGetters({
      userInfo: 'user',
      userId: 'userId',
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

    isFormulator() {
      return this.$store.getters.roles.includes('formulator');
    },
  },
  created() {
    this.getRPL();
    this.getLastimeRPL();
    // this.getUserInfo();
  },
  methods: {
    async getRPL() {
      this.loading = true;
      const list = await rplResource.list({
        idProject: this.idProject,
      });
      this.list = list.map((x) => {
        if (x.type === 'subtitle') {
          const impactSource = x.impact_source.map((y, indx) => {
            y.num = indx + 1;
            y.show = true;
            return y;
          });
          x.impact_source = impactSource;

          const indicator = x.indicator.map((y, indx) => {
            y.num = indx + 1;
            y.show = true;
            return y;
          });
          x.indicator = indicator;

          const collection_method = x.collection_method.map((y, indx) => {
            y.num = indx + 1;
            y.show = true;
            return y;
          });
          x.collection_method = collection_method;

          const location = x.location.map((y, indx) => {
            y.num = indx + 1;
            y.show = true;
            return y;
          });
          x.location = location;
        }

        return x;
      });
      this.loading = false;
    },

    async getLastimeRPL() {
      this.lastTime = await rplResource.list({
        lastTime: 'true',
        idProject: this.idProject,
      });
    },
    async handleUploadChange(file, fileList) {
      const formData = new FormData();
      formData.append('idProject', this.idProject);
      formData.append('map_file', file.raw);
      formData.append('map', 'true');
      this.loadingMap = true;
      await rplResource.store(formData);
      this.loadingMap = false;
      this.$message({
        message: 'Data is saved Successfully',
        type: 'success',
        duration: 5 * 1000,
      });
    },
    checkSubmit() {
      let errors = 0;

      this.errors = this.list.map((x) => {
        if (x.type === 'subtitle') {
          if (
            !x.indicator ||
            !x.impact_source ||
            !x.period_number ||
            !x.period_description ||
            !x.executor ||
            !x.supervisor ||
            !x.report_recipient ||
            x.impact_source.length === 0 ||
            x.indicator.length === 0 ||
            x.collection_method.length === 0 ||
            x.location.length === 0
          ) {
            errors++;
          }

          let impactSourceError =
            x.impact_source.filter((z) => z.show === true).length === 0;

          if (!impactSourceError) {
            const filter = x.impact_source.filter((y) => {
              return Boolean(!y.description) && y.show === true;
            });

            if (filter.length > 0) {
              impactSourceError = true;
              errors++;
            }
          }

          let indicatorError =
            x.indicator.filter((z) => z.show === true).length === 0;

          if (!indicatorError) {
            const filter = x.indicator.filter((y) => {
              return Boolean(!y.description) && y.show === true;
            });

            if (filter.length > 0) {
              indicatorError = true;
              errors++;
            }
          }

          let collectionMethodError =
            x.collection_method.filter((z) => z.show === true).length === 0;

          if (!collectionMethodError) {
            const filter = x.collection_method.filter((y) => {
              return Boolean(!y.description) && y.show === true;
            });

            if (filter.length > 0) {
              collectionMethodError = true;
              errors++;
            }
          }

          let locationError =
            x.location.filter((z) => z.show === true).length === 0;

          if (!locationError) {
            const filter = x.location.filter((y) => {
              return Boolean(!y.description) && y.show === true;
            });

            if (filter.length > 0) {
              locationError = true;
              errors++;
            }
          }

          return {
            indicator: indicatorError,
            impact_source: impactSourceError,
            collection_method: collectionMethodError,
            location: locationError,
            period_number: !x.period_number,
            period_description: !x.period_description,
            executor: !x.executor,
            supervisor: !x.supervisor,
            report_recipient: !x.report_recipient,
          };
        }

        return {};
      });

      if (errors === 0) {
        this.handleSubmit();
      }
    },
    checkError(scopeType, idx, name) {
      if (scopeType === 'subtitle') {
        if (this.errors.length > 0) {
          if (this.errors[idx][name]) {
            return true;
          }
        }
      }

      return false;
    },
    async handleSubmit() {
      this.loadingSubmit = true;
      const sendForm = this.list.filter((com) => com.type === 'subtitle');
      const finalForm = sendForm.map((x) => {
        const impactSource = x.impact_source.filter((y) => {
          return y.show === true;
        });
        x.impact_source = impactSource;

        const indicator = x.indicator.filter((y) => {
          return y.show === true;
        });
        x.indicator = indicator;

        const collection_method = x.collection_method.filter((y) => {
          return y.show === true;
        });
        x.collection_method = collection_method;

        const location = x.location.filter((y) => {
          return y.show === true;
        });
        x.location = location;
        return x;
      });
      const time = await rplResource.store({
        monitor: finalForm,
        deletedImpactSource: this.deletedImpactSource,
        deletedIndicator: this.deletedIndicator,
        deletedCollectionMethod: this.deletedCollectionMethod,
        deletedLocation: this.deletedLocation,
        type: this.lastTime ? 'update' : 'new',
        idProject: this.$route.params.id,
      });
      this.loadingSubmit = false;
      this.lastTime = time;
      this.getRPL();
      this.$message({
        message: 'Data is saved Successfully',
        type: 'success',
        duration: 5 * 1000,
      });
      this.deletedImpactSource = [];
      this.deletedIndicator = [];
      this.deletedCollectionMethod = [];
      this.deletedLocation = [];
    },
    async handleSubmitComment() {
      this.loadingSubmitComment = true;

      const newComment = await rplResource.store({
        type: 'impact-comment',
        description: this.impactComment,
        id_impact_identification: this.selectedImpactCommentId,
        id_user: this.userInfo.id,
        column_type: this.impactColumnType,
        id_project: this.$route.params.id,
      });

      const indexImpact = this.list.findIndex((ide) => {
        return (
          ide.id === this.selectedImpactCommentId && ide.type === 'subtitle'
        );
      });

      this.list[indexImpact].comments.unshift(newComment);

      this.loadingSubmitComment = false;
      this.impactComment = null;
      this.impactColumnType = null;
    },
    async handleSubmitReply(id, description) {
      const newCommentReply = await rplResource.store({
        type: 'impact-comment-reply',
        description: description,
        id_comment: id,
        id_user: this.userInfo.id,
        id_impact_identification: this.selectedImpactCommentId,
        id_project: this.$route.params.id,
      });

      const indexImpact = this.list.findIndex((ide) => {
        return (
          ide.id === this.selectedImpactCommentId && ide.type === 'subtitle'
        );
      });

      const indexComment = this.list[indexImpact].comments.findIndex(
        (com) => com.id === id
      );

      this.list[indexImpact].comments[indexComment].replies.push(
        newCommentReply
      );
      this.list[indexImpact].comments[indexComment].reply_desc = null;
    },
    async handleCheckedComment(id) {
      await rplResource.store({
        type: 'checked-comment',
        id,
      });
    },
    // async getUserInfo() {
    //   this.userInfo = await this.$store.dispatch('user/getInfo');
    // },
    deleteEditor(num, idx, idxList, documentType) {
      if (documentType === 'impact source') {
        const impactSource = this.list[idxList].impact_source[idx];
        if (impactSource.id) {
          this.deletedImpactSource.push(impactSource.id);
        }
        this.list[idxList].impact_source[idx].show = false;
      } else if (documentType === 'indicator') {
        const indicator = this.list[idxList].indicator[idx];
        if (indicator.id) {
          this.deletedIndicator.push(indicator.id);
        }
        this.list[idxList].indicator[idx].show = false;
      } else if (documentType === 'collection method') {
        const collection_method = this.list[idxList].collection_method[idx];
        if (collection_method.id) {
          this.deletedCollectionMethod.push(collection_method.id);
        }
        this.list[idxList].collection_method[idx].show = false;
      } else if (documentType === 'location') {
        const location = this.list[idxList].location[idx];
        if (location.id) {
          this.deletedLocation.push(location.id);
        }
        this.list[idxList].location[idx].show = false;
      }
    },
    handleAddImpactSource(idx) {
      const data = this.list[idx].impact_source;
      this.list[idx].impact_source.push({
        num: data.length === 0 ? 1 : data[data.length - 1].num + 1,
        id: null,
        description: null,
        show: true,
      });
    },
    handleAddIndicator(idx) {
      const data = this.list[idx].indicator;
      this.list[idx].indicator.push({
        num: data.length === 0 ? 1 : data[data.length - 1].num + 1,
        id: null,
        description: null,
        show: true,
      });
    },
    handleAddCollectionMethod(idx) {
      const data = this.list[idx].collection_method;
      this.list[idx].collection_method.push({
        num: data.length === 0 ? 1 : data[data.length - 1].num + 1,
        id: null,
        description: null,
        show: true,
      });
    },
    handleAddLocation(idx) {
      const data = this.list[idx].location;
      this.list[idx].location.push({
        num: data.length === 0 ? 1 : data[data.length - 1].num + 1,
        id: null,
        description: null,
        show: true,
      });
    },
    arraySpanMethod({ row, column, rowIndex, columnIndex }) {
      if (row.type === 'title' && columnIndex === 1) {
        return [1, 9];
      }

      if (row.type === 'master-title' && columnIndex === 0) {
        return [1, 10];
      }

      if (row.type === 'comments' && columnIndex === 0) {
        return [1, 10];
      }
    },
    tableRowClassName({ row, rowIndex }) {
      if (row.type === 'master-title') {
        return 'master-title';
      }
      if (row.type === 'comments') {
        return `beige comment-${row.id} comment-hide`;
      }
      return '';
    },
    showOrHideComment(id) {
      this.selectedImpactCommentId = id;
      document.querySelectorAll('.beige').forEach((e) => {
        if (!e.classList.contains(`comment-${id}`)) {
          e.classList.add('comment-hide');
        }
      });
      if (
        document
          .querySelector(`.comment-${id}`)
          .classList.contains('comment-hide')
      ) {
        document
          .querySelector(`.comment-${id}`)
          .classList.remove('comment-hide');
      } else {
        document.querySelector(`.comment-${id}`).classList.add('comment-hide');
      }
    },
  },
};
</script>

<style>
.row-title {
  font-weight: bold;
}
.el-table .cell {
  word-break: break-word;
}
.master-title {
  background: #81de69 !important;
  color: white;
}
.comment-hide {
  display: none;
}
.btn-comment-container button {
  font-size: 20px;
}
.comment-card {
  width: 300px;
  margin-right: 30px;
  display: inline-block;
  margin-bottom: 13px;
  vertical-align: top;
}
.send-btn-container {
  text-align: right;
  margin-top: 10px;
}
.beige {
  background: beige !important;
}
.beige td {
  padding-top: 0 !important;
}
.comment-body {
  font-size: 15px;
  padding-right: 20px;
  padding-left: 20px;
  padding-bottom: 20px;
}
.comment-header {
  padding-top: 20px;
  padding-left: 20px;
  padding-right: 20px;
  display: flex;
  justify-content: space-between;
}
.comment-header p {
  font-size: 12px;
  padding: 0;
  margin: 0;
  margin-bottom: 1px;
  font-weight: lighter;
}
.comment-card .el-card__body {
  padding: 0 !important;
}
.comment-reply {
  background: #ceeccb;
  padding: 20px;
  border-top: 1px solid #ccc;
}
.comment-header.reply,
.comment-body.reply {
  background: #ceeccb;
}
.upload-demo {
  font-size: 0.8rem;
  background: #e2cd39;
  padding: 8px;
  color: white;
  border-radius: 3px;
}
.is-error .el-input__inner,
.is-error .el-radio__inner,
.is-error .el-textarea__inner {
  border-color: #f56c6c;
}
</style>
