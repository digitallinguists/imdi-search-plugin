jQuery(document).ready(function(elem){

	function onTypeListChange(event) {
		if (jQuery(event.target).attr("value") == "occurrences")
			jQuery(event.target).parent().parent().replaceWith(makeOccurrencesCategoryRow({}));
		if (jQuery(event.target).attr("value") == "predefined")
			jQuery(event.target).parent().parent().replaceWith(makePredefinedCategoryRow({}));
	}

	function appendEditButtonSet(div) {
		var removeLink = jQuery("<p style='float:bottom;'>remove</p>", {id: "imdi_remove_cat", href: "#"});
			jQuery(removeLink).click(function (e) {
				e.preventDefault();
				jQuery(this).parents("#categoryFields").remove();
			});

			var upLink = jQuery("<p style='float:bottom;'>move up</p>", {id: "imdi_remove_cat", href: "#"});
			jQuery(upLink).click(function (e) {
				e.preventDefault();
				jQuery(this).parents("#categoryFields").prev("#categoryFields").before(jQuery(this).parents("#categoryFields"));
			});

			var downLink = jQuery("<p style='float:bottom;'>move down</p>", {id: "imdi_remove_cat", href: "#"});
			jQuery(downLink).click(function (e) {
				e.preventDefault();
				jQuery(this).parents("#categoryFields").next("#categoryFields").after(jQuery(this).parents("#categoryFields"));
			});

		jQuery(div).append(jQuery("<div style='float: left; clear:right;'></div>").append(upLink, downLink, removeLink));
	}

	function makeOccurrencesCategoryRow(category) {


		var div = jQuery("<div>", {id: "categoryFields", style: "clear:both; border: 1px dashed grey; overflow:hidden; zoom: 1;"});

		var typeList = jQuery("<select>", {id: "categoryField", type: "select", name: "type", size: "1"});
			jQuery(typeList).append(jQuery("<option>", {selected: true}).text("occurrences"));
			jQuery(typeList).append(jQuery("<option>").text("predefined"));

		jQuery(typeList).change(onTypeListChange);



		jQuery(div).append(jQuery("<div style='float: left; display:block;'><p>Type</p></div>").append(typeList));

		var nameField = jQuery("<input>", { id: "categoryField", type: "text", name: "name", value: category['name']});
		var pathField = jQuery("<input>", { id: "categoryField", type: "text", name: "path", value: category['path']});

	
		jQuery(div).append(jQuery("<div style='float: left;'><p>Category Name</p></div>").append(nameField));
		jQuery(div).append(jQuery("<div style='float: left;'><p>Path</p></div>").append(pathField));

		appendEditButtonSet(div);			

		return div;
	}

	function makePredefinedCategoryRow(category)
	{
		var div = jQuery("<div>", {id: "categoryFields", style: "clear:both; border: 1px dashed grey; overflow:hidden; zoom: 1;"});

		var typeList = jQuery("<select>", {id: "categoryField", type: "select", name: "type", size: "1"});
			jQuery(typeList).append(jQuery("<option>").text("occurrences"));
			jQuery(typeList).append(jQuery("<option>", {selected: true}).text("predefined"));

 		jQuery(typeList).change(onTypeListChange);
 		jQuery(div).append(jQuery("<div style='float: left; display:block;'><p>Type</p></div>").append(typeList));
		
		

		var nameField = jQuery("<input>", { id: "categoryField", type: "text", name: "name", value: category['name']});
 		jQuery(div).append(jQuery("<div style='float: left; display:block;'><p>Category Name</p></div>").append(nameField));

		var itemsDiv = jQuery("<div style='display:block';><p style='clear:both;'><b>Items</b></p></div>");

		addItemLink = jQuery("<a>Add Item</a>", {href :"#", id:"imdi_add_item"} );
		addItemLink.click(function(e) {
			itemsDiv.append(makePredefinedItem({}));
		});

		for (var i in category['items']) {
			itemsDiv.append(makePredefinedItem(category['items'][i]));
		}

		appendEditButtonSet(div);


		itemsDiv.append(addItemLink);

		div.append(itemsDiv);


		return div;
	}

	function makePredefinedItem(item) {
		var div = jQuery("<div>", {id: "itemFields", style: "clear:both; overflow:hidden; zoom: 1;"});

		var itemNameField = jQuery("<input>", { id: "itemField", type: "text", name: "name", value: item['name']});
		jQuery(div).append(jQuery("<div style='float: left; display:block;'><p>Name</p></div>").append(itemNameField));

		var queryField = jQuery("<input>", { id: "itemField", type: "text", name: "query", value: item['query']});
		jQuery(div).append(jQuery("<div style='float: left; display:block;'><p>Query</p></div>").append(queryField));
	
			var removeLink = jQuery("<p><a>remove</a></p>", {id: "imdi_remove_cat", href: "#"});
			jQuery(removeLink).click(function (e) {
				e.preventDefault();
				jQuery(this).parent().remove();
			});
		jQuery(div).append(removeLink);

		return div;
	}

	jQuery("div#categoryRow").each(function() {
		var category = jQuery.parseJSON(jQuery(this).text());

		console.log("TYPE: " + category['type']);

		if (category['type'] == "occurrences")
			jQuery(this).replaceWith(makeOccurrencesCategoryRow(category));
		else if (category['type'] == "predefined")
			jQuery(this).replaceWith(makePredefinedCategoryRow(category));
	});

	jQuery( "form#imdi_options" ).submit(function( event ) {
  		var a_categories = new Array();

  		jQuery("div#categoryFields").each(function() {
			var a_category = {};
			jQuery(this).children("div").children("#categoryField").each(function(event) {
				a_category[jQuery(this).attr("name")] = jQuery(this).attr("value");
			});


				var a_items = new Array();
				jQuery(this).children("div").children("#itemFields").each(function () {
					console.log("itemFIELDs");
					console.log(jQuery(this).html());
					var a_item = {};
					 jQuery(this).children("div").children("#itemField").each(function() {
					 	a_item[jQuery(this).attr("name")] = jQuery(this).attr("value");
					 });
					 a_items.push(a_item);
				});
				a_category['items'] = a_items;
			
			a_categories.push(a_category);
		});

  		jQuery(this).append(jQuery("<input>", {type: "hidden", name:"imdi_categories", value: JSON.stringify(a_categories)}));
	});

	jQuery("#imdi_add_cat").click(function (e) {
		e.preventDefault();
		jQuery("div#categories").append(makeOccurrencesCategoryRow);
	});


});