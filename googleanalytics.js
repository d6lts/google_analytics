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
      if ((ga.trackDownload && isDownload.test(this.href)) || isInternalSpecial.test(this.href)) {
        // Keep the internal URL for Google Analytics website overlay intact.
        pageTracker._trackPageview(this.href.replace(isInternal, ''));
      }
    }
    else {
      if (ga.trackMailto && this.href.indexOf('mailto:') == 0) {
        // Mailto link clicked.
        pageTracker._trackPageview('/mailto/' + this.href.substring(7));
      }
      else if (ga.trackOutgoing) {
        // External link clicked. Clean and track the URL.
        pageTracker._trackPageview('/outgoing/' + this.href.replace(/^(https?|ftp|news|nntp|telnet|irc|ssh|sftp|webcal):\/\//i, '').split('/').join('--'));
      }
    }
  });
}
