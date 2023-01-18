<template>
  <div class="login">
    <div v-if="form === 'login'" class="login-container">
      <div class="login-content">
        <el-form
          ref="loginForm"
          :model="loginForm"
          :rules="loginRules"
          class="login-form"
          auto-complete="on"
          label-position="left"
        >
          <div class="title-wrap">
            <router-link to="/">
              <img
                class="logo"
                alt="Amdalnet"
                :src="logo"
              >
              <!-- h3 class="title">
                {{ $t('login.title') }}
                <lang-select class="set-language" />
              </h3 -->
            </router-link>
          </div>
          <el-form-item prop="email">
            <span class="svg-container">
              <svg-icon icon-class="user" />
            </span>
            <el-input
              v-model="loginForm.email"
              name="email"
              type="text"
              auto-complete="on"
              :placeholder="$t('login.email')"
            />
          </el-form-item>
          <el-form-item prop="password">
            <span class="svg-container">
              <svg-icon icon-class="password" />
            </span>
            <el-input
              v-model="loginForm.password"
              name="password"
              auto-complete="on"
              placeholder="password"
              :type="pwdType"
              @keyup.enter.native="handleLogin"
            />
            <span class="show-pwd" @click="showPwd">
              <svg-icon icon-class="eye" />
            </span>
          </el-form-item>
          <el-form-item>
            <el-button :loading="loading" type="primary" style="width:100%;" @click.native.prevent="handleLogin">
              {{ $t('login.logIn') }}
            </el-button>
          </el-form-item>
          <el-row type="flex" class="row-bg" justify="space-between">
            <el-button type="text" style="background-color: transparent; color: blue;" @click="handleOpenResetPassword">
              <span>Lupa Kata Sandi?</span>
            </el-button>
            <el-button type="text" style="background-color: transparent; color: blue;" @click="handleOpenRegister">Tidak Memiliki Akun?
              <span style="color: red">Buat Akun Baru</span>
            </el-button>
          </el-row>
          <el-divider>atau</el-divider>
          <div style="text-align: start; margin-bottom: 15px;">
            <span style="font-size: smaller; color: black;">Masuk dengan Akun OSS RBA</span>
          </div>
          <el-row>
            <router-link to="/oss/login">
              <el-button type="primary" style="width: 100%;">
                <div>
                  <img
                    alt="OSS RBA"
                    :src="logoOSS"
                    style="max-height: 20px !important;"
                  >
                </div>
              </el-button>
            </router-link>
          </el-row>
        </el-form>
      </div>
    </div>
    <!-- <div v-else-if="form === 'resetpassword'" class="login-container">
      <div class="login-content">
        <el-form ref="resetForm" :model="resetForm" :rules="resetRules" class="reset-form" auto-complete="on" label-position="left">
          <div class="title-wrap">
            <h3> class="title">
              {{ $t('login.resetPassword') }}
            </h3>
          </div>
          <el-form-item prop="email">
            <span class="svg-container">
              <svg-icon icon-class="user" />
            </span>
            <el-input v-model="resetForm.email" name="email" type="text" auto-complete="on" :placeholder="$t('login.email')" />
          </el-form-item>
          <el-form-item>
            <el-button :loading="loading" type="primary" style="width:100%;" @click.native.prevent="handleLogin">
              {{ $t('login.lanjutReset') }}
            </el-button>
          </el-form-item>
          <el-row type="flex" justify="space-between">
            <el-col :span="10">
              <el-button type="text" style="background-color: transparent; color: blue;" @click="handleCancelReset">Kembali ke Halaman Login</el-button>
            </el-col>
          </el-row>
        </el-form>
      </div>
    </div> -->
    <div v-else class="registration-container">
      <el-card shadow="always">
        <el-row type="flex" justify="center">
          <el-col :span="8">
            <h2>{{ $t('login.registrationForm') }}</h2>
          </el-col>
        </el-row>
        <el-row type="flex" justify="space-between" style="margin-bottom: 20px">
          <el-radio-group v-model="user_type" @change="onChangeRadioUserType">
            <el-radio style="width: 250px" label="Pemrakarsa" border>Pemrakarsa Badan Usaha</el-radio>
            <el-radio style="width: 250px" label="Pemerintah" border>Pemrakarsa Pemerintah</el-radio>
            <el-radio style="width: 250px" label="Penyusun" border>Penyusun</el-radio>
          </el-radio-group>
        </el-row>
        <el-row v-if="user_type === 'Pemrakarsa'">
          <el-form ref="regPemrakarsa" :model="registrationForm" :rules="regPemrakarsaRules">
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="nib" :label="$t('login.nib')">
                  <el-input v-model="registrationForm.nib" name="nib" type="text" :placeholder="$t('login.nib')" />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Unggah Dokumen NIB dan SPPL dari OSS" prop="fileNibDocOss">
                  <classic-upload
                    :name="fileNibDocOssName"
                    style="border: 0 !important"
                    :fid="'nibDocOssFile'"
                    @handleFileUpload="handleFilenibDocOssUpload($event)"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="agency_type" :label="$t('login.companyType')">
                  <el-select
                    v-model="registrationForm.agency_type"
                    name="agency_type"
                    :placeholder="$t('login.companyType')"
                    style="width: 100%"
                  >
                    <el-option
                      v-for="item in companyTypeoptions"
                      :key="item.value"
                      :label="item.label"
                      :value="item.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="name" :label="$t('login.companyName')">
                  <el-input
                    v-model="registrationForm.name"
                    name="name"
                    type="text"
                    auto-complete="on"
                    :placeholder="$t('login.companyName')"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row>
              <el-form-item prop="address" :label="$t('login.address')">
                <el-input
                  v-model="registrationForm.address"
                  name="name"
                  type="textarea"
                  auto-complete="on"
                  :placeholder="$t('login.address')"
                />
              </el-form-item>
            </el-row>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="province" :label="$t('login.province')">
                  <el-select
                    v-model="registrationForm.province"
                    filterable
                    name="province"
                    :placeholder="$t('login.province')"
                    style="width: 100%"
                    @change="changeProvince"
                  >
                    <el-option
                      v-for="item in provinceOptions"
                      :key="item.value"
                      :label="item.label"
                      :value="item.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="district" :label="$t('login.district')">
                  <el-select
                    v-model="registrationForm.district"
                    filterable
                    name="district"
                    :placeholder="$t('login.district')"
                    style="width: 100%"
                  >
                    <el-option
                      v-for="item in districtOptions"
                      :key="item.value"
                      :label="item.label"
                      :value="item.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="email" :label="$t('login.email')">
                  <el-input
                    v-model="registrationForm.email"
                    name="name"
                    type="text"
                    auto-complete="on"
                    :placeholder="$t('login.email')"
                    suffix-icon="el-icon-message"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="password" :label="$t('login.password')">
                  <el-input
                    v-model="registrationForm.password"
                    name="password"
                    auto-complete="on"
                    placeholder="password"
                    :type="pwdType"
                  >
                    <span slot="append" class="show-pwd" @click="showPwd">
                      <svg-icon icon-class="eye" />
                    </span>
                  </el-input>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="pic" :label="$t('login.pic')">
                  <el-input
                    v-model="registrationForm.pic"
                    name="name"
                    type="text"
                    auto-complete="on"
                    :placeholder="$t('login.pic')"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="picRole" :label="$t('login.picRole')">
                  <el-input
                    v-model="registrationForm.picRole"
                    name="pic"
                    auto-complete="on"
                    :placeholder="$t('login.picRole')"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="fileAgencyUpload" :label="$t('login.companyLogo')">
                  <el-upload
                    drag
                    class="avatar-uploader"
                    action="https://jsonplaceholder.typicode.com/posts/"
                    :show-file-list="false"
                    :auto-upload="false"
                    :on-change="handleAgencyLogoUpload"
                    :before-upload="beforeAvatarUpload"
                  >
                    <img v-if="imageUrl" :src="imageUrl" class="avatar" alt="avatar">
                    <div v-else>
                      <i class="el-icon-upload" />
                      <div class="el-upload__text">Taruh File disini atau <em>tekan untuk unggah</em></div>
                      <div slot="tip" class="el-upload__tip">jpg/png files dengan ukuran kurang dari 2MB</div>
                    </div>
                  </el-upload>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="phone" :label="$t('login.phone')">
                  <el-input
                    v-model.number="registrationForm.phone"
                    name="phone"
                    auto-complete="on"
                    placeholder="Nomor Telepon"
                    suffix-icon="el-icon-phone"
                  >
                    <template slot="prepend">+62</template>
                  </el-input>
                </el-form-item>
              </el-col>
            </el-row>
          </el-form>
        </el-row>
        <el-row v-if="user_type === 'Pemerintah'">
          <el-form ref="regPemerintah" :model="registrationForm" :rules="regPemerintahRules">
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="agency_type" :label="$t('login.agencyType')">
                  <el-select
                    v-model="registrationForm.agency_type"
                    name="agency_type"
                    :placeholder="$t('login.agencyType')"
                    style="width: 100%"
                  >
                    <el-option
                      v-for="item in agencyTypeoptions"
                      :key="item.value"
                      :label="item.label"
                      :value="item.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="name" :label="$t('login.agencyName')">
                  <el-input
                    v-model="registrationForm.name"
                    name="name"
                    type="text"
                    auto-complete="on"
                    :placeholder="$t('login.agencyName')"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row>
              <el-form-item prop="address" :label="$t('login.address')">
                <el-input
                  v-model="registrationForm.address"
                  name="name"
                  type="textarea"
                  auto-complete="on"
                  :placeholder="$t('login.address')"
                />
              </el-form-item>
            </el-row>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="province" :label="$t('login.province')">
                  <el-select
                    v-model="registrationForm.province"
                    filterable
                    name="province"
                    :placeholder="$t('login.province')"
                    style="width: 100%"
                    @change="changeProvince"
                  >
                    <el-option
                      v-for="item in provinceOptions"
                      :key="item.value"
                      :label="item.label"
                      :value="item.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="district" :label="$t('login.district')">
                  <el-select
                    v-model="registrationForm.district"
                    filterable
                    name="district"
                    :placeholder="$t('login.district')"
                    style="width: 100%"
                  >
                    <el-option
                      v-for="item in districtOptions"
                      :key="item.value"
                      :label="item.label"
                      :value="item.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="email" :label="$t('login.email')">
                  <el-input
                    v-model="registrationForm.email"
                    name="name"
                    type="text"
                    auto-complete="on"
                    :placeholder="$t('login.email')"
                    suffix-icon="el-icon-message"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="password" :label="$t('login.password')">
                  <el-input
                    v-model="registrationForm.password"
                    name="password"
                    auto-complete="on"
                    placeholder="password"
                    :type="pwdType"
                  >
                    <span slot="append" class="show-pwd" @click="showPwd">
                      <svg-icon icon-class="eye" />
                    </span>
                  </el-input>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="pic" :label="$t('login.pic')">
                  <el-input
                    v-model="registrationForm.pic"
                    name="name"
                    type="text"
                    auto-complete="on"
                    :placeholder="$t('login.pic')"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="picRole" :label="$t('login.picRole')">
                  <el-input
                    v-model="registrationForm.picRole"
                    name="pic"
                    auto-complete="on"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="fileAgencyUpload" :label="$t('login.agencyLogo')">
                  <el-upload
                    drag
                    class="avatar-uploader"
                    action="https://jsonplaceholder.typicode.com/posts/"
                    :show-file-list="false"
                    :auto-upload="false"
                    :on-change="handleAgencyLogoUpload"
                    :before-upload="beforeAvatarUpload"
                  >
                    <img v-if="imageUrl" :src="imageUrl" class="avatar" alt="avatar">
                    <div v-else>
                      <i class="el-icon-upload" />
                      <div class="el-upload__text">Taruh File disini atau <em>tekan untuk unggah</em></div>
                      <div slot="tip" class="el-upload__tip">jpg/png files dengan ukuran kurang dari 2MB</div>
                    </div>
                  </el-upload>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="phone" :label="$t('login.phone')">
                  <el-input
                    v-model.number="registrationForm.phone"
                    name="phone"
                    auto-complete="on"
                    placeholder="Nomor Telepon"
                    suffix-icon="el-icon-phone"
                  >
                    <template slot="prepend">+62</template>
                  </el-input>
                </el-form-item>
              </el-col>
            </el-row>
          </el-form>
        </el-row>
        <el-row v-else-if="user_type === 'Penyusun'">
          <el-form ref="regPenyusun" :model="registrationForm" :rules="regPenyusunRules">
            <div style="background-color: #ddddd5; padding: 5px 0;">
              <p style="padding: 0 30px; font-size: 17px;"><b>Pastikan nama lengkap beserta gelar yang Anda masukkan
                sama dengan nama yang tercantum pada sertifikat penyusun ( Khusus penyusun ATPA atau KTPA )</b></p>
            </div>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="name" :label="$t('login.name')">
                  <el-input
                    v-model="registrationForm.name"
                    name="name"
                    type="text"
                    auto-complete="on"
                    :placeholder="$t('login.name')"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="name" :label="$t('login.nik')">
                  <el-input
                    v-model="registrationForm.nik"
                    name="nik"
                    type="text"
                    auto-complete="on"
                    :placeholder="$t('login.nik')"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            <el-row>
              <el-form-item prop="address" :label="$t('login.address')">
                <el-input
                  v-model="registrationForm.address"
                  name="name"
                  type="textarea"
                  auto-complete="on"
                  :placeholder="$t('login.address')"
                />
              </el-form-item>
            </el-row>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="province" :label="$t('login.province')">
                  <el-select
                    v-model="registrationForm.province"
                    filterable
                    name="province"
                    :placeholder="$t('login.province')"
                    style="width: 100%"
                    @change="changeProvince"
                  >
                    <el-option
                      v-for="item in provinceOptions"
                      :key="item.value"
                      :label="item.label"
                      :value="item.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="district" :label="$t('login.district')">
                  <el-select
                    v-model="registrationForm.district"
                    filterable
                    name="district"
                    :placeholder="$t('login.district')"
                    style="width: 100%"
                  >
                    <el-option
                      v-for="item in districtOptions"
                      :key="item.value"
                      :label="item.label"
                      :value="item.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="email" :label="$t('login.email')">
                  <el-input
                    v-model="registrationForm.email"
                    name="name"
                    type="text"
                    auto-complete="on"
                    :placeholder="$t('login.email')"
                    suffix-icon="el-icon-message"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="password" :label="$t('login.password')">
                  <el-input
                    v-model="registrationForm.password"
                    name="password"
                    auto-complete="on"
                    placeholder="password"
                    :type="pwdType"
                  >
                    <span slot="append" class="show-pwd" @click="showPwd">
                      <svg-icon icon-class="eye" />
                    </span>
                  </el-input>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item prop="phone" :label="$t('login.phone')">
                  <el-input
                    v-model.number="registrationForm.phone"
                    name="phone"
                    auto-complete="on"
                    placeholder="Nomor Telepon"
                    suffix-icon="el-icon-phone"
                  >
                    <template slot="prepend">+62</template>
                  </el-input>
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="confirmPassword" :label="$t('login.confirmPassword')">
                  <el-input
                    v-model="registrationForm.confirmPassword"
                    name="confirmPassword"
                    auto-complete="on"
                    :placeholder="$t('login.confirmPassword')"
                    :type="pwdType"
                  >
                    <span slot="append" class="show-pwd" @click="showPwd">
                      <svg-icon icon-class="eye" />
                    </span>
                  </el-input>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row :gutter="24">
              <el-col :span="12">
                <el-form-item>
                  <el-checkbox v-model="registrationForm.isCertified" name="isCertified">Memiliki Sertifikat Kompetensi
                    AMDAL
                  </el-checkbox>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row v-if="registrationForm.isCertified" :gutter="24">
              <el-col :span="12">
                <el-form-item prop="reg_no" :label="$t('login.registrationNumber')">
                  <el-input
                    v-model="registrationForm.reg_no"
                    name="reg_no"
                    type="text"
                    auto-complete="on"
                    :placeholder="$t('login.registrationNumber')"
                    suffix-icon="el-icon-user-solid"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item prop="certificateUpload" :label="$t('login.certificateUpload')" class="form-btn-cert">
                  <el-upload
                    action="#"
                    :auto-upload="false"
                    :on-change="handleCertificateUpload"
                    :show-file-list="false"
                  >
                    <el-button
                      size="small"
                      type="warning"
                    >
                      Upload
                    </el-button>
                    <span>{{ certificateName }}</span>
                  </el-upload>
                </el-form-item>
              </el-col>
            </el-row>
            <el-row v-if="registrationForm.isCertified" :gutter="24">
              <el-col :span="12">
                <el-form-item prop="id_lsp" label="Nama LSP">
                  <el-select
                    v-model="registrationForm.id_lsp"
                    filterable
                    name="lsp"
                    style="width: 100%"
                  >
                    <el-option
                      v-for="item in lspOptions"
                      :key="item.value"
                      :label="item.label"
                      :value="item.value"
                    />
                  </el-select>
                </el-form-item>
              </el-col>
            </el-row>
          </el-form>
        </el-row>
        <el-row type="flex" justify="space-between">
          <el-col :span="10">
            <el-button type="text" style="background-color: transparent; color: blue;" @click="handleCancelReg">Sudah
              Memiliki Akun?
            </el-button>
          </el-col>
          <el-col :span="2">
            <el-button type="warning" :loading="loading" size="mini" @click="handleReg">Buat</el-button>
          </el-col>
        </el-row>
      </el-card>
    </div>
    <!-- <div v-else class="registration-container">
      <el-form ref="registrationForm" :model="registrationForm" :rules="registrationRules" class="registration-form" auto-complete="on" label-position="top">
        <h2>{{ $t('login.registrationForm') }}</h2>
        <div>
          <el-radio v-model="user_type" label="pemrakarsa" border>Pemrakarsa Badan Usaha</el-radio>
          <el-radio v-model="user_type" label="pemerintah" border>Pemrakarsa Pemerintah</el-radio>
          <el-radio v-model="user_type" label="penyusun" border>Penyusun</el-radio>
        </div>
        <el-form-item prop="user_type" :label="$t('login.userType')">
          <el-select
            v-model="registrationForm.user_type"
            name="user_type"
            :placeholder="$t('login.userType')"
            style="width: 100%"
          >
            <el-option
              v-for="item in userTypeoptions"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item prop="name" :label="$t('login.name')">
          <el-input v-model="registrationForm.name" name="name" type="text" auto-complete="on" :placeholder="$t('login.name')" suffix-icon="el-icon-user" />
        </el-form-item>
        <el-form-item prop="pic" :label="$t('login.pic')">
          <el-input v-model="registrationForm.pic" name="pic" type="text" auto-complete="on" :placeholder="$t('login.pic')" suffix-icon="el-icon-user" />
        </el-form-item>
        <el-form-item prop="address" :label="$t('login.address')">
          <el-input v-model="registrationForm.address" name="address" type="textarea" auto-complete="on" :placeholder="$t('login.address')" />
        </el-form-item>
        <el-form-item prop="email" :label="$t('login.email')">
          <el-input v-model="registrationForm.email" name="email" type="text" auto-complete="on" :placeholder="$t('login.email')" suffix-icon="el-icon-message" />
        </el-form-item>
        <el-form-item prop="phone" :label="$t('login.phone')">
          <el-input v-model="registrationForm.phone" name="phone" type="text" auto-complete="on" :placeholder="$t('login.phone')" suffix-icon="el-icon-phone" />
        </el-form-item>
        <el-row type="flex" class="row-bg" justify="space-between">
          <el-button type="text" style="background-color: transparent; color: blue;" @click="handleCancelReg">Sudah Memiliki Akun?</el-button>
          <el-button type="warning" :loading="loading" size="mini" @click="handleReg">Buat</el-button>
        </el-row>
      </el-form>
    </div> -->
  </div>
