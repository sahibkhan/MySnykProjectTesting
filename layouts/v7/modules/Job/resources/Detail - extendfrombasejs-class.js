Vtiger_Detail_Js("Job_Detail_Js",{},{
    
    /**
     * Function to register recordpresave event
     */

    registerShowHideAirSeaBlock : function() {
           var thisInstance = this;
           $("div[data-blockid='904']").hide();
           $("div[data-blockid='903']").hide();
           var mode = jQuery('#Job_detailView_fieldValue_cf_1711 span span').text();
            mode_arr =  ['Air'];
            if (Array.isArray(mode_arr)) {
            
                for(var i = 0; i < mode_arr.length; i++)
                {   
                   if(mode_arr[i] == 'Air'){
                    
                    console.log(mode[i]);
                        // hide BLOCK 
                    $("div[data-blockid='904']").show();    
                        
                    }else if(mode_arr[i] == 'Ocean'){
                        // hide BLOCK       
                    $("div[data-blockid='903']").show();
                    
                    }else if(mode_arr[i] == 'Air/Sea'){
                        $("div[data-blockid='904']").show();  
                        $("div[data-blockid='903']").show();
                    
                    }
                }    
            }
           
        },
        loadSelectedTabContents: function(tabElement, urlAttributes){
            var self = this;
            var detailViewContainer = this.getDetailViewContainer();
            var url = tabElement.data('url');
            self.loadContents(url,urlAttributes).then(function(data){
                self.deSelectAllrelatedTabs();
                self.markRelatedTabAsSelected(tabElement);
                var container = jQuery('.relatedContainer');
                app.event.trigger("post.relatedListLoad.click",container.find(".searchRow"));
                // Added this to register pagination events in related list
                var relatedModuleInstance = self.getRelatedController();
                //Summary tab is clicked
                if(tabElement.data('linkKey') == self.detailViewSummaryTabLabel) {
                    self.registerShowHideAirSeaBlock();
                    self.registerSummaryViewContainerEvents(detailViewContainer);
                    self.registerEventForPicklistDependencySetup(self.getForm());
                }

                //Detail tab is clicked
                if(tabElement.data('linkKey') == self.detailViewDetailTabLabel) {
                    self.registerShowHideAirSeaBlock();
                    self.registerEventForPicklistDependencySetup(self.getForm());
                }

                // Registering engagement events if clicked tab is History
                if(tabElement.data('labelKey') == self.detailViewHistoryTabLabel){
                    var engagementsContainer = jQuery(".engagementsContainer");
                    if(engagementsContainer.length > 0){
                        app.event.trigger("post.engagements.load");
                    }
                }

                relatedModuleInstance.initializePaginationEvents();
                //prevent detail view ajax form submissions
                jQuery('form#detailView').on('submit', function(e) {
                    e.preventDefault();
                });
            });
    },

    /**
     * Function which will register all the events
     */
    registerEvents : function() {

        this._super();
        this.registerShowHideAirSeaBlock();
       
    }
})

