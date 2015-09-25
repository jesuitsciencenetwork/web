$(function () {
    $(window).scroll(function () {
        var y = $(window).scrollTop();
        if (y > 0) {
            $("#top-shadow").css({'display': 'block', 'opacity': Math.min(1, y / 40)});
        } else {
            $("#top-shadow").css({'display': 'none'});
        }
    });
});
