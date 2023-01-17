<template>
  <el-form v-loading="petaLoading" label-position="top" label-width="100px">
    <div style="margin-bottom: 10px;">
      <a href="/sample_map/template_shp_titik_kelola_pantau_amdalnet.zip" class="download__sample" target="_blank" rel="noopener noreferrer"><i class="ri-road-map-line" /> Download Contoh SHP Titik Kelola/ Pantau</a>
      <a href="/JUKNIS_DATA_SPASIAL_AMDALNET_TITIK_KELOLA_PANTAU.pdf" class="download__juknis" title="Download Juknis Peta" target="_blank" rel="noopener noreferrer"><i class="ri-file-line" /> Download Juknis Peta Titik Kelola/ Pantau</a>
    </div>
    <div style="margin-bottom: 10px;">
      <a href="/sample_map/template_shp_area_kelola_pantau_amdalnet.zip" class="download__sample" target="_blank" rel="noopener noreferrer"><i class="ri-road-map-line" /> Download Contoh SHP Lokasi Kelola/ Pantau</a>
      <a href="/JUKNIS_DATA_SPASIAL_AMDALNET_AREA_KELOLA_PANTAU.pdf" class="download__juknis" title="Download Juknis Peta" target="_blank" rel="noopener noreferrer"><i class="ri-file-line" /> Download Juknis Peta Lokasi Kelola/ Pantau</a>
    </div>
    <!-- ekologis -->
    <el-form-item label="Peta Titik Pengelolaan" :required="required">
      <el-col :span="11" style="margin-right:1em;">

        <fieldset style="border:1px solid #e0e0e0; border-radius: 0.3em; width:100%; padding: .5em;">
          <legend style="margin:0 2em;">File-File SHP yang sudah di-zip
            <div v-if="url_peta_pengelolaan_shp != null" class="current">tersimpan: <a style="color: green" :href="url_peta_pengelolaan_shp"><strong>{{ peta_pengelolaan_shp_name }}<i class="el-icon-circle-check" /></strong></a>
              <!-- &nbsp;<i class="el-icon-delete"></i>-->
            </div>
          </legend>
          <form v-if="isFormulator" @submit.prevent="handleSubmit">
            <input ref="refPengelolaanSHP" type="file" class="form-control-file" :disabled="isReadOnly" @change="!isReadOnly && onChangeFiles(1)">
            <!-- <button type="submit">Unggah</button> -->
          </form>
        </fieldset>

      </el-col>
      <el-col :span="11" style="margin-right:1em;">
        <fieldset style="border:1px solid #e0e0e0; border-radius: 0.3em; width:100%; padding: .5em;">
          <legend style="margin:0 2em;">Versi PDF
            <div v-if="url_peta_pengelolaan_pdf != null" class="current">tersimpan: <a style="color: green" :href="url_peta_pengelolaan_pdf" target="_blank"><strong>{{ peta_pengelolaan_pdf_name }}<i class="el-icon-circle-check" /></strong></a></div>
          </legend>
          <form v-if="isFormulator" @submit.prevent="handleSubmit">
            <input ref="refPengelolaanPDF" type="file" class="form-control-file" accept="application/pdf" :disabled="isReadOnly" @change="!isReadOnly && onChangeFiles(2)">
            <!-- <button type="submit">Unggah</button> -->
          </form>
        </fieldset>
      </el-col>
    </el-form-item>

    <el-form-item label="Peta Lokasi Pengelolaan" :required="required">
      <el-col :span="11" style="margin-right:1em;">

        <fieldset style="border:1px solid #e0e0e0; border-radius: 0.3em; width:100%; padding: .5em;">
          <legend style="margin:0 2em;">File-File SHP yang sudah di-zip
            <div v-if="url_peta_area_pengelolaan_shp != null" class="current">tersimpan: <a style="color: green" :href="url_peta_area_pengelolaan_shp"><strong>{{ peta_area_pengelolaan_shp_name }}<i class="el-icon-circle-check" /></strong></a>
              <!-- &nbsp;<i class="el-icon-delete"></i>-->
            </div>
          </legend>
          <form v-if="isFormulator" @submit.prevent="handleSubmit">
            <input ref="refAreaPengelolaanSHP" type="file" class="form-control-file" :disabled="isReadOnly" @change="!isReadOnly && onChangeFiles(5)">
            <!-- <button type="submit">Unggah</button> -->
          </form>
        </fieldset>

      </el-col>
      <!-- <el-col :span="11" style="margin-right:1em;">
        <fieldset style="border:1px solid #e0e0e0; border-radius: 0.3em; width:100%; padding: .5em;">
          <legend style="margin:0 2em;">Versi PDF
            <div v-if="url_peta_area_pengelolaan_pdf != null" class="current">tersimpan: <a style="color: green" :href="url_peta_area_pengelolaan_pdf" target="_blank"><strong>{{ peta_area_pengelolaan_pdf_name }}<i class="el-icon-circle-check" /></strong></a></div>
          </legend>
          <form v-if="isFormulator" @submit.prevent="handleSubmit">
            <input ref="refAreaPengelolaanPDF" type="file" class="form-control-file" accept="application/pdf" :disabled="isReadOnly" @change="!isReadOnly && onChangeFiles(6)">
            <button type="submit">Unggah</button> -->
      <!-- </form>
        </fieldset>
      </el-col> -->
    </el-form-item>

    <el-form-item label="Peta Titik Pemantauan" :required="required">
      <el-col :span="11" style="margin-right:1em;">
        <fieldset style="border:1px solid #e0e0e0; border-radius: 0.3em; width:100%; padding: .5em;">
          <legend style="margin:0 2em;">File-File SHP yang sudah di-zip
            <div v-if="url_peta_pemantauan_shp != null" class="current">tersimpan: <a style="color: green" :href="url_peta_pemantauan_shp"><strong>{{ peta_pemantauan_shp_name }}<i class="el-icon-circle-check" /></strong></a></div>
          </legend>

          <form v-if="isFormulator" @submit.prevent="handleSubmit">
            <input ref="refPemantauanSHP" type="file" class="form-control-file" :disabled="isReadOnly" @change="!isReadOnly && onChangeFiles(3)">
            <!-- <button type="submit">Unggah</button> -->
          </form>
        </fieldset>

      </el-col>

      <el-col :span="11" style="margin-right:1em;">
        <fieldset style="border:1px solid #e0e0e0; border-radius: 0.3em; width:100%; padding: .5em;">
          <legend style="margin:0 2em;">Versi PDF
            <div v-if="url_peta_pemantauan_pdf != null" class="current">tersimpan: <a style="color: green" :href="url_peta_pemantauan_pdf" target="_blank"><strong>{{ peta_pemantauan_pdf_name }}<i class="el-icon-circle-check" /></strong></a></div>
          </legend>

          <form v-if="isFormulator" @submit.prevent="handleSubmit">
            <input ref="refPemantauanPDF" type="file" class="form-control-file" accept="application/pdf" :disabled="isReadOnly" @change="!isReadOnly && onChangeFiles(4)">
            <!-- <button type="submit">Unggah</button> -->
          </form>
        </fieldset>
      </el-col>
    </el-form-item>

    <el-form-item label="Peta Lokasi Pemantauan" :required="required">
      <el-col :span="11" style="margin-right:1em;">
        <fieldset style="border:1px solid #e0e0e0; border-radius: 0.3em; width:100%; padding: .5em;">
          <legend style="margin:0 2em;">File-File SHP yang sudah di-zip
            <div v-if="url_peta_area_pemantauan_shp != null" class="current">tersimpan: <a style="color: green" :href="url_peta_area_pemantauan_shp"><strong>{{ peta_area_pemantauan_shp_name }}<i class="el-icon-circle-check" /></strong></a></div>
          </legend>

          <form v-if="isFormulator" @submit.prevent="handleSubmit">
            <input ref="refAreaPemantauanSHP" type="file" class="form-control-file" :disabled="isReadOnly" @change="!isReadOnly && onChangeFiles(7)">
            <!-- <button type="submit">Unggah</button> -->
          </form>
        </fieldset>

      </el-col>

      <!-- <el-col :span="11" style="margin-right:1em;">
        <fieldset style="border:1px solid #e0e0e0; border-radius: 0.3em; width:100%; padding: .5em;">
          <legend style="margin:0 2em;">Versi PDF
            <div v-if="url_peta_area_pemantauan_pdf != null" class="current">tersimpan: <a style="color: green" :href="url_peta_area_pemantauan_pdf" target="_blank"><strong>{{ peta_area_pemantauan_pdf_name }}<i class="el-icon-circle-check" /></strong></a></div>
          </legend>

          <form v-if="isFormulator" @submit.prevent="handleSubmit">
            <input ref="refAreaPemantauanPDF" type="file" class="form-control-file" accept="application/pdf" :disabled="isReadOnly" @change="!isReadOnly && onChangeFiles(8)"> -->
      <!-- <button type="submit">Unggah</button> -->
      <!-- </form>
        </fieldset>
      </el-col> -->
    </el-form-item>

    <div id="mapView" class="map-wrapper" />

    <el-row v-if="isFormulator" style="text-align:right;">
      <el-button size="medium" type="primary" :disabled="isReadOnly" @click="!isReadOnly && handleSubmit()">Unggah Peta</el-button>
    </el-row>
  </el-form>
