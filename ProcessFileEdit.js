/*jslint this:true */
/*jslint browser:true */
/*global
    $, window, parent
*/
$(document).ready(function () {
    "use strict";

    // Hide all subfolders at startup
    // $(".php-file-tree").find("ul").hide(); I hide them with css, much faster

    // Expand/collapse on click
    $(".pft-d a").click(function (e) {
        var parent = $(this).parent();
        e.preventDefault();
        parent.find("ul:first").slideToggle("fast");
        parent.toggleClass("pft-d-open");
        //if(parent().attr("class") == "pft-d") {
        if (parent.hasClass("pft-d")) {
            return false;
        }
    });

    function setupCloneButton1() {
        // this is a copy of main.js from admin theme

        if ($("body").is(".modal") === false) {
            return; // prevent doubling in main.js
        }

        // if there are buttons in the format "a button" without ID attributes, copy them into the masthead
        // or buttons in the format button.head_button_clone with an ID attribute.
        // var $buttons = $("#content a[id=''] button[id=''], #content button.head_button_clone[id!='']");
        // var $buttons = $("#content a:not([id]) button:not([id]), #content button.head_button_clone[id!=]");
        var $buttons = $("button.pw-head-button, button.head-button, button.head_button_clone"); // head_button_clone is legacy

        if ($buttons.length === 0) {
            return;
        }

        var $head = $("#head_button");
        //MP if($head.length == 0) $head = $("<div id='head_button'></div>").prependTo("#breadcrumbs .container");
        if ($head.length === 0) {
            $head = $("<div id='head_button'></div>").prependTo("#content .container"); // now #pw-content .pw-container
        }

        $buttons.each(function () {
            var $t = $(this);
            var $a = $t.parent("a");
            var $button;
            if ($a.length > 0) {
                $button = $t.parent("a").clone(true);
                $head.prepend($button);
            } else if ($t.hasClass("pw-head-button") || $t.hasClass("head_button_clone") || $t.hasClass("head-button")) { // head-button is legacy
                $button = $t.clone(true);
                $button.attr("data-from_id", $t.attr("id")).attr("id", $t.attr("id") + "_copy");
                $button.click(function () {
                    $("#" + $(this).attr("data-from_id")).click();
                    return false;
                });
                $head.prepend($button);
            }
        });
        $head.show();
    }

    setupCloneButton1();

    var saveButton = $("#saveFile");
    var isChanged = $("#change");
    var closeBtn = parent.$(".ui-dialog-titlebar-close");

    saveButton.on("click", function (e) {
        e.preventDefault();
        window.editor.save();

        $.ajax({
            url: saveButton.data("url"),
            data: $("#editForm").serializeArray(),
            type: "POST",
            success: function (data) {
                if (data === "") {
                    isChanged.html(""); //remove changes indicator (*)
                    $("#saveFile").addClass("ui-state-disabled");
                    $("#saveFile_copy").addClass("ui-state-disabled");
                } else {
                    window.alert(data);
                }
                return false;
            },
            error: function (xhr, textStatus) {
                window.alert("Ajax request failed: " + textStatus);
                return false;
            }
        });

        return false;
    });

    /*
    $(document).on("keydown", function (e) {
        e = e || window.event;
        var closeBtnCurrent = parent.$(".ui-dialog-titlebar-close");
        if (e.keyCode === 27 && closeBtnCurrent.length) {
            closeBtnCurrent.trigger("mousedown");
        }
    });
    */

    closeBtn.on("mousedown", function (e) {
        if (isChanged.html() !== "") {
            var confirm = window.confirm("File is not saved. Continue?");
            if (!confirm) {
                e.preventDefault();
                return false;
            }
            isChanged.html("");
            closeBtn.click();
        }
    });

    if (!!$.prototype.magnificPopup) {
        var magnificOptions = {
            closeOnContentClick: true,
            closeBtnInside: true
        };

        var magnificOptionsImage = $.extend(true, {}, magnificOptions);
        magnificOptionsImage.type = "image";
        $(".ext-jpg a, .ext-png a, .ext-gif a, .ext-bmp a").magnificPopup(magnificOptionsImage);
    }

});
