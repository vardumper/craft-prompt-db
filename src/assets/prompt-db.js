"use strict";

var $form = $("#prompt-db-form");
var $submit = $("#prompt-db-submit");
var $spinner = $("#prompt-db-spinner");
var $prompt = $("#prompt-db-prompt");
var $results = $("#prompt-db-result");
var $query = $("#prompt-db-generated-query").find("code.hljs");
var executing = false;

$form.on("submit", function (ev) {
  ev.preventDefault();

  if (executing) {
    return;
  }

  $submit.addClass("active");
  $spinner.removeClass("hidden");
  $query.parent().parent().attr("hidden", true);
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
        }

        $results.html(html);
        if (query.length) {
          $query.typed({
            strings: [query.replace(/  |\r\n|\n|\r/gm, "")],
            // Optionally use an HTML element to grab strings from (must wrap each string in a <p>)
            stringsElement: null,
            // typing speed
            typeSpeed: 10,
            // time before typing starts
            startDelay: 0,
            // backspacing speed
            backSpeed: 0,
            // time before backspacing
            backDelay: 0,
            // loop
            loop: false,
            // false = infinite
            loopCount: 0,
            // show cursor
            showCursor: false,
            // character for cursor
            cursorChar: "|",
            // attribute to type (null == text)
            attr: null,
            // either html or text
            contentType: "html",
            // call when done callback function
            callback: function () {
              $query.parent().parent().removeAttr("hidden");
            },
            // starting callback function before each string
            preStringTyped: function () {
              $query.parent().parent().removeAttr("hidden");
            },
            //callback for every typed string
            onStringTyped: function () {
              hljs.highlightAll();
            },
            // callback for reset
            resetCallback: function () {},
          });
        }
      }
    }
  );
});

!(function () {
  hljs.highlightAll();
})();
