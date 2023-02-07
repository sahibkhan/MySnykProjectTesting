 include ('../RateCalculator/ratecalculatorfunc.php');

 "https://api.github.com/search/repositories?page=2&q=language:javascript&sort=stars&order=desc";
$(document).ready(function(){
   $('.js-data-example-ajax').select2({
     ajax: {
        url: 'https://api.github.com/search/repositories?term=sel&_type=query',
        dataType: 'json',
        delay: 250,
        processResults: function (data) {
          // Transforms the top-level key of the response object from 'items' to 'results'
          console.log(`data`, data.items)
          return {
            results: $.map(data.items, function(item){
              return { id: item.id, text: item.name }
            })
          };
        },

        data: function (params) {
          var query = {
            q: params.term,
          }

          // Query parameters will be ?search=[term]&type=public
          return query;
        }
        // Additional AJAX parameters go here; see the end of this chapter for the full code of this example
      }
    });
     
});    



