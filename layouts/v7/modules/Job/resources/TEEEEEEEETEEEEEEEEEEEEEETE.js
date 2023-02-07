function fieldsAirEnable(){
	//Orgin Air Field disable by default 
	$('select[data-fieldname="job_air_origin_country"]').val('');
    $('select[data-fieldname="job_air_origin_state"]').val('');
    $('select[data-fieldname="job_air_origin_city"]').val('');
	$('select[data-fieldname="job_air_origin_port_code"]').val('');
	$('select[data-fieldname="job_air_origin_airport_name"]').val('');
	document.getElementById("Job_editView_fieldName_job_air_origin_country_id").value='';
	document.getElementById("Job_editView_fieldName_job_air_origin_city_code").value= '';
	document.getElementById("Job_editView_fieldName_job_air_origin_unlocode").value= '';  

  //Orgin Destination Field disable by default 
	$('select[data-fieldname="job_air_destination_country"]').val('');
	$('select[data-fieldname="job_air_destination_state"]').val('');
	$('select[data-fieldname="job_air_destination_city"]').val('');
	$('select[data-fieldname="job_air_destination_port_code"]').val('');
	document.getElementById("Job_editView_fieldName_job_air_destination_country_id").value='';
	document.getElementById("Job_editView_fieldName_job_air_destination_city_code").value='';
	document.getElementById("Job_editView_fieldName_job_air_destination_unlocode").value='';

}

function fieldsSeaEnable(){
	alert('testtest');
	//Orgin Sea Field disable by default 
	$('select[data-fieldname="job_sea_loading_port_code"]').val('');
	$('select[data-fieldname="job_sea_loading_country"]').val('');
	$('select[data-fieldname="job_sea_loading_state"]').val('');
	$('select[data-fieldname="job_sea_loading_city"]').val('');
	document.getElementById("Job_editView_fieldName_job_sea_loading_country_id").value='';
	document.getElementById("Job_editView_fieldName_job_sea_loading_city_id").value='';
	document.getElementById("Job_editView_fieldName_job_sea_loading_unlocode").value='';
	

	$('select[data-fieldname="job_sea_discharge_country"]').val('');
	$('select[data-fieldname="job_sea_discharge_state"]').val('');
    $('select[data-fieldname="job_sea_discharge_city"]').val('');
	$('select[data-fieldname="job_sea_discharge_port_code"]').val('');
	document.getElementById("Job_editView_fieldName_job_sea_discharge_country_id").value='';
	document.getElementById("Job_editView_fieldName_job_sea_discharge_city_id").value='';
	document.getElementById("Job_editView_fieldName_job_sea_discharge_unlocode").value='';


}