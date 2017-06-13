
/**
** Scripts dedicated to grabbing in admin/index.php
*/

"use strict";
var dragSrcEl = null;

function handleDragStart(e) {
	console.log('handleDragStart');
	  // Target (this) element is the source node.
	  dragSrcEl = this;

	  e.dataTransfer.effectAllowed = 'move';
	  e.dataTransfer.setData('text/html', this.outerHTML);
	  this.classList.add('dragElem');
	}

	function handleDragOver(e) {
		console.log('handleDragOver');
		if (e.preventDefault) {
		e.preventDefault(); // Necessary. Allows us to drop.
	}
	this.classList.add('over');
	  e.dataTransfer.dropEffect = 'move';  // See the section on the DataTransfer object.

	  return false;
	}

	function handleDragEnter(e) {
		console.log('handleDragEnter');
	  // this / e.target is the current hover target.
	}

	function handleDragLeave(e) {
		console.log('handleDragLeave');
		this.classList.remove('over');  // this / e.target is previous target element.
	}

	function handleDrop(e) {
		console.log('handleDrop');
	  // this/e.target is current target element.

	  if (e.stopPropagation) {
		e.stopPropagation(); // Stops some browsers from redirecting.
	}

	  // Don't do anything if dropping the same column we're dragging.
	  if (dragSrcEl != this) {
		// Set the source column's HTML to the HTML of the column we dropped on.
		//alert(this.outerHTML);
		//dragSrcEl.innerHTML = this.innerHTML;
		//this.innerHTML = e.dataTransfer.getData('text/html');
		this.parentNode.removeChild(dragSrcEl);
		var dropHTML = e.dataTransfer.getData('text/html');
		this.insertAdjacentHTML('beforebegin',dropHTML);
		var dropElem = this.previousSibling;
		addDnDHandlers(dropElem);
	}
	this.classList.remove('over');
	return false;
}

function handleDragEnd(e) {
	console.log('handleDragEnd');
	  // this/e.target is the source node.
	  this.classList.remove('over')
	  this.classList.remove('dragElem');


	  /*[].forEach.call(cols, function (col) {
		col.classList.remove('over');
	});*/
}

function addDnDHandlers(elem) {
	console.log('addDnDHandlers');
	elem.addEventListener('dragstart', handleDragStart, false);
	elem.addEventListener('dragenter', handleDragEnter, false)
	elem.addEventListener('dragover', handleDragOver, false);
	elem.addEventListener('dragleave', handleDragLeave, false);
	elem.addEventListener('drop', handleDrop, false);
	elem.addEventListener('dragend', handleDragEnd, false);
}

	// run when everything is loaded
	document.addEventListener("DOMContentLoaded", function(event) {
		var cols = document.querySelectorAll('#order li');
		[].forEach.call(cols, addDnDHandlers);
	});

// set order
function changeOrder()
{
	var cols = document.querySelectorAll('#order li');
	var i = 1;
	[].forEach.call(
		cols,
		function(col)
		{
			console.log(col.dataset.id);
			var c = document.getElementById(col.dataset.id);
			console.log(c);
			c.style.order = i*4;
			++i;
		}
		);
}

//Print or hind the grab / swipe buttons
function displayOrderChanger() {
	if (document.getElementById("order").style.display == "none")
		document.getElementById("order").style.display = "block";
	else
		document.getElementById("order").style.display = "none";
}