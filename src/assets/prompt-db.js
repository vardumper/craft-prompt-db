var $form = $("#prompt-db-form");
var $submit = $("#prompt-db-submit");
var $spinner = $("#prompt-db-spinner");
var $prompt = $("#prompt-db-prompt");
var $results = $("#prompt-db-result");
var executing = false;

$form.on("submit", function (ev) {
  ev.preventDefault();

  if (executing) {
    return;
  }

  $submit.addClass("active");
  $spinner.removeClass("hidden");
  executing = true;

  Craft.postActionRequest(
    "prompt-db/default/execute",
    { prompt: $prompt.val() },
    function (response, textStatus) {
      $submit.removeClass("active");
      $spinner.addClass("hidden");
      executing = false;

      if (textStatus == "success") {
        var html;

        if (response.success) {
          if (response.result.length) {
            html = response.result;
          }
        } else {
          html = '<p class="error">' + response.error + "</p>";
        }

        $results.html(html);
      }
    }
  );
});
