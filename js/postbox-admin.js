$(function() { 
    $("#tabs").tabs({
        ajaxOptions: {
            error: function(xhr, status, index, anchor) {
                $(anchor.hash).html("Couldn't load this tab. We'll try to fix this as soon as possible.");
            },
            success: function() {
                $(".admin-postbox-message-full").hide();
                $(".admin-postbox-message-more a").click(function() {
                    $(this).closest("td").find(".admin-postbox-message-full").show();
                    $(this).closest(".admin-postbox-message-ellipsis").hide();
                    return false;
                });
            }
        },
    });
});