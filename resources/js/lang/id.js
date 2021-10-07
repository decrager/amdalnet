export default {
  route: {
    dashboard: 'Dasbor',
    introduction: 'Pengantar',
    documentation: 'Dokumentasi',
    guide: 'Panduan',
    permission: 'Perizinan',
    pagePermission: 'Perizinan Halaman',
    rolePermission: 'Perizinan Peran',
    directivePermission: 'Arahan',
    icons: 'Ikon',
    components: 'Komponen',
    componentIndex: 'Pengenalan',
    tinymce: 'Tinymce',
    markdown: 'Markdown',
    jsonEditor: 'Editor JSON',
    dndList: 'Daftar Dnd',
    splitPane: 'SplitPane',
    avatarUpload: 'Unggah Avatar',
    dropzone: 'Zona Drop',
    sticky: 'Sticky',
    countTo: 'CountTo',
    componentMixin: 'Mixin',
    backToTop: 'Kembali ke atas',
    dragDialog: 'Dialog Tarik',
    dragSelect: 'Pilih Tarik',
    dragKanban: 'Kanban Tarik',
    charts: 'Grafik',
    keyboardChart: 'Grafik Keyboard',
    lineChart: 'Grafik Garis',
    mixChart: 'Grafik Campur',
    example: 'Contoh',
    nested: 'Rute Bersarang',
    menu1: 'Menu 1',
    'menu1-1': 'Menu 1-1',
    'menu1-2': 'Menu 1-2',
    'menu1-2-1': 'Menu 1-2-1',
    'menu1-2-2': 'Menu 1-2-2',
    'menu1-3': 'Menu 1-3',
    menu2: 'Menu 2',
    table: 'Tabel',
    dynamicTable: 'Tabel Dinamis',
    dragTable: 'Tabel Tarik',
    inlineEditTable: 'Sunting Ditempat',
    complexTable: 'Tabel Kompleks',
    treeTable: 'Tabel Pohon',
    customTreeTable: 'Kustom Tabel Pohon',
    tab: 'Tab',
    form: 'Formulir',
    createArticle: 'Buat Artikel',
    editArticle: 'Sunting Artikel',
    articleList: 'Artikel',
    errorPages: 'Halaman Error',
    page401: '401',
    page404: '404',
    errorLog: 'Log Error',
    excel: 'Excel',
    exportExcel: 'Export Excel',
    selectExcel: 'Export Terpilih',
    mergeHeader: 'Gabung Header',
    uploadExcel: 'Unggah Excel',
    zip: 'Zip',
    pdf: 'PDF',
    exportZip: 'Export Zip',
    theme: 'Tema',
    clipboardDemo: 'Clipboard',
    i18n: 'I18n',
    externalLink: 'Tautan Eksternal',
    elementUi: 'Element UI',
    administrator: 'Administrator',
    users: 'Pengguna',
    userProfile: 'Profile Pengguna',
  },
  navbar: {
    logOut: 'Keluar',
    dashboard: 'Dasbor',
    github: 'Github',
    theme: 'Tema',
    size: 'Ukuran Global',
    profile: 'Profile',
  },
  login: {
    title: 'Masuk ke Dasbor Anda',
    logIn: 'Masuk',
    username: 'Nama pengguna',
    password: 'Password',
    any: 'apa saja',
    thirdparty: 'Terhubung dengan',
    thirdpartyTips: 'Tidak bisa disimulasikan di lokal, silahkan gabungkan dengan simulasi yang Anda miliki!',
    email: 'Surel',
  },
  documentation: {
    documentation: 'Dokumentasi',
    laravel: 'Laravel',
    github: 'Github Repository',
  },
  permission: {
    addRole: 'Peran Baru',
    editPermission: 'Sunting Perizinan',
    roles: 'Peran Anda',
    switchRoles: 'Mengalihkan peran',
    tips: 'Dalam beberapa kasus, tidak cocok menggunakan v-role/v-permission, seperti pada komponen Tab atau el-table-column dan asyncrhonous dom rendering, dalam hal ini dapat dicapai hanya dengan setting v-if dengan checkRole dan/atau checkPermission.',
    delete: 'Hapus',
    confirm: 'Konfirmasi',
    cancel: 'Batal',
  },
  guide: {
    description: 'The guide page is useful for some people who entered the project for the first time. You can briefly introduce the features of the project. Demo is based on ',
    button: 'Tampilkan Panduan',
  },
  components: {
    documentation: 'Dokumentasi',
    tinymceTips: 'Rich text editor is a core part of management system, but at the same time is a place with lots of problems. In the process of selecting rich texts, I also walked a lot of detours. The common rich text editors in the market are basically used, and the finally chose Tinymce. See documentation for more detailed rich text editor comparisons and introductions.',
    dropzoneTips: 'Because my business has special needs, and has to upload images to qiniu, so instead of a third party, I chose encapsulate it by myself. It is very simple, you can see the detail code in @/components/Dropzone.',
    stickyTips: 'when the page is scrolled to the preset position will be sticky on the top.',
    backToTopTips1: 'When the page is scrolled to the specified position, the Back to Top button appears in the lower right corner',
    backToTopTips2: 'You can customize the style of the button, show / hide, height of appearance, height of the return. If you need a text prompt, you can use element-ui el-tooltip elements externally',
    imageUploadTips: 'Since I was using only the vue@1 version, and it is not compatible with mockjs at the moment, I modified it myself, and if you are going to use it, it is better to use official version.',
  },
  table: {
    description: 'Deskripsi',
    dynamicTips1: 'Fixed header, sorted by header order',
    dynamicTips2: 'Not fixed header, sorted by click order',
    dragTips1: 'The default order',
    dragTips2: 'The after dragging order',
    name: 'Nama',
    title: 'Judul',
    importance: 'Imp',
    type: 'Type',
    remark: 'Catatan',
    search: 'Cari',
    add: 'Tambah',
    export: 'Export',
    reviewer: 'reviewer',
    id: 'ID',
    date: 'Tanggal',
    author: 'Pengarang',
    readings: 'Bacaan',
    status: 'Status',
    actions: 'Aksi',
    edit: 'Edit',
    publish: 'Terbit',
    draft: 'Draf',
    delete: 'Hapus',
    cancel: 'Batal',
    confirm: 'Konfirmasi',
    keyword: 'Katakunci',
    role: 'Peran',
  },
  errorLog: {
    tips: 'Please click the bug icon in the upper right corner',
    description: 'Now the management system are basically the form of the spa, it enhances the user experience, but it also increases the possibility of page problems, a small negligence may lead to the entire page deadlock. Fortunately Vue provides a way to catch handling exceptions, where you can handle errors or report exceptions.',
    documentation: 'Document introduction',
  },
  excel: {
    export: 'Export',
    selectedExport: 'Export Selected Items',
    placeholder: 'Please enter the file name(default excel-list)',
  },
  zip: {
    export: 'Export',
    placeholder: 'Please enter the file name(default file)',
  },
  pdf: {
    tips: 'Here we use window.print() to implement the feature of downloading pdf.',
  },
  theme: {
    change: 'Change Theme',
    documentation: 'Theme documentation',
    tips: 'Tips: It is different from the theme-pick on the navbar is two different skinning methods, each with different application scenarios. Refer to the documentation for details.',
  },
  tagsView: {
    refresh: 'Refresh',
    close: 'Tutup',
    closeOthers: 'Tutup Lainnya',
    closeAll: 'Tutup Semua',
  },
  settings: {
    title: 'Page style setting',
    theme: 'Theme Color',
    tagsView: 'Open Tags-View',
    fixedHeader: 'Fixed Header',
    sidebarLogo: 'Sidebar Logo',
  },
  user: {
    'role': 'Peran',
    'password': 'Password',
    'confirmPassword': 'Konfirmasi password',
    'name': 'Nama',
    'email': 'Surel',
  },
  roles: {
    description: {
      admin: 'Super Administrator. Have access and full permission to all pages.',
      manager: 'Manager. Have access and permission to most of pages except permission page.',
      editor: 'Editor. Have access to most of pages, full permission with articles and related resources.',
      user: 'Normal user. Have access to some pages',
      visitor: 'Visitor. Have access to static pages, should not have any writable permission',
    },
  },
};
