jQuery(function ($) {
  $(document).ready(function() {
    // Initialize the countdown timer
    var expiryDate = $('#countdownTimer').data('expiry');
    var timerInterval = setInterval(function() {
      makeTimer(expiryDate, timerInterval);
    }, 1000);
  });

  function makeTimer(expiry, interval) {
    var endTime = new Date(expiry.replace(/-/g, "/")); // Regex to replace '-' with '/' for better browser compatability
    var now = new Date();
    var timeLeft = (endTime - now) / 1000;

    if (timeLeft <= 0) {
      clearInterval(interval);
      $("#countdownTimer").html("EXPIRED");
      return;
    }

    var days = Math.floor(timeLeft / 86400);
    timeLeft -= days * 86400;
    var hours = Math.floor(timeLeft / 3600) % 24;
    timeLeft -= hours * 3600;
    var minutes = Math.floor(timeLeft / 60) % 60;
    timeLeft -= minutes * 60;
    var seconds = Math.floor(timeLeft % 60);

    var formattedTime = (days > 0 ? days + "d " : "") +
      (hours < 10 ? "0" : "") + hours + "h " +
      (minutes < 10 ? "0" : "") + minutes + "m " +
      (seconds < 10 ? "0" : "") + seconds + "s";
    $("#countdownTimer").html(formattedTime);
  }
})