</template>

<script>
// import LangSelect from '@/components/LangSelect';
import { validEmail } from '@/utils/validate';
import { csrf } from '@/api/auth';
import Resource from '@/api/resource';
import ClassicUpload from '@/components/ClassicUpload';

const lspResource = new Resource('lsp');
const provinceResource = new Resource('provinces');
const districtResource = new Resource('districts');
const initiatorResource = new Resource('initiators');
const formulatorResource = new Resource('formulators');

const logo = require('@/assets/login/logo-amdal.png').default;
const logoOSS = require('@/assets/login/logo-oss.svg').default;

export default {
  name: 'Login',
  components: {
    ClassicUpload,
  },
  data() {
    const validateEmail = (rule, value, callback) => {
      if (!validEmail(value)) {
        callback(new Error('Silakan masukkan email yang benar'));
      } else {
        callback();
      }
    };
    const validateFileAgencyUpload = (rule, value, callback) => {
      if (!this.registrationForm.fileAgencyUpload) {
        callback(new Error('Anda Belum Upload Logo Anda'));
      } else {
        callback();
      }
    };
    const validatefileNibDocOss = (rule, value, callback) => {
      if (!this.registrationForm.fileNibDocOss) {
        callback(new Error('File Nib Document Oss Belum Diunggah'));
      }
      callback();
    };
    const validatePass = (rule, value, callback) => {
      if (value.length < 4) {
        callback(new Error('Password cannot be less than 4 digits'));
      } else {
        callback();
      }
    };
    const validateConfirmPassword = (rule, value, callback) => {
      if (!value) {
        callback(new Error('Silahkan masukkan konfirmasi yang sesuai'));
      } else if (value !== this.registrationForm.password) {
        callback(new Error('Silahkan masukkan konfirmasi yang sesuai'));
      } else {
        callback();
      }
    };
    const validateRegistrationNumber = (rule, value, callback) => {
      if (this.registrationForm.isCertified && (!value)) {
        callback(new Error('Silahkan masukkan nomor registrasi anda'));
      } else {
        callback();
      }
    };
    const validateCertificateUpload = (rule, value, callback) => {
      if (this.registrationForm.isCertified && (!this.registrationForm.file_sertifikat)) {
        callback(new Error('Anda Belum Upload Sertifikat Anda'));
      } else {
        callback();
      }
    };
    return {
      user_type: 'Pemerintah',
      loginForm: {},
      loginRules: {
        email: [{ required: true, trigger: 'blur', validator: validateEmail }],
        password: [{ required: true, trigger: 'blur', validator: validatePass }],
      },
      regPemrakarsaRules: {
        agency_type: [{ required: true, trigger: 'change', message: 'Jenis Perusahaan Dibutuhkan' }],
        province: [{ required: true, trigger: 'change', message: 'Provinsi Dibutuhkan' }],
        district: [{ required: true, trigger: 'change', message: 'Kabupaten Dibutuhkan' }],
        name: [{ required: true, trigger: 'blur', message: 'Nama Instansi Dibutuhkan' }],
        pic: [{ required: true, trigger: 'blur', message: 'Penanggung Jawab Dibutuhkan' }],
        picRole: [{ required: true, trigger: 'blur', message: 'Jabatan Dibutuhkan' }],
        address: [{ required: true, trigger: 'blur', message: 'Alamat Dibutuhkan' }],
        email: [{ required: true, trigger: 'blur', validator: validateEmail }],
        fileAgencyUpload: [{ required: true, trigger: 'change', validator: validateFileAgencyUpload }],
        fileNibDocOss: [{ required: true, trigger: 'change', validator: validatefileNibDocOss }],
        password: [{
          required: true,
          pattern: /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$/,
          message: 'minimal 8 karakter, harus mengandung minimal 1 huruf besar, 1 huruf kecil, dan 1 angka, Dapat berisi karakter khusus',
          trigger: 'blur',
        }],
        nib: [{ required: true, trigger: 'blur', message: 'NIB Dibutuhkan' }],
        // phone: [{ required: true, trigger: 'blur', message: 'Silahkan Masukan Nomor Telepon Yang Benar' }],
        phone: [{ required: true, message: 'Nomor Telepon wajib diisi' }, {
          type: 'number',
          message: 'Nomor Telepon berupa angka',
        }],
      },
      regPenyusunRules: {
        province: [{ required: true, trigger: 'change', message: 'Provinsi Dibutuhkan' }],
        district: [{ required: true, trigger: 'change', message: 'Kabupaten Dibutuhkan' }],
        name: [{ required: true, trigger: 'blur', message: 'Nama Penyusun Dibutuhkan' }],
        nik: [{ required: true, trigger: 'blur', message: 'NIK Dibutuhkan' }],
        // pic: [{ required: true, trigger: 'blur', message: 'Penanggung Jawab Dibutuhkan' }],
        // picRole: [{ required: true, trigger: 'blur', message: 'Jabatan Dibutuhkan' }],
        address: [{ required: true, trigger: 'blur', message: 'Alamat Dibutuhkan' }],
        email: [{ required: true, trigger: 'blur', validator: validateEmail }],
        // fileAgencyUpload: [{ required: true, trigger: 'change', validator: validateFileAgencyUpload }],
        password: [{
          required: true,
          pattern: /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$/,
          message: 'minimal 8 karakter, harus mengandung minimal 1 huruf besar, 1 huruf kecil, dan 1 angka, Dapat berisi karakter khusus',
          trigger: 'blur',
        }],
        confirmPassword: [{ required: true, trigger: 'blur', validator: validateConfirmPassword }],
        phone: [{ required: true, message: 'Nomor Telepon wajib diisi' }, {
          type: 'number',
          message: 'Nomor Telepon berupa angka',
        }],
        reg_no: [{ required: true, validator: validateRegistrationNumber }],
        certificateUpload: [{ required: true, validator: validateCertificateUpload }],
      },
      regPemerintahRules: {
        agency_type: [{ required: true, trigger: 'change', message: 'Jenis Instansi Dibutuhkan' }],
        province: [{ required: true, trigger: 'change', message: 'Provinsi Dibutuhkan' }],
        district: [{ required: true, trigger: 'change', message: 'Kabupaten Dibutuhkan' }],
        name: [{ required: true, trigger: 'blur', message: 'Nama Instansi Dibutuhkan' }],
        pic: [{ required: true, trigger: 'blur', message: 'Penanggung Jawab Dibutuhkan' }],
        picRole: [{ required: true, trigger: 'blur', message: 'Jabatan Dibutuhkan' }],
        address: [{ required: true, trigger: 'blur', message: 'Alamat Dibutuhkan' }],
        email: [{ required: true, trigger: 'blur', validator: validateEmail }],
        fileAgencyUpload: [{ required: true, trigger: 'change', validator: validateFileAgencyUpload }],
        password: [{
          required: true,
          pattern: /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$/,
          message: 'minimal 8 karakter, harus mengandung minimal 1 huruf besar, 1 huruf kecil, dan 1 angka, Dapat berisi karakter khusus',
          trigger: 'blur',
        }],
      },
      registrationRules: {
        user_type: [{ required: true, trigger: 'change', message: 'Pilih Jenis User' }],
        name: [{ required: true, trigger: 'blur' }],
        pic: [{ required: true, trigger: 'blur' }],
        address: [{ required: true, trigger: 'blur' }],
        email: [{ required: true, trigger: 'blur' }],
        phone: [{ required: true, trigger: 'blur' }],
      },
      loading: false,
      pwdType: 'password',
      redirect: undefined,
      otherQuery: {},
      logo: logo,
      logoOSS: logoOSS,
      registrationForm: {},
      provinceOptions: [],
      districtOptions: [],
      lspOptions: [],
      agencyTypeoptions: [
        {
          value: 'Kementerian',
          label: 'Kementerian',
        },
        {
          value: 'Lembaga',
          label: 'Lembaga',
        },
        {
          value: 'Pemerintah Provinsi',
          label: 'Pemerintah Provinsi',
        },
        {
          value: 'Pemerintah Kabupaten/Kota',
          label: 'Pemerintah Kabupaten/Kota',
        },
        {
          value: 'Lainnya',
          label: 'Lainnya',
        },
      ],
      companyTypeoptions: [
        {
          value: 'pt',
          label: 'Perseroan Terbatas (PT)',
        },
        {
          value: 'ptpr',
          label: 'Perseroan Terbatas (PT) Perorangan',
        },
        {
          value: 'cv',
          label: 'Persekutuan Komanditer (CV / Commanditaire Vennootschap)',
        },
        {
          value: 'fa',
          label: 'Persekutuan Firma (FA / Vennootschap Onder Firma)',
        },
        {
          value: 'pp',
          label: 'Persekutuan Perdata',
        },
        {
          value: 'kpi',
          label: 'Koperasi',
        },
        {
          value: 'pap',
          label: 'Persyarikatan atau Perkumpulan',
        },
        {
          value: 'ysn',
          label: 'Yayasan',
        },
        {
          value: 'perum',
          label: 'Perusahaan Umum (Perum)',
        },
        {
          value: 'perumda',
          label: 'Perusahaan Umum Daerah (Perumda)',
        },
        {
          value: 'blu',
          label: 'Badan Layanan Umum',
        },
        {
          value: 'bhl',
          label: 'Badan Hukum Lainnya',
        },
      ],
      form: 'login',
      fileAgencyUpload: null,
      fileAgencyUploadName: '',
      imageUrl: null,
      certificateName: null,
      fileNibDocOssName: 'No File Selected',
    };
  },
  watch: {
    $route: {
      handler: function(route) {
        const query = route.query;
        if (query) {
          this.redirect = query.redirect;
          this.otherQuery = this.getOtherQuery(query);
        }
      },
      immediate: true,
    },
  },
  mounted() {
    this.getProvinces();
    this.getLsp();
  },
  methods: {
    onChangeRadioUserType() {
      console.log('masuk');
      this.registrationForm = {};
      if (this.user_type === 'Pemerintah') {
        this.$refs['regPemerintah'].resetFields();
      } else if (this.user_type === 'Pemrakarsa') {
        this.$refs['regPemrakarsa'].resetFields();
      } else if (this.user_type === 'Penyusun') {
        this.$refs['regPenyusun'].resetFields();
      }
    },
    checkCvFile() {
      document.querySelector('#cvFile').click();
    },
    checkCvFileSure(e) {
      this.cvFileName = e.target.files[0].name;
      this.cvFileUpload = e.target.files[0];
    },
    handleAgencyLogoUpload(file, filelist) {
      this.imageUrl = URL.createObjectURL(file.raw);

      if (file.raw.size > 1048576) {
        this.showFileAlert('1');
        return;
      }

      // this.fileAgencyUpload = e.target.files[0];
      this.registrationForm.fileAgencyUpload = file.raw;
    },
    handleFilenibDocOssUpload(e) {
      // reset validation
      this.$refs['regPemrakarsa'].fields.find((f) => f.prop === 'fileNibDocOss').resetField();

      if (e.target.files[0].size > 10485760) {
        this.showFileAlert();
        return;
      }

      if (e.target.files[0].type !== 'application/pdf') {
        this.$alert('File yang diterima hanya .PDF', 'Format Salah');
        return;
      }

      this.fileNibDocOss = e.target.files[0];
      this.registrationForm.fileNibDocOss = e.target.files[0];
      this.fileNibDocOssName = e.target.files[0].name;
    },
    handleCertificateUpload(file, filelist) {
      if (file.raw.size > 5242880) {
        this.showFileAlert('5');
        return;
      }

      this.registrationForm.file_sertifikat = file.raw;
      this.certificateName = file.name;
    },
    showFileAlert(val) {
      this.$alert(`File Yang Diupload Melebihi ${val} MB`, {
        confirmButtonText: 'OK',
      });
    },
    beforeAvatarUpload(file) {
      const isJPG = file.type === 'image/jpeg';
      const isLt2M = file.size / 1024 / 1024 < 2;

      if (!isJPG) {
        this.$message.error('Avatar picture must be JPG format!');
      }
      if (!isLt2M) {
        this.$message.error('Avatar picture size can not exceed 2MB!');
      }
      return isJPG && isLt2M;
    },
    handleFileAgencyLogoUpload(e) {
      // reset validation
      // this.$refs['tapakProyek'].fields.find((f) => f.prop === 'fileKtr').resetField();

      if (e.target.files[0].size > 1048576) {
        this.showFileAlert('1');
        return;
      }

      // this.fileAgencyUpload = e.target.files[0];
      this.registrationForm.fileAgencyUpload = e.target.files[0];
      this.fileAgencyUploadName = e.target.files[0].name;
    },
    showPwd() {
      if (this.pwdType === 'password') {
        this.pwdType = '';
      } else {
        this.pwdType = 'password';
      }
    },
    handleLogin() {
      this.$refs.loginForm.validate(valid => {
        if (valid) {
          this.loading = true;
          csrf().then(() => {
            this.$store.dispatch('user/login', this.loginForm)
              .then((response) => {
                this.$router.push({ path: this.redirect || '/dashboard', query: this.otherQuery }, onAbort => {
                });
                this.loading = false;
                window.location.reload();
              })
              .catch(() => {
                this.$message({
                  message: 'Maaf Email atau Password yang anda masukkan kurang tepat',
                  type: 'error',
                  duration: 5 * 1000,
                });
                this.loading = false;
              });
          });
        } else {
          console.log('error submit!!');
          return false;
        }
      });
    },
    getOtherQuery(query) {
      return Object.keys(query).reduce((acc, cur) => {
        if (cur !== 'redirect') {
          acc[cur] = query[cur];
        }
        return acc;
      }, {});
    },
    handleOpenRegister() {
      this.form = 'register';
    },
    handleCancelReg() {
      this.form = 'login';
    },
    handleOpenResetPassword() {
      // this.form = 'resetpassword';
      this.$router.push({
        name: 'OssResetPassword',
      });
    },
    handleCancelReset() {
      this.form = 'login';
    },
    async changeProvince(value) {
      // change all district by province
      this.getDistricts(value);
    },
    async getDistricts(idProv) {
      const { data } = await districtResource.list({ idProv });
      this.districtOptions = data.map((i) => {
        return { value: i.id, label: i.name };
      });
    },
    async getProvinces() {
      const { data } = await provinceResource.list({});
      this.provinceOptions = data.map((i) => {
        return { value: i.id, label: i.name };
      });
    },
    async getLsp() {
      const { data } = await lspResource.list({
        options: 'true',
      });
      this.lspOptions = data.map((i) => {
        return { value: i.id, label: i.lsp_name };
      });
    },
    handleReg() {
      if (this.user_type === 'Pemerintah') {
        this.$refs.regPemerintah.validate(valid => {
          if (valid) {
            console.log(this.registrationForm);
            this.registrationForm.user_type = this.user_type;

            // make form data because we got file
            const formData = new FormData();

            // eslint-disable-next-line no-undef
            _.each(this.registrationForm, (value, key) => {
              formData.append(key, value);
            });

            this.loading = true;
            csrf().then(() => {
              initiatorResource
                .store(formData)
                .then((response) => {
                  if (response.error) {
                    this.$message({
                      message: response.error,
                      type: 'error',
                      duration: 5 * 1000,
                    });
                    this.loading = false;

                    return false;
                  }

                  this.$message({
                    message:
                      'User Dengan Email ' +
                      this.registrationForm.email +
                      ' Berhasil Dibuat',
                    type: 'success',
                    duration: 5 * 1000,
                  });
                  this.loading = false;
                  this.form = 'login';
                  this.registrationForm = {};
                })
                .catch((error) => {
                  console.log(error);
                  this.loading = false;
                });
            });
          } else {
            console.log('error submit!!');
            this.loading = false;
            return false;
          }
        });
      } else if (this.user_type === 'Pemrakarsa') {
        this.$refs.regPemrakarsa.validate(valid => {
          if (valid) {
            console.log(this.registrationForm);
            this.registrationForm.user_type = this.user_type;

            // make form data because we got file
            const formData = new FormData();

            // eslint-disable-next-line no-undef
            _.each(this.registrationForm, (value, key) => {
              formData.append(key, value);
            });

            this.loading = true;
            csrf().then(() => {
              initiatorResource
                .store(formData)
                .then((response) => {
                  if (response.error) {
                    this.$message({
                      message: response.error,
                      type: 'error',
                      duration: 5 * 1000,
                    });
                    this.loading = false;

                    return false;
                  }

                  this.$message({
                    message:
                      'User Dengan Email ' +
                      this.registrationForm.email +
                      ' Berhasil Dibuat',
                    type: 'success',
                    duration: 5 * 1000,
                  });
                  this.loading = false;
                  this.form = 'login';
                  this.registrationForm = {};
                })
                .catch((error) => {
                  this.loading = false;
                  console.log(error);
                });
            });
          } else {
            console.log('error submit!!');
            this.loading = false;
            return false;
          }
        });
      } else if (this.user_type === 'Penyusun') {
        this.$refs.regPenyusun.validate(valid => {
          if (valid) {
            this.registrationForm.user_type = this.user_type;
            this.registrationForm.expertise = 'penyusun';

            // make form data because we got file
            const formData = new FormData();
            formData.append('registration', 'true');

            // eslint-disable-next-line no-undef
            _.each(this.registrationForm, (value, key) => {
              formData.append(key, value);
            });

            this.loading = true;
            csrf().then(() => {
              formulatorResource
                .store(formData)
                .then((response) => {
                  if (response.error) {
                    if (response.error === 'reg_no') {
                      this.$alert('<h3>Data Penyusun dengan Nomor Registrasi tersebut tidak ditemukan</h3><p>Periksa kembali nomor registrasi yang Anda masukkan <b>atau</b> lanjutkan registrasi sebagai Tenaga Ahli</p>',
                        '',
                        { dangerouslyUseHTMLString: true, center: true });
                    } else {
                      this.$message({
                        message: response.error,
                        type: 'error',
                        duration: 5 * 1000,
                      });
                    }
                    this.loading = false;

                    return false;
                  }

                  if (response.message === 'success_certificate') {
                    this.$alert('<h3>File Sertifikat untuk No Registrasi tersebut sudah terdapat dalam Sistem Amdalnet, Periksa kembali file sertifikat anda pada menu profile.</h3>',
                      '',
                      { dangerouslyUseHTMLString: true, center: true, customClass: 'alert-certificate' });
                  } else {
                    this.$message({
                      message:
                        'User Dengan Email ' +
                        this.registrationForm.email +
                        ' Berhasil Dibuat',
                      type: 'success',
                      duration: 5 * 1000,
                    });
                  }

                  this.loading = false;
                  this.form = 'login';
                  this.registrationForm = {};
                })
                .catch((error) => {
                  this.loading = false;
                  console.log(error);
                });
            });
          } else {
            console.log('error submit!!');
            this.loading = false;
            return false;
          }
        });
      }
    },
  },
};
</script>

