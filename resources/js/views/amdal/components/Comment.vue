<template>
  <div
    v-if="!(isFormulator && comments.length === 0)"
    v-loading="loading"
    class="comment-container"
    @click.stop
  >
    <h4>MASUKAN/SARAN PERBAIKAN</h4>
    <div class="comment-list">
      <div v-if="!isFormulator" class="comment-card">
        <el-card style="margin-bottom: 10px">
          <div class="comment-body" style="padding-top: 20px">
            <el-select
              v-if="withstage"
              v-model="idStage"
              v-loading="loadingStage"
              placeholder="Pilih Tahap"
              style="width: 100%"
              :class="{ 'is-error': isStageError }"
            >
              <el-option
                v-for="item in stages"
                :key="item.id"
                :label="item.name"
                :value="item.id"
              />
            </el-select>
            <small v-if="isStageError" style="color: #f56c6c">
              Tahapan Wajib Dipilih
            </small>
            <div style="margin-bottom: 10px">{{ '' }}</div>
            <el-select
              v-model="column"
              placeholder="Pilih Kolom"
              style="width: 100%"
              :class="{ 'is-error': isColumnError }"
            >
              <el-option
                v-for="item in kolom"
                :key="item.label"
                :label="item.label"
                :value="item.value"
              />
            </el-select>
            <small v-if="isColumnError" style="color: #f56c6c">
              Kolom Wajib Dipilih
            </small>
            <div style="margin-bottom: 10px">{{ '' }}</div>
            <!-- <el-input
              v-model="comment"
              type="textarea"
              :rows="2"
              placeholder="Tulis Masukan..."
              :class="{ 'is-error': isCommentError }"
            /> -->
            <TextEditor
              v-model="comment"
              output-format="html"
              :menubar="''"
              :image="false"
              :toolbar="['bold italic underline bullist numlist fullscreen edit']"
              :height="50"
            />
            <small v-if="isCommentError" style="color: #f56c6c">
              Masukan Wajib Diisi
            </small>
            <div class="send-btn-container">
              <el-button
                :loading="loadingSubmitComment"
                type="primary"
                size="mini"
                @click="handleCheckSubmitComment"
              >
                {{ 'Kirim' }}
              </el-button>
            </div>
          </div>
        </el-card>
      </div>
      <div v-for="(com, index) in comments" :key="com.id" class="comment-card">
        <el-card style="margin-bottom: 10px">
          <div class="comment-header">
            <div>
              <p>{{ com.user }}</p>
              <p v-if="withstage">Tahap {{ com.stage }}</p>
              <p>{{ com.column_type }}</p>
              <p>{{ com.created_at }}</p>
            </div>
            <el-checkbox
              v-if="!isFormulator"
              v-model="comments[index].is_checked"
              @change="handleCheckedComment(comments[index].id)"
            />
          </div>
          <div class="comment-body" v-html="com.description" />
          <div v-for="rep in comments[index].replies" :key="rep.id">
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
            <TextEditor
              v-model="comments[index].temp_reply"
              output-format="html"
              :menubar="''"
              :image="false"
              :toolbar="['bold italic underline bullist numlist fullscreen']"
              :height="50"
            />
            <small v-if="errorReply[`reply-${index}`]" style="color: #f56c6c">
              Catatan Balasan Wajib Dipilih
            </small>
            <div class="send-btn-container">
              <el-button
                :loading="
                  loadingSubmitReply[`loading-reply-${comments[index].id}`]
                "
                type="primary"
                size="mini"
                @click="handleCheckSubmitReply(index, comments[index].id)"
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

<script>
import { mapGetters } from 'vuex';
import TextEditor from '@/components/Tinymce';
import Resource from '@/api/resource';
const projectStageResource = new Resource('project-stages');
const kaCommentResource = new Resource('ka-comment');

