Vtiger_Edit_Js("WagonTrip_Edit_Js",{},{

			
	init : function(container) {
		this._super(container);
		jQuery("[name='assigned_user_id']").prop('disabled', true);

		$('input[name="cf_5790"]').prop('readonly', true); // Wagon Ref No

		$('input[name="cf_6494"]').prop('readonly', true); //trip Standard Days
		$('input[name="cf_6496"]').prop('readonly', true); // Standard Distance(km)
		$('input[name="cf_6498"]').prop('readonly', true); // wagon owner rate
		$('input[name="cf_6500"]').prop('readonly', true); // standard indirect cost
		$('input[name="cf_6502"]').prop('readonly', true); // total pre budget
	},

	getWagonRate : function(container){
		var self = this;

		jQuery('[name="cf_6492"], [name="cf_5800"]').on('change', function(){
			var wagon_id = $('[name="cf_5800"]').val();	
			var trip_template_id = $('[name="cf_6492"]').val();
			
				$.post('include/WagonTrip/wagon_owner_rate.php',{ wagon_id: wagon_id,
																  trip_template_id:trip_template_id															 
				},function(data){
	
						var result=JSON.parse(data);
							//$('[name="cf_6498"]').attr("value", result['wagon_owner_rate']);
							$('[name="cf_6498"]').val(result['wagon_owner_rate']);
							$('[name="cf_6494"]').val(result['standard_days']);
							$('[name="cf_6496"]').val(result['standard_distance']);
							$('[name="cf_6500"]').val(result['standard_indirect_cost']);
							$('[name="cf_6502"]').val(result['total_pre_budget']);
	
						});	
		});

	},

	registerBasicEvents: function(container){
        this._super(container);
		this.getWagonRate(container);
	 },

});