</template>
<style scoped>
 legend {line-height: 1.5em; margin: .5em 0 2em;}
 div.map-wrapper { height: 400px;}
 fieldset {
   padding:1em;
 }
</style>
<script>
import { mapGetters } from 'vuex';
import Resource from '@/api/resource';
import request from '@/utils/request';
import axios from 'axios';

// Map-related
import shp from 'shpjs';
import Map from '@arcgis/core/Map';
import MapView from '@arcgis/core/views/MapView';
import GeoJSONLayer from '@arcgis/core/layers/GeoJSONLayer';
import Legend from '@arcgis/core/widgets/Legend';
import LayerList from '@arcgis/core/widgets/LayerList';
import * as urlUtils from '@arcgis/core/core/urlUtils';
import qs from 'qs';
import esriRequest from '@arcgis/core/request';
import urlBlob from '../../webgis/scripts/urlBlob';
import popupTemplate from '../../webgis/scripts/popupTemplate';
import popupTemplateKelolaPantau from '../../webgis/scripts/popupTemplateKelolaPantau';
import Expand from '@arcgis/core/widgets/Expand';
const uploadMaps = new Resource('project-map');
// const unggahMaps = new Resource('upload-map');
// const unduhMaps = new Resource('download-map');

export default {
  name: 'UploadPetaBatasRklRpl',
  data() {
    return {
      data: [],
      idProject: 0,
      currentMaps: [],
      petaLoading: false,
      petaPengelolaanPDF: '',
      petaPemantauanSHP: '',
      petaPemantauanPDF: '',
      petaPengelolaanSHP: '',
      petaAreaPengelolaanPDF: '',
      petaAreaPemantauanSHP: '',
      petaAreaPemantauanPDF: '',
      petaAreaPengelolaanSHP: '',
      petaTapakPDF: '',
      petaTapakSHP: '',
      petaEkologisPDF: '',
      petaEkologisSHP: '',
      petaSosialPDF: '',
      petaSosialSHP: '',
      petaStudiPDF: '',
      petaStudiSHP: '',
      url_peta_pengelolaan_pdf: null,
      peta_pengelolaan_shp_name: null,
      peta_pengelolaan_pdf_name: null,
      url_peta_pengelolaan_shp: null,
      peta_area_pengelolaan_pdf_name: null,
      url_peta_area_pengelolaan_pdf: null,
      peta_area_pengelolaan_shp_name: null,
      url_peta_area_pengelolaan_shp: null,
      peta_pemantauan_pdf_name: null,
      url_peta_pemantauan_pdf: null,
      peta_pemantauan_shp_name: null,
      url_peta_pemantauan_shp: null,
      peta_area_pemantauan_pdf_name: null,
      url_peta_area_pemantauan_pdf: null,
      peta_area_pemantauan_shp_name: null,
      url_peta_area_pemantauan_shp: null,
      files: [],
      idPengelolaanSHP: 0,
      idPengelolaanPDF: 0,
      idPemantauanPDF: 0,
      idPemantauanSHP: 0,
      idAreaPengelolaanSHP: 0,
      idAreaPengelolaanPDF: 0,
      idAreaPemantauanPDF: 0,
      idAreaPemantauanSHP: 0,
      idTapakSHP: 0,
      idTapakPDF: 0,
      idEkologisSHP: 0,
      idEkologisPDF: 0,
      idSosialSHP: 0,
      idSosialPDF: 0,
      idStudiSHP: 0,
      idStudiPDF: 0,
      index: 0,
      param: [],
      required: true,
      fileMap: [],
      mapShpFile: [],
      projectTitle: '',
      token: '',
      geomKelolaGeojson: {},
      geomPantauGeojson: {},
      geomKelolaProperties: {},
      geomPantauProperties: {},
      geomKelolaStyles: null,
      geomPantauStyles: null,
      geomAreaKelolaGeojson: {},
      geomAreaPantauGeojson: {},
      geomAreaKelolaProperties: {},
      geomAreaPantauProperties: {},
      geomAreaKelolaStyles: null,
      geomAreaPantauStyles: null,
      mapGeojsonArrayProject: [],
      // isVisible: false,
      // visible: [false, false, false, false, false, false, false],
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
    isFormulator() {
      return this.$store.getters.roles.includes('formulator');
    },
  },
  mounted() {
    urlUtils.addProxyRule({
      proxyUrl: 'proxy/proxy.php',
      urlPrefix: 'https://amdalgis.menlhk.go.id/',
    });

    var data = qs.stringify({
      'username': 'Amdalnet',
      'password': 'Amdalnet123',
      'client': 'requestip',
      'expiration': 20160,
      'f': 'json',
    });

    var config = {
      method: 'post',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Connection': 'keep-alive',
        'Content-Encoding': 'gzip',
      },
      body: data,
      responseType: 'json',
    };

    esriRequest('https://amdalgis.menlhk.go.id/portal/sharing/rest/generateToken', config)
      .then(response => {
        this.token = response.data.token;
      });

    this.idProject = parseInt(this.$route.params && this.$route.params.id);
    this.getData();
    this.getMap();
  },
  methods: {
    getMap() {
      this.petaLoading = true;
      const map = new Map({
        basemap: 'satellite',
      });

      const mapView = new MapView({
        container: 'mapView',
        map: map,
        center: [115.287, -1.588],
        zoom: 5,
      });
      this.$parent.mapView = mapView;

      axios.get(`api/map-geojson?id=${this.idProject}&type=tapak&step=ka&limit=1`)
        .then((response) => {
          response.data.forEach((item) => {
            const getType = JSON.parse(item.feature_layer);
            const propType = getType.features[0].properties.type;
            const propFields = getType.features[0].properties.field;
            const propStyles = getType.features[0].properties.styles;
            const step = getType.features[0].properties.step;

            // Tapak
            if (propType === 'tapak' && step === 'ka') {
              const geojsonLayerArray = new GeoJSONLayer({
                url: urlBlob(item.feature_layer),
                outFields: ['*'],
                visible: true,
                title: 'Layer Tapak Proyek',
                renderer: propStyles,
                popupTemplate: popupTemplate(propFields),
              });

              mapView.on('layerview-create', async(event) => {
                await mapView.goTo({
                  target: geojsonLayerArray.fullExtent,
                });
              });

              this.mapGeojsonArrayProject.push(geojsonLayerArray);
            }
          });
        });

      axios.get(`api/map-geojson?id=${this.idProject}`)
        .then((response) => {
          response.data.forEach((item) => {
            const getType = JSON.parse(item.feature_layer);
            const propType = getType.features[0].properties.type;
            const propFields = getType.features[0].properties.field;
            const propStyles = getType.features[0].properties.styles;

            // Ecology
            if (propType === 'ecology') {
              const geojsonLayerArray = new GeoJSONLayer({
                url: urlBlob(item.feature_layer),
                outFields: ['*'],
                title: 'Layer Batas Ekologis',
                visible: true,
                renderer: propStyles,
                popupTemplate: popupTemplate(propFields),
              });

              this.mapGeojsonArrayProject.push(geojsonLayerArray);
            }

            // Social
            if (propType === 'social') {
              const geojsonLayerArray = new GeoJSONLayer({
                url: urlBlob(item.feature_layer),
                outFields: ['*'],
                visible: true,
                title: 'Layer Batas Sosial',
                renderer: propStyles,
                popupTemplate: popupTemplate(propFields),
              });

              this.mapGeojsonArrayProject.push(geojsonLayerArray);
            }

            // Study
            if (propType === 'study') {
              const geojsonLayerArray = new GeoJSONLayer({
                url: urlBlob(item.feature_layer),
                outFields: ['*'],
                visible: true,
                title: 'Layer Batas Studi',
                renderer: propStyles,
                popupTemplate: popupTemplate(propFields),
              });

              this.mapGeojsonArrayProject.push(geojsonLayerArray);
              mapView.on('layerview-create', async(event) => {
                await mapView.goTo({
                  target: geojsonLayerArray.fullExtent,
                });
              });
            }

            // Pemantauan
            if (propType === 'pemantauan') {
              const geojsonLayerArray = new GeoJSONLayer({
                url: urlBlob(item.feature_layer),
                outFields: ['*'],
                visible: true,
                title: 'Layer Titik Pemantauan',
                renderer: propStyles,
                popupTemplate: popupTemplate(propFields),
              });

              this.mapGeojsonArrayProject.push(geojsonLayerArray);
            }

            // Area Pemantauan
            if (propType === 'area-pemantauan') {
              const geojsonLayerArray = new GeoJSONLayer({
                url: urlBlob(item.feature_layer),
                outFields: ['*'],
                visible: true,
                title: 'Layer Area Pemantauan',
                renderer: propStyles,
                popupTemplate: popupTemplate(propFields),
              });

              this.mapGeojsonArrayProject.push(geojsonLayerArray);
            }

            // Pengelolaan
            if (propType === 'pengelolaan') {
              const geojsonLayerArray = new GeoJSONLayer({
                url: urlBlob(item.feature_layer),
                outFields: ['*'],
                visible: true,
                title: 'Layer Titik Pengelolaan',
                renderer: propStyles,
                popupTemplate: popupTemplate(propFields),
              });

              this.mapGeojsonArrayProject.push(geojsonLayerArray);
            }

            // Area Pengelolaan
            if (propType === 'area-pengelolaan') {
              const geojsonLayerArray = new GeoJSONLayer({
                url: urlBlob(item.feature_layer),
                outFields: ['*'],
                visible: true,
                title: 'Layer Area Pengelolaan',
                renderer: propStyles,
                popupTemplate: popupTemplate(propFields),
              });

              this.mapGeojsonArrayProject.push(geojsonLayerArray);
            }

            map.addMany(this.mapGeojsonArrayProject);
          });
          this.petaLoading = false;
        });
      const layerList = new LayerList({
        view: mapView,
        container: document.createElement('div'),
        listItemCreatedFunction: this.defineActions,
      });

      layerList.on('trigger-action', (event) => {
        const id = event.action.id;
        if (id === 'full-extent') {
          mapView.goTo({
            target: event.item.layer.fullExtent,
          });
        }
      });

      const legend = new Legend({
        view: mapView,
        container: document.createElement('div'),
      });

      const layerListExpand = new Expand({
        expandIconClass: 'esri-icon-layer-list',
        expandTooltip: 'Layer List',
        view: mapView,
        content: layerList,
      });

      const legendExpand = new Expand({
        expandIconClass: 'esri-icon-basemap',
        expandTooltip: 'Legend Layer',
        view: mapView,
        content: legend,
      });

      mapView.ui.add(layerListExpand, 'top-right');
      mapView.ui.add(legendExpand, 'top-right');
    },
    async getData(){
      this.data = [];
      const files = await uploadMaps.list({
        id_project: this.idProject,
      });
      this.data = files.data;
      this.loadAttachment();
      this.process(files.data);
    },
    loadAttachment() {
      const data = this.data;
      data.forEach((map) => {
        if (map.file_type === 'SHP' && map.attachment_type === 'pengelolaan') {
          this.url_peta_pengelolaan_shp = map.map_file_url;
          this.peta_pengelolaan_shp_name = map.original_filename;
        } else if (map.file_type === 'PDF' && map.attachment_type === 'pengelolaan') {
          this.url_peta_pengelolaan_pdf = map.map_file_url;
          this.peta_pengelolaan_pdf_name = map.original_filename;
        } else if (map.file_type === 'SHP' && map.attachment_type === 'area-pengelolaan') {
          this.url_peta_area_pengelolaan_shp = map.map_file_url;
          this.peta_area_pengelolaan_shp_name = map.original_filename;
        } else if (map.file_type === 'PDF' && map.attachment_type === 'area-pengelolaan') {
          this.url_peta_area_pengelolaan_pdf = map.map_file_url;
          this.peta_area_pengelolaan_pdf_name = map.original_filename;
        } else if (map.file_type === 'SHP' && map.attachment_type === 'pemantauan') {
          this.url_peta_pemantauan_shp = map.map_file_url;
          this.peta_pemantauan_shp_name = map.original_filename;
        } else if (map.file_type === 'PDF' && map.attachment_type === 'pemantauan') {
          this.url_peta_pemantauan_pdf = map.map_file_url;
          this.peta_pemantauan_pdf_name = map.original_filename;
        } else if (map.file_type === 'SHP' && map.attachment_type === 'area-pemantauan') {
          this.url_peta_area_pemantauan_shp = map.map_file_url;
          this.peta_area_pemantauan_shp_name = map.original_filename;
        } else if (map.file_type === 'PDF' && map.attachment_type === 'area-pemantauan') {
          this.url_peta_area_pemantauan_pdf = map.map_file_url;
          this.peta_area_pemantauan_pdf_name = map.original_filename;
        }
      });
    },
    process(files){
      files.forEach((e) => {
        switch (e.attachment_type){
          case 'tapak':
            if (e.file_type === 'SHP') {
              this.petaTapakSHP = e.original_filename;
              this.idTapakSHP = e.id;
            } else {
              this.petaTapakPDF = e.original_filename;
              this.idTapakPDF = e.id;
            }
            break;
          case 'ecology':
            if (e.file_type === 'SHP') {
              this.petaEkologisSHP = e.original_filename;
              this.idEkologisSHP = e.id;
            } else {
              this.petaEkologisPDF = e.original_filename;
              this.idEkologisPDF = e.id;
            }
            break;
          case 'social':
            if (e.file_type === 'SHP') {
              this.petaSosialSHP = e.original_filename;
              this.idSosialSHP = e.id;
            } else {
              this.petaSosialPDF = e.original_filename;
              this.idSosialPDF = e.id;
            }
            break;
          case 'study':
            if (e.file_type === 'SHP') {
              this.petaStudiSHP = e.original_filename;
              this.idStudiSHP = e.id;
            } else {
              this.petaStudiPDF = e.original_filename;
              this.idStudiPDF = e.id;
            }
            break;
          case 'pengelolaan':
            if (e.file_type === 'SHP') {
              this.petaPengelolaanSHP = e.original_filename;
              this.idPengelolaanSHP = e.id;
            } else {
              this.petaPengelolaanPDF = e.original_filename;
              this.idPengelolaanPDF = e.id;
            }
            break;
          case 'pemantauan':
            if (e.file_type === 'SHP') {
              this.petaPemantauanSHP = e.original_filename;
              this.idPemantauanSHP = e.id;
            } else {
              this.petaPemantauanPDF = e.original_filename;
              this.idPemantauanPDF = e.id;
            }
            break;
          case 'area-pengelolaan':
            if (e.file_type === 'SHP') {
              this.petaAreaPengelolaanSHP = e.original_filename;
              this.idAreaPengelolaanSHP = e.id;
            } else {
              this.petaAreaPengelolaanPDF = e.original_filename;
              this.idAreaPengelolaanPDF = e.id;
            }
            break;
          case 'area-pemantauan':
            if (e.file_type === 'SHP') {
              this.petaAreaPemantauanSHP = e.original_filename;
              this.idAreaPemantauanSHP = e.id;
            } else {
              this.petaAreaPemantauanPDF = e.original_filename;
              this.idAreaPemantauanPDF = e.id;
            }
            break;
        }
      });
    },
    uploadMap(){
      const map = new Map({
        basemap: 'satellite',
      });

      //  Map Pengelolaan
      const mapPengelolaan = this.$refs.refPengelolaanSHP.files[0];
      const readerPengelolaan = new FileReader();
      readerPengelolaan.onload = (event) => {
        const base = event.target.result;
        shp(base).then((data) => {
          const valid = [
            'ID',
            'KETERANGAN',
            'PEMRAKARSA',
            'KEGIATAN',
            'TAHUN',
            'LAYER',
            'TIPE_DOKUM',
            'PROVINSI',
            'KODE',
          ];

          const uploaded = Object.keys(data.features[0].properties);
          const checker = (arr, target) => target.every(v => arr.includes(v));
          const checkShapefile = checker(uploaded, valid);

          if (!checkShapefile) {
            alert('Atribut .shp yang dimasukkan tidak sesuai dengan format yang benar. Download sample diatas!');
            this.$refs.refPengelolaanSHP.value = null;
            return;
          }

          this.geomKelolaGeojson = [];
          this.geomKelolaProperties = [];
          this.geomKelolaStyles = 6;

          data.features.map((value, index) => {
            this.geomKelolaGeojson.push(value.geometry);
            this.geomKelolaProperties.push(value.properties);
          });

          const blob = new Blob([JSON.stringify(data)], {
            type: 'application/json',
          });

          const renderer = {
            type: 'simple',
            field: '*',
            symbol: {
              type: 'picture-marker', // autocasts as new SimpleMarkerSymbol()
              url: '/titik_kelola.png',
              width: '24px',
              height: '24px',
            },
          };
          const url = URL.createObjectURL(blob);
          const layerPengelolaan = new GeoJSONLayer({
            url: url,
            visible: true,
            outFields: ['*'],
            opacity: 0.75,
            title: 'Layer Titik Pengelolaan',
            renderer: renderer,
            popupTemplate: popupTemplateKelolaPantau(this.geomKelolaProperties),
          });

          map.add(layerPengelolaan);
        });
      };
      if (mapPengelolaan !== undefined) {
        readerPengelolaan.readAsArrayBuffer(mapPengelolaan);
      }

      //  Map Area Pengelolaan
      const mapAreaPengelolaan = this.$refs.refAreaPengelolaanSHP.files[0];
      const readerAreaPengelolaan = new FileReader();
      readerAreaPengelolaan.onload = (event) => {
        const base = event.target.result;
        shp(base).then((data) => {
          const valid = [
            'ID',
            'KETERANGAN',
            'PEMRAKARSA',
            'KEGIATAN',
            'TAHUN',
            'LAYER',
            'TIPE_DOKUM',
            'PROVINSI',
            'KODE',
          ];

          const uploaded = Object.keys(data.features[0].properties);
          const checker = (arr, target) => target.every(v => arr.includes(v));
          const checkShapefile = checker(uploaded, valid);

          if (!['Polygon', 'MultiPolygon'].includes(data.features[0].geometry.type)) {
            alert('SHP yang dimasukkan harus Polygon atau MultiPolygon!.');
            this.$refs.refAreaPengelolaanSHP.value = null;
            return;
          }

          if (!checkShapefile) {
            alert('Atribut .shp yang dimasukkan tidak sesuai dengan format yang benar. Download sample diatas!');
            this.$refs.refAreaPengelolaanSHP.value = null;
            return;
          }

          this.geomAreaKelolaGeojson = [];
          this.geomAreaKelolaProperties = [];
          this.geomAreaKelolaStyles = 6;

          data.features.map((value, index) => {
            this.geomAreaKelolaGeojson.push(value.geometry);
            this.geomAreaKelolaProperties.push(value.properties);
          });

          const blob = new Blob([JSON.stringify(data)], {
            type: 'application/json',
          });

          const renderer = {
            type: 'simple',
            field: '*',
            // symbol: {
            //   type: 'picture-marker', // autocasts as new SimpleMarkerSymbol()
            //   url: '/titik_kelola.png',
            //   width: '24px',
            //   height: '24px',
            // },
            symbol: {
              type: 'simple-fill',
              color: [200, 0, 0, 1],
              outline: {
                color: [200, 0, 0, 1],
              },
            },
          };
          const url = URL.createObjectURL(blob);
          const layerAreaPengelolaan = new GeoJSONLayer({
            url: url,
            visible: true,
            outFields: ['*'],
            opacity: 0.75,
            title: 'Layer Area Pengelolaan',
            renderer: renderer,
            popupTemplate: popupTemplateKelolaPantau(this.geomAreaKelolaProperties),
          });

          map.add(layerAreaPengelolaan);
        });
      };
      if (mapAreaPengelolaan !== undefined) {
        readerAreaPengelolaan.readAsArrayBuffer(mapAreaPengelolaan);
      }

      //  Map Pemantauan
      const mapPemantauan = this.$refs.refPemantauanSHP.files[0];
      const readerPemantauan = new FileReader();
      readerPemantauan.onload = (event) => {
        const base = event.target.result;
        shp(base).then((data) => {
          const valid = [
            'ID',
            'KETERANGAN',
            'PEMRAKARSA',
            'KEGIATAN',
            'TAHUN',
            'LAYER',
            'TIPE_DOKUM',
            'PROVINSI',
            'KODE',
          ];

          const uploaded = Object.keys(data.features[0].properties);

          const checker = (arr, target) => target.every(v => arr.includes(v));
          const checkShapefile = checker(uploaded, valid);

          if (!checkShapefile) {
            alert('Atribut .shp yang dimasukkan tidak sesuai dengan format yang benar. Download sample diatas!');
            this.$refs.refPemantauanSHP.value = null;
            return;
          }

          this.geomPantauGeojson = [];
          this.geomPantauProperties = [];
          this.geomPantauStyles = 5;

          data.features.map((value, index) => {
            this.geomPantauGeojson.push(value.geometry);
            this.geomPantauProperties.push(value.properties);
          });

          const blob = new Blob([JSON.stringify(data)], {
            type: 'application/json',
          });

          const renderer = {
            type: 'simple',
            field: '*',
            symbol: {
              type: 'picture-marker', // autocasts as new SimpleMarkerSymbol()
              url: '/titik_pantau.png',
              width: '24px',
              height: '24px',
            },
          };
          const url = URL.createObjectURL(blob);
          const layerPemantauan = new GeoJSONLayer({
            url: url,
            visible: true,
            outFields: ['*'],
            opacity: 0.75,
            title: 'Layer Titik Pemantauan',
            renderer: renderer,
            popupTemplate: popupTemplateKelolaPantau(this.geomPantauProperties),
          });

          map.add(layerPemantauan);
          // mapView.on('layerview-create', async(event) => {
          //   await mapView.goTo({
          //     target: layerPemantauan.fullExtent,
          //   });
          // });
        });
      };
      if (mapPemantauan !== undefined) {
        console.log(mapPemantauan);
        readerPemantauan.readAsArrayBuffer(mapPemantauan);
      }

      //  Map Area Pemantauan
      const mapAreaPemantauan = this.$refs.refAreaPemantauanSHP.files[0];
      const readerAreaPemantauan = new FileReader();
      readerAreaPemantauan.onload = (event) => {
        const base = event.target.result;
        shp(base).then((data) => {
          const valid = [
            'ID',
            'KETERANGAN',
            'PEMRAKARSA',
            'KEGIATAN',
            'TAHUN',
            'LAYER',
            'TIPE_DOKUM',
            'PROVINSI',
            'KODE',
          ];

          const uploaded = Object.keys(data.features[0].properties);

          const checker = (arr, target) => target.every(v => arr.includes(v));
          const checkShapefile = checker(uploaded, valid);

          if (!['Polygon', 'MultiPolygon'].includes(data.features[0].geometry.type)) {
            alert('SHP yang dimasukkan harus Polygon atau MultiPolygon!.');
            this.$refs.refAreaPemantauanSHP.value = null;
            return;
          }

          if (!checkShapefile) {
            alert('Atribut .shp yang dimasukkan tidak sesuai dengan format yang benar. Download sample diatas!');
            this.$refs.refAreaPemantauanSHP.value = null;
            return;
          }

          this.geomAreaPantauGeojson = [];
          this.geomAreaPantauProperties = [];
          this.geomAreaPantauStyles = 5;

          data.features.map((value, index) => {
            this.geomAreaPantauGeojson.push(value.geometry);
            this.geomAreaPantauProperties.push(value.properties);
          });

          const blob = new Blob([JSON.stringify(data)], {
            type: 'application/json',
          });

          const renderer = {
            type: 'simple',
            field: '*',
            // symbol: {
            //   type: 'picture-marker', // autocasts as new SimpleMarkerSymbol()
            //   url: '/titik_pantau.png',
            //   width: '24px',
            //   height: '24px',
            // },
            symbol: {
              type: 'simple-fill',
              color: [200, 0, 0, 1],
              outline: {
                color: [200, 0, 0, 1],
              },
            },
          };
          const url = URL.createObjectURL(blob);
          const layerAreaPemantauan = new GeoJSONLayer({
            url: url,
            visible: true,
            outFields: ['*'],
            opacity: 0.75,
            title: 'Layer Area Pemantauan',
            renderer: renderer,
            popupTemplate: popupTemplateKelolaPantau(this.geomAreaPantauProperties),
          });

          map.add(layerAreaPemantauan);
          mapView.on('layerview-create', async(event) => {
            await mapView.goTo({
              target: layerAreaPemantauan.fullExtent,
            });
          });
        });
      };
      if (mapAreaPemantauan !== undefined) {
        readerAreaPemantauan.readAsArrayBuffer(mapAreaPemantauan);
      }

      const mapView = new MapView({
        container: 'mapView',
        map: map,
        center: [115.287, -1.588],
        zoom: 5,
      });
      this.$parent.mapView = mapView;

      const layerList = new LayerList({
        view: mapView,
        container: document.createElement('div'),
        listItemCreatedFunction: this.defineActions,
      });

      layerList.on('trigger-action', (event) => {
        const id = event.action.id;
        if (id === 'full-extent') {
          mapView.goTo({
            target: event.item.layer.fullExtent,
          }).catch(function(error) {
            console.error(error);
          });
        }
      });

      const legend = new Legend({
        view: mapView,
        container: document.createElement('div'),
      });

      const layerListExpand = new Expand({
        expandIconClass: 'esri-icon-layer-list',
        expandTooltip: 'Layer List',
        view: mapView,
        content: layerList,
      });

      const legendExpand = new Expand({
        expandIconClass: 'esri-icon-basemap',
        expandTooltip: 'Legend Layer',
        view: mapView,
        content: legend,
      });

      mapView.ui.add(layerListExpand, 'top-right');
      mapView.ui.add(legendExpand, 'top-right');
      this.isMapUploaded = true;
    },
    handleSubmit(){
      if (this.$refs.refPengelolaanPDF.files[0] === undefined && this.peta_pengelolaan_pdf_name === null) {
        this.$alert('Silahkan Unggah Peta Pengelolaan PDF', 'Perhatian', {
          center: true,
        });
      } else if (this.$refs.refPengelolaanSHP.files[0] === undefined && this.$refs.refAreaPengelolaanSHP.files[0] === undefined && this.peta_pengelolaan_shp_name === null && this.peta_area_pengelolaan_shp_name === null) {
        this.$alert('Silahkan Unggah Peta Pengelolaan dan/ Lokasi Pengelolaan SHP', 'Perhatian', {
          center: true,
        });
      } else if (this.$refs.refPemantauanPDF.files[0] === undefined && this.peta_pemantauan_pdf_name === null) {
        this.$alert('Silahkan Unggah Peta Pemantauan PDF', 'Perhatian', {
          center: true,
        });
      } else if (this.$refs.refPemantauanSHP.files[0] === undefined && this.$refs.refAreaPemantauanSHP.files[0] === undefined && this.peta_pemantauan_shp_name === null && this.peta_area_pemantauan_shp_name === null) {
        this.$alert('Silahkan Unggah Peta Pemantauan dan/ Lokasi Pemantauan SHP', 'Perhatian', {
          center: true,
        });
      } else {
        this.petaLoading = true;
        const formData = new FormData();
        formData.append('id_project', this.idProject);
        formData.append('step', 'rkl-rpl');

        urlUtils.addProxyRule({
          proxyUrl: 'proxy/proxy.php',
          urlPrefix: 'https://amdalgis.menlhk.go.id/',
        });
        this.files.forEach((e, i) => {
          formData.append('files[]', e[0]);
          formData.append('params[]', JSON.stringify(this.param[i]));
          if (this.param[i].file_type === 'SHP') {
            // titik
            formData.append('geomKelolaGeojson', JSON.stringify(this.geomKelolaGeojson));
            formData.append('geomPantauGeojson', JSON.stringify(this.geomPantauGeojson));
            formData.append('geomKelolaProperties', JSON.stringify(this.geomKelolaProperties));
            formData.append('geomPantauProperties', JSON.stringify(this.geomPantauProperties));
            formData.append('geomKelolaStyles', JSON.stringify(this.geomKelolaStyles));
            formData.append('geomPantauStyles', JSON.stringify(this.geomPantauStyles));

            // Area
            formData.append('geomAreaKelolaGeojson', JSON.stringify(this.geomAreaKelolaGeojson));
            formData.append('geomAreaPantauGeojson', JSON.stringify(this.geomAreaPantauGeojson));
            formData.append('geomAreaKelolaProperties', JSON.stringify(this.geomAreaKelolaProperties));
            formData.append('geomAreaPantauProperties', JSON.stringify(this.geomAreaPantauProperties));
            formData.append('geomAreaKelolaStyles', JSON.stringify(this.geomAreaKelolaStyles));
            formData.append('geomAreaPantauStyles', JSON.stringify(this.geomAreaPantauStyles));
          }

          var projectTitle = '';

          axios.get(`api/projects/${this.idProject}`)
            .then(response => {
              projectTitle = response.data.project_title;
              console.log(response.data);
              console.log(response.data.project_title);
            });

          console.log(projectTitle);

          var myHeaders = new Headers();
          myHeaders.append('Authorization', 'Bearer ' + this.token);

          var formdatas = new FormData();
          formdatas.append('url', 'https://amdalgis.menlhk.go.id/server/rest/services/Hosted/Test/FeatureServer');
          formdatas.append('type', 'Feature Service');
          formdatas.append('title', projectTitle + this.idProject);
          formdatas.append('file', e[0]);

          var requestOptions = {
            method: 'POST',
            headers: myHeaders,
            body: formdatas,
            redirect: 'follow',
          };

          esriRequest('https://amdalgis.menlhk.go.id/portal/sharing/rest/content/users/Amdalnet/addItem', requestOptions)
            .then(response => response.text())
            .then(result => console.log(result))
            .catch(error => console.log('error', error));
        });

        axios.post('api/upload-maps', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          }})
          .then((response) => {
            if (response) {
              this.petaLoading = false;
              this.$message({
                message: 'Berhasil menyimpan file ', //  + this.files[0].name,
                type: 'success',
              });
              this.$emit('handlePetaBatasUploaded');
              this.$emit('handleEnableSimpanLanjutkan');
            }
          });
      }
    },
    async doSubmit(load){
      return request(load);
    },
    download(id){
      return;
    },
    onChangeFiles(idx){
      const index = idx - 1;
      switch (idx) {
        case 1:
          if (this.$refs.refPengelolaanSHP.files[0].size <= 10485760) {
            if (!this.$refs.refPengelolaanSHP.files[0].type.includes('zip')) {
              this.$refs.refPengelolaanSHP.value = null;
              this.$alert('File yang diterima hanya .zip', 'Format Peta Titik Pengelolaan Salah', {
                center: true,
              });
              return;
            } else {
              this.files[index] = this.$refs.refPengelolaanSHP.files;
              this.param[index] = {
                attachment_type: 'pengelolaan',
                file_type: 'SHP',
              };
            }
          } else {
            this.$alert('Ukuran file tidak boleh lebih dari 10 MB', '', {
              center: true,
            });
            return;
          }
          // this.param[index]['file_type'] = 'SHP';
          break;
        case 2:
          if (this.$refs.refPengelolaanPDF.files[0].size <= 10485760) {
            if (this.$refs.refPengelolaanPDF.files[0].type !== 'application/pdf') {
              this.$refs.refPengelolaanPDF.value = null;
              this.$alert('File yang diterima hanya .pdf', 'Format Peta Titik Pengelolaan Salah', {
                center: true,
              });
              return;
            } else {
              this.files[index] = this.$refs.refPengelolaanPDF.files;
              this.param[index] = {
                attachment_type: 'pengelolaan',
                file_type: 'PDF',
              };
            }
          } else {
            this.$alert('Ukuran file tidak boleh lebih dari 10 MB', '', {
              center: true,
            });
            return;
          }

          // this.param[index]['file_type'] = 'PDF';
          // this.embedSrc = window.URL.createObjectURL(this.$refs.refPengelolaanPDF.files);
          break;
        case 3:
          if (this.$refs.refPemantauanSHP.files[0].size <= 10485760) {
            if (!this.$refs.refPemantauanSHP.files[0].type.includes('zip')) {
              this.$refs.refPemantauanSHP.value = null;
              this.$alert('File yang diterima hanya .zip', 'Format Peta Titik Pemantauan Salah', {
                center: true,
              });
              return;
            } else {
              this.files[index] = this.$refs.refPemantauanSHP.files;
              this.param[index] = {
                attachment_type: 'pemantauan',
                file_type: 'SHP',
              };
            }
          } else {
            this.$alert('Ukuran file tidak boleh lebih dari 10 MB', '', {
              center: true,
            });
            return;
          }
          break;
        case 4:
          if (this.$refs.refPemantauanPDF.files[0].size <= 10485760) {
            if (this.$refs.refPemantauanPDF.files[0].type !== 'application/pdf') {
              this.$refs.refPemantauanPDF.value = null;
              this.$alert('File yang diterima hanya .pdf', 'Format Peta Titik Pemantauan Salah', {
                center: true,
              });
              return;
            } else {
              this.files[index] = this.$refs.refPemantauanPDF.files;
              this.param[index] = {
                attachment_type: 'pemantauan',
                file_type: 'PDF',
              };
            }
          } else {
            this.$refs.refPemantauanPDF.value = null;
            this.$alert('Ukuran file tidak boleh lebih dari 10 MB', '', {
              center: true,
            });
            return;
          }
          break;
        case 5:
          if (this.$refs.refAreaPengelolaanSHP.files[0].size <= 10485760) {
            if (!this.$refs.refAreaPengelolaanSHP.files[0].type.includes('zip')) {
              this.$refs.refAreaPengelolaanSHP.value = null;
              this.$alert('File yang diterima hanya .zip', 'Format Peta Lokasi Pengelolaan Salah', {
                center: true,
              });
              return;
            } else {
              this.files[index] = this.$refs.refAreaPengelolaanSHP.files;
              this.param[index] = {
                attachment_type: 'area-pengelolaan',
                file_type: 'SHP',
              };
            }
          } else {
            this.$alert('Ukuran file tidak boleh lebih dari 10 MB', '', {
              center: true,
            });
            return;
          }
          break;
        case 6:
          if (this.$refs.refAreaPengelolaanPDF.files[0].size <= 10485760) {
            if (this.$refs.refAreaPengelolaanPDF.files[0].type !== 'application/pdf') {
              this.$refs.refAreaPengelolaanPDF.value = null;
              this.$alert('File yang diterima hanya .pdf', 'Format Peta Lokasi Pengelolaan Salah', {
                center: true,
              });
              return;
            } else {
              this.files[index] = this.$refs.refAreaPengelolaanPDF.files;
              this.param[index] = {
                attachment_type: 'area-pengelolaan',
                file_type: 'PDF',
              };
            }
          } else {
            this.$refs.refAreaPengelolaanPDF.value = null;
            this.$alert('Ukuran file tidak boleh lebih dari 10 MB', '', {
              center: true,
            });
            return;
          }
          break;
        case 7:
          if (this.$refs.refAreaPemantauanSHP.files[0].size <= 10485760) {
            if (!this.$refs.refAreaPemantauanSHP.files[0].type.includes('zip')) {
              this.$refs.refAreaPemantauanSHP.value = null;
              this.$alert('File yang diterima hanya .zip', 'Format Peta Lokasi Pemantauan Salah', {
                center: true,
              });
              return;
            } else {
              this.files[index] = this.$refs.refAreaPemantauanSHP.files;
              this.param[index] = {
                attachment_type: 'area-pemantauan',
                file_type: 'SHP',
              };
            }
          } else {
            this.$alert('Ukuran file tidak boleh lebih dari 10 MB', '', {
              center: true,
            });
            return;
          }

          break;
        case 8:
          if (this.$refs.refAreaPemantauanPDF.files[0].size <= 10485760) {
            if (this.$refs.refAreaPemantauanPDF.files[0].type !== 'application/pdf') {
              this.$refs.refAreaPemantauanPDF.value = null;
              this.$alert('File yang diterima hanya .pdf', 'Format Peta Lokasi Pemantauan Salah', {
                center: true,
              });
              return;
            } else {
              this.files[index] = this.$refs.refAreaPemantauanPDF.files;
              this.param[index] = {
                attachment_type: 'area-pemantauan',
                file_type: 'PDF',
              };
            }
          } else {
            this.$refs.refAreaPemantauanPDF.value = null;
            this.$alert('Ukuran file tidak boleh lebih dari 10 MB', '', {
              center: true,
            });
            return;
          }
          break;
        default:
      }
      // this.showMap(idx);
      this.uploadMap();
    },
    defineActions(event) {
      const item = event.item;

      item.actionsSections = [
        [
          {
            title: 'Go to full extent',
            className: 'esri-icon-zoom-in-magnifying-glass',
            id: 'full-extent',
          },
        ],
      ];
    },
    showMap(id){
      this.visible[id] = true;
      this.isVisible = true;
      document.getElementById('map' + id).style.display = 'block';
    },
  },
};
</script>

<style scoped>
.download__sample {
  color: white;
  padding: 7px;
  background-color: rgb(33, 94, 4);
  border-radius: 4px;
  font-weight: 500;
  font-size: 13px;
}
.download__juknis {
  color: white;
  padding: 5px;
  background-color: #dd8705;
  border-radius: 4px;
  font-weight: 500;
  font-size: 11px;
}

.download__juknis:hover {
  background-color: #af6900;
  color: white;
}

.download__sample:hover {
  background-color: orangered;
  color: white;
}
</style>
