$(document).ready( function() {

	// Hide all subfolders at startup
	//MP $(".php-file-tree").find("ul").hide(); I hide them in css, much faster

	// Expand/collapse on click
	$(".pft-dir a").click( function() {
		$(this).parent().find("ul:first").slideToggle("fast");
		//MP if( $(this).parent().attr('className') == "pft-dir" ) {
		if( $(this).parent().attr('class') == "pft-dir" ) {
			return false;
		};
	});

	saveButton = $("#saveFile");
	saveButton.on('click', function (e) {
		e.preventDefault();
		window.editor.save();

		$.ajax({
			url: saveButton.data('url'),
			data: $("#editForm").serializeArray(),
			type: "POST", //use method for jQuery from 1.9.0
			//cache: false,
			success: function (data) { //use done for jQuery 3.0
				if(data === "") {
					$("#change").html(""); //remove *
				} else {
					alert(data);
				}
				return false;
			},
			error: function (xhr, textStatus) { //use fail for jQuery 3.0
				alert("Ajax request failed: " + textStatus);
				return false;
			}
		});

		return false;
	});

});
