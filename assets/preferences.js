/*-----------------------------------------------------------------------------
URL Router
-----------------------------------------------------------------------------*/

(function($) {
  $(document).ready(function() {
    var duplicator = $('#router-duplicator');
    duplicator.symphonyDuplicator({
      orderable: true,
      collapsible: true
    });
    duplicator.bind('collapsestop', function(event, item) {
      var instance = $(item);
      instance.find('.header > span:not(:has(i))').append(
        $('<i />').text(instance.find('label:first input').attr('value') + " to " + instance.find('label:nth-child(2) input').attr('value'))
      );
    });
    duplicator.bind('expandstop', function(event, item) {
      $(item).find('.header > span > i').remove();
    });
  });

})(jQuery);

/*---------------------------------------------------------------------------*/