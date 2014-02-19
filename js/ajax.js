 /**
 * jQuery and AJAX for the IMDI Archive Search Plugin.
 *
 * @since 1.0.0
 *
 * @package	IMDI Archive Search Plugin
 * @author	Paul Trilsbeek
 */

function advancedSearchMakeActorGroup(letter) {
	return {name: "(" + letter + ")", value: "Session.MDGroup.Actors.Actor(" + letter + ")", group: [
			{ name: "Name", value: "Session.MDGroup.Actors.Actor(" + letter + ").Name" },
			{ name: "Full Name", value: "Session.MDGroup.Actors.Actor(" + letter + ").FullName"},
			{ name: "Code", value: "Session.MDGroup.Actors.Actor(" + letter + ").Code"},
			{ name: "Age", value: "Session.MDGroup.Actors.Actor(" + letter + ").Age", operators: ["=", "<", ">"] },
			{ name: "Birth Date", value: "Session.MDGroup.Actors.Actor(" + letter + ").BirthDate", type: "date", operators: ["=", "<", ">"]},
			{ name: "Sex", value: "Session.MDGroup.Actors.Actor(" + letter + ").Sex", autocomplete: {type: "occurrences"}},
			{ name: "Education", value: "Session.MDGroup.Actors.Actor(" + letter + ").Education"},
			{ name: "Role", value: "Session.MDGroup.Actors.Actor(" + letter + ").Role", autocomplete: {type: "occurrences"}},
			{ name: "Ethnic Group", value: "Session.MDGroup.Actors.Actor(" + letter + ").EthnicGroup", autocomplete: {type: "occurrences"}},
			{ name: "Family Social Role", value: "Session.MDGroup.Actors.Actor(" + letter + ").FamilySocialRole"},
			{ name: "Description", value: "Session.MDGroup.Actors.Actor(" + letter + ").Description"},
			{ name: "Anonymized", value: "Session.MDGroup.Actors.Actor(" + letter + ").Anonymized", autocomplete: {type: "predefined", items: ["yes", "no"]}},
			{ name: "Contact", value: "Session.MDGroup.Actors.Actor(" + letter + ").Contact", group: [
				{ name: "Name", value: "Session.MDGroup.Actors.Actor(" + letter + ").Contact.Name", autocomplete: {type: "occurrences"}},
				{ name: "Address", value: "Session.MDGroup.Actors.Actor(" + letter + ").Contact.Address"},
				{ name: "Email", value: "Session.MDGroup.Actors.Actor(" + letter + ").Project.Contact.Email"},
				{ name: "Organisation", value: "Session.Actors.Actor(" + letter + ").Project.Contact.Organisation", autocomplete: {type: "occurrences"}}
			]}
		]};
}

function advancedSearchMakeLanguageGroup(letter) {
	return  { name: "(" + letter + ")", value: "Session.MDGroup.Content.Languages.Language(" + letter + ")", group: [
				{ name: "Name", value: "Session.MDGroup.Content.Languages.Language(" + letter + ").Name", autocomplete: {type: "occurrences"}},
				{ name: "ID", value: "Session.MDGroup.Content.Languages.Language(" + letter + ").ID", autocomplete: {type: "occurrences"}}
			]};
}

function advancedSearchMakeMediaFileGroup(letter) {
	return  { name: "(" + letter + ")", value: "Session.Resources.MediaFile(" + letter + ")", group: [
				{ name: "Type", value: "Session.Resources.MediaFile(" + letter + ").Type", autocomplete: {type: "occurrences"}},
				{ name: "Format", value: "Session.Resources.MediaFile(" + letter + ").Format", autocomplete: {type: "occurrences"}}
			]};
}

function advancedSearchMakeWrittenResourceGroup(letter) {
	return  { name: "(" + letter + ")", value: "Session.Resources.WrittenResource(" + letter + ")", group: [
				{ name: "Date", value: "Session.Resources.WrittenResource(" + letter + ").Date", type: "date"},
				{ name: "Type", value: "Session.Resources.WrittenResource(" + letter + ").Type", autocomplete: {type: "occurrences"}},
				{ name: "Subtype", value: "Session.Resources.WrittenResource(" + letter + ").Subtype", autocomplete: {type: "occurrences"}},
				{ name: "Format", value: "Session.Resources.WrittenResource(" + letter + ").Format", autocomplete: {type: "occurrences"}},


			]};
}

