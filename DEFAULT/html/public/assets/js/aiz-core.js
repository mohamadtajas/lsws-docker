($.fn.toggleAttr = function (a, t, l) {
    return this.each(function () {
        var i = $(this);
        i.attr(a) == t ? i.attr(a, l) : i.attr(a, t);
    });
}),
    (function ($) {
        "use strict";
        (AIZ.data = {
            csrf: $('meta[name="csrf-token"]').attr("content"),
            appUrl: $('meta[name="app-url"]').attr("content"),
            fileBaseUrl: $('meta[name="file-base-url"]').attr("content"),
        }),
            (AIZ.uploader = {
                data: {
                    selectedFiles: [],
                    selectedFilesObject: [],
                    clickedForDelete: null,
                    allFiles: [],
                    multiple: !1,
                    type: "all",
                    next_page_url: null,
                    prev_page_url: null,
                },
                removeInputValue: function (a, t, l) {
                    var i = t.filter(function (t) {
                        return t !== a;
                    });
                    i.length > 0
                        ? $(l)
                              .find(".file-amount")
                              .html(AIZ.uploader.updateFileHtml(i))
                        : l.find(".file-amount").html(AIZ.local.choose_file),
                        $(l).find(".selected-files").val(i);
                },
                removeAttachment: function () {
                    $(document).on("click", ".remove-attachment", function () {
                        var a = $(this)
                                .closest(".file-preview-item")
                                .data("id"),
                            t = $(this)
                                .closest(".file-preview")
                                .prev('[data-toggle="aizuploader"]')
                                .find(".selected-files")
                                .val()
                                .split(",")
                                .map(Number);
                        AIZ.uploader.removeInputValue(
                            a,
                            t,
                            $(this)
                                .closest(".file-preview")
                                .prev('[data-toggle="aizuploader"]')
                        ),
                            $(this).closest(".file-preview-item").remove();
                    });
                },
                deleteUploaderFile: function () {
                    $(".aiz-uploader-delete").each(function () {
                        $(this).on("click", function (a) {
                            a.preventDefault();
                            var t = $(this).data("id");
                            (AIZ.uploader.data.clickedForDelete = t),
                                $("#aizUploaderDelete").modal("show"),
                                $(".aiz-uploader-confirmed-delete").on(
                                    "click",
                                    function (a) {
                                        if (
                                            (a.preventDefault(), 1 === a.detail)
                                        ) {
                                            var t =
                                                AIZ.uploader.data.allFiles[
                                                    AIZ.uploader.data.allFiles.findIndex(
                                                        (a) =>
                                                            a.id ===
                                                            AIZ.uploader.data
                                                                .clickedForDelete
                                                    )
                                                ];
                                            $.ajax({
                                                url:
                                                    AIZ.data.appUrl +
                                                    "/aiz-uploader/destroy/" +
                                                    AIZ.uploader.data
                                                        .clickedForDelete,
                                                type: "DELETE",
                                                dataType: "JSON",
                                                data: {
                                                    id: AIZ.uploader.data
                                                        .clickedForDelete,
                                                    _method: "DELETE",
                                                    _token: AIZ.data.csrf,
                                                },
                                                success: function () {
                                                    (AIZ.uploader.data.selectedFiles =
                                                        AIZ.uploader.data.selectedFiles.filter(
                                                            function (a) {
                                                                return (
                                                                    a !==
                                                                    AIZ.uploader
                                                                        .data
                                                                        .clickedForDelete
                                                                );
                                                            }
                                                        )),
                                                        (AIZ.uploader.data.selectedFilesObject =
                                                            AIZ.uploader.data.selectedFilesObject.filter(
                                                                function (a) {
                                                                    return (
                                                                        a !== t
                                                                    );
                                                                }
                                                            )),
                                                        AIZ.uploader.updateUploaderSelected(),
                                                        AIZ.uploader.getAllUploads(
                                                            AIZ.data.appUrl +
                                                                "/aiz-uploader/get-uploaded-files"
                                                        ),
                                                        (AIZ.uploader.data.clickedForDelete =
                                                            null),
                                                        $(
                                                            "#aizUploaderDelete"
                                                        ).modal("hide");
                                                },
                                            });
                                        }
                                    }
                                );
                        });
                    });
                },
                uploadSelect: function () {
                    $(".aiz-uploader-select").each(function () {
                        var a = $(this);
                        a.on("click", function (t) {
                            var l = $(this).data("value"),
                                i =
                                    AIZ.uploader.data.allFiles[
                                        AIZ.uploader.data.allFiles.findIndex(
                                            (a) => a.id === l
                                        )
                                    ];
                            a
                                .closest(".aiz-file-box-wrap")
                                .toggleAttr("data-selected", "true", "false"),
                                AIZ.uploader.data.multiple ||
                                    a
                                        .closest(".aiz-file-box-wrap")
                                        .siblings()
                                        .attr("data-selected", "false"),
                                AIZ.uploader.data.selectedFiles.includes(l)
                                    ? ((AIZ.uploader.data.selectedFiles =
                                          AIZ.uploader.data.selectedFiles.filter(
                                              function (a) {
                                                  return a !== l;
                                              }
                                          )),
                                      (AIZ.uploader.data.selectedFilesObject =
                                          AIZ.uploader.data.selectedFilesObject.filter(
                                              function (a) {
                                                  return a !== i;
                                              }
                                          )))
                                    : (AIZ.uploader.data.multiple ||
                                          ((AIZ.uploader.data.selectedFiles =
                                              []),
                                          (AIZ.uploader.data.selectedFilesObject =
                                              [])),
                                      AIZ.uploader.data.selectedFiles.push(l),
                                      AIZ.uploader.data.selectedFilesObject.push(
                                          i
                                      )),
                                AIZ.uploader.addSelectedValue(),
                                AIZ.uploader.updateUploaderSelected();
                        });
                    });
                },
                updateFileHtml: function (a) {
                    var t = "";
                    if (a.length > 1) var t = AIZ.local.files_selected;
                    else var t = AIZ.local.file_selected;
                    return a.length + " " + t;
                },
                updateUploaderSelected: function () {
                    $(".aiz-uploader-selected").html(
                        AIZ.uploader.updateFileHtml(
                            AIZ.uploader.data.selectedFiles
                        )
                    );
                },
                clearUploaderSelected: function () {
                    $(".aiz-uploader-selected-clear").on("click", function () {
                        (AIZ.uploader.data.selectedFiles = []),
                            AIZ.uploader.addSelectedValue(),
                            AIZ.uploader.addHiddenValue(),
                            AIZ.uploader.resetFilter(),
                            AIZ.uploader.updateUploaderSelected(),
                            AIZ.uploader.updateUploaderFiles();
                    });
                },
                resetFilter: function () {
                    $('[name="aiz-uploader-search"]').val(""),
                        $('[name="aiz-show-selected"]').prop("checked", !1),
                        $(
                            '[name="aiz-uploader-sort"] option[value=newest]'
                        ).prop("selected", !0);
                },
                getAllUploads: function (a, t = null, l = null) {
                    $(".aiz-uploader-all").html(
                        '<div class="align-items-center d-flex h-100 justify-content-center w-100"><div class="spinner-border" role="status"></div></div>'
                    );
                    var i = {};
                    null != t && t.length > 0 && (i.search = t),
                        null != l && l.length > 0
                            ? (i.sort = l)
                            : (i.sort = "newest"),
                        $.get(a, i, function (a, t) {
                            "string" == typeof a && (a = JSON.parse(a)),
                                (AIZ.uploader.data.allFiles = a.data),
                                AIZ.uploader.allowedFileType(),
                                AIZ.uploader.addSelectedValue(),
                                AIZ.uploader.addHiddenValue(),
                                AIZ.uploader.updateUploaderFiles(),
                                null != a.next_page_url
                                    ? ((AIZ.uploader.data.next_page_url =
                                          a.next_page_url),
                                      $("#uploader_next_btn").removeAttr(
                                          "disabled"
                                      ))
                                    : $("#uploader_next_btn").attr(
                                          "disabled",
                                          !0
                                      ),
                                null != a.prev_page_url
                                    ? ((AIZ.uploader.data.prev_page_url =
                                          a.prev_page_url),
                                      $("#uploader_prev_btn").removeAttr(
                                          "disabled"
                                      ))
                                    : $("#uploader_prev_btn").attr(
                                          "disabled",
                                          !0
                                      );
                        });
                },
                showSelectedFiles: function () {
                    $('[name="aiz-show-selected"]').on("change", function () {
                        $(this).is(":checked")
                            ? (AIZ.uploader.data.allFiles =
                                  AIZ.uploader.data.selectedFilesObject)
                            : AIZ.uploader.getAllUploads(
                                  AIZ.data.appUrl +
                                      "/aiz-uploader/get-uploaded-files"
                              ),
                            AIZ.uploader.updateUploaderFiles();
                    });
                },
                searchUploaderFiles: function () {
                    $('[name="aiz-uploader-search"]').on("keyup", function () {
                        var a = $(this).val();
                        AIZ.uploader.getAllUploads(
                            AIZ.data.appUrl +
                                "/aiz-uploader/get-uploaded-files",
                            a,
                            $('[name="aiz-uploader-sort"]').val()
                        );
                    });
                },
                sortUploaderFiles: function () {
                    $('[name="aiz-uploader-sort"]').on("change", function () {
                        var a = $(this).val();
                        AIZ.uploader.getAllUploads(
                            AIZ.data.appUrl +
                                "/aiz-uploader/get-uploaded-files",
                            $('[name="aiz-uploader-search"]').val(),
                            a
                        );
                    });
                },
                addSelectedValue: function () {
                    for (var a = 0; a < AIZ.uploader.data.allFiles.length; a++)
                        AIZ.uploader.data.selectedFiles.includes(
                            AIZ.uploader.data.allFiles[a].id
                        )
                            ? (AIZ.uploader.data.allFiles[a].selected = !0)
                            : (AIZ.uploader.data.allFiles[a].selected = !1);
                },
                addHiddenValue: function () {
                    for (var a = 0; a < AIZ.uploader.data.allFiles.length; a++)
                        AIZ.uploader.data.allFiles[a].aria_hidden = !1;
                },
                allowedFileType: function () {
                    if ("all" !== AIZ.uploader.data.type) {
                        let a = AIZ.uploader.data.type.split(",");
                        AIZ.uploader.data.allFiles =
                            AIZ.uploader.data.allFiles.filter(function (t) {
                                return a.includes(t.type);
                            });
                    }
                },
                updateUploaderFiles: function () {
                    $(".aiz-uploader-all").html(
                        '<div class="align-items-center d-flex h-100 justify-content-center w-100"><div class="spinner-border" role="status"></div></div>'
                    );
                    var a = AIZ.uploader.data.allFiles;
                    setTimeout(function () {
                        if (($(".aiz-uploader-all").html(null), a.length > 0))
                            for (var t = 0; t < a.length; t++) {
                                var l = "";
                                l =
                                    "image" === a[t].type
                                        ? '<img src="' +
                                          AIZ.data.fileBaseUrl +
                                          a[t].file_name +
                                          '" class="img-fit">'
                                        : '<i class="la la-file-text"></i>';
                                var i =
                                    '<div class="aiz-file-box-wrap" aria-hidden="' +
                                    a[t].aria_hidden +
                                    '" data-selected="' +
                                    a[t].selected +
                                    '"><div class="aiz-file-box"><div class="card card-file aiz-uploader-select" title="' +
                                    a[t].file_original_name +
                                    "." +
                                    a[t].extension +
                                    '" data-value="' +
                                    a[t].id +
                                    '"><div class="card-file-thumb">' +
                                    l +
                                    '</div><div class="card-body"><h6 class="d-flex"><span class="text-truncate title">' +
                                    a[t].file_original_name +
                                    '</span><span class="ext flex-shrink-0">.' +
                                    a[t].extension +
                                    "</span></h6><p>" +
                                    AIZ.extra.bytesToSize(a[t].file_size) +
                                    "</p></div></div></div></div>";
                                $(".aiz-uploader-all").append(i);
                            }
                        else
                            $(".aiz-uploader-all").html(
                                '<div class="align-items-center d-flex h-100 justify-content-center w-100 nav-tabs"><div class="text-center"><h3>No files found</h3></div></div>'
                            );
                        AIZ.uploader.uploadSelect(),
                            AIZ.uploader.deleteUploaderFile();
                    }, 300);
                },
                inputSelectPreviewGenerate: function (a) {
                    a
                        .find(".selected-files")
                        .val(AIZ.uploader.data.selectedFiles),
                        a.next(".file-preview").html(null),
                        AIZ.uploader.data.selectedFiles.length > 0
                            ? $.post(
                                  AIZ.data.appUrl +
                                      "/aiz-uploader/get_file_by_ids",
                                  {
                                      _token: AIZ.data.csrf,
                                      ids: AIZ.uploader.data.selectedFiles.toString(),
                                  },
                                  function (t) {
                                      if (
                                          (a.next(".file-preview").html(null),
                                          t.length > 0)
                                      ) {
                                          a.find(".file-amount").html(
                                              AIZ.uploader.updateFileHtml(t)
                                          );
                                          for (var l = 0; l < t.length; l++) {
                                              var i = "";
                                              i =
                                                  "image" === t[l].type
                                                      ? '<img src="' +
                                                        t[l].file_name +
                                                        '" class="img-fit">'
                                                      : '<i class="la la-file-text"></i>';
                                              var o =
                                                  '<div class="d-flex justify-content-between align-items-center mt-2 file-preview-item" data-id="' +
                                                  t[l].id +
                                                  '" title="' +
                                                  t[l].file_original_name +
                                                  "." +
                                                  t[l].extension +
                                                  '"><div class="align-items-center align-self-stretch d-flex justify-content-center thumb">' +
                                                  i +
                                                  '</div><div class="col body"><h6 class="d-flex"><span class="text-truncate title">' +
                                                  t[l].file_original_name +
                                                  '</span><span class="flex-shrink-0 ext">.' +
                                                  t[l].extension +
                                                  "</span></h6><p>" +
                                                  AIZ.extra.bytesToSize(
                                                      t[l].file_size
                                                  ) +
                                                  '</p></div><div class="remove"><button class="btn btn-sm btn-link remove-attachment" type="button"><i class="la la-close"></i></button></div></div>';
                                              a.next(".file-preview").append(o);
                                          }
                                      } else
                                          a.find(".file-amount").html(
                                              AIZ.local.choose_file
                                          );
                                  }
                              )
                            : a
                                  .find(".file-amount")
                                  .html(AIZ.local.choose_file);
                },
                editorImageGenerate: function (a) {
                    if (AIZ.uploader.data.selectedFiles.length > 0)
                        for (
                            var t = 0;
                            t < AIZ.uploader.data.selectedFiles.length;
                            t++
                        ) {
                            var l = AIZ.uploader.data.allFiles.findIndex(
                                    (a) =>
                                        a.id ===
                                        AIZ.uploader.data.selectedFiles[t]
                                ),
                                i = "";
                            "image" === AIZ.uploader.data.allFiles[l].type &&
                                ((i =
                                    '<img src="' +
                                    AIZ.data.fileBaseUrl +
                                    AIZ.uploader.data.allFiles[l].file_name +
                                    '">'),
                                a[0].insertHTML(i));
                        }
                },
                dismissUploader: function () {
                    $("#aizUploaderModal").on("hidden.bs.modal", function () {
                        $(".aiz-uploader-backdrop").remove(),
                            $("#aizUploaderModal").remove();
                    });
                },
                trigger: function (
                    a = null,
                    t = "",
                    l = "all",
                    i = "",
                    o = !1,
                    n = null
                ) {
                    var a = $(a),
                        o = o,
                        l = l,
                        s = i;
                    "" !== s
                        ? (AIZ.uploader.data.selectedFiles = s
                              .split(",")
                              .map(Number))
                        : (AIZ.uploader.data.selectedFiles = []),
                        void 0 !== l &&
                            l.length > 0 &&
                            (AIZ.uploader.data.type = l),
                        o
                            ? (AIZ.uploader.data.multiple = !0)
                            : (AIZ.uploader.data.multiple = !1),
                        $.post(
                            AIZ.data.appUrl + "/aiz-uploader",
                            { _token: AIZ.data.csrf },
                            function (l) {
                                $("body").append(l),
                                    $("#aizUploaderModal").modal("show"),
                                    AIZ.plugins.aizUppy(),
                                    AIZ.uploader.getAllUploads(
                                        AIZ.data.appUrl +
                                            "/aiz-uploader/get-uploaded-files",
                                        null,
                                        $('[name="aiz-uploader-sort"]').val()
                                    ),
                                    AIZ.uploader.updateUploaderSelected(),
                                    AIZ.uploader.clearUploaderSelected(),
                                    AIZ.uploader.sortUploaderFiles(),
                                    AIZ.uploader.searchUploaderFiles(),
                                    AIZ.uploader.showSelectedFiles(),
                                    AIZ.uploader.dismissUploader(),
                                    $("#uploader_next_btn").on(
                                        "click",
                                        function () {
                                            null !=
                                                AIZ.uploader.data
                                                    .next_page_url &&
                                                ($(
                                                    '[name="aiz-show-selected"]'
                                                ).prop("checked", !1),
                                                AIZ.uploader.getAllUploads(
                                                    AIZ.uploader.data
                                                        .next_page_url
                                                ));
                                        }
                                    ),
                                    $("#uploader_prev_btn").on(
                                        "click",
                                        function () {
                                            null !=
                                                AIZ.uploader.data
                                                    .prev_page_url &&
                                                ($(
                                                    '[name="aiz-show-selected"]'
                                                ).prop("checked", !1),
                                                AIZ.uploader.getAllUploads(
                                                    AIZ.uploader.data
                                                        .prev_page_url
                                                ));
                                        }
                                    ),
                                    $(".aiz-uploader-search i").on(
                                        "click",
                                        function () {
                                            $(this)
                                                .parent()
                                                .toggleClass("open");
                                        }
                                    ),
                                    $(
                                        '[data-toggle="aizUploaderAddSelected"]'
                                    ).on("click", function () {
                                        "input" === t
                                            ? AIZ.uploader.inputSelectPreviewGenerate(
                                                  a
                                              )
                                            : "direct" === t &&
                                              n(
                                                  AIZ.uploader.data
                                                      .selectedFiles
                                              ),
                                            $("#aizUploaderModal").modal(
                                                "hide"
                                            );
                                    });
                            }
                        );
                },
                initForInput: function () {
                    $(document).on(
                        "click",
                        '[data-toggle="aizuploader"]',
                        function (a) {
                            if (1 === a.detail) {
                                var t = $(this),
                                    l = t.data("multiple"),
                                    i = t.data("type"),
                                    o = t.find(".selected-files").val();
                                (l = l || ""),
                                    (i = i || ""),
                                    (o = o || ""),
                                    AIZ.uploader.trigger(
                                        this,
                                        "input",
                                        i,
                                        o,
                                        l
                                    );
                            }
                        }
                    );
                },
                previewGenerate: function () {
                    $('[data-toggle="aizuploader"]').each(function () {
                        var a = $(this),
                            t = a.find(".selected-files").val();
                        "" != t &&
                            $.post(
                                AIZ.data.appUrl +
                                    "/aiz-uploader/get_file_by_ids",
                                { _token: AIZ.data.csrf, ids: t },
                                function (t) {
                                    if (
                                        (a.next(".file-preview").html(null),
                                        t.length > 0)
                                    ) {
                                        a.find(".file-amount").html(
                                            AIZ.uploader.updateFileHtml(t)
                                        );
                                        for (var l = 0; l < t.length; l++) {
                                            var i = "";
                                            i =
                                                "image" === t[l].type
                                                    ? '<img src="' +
                                                      t[l].file_name +
                                                      '" class="img-fit">'
                                                    : '<i class="la la-file-text"></i>';
                                            var o =
                                                '<div class="d-flex justify-content-between align-items-center mt-2 file-preview-item" data-id="' +
                                                t[l].id +
                                                '" title="' +
                                                t[l].file_original_name +
                                                "." +
                                                t[l].extension +
                                                '"><div class="align-items-center align-self-stretch d-flex justify-content-center thumb">' +
                                                i +
                                                '</div><div class="col body"><h6 class="d-flex"><span class="text-truncate title">' +
                                                t[l].file_original_name +
                                                '</span><span class="ext flex-shrink-0">.' +
                                                t[l].extension +
                                                "</span></h6><p>" +
                                                AIZ.extra.bytesToSize(
                                                    t[l].file_size
                                                ) +
                                                '</p></div><div class="remove"><button class="btn btn-sm btn-link remove-attachment" type="button"><i class="la la-close"></i></button></div></div>';
                                            a.next(".file-preview").append(o);
                                        }
                                    } else
                                        a.find(".file-amount").html(
                                            AIZ.local.choose_file
                                        );
                                }
                            );
                    });
                },
            }),
            (AIZ.plugins = {
                metismenu: function () {
                    $('[data-toggle="aiz-side-menu"]').metisMenu();
                },
                bootstrapSelect: function (a = "") {
                    $(".aiz-selectpicker").each(function (t) {
                        var l = $(this);
                        if (!l.parent().hasClass("bootstrap-select")) {
                            var i = l.data("selected");
                            void 0 !== i && l.val(i),
                                l.selectpicker({
                                    size: 5,
                                    noneSelectedText:
                                        AIZ.local.nothing_selected,
                                    virtualScroll: !1,
                                });
                        }
                        "refresh" === a && l.selectpicker("refresh"),
                            "destroy" === a && l.selectpicker("destroy");
                    });
                },
                tagify: function () {
                    $(".aiz-tag-input")
                        .not(".tagify")
                        .each(function () {
                            var $this = $(this),
                                maxTags = $this.data("max-tags"),
                                whitelist = $this.data("whitelist"),
                                onchange = $this.data("on-change");
                            (maxTags = maxTags || 1 / 0),
                                (whitelist = whitelist || []),
                                $this.tagify({
                                    maxTags: maxTags,
                                    whitelist: whitelist,
                                    dropdown: { enabled: 1 },
                                });
                            try {
                                callback = eval(onchange);
                            } catch (e) {
                                var callback = "";
                            }
                            "function" == typeof callback &&
                                ($this.on("removeTag", function () {
                                    callback();
                                }),
                                $this.on("add", function () {
                                    callback();
                                }));
                        });
                },
                textEditor: function () {
                    $(".aiz-text-editor").each(function (a) {
                        var t = $(this),
                            l = t.data("buttons"),
                            i = t.data("min-height"),
                            o = t.attr("placeholder"),
                            n = t.data("format");
                        (l = l || [
                            ["font", ["bold", "underline", "italic", "clear"]],
                            ["para", ["ul", "ol", "paragraph"]],
                            ["style", ["style"]],
                            ["color", ["color"]],
                            ["table", ["table"]],
                            ["insert", ["link", "picture", "video"]],
                            ["view", ["fullscreen", "undo", "redo"]],
                        ]),
                            (o = o || ""),
                            (i = i || 200),
                            (n = void 0 !== n && n),
                            t.summernote({
                                toolbar: l,
                                placeholder: o,
                                disableDragAndDrop: !0,
                                height: i,
                                callbacks: {
                                    onImageUpload: function (a) {
                                        a.pop();
                                    },
                                    onPaste: function (a) {
                                        if (n) {
                                            var t = (
                                                (a.originalEvent || a)
                                                    .clipboardData ||
                                                window.clipboardData
                                            ).getData("Text");
                                            a.preventDefault(),
                                                document.execCommand(
                                                    "insertText",
                                                    !1,
                                                    t
                                                );
                                        }
                                    },
                                },
                            });
                        var s = t.summernote(
                            "module",
                            "videoDialog"
                        ).createVideoNode;
                        t.summernote("module", "videoDialog").createVideoNode =
                            function (a) {
                                var t = $(
                                        '<div class="embed-responsive embed-responsive-16by9"></div>'
                                    ),
                                    l = s(a);
                                return (
                                    (l = $(l).addClass(
                                        "embed-responsive-item"
                                    )),
                                    t.append(l)[0]
                                );
                            };
                    });
                },
                dateRange: function () {
                    $(".aiz-date-range").each(function () {
                        var a = $(this),
                            t = moment().startOf("day");
                        a.val();
                        var l = !1,
                            i = !1,
                            o = !1,
                            n = {
                                Today: [moment(), moment()],
                                Yesterday: [
                                    moment().subtract(1, "days"),
                                    moment().subtract(1, "days"),
                                ],
                                "Last 7 Days": [
                                    moment().subtract(6, "days"),
                                    moment(),
                                ],
                                "Last 30 Days": [
                                    moment().subtract(29, "days"),
                                    moment(),
                                ],
                                "This Month": [
                                    moment().startOf("month"),
                                    moment().endOf("month"),
                                ],
                                "Last Month": [
                                    moment()
                                        .subtract(1, "month")
                                        .startOf("month"),
                                    moment()
                                        .subtract(1, "month")
                                        .endOf("month"),
                                ],
                            },
                            s = a.data("single"),
                            d = a.data("show-dropdown"),
                            r = a.data("format"),
                            c = a.data("separator"),
                            u = a.data("past-disable"),
                            p = a.data("future-disable"),
                            f = a.data("time-picker"),
                            m = a.data("time-gap"),
                            o = a.data("advanced-range");
                        (s = !!s && s),
                            (d = !!d && d),
                            (r = r || "YYYY-MM-DD"),
                            (c = c || " / "),
                            (l = u ? t : l),
                            (i = p ? t : i),
                            (f = !!f && f),
                            (m = m || 1),
                            (n = o ? n : ""),
                            a.daterangepicker({
                                singleDatePicker: s,
                                showDropdowns: d,
                                minDate: l,
                                maxDate: i,
                                timePickerIncrement: m,
                                autoUpdateInput: !1,
                                ranges: n,
                                locale: {
                                    format: r,
                                    separator: c,
                                    applyLabel: "Select",
                                    cancelLabel: "Clear",
                                },
                            }),
                            s
                                ? a.on(
                                      "apply.daterangepicker",
                                      function (t, l) {
                                          a.val(l.startDate.format(r));
                                      }
                                  )
                                : a.on(
                                      "apply.daterangepicker",
                                      function (t, l) {
                                          a.val(
                                              l.startDate.format(r) +
                                                  c +
                                                  l.endDate.format(r)
                                          );
                                      }
                                  ),
                            a.on("cancel.daterangepicker", function (t, l) {
                                a.val("");
                            });
                    });
                },
                timePicker: function () {
                    $(".aiz-time-picker").each(function () {
                        var a = $(this),
                            t = a.data("minute-step"),
                            l = a.data("default");
                        (t = t || 10),
                            (l = l || "00:00"),
                            a.timepicker({
                                template: "dropdown",
                                minuteStep: t,
                                defaultTime: l,
                                icons: {
                                    up: "las la-angle-up",
                                    down: "las la-angle-down",
                                },
                                showInputs: !1,
                            });
                    });
                },
                colorPicker: function () {
                    $(".aiz-color-picker").on("change", function () {
                        var a = $(this);
                        let t = a.val();
                        a.parent().parent().siblings(".aiz-color-input").val(t);
                    }),
                        $(".aiz-color-input").on("change", function () {
                            let a = $(this).val();
                            $(this)
                                .siblings(".input-group-append")
                                .children(".input-group-text")
                                .children(".aiz-color-picker")
                                .val(a);
                        });
                },
                fooTable: function () {
                    $(".aiz-table").each(function () {
                        var a = $(this),
                            t = a.data("empty");
                        (t = t || AIZ.local.nothing_found),
                            a.footable({
                                breakpoints: {
                                    xs: 576,
                                    sm: 768,
                                    md: 992,
                                    lg: 1200,
                                    xl: 1400,
                                },
                                cascade: !0,
                                on: {
                                    "ready.ft.table": function (a, t) {
                                        AIZ.extra.deleteConfirm(),
                                            AIZ.plugins.bootstrapSelect(
                                                "refresh"
                                            );
                                    },
                                },
                                empty: t,
                            });
                    });
                },
                notify: function (a = "dark", t = "") {
                    $.notify(
                        { message: t },
                        {
                            showProgressbar: !0,
                            delay: 2500,
                            mouse_over: "pause",
                            placement: { from: "bottom", align: "left" },
                            animate: {
                                enter: "animated fadeInUp",
                                exit: "animated fadeOutDown",
                            },
                            type: a,
                            template:
                                '<div data-notify="container" class="aiz-notify alert alert-{0}" role="alert"><button type="button" aria-hidden="true" data-notify="dismiss" class="close"><i class="las la-times"></i></button><span data-notify="message">{2}</span><div class="progress" data-notify="progressbar"><div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div></div></div>',
                        }
                    );
                },
                aizUppy: function () {
                    if ($("#aiz-upload-files").length > 0) {
                        var a = Uppy.Core({ autoProceed: !0 });
                        a.use(Uppy.Dashboard, {
                            target: "#aiz-upload-files",
                            inline: !0,
                            showLinkToFileUploadResult: !1,
                            showProgressDetails: !0,
                            hideCancelButton: !0,
                            hidePauseResumeButton: !0,
                            hideUploadButton: !0,
                            proudlyDisplayPoweredByUppy: !1,
                            locale: {
                                strings: {
                                    addMoreFiles: AIZ.local.add_more_files,
                                    addingMoreFiles:
                                        AIZ.local.adding_more_files,
                                    dropPaste:
                                        AIZ.local.drop_files_here_paste_or +
                                        " %{browse}",
                                    browse: AIZ.local.browse,
                                    uploadComplete: AIZ.local.upload_complete,
                                    uploadPaused: AIZ.local.upload_paused,
                                    resumeUpload: AIZ.local.resume_upload,
                                    pauseUpload: AIZ.local.pause_upload,
                                    retryUpload: AIZ.local.retry_upload,
                                    cancelUpload: AIZ.local.cancel_upload,
                                    xFilesSelected: {
                                        0:
                                            "%{smart_count} " +
                                            AIZ.local.file_selected,
                                        1:
                                            "%{smart_count} " +
                                            AIZ.local.files_selected,
                                    },
                                    uploadingXFiles: {
                                        0:
                                            AIZ.local.uploading +
                                            " %{smart_count} " +
                                            AIZ.local.file,
                                        1:
                                            AIZ.local.uploading +
                                            " %{smart_count} " +
                                            AIZ.local.files,
                                    },
                                    processingXFiles: {
                                        0:
                                            AIZ.local.processing +
                                            " %{smart_count} " +
                                            AIZ.local.file,
                                        1:
                                            AIZ.local.processing +
                                            " %{smart_count} " +
                                            AIZ.local.files,
                                    },
                                    uploading: AIZ.local.uploading,
                                    complete: AIZ.local.complete,
                                },
                            },
                        }),
                            a.use(Uppy.XHRUpload, {
                                endpoint:
                                    AIZ.data.appUrl + "/aiz-uploader/upload",
                                fieldName: "aiz_file",
                                formData: !0,
                                headers: { "X-CSRF-TOKEN": AIZ.data.csrf },
                            }),
                            a.on("upload-success", function () {
                                AIZ.uploader.getAllUploads(
                                    AIZ.data.appUrl +
                                        "/aiz-uploader/get-uploaded-files"
                                );
                            });
                    }
                },
                tooltip: function () {
                    $("body")
                        .tooltip({ selector: '[data-toggle="tooltip"]' })
                        .click(function () {
                            $('[data-toggle="tooltip"]').tooltip("hide");
                        });
                },
                countDown: function () {
                    $(".aiz-count-down").length > 0 &&
                        $(".aiz-count-down").each(function () {
                            var a = $(this),
                                t = a.data("date");
                            a.countdown(t).on("update.countdown", function (a) {
                                $(this).html(
                                    a.strftime(
                                        '<div class="countdown-item"><span class="countdown-digit">%-D</span></div><span class="countdown-separator">:</span><div class="countdown-item"><span class="countdown-digit">%H</span></div><span class="countdown-separator">:</span><div class="countdown-item"><span class="countdown-digit">%M</span></div><span class="countdown-separator">:</span><div class="countdown-item"><span class="countdown-digit">%S</span></div>'
                                    )
                                );
                            });
                        });
                },
                countDownCircle: function () {
                    let a =
                        '<div id="time"><div class="circle"><svg><circle cx="30" cy="30" r="30"></circle><circle cx="30" cy="30" r="30" id="dd"></circle></svg><div id="days">00 <br><span>Days</span></div></div><div class="circle"><svg><circle cx="30" cy="30" r="30"></circle><circle cx="30" cy="30" r="30" id="hh"></circle></svg><div id="hours">00 <br><span>Hrs</span></div></div><div class="circle"><svg><circle cx="30" cy="30" r="30"></circle><circle cx="30" cy="30" r="30" id="mm"></circle></svg><div id="minutes">00 <br><span>Min</span></div></div><div class="circle"><svg><circle cx="30" cy="30" r="30"></circle><circle cx="30" cy="30" r="30" id="ss"></circle></svg><div id="seconds">00 <br><span>Sec</span></div></div></div>';
                    $(".aiz-count-down-circle").length > 0 &&
                        $(".aiz-count-down-circle").each(function () {
                            var t = $(this);
                            t.html(a);
                            let l = t.find("#days"),
                                i = t.find("#hours"),
                                o = t.find("#minutes"),
                                n = t.find("#seconds"),
                                s = t.find("#dd"),
                                d = t.find("#hh"),
                                r = t.find("#mm"),
                                c = t.find("#ss");
                            var u = t.attr("end-date");
                            let p = new Date(u).getTime(),
                                f = setInterval(function () {
                                    let a = new Date().getTime(),
                                        t = p - a;
                                    if (t > 0) {
                                        let u = Math.floor(t / 864e5),
                                            m = Math.floor((t % 864e5) / 36e5),
                                            h = Math.floor((t % 36e5) / 6e4),
                                            v = Math.floor((t % 6e4) / 1e3);
                                        l.html(u + "<br><span>Days</span>"),
                                            i.html(m + "<br><span>Hrs</span>"),
                                            o.html(h + "<br><span>Min</span>"),
                                            n.html(v + "<br><span>Sec</span>"),
                                            s.css(
                                                "strokeDashoffset",
                                                190 - (190 * u) / 365
                                            ),
                                            d.css(
                                                "strokeDashoffset",
                                                190 - (190 * m) / 24
                                            ),
                                            r.css(
                                                "strokeDashoffset",
                                                190 - (190 * h) / 60
                                            ),
                                            c.css(
                                                "strokeDashoffset",
                                                190 - (190 * v) / 60
                                            );
                                    } else clearInterval(f);
                                });
                        });
                },
                slickCarousel: function () {
                    $(".aiz-carousel")
                        .not(".slick-initialized")
                        .each(function () {
                            var a = $(this),
                                t = a.data("xs-items"),
                                l = a.data("sm-items"),
                                i = a.data("md-items"),
                                o = a.data("lg-items"),
                                n = a.data("xl-items"),
                                s = a.data("items"),
                                d = a.data("center"),
                                r = a.data("arrows"),
                                c = a.data("dots"),
                                u = a.data("rows"),
                                p = a.data("autoplay"),
                                f = a.data("autoplay-speed"),
                                m = a.data("fade"),
                                h = a.data("nav-for"),
                                v = a.data("infinite"),
                                g = a.data("focus-select"),
                                b = a.data("auto-height"),
                                y = a.data("vertical"),
                                k = a.data("vertical-xs"),
                                x = a.data("vertical-sm"),
                                w = a.data("vertical-md"),
                                z = a.data("vertical-lg"),
                                C = a.data("vertical-xl");
                            (s = s || 1),
                                (n = n || s),
                                (o = o || n),
                                (i = i || o),
                                (l = l || i),
                                (t = t || l),
                                (y = !!y && y),
                                (C = void 0 === C ? y : C),
                                (z = void 0 === z ? C : z),
                                (w = void 0 === w ? z : w),
                                (x = void 0 === x ? w : x),
                                (k = void 0 === k ? x : k),
                                (d = !!d && d),
                                (r = !!r && r),
                                (c = !!c && c),
                                (u = u || 1),
                                (p = !!p && p),
                                (f = f || "1500"),
                                (m = !!m && m),
                                (h = h || null),
                                (v = !!v && v),
                                (g = !!g && g),
                                (b = !!b && b);
                            var _ = "rtl" === $("html").attr("dir") && !y,
                                F = "rtl" === $("html").attr("dir") && !C,
                                S = "rtl" === $("html").attr("dir") && !z,
                                U = "rtl" === $("html").attr("dir") && !w,
                                T = "rtl" === $("html").attr("dir") && !x,
                                D = "rtl" === $("html").attr("dir") && !k;
                            a.slick({
                                slidesToShow: s,
                                autoplay: p,
                                autoplaySpeed: f,
                                dots: c,
                                arrows: r,
                                infinite: v,
                                vertical: y,
                                rtl: _,
                                rows: u,
                                centerPadding: "0px",
                                centerMode: d,
                                fade: m,
                                asNavFor: h,
                                focusOnSelect: g,
                                adaptiveHeight: b,
                                slidesToScroll: 1,
                                prevArrow:
                                    '<button type="button" class="slick-prev"><i class="las la-angle-left"></i></button>',
                                nextArrow:
                                    '<button type="button" class="slick-next"><i class="las la-angle-right"></i></button>',
                                responsive: [
                                    {
                                        breakpoint: 1500,
                                        settings: {
                                            slidesToShow: n,
                                            vertical: C,
                                            rtl: F,
                                        },
                                    },
                                    {
                                        breakpoint: 1200,
                                        settings: {
                                            slidesToShow: o,
                                            vertical: z,
                                            rtl: S,
                                        },
                                    },
                                    {
                                        breakpoint: 992,
                                        settings: {
                                            slidesToShow: i,
                                            vertical: w,
                                            rtl: U,
                                        },
                                    },
                                    {
                                        breakpoint: 768,
                                        settings: {
                                            slidesToShow: l,
                                            vertical: x,
                                            rtl: T,
                                        },
                                    },
                                    {
                                        breakpoint: 576,
                                        settings: {
                                            slidesToShow: t,
                                            vertical: k,
                                            rtl: D,
                                        },
                                    },
                                ],
                            });
                        });
                },
                chart: function (a, t) {
                    $(a).length &&
                        $(a).each(function () {
                            var a = $(this);
                            new Chart(a, t);
                        });
                },
                noUiSlider: function () {
                    $(".aiz-range-slider")[0] &&
                        $(".aiz-range-slider").each(function () {
                            var a =
                                    document.getElementById(
                                        "input-slider-range"
                                    ),
                                t = document.getElementById(
                                    "input-slider-range-value-low"
                                ),
                                l = document.getElementById(
                                    "input-slider-range-value-high"
                                ),
                                i = [t, l];
                            noUiSlider.create(a, {
                                start: [
                                    parseInt(
                                        t.getAttribute("data-range-value-low")
                                    ),
                                    parseInt(
                                        l.getAttribute("data-range-value-high")
                                    ),
                                ],
                                connect: !0,
                                range: {
                                    min: parseInt(
                                        a.getAttribute("data-range-value-min")
                                    ),
                                    max: parseInt(
                                        a.getAttribute("data-range-value-max")
                                    ),
                                },
                            }),
                                a.noUiSlider.on("update", function (a, t) {
                                    i[t].textContent = a[t];
                                }),
                                a.noUiSlider.on("change", function (a, t) {
                                    rangefilter(a);
                                });
                        });
                },
                zoom: function () {
                    $(".img-zoom")[0] &&
                        ($(".img-zoom").zoom({ magnify: 1.5 }),
                        ("ontouchstart" in window ||
                            navigator.maxTouchPoints > 0 ||
                            navigator.msMaxTouchPoints > 0) &&
                            $(".img-zoom").trigger("zoom.destroy"));
                },
                jsSocials: function () {
                    $(".aiz-share")[0] &&
                        $(".aiz-share").jsSocials({
                            showLabel: !1,
                            showCount: !1,
                            shares: [
                                { share: "email", logo: "lar la-envelope" },
                                { share: "twitter", logo: "lab la-twitter" },
                                {
                                    share: "facebook",
                                    logo: "lab la-facebook-f",
                                },
                                {
                                    share: "linkedin",
                                    logo: "lab la-linkedin-in",
                                },
                                { share: "whatsapp", logo: "lab la-whatsapp" },
                            ],
                        });
                },
                particles: function () {
                    particlesJS("particles-js", {
                        particles: {
                            number: {
                                value: 80,
                                density: { enable: !0, value_area: 800 },
                            },
                            color: { value: "#dfdfe6" },
                            shape: {
                                type: "circle",
                                stroke: { width: 0, color: "#000000" },
                                polygon: { nb_sides: 5 },
                                image: {
                                    src: "img/github.svg",
                                    width: 100,
                                    height: 100,
                                },
                            },
                            opacity: {
                                value: 0.5,
                                random: !1,
                                anim: {
                                    enable: !1,
                                    speed: 1,
                                    opacity_min: 0.1,
                                    sync: !1,
                                },
                            },
                            size: {
                                value: 5,
                                random: !0,
                                anim: {
                                    enable: !1,
                                    speed: 40,
                                    size_min: 0.1,
                                    sync: !1,
                                },
                            },
                            line_linked: {
                                enable: !0,
                                distance: 150,
                                color: "#dfdfe6",
                                opacity: 0.4,
                                width: 1,
                            },
                            move: {
                                enable: !0,
                                speed: 6,
                                direction: "none",
                                random: !1,
                                straight: !1,
                                out_mode: "out",
                                attract: {
                                    enable: !1,
                                    rotateX: 600,
                                    rotateY: 1200,
                                },
                            },
                        },
                        interactivity: {
                            detect_on: "canvas",
                            events: {
                                onhover: { enable: !0, mode: "repulse" },
                                onclick: { enable: !0, mode: "push" },
                                resize: !0,
                            },
                            modes: {
                                grab: {
                                    distance: 400,
                                    line_linked: { opacity: 1 },
                                },
                                bubble: {
                                    distance: 400,
                                    size: 40,
                                    duration: 2,
                                    opacity: 8,
                                    speed: 3,
                                },
                                repulse: { distance: 200 },
                                push: { particles_nb: 4 },
                                remove: { particles_nb: 2 },
                            },
                        },
                        retina_detect: !0,
                        config_demo: {
                            hide_card: !1,
                            background_color: "#b61924",
                            background_image: "",
                            background_position: "50% 50%",
                            background_repeat: "no-repeat",
                            background_size: "cover",
                        },
                    });
                },
            }),
            (AIZ.extra = {
                refreshToken: function () {
                    $.get(AIZ.data.appUrl + "/refresh-csrf").done(function (a) {
                        AIZ.data.csrf = a;
                    });
                },
                mobileNavToggle: function () {
                    window.matchMedia("(max-width: 1200px)").matches &&
                        $("body").addClass("side-menu-closed"),
                        $('[data-toggle="aiz-mobile-nav"]').on(
                            "click",
                            function () {
                                $("body").hasClass("side-menu-open")
                                    ? $("body")
                                          .addClass("side-menu-closed")
                                          .removeClass("side-menu-open")
                                    : $("body").hasClass("side-menu-closed")
                                    ? $("body")
                                          .removeClass("side-menu-closed")
                                          .addClass("side-menu-open")
                                    : $("body")
                                          .removeClass("side-menu-open")
                                          .addClass("side-menu-closed");
                            }
                        ),
                        $(".aiz-sidebar-overlay").on("click", function () {
                            $("body")
                                .removeClass("side-menu-open")
                                .addClass("side-menu-closed");
                        });
                },
                initActiveMenu: function () {
                    $('[data-toggle="aiz-side-menu"] a').each(function () {
                        var a = window.location.href.split(/[?#]/)[0];
                        (this.href == a || $(this).hasClass("active")) &&
                            ($(this).addClass("active"),
                            $(this)
                                .closest(".aiz-side-nav-item")
                                .addClass("mm-active"),
                            $(this)
                                .closest(".level-2")
                                .siblings("a")
                                .addClass("level-2-active"),
                            $(this)
                                .closest(".level-3")
                                .siblings("a")
                                .addClass("level-3-active"));
                    });
                },
                deleteConfirm: function () {
                    $(".confirm-delete").click(function (a) {
                        a.preventDefault();
                        var t = $(this).data("href");
                        $("#delete-modal").modal("show"),
                            $("#delete-link").attr("href", t);
                    }),
                        $(".confirm-cancel").click(function (a) {
                            a.preventDefault();
                            var t = $(this).data("href");
                            $("#cancel-modal").modal("show"),
                                $("#cancel-link").attr("href", t);
                        }),
                        $(".confirm-complete").click(function (a) {
                            a.preventDefault();
                            var t = $(this).data("href");
                            $("#complete-modal").modal("show"),
                                $("#comfirm-link").attr("href", t);
                        }),
                        $(".confirm-alert").click(function (a) {
                            a.preventDefault();
                            var t = $(this).data("href"),
                                l = $(this).data("target");
                            $(l).modal("show"),
                                $(l).find(".comfirm-link").attr("href", t),
                                $("#comfirm-link").attr("href", t);
                        });
                },
                bytesToSize: function (a) {
                    var t = ["Bytes", "KB", "MB", "GB", "TB"];
                    if (0 == a) return "0 Byte";
                    var l = parseInt(Math.floor(Math.log(a) / Math.log(1024)));
                    return Math.round(a / Math.pow(1024, l), 2) + " " + t[l];
                },
                multiModal: function () {
                    $(document).on("show.bs.modal", ".modal", function (a) {
                        var t = 1040 + 10 * $(".modal:visible").length;
                        $(this).css("z-index", t),
                            setTimeout(function () {
                                $(".modal-backdrop")
                                    .not(".modal-stack")
                                    .css("z-index", t - 1)
                                    .addClass("modal-stack");
                            }, 0);
                    }),
                        $(document).on("hidden.bs.modal", function () {
                            $(".modal.show").length > 0 &&
                                $("body").addClass("modal-open");
                        });
                },
                bsCustomFile: function () {
                    $(".custom-file input").change(function (a) {
                        for (
                            var t = [], l = 0;
                            l < $(this)[0].files.length;
                            l++
                        )
                            t.push($(this)[0].files[l].name);
                        1 === t.length
                            ? $(this).next(".custom-file-name").html(t[0])
                            : t.length > 1
                            ? $(this)
                                  .next(".custom-file-name")
                                  .html(
                                      t.length + " " + AIZ.local.files_selected
                                  )
                            : $(this)
                                  .next(".custom-file-name")
                                  .html(AIZ.local.choose_file);
                    });
                },
                stopPropagation: function () {
                    $(document).on("click", ".stop-propagation", function (a) {
                        a.stopPropagation();
                    });
                },
                outsideClickHide: function () {
                    $(document).on("click", function (a) {
                        $(".document-click-d-none").addClass("d-none");
                    });
                },
                inputRating: function () {
                    $(".rating-input").each(function () {
                        $(this)
                            .find("label")
                            .on({
                                mouseover: function (a) {
                                    $(this).find("i").addClass("hover"),
                                        $(this)
                                            .prevAll()
                                            .find("i")
                                            .addClass("hover");
                                },
                                mouseleave: function (a) {
                                    $(this).find("i").removeClass("hover"),
                                        $(this)
                                            .prevAll()
                                            .find("i")
                                            .removeClass("hover");
                                },
                                click: function (a) {
                                    $(this)
                                        .siblings()
                                        .find("i")
                                        .removeClass("active"),
                                        $(this).find("i").addClass("active"),
                                        $(this)
                                            .prevAll()
                                            .find("i")
                                            .addClass("active");
                                },
                            }),
                            $(this).find("input").is(":checked") &&
                                ($(this)
                                    .find("label")
                                    .siblings()
                                    .find("i")
                                    .removeClass("active"),
                                $(this)
                                    .find("input:checked")
                                    .closest("label")
                                    .find("i")
                                    .addClass("active"),
                                $(this)
                                    .find("input:checked")
                                    .closest("label")
                                    .prevAll()
                                    .find("i")
                                    .addClass("active"));
                    });
                },
                scrollToBottom: function () {
                    $(".scroll-to-btm").each(function (a, t) {
                        t.scrollTop = t.scrollHeight;
                    });
                },
                classToggle: function () {
                    $(document).on(
                        "click",
                        '[data-toggle="class-toggle"]',
                        function () {
                            var a = $(this),
                                t = a.data("target"),
                                l = a.data("same"),
                                i = $(this).data("backdrop");
                            $(t).hasClass("active")
                                ? ($(t).removeClass("active"),
                                  $(l).removeClass("active"),
                                  a.removeClass("active"),
                                  $("body").removeClass("overflow-hidden"))
                                : ($(t).addClass("active"),
                                  a.addClass("active"),
                                  "static" == i &&
                                      $("body").addClass("overflow-hidden"));
                        }
                    );
                },
                collapseSidebar: function () {
                    $(document).on(
                        "click",
                        '[data-toggle="collapse-sidebar"]',
                        function (a, t) {
                            var l = $(this),
                                i = $(this).data("target"),
                                o = $(this).data("siblings");
                            e.preventDefault(),
                                $(i).hasClass("opened")
                                    ? ($(i).removeClass("opened"),
                                      $(o).removeClass("opened"),
                                      $(l).removeClass("opened"))
                                    : ($(i).addClass("opened"),
                                      $(l).addClass("opened"));
                        }
                    );
                },
                autoScroll: function () {
                    $(".aiz-auto-scroll").length > 0 &&
                        $(".aiz-auto-scroll").each(function () {
                            var a = $(this).data("options");
                            (a = JSON.parse(
                                (a = a || '{"delay" : 2000 ,"amount" : 70 }')
                            )),
                                (this.delay = parseInt(a.delay) || 2e3),
                                (this.amount = parseInt(a.amount) || 70),
                                (this.autoScroll = $(this)),
                                (this.iScrollHeight =
                                    this.autoScroll.prop("scrollHeight")),
                                (this.iScrollTop =
                                    this.autoScroll.prop("scrollTop")),
                                (this.iHeight = this.autoScroll.height());
                            var t = this;
                            this.timerId = setInterval(function () {
                                t.iScrollTop + t.iHeight < t.iScrollHeight
                                    ? ((t.iScrollTop =
                                          t.autoScroll.prop("scrollTop")),
                                      (t.iScrollTop += t.amount),
                                      t.autoScroll.animate(
                                          { scrollTop: t.iScrollTop },
                                          "slow",
                                          "linear"
                                      ))
                                    : ((t.iScrollTop -= t.iScrollTop),
                                      t.autoScroll.animate(
                                          { scrollTop: "0px" },
                                          "fast",
                                          "swing"
                                      ));
                            }, t.delay);
                        });
                },
                addMore: function () {
                    $('[data-toggle="add-more"]').each(function () {
                        var a = $(this),
                            t = a.data("content"),
                            l = a.data("target");
                        a.on("click", function (a) {
                            a.preventDefault(),
                                $(l).append(t),
                                AIZ.plugins.bootstrapSelect();
                        });
                    });
                },
                removeParent: function () {
                    $(document).on(
                        "click",
                        '[data-toggle="remove-parent"]',
                        function () {
                            var a = $(this),
                                t = a.data("parent");
                            a.closest(t).remove();
                        }
                    );
                },
                selectHideShow: function () {
                    $('[data-show="selectShow"]').each(function () {
                        var a = $(this).data("target");
                        $(this).on("change", function () {
                            var t = $(this).val();
                            $(a)
                                .children()
                                .not("." + t)
                                .addClass("d-none"),
                                $(a)
                                    .find("." + t)
                                    .removeClass("d-none");
                        });
                    });
                },
                plusMinus: function () {
                    $(".aiz-plus-minus input").each(function () {
                        var a = $(this),
                            t = parseInt($(this).attr("min")),
                            l = parseInt($(this).attr("max")),
                            i = parseInt($(this).val());
                        i <= t
                            ? a
                                  .siblings('[data-type="minus"]')
                                  .attr("disabled", !0)
                            : a
                                  .siblings('[data-type="minus"]')
                                  .attr("disabled") &&
                              a
                                  .siblings('[data-type="minus"]')
                                  .removeAttr("disabled"),
                            i >= l
                                ? a
                                      .siblings('[data-type="plus"]')
                                      .attr("disabled", !0)
                                : a
                                      .siblings('[data-type="plus"]')
                                      .attr("disabled") &&
                                  a
                                      .siblings('[data-type="plus"]')
                                      .removeAttr("disabled");
                    }),
                        $(".aiz-plus-minus button")
                            .off("click")
                            .on("click", function (a) {
                                a.preventDefault();
                                var t = $(this).attr("data-field"),
                                    l = $(this).attr("data-type"),
                                    i = $("input[name='" + t + "']"),
                                    o = parseInt(i.val());
                                isNaN(o)
                                    ? i.val(0)
                                    : "minus" == l
                                    ? (o > i.attr("min") &&
                                          i.val(o - 1).change(),
                                      parseInt(i.val()) == i.attr("min") &&
                                          $(this).attr("disabled", !0))
                                    : "plus" == l &&
                                      (o < i.attr("max") &&
                                          i.val(o + 1).change(),
                                      parseInt(i.val()) == i.attr("max") &&
                                          $(this).attr("disabled", !0));
                            }),
                        $(".aiz-plus-minus input")
                            .off("change")
                            .on("change", function () {
                                var a = parseInt($(this).attr("min")),
                                    t = parseInt($(this).attr("max")),
                                    l = parseInt($(this).val());
                                (name = $(this).attr("name")),
                                    l >= a
                                        ? $(this)
                                              .siblings("[data-type='minus']")
                                              .removeAttr("disabled")
                                        : (alert(
                                              translate(
                                                  "Sorry, the minimum limit has been reached"
                                              )
                                          ),
                                          $(this).val(a)),
                                    l <= t
                                        ? $(this)
                                              .siblings("[data-type='plus']")
                                              .removeAttr("disabled")
                                        : (alert(
                                              translate(
                                                  "Sorry, the maximum limit has been reached"
                                              )
                                          ),
                                          $(this).val(t)),
                                    "function" == typeof getVariantPrice &&
                                        getVariantPrice();
                            });
                },
                hovCategoryMenu: function () {
                    $("#category-menu-icon, #category-sidebar")
                        .on("mouseover", function (a) {
                            $("#hover-category-menu")
                                .addClass("active")
                                .removeClass("d-none");
                        })
                        .on("mouseout", function (a) {
                            $("#hover-category-menu")
                                .addClass("d-none")
                                .removeClass("active");
                        });
                },
                clickCategoryMenu: function () {
                    var a = $("#click-category-menu");
                    a.hide(),
                        a.removeClass("d-none"),
                        $("#category-menu-bar").on("click", function (t) {
                            a.slideToggle(500),
                                $("#category-menu-bar-icon").hasClass("show")
                                    ? $("#category-menu-bar-icon").removeClass(
                                          "show"
                                      )
                                    : $("#category-menu-bar-icon").addClass(
                                          "show"
                                      );
                        });
                },
                hovUserTopMenu: function () {
                    $("#nav-user-info")
                        .on("mouseover", function (a) {
                            $(".hover-user-top-menu").addClass("active");
                        })
                        .on("mouseout", function (a) {
                            $(".hover-user-top-menu").removeClass("active");
                        });
                },
                trimAppUrl: function () {
                    "/" == AIZ.data.appUrl.slice(-1) &&
                        (AIZ.data.appUrl = AIZ.data.appUrl.slice(
                            0,
                            AIZ.data.appUrl.length - 1
                        ));
                },
                setCookie: function (a, t, l) {
                    var i = new Date();
                    i.setTime(i.getTime() + 864e5 * l);
                    var o = "expires=" + i.toUTCString();
                    document.cookie = a + "=" + t + ";" + o + ";path=/";
                },
                getCookie: function (a) {
                    for (
                        var t = a + "=",
                            l = decodeURIComponent(document.cookie).split(";"),
                            i = 0;
                        i < l.length;
                        i++
                    ) {
                        for (var o = l[i]; " " === o.charAt(0); )
                            o = o.substring(1);
                        if (0 === o.indexOf(t))
                            return o.substring(t.length, o.length);
                    }
                    return "";
                },
                acceptCookie: function () {
                    AIZ.extra.getCookie("acceptCookies") ||
                        $(".aiz-cookie-alert").addClass("show"),
                        $(".aiz-cookie-accept").on("click", function () {
                            AIZ.extra.setCookie("acceptCookies", !0, 60),
                                $(".aiz-cookie-alert").removeClass("show");
                        });
                },
                setSession: function () {
                    $(".set-session").each(function () {
                        var a = $(this),
                            t = a.data("key"),
                            l = a.data("value");
                        let i = new Date(),
                            o = { value: l, expiry: i.getTime() + 36e5 };
                        a.on("click", function () {
                            localStorage.setItem(t, JSON.stringify(o));
                        });
                    });
                },
                showSessionPopup: function () {
                    $(".removable-session").each(function () {
                        var a = $(this),
                            t = a.data("key");
                        a.data("value");
                        var l = {};
                        localStorage.getItem(t) &&
                            (l = JSON.parse((l = localStorage.getItem(t))));
                        let i = new Date();
                        (void 0 === l.expiry || i.getTime() > l.expiry) &&
                            a.removeClass("d-none");
                    });
                },
            }),
            setInterval(function () {
                AIZ.extra.refreshToken();
            }, 36e5),
            AIZ.extra.initActiveMenu(),
            AIZ.extra.mobileNavToggle(),
            AIZ.extra.deleteConfirm(),
            AIZ.extra.multiModal(),
            AIZ.extra.inputRating(),
            AIZ.extra.bsCustomFile(),
            AIZ.extra.stopPropagation(),
            AIZ.extra.outsideClickHide(),
            AIZ.extra.scrollToBottom(),
            AIZ.extra.classToggle(),
            AIZ.extra.collapseSidebar(),
            AIZ.extra.autoScroll(),
            AIZ.extra.addMore(),
            AIZ.extra.removeParent(),
            AIZ.extra.selectHideShow(),
            AIZ.extra.plusMinus(),
            AIZ.extra.hovCategoryMenu(),
            AIZ.extra.clickCategoryMenu(),
            AIZ.extra.hovUserTopMenu(),
            AIZ.extra.trimAppUrl(),
            AIZ.extra.acceptCookie(),
            AIZ.extra.setSession(),
            AIZ.extra.showSessionPopup(),
            AIZ.plugins.metismenu(),
            AIZ.plugins.bootstrapSelect(),
            AIZ.plugins.tagify(),
            AIZ.plugins.textEditor(),
            AIZ.plugins.tooltip(),
            AIZ.plugins.countDown(),
            AIZ.plugins.countDownCircle(),
            AIZ.plugins.dateRange(),
            AIZ.plugins.timePicker(),
            AIZ.plugins.colorPicker(),
            AIZ.plugins.fooTable(),
            AIZ.plugins.slickCarousel(),
            AIZ.plugins.noUiSlider(),
            AIZ.plugins.zoom(),
            AIZ.plugins.jsSocials(),
            AIZ.uploader.initForInput(),
            AIZ.uploader.removeAttachment(),
            AIZ.uploader.previewGenerate();
    })(jQuery);
