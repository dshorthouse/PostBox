var PostBox = PostBox || { 'settings': {} };

function isValidEmailAddress(emailAddress) {
   var emailPattern = new RegExp(/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$/);
   return emailPattern.test(emailAddress);
}

$(function(){

  if(PostBox.settings.queueID.length > 0 || $('#results').length > 0) {
    $('#results').slideDown();
  }

  for(var i=0 ; i < PostBox.settings.queueID.length; i++) {
    var id = i;
    var queued = true;
    $('#postbox-queue-' + id + ' div.postbox-results').html("Checking...");
    $('#postbox-queue-' + i + ' div.postbox-results').doTimeout('loop', 2000, function() {
        var url = PostBox.settings.baseUrl + '/queue/?q=' + PostBox.settings.queueID[id];
        $.get(url, {}, function(data) {
            var message = data.message;
            if(data.status == 'error' || data.status == 'failed') {
                $('#postbox-queue-' + id + ' div.postbox-throbber').hide();
                $('#results').animate({backgroundColor:'red'}, 1000);
                $('#postbox-queue-' + id + ' div.postbox-results').css({'color':'white', 'paddingLeft':'0px'}).html(message);
                queued = false;
            }
            else if(data.status == 'complete') {
                $('#postbox-queue-' + id + ' div.postbox-throbber').hide();
                $('#postbox-queue-' + id + ' div.postbox-results').css({'paddingLeft':'0px'}).html(message);
                queued = false;
            }
            else {
                var queueUrl = url.replace("/queue", "");
                message += '<br>You may wait here or return to<br><a href="' + queueUrl + '">' + queueUrl + '</a>';
                var queueCount = (data.jobqueue !== undefined) ? data.jobqueue : "1";
                $('#postbox-queue-' + id + ' div.postbox-results').html(message + "<span class=\"queue-status\">Jobs in queue: " + queueCount + "</span>");
            }
        }, "json");
        return queued;
      });
  }

  if($('div.section p.error').length > 0) {
        $('div.section p.error').prev().hide();
        $('#results').animate({backgroundColor:'#FA2525'}, 1000);
  }

  $("#email")
   .blur(function() {
    var message = $("#email_validation");
    var email = $(this).val();
    if(isValidEmailAddress(email)){
      message.html('');
    }
    else {
      message.html('<span class="fail">invalid email address</span>');
    }
 });
    
});