if (jQuery) {
    (function($){
        $(document).ready(function(){
            $('input.tx-twlucenesearch-sword').each(function() {
                $(this).autocomplete('index.php?eID=eidautocomplete', {
                    minChars: 3,
                    queryParamName: 'tx_twlucenesearch_luceneautocomplete[searchterm]',
                    remoteDataType: 'json'
                });
            });
        });
    })(jQuery);
}
