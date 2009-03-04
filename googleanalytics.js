// $Id$

Drupal.behaviors.gaTrackerAttach = function(context) {

  // Attach onclick event to all links.
  $('a', context).click( function() {
    var ga = Drupal.settings.googleanalytics;
    // Expression to check for absolute internal links.
    var isInternal = new RegExp("^(https?):\/\/" + window.location.host, "i");
    // Expression to check for special links like gotwo.module /go/* links.
    var isInternalSpecial = new RegExp("(\/go\/.*)$", "i");
    // Expression to check for download links.
    var isDownload = new RegExp("\\.(" + ga.trackDownloadExtensions + ")$", "i");

    // Is the clicked URL internal?
    if (isInternal.test(this.href)) {
      // Is download tracking activated and the file extension configured for download tracking?
      if (ga.trackDownload && isDownload.test(this.href)) {
        // Download link clicked.
        var extension = isDownload.exec(this.href);
        try{
        pageTracker._trackEvent("Downloads", extension[1].toUpperCase(), this.href.replace(isInternal, ''));
        } catch(err) {}
      }
      else if (isInternalSpecial.test(this.href)) {
        // Keep the internal URL for Google Analytics website overlay intact.
        try{
        pageTracker._trackPageview(this.href.replace(isInternal, ''));
        } catch(err) {}
      }
    }
    else {
      if (ga.trackMailto && $(this).is("a[href^=mailto:]")) {
        // Mailto link clicked.
        try{
        pageTracker._trackEvent("Mails", "Click", this.href.substring(7));
        } catch(err) {}
      }
      else if (ga.trackOutgoing) {
        // External link clicked.
        try{
        pageTracker._trackEvent("Outgoing links", "Click", this.href);
        } catch(err) {}
      }
    }
  });
}
