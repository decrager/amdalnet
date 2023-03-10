<template>
  <div :class="className" :style="{height:height,width:width}" />
</template>

<script>
import echarts from 'echarts';
require('echarts/theme/macarons'); // echarts theme
import { debounce } from '@/utils';

export default {
  props: {
    className: {
      type: String,
      default: 'chart',
    },
    width: {
      type: String,
      default: '100%',
    },
    height: {
      type: String,
      default: '300px',
    },
    chartData: {
      type: Object,
      required: true,
    },
    xAxis: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      chart: null,
      sidebarElm: null,
    };
  },
  watch: {
    chartData: {
      deep: true,
      handler(val) {
        this.setOptions(val);
      },
    },
  },
  mounted() {
    this.initChart();
    if (this.autoResize) {
      this.__resizeHandler = debounce(() => {
        if (this.chart) {
          this.chart.resize();
        }
      }, 100);
      window.addEventListener('resize', this.__resizeHandler);
    }
    // Monitor the sidebar changes
    this.sidebarElm = document.getElementsByClassName('sidebar-container')[0];
    this.sidebarElm &&
      this.sidebarElm.addEventListener(
        'transitionend',
        this.sidebarResizeHandler
      );
  },
  beforeDestroy() {
    if (!this.chart) {
      return;
    }
    if (this.autoResize) {
      window.removeEventListener('resize', this.__resizeHandler);
    }

    this.sidebarElm &&
      this.sidebarElm.removeEventListener(
        'transitionend',
        this.sidebarResizeHandler
      );

    this.chart.dispose();
    this.chart = null;
  },
  methods: {
    sidebarResizeHandler(e) {
      if (e.propertyName === 'width') {
        this.__resizeHandler();
      }
    },
    setOptions({ amdalData, ukluplData, spplData, aARKLRPLData } = {}) {
      const xAxisData = this.xAxis;
      console.log({ data: this.xAxis });
      this.chart.setOption({
        xAxis: {
          data: xAxisData,
          // left: 1,
          // boundaryGap: false,
          // axisTick: {
          //   show: false,
          // },
        },
        grid: {
          left: 10,
          right: 10,
          bottom: 20,
          top: 30,
          containLabel: true,
        },
        tooltip: {
          trigger: 'axis',
          axisPointer: {
            type: 'cross',
          },
          padding: [5, 10],
        },
        yAxis: {
          axisTick: {
            show: false,
          },
        },
        legend: {
          data: ['AMDAL', 'UKL UPL', 'SPPL', 'Addendum ANDAL dan RKL RPL'],
        },
        series: [
          {
            name: 'AMDAL',
            itemStyle: {
              normal: {
                color: '#0079c1',
                lineStyle: {
                  color: '#0079c1',
                  width: 1,
                },
              },
            },
            smooth: true,
            type: 'bar',
            data: amdalData,
            animationDuration: 2800,
            animationEasing: 'cubicInOut',
          },
          {
            name: 'UKL UPL',
            smooth: true,
            type: 'bar',
            itemStyle: {
              normal: {
                color: '#449748',
                lineStyle: {
                  color: '#449748',
                  width: 1,
                },
              },
            },
            data: ukluplData,
            animationDuration: 2800,
            animationEasing: 'quadraticOut',
          },
          {
            name: 'SPPL',
            itemStyle: {
              normal: {
                color: '#eb8a00',
                lineStyle: {
                  color: '#eb8a00',
                  width: 1,
                },
              },
            },
            smooth: true,
            type: 'bar',
            data: spplData,
            animationDuration: 2800,
            animationEasing: 'cubicInOut',
          },
          {
            name: 'Addendum ANDAL dan RKL RPL',
            itemStyle: {
              normal: {
                color: '#fac400',
                lineStyle: {
                  color: '#fac400',
                  width: 1,
                },
              },
            },
            smooth: true,
            type: 'bar',
            data: aARKLRPLData,
            animationDuration: 2800,
            animationEasing: 'cubicInOut',
          },
        ],
      });
    },
    initChart() {
      this.chart = echarts.init(this.$el, 'macarons');
      this.setOptions(this.chartData);
    },
  },
};
</script>
