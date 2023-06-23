var $form = $("#prompt-db-form");
var $submit = $("#prompt-db-submit");
var $spinner = $("#prompt-db-spinner");
var $prompt = $("#prompt-db-prompt");
var $results = $("#prompt-db-result");
var $query = $("#prompt-db-generated-query");
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
        let html, query;

        if (response.success) {
          if (response.grid.length) {
            html = response.grid;
            query = response.sql;
          }
        } else {
          html = '<p class="error">' + response.error + "</p>";
          query = "";
        }

        $results.html(html);
        $query.html(query);
      }
    }
  );
});
