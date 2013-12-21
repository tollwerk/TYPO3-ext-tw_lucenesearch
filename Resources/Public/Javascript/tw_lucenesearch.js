if (jQuery) {
	jQuery(document).ready(function(){
		jQuery('input.tx-twlucenesearch-sword').each(function() {
			this._default		= '' + jQuery.trim(this.title);
			this.title			= '';
			this._onfocus		= function(){
				if (this._default.length && (this.value == this._default)) {
					this.value	= '';
					jQuery(this).removeClass('default');
				}
			};
			this._onblur		= function() {
				if (this._default.length && !jQuery.trim(this.value).length) {
					this.value	= this._default;
					jQuery(this).addClass('default');
				}
			};
			this._onblur();
			jQuery(this.form).submit(jQuery.proxy(this._onfocus, this));
		}).focus(function(){ this._onfocus(); }).blur(function(){ this._onblur(); });
	});
}