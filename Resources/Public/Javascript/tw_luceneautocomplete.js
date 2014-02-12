if (jQuery) {
	jQuery(document).ready(function(){
		jQuery('input.tx-twlucenesearch-sword').each(function() {
			jQuery(this).autocomplete('index.php?eID=eidautocomplete', {
		        minChars: 3,   
		        queryParamName: 'tx_twlucenesearch_luceneautocomplete[searchterm]',
		        remoteDataType: 'json'
		    });
		}); 
	});
} 
