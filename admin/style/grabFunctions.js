
/**
** Scripts dedicated to grabbing in admin/index.php
*/

"use strict";
var dragSrcEl = null;

function handleDragStart(e) {
	  // Target (this) element is the source node.
	  dragSrcEl = this;

	  e.dataTransfer.effectAllowed = 'move';
	  e.dataTransfer.setData('text/html', this.outerHTML);
	  this.classList.add('dragElem');
	}

	function handleDragOver(e) {
		if (e.preventDefault) {
		e.preventDefault(); // Necessary. Allows us to drop.
	}
	this.classList.add('over');
	  e.dataTransfer.dropEffect = 'move';  // See the section on the DataTransfer object.

	  return false;
	}

function handleDragEnter(e) {
	// this / e.target is the current hover target.
}


function handleDragLeave(e) {
	this.classList.remove('over');  // this / e.target is previous target element.
}

function handleDrop(e) {
	if (e.stopPropagation) {
		e.stopPropagation();
	}
	if (dragSrcEl != this) {
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
	this.classList.remove('over')
	this.classList.remove('dragElem');
}

function addDnDHandlers(elem) {
	elem.addEventListener('dragstart', handleDragStart, false);
	//elem.addEventListener('dragenter', handleDragEnter, false)
	elem.addEventListener('dragover', handleDragOver, false);
	elem.addEventListener('dragleave', handleDragLeave, false);
	elem.addEventListener('drop', handleDrop, false);
	elem.addEventListener('dragend', handleDragEnd, false);
}
	document.addEventListener("DOMContentLoaded", function(event) {
	var cols = document.querySelectorAll('#order li');
	[].forEach.call(cols, addDnDHandlers);
	});

/*
** Set graphs order
*/

function changeOrder()
{
	var cols = document.querySelectorAll('#order li');
	var i = 1;
	[].forEach.call(
		cols,
		function(col)
		{
			var c = document.getElementById(col.dataset.id);
			c.style.order = i*4;
			++i;
		}
		);
}

/*
** Print or hind the grab / swipe buttons at the bottom of the page
*/

function displayOrderChanger() {
	var div = document.getElementById("order");
  div.style.display = (div.style.display == "block") ? 'none' : 'block';
}