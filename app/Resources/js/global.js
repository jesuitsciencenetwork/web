window.scrollTo = function scrollTo(fragment, offset) {
    var $fragment = $(fragment);
    var offset = offset || 0;
    var scrollPos = $fragment.length > 0 ? $fragment.offset().top - offset : 0;
    $('body,html').animate({
        scrollTop: scrollPos
    }, 250);
};

window.initMap = function initMap() {
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

    $('[data-toggle="tooltip"]').tooltip();

    $(window).on('hashchange', function(event) {
        event.preventDefault();
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
            $(this).attr('placeholder', 'Quick Search');
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
                    }
                }
            }];

        if (quickSearch) {
            taArgs.push({
                name: 'jsn-place-search',
                display: 'value',
                source: jsnPlaceSearch,
                limit: Infinity,
                templates: {
                    header: '<h4 class="tt-header">Places</h4>'
                }
            });
            taArgs.push({
                name: 'subject-search',
                display: 'value',
                source: subjects,
                limit: Infinity,
                templates: {
                    header: '<h4 class="tt-header">Subjects</h4>'
                }
            });
            taArgs.push({
                name: 'occupation-search',
                display: 'value',
                source: occupations,
                limit: Infinity,
                templates: {
                    header: '<h4 class="tt-header">Occupations</h4>'
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
            });
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

            if (to > 1890) {
                to = 1890;
                $('#when-to').val(1890);
            }
            slider.setValue([from, to])
        });
    }

    if ($('.searchbox').length) {
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

        $body.one('map:ready', function() {
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
        });
    }


});
