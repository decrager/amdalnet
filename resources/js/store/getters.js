const getters = {
  sidebar: state => state.app.sidebar,
  language: state => state.app.language,
  size: state => state.app.size,
  device: state => state.app.device,
  visitedViews: state => state.tagsView.visitedViews,
  cachedViews: state => state.tagsView.cachedViews,
  userId: state => state.user.id,
  token: state => state.user.token,
  avatar: state => state.user.avatar,
  name: state => state.user.name,
  user: state => state.user.user,
  introduction: state => state.user.introduction,
  roles: state => state.user.roles,
  permissions: state => state.user.permissions,
  permissionRoutes: state => state.permission.routes,
  addRoutes: state => state.permission.addRoutes,
  amdals: state => state.announcement.amdals,
  uklupls: state => state.announcement.uklupls,
  alls: state => state.announcement.alls,
  countAmdal: state => state.announcement.countAmdal,
  countUklupl: state => state.announcement.countUklupl,
  countAll: state => state.announcement.countAll,
  loadingStatus: state => state.announcement.loadingStatus,
  projectOptions: state => state.project.projectOptions,
  membershipOptions: state => state.project.membershipOptions,
  projectFieldOptions: state => state.project.projectFieldOptions,
  sectorOptions: state => state.project.sectorOptions,
  provinceOptions: state => state.project.provinceOptions,
  cityOptions: state => state.project.cityOptions,
  unitOptions: state => state.project.unitOptions,
  businessTypeOptions: state => state.project.businessTypeOptions,
  formulators: state => state.project.formulators,
  teamType: state => state.project.teamType,
  kblis: state => state.project.listKbli,
  lpjps: state => state.project.listLpjp,
  step: state => state.workflow.step,
  isPemerintah: state => state.initiator.isPemerintah,
  jumlahAnggota: state => state.penyusun.jumlahAnggota,
};
export default getters;
