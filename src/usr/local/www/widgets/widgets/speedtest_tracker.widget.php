<?php
require_once("guiconfig.inc");
?> 

<!-- The speedtest_tracker widget panel -->
<div id="speedtest-data">
  <div id="container" style="width: 100;overflow: hidden;" align="right">
<h6 align="center">Internet Speed - Mbps (Speedtest by Ookla)</h6>
    <canvas id="speedTestChart"></canvas>
  </div>
</div>

<thead>
	<title>Speedtest Tracker</title>
	<style>
		:root {
			/* Match traffic_graphs.widget.php (D3 Category10 palette) */
			--download-color: #1f77b4; /* blue, matches "in" */
			--upload-color: #ff7f0e;   /* orange, matches "out" */
			--label-color: #212529;
		}
		canvas {
			-moz-user-select: none;
			-webkit-user-select: none;
			-ms-user-select: none;
		}
	</style>
</thead>
<tbody>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let speedChartInstance = null;

function renderChart(timestampData, downloadData, uploadData) {

    const ctx = document.getElementById("speedTestChart")?.getContext("2d");
    if (!ctx) return;
    
	if (speedChartInstance !== null) {
		speedChartInstance.destroy();
	}
    
	const color = Chart.helpers.color;
	const TIMELABELS = [];

	timestampData.forEach(ts => {
		TIMELABELS.push(new Date(ts).toLocaleString([], {
			timeStyle: 'short'
		}));
	});
	
	// Get theme colors from CSS variables
	const rootStyle = getComputedStyle(document.documentElement);

	let labelColor = rootStyle.getPropertyValue('--label-color').trim() || '#212529';
	const tdtag = document.querySelector("td");
	if (tdtag) {
		labelColor = getComputedStyle(tdtag).color;
	}
	const downloadColor = rootStyle.getPropertyValue('--download-color').trim() || '#1f77b4';
	const uploadColor = rootStyle.getPropertyValue('--upload-color').trim() || '#ff7f0e';

	const lineChartData = {
		labels: TIMELABELS,
		datasets: [{
            label: 'Download',
            backgroundColor: color(downloadColor).alpha(0.2).rgbString(),
            borderColor: downloadColor,
            borderWidth: 2,
            data: downloadData 
        },
		{
			label: 'Upload',
			backgroundColor: color(uploadColor).alpha(0.2).rgbString(),
			borderColor: uploadColor,
			borderWidth: 2,
			data: uploadData
		}]
	};
		
    speedChartInstance = new Chart(ctx, {
        type: 'line',
        data: lineChartData,
		options: {
			responsive: true,
			tension: 0.35,
			//fill: true,
			//pointStyle: 'rectRounded',
			legend: {
				position: 'top'
			},
			scales: {
				y: { //beginAtZero: true,
			        ticks: {
			        	color: labelColor  // Y-axis tick labels
			        },	
					stepSize: 100
				},
				x: {
					ticks: {
						color: labelColor  // X-axis tick labels
					}
				}
			}
		}
    });
}


function reloadWidget(force = false) {
  const url = `/widgets/include/speedtest_tracker_backend.php?force=${force}&_=${Date.now()}`;

  fetch(url)
    .then(response => {
      if (!response.ok) throw new Error("Request failed");
      return response.json();
    })
    .then(data => {
      document.querySelector("#speedtest-data h6").textContent = data.title;
      renderChart(data.labels, data.download, data.upload);
      console.log("Speedtest Tracker Smart Data Load:", data.debug);
    })
    .catch(err => console.error("Widget fetch failed:", err));
}

// Initial load and periodic refresh
reloadWidget(true);

// On interval, allow cache if still valid
setInterval(() => reloadWidget(false), 30000); // 900000 = 15 minutes

</script>
</tbody>