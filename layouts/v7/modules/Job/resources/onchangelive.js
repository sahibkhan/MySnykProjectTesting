//added onchange funtion by sahib khan on 22
 
 $(document).ready(function() {
   alert('testing');
    // on change of your SELECT ELEMENT
    $( document ).on( 'change', 'select[id="s2id_Job_Edit_fieldName_cf_1711"]', function( e ) {
    
        // get the value of your target SELECT ELEMENT
        selectElement = $('select[data-fieldname="s2id_Job_Edit_fieldName_cf_1711"]').val();

        // uncomment this for to see SELECT ELEMENT value
        alert('Select element value: ' + selectElement);

    

    });
    
    

}); 





