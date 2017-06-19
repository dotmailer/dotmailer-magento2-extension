require(["jquery", "jquery/ui"], function ($) {
    "use strict";

    var el = $(".ddg-datepicker");

    el.datepicker({dateFormat:"yy-mm-dd"});
    el.addClass("datepicker");
});