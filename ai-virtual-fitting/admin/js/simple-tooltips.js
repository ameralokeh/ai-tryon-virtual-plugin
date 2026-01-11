/**
 * Simple Tooltips for AI Virtual Fitting
 * Basic, reliable tooltip implementation
 */

jQuery(document).ready(function($) {
    // Create tooltip container
    if ($('#simple-tooltip').length === 0) {
        $('body').append('<div id="simple-tooltip" style="position: absolute; background: #333; color: white; padding: 8px 12px; border-radius: 4px; font-size: 12px; z-index: 999999; display: none; max-width: 300px; word-wrap: break-word;"></div>');
    }
    
    var tooltip = $('#simple-tooltip');
    
    // Handle tooltip hover events
    $(document).on('mouseenter', '.help-tooltip', function(e) {
        var text = $(this).attr('title') || $(this).data('tooltip');
        if (text) {
            tooltip.html(text).show();
            positionTooltip(e, tooltip);
        }
    });
    
    $(document).on('mouseleave', '.help-tooltip', function() {
        tooltip.hide();
    });
    
    $(document).on('mousemove', '.help-tooltip', function(e) {
        positionTooltip(e, tooltip);
    });
    
    function positionTooltip(e, tooltip) {
        var x = e.pageX + 10;
        var y = e.pageY - 30;
        
        // Keep tooltip on screen
        if (x + tooltip.outerWidth() > $(window).width()) {
            x = e.pageX - tooltip.outerWidth() - 10;
        }
        if (y < $(window).scrollTop()) {
            y = e.pageY + 20;
        }
        
        tooltip.css({
            left: x + 'px',
            top: y + 'px'
        });
    }
});