$(function () {
  const $form = $("#form");
  const $loader = $("#loader");
  const $submitBtn = $("#submitBtn");
  const $randomBtn = $("#button-random-entry");
  const $results = $("#results");
  const $clearAllBtn = $("#clearAllBtn");
  const $highlightLevel = $("#highlightLevel");
  const $highlightLevelValue = $("#highlightLevelValue");

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

    return {
      entry: $entry,
      content: $content,
    };
  }

  $form.on("submit", function (e) {
    e.preventDefault();

    createEntry(new FormData(this));
  });

  $randomBtn.on("click", function () {
    const data = new FormData();

    data.append("text", "");
    data.append("detail_level", $("#highlightLevel").val());
    data.append("task", $("#task").val());
    data.append("type", $("#type").val());
    data.append("reinject_text", $("#reinjectText").is(":checked") ? "1" : "0");

    if (!$("#type").val()) {
      return;
    }

    createEntry(data);
  });

  $highlightLevel.on("input", function () {
    $highlightLevelValue.text($(this).val());
  });

  $clearAllBtn.on("click", function () {
    $results.empty();
  });

  const $type = $("#type");
  const $reinjectText = $("#reinjectText");

  function updateReinjectAvailability() {
    const value = $type.val();

    const allowed = !["cleaned", "stop-list"].includes(value);

    $reinjectText.prop("disabled", !allowed);

    if (!allowed) {
      $reinjectText.prop("checked", false);
    }
  }

  $type.on("change", updateReinjectAvailability);

  updateReinjectAvailability();

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

  function createEntry(formData) {
    setLoading(true);

    $.ajax({
      url: "/ajax/create_entry",
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
    })
      .done(function (response) {
        const loadingResult = addResult(`
        <div class="task-loading">
          <div class="spinner"></div>
        </div>
      `);

        if (response.success && response.task_id) {
          pollTask(response.task_id, loadingResult.content);
        } else {
          loadingResult.content.html(
            "<pre>" +
              escapeHtml(response.error || "Task creation failed") +
              "</pre>",
          );
        }
      })
      .fail(function (xhr, status, err) {
        addResult("<pre>" + escapeHtml(String(err || status)) + "</pre>");
      })
      .always(function () {
        setLoading(false);
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

  function pollTask(taskId, $container) {
    const interval = setInterval(function () {
      $.ajax({
        url: "/ajax/task_status",
        method: "GET",
        data: {
          task_id: taskId,
        },
        dataType: "json",
      })
        .done(function (response) {
          if (!response.success) {
            clearInterval(interval);

            $container.html(
              "<pre>" +
                escapeHtml(response.error || "Task check failed") +
                "</pre>",
            );

            return;
          }

          if (response.status === "done") {
            clearInterval(interval);

            appendHtmlAndRunScripts(
              $container,
              response.html || "<pre>Task done</pre>",
            );
          }

          if (response.status === "failed") {
            clearInterval(interval);

            $container.html(
              "<pre>" + escapeHtml(response.error || "Task failed") + "</pre>",
            );
          }
        })
        .fail(function (xhr, status, err) {
          clearInterval(interval);

          $container.html(
            "<pre>" + escapeHtml(String(err || status)) + "</pre>",
          );
        });
    }, 2000);
  }
});
