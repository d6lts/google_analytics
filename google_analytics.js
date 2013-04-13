(function ($) {

$(document).ready(function() {

  // Expression to check for absolute internal links.
  var Google_Analytics.isInternal = new RegExp("^(https?):\/\/" + window.location.host, "i");

  // Attach onclick event to document only and catch clicks on all elements.
  $(document.body).click(function(event) {
    // Catch the closest surrounding link of a clicked element.
    $(event.target).closest("a,area").each(function() {

      var ga = Drupal.settings.google_analytics;
      // Expression to check for special links like gotwo.module /go/* links.
      var Google_Analytics.isInternalSpecial = new RegExp("(\/go\/.*)$", "i");
      // Expression to check for download links.
      var Google_Analytics.isDownload = new RegExp("\\.(" + ga.trackDownloadExtensions + ")$", "i");

      // Is the clicked URL internal?
      if (Google_Analytics.isInternal.test(this.href)) {
        // Skip 'click' tracking, if custom tracking events are bound.
        if ($(this).is('.colorbox')) {
          // Do nothing here. The custom event will handle all tracking.
        }
        // Is download tracking activated and the file extension configured for download tracking?
        else if (ga.trackDownload && Google_Analytics.isDownload.test(this.href)) {
          // Download link clicked.
          var extension = Google_Analytics.isDownload.exec(this.href);
          ga("send", "event", "Downloads", extension[1].toUpperCase(), this.href.replace(Google_Analytics.isInternal, ''));
        }
        else if (Google_Analytics.isInternalSpecial.test(this.href)) {
          // Keep the internal URL for Google Analytics website overlay intact.
          ga("send", "pageview", { page: this.href.replace(Google_Analytics.isInternal, '')});
        }
      }
      else {
        if (ga.trackMailto && $(this).is("a[href^='mailto:'],area[href^='mailto:']")) {
          // Mailto link clicked.
          ga("send", "event", "Mails", "Click", this.href.substring(7));
        }
        else if (ga.trackOutbound && this.href.match(/^\w+:\/\//i)) {
          if (ga.trackDomainMode == 2 && Google_Analytics.isCrossDomain($(this).attr('hostname'), ga.trackCrossDomains)) {
            // Top-level cross domain clicked. document.location is handled by _link internally.
            event.preventDefault();
            // @todo: unknown upgrade path
            //_gaq.push(["_link", this.href]);
            //ga("link", this.href); ???
          }
          else {
            // External link clicked.
            ga("send", "event", "Outbound links", "Click", this.href);
          }
        }
      }
    });
  });

  // Colorbox: This event triggers when the transition has completed and the
  // newly loaded content has been revealed.
  $(document).bind("cbox_complete", function() {
    var href = $.colorbox.element().attr("href");
    if (href) {
      ga("send", "pageview", { page: href.replace(Google_Analytics.isInternal, '') });
    }
  });

});

/**
 * Check whether the hostname is part of the cross domains or not.
 *
 * @param string hostname
 *   The hostname of the clicked URL.
 * @param array crossDomains
 *   All cross domain hostnames as JS array.
 *
 * @return boolean
 */
function Google_Analytics.isCrossDomain(hostname, crossDomains) {
  return $.inArray(hostname, crossDomains) > -1 ? true : false;
}

})(jQuery);
