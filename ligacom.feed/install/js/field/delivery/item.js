(function(BX, $, window) {

	'use strict';

	var Reference = BX.namespace('LigacomFeed.Field.Reference');
	var Delivery = BX.namespace('LigacomFeed.Field.Delivery');

	var constructor = Delivery.Item = Reference.Base.extend({

		defaults: {
			inputElement: '.js-delivery-item__input',

			lang: {},
			langPrefix: 'LIGACOM_FEED_FIELD_DELIVERY_'
		}

	}, {
		dataName: 'FieldDeliveryItem'
	});

})(BX, jQuery, window);