<style rel="stylesheet/scss" lang="scss">
$bg: #2d3a4b;
$light_gray: #5F6368;

/* reset element-ui css */
.login-container {
  .el-input {
    display: inline-block;
    height: 47px;
    width: 85%;

    input {
      background: transparent;
      border: 0;
      -webkit-appearance: none;
      border-radius: 0;
      padding: 12px 5px 12px 15px;
      color: $light_gray;
      height: 47px;

      &:-webkit-autofill {
        -webkit-box-shadow: 0 0 0 1000px #d8e4d8 inset !important;
        box-shadow: 0 0 0 1000px #d8e4d8 inset !important;
        -webkit-text-colorfill-color: rgb(8, 7, 7) !important;
      }
    }
  }

  .el-form-item {
    border: 1px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.1);
    border-radius: 5px;
    color: #454545;
  }
}

.el-upload-dragger {
  width: auto;
}

.form-btn-cert {
  display: inline-block;
}

.alert-certificate {
  width: 37%;
}

.alert-certificate button.el-button--primary {
  background-color: #ff4949;
  border-color: #ff4949;
}
</style>

<style rel="stylesheet/scss" lang="scss" scoped>

$bg: #2d3a4b;
$dark_gray: #889aa4;
$light_gray: rgb(7, 6, 6);
$bgColor: #143A16;
$brown: #B27C66;
$textColor: #eee;

