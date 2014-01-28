if (jQuery) {
	jQuery(document).ready(function(){
		jQuery('input.tx-twlucenesearch-sword').each(function() {
			jQuery(this).autocomplete('index.php?eID=eidautosuggest', {
		        minChars: 3,  
		        queryParamName: 'tx_twlucenesearch_luceneautosuggest[searchterm]',
		        remoteDataType: 'json'
		    });
		}); 
	});
} 
