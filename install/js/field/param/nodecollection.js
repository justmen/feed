(function(BX, $, window) {

	var Reference = BX.namespace('LigacomFeed.Field.Reference');
	var Param = BX.namespace('LigacomFeed.Field.Param');
	var Source = BX.namespace('LigacomFeed.Source');

	var constructor = Param.NodeCollection = Param.TagCollection.extend({

		defaults: {
			itemElement: '.js-param-node-collection__item',
			itemAddHolderElement: '.js-param-node-collection__item-add-holder',
			itemAddElement: '.js-param-node-collection__item-add',
			itemDeleteElement: '.js-param-node-collection__item-delete'
		},

		toggleItemDeleteView: function() {
			// nothing
		},

		getItemPlugin: function() {
			return Param.Node;
		}

	}, {
		dataName: 'FieldParamNode'
	});

})(BX, jQuery, window);