.login {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background-color: $bgColor;
  background-image: url('../../assets/login/cover.png');
  transition: background-color .3s ease-in-out;
  overflow: auto;
  background-size: cover;
  background-position: center;

  .login-container {
    // background: $bg;
    // width: 1120px;
    // min-height: 590px;
    // display: grid;
    text-align: center;
    margin: auto;
    grid-template-columns: auto 480px;
    transition: all .3s ease-in-out;
    transform: scale(1);

    .logo {
      display: inline-block;
      margin-bottom: 20px;
      width: 250px;
    }

    // .login-image {
    //   display: flex;
    //   flex-direction: row;
    //   justify-content: flex-end;
    //   overflow: hidden;
    //   background-color: #303c4b;
    //   background-image: url('../../assets/login/cover.png');
    //   background-position: 50%;
    //   background-size: cover;
    //   opacity: 1;
    //   transition: opacity .3s ease-in-out,padding .2s ease-in-out;

    //   .photo-credit {
    //     justify-content: flex-end;
    //     align-self: flex-end;
    //     background-color: rgba(255,255,255,0.8);
    //     margin: 10px;
    //     padding: 5px 8px;

    //     h4, span { margin: 0; }
    //   }
    // }
    .el-button {
      background-color: #FFA84C;
    }

    .login-form {
      width: 50vw;
      min-width: 360px;
      max-width: 560px;
      margin: auto;
      background: #fff;
      padding: 60px 60px;
      position: relative;
      opacity: 1;
      transition: opacity .3s ease-in-out, padding .2s ease-in-out;
    }

    .tips {
      font-size: 14px;
      color: #fff;
      margin-bottom: 10px;

      span {
        &:first-of-type {
          margin-right: 16px;
        }
      }
    }

    .svg-container {
      padding: 6px 5px 6px 15px;
      color: $dark_gray;
      vertical-align: middle;
      width: 30px;
      display: inline-block;
    }

    .title-wrap {
      display: block;
      margin-bottom: 15px;

      .title {
        font-size: 24px;
        color: $textColor;
        margin: 0 auto 10px auto;
        text-align: left;
        font-weight: bold;
      }

      .sub-heading {
        font-size: 14px;
        color: $textColor;
        padding-bottom: 15px;
      }
    }

    .show-pwd {
      position: absolute;
      right: 10px;
      top: 7px;
      font-size: 16px;
      color: $dark_gray;
      cursor: pointer;
      user-select: none;
    }

    .set-language {
      color: $textColor;
      position: absolute;
      top: 40px;
      right: 35px;
    }
  }

  .registration-container {
    margin: auto;
    grid-template-columns: auto 480px;
    transition: all .3s ease-in-out;
    transform: scale(1);
    max-width: 882px;

    .registration-form {
      width: 50vw;
      min-width: 360px;
      max-width: 560px;
      margin: auto;
      background: #fff;
      padding: 50px 60px;
      position: relative;
      opacity: 1;
      transition: opacity .3s ease-in-out, padding .2s ease-in-out;
      border-radius: 20px;
    }
  }
}

.el-row {
  margin-bottom: 5px;

  &:last-child {
    margin-bottom: 0;
  }
}

.avatar-uploader .el-upload {
  border: 1px dashed #d9d9d9;
  border-radius: 6px;
  cursor: pointer;
  position: relative;
  overflow: hidden;
}

.avatar-uploader .el-upload:hover {
  border-color: #409EFF;
}

.avatar-uploader-icon {
  font-size: 28px;
  color: #8c939d;
  width: 178px;
  height: 178px;
  line-height: 178px;
  text-align: center;
}

.avatar {
  width: 178px;
  height: 178px;
  display: block;
}
</style>
