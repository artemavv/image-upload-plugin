
function get_labels_from_table() {
	
	const table = document.getElementById('iua-product-statistics');
	const labels = [];
	
	if ( table ) {
		const rows = table.getElementsByTagName('tr');
	
		// Start from i = 1 to skip the header row
		for ( let i = 1; i < rows.length; i++ ) {
				const firstCell = rows[i].cells[0];
				
				if (firstCell) {
						labels.push(firstCell.textContent.trim());
				}
		}	
	}
	
	return labels;
}

function get_datasets_from_table() {
	
	const table = document.getElementById('iua-product-statistics');
	const datasets = [];
	
	if ( table ) {
		const rows = table.getElementsByTagName('tr');
	
		// Start from i = 1 to skip the header row
		for ( let i = 0; i < rows.length; i++ ) {
			
			for ( let j = 1; j < rows[i].cells.length; j++ ) { // Start from j = 1 to skip the date column
			
				const d_id = j - 1;
				const datasetCell = rows[i].cells[j];
			
				if ( typeof datasets[d_id] === 'undefined' ) { // the very first row with labels
					
					const datasetLabel = datasetCell.textContent.trim();
					datasets[d_id] = {
						label: datasetLabel,
						data: []
					};
				}
				else {
					const value = datasetCell.textContent.trim();
					datasets[d_id].data.push( value );
				}
				
				
			}
		}	
	}
	
	console.log(datasets);
	
	return datasets;
}

function get_chart_data_for_iua() {

	const data = {
		labels: get_labels_from_table(),
		datasets: get_datasets_from_table() /* [
			{
				label: 'Dataset 1',
				data: Utils.numbers(NUMBER_CFG),
				borderColor: Utils.CHART_COLORS.red,
				backgroundColor: Utils.transparentize(Utils.CHART_COLORS.red, 0.5),
			},
			{
				label: 'Dataset 2',
				data: Utils.numbers(NUMBER_CFG),
				borderColor: Utils.CHART_COLORS.blue,
				backgroundColor: Utils.transparentize(Utils.CHART_COLORS.blue, 0.5),
			}
		]*/
	};
	
	return data;
}

const iua_chart_ctx = document.getElementById('iua-chart');

if ( iua_chart_ctx ) {

	const iua_data = get_chart_data_for_iua();

console.log(iua_data);
	new Chart(iua_chart_ctx, {
		type: 'line',
		data: iua_data,
		/*
		 data: {
		 labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
		 datasets: [{
		 label: '# of Votes',
		 data: [12, 19, 3, 5, 2, 3],
		 borderWidth: 1
		 }]
		 },*/
		options: {
			scales: {
				y: {
					beginAtZero: true
				}
			}
		}
	});
}