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
            var resultLabel =
              response.resultCount === 1
                ? Craft.t("query", "1 result:")
                : Craft.t("query", "{num} results:", {
                    num: response.formattedTotal,
                  });

            html =
              "<p>" +
              resultLabel +
              "</p>" +
              '<table class="data fullwidth">' +
              "<thead>" +
              "<tr>";

            for (var key in response.result[0]) {
              html += '<th scope="col">' + key + "</th>";
            }

            html += "</tr>" + "</thead>" + "</tbody>";

            for (var i = 0; i < response.result.length; i++) {
              html += "<tr>";
              for (var cell in response.result[i]) {
                html += '<td style="vertical-align: top"><pre>';

                if (response.result[i][cell] === null) {
                  html += '<span class="extralight">NULL</span>';
                } else {
                  html += response.result[i][cell];
                }

                html += "</pre></td>";
              }
              html += "</tr>";
            }

            html += "<tbody>" + "</table>";
          }
        } else {
          html = '<p class="error">' + response.error + "</p>";
        }

        $results.html(html);
      }
    }
  );
});