var advancedsearch =  {group : [
	{ name: "Session Name", value: "Session.Name" },
	{ name: "Session Title", value: "Session.Title" },
	{ name: "Date", value: "Session.Date", type: "date", operators: ["=", "<", ">"] },
	{ name: "Location", value: "Session.MDGroup.Location", group: [
		{ name: "Continent", value: "Session.MDGroup.Location.Continent", autocomplete: {type: "occurrences"}},
		{ name: "Country", value: "Session.MDGroup.Location.Country", autocomplete: {type: "occurrences"}},
		{ name: "Region", value: "Session.MDGroup.Location.Region"},
		{ name: "Address", value: "Session.MDGroup.Location.Address"}
	]},
	{ name: "Project", value: "Session.MDGroup.Project", group: [
		{ name: "Name", value: "Session.MDGroup.Project.Name", autocomplete: {type: "occurrences"}},
		{ name: "Title", value: "Session.MDGroup.Project.Title", autocomplete: {type: "occurrences"}},
		{ name: "ID", value: "Session.MDGroup.Project.ID", autocomplete: {type: "occurrences"}},
		{ name: "Description", value: "Session.MDGroup.Project.Description"},
		{ name: "Contact", value: "Session.MDGroup.Project.Contact", group: [
			{ name: "Name", value: "Session.MDGroup.Project.Contact.Name", autocomplete: {type: "occurrences"}},
			{ name: "Address", value: "Session.MDGroup.Project.Contact.Address"},
			{ name: "Email", value: "Session.MDGroup.Location.Project.Contact.Email"},
			{ name: "Organisation", value: "Session.MDGroup.Project.Contact.Organisation", autocomplete: {type: "occurrences"}}
		]}		
	]},
	{ name: "Content", value: "Session.MDGroup.Content", group: [
		{ name: "Languages", value: "Session.MDGroup.Content.Languages", group: [
			advancedSearchMakeLanguageGroup("X"),
			advancedSearchMakeLanguageGroup("Y"),
			advancedSearchMakeLanguageGroup("Z"),
			advancedSearchMakeLanguageGroup("U"),
			advancedSearchMakeLanguageGroup("V"),
			advancedSearchMakeLanguageGroup("W")
		]},
		{ name: "Genre", value: "Session.MDGroup.Content.Genre", autocomplete: {type: "occurrences"}},
		{ name: "Subgenre", value: "Session.MDGroup.Content.Subgenre", autocomplete: {type: "occurrences"}},
		{ name: "Task", value: "Session.MDGroup.Content.Task", autocomplete: {type: "occurrences"}},
		{ name: "Description", value: "Session.MDGroup.Content.Description"},
		{ name: "Communication Context", value: "Session.MDGroup.Content.CommunicationContext", group: [
			{ name: "Event Structure", value: "Session.MDGroup.Content.CommunicationContext.EventStructure", autocomplete: {type: "occurrences"}},
			{ name: "Planning Type", value: "Session.MDGroup.Content.CommunicationContext.PlanningType", autocomplete: {type: "occurrences"}},
			{ name: "Interactivity", value: "Session.MDGroup.Content.CommunicationContext.Interactivity", autocomplete: {type: "occurrences"}},
			{ name: "Social Context", value: "Session.MDGroup.Content.CommunicationContext.SocialContext", autocomplete: {type: "occurrences"}},
			{ name: "Involvement", value: "Session.MDGroup.Content.CommunicationContext.Involvement", autocomplete: {type: "occurrences"}}
		]}
	]},
	{ name: "Actors", value: "Session.MDGroup.Actors", group: [
		advancedSearchMakeActorGroup("X"),
		advancedSearchMakeActorGroup("Y"),		
		advancedSearchMakeActorGroup("Z"),	
		advancedSearchMakeActorGroup("U"),
		advancedSearchMakeActorGroup("V"),	
		advancedSearchMakeActorGroup("W")
	]},
	{ name: "Resources", value: "Session.Resources", group: [
			{ name: "MediaFile", group: [
				advancedSearchMakeMediaFileGroup("X"),
				advancedSearchMakeMediaFileGroup("Y"),		
				advancedSearchMakeMediaFileGroup("Z"),	
				advancedSearchMakeMediaFileGroup("U"),
				advancedSearchMakeMediaFileGroup("V"),	
				advancedSearchMakeMediaFileGroup("W")
			]},
			{ name: "WrittenResource", group: [
				advancedSearchMakeWrittenResourceGroup("X"),
				advancedSearchMakeWrittenResourceGroup("Y"),		
				advancedSearchMakeWrittenResourceGroup("Z"),	
				advancedSearchMakeWrittenResourceGroup("U"),
				advancedSearchMakeWrittenResourceGroup("V"),	
				advancedSearchMakeWrittenResourceGroup("W")
			]}
		]}	
]};

