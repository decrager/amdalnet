import Layout from '@/layout';

const masterRoutes = {
  path: '/master-data',
  component: Layout,
  alwaysShow: true,
  meta: {
    title: 'masterData',
    icon: 'cube',
    permissions: [
      'view menu lpjp',
      'view menu rona awal',
      'view menu formulator',
      'view menu components',
      'view menu sop',
      'view menu expert',
      'view menu institution',
      'view menu tuk member list',
      'view menu materials and policies',
      'view menu permission list',
      'view menu env approve',
      'view menu ukl upl',
      'view menu sop management',
      'view menu video tutorial',
    ],
  },
  children: [
    {
      path: '/provinces',
      component: () => import('@/views/master-data/province'),
      name: 'province',
      hidden: true,
      meta: { title: 'provinsi', icon: 'el-icon-school' },
    },
    {
      path: 'lpjp',
      component: () => import('@/views/lpjp/index'),
      name: 'lpjp',
      meta: {
        title: 'LPJP',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu lpjp'],
      },
    },
    {
      path: 'lpjp/create',
      component: () => import('@/views/lpjp/Create'),
      name: 'createLpjp',
      hidden: true,
      meta: {
        title: 'Tambah LPJP',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage lpjp'],
      },
    },
    {
      path: 'lpjp/edit/:id',
      component: () => import('@/views/lpjp/Create'),
      name: 'editLpjp',
      hidden: true,
      meta: {
        title: 'Edit LPJP',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage lpjp'],
      },
    },
    {
      path: 'rona-awal',
      component: () => import('@/views/rona-awal/index'),
      name: 'rona-awal',
      meta: {
        title: 'Rona-Awal',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu rona awal'],
      },
    },
    {
      path: 'rona-awal/create',
      component: () => import('@/views/lpjp/Create'),
      name: 'createRonaAwal',
      hidden: true,
      meta: {
        title: 'Tambah Rona Lingkungan',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage rona awal'],
      },
    },
    {
      path: 'component',
      component: () => import('@/views/component/index'),
      name: 'component',
      meta: {
        title: 'Komponen',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu components'],
      },
    },
    {
      path: 'formulator',
      component: () => import('@/views/formulator/index'),
      name: 'formulator',
      meta: {
        title: 'Penyusun',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu formulator'],
      },
    },
    {
      path: 'formulator/create',
      component: () => import('@/views/formulator/Create'),
      name: 'createFormulator',
      hidden: true,
      meta: {
        title: 'Tambah Penyusun',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage formulator'],
      },
    },
    {
      path: 'formulator/edit/:id',
      component: () => import('@/views/formulator/Create'),
      name: 'editFormulator',
      hidden: true,
      meta: {
        title: 'Edit Penyusun',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage formulator'],
      },
    },
    {
      path: 'formulator/sertifikasi/:id',
      component: () => import('@/views/formulator/Certification'),
      name: 'certificateFormulator',
      hidden: true,
      meta: {
        title: 'Sertifikasi Penyusun',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage formulator'],
      },
    },
    {
      path: 'bank-ahli',
      component: () => import('@/views/expert-bank/index'),
      name: 'expertBank',
      meta: {
        title: 'expertBank',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu expert'],
      },
    },
    {
      path: 'bank-ahli/create',
      component: () => import('@/views/expert-bank/Create'),
      name: 'createExpertBank',
      hidden: true,
      meta: {
        title: 'Tambah Bank Ahli',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage expert'],
      },
    },
    {
      path: 'bank-ahli/edit/:id',
      component: () => import('@/views/expert-bank/Create'),
      name: 'editExpertBank',
      hidden: true,
      meta: {
        title: 'Edit Bank Ahli',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage expert'],
      },
    },
    {
      path: 'sop',
      component: () => import('@/views/master-sop/index'),
      name: 'sop',
      meta: {
        title: 'SOP',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu sop'],
      },
    },
    {
      path: 'sop/create',
      component: () => import('@/views/master-sop/Create'),
      name: 'createSop',
      hidden: true,
      meta: {
        title: 'Tambah SOP',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage sop'],
      },
    },
    {
      path: 'instansi-pemerintah',
      component: () => import('@/views/government-institution/index'),
      name: 'governmentInstitution',
      meta: {
        title: 'Instansi Pemerintah',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu institution'],
      },
    },
    {
      path: 'anggota-tuk',
      component: () => import('@/views/employee/index'),
      name: 'employeeTuk',
      meta: {
        title: 'Daftar Anggota TUK',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu tuk member list'],
      },
    },
    {
      path: 'anggota-tuk/create',
      component: () => import('@/views/employee/Create'),
      name: 'createEmployeeTuk',
      hidden: true,
      meta: {
        title: 'Tambah Anggota TUK',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage tuk member list'],
      },
    },
    {
      path: 'anggota-tuk/edit/:id',
      component: () => import('@/views/employee/Create'),
      name: 'editEmployeeTuk',
      hidden: true,
      meta: {
        title: 'Edit Anggota TUK',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage tuk member list'],
      },
    },
    {
      path: 'materi-kebijakan/materi/create',
      component: () => import('@/views/materi/Create'),
      name: 'addMateri',
      hidden: true,
      meta: {
        title: 'Tambah Materi',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage materials and policies'],
      },
    },
    {
      path: 'materi-kebijakan/materi/edit',
      component: () => import('@/views/materi/Edit'),
      name: 'editMateri',
      hidden: true,
      meta: {
        title: 'Edit Materi',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage materials and policies'],
      },
    },
    {
      path: 'materi-kebijakan/peraturan/create',
      component: () => import('@/views/peraturan/Create'),
      name: 'addPeraturan',
      hidden: true,
      meta: {
        title: 'Tambah Peraturan',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage materials and policies'],
      },
    },
    {
      path: 'materi-kebijakan/peraturan/create',
      component: () => import('@/views/peraturan/Edit'),
      name: 'DaftarIzin',
      hidden: true,
      meta: {
        title: 'Ubah Peraturan',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage materials and policies'],
      },
    },
    {
      path: 'materi-kebijakan/kebijakan/create',
      component: () => import('@/views/kebijakan/Create'),
      name: 'addKebijakan',
      hidden: true,
      meta: {
        title: 'Tambah Kebijakan',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage materials and policies'],
      },
    },
    {
      path: 'materi-kebijakan/kebijakan/edit',
      component: () => import('@/views/kebijakan/Edit'),
      name: 'editKebijakan',
      hidden: true,
      meta: {
        title: 'Edit Kebijakan',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage materials and policies'],
      },
    },
    {
      path: 'master-data/materi-kebijakan',
      component: () => import('@/views/materi-kebijakan/index'),
      name: 'MateriDanKebijakan',
      meta: {
        title: 'Materi dan Kebijakan',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu materials and policies'],
      },
    },
    {
      path: 'daftar-izin',
      component: () => import('@/views/master-izin/index'),
      name: 'DaftarIzins',
      meta: {
        title: 'Daftar Izin',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu permission list'],
      },
    },
    {
      path: 'daftar-izin/create',
      component: () => import('@/views/master-izin/Create'),
      name: 'AddIzin',
      hidden: true,
      meta: {
        title: 'Tambah Izin Baru',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage permission list'],
      },
    },
    {
      path: 'daftar-izin/edit',
      component: () => import('@/views/master-izin/Edit'),
      name: 'EditIzin',
      hidden: true,
      meta: {
        title: 'Ubah Izin',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage permission list'],
      },
    },
    {
      path: 'template-persetujuan',
      component: () => import('@/views/template-persetujuan/index'),
      name: 'DaftarPersetujuan',
      meta: {
        title: 'Persetujuan Lingkungan',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu env approve'],
      },
    },
    {
      path: 'template-persetujuan/create',
      component: () => import('@/views/template-persetujuan/Create'),
      name: 'AddDaftarPersetujuan',
      hidden: true,
      meta: {
        title: 'Tambah Persetujuan Lingkungan',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage env approve'],
      },
    },
    {
      path: 'template-persetujuan/edit',
      component: () => import('@/views/template-persetujuan/Edit'),
      name: 'EditDaftarPersetujuan',
      hidden: true,
      meta: {
        title: 'Edit Persetujuan Lingkungan',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage env approve'],
      },
    },
    {
      path: 'template-ukl-upl',
      component: () => import('@/views/template-ukl-menengah/index'),
      name: 'UklRendah',
      meta: {
        title: 'UKL-UPL',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu ukl upl'],
      },
    },
    {
      path: 'template-ukl-upl/create',
      component: () => import('@/views/template-ukl-menengah/Create'),
      name: 'AddUklMenengah',
      hidden: true,
      meta: {
        title: 'Tambah UKL-UPL',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage ukl upl'],
      },
    },
    {
      path: 'template-ukl-upl/edit',
      component: () => import('@/views/template-ukl-menengah/Edit'),
      name: 'EditUklMenengah',
      hidden: true,
      meta: {
        title: 'Edit UKL-UPL',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage ukl upl'],
      },
    },
    {
      path: 'sop-pengelolaan-dan-pemantauan-lingkungan',
      component: () => import('@/views/sop-pengelolaan/index'),
      name: 'SopPengelolaan',
      meta: {
        title: 'SOP Pengelolaan',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu sop management'],
      },
    },
    {
      path: 'sop-pengelolaan-dan-pemantauan-lingkungan/create',
      component: () => import('@/views/sop-pengelolaan/Create'),
      name: 'CreateSopPengelolaan',
      hidden: true,
      meta: {
        title: 'SOP Pengelolaan',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage sop management'],
      },
    },
    {
      path: 'sop-pengelolaan-dan-pemantauan-lingkungan/edit',
      component: () => import('@/views/sop-pengelolaan/Edit'),
      name: 'EditSopPengelolaan',
      hidden: true,
      meta: {
        title: 'SOP Pengelolaan',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage sop management'],
      },
    },
    {
      path: 'video-tutorial',
      component: () => import('@/views/video-tutorial/index'),
      name: 'VideoTutorial',
      meta: {
        title: 'Video Tutorial',
        icon: 'documentation',
        noCache: true,
        permissions: ['view menu video tutorial'],
      },
    },
    {
      path: 'video-tutorial/create',
      component: () => import('@/views/video-tutorial/Create'),
      name: 'CreateVideoTutorial',
      hidden: true,
      meta: {
        title: 'Create Video Tutorial',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage video tutorial'],
      },
    },
    {
      path: 'video-tutorial/edit',
      component: () => import('@/views/video-tutorial/Edit'),
      name: 'EditVideoTutorial',
      hidden: true,
      meta: {
        title: 'Edit Video Tutorial',
        icon: 'documentation',
        noCache: true,
        permissions: ['manage video tutorial'],
      },
    },
  ],
};

export default masterRoutes;
