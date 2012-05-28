function formViewer(element, optConf) {
	this.options={};
	this.mode;
	var self=$(this);
	this.getStructure = function (){
		//var self = $(this);
		if (typeof($(this).options.fields)=='undefined'){
			alert('error2');
			return;
		}
		console.log(self.options.fields);
		$.each(self.options.fields,function(k,v){
			
		});
	};
	this.getRow = function(){
		
	}
	this.createForm = function(){
		
	}
	this.options=optConf;
	this.getStructure()
	//console.log(this.options);
}