export default {
  name: 'Comment',
  components: {
    TextEditor,
  },
  props: {
    withstage: {
      type: Boolean,
      default: () => false,
    },
    impactidentification: {
      type: Number,
      default: () => 0,
    },
    commenttype: {
      type: String,
      default: () => '',
    },
    kolom: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      column: null,
      idStage: null,
      comment: null,
      reply: null,
      comments: [],
      stages: [],
      errorReply: {},
      idProject: this.$route.params.id,
      loading: false,
      loadingStage: false,
      loadingSubmitComment: false,
      loadingSubmitReply: {},
      // userInfo: {},
      isStageError: false,
      isColumnError: false,
      isCommentError: false,
    };
  },
  computed: {
    ...mapGetters({
      userInfo: 'user',
      userId: 'userId',
    }),
    isFormulator() {
      return this.$store.getters.roles.includes('formulator');
    },
  },
  watch: {
    impactidentification(newVal, oldVal) {
      this.getComments();
    },
  },
  created() {
    this.getStages();
    this.getComments();
    // this.getUserInfo();
  },
  methods: {
    async getStages() {
      this.loadingStage = true;
      const prjStages = await projectStageResource.list({
        ordered: true,
      });
      this.stages = prjStages.data;
      this.loadingStage = false;
    },
    async getComments() {
      this.loading = true;
      const comments = await kaCommentResource.list({
        withStage: this.withstage ? 'true' : 'false',
        impactIdentification: this.impactidentification,
        idProject: this.$route.params.id,
        commentType: this.commenttype,
        routeName: this.$route.name,
      });
      this.comments = comments;
      this.loading = false;
    },
    handleCheckSubmitComment() {
      this.clearError();

      const error = [];

      if (this.withstage && !this.idStage) {
        this.isStageError = true;
        error.push('error');
      }

      if (!this.column) {
        this.isColumnError = true;
        error.push('error');
      }

      if (!this.comment) {
        this.isCommentError = true;
        error.push('error');
      }

      if (error.length === 0) {
        this.handleSubmitComment();
      }
    },
    async handleSubmitComment() {
      this.loadingSubmitComment = true;

      const newComment = await kaCommentResource.store({
        type: 'parent-comment',
        description: this.comment,
        id_impact_identification: this.impactidentification,
        id_user: this.userInfo.id,
        column_type: this.column,
        id_stage: this.idStage,
        document_type: this.commenttype,
        id_project: this.idProject,
        routeName: this.$route.name,
      });

      this.comments.unshift(newComment);
      this.comment = null;
      this.idStage = null;
      this.column = null;
      this.$message({
        message: 'Komentar Berhasil Disimpan',
        type: 'success',
        duration: 5 * 1000,
      });
      this.loadingSubmitComment = false;
      this.$emit('addComment', true);
    },
    handleCheckSubmitReply(index, id) {
      this.errorReply = {};

      const reply = this.comments[index].temp_reply;
      if (!reply) {
        this.errorReply[`reply-${index}`] = true;
      } else {
        this.handleSubmitReply(id, reply);
      }
    },
    async handleSubmitReply(id, description) {
      this.loadingSubmitReply[`loading-reply-${id}`] = true;
      const newCommentReply = await kaCommentResource.store({
        type: 'reply-comment',
        description: description,
        id_comment: id,
        id_user: this.userInfo.id,
        id_impact_identification: this.impactidentification,
        document_type: this.commenttype,
        id_project: this.idProject,
        routeName: this.$route.name,
      });

      const indexComment = this.comments.findIndex((com) => com.id === id);

      this.comments[indexComment].replies.push(newCommentReply);
      this.comments[indexComment].temp_reply = null;
      this.$message({
        message: 'Catatan Balasan Berhasil Disimpan',
        type: 'success',
        duration: 5 * 1000,
      });
      this.loadingSubmitReply[`loading-reply-${id}`] = false;
    },
    async handleCheckedComment(id) {
      await kaCommentResource.store({
        type: 'checked-comment',
        id,
      });
    },
    async getUserInfo() {
      // this.userInfo = await this.$store.dispatch('user/getInfo');
    },
    clearError() {
      this.isStageError = false;
      this.isColumnError = false;
      this.isCommentError = false;
    },
  },
};
</script>

<style>
.is-error .el-input__inner,
.is-error .el-textarea__inner {
  border-color: #f56c6c;
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
.comment-body.reply {
  margin-top: -15px;
}
</style>