jQuery(document).ready(function($){

	$( ".imdi-category-tabs" ).tabs({ collapsible: true });

	if (History.enabled) {
		if (!History.getState().data) {
			if (_GET['query']) {
				History.replaceState({query: _GET['query']});
			}
		}
	}

	if (!History.enabled) prefill_values(_GET);


	$(window).bind('statechange',function(){ 
		console.log("THE STATE");
		//console.log(History.getState());

		console.log("popp");
		if (History.getState().data) {
			//if (!(History.getState().data['new_request'] == true)) prefill_values(History.getState().data);

			if (History.getState().data.query) {
				var query_value = decodeURIComponent(History.getState().data.query);
				var beginningAt = History.getState().data.beginningAt ? History.getState().data.beginningAt : 0;
				prefill_values(History.getState().data);
				imdiRequest(query_value, beginningAt);
			}
		} 
		
	 });

	function category_link_onclick(e) {
		if (History.enabled) {
			e.preventDefault();
			var query_string = getUrlVars(e.target.href)['query'];
			History.pushState({query: query_string, beginningAt: 0}, null, '/browse?query=' + query_string);
		}	
	}

	function page_link_onclick(e) {
		if (History.enabled) {
			e.preventDefault();
			var query_string = getUrlVars(e.target.href)['query'];
			var beginningAt = getUrlVars(e.target.href)['beginningAt'];
			History.pushState({query: query_string, beginningAt: beginningAt}, null, '/browse?query=' + query_string + "&beginningAt=" + beginningAt);
		}	
	}

	$('#categories a').click(category_link_onclick);

	// $('#results-area').on("click", "div#toggle-save", function(){
	// 				alert("save");
	// 				toggleSaveRequest(this, $(this).children('#imdi_session_url').attr('value'));
	// });


	/**
	 * Perform the AJAX request when the user clicks the search button.
	 */

	 function imdiRequest(query_value, beginningAt) {

		/** Output a message and loading icon */

		var default_text = $('#query-api').val();
		$('#query-api').val(imdi_archive_search_plugin_object.searching);
		//$('#query-api').prop('disabled', true);
		$('#results').after('<span class="waiting"></span>');

		$('.search-results').empty();

		/** Setup our AJAX request */
		var opts = {
			url: imdi_archive_search_plugin_object.url,
			type: 'GET',
			async: true,
			cache: false,
			dataType: 'json',
			data: {
				action: 'search_IMDI_archive',
				nonce: imdi_archive_search_plugin_object.nonce,
				query: query_value,
				beginningAt:  (_GET && _GET['beginningAt']) ? _GET['beginningAt'] : beginningAt
			},
			success: function(response){


				console.log(JSON.stringify(response));

				/** Make sure to remove any previous error messages or data if we have any and append our data */

				$('.search-results').append(response.html);

				$('#pageIndex a').click(page_link_onclick);


				jQuery(".imdi_detailtabs").each(function(){jQuery(this).tabs(
					{
						collapsible: true,
						active: false,
						activate: function(event, ui) {
							if ($(ui.newTab[0]) && $(ui.newTab[0]).hasClass("imdi_infoCountry")) {
								if (L) {
									var countryName = $(ui.newTab[0]).children('a').text();
									// if we have leaflet.js (a map library), look up the country
									$.getJSON("http://open.mapquestapi.com/nominatim/v1/search.php?format=json&q=" + countryName, function(data) {

										// if we have coordinates, display the map
										if (data.length >= 1 && !data.error) {
											var map = L.map($($(ui.newPanel[0]).children('.map')[0]).attr('id')).setView([data[0].lat, data[0].lon], 3);
											L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		    									attribution: 'Map &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>',
		    									maxZoom: 18
											}).addTo(map);
											var marker = L.marker([data[0].lat, data[0].lon]).addTo(map);
										}

						
									})
									
								}
							}
						}
					})
				});
				
				/** Remove the loading icon and replace the button with default text */
				$('.waiting').remove();
				$('#query-api').val(default_text);

			},
			error: function(xhr, textStatus ,e) {
				console.log("huhu2");
				console.log(e);
				console.log(xhr);
				console.log(textStatus);
				/** Make sure to remove any previous error messages or data if we have any */
				$('.search-results').empty();
				
				/** If we have a response as to why our request didn't work, let's output it or give a default error message */
				if ( xhr.responseText )
					$('.search-results').append('<p class="plugin-error">' + xhr.responseText + '</p>');
				else
					$('.search-results').append('<p class="plugin-error">' + imdi_archive_search_plugin_object.error + '</p>');
					
				/** Remove the loading icon and replace the button with default text */
				$('.waiting').remove();
				$('#query-api').val(default_text);
			}
		}
		
		/** Process our actual AJAX request */
		$.ajax(opts);;
	 }

	 function occurrencesRequest(dummyDiv, path, name) {
		
		console.log("Send occurrencesRequest:" + path + " | " + name);

		/** Setup our AJAX request */
		var opts = {
			url: imdi_archive_search_plugin_object.url,
			type: 'GET',
			async: false, 
			cache: false,
			dataType: 'json',
			data: {
				action: 'IMDI_get_occurrences',
				nonce: imdi_archive_search_plugin_object.nonce,
				path: path,
				title: name,
				responseType: "category"
			},
			success: function(html) {
				console.log("got category");
				console.log(html);
				append_category(html, dummyDiv)
			},
			error: category_error
		}
		
		/** Process our actual AJAX request */
		$.ajax(opts);
	 }

	 function append_category(html, dummyDiv) {
	 	$(dummyDiv).replaceWith(html);
		$('#categories a').click(category_link_onclick);
	 }

	 function category_error(xhr, textStatus, e) {
	 	console.log("error");
		console.log(xhr);
	 }

	 function outputPredefinedCategoryRequest(dummyDiv, items, name) {

	 			console.log("Send outputPredefinedCategoryRequest:" + items + " | " + name);

	 		var opts = {
			url: imdi_archive_search_plugin_object.url,
			type: 'GET',
			async: true, 
			cache: false,
			dataType: 'json',
			data: {
				action: 'IMDI_output_category',
				nonce: imdi_archive_search_plugin_object.nonce,
				items: JSON.stringify(items),
				title: name
			},
			success: function(html) {
				append_category(html, dummyDiv)
			},
			error: category_error

	 	};

	 	$.ajax(opts);

	 }

	 function toggleSaveRequest(elem, surl) {
	 	var opts = {
			url: imdi_archive_search_plugin_object.url,
			type: 'GET',
			async: true, 
			cache: false,
			dataType: 'json',
			data: {
				action: 'IMDI_toggle_save_session',
				nonce: imdi_archive_search_plugin_object.nonce,
				session_url: surl
			},
			success: function(html) {
				jQuery(elem).html(html);
			},
			error: category_error

	 	};

	 	$.ajax(opts);
	 }

	$('body').on('click.searchIMDIarchive', '#query-api', function(e){
	
		/** Prevent the default action from occurring */
		e.preventDefault();	

		query_value = $('#imdi-query-value').val();	

		var History = window.History;

		if (History.enabled) {
			History.pushState({query: query_value, beginningAt: 0}, null, '/browse?query=' + query_value);
		}
		else imdiRequest(query_value, 0);

	});
	$('body').on('click.searchIMDIarchive', '#query-api-advanced', function(e){
	
		/** Prevent the default action from occurring */
		e.preventDefault();		

		var query_value = advancedSearchBuildQuery();

		var constraints = Array();
		var counter = 0;

		jQuery("div#constraint").each(function () {
			var inputField = jQuery(this).children("input#constraint")[0];
			var operator = jQuery(this).children("select#operators")[0];
			var lastGroupSelect = jQuery(this).children("select.group").last();

			constraints.push({
				index: advancedSearchGetIndex(lastGroupSelect),
				operator: jQuery(operator).attr("value")
			});
			counter++;
		}); 

		query_value = imdiURLEncode(query_value);

		var History = window.History;

		if (History.enabled) {
			History.pushState({query: query_value, constraints: constraints, beginningAt: 0}, null, '/browse?query=' + query_value + "&constraints=" + encodeURIComponent(JSON.stringify(constraints)));
		}
		else imdiRequest(query_value, 0);

		console.log(advancedSearchBuildQuery());
	});

	if(_GET && _GET['query']) {
		$('#imdi-query-value').val(_GET['query']);
		imdiRequest(_GET['query'], 0);
	}

	for (var i in imdi_archive_search_plugin_object.categories) {
		console.log(imdi_archive_search_plugin_object.categories[i]);

		var dummyDiv = jQuery("<div>", {id: "cat_" + i});

		jQuery("#categories").append(dummyDiv);

		if (imdi_archive_search_plugin_object.categories[i]['type'] == "occurrences")
			occurrencesRequest(dummyDiv, imdi_archive_search_plugin_object.categories[i]['path'], imdi_archive_search_plugin_object.categories[i]['name'], "category");
		else if (imdi_archive_search_plugin_object.categories[i]['type'] == "predefined")
			outputPredefinedCategoryRequest(dummyDiv, imdi_archive_search_plugin_object.categories[i]['items'], imdi_archive_search_plugin_object.categories[i]['name']);

		//sleep(1000);

	}

	function advancedSearchGetIndex(groupSelect) {
		var indexArray = Array()

		indexArray.push(jQuery(groupSelect).attr("value"));

		jQuery(groupSelect).prevAll("select.group").each(function (){
			indexArray.push(jQuery(this).attr("value"));
		});

		return indexArray.reverse();
	}

	function advancedSearchAddGroup(group, selected, insertAfter) {

		var groupSelect = jQuery("<select>", {id: group.name, class: "group", style: "width: auto;"});

		for (var i in group) {

			var option = jQuery("<option>", {value: i}).text(group[i].name);
			if (!selected)
				if (i == 0)
					jQuery(option).attr("selected", true);
			if (selected == group[i].name)
				jQuery(option).attr("selected", true);

			jQuery(groupSelect).append(option);
		}

		jQuery(insertAfter).after(groupSelect);
		jQuery(groupSelect).change(onConstraintValueChange);

		if (group[jQuery(groupSelect).attr("value")].group)
			advancedSearchAddGroup(group[jQuery(groupSelect).attr("value")].group, null, groupSelect);
		else
			advancedSearchAddInput(group[jQuery(groupSelect).attr("value")], groupSelect);
	}

	function advancedSearchAddInput(item, insertAfter) {
		var inputField = jQuery("<input>", {type: "text", name: item.value, id: "constraint", style:"width:auto;"});
		jQuery(insertAfter).after(inputField);

		if (item.operators) {
			var operatorSelect = jQuery("<select>", {id: "operators", style: "width: auto;"});
			for (var i in item.operators) {
				jQuery(operatorSelect).append(jQuery("<option>" + item.operators[i] + "</option>"));
			}
			inputField.before(operatorSelect);
		}

		if (item.type == 'date') {
			inputField.attr("placeholder", "YYYY-MM-DD");
		}

		if (item.autocomplete) {
			if (item.autocomplete.type == "predefined") {
				jQuery(inputField).autocomplete({source: item.autocomplete.items});
			} else if (item.autocomplete.type == "occurrences") {
				$(inputField).addClass('ui-autocomplete-loading');
				$.ajax({ 
					url: imdi_archive_search_plugin_object.url,
							type: 'GET',
					async: true, 
					cache: false,
					dataType: 'json',
					data: {
						action: 'IMDI_get_occurrences',
						nonce: imdi_archive_search_plugin_object.nonce,
						path: jQuery(inputField).attr("name"),
						title: name,
						responseType: "autocomplete"
					},
					success: function(json) {
						console.log(json);
						jQuery(inputField).autocomplete({
							source: json
						});
						$(inputField).removeClass('ui-autocomplete-loading');
					},
					error: function(json) {
						console.log("autocomplete occurrences error");
						console.log(json);
						$(inputField).removeClass('ui-autocomplete-loading');
					}
				});			
			}
		}
	}
	

	function advancedSearchAddConstraintRow(div, isFirst) {
		var constraintRowDiv = jQuery("<div>", {id: "constraint"});
		var hook = jQuery("<span>", {id: "hook"});
		constraintRowDiv.append(hook);


		advancedSearchAddGroup(advancedsearch['group'], null, hook);
		
		if (!isFirst) {

			//jQuery(constraintRowDiv).prepend(jQuery("<select name='combine' style='width:auto;'><option>AND</option><option>OR</option></select>"));

			var removeButton = jQuery("<div style='float: right;'><a href='#''>remove</a></div>");
			jQuery(removeButton).click(function (e) {
				e.preventDefault();
				jQuery(this).parent().remove();
			});
			constraintRowDiv.append(removeButton);
		}

		div.append(constraintRowDiv);
		return constraintRowDiv;
	}

	function advancedSearchBuildQuery() {
		var queryString = 'a:';

		jQuery("div#constraint").each (function() {
			var inputField = jQuery(this).children("input#constraint");

			var constraintName =  jQuery(inputField).attr("name");
			var constraintValue = "\"" + jQuery(inputField).attr("value") + "\"";
			if (jQuery(inputField).prev("select#operators").length) {
				constraintValue = "{" + jQuery(inputField).prev("select#operators").attr("value") + constraintValue + "}";
			}

			queryString += constraintName + " = " + constraintValue + "\\\\n";
		});

		return queryString;
	}

	function onConstraintValueChange(e) {
		jQuery(e.target).nextAll("select, input").remove();
		var indexArray = advancedSearchGetIndex(e.target);

		var itemString = 'advancedsearch';
		for (var i in indexArray) {
			itemString += ".group[" + indexArray[i] + "]";
		}

		console.log(itemString);
		console.log(indexArray);

		var item = eval(itemString);

		if (typeof item.group != 'undefined') {
			advancedSearchAddGroup(item.group, null, e.target, item.replace);
		}
		else 
			advancedSearchAddInput(item, e.target);
	}

	
	

	// Add initial advanced search row

	function prefill_values(data) {

		jQuery("#imdi-advanced-search").empty();
		console.log(data['constraints']);
		if (data['constraints']) {
				$( ".imdi-category-tabs" ).tabs( "option", "active", 2 ); 
				var constraints = data['constraints'];
				if (typeof constraints == 'string' || constraints instanceof String)
					constraints = JSON.parse(constraints);
				for (var i in constraints) {
					var constraintsRow = advancedSearchAddConstraintRow(jQuery("#imdi-advanced-search"), (i==0));
					for (var j in constraints[i]['index']) {
						console.log("option[value=\""+ constraints[i]['index'][j] + "\"]");
						var lastGroupSelect = jQuery(constraintsRow).children("select.group").last();
						jQuery(lastGroupSelect).children("option[value=\""+ constraints[i]['index'][j] + "\"]").attr("selected", true);
						jQuery(lastGroupSelect).trigger("change");
					}
					jQuery(constraintsRow).children("select#operators").val(constraints[i]['operator']);
				}
				if (data['query']) {
					var queryArray = decodeURIComponent(data['query']).substr(2).split("\\\\n");
					for (var i in queryArray) {
						var constraintArray = queryArray[i].replace(/\{=|[{}"<>]/g, '').split(" = ");
						jQuery("input#constraint[name=\"" + constraintArray[0] + "\"]").val(constraintArray[1]);
						console.log( constraintArray[0] +  " soll sein: " + constraintArray[1]);
					}
				}
			} else {
		advancedSearchAddConstraintRow(jQuery("#imdi-advanced-search"), true);
		// this is not an advanced search because we have no constraints parameter
		// if first two letter of query string is "a:" then it is a cagegoty
		// else a simple search
		if (data['query']) {
			if (decodeURIComponent(data['query']).substr(0, 2) == "a:") {
				$( ".imdi-category-tabs" ).tabs( "option", "active", 0);
			}
		else 
			$( ".imdi-category-tabs" ).tabs( "option", "active", 1);
			$( "input#imdi-query-value" ).val(data['query']);
		}
	}
	}


	
	



	jQuery("#imdi-add-constraint").click(function(e) {
		e.preventDefault();
		advancedSearchAddConstraintRow(jQuery("#imdi-advanced-search"));
	});

});



/** Helpers */

function imdiURLEncode(string) {
	return encodeURIComponent(string).replace(/\*/g, '%2A').replace(/\(/g, '%28').replace(/\)/g, '%29');
}

function getNestedProp(obj, p)
{
  p = p.split('.');
  for (var i = 0, len = p.length; i < len - 1; i++)
    obj = obj[p[i]];

 return obj[p[len - 1]];
};

function getUrlVars(url)
{
    var vars = [], hash;
    var hashes = url.slice(url.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}

function sleep(milliseconds) {
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  }
}