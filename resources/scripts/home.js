$(function () {
  const $form = $("#form");
  const $loader = $("#loader");
  const $submitBtn = $("#submitBtn");
  const $randomBtn = $("#button-random-entry");
  const $results = $("#results");
  const $clearAllBtn = $("#clearAllBtn");

  function setLoading(state) {
    $loader.css("display", state ? "flex" : "none");
    $submitBtn.prop("disabled", state);
    $randomBtn.prop("disabled", state);
  }

  function addResult(html) {
    const $entry = $("<div>", {
      class: "result-entry",
    });

    const $actions = $("<div>", {
      class: "result-actions",
    });

    const $clearBtn = $("<button>", {
      type: "button",
      class: "secondary",
      text: "Clear",
    });

    const $content = $("<div>");

    $clearBtn.on("click", function () {
      $entry.remove();
    });

    $actions.append($clearBtn);
    $entry.append($actions);
    $entry.append($content);

    $results.prepend($entry);

    appendHtmlAndRunScripts($content, html);
  }

  $form.on("submit", function (e) {
    e.preventDefault();
    setLoading(true);

    $.ajax({
      url: "/ajax/create_entry",
      method: "POST",
      data: new FormData(this),
      processData: false,
      contentType: false,
      dataType: "html",
    })
      .done(function (html) {
        addResult(html);
      })
      .fail(function (xhr, status, err) {
        addResult("<pre>" + escapeHtml(String(err || status)) + "</pre>");
      })
      .always(function () {
        setLoading(false);
      });
  });

  $randomBtn.on("click", function () {
    setLoading(true);

    const data = new FormData();
    data.append("text", "");

    $.ajax({
      url: "ajax/create_entry",
      method: "POST",
      data: data,
      processData: false,
      contentType: false,
      dataType: "html",
    })
      .done(function (html) {
        addResult(html);
      })
      .fail(function (xhr, status, err) {
        addResult("<pre>" + escapeHtml(String(err || status)) + "</pre>");
      })
      .always(function () {
        setLoading(false);
      });
  });

  $clearAllBtn.on("click", function () {
    $results.empty();
  });

  function appendHtmlAndRunScripts($container, html) {
    const $tmp = $("<div>").html(html);
    const $scripts = $tmp.find("script").remove();

    $container.empty().append($tmp.contents());

    $scripts.each(function () {
      const oldScript = this;
      const script = document.createElement("script");

      if (oldScript.src) {
        script.src = oldScript.src;
      }

      script.text = oldScript.textContent;
      $container[0].appendChild(script);
    });
  }

  function escapeHtml(s) {
    return s
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#39;");
  }
});
