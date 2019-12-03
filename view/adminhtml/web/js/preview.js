require(['jquery', 'domReady!'], function ($j) {
    'use strict';

    /**
     * Apply styles
     * @param {String} styles
     * @param {Object} element
     */
    function applyStyle(styles, element) {
        element.css({
            'font-weight': 'normal', 'font-style': 'normal', 'text-decoration': 'none'
        });
        $j.each(styles, function (index, style) {
            switch (style) {
                case 'nostyle':
                    element.css({
                        'font-weight': 'normal', 'font-style': 'normal', 'text-decoration': 'none'
                    });
                    break;

                case 'bold':
                    element.css('font-weight', 'bold');
                    break;

                case 'italic':
                    element.css('font-style', 'italic');
                    break;

                case 'underline':
                    element.css('text-decoration', 'underline');
                    break;
            }
        });
    }

    /**
     * Filter styles
     * @param {String} id
     * @param {String} value
     */
    function updateStyle(id, value) {
        var link = $j('#' + 'connector_configuration_dynamic_content_style_link-preview'),
            name = $j('#' + 'connector_configuration_dynamic_content_style_name-preview'),
            doc = $j('#' + 'connector_configuration_dynamic_content_style_doc-preview'),
            price = $j('#' + 'connector_configuration_dynamic_content_style_price-preview'),
            coupon = $j('#' + 'connector_configuration_dynamic_content_style_coupon-preview');

        switch (id) {
            case 'connector_configuration_dynamic_content_style_font_color':
                name.css('color', value);
                break;

            case 'connector_configuration_dynamic_content_style_font_size':
                name.css('font-size', value);
                break;

            case 'connector_configuration_dynamic_content_style_font_style':
                applyStyle(value, name);
                break;

            case 'connector_configuration_dynamic_content_style_price_color':
                price.css('color', value);
                break;

            case 'connector_configuration_dynamic_content_style_price_font_size':
                price.css('font-size', value);
                break;

            case 'connector_configuration_dynamic_content_style_price_font_style':
                applyStyle(value, price);
                break;

            case 'connector_configuration_dynamic_content_style_product_link_color':
                link.css('color', value);
                break;

            case 'connector_configuration_dynamic_content_style_product_link_font_size':
                link.css('font-size', value);
                break;

            case 'connector_configuration_dynamic_content_style_product_link_style':
                applyStyle(value, link);
                break;

            case 'connector_configuration_dynamic_content_style_font':
                doc.css('font-family', value);
                break;

            case 'connector_configuration_dynamic_content_style_color':
                doc.css('background-color', value);
                break;

            case 'connector_configuration_dynamic_content_style_coupon_font_color':
                coupon.css('color', value);
                break;

            case 'connector_configuration_dynamic_content_style_coupon_font_size':
                coupon.css('font-size', value);
                break;

            case 'connector_configuration_dynamic_content_style_coupon_font_picker':
                coupon.css('font-family', value);
                break;

            case 'connector_configuration_dynamic_content_style_coupon_background_color':
                coupon.css('background-color', value);
                break;

            case 'connector_configuration_dynamic_content_style_coupon_font_style':
                applyStyle(value, coupon);
                break;
        }
    }

    /**
     * Initial function
     */
    function init() {
        var s = $j('#ddg-edc-preview'),
            pos = s.position(),
            elementsA = [
                $j('#connector_configuration_dynamic_content_style_font_color'),
                $j('#connector_configuration_dynamic_content_style_font_size'),
                $j('#connector_configuration_dynamic_content_style_price_color'),
                $j('#connector_configuration_dynamic_content_style_price_font_size'),
                $j('#connector_configuration_dynamic_content_style_product_link_color'),
                $j('#connector_configuration_dynamic_content_style_product_link_font_size'),
                $j('#connector_configuration_dynamic_content_style_font'),
                $j('#connector_configuration_dynamic_content_style_color'),
                $j('#connector_configuration_dynamic_content_style_coupon_font_color'),
                $j('#connector_configuration_dynamic_content_style_coupon_font_size'),
                $j('#connector_configuration_dynamic_content_style_coupon_font_picker'),
                $j('#connector_configuration_dynamic_content_style_coupon_background_color')
            ],
            elementsB = [
                $j('#connector_configuration_dynamic_content_style_font_style'),
                $j('#connector_configuration_dynamic_content_style_price_font_style'),
                $j('#connector_configuration_dynamic_content_style_product_link_style'),
                $j('#connector_configuration_dynamic_content_style_coupon_font_style')
            ];

        $j(window).scroll(function () {
            var windowpos = $j(window).scrollTop();

            if (windowpos >= pos.top &&
                windowpos <
                $j('#connector_configuration_dynamic_content_style_coupon_font_style').position().top - 350) {
                s.attr('style', ''); //kill absolute positioning
                s.css({
                    position: 'fixed', top: '80px', left: $j(document).innerWidth() - 250
                });
            } else if (windowpos >=
                $j('#connector_configuration_dynamic_content_style_coupon_font_style').position().top - 350) {
                s.css({
                    position: 'absolute',
                    top:
                    $j('#connector_configuration_dynamic_content_style_coupon_font_style').position().top - 350 + 'px',
                    left: $j(document).innerWidth() - 250
                });
            } else {
                s.css({
                    position: 'absolute',
                    top: pos.top + 'px',
                    left: $j(document).innerWidth() - 250
                });
            }
        });

        $j(window).resize(function () {
            $j('#ddg-edc-preview').css({
                left: $j(document).innerWidth() - 250
            });
        });

        $j.each(elementsA, function (index, element) {
            var id = element.attr('id'),
                value = element.val();

            updateStyle(id, value);
            $j(element).on('change keyup blur input', function () {
                id = element.attr('id');
                value = element.val();
                updateStyle(id, value);
            });
        });
        $j.each(elementsB, function (index, element) {
            var id = element.attr('id'),
                value = element.val();

            updateStyle(id, value);
            $j(element).on('change', function () {
                id = element.attr('id');
                value = element.val();
                updateStyle(id, value);
            });
        });
    }

    //initialise
    init();
});
