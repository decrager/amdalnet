<template>
  <div class="navbar">
    <hamburger id="hamburger-container" :is-active="sidebar.opened" class="hamburger-container" @toggleClick="toggleSideBar" />

    <breadcrumb id="breadcrumb-container" class="breadcrumb-container" />

    <div class="right-menu">
      <template v-if="device!=='mobile'">
        <!-- <lang-select class="right-menu-item hover-effect" /> -->
        <el-dropdown class="right-menu-item hover-effect" @command="handleNotif">
          <el-badge is-dot :hidden="sumNotifUnread > 0">
            <el-button icon="el-icon-bell" type="text" style="cursor: pointer; font-size: 18px; vertical-align: middle; color: white" @click="markAsRead" />
            <!-- <i class="el-icon-bell" style="cursor: pointer; font-size: 18px; vertical-align: middle;" /> -->
          </el-badge>
          <el-dropdown-menu slot="dropdown" class="notif-dropdown">
            <div v-loading="loadingNotif">
              <el-dropdown-item v-for="notif in notifications.notifications" :key="notif.id" :command="notif">
                <div>
                  {{ notif.data.message }}
                </div>
              </el-dropdown-item>
            </div>
          </el-dropdown-menu>
        </el-dropdown>
        <!-- <search id="header-search" class="right-menu-item" /> -->

        <!-- <screenfull id="screenfull" class="right-menu-item hover-effect" />

        <el-tooltip :content="$t('navbar.size')" effect="dark" placement="bottom">
          <size-select id="size-select" class="right-menu-item hover-effect" />
        </el-tooltip> -->
      </template>
      <el-button type="text" style="cursor: default; font-size: 18px; vertical-align: 15px; margin-right: 10px; color: white;">{{ getRole }}</el-button>
      <el-dropdown class="avatar-container right-menu-item hover-effect" trigger="click">
        <div class="avatar-wrapper">
          <img :src="avatar || 'no-avatar.png'" class="user-avatar" @error="$event.target.src='no-avatar.png'">
          <i class="el-icon-caret-bottom" />
        </div>
        <el-dropdown-menu slot="dropdown">
          <router-link to="/dashboard">
            <el-dropdown-item>
              {{ $t('navbar.dashboard') }}
            </el-dropdown-item>
          </router-link>
          <router-link v-show="userId !== null" :to="`/profile/edit`">
            <el-dropdown-item>
              {{ $t('navbar.profile') }}
            </el-dropdown-item>
          </router-link>
          <el-dropdown-item divided>
            <span style="display:block;" @click="logout">{{ $t('navbar.logOut') }}</span>
          </el-dropdown-item>
        </el-dropdown-menu>
      </el-dropdown>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
// import Echo from 'laravel-echo';
import Resource from '@/api/resource';
const markAsReadResource = new Resource('mark-all-read');
import Breadcrumb from '@/components/Breadcrumb';
import Hamburger from '@/components/Hamburger';
// import Screenfull from '@/components/Screenfull';
// import SizeSelect from '@/components/SizeSelect';
// import LangSelect from '@/components/LangSelect';
// import Search from '@/components/HeaderSearch';

export default {
  components: {
    Breadcrumb,
    Hamburger,
    // Screenfull,
    // SizeSelect,
  },
  data() {
    return {
      limit: 5,
      loadingNotif: false,
      isPemerintah: false,
    };
  },
  computed: {
    ...mapGetters([
      'sidebar',
      'name',
      'avatar',
      'device',
      'userId',
      'notifications',
    ]),
    getRole() {
      const roles = this.$store.getters.roles.map(value => {
        if (value === 'institution' || value === 'initiator' || value === 'pustanling' || value === 'examiner-secretary' || value === 'examiner-substance' || value === 'examiner-administration') {
          if (value === 'institution'){
            value = 'LSP';
          } else if (value === 'pustanling'){
            value = 'Pusfaster';
          } else if (value === 'examiner-secretary'){
            value = 'Kepala Sekretariat';
          } else if (value === 'examiner-substance'){
            value = 'Validator';
          } else if (value === 'examiner-administration'){
            value = 'Validator';
          } else if (value === 'initiator' && this.$store.getters.isPemerintah === true) {
            value = 'Pemrakarsa Pemerintah';
          } else if (value === 'initiator' && this.$store.getters.isPemerintah === false) {
            value = 'Pemrakarsa Pelaku Usaha';
          }
          const translatedRole = this.$t(value);
          return this.$options.filters.uppercaseFirst(translatedRole);
        } else if (value !== 'institution' || value !== 'pustanling' || value !== 'examiner-secretary' || value !== 'examiner-substance' || value !== 'examiner-administration') {
          const translatedRole = this.$t('roles.title.' + value);
          return this.$options.filters.uppercaseFirst(translatedRole);
        }
      });
      return roles.join(' | ');
    },
    sumNotifUnread(){
      return !this.notifications.unread;
    },
  },
  mounted(){
    // console.log(this.$store.getters.token);
    // window.Echo.join(`chat`)
    //   .here((users) => {
    //     //
    //   })
    //   .joining((user) => {
    //     console.log(user.name);
    //   })
    //   .leaving((user) => {
    //     console.log(user.name);
    //   })
    //   .error((error) => {
    //     console.error(error);
    //   });
    if (window.Echo) {
      window.Echo.channel('notif')
        .listen('NotificationEvent', e => {
          this.limit = 5;
          this.$store.dispatch('user/getNotifications', this.limit);
        });
    }

    // Detect when scrolled to bottom.
    const listElm = document.querySelector(
      'ul.el-dropdown-menu.notif-dropdown'
    );
    listElm.addEventListener('scroll', (e) => {
      if (listElm.scrollTop + listElm.clientHeight >= listElm.scrollHeight) {
        const difference = this.notifications.total - this.limit;
        if (difference > 0 || (difference >= -5 && difference < 0)) {
          this.loadNotifications();
        }
      }
    });
  },
  methods: {
    handleNotif(notif){
      if (notif.data.path){
        this.$router.push({
          path: notif.data.path,
        });
      }
    },
    markAsRead(){
      console.log('test');
      markAsReadResource.get(this.userId);
    },
    toggleSideBar() {
      this.$store.dispatch('app/toggleSideBar');
    },
    async logout() {
      await this.$store.dispatch('user/logout');
      this.$router.push('/');
    },
    async loadNotifications() {
      this.loadingNotif = true;
      this.limit += 5;
      await this.$store.dispatch('user/getNotifications', this.limit);
      this.loadingNotif = false;
    },
  },
};
</script>
<style>
.el-badge__content.is-fixed.is-dot {
  right: 5px;
  top: 10px;
}
.notif-dropdown {
    width: 20em;
    top: 0px !important;
    height: 20em !important;
    overflow-y: auto;
    overflow-x: clip !important;
}
.notif-dropdown li {
  font-size: 0.9em !important;
  line-height: 1.8em !important;
}
.notif-dropdown li div {
  border-bottom: 1px solid #c9c6c6;
  padding-bottom: 5px;
}
.notif-dropdown li:not(:first-child) div {
  padding-top: 10px;
}
</style>
<style lang="scss" scoped>
.navbar {
  height: 50px;
  overflow: hidden;
  position: relative;
  background: #143b17; // #099C4B;
  //margin: 5px;
  //border-radius: 8px;
  box-shadow: 0 4px 4px rgba(0,21,41,.3);
  margin: 0 0.5em 0.3em;
  border-radius: 0.3em;

  .hamburger-container {
    line-height: 46px;
    height: 100%;
    float: left;
    cursor: pointer;
    transition: background .3s;
    -webkit-tap-highlight-color:transparent;

    &:hover {
      background: rgba(0, 0, 0, .025)
    }
  }

  .breadcrumb-container {
    float: left;
  }

  .errLog-container {
    display: inline-block;
    vertical-align: top;
  }

  .right-menu {
    float: right;
    height: 100%;
    line-height: 50px;

    &:focus {
      outline: none;
    }

    .right-menu-item {
      display: inline-block;
      padding: 0 8px;
      height: 100%;
      font-size: 18px;
      /* color: #5a5e66; */
      color: white;
      vertical-align: text-bottom;

      &.hover-effect {
        cursor: pointer;
        transition: background .3s;

        &:hover {
          background: rgba(0, 0, 0, .025)
        }
      }
    }

    .avatar-container {
      margin-right: 30px;

      .avatar-wrapper {
        margin-top: 5px;
        position: relative;

        .user-avatar {
          cursor: pointer;
          width: 40px;
          height: 40px;
          border-radius: 4px;
        }

        .el-icon-caret-bottom {
          cursor: pointer;
          position: absolute;
          right: -20px;
          top: 25px;
          font-size: 12px;
        }
      }
    }
  }
}
</style>
