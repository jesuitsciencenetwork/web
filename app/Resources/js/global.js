window.scrollTo = function scrollTo(fragment, offset) {
    var offset = offset || 0;
    var scrollPos = $(fragment).offset().top - offset;
    $('body,html').animate({
        scrollTop: scrollPos
    }, 250);
};

$(function () {
    $('a[href^="#"]').click(function (event) {
        event.preventDefault();
        scrollTo($(this).attr('href'));
    });

    $(window).on('hashchange', function(event) {
        event.preventDefault();
        scrollTo(window.location.hash);
    });
    //
    //if (location.hash) {
    //    var $body = $('body');
    //    $body.scroll($body.scrollTop - 70);
    //}

    $('.selectpicker').selectpicker({
        iconBase: 'fa',
        tickIcon: 'fa-check'
    });

    $('.js-show-hiddennames').click(function() {
        $(this).closest('div').addClass('hidden').closest('div.panel-body').find('li.hidden').removeClass('hidden');
    });

    var jsnSearch = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: $('#jsn-search').data('url'),
            wildcard: '%QUERY'
        }
    });

    var jsnPlaceSearch = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        remote: {
            url: $('#jsn-search').data('url-places'),
            wildcard: '%QUERY'
        }
    });

    var subjects = new Bloodhound({
        datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
        queryTokenizer: Bloodhound.tokenizers.whitespace,
        prefetch: '/subjects.json'
    });

    var footer = $('#jsn-search').data('footer');
    $('#jsn-search').typeahead({
        hint: false,
        highlight: true,
        changeInputValue: false
    }, {
        name: 'jsn-search',
        display: 'value',
        source: jsnSearch,
        limit: Infinity,
        templates: {
            header: '<h4 class="tt-header">Persons</h4>',
            suggestion: function(item) {
                return '<div>' + item.value + (item.text ? '<br><small class="text-muted">' + item.text + '</small>' : '') + '</div>';
            }
        }
    },{
        name: 'jsn-place-search',
        display: 'value',
        source: jsnPlaceSearch,
        limit: Infinity,
        templates: {
            header: '<h4 class="tt-header">Places</h4>'
        }
    }, {
        name: 'subject-search',
        display: 'value',
        source: subjects,
        limit: Infinity,
        templates: {
            header: '<h4 class="tt-header">Subjects</h4>'
        }
    })
        .on('typeahead:cursorchange', function(e) {
            e.preventDefault();
            return false;
        })
        .on('typeahead:select', function(e, sel) {
            window.location.href = sel.url;
        });
});
