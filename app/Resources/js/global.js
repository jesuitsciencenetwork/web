window.scrollTo = function scrollTo(fragment, offset) {
    var $fragment = $(fragment);
    var offset = offset || 0;
    var scrollPos = $fragment.length > 0 ? $fragment.offset().top - offset : 0;
    $('body,html').animate({
        scrollTop: scrollPos
    }, 250);
};

var mapIsReady = false;

window.initMap = function initMap() {
    mapIsReady = true;
    $('body').trigger('map:ready');
};

$(function () {
    var $body = $('body');

    $('a[href^="#"]:not([data-toggle="tab"]):not([href="#"])').click(function (event) {
        event.preventDefault();
        var $this = $(this), fragment = $(this).attr('href'), $fragment = $(fragment);
        if ($this.hasClass('jumphighlight')) {
            $fragment.effect('highlight', 2500);
        }
        scrollTo(fragment, $fragment.hasClass('jumptarget') ? 0 : 70);
        return false;
    });

    $('.js-popover').popover();
    $('.js-popover-html').popover({html: true});

    $('[data-toggle="tooltip"]').tooltip();

    $(window).on('hashchange', function(event) {
        event.preventDefault();
        if (window.location.hash.match(/=/)) {
            // this is a param list, skip scrollTo
            return;
        }
        scrollTo(window.location.hash);
    });

    $('.selectpicker').selectpicker({
        iconBase: 'fa',
        tickIcon: 'fa-check'
    });

    $('.js-show-hiddennames').click(function() {
        $(this).closest('div').addClass('hidden').closest('div.panel-body').find('li.hidden').removeClass('hidden');
    });

    if ($('#jsn-search').length > 0) {
        var jsnSearch = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                url: $body.data('url-autocomplete-persons'),
                wildcard: '%QUERY'
            }
        });

        var jsnPlaceSearch = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            remote: {
                url: $body.data('url-autocomplete-places'),
                wildcard: '%QUERY'
            }
        });

        var subjects = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            prefetch: '/subjects.json'
        });

        var occupations = new Bloodhound({
            datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
            queryTokenizer: Bloodhound.tokenizers.whitespace,
            prefetch: '/occupations.json'
        });

        $('#jsn-search').on('focus', function() {
            $(this).attr('placeholder', 'Person, place, or subject');
        }).on('blur', function() {
            $(this).attr('placeholder', 'Quick search');
        });
    }

    $('.js-form-nosubmit').submit(function(e) {
        e.preventDefault();
        return false;
    });

    $body.on('click', 'table.click-to-select tr', function (e) {
        if ($(e.target).is('input, a')) {
            return;
        }
        $(this).find('input').trigger('click');
    });

    $('#apply-sources').on('click', function() {
        var $btn = $(this), $form = $btn.closest('.modal').find('form');
        $form.submit();
    });

    $('input.collector').each(function() {
        var $coll = $(this), $form = $coll.closest('form');
        $form.find('input[type="checkbox"]').prop('checked', false);
        $.each($coll.val().split(" "), function(k, val) {
            $('input[type="checkbox"][value="'+val+'"]').prop('checked', true);
        });
    });

    $('.click-to-select input[type="checkbox"]').on('change', function () {
        var $this = $(this), $form = $this.closest('form');
        $form.find('input.collector').val(
            $form.find('input[type="checkbox"]:checked').map(function() {return $(this).val(); }).toArray().join(' ')
        );
    });

    if ($('.searchbox').length) {
        var initWhereSearch = function() {
            console.log('in map:ready');
            var service = new google.maps.places.AutocompleteService();
            var geocoder = new google.maps.Geocoder();

            var regions = new Bloodhound({
                datumTokenizer: Bloodhound.tokenizers.obj.whitespace('value'),
                queryTokenizer: Bloodhound.tokenizers.whitespace,
                prefetch: '/regions.json'
            });

            $('#where-search').typeahead({
                    hint: false,
                    highlight: true,
                    changeInputValue: false
                },
                {
                    source: regions,
                    templates: {
                        header: '<h4 class="tt-header">Regions</h4>',
                        suggestion: function(item) {
                            return '<div>' + item.value + (item.text ? '<br><small class="text-muted">' + item.text + '</small>' : '') + '</div>';
                        }
                    }
                },
                {
                    templates: {
                        header: '<h4 class="tt-header">Cities</h4>',
                        suggestion: function(prediction) {
                            var i = prediction.terms[1].offset;
                            return '<div>' + prediction.description.slice(0, i-2) + '<br><small class="text-muted">' + prediction.description.slice(i) + '</small></div>';
                        }
                    },
                    display: 'value',
                    source: function(query, syncResults, asyncResults) {
                        syncResults([]);
                        service.getPlacePredictions({ input: query, types: ['(cities)'] }, function(predictions, status) {
                            if (status == google.maps.places.PlacesServiceStatus.OK) {
                                asyncResults(predictions);
                            }
                        });
                    }
                });

            $('#where-search')
                .on('typeahead:cursorchange', function(e) {
                    e.preventDefault();
                    return false;
                })
                .on("typeahead:select", function (e, item) {
                    if (item.url) {
                        // region item
                        window.location.href = item.url;
                    } else {
                        // google item
                        geocoder.geocode({'placeId': item.place_id}, function(results, status) {
                            if (status === google.maps.GeocoderStatus.OK) {
                                if (results[0]) {
                                    var loc = results[0].geometry.location;
                                    console.log(loc);
                                    $('#where-lat').val(loc.lat);
                                    $('#where-lng').val(loc.lng);
                                    $('#where-descr').val(item.description);
                                    $('#where-selection').html(item.description);
                                    $('#where-button').prop('disabled', false).removeClass('disabled');
                                    $('#where-box').removeClass('hidden');
                                    $('#where-search').val('');
                                    $('#where-radius').focus();
                                } else {
                                    window.alert('No results found');
                                }
                            } else {
                                window.alert('Geocoder failed due to: ' + status);
                            }
                        });
                    }
                })
//                    .bind("typeahead:cursorchanged", addressPicker.updateMap)
            ;
        };
        if (mapIsReady) {
            initWhereSearch();
        } else {
            $body.one('map:ready', initWhereSearch);
        }
        $('.js-tree').each(function() {
            var $this = $(this);

            var updateSelection = function(e, n) {
                if (n.nodes) {
                    $.each(n.nodes, function(i, node) {
                        $this.treeview(n.state.checked ? 'selectNode' : 'unselectNode', [node.nodeId, {silent: false}]);
                    });
                    return;
                }

                $this.treeview(n.state.checked ? 'selectNode' : 'unselectNode', [n.nodeId, {silent: true}]);
                var selection = $this.treeview('getChecked');

                if (selection.length) {
                    $('#what-button').removeClass('disabled').prop('disabled', false);
                    $('#what-selection').val($.map(selection, function(n) {
                        return n.id;
                    }).join(" "));
                } else {
                    $('#what-button').addClass('disabled').prop('disabled', true);
                    $('#what-selection').val('');
                }
            };

            var checkRow = function (e, n) {
                $this.treeview(n.state.selected ? 'checkNode' : 'uncheckNode', [n.nodeId, {silent: false}]);
                return false;
            };

            $this.treeview({
                data: $this.data('tree'),
                multiSelect: true,
                collapseIcon: 'fa fa-minus',
                expandIcon: 'fa fa-plus',
                checkedIcon: 'fa fa-check-square-o',
                uncheckedIcon: 'fa fa-square-o',
                levels: 0,
                showCheckbox: true,
                onNodeChecked: updateSelection,
                onNodeUnchecked: updateSelection,
                onNodeSelected: checkRow,
                onNodeUnselected: checkRow
            });
        });

    }

    $('.js-autocomplete').each(function() {
        var $this = $(this), quickSearch = $this.hasClass('js-quicksearch'),
            taArgs = [{
                hint: false,
                highlight: true,
                changeInputValue: false
            }, {
                name: 'jsn-search',
                display: 'value',
                source: jsnSearch,
                limit: Infinity,
                templates: {
                    header: quickSearch ? '<h4 class="tt-header">Persons</h4>' : null,
                    suggestion: function(item) {
                        return '<div>' + item.value + (item.text ? '<br><small class="text-muted">' + item.text + '</small>' : '') + '</div>';
                    },
                    empty: [
                        quickSearch ? '<h4 class="tt-header">Persons</h4>' : '',
                        '<div class="text-center empty-message">',
                        '<em class="text-more-muted">No entries matching your query</em>',
                        '</div>'
                    ].join('\n')
                }
            }];

        if (quickSearch) {
            taArgs.push({
                name: 'jsn-place-search',
                display: 'value',
                source: jsnPlaceSearch,
                limit: Infinity,
                templates: {
                    header: '<h4 class="tt-header">Places</h4>',
                    empty: [
                        '<h4 class="tt-header">Places</h4>',
                        '<div class="text-center empty-message">',
                        '<em class="text-more-muted">No entries matching your query</em>',
                        '</div>'
                    ].join('\n')
                }
            });
            taArgs.push({
                name: 'subject-search',
                display: 'value',
                source: subjects,
                limit: Infinity,
                templates: {
                    header: '<h4 class="tt-header">Subjects</h4>',
                    empty: [
                        '<h4 class="tt-header">Subjects</h4>',
                        '<div class="text-center empty-message">',
                        '<em class="text-more-muted">No entries matching your query</em>',
                        '</div>'
                    ].join('\n')
                }
            });
            taArgs.push({
                name: 'occupation-search',
                display: 'value',
                source: occupations,
                limit: Infinity,
                templates: {
                    header: '<h4 class="tt-header">Occupations</h4>',
                    empty: [
                        '<h4 class="tt-header">Occupations</h4>',
                        '<div class="text-center empty-message">',
                        '<em class="text-more-muted">No entries matching your query</em>',
                        '</div>'
                    ].join('\n')
                }
            });
        }

        $this
            .typeahead
            .apply($this, taArgs)
            .on('typeahead:cursorchange', function(e) {
                e.preventDefault();
                return false;
            })
            .on('typeahead:select', function(e, sel) {
                window.location.href = sel.url;
            })
        ;

        var asyncActive = 0, grp = $this.closest('.form-group');
        $this
            .on('typeahead:asyncrequest', function(e, q, ds) {
                asyncActive += 1;
                grp.toggleClass('loading', asyncActive > 0);
            })
            .on('typeahead:asynccancel typeahead:asyncreceive', function() {
                asyncActive -= 1;
                grp.toggleClass('loading', asyncActive > 0);
            })
        ;
    });

    if ($('.when-slider').length) {
        var slider = $('.when-slider').slider({
            tooltip: 'hide',

        }).on('slide', function() {
            var val = slider.getValue();
            $('#when-from').val(val[0]);
            $('#when-to').val(val[1]);
        }).data('slider');

        $('#when-from, #when-to').on('change', function () {
            var from = parseInt($('#when-from').val(), 10),
                to = parseInt($('#when-to').val(), 10);

            if (from < 1490) {
                from = 1490;
                $('#when-from').val(1490);
            }

            if (to > 1870) {
                to = 1870;
                $('#when-to').val(1870);
            }
            slider.setValue([from, to])
        });

        $('.js-apply-daterange').click(function() {
            var $this = $(this),
                params = $this.data('params');

            params.from = $('#when-from').val();
            params.to   = $('#when-to').val();

            window.location.href = $this.data('url') + '?' + $.param(params);
        });
    }


});
