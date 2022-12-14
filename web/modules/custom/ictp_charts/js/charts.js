(function () {
  'use strict';
  Drupal.behaviors.ictpCharts = {
    attach: function () {

      let chartcontainer = document.querySelectorAll('.charts-highchart').forEach(function (el) {
        el.addEventListener('drupalChartsConfigsInitialization', function (e) {
          console.log('FIRE')
          let data = e.detail;
          const id = data.drupalChartDivId;
          data.legend.useHTML = true
          data.legend.layout = 'horizontal'

          //altezza grafico 2/3 della viewport (compresa legenda ?? calcolo fatto dalla libreria sul container)
          let chartheight = window.innerHeight*2/3;
          //fissa altezza minima a 600px per evitare ridimensionameto troppo piccolo
          data.chart.height = chartheight < 600 ? 600 : chartheight

          //rotazine labels sugli assi
          data.xAxis[0].labels.rotation = -30;
          data.yAxis[0].labels.rotation = 0;

          //larghezza fissa colonne
          //data.plotOptions.series.pointWidth = 20;

          data.legend.labelFormatter = function () {
            return '<span>symbol</span>' + this.name;
          }


          if (id === 'charts-item-91fb9792-84bb-4f4e-a9fa-1822c231563e-0') {
            // solo publications
            data.series[0].innerSize = '75%'
            data.tooltip.pointFormat = '{series.name}: <b>{point.percentage:.1f}%</b>'
            data.plotOptions.pie.allowPointSelect = true
            data.plotOptions.pie.dataLabels = {
              format: '<b>{point.name}</b><br>{point.percentage:.1f} %'
            }

          } else if (id === 'charts-item-a2e06067-a6e1-477b-b85f-e8d0a4da7a37-0') {
            data.xAxis[0].tickInterval = 5;
          }
          
          Drupal.Charts.Contents.update(id, data);
        });
      })

      setTimeout(() => {
        var array = []
        document.querySelectorAll('.highcharts-root .highcharts-legend .highcharts-legend-item .highcharts-point').forEach(el => {
          //console.log(el.getAttribute('fill'))
          array.push(el.getAttribute('fill'))
        })

        document.querySelectorAll('.charts-highchart .highcharts-legend .highcharts-legend-item span span').forEach((el, ind) => {
          /* console.log(el.getAttribute('fill')) */
          el.setAttribute('style', 'background-color:' + array[ind] + ';')
          /* array.push(el.getAttribute('fill')) */
        })
      }, 50);

    }
  };
}());