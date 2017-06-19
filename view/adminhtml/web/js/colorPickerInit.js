require(['jquery', 'domReady!'], function($){
    "use strict";

    $( ".ddg-colpicker" ).colpick({
        onChange:function(hsb,hex,rgb,el)
        {
            $(el).val('#'+hex);
        }
    });
});