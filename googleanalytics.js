// $Id$

Drupal.gaTrackerAttach = function(context) {
  context = context || document;

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
        if (ga.LegacyVersion) {
          try{
          urchinTracker(this.href.replace(isInternal, ''));
          } catch(err) {}
        }
        else {
          // Download link clicked.
          var extension = isDownload.exec(this.href);
          try{
          pageTracker._trackEvent("Downloads", extension[1].toUpperCase(), this.href.replace(isInternal, ''));
          } catch(err) {}
        }
      }
      else if (isInternalSpecial.test(this.href)) {
        // Keep the internal URL for Google Analytics website overlay intact.
        if (ga.LegacyVersion) {
          try{
          urchinTracker(this.href.replace(isInternal, ''));
          } catch(err) {}
        }
        else {
          try{
          pageTracker._trackPageview(this.href.replace(isInternal, ''));
          } catch(err) {}
        }
      }
    }
    else {
      if (ga.trackMailto && $(this).is("a[@href^=mailto:]")) {
        // Mailto link clicked.
        if (ga.LegacyVersion) {
          try{
          urchinTracker('/mailto/' + this.href.substring(7));
          } catch(err) {}
        }
        else {
          try{
          pageTracker._trackEvent("Mails", "Click", this.href.substring(7));
          } catch(err) {}
        }
      }
      else if (ga.trackOutgoing) {
        // External link clicked. Clean and track the URL.
        if (ga.LegacyVersion) {
          try{
          urchinTracker('/outgoing/' + this.href.replace(/^(https?|ftp|news|nntp|telnet|irc|ssh|sftp|webcal):\/\//i, '').split('/').join('--'));
          } catch(err) {}
        }
        else {
          try{
          pageTracker._trackEvent("Outgoing links", "Click", this.href);
          } catch(err) {}
        }
      }
    }
  });
};

if (Drupal.jsEnabled) {
  $(document).ready(Drupal.gaTrackerAttach);
}
