
(function() {
	var DOM = tinymce.DOM;

	tinymce.create('tinymce.plugins.UBPFEditor', {

		init : function(ed, url) {
			var t = this;
			
			ed.onInit.add(function(ed) {
				// remove invalid parent paragraphs when pasting HTML and/or switching to the HTML editor and back
				ed.onBeforeSetContent.add(function(ed, o) {
					if ( o.content ) {
						o.content = o.content.replace(/<p>\s*<(p|div|ul|ol|dl|table|blockquote|h[1-6]|fieldset|pre|address)( [^>]*)?>/gi, '<$1$2>');
						o.content = o.content.replace(/<\/(p|div|ul|ol|dl|table|blockquote|h[1-6]|fieldset|pre|address)>\s*<\/p>/gi, '</$1>');
					}
				});
			});

			// keep empty paragraphs :(
			ed.onSaveContent.addToTop(function(ed, o) {
				o.content = o.content.replace(/<p>(<br ?\/?>|\u00a0|\uFEFF)?<\/p>/g, '<p>&nbsp;</p>');
			});

			ed.onSaveContent.add(function(ed, o) {
				if ( typeof(switchEditors) == 'object' ) {
					if ( ed.isHidden() )
						o.content = o.element.value;
					else
						o.content = switchEditors.pre_wpautop(o.content);
				}
			});
		},
	});

	// Register plugin
	tinymce.PluginManager.add('ubpfeditor', tinymce.plugins.UBPFEditor);
})();
