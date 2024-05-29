'use strict';

if (document.getElementById('mailmagik_form')) {
    document.getElementById("mailmagik_form").submit();
}

$(function () {
    if (window.location.href.search('settings/email') == -1) {
        return;
    }

    const parseVia = $('input[name=mailmagik_parse_via]');

    function updateParseVia() {
        if (parseVia.prop('checked')) {
            $('div#parse-to').show();
            $('div#parse-subject').hide();
        } else {
            $('div#parse-to').hide();
            $('div#parse-subject').show();
        }
    }

    updateParseVia();
    parseVia.change(function() {
        updateParseVia();
    });

    const parsingEnable = $('input[name=mailmagik_parsing_enable]');
    const parsingRemovadata = $('input[name=mailmagik_parsing_remove_data]');

    function updateParsing() {
        parsingRemovadata.prop('disabled', !parsingEnable[1].checked);
    }

    updateParsing();
    parsingEnable.change(function() {
        updateParsing();
    });

})
