$(function(){
    var toggle = first = true;
    $('#q-advance').click(function(){
        var popover = $(this);
        if (first) {
            first = false;
            $.get('/search/tips.html', function(data) {
                popover.attr('data-content', data);
                popover.popover({
                    trigger:'manual',
                    html:true,
                    placement:'below',
                    offset:8
                }).popover('show');
            });
        } else {
            popover.popover(toggle?'show':'hide');
        }
        toggle = !toggle;
        return false;
    });

    var range_load = false;
    $('#custom-time').click(function() {
        if (!range_load) {
            range_load = !range_load;
            $.get('/search/range.php', function(data) {
                $('#range').html(data);
            });
        } else {
            $('#range').toggle();
        }
        return false;
    });
});	
