$(document).ready(function() {
	var myLayout = $('#container').layout({
		north: { size: 90, spacing_open: 10, closable: false, resizable: false },
		south: { size: 20, spacing_open: 10, closable: false, resizable: false }
	});
	$("#myform").dform({
		"action" : "index.php",
		"method" : "post",
		'class': "ui-widget",
		'css':'width:100%;',
		"elem" :
		[	{
			'type' : "div",
			'class': '',
			'html': [{
					'type' : "span",
					'class':'',
					'html':'Form di Test'
			},{
				'type':'button',
				'html':'edit',
				'class':'edit-btn',
				'css':'float:right;'
					
			}
			]
		},
			{
			'type' : "table",
			'class':'stiletabella',
			'html' : [{
				'type':'tr',
				'html':[{
					'type':'td',
					'html':[{
						"type":"select",
						"name":"continent",
						"caption":"Choose a continent",
						"options":{
							"america":"America",
							"europe":{
								"selected":"true",
								"id":"europe-option",
								"value":"europe",
								"html":"Europe"
							},
							"asia":"Asia",
							"africa":"Africa",
							"australia":"Australia"
						}
					}]
				},
				{
					'type':'td',
					'html':[{
						"name":"city",
						"caption":"City",
						"type":"text",
						"validate":{ "required":true }
					}]
				}
			]
		},
		{
				'type':'tr',
				'html':[{
					'type':'td',
					'html':[{
						"type" : "select",
						'caption':'State',
						"options" : {
						"northamerica" : {
							"type" : "optgroup",
							"label" : "North America",
							"options" : {
						"us" : "USA",
						"ca" : "Canada"
							}
						},
						"europe" : {
							"type" : "optgroup",
							"label" : "Europe",
							"options" : {
							"de" : {
							"selected" : "selected",
							"html" : "Germany"
						},
						"fr" : "France"
							}
						}
						}
					}]
				},
				{
					'type':'td',
					'html':[{
						"name":"date",
						"caption":"Date",
						"type":"text",
						"datepicker":{}
					}]
				}
			]
		}
		]
	}]
	});
});


