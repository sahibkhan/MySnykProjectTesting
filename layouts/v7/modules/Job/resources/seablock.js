//sea_loading_country=job_sea_loading_country
$('select[data-fieldname="job_sea_loading_country"]').change(function(e){
   // e.preventDefault();

    var port_country_code =  $('select[data-fieldname="job_sea_loading_country"]').val(); 
    if(port_country_code!='')
    { 
     
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getseadata.php",
	           data:  {port_country_code:port_country_code} ,
	           success : function(data) {
	          console.log(data);
	          
	            $('select[data-fieldname="job_sea_loading_city"]').html(data);
	          	$('select').select2();
	            },error:function(e){
	               alert("error");}


	    });
    }
   

});

//puplate portCode on the base of  job_sea_loading_city by sahib
$('select[data-fieldname="job_sea_loading_city"]').change(function(e){
    //e.preventDefault();

    var port_city_code =  $('select[data-fieldname="job_sea_loading_city"]').val();
    var port_country_code =  $('select[data-fieldname="job_sea_loading_country"]').val(); 
 
    if(cityId!='')
    {
    	
        $.ajax({
           type: "POST",
           url : "include/Job/getseadata.php",
           data:  {port_city_code:port_city_code,portcountrycode:port_country_code} ,
           success : function(data) {
          
                   $('select[data-fieldname="job_sea_loading_port_code"]').html(data);	
                 	$('select').select2();
                },error:function(e){
	               alert("error");}

        });
    } 
  

});

//puplate value into read only field on the base of Air portName
//postcode= job_air_origin_airport_name
$('select[data-fieldname="job_sea_loading_port_code"]').change(function(e){
    e.preventDefault();
     var port_unlocode =  $('select[data-fieldname="job_sea_loading_port_code"]').val(); 

    	//Fill value into read only field on the base of  Origin Air Port code
   if(port_unlocode!='')
    {
	    $.ajax({
	           type: "POST",
	           url : "include/Job/getseadata.php",
	           data:  {port_unlocode:port_unlocode} ,
	           dataType: 'json',
	           success : function(data) {
	           //	alert(JSON.stringify(data));
	           	document.getElementById("Job_editView_fieldName_job_sea_loading_country_id").value= data[0].id;
	           	document.getElementById("Job_editView_fieldName_job_sea_loading_city_id").value= data[0].city_code;
	           	document.getElementById("Job_editView_fieldName_job_sea_loading_unlocode").value= data[0].air_unlocode;

	            }

	    });
    }
       
  

});
//END LIEF SIDE BLOCK ORIGIN 

