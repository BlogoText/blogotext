
/**
** Scripts dedicated to grabbing in admin/index.php
*/

"use strict";
var dragSrcEl = null;

function handleDragStart(e)
{
    dragSrcEl = this;

    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.outerHTML);
    this.classList.add('dragElem');
}

function handleDragOver(e)
{
    if (e.preventDefault) {
        e.preventDefault();
    }
    this.classList.add('over');
    this.previousSibling.classList.add('previous');
    e.dataTransfer.dropEffect = 'move';

    return false;
}

function handleDragEnter(e)
{
    /**
     * this / e.target is the current hover target.
     */
}


function handleDragLeave(e)
{
    this.classList.remove('over')
    this.previousSibling.classList.remove('previous');
}

function handleDrop(e)
{
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
    this.previousSibling.classList.remove('previous');
    return false;
}

function handleDragEnd(e)
{
    this.classList.remove('over')
    this.previousSibling.classList.remove('previous');
    this.classList.remove('dragElem');
}

function addDnDHandlers(elem)
{
    elem.addEventListener('dragstart', handleDragStart, false);
    //elem.addEventListener('dragenter', handleDragEnter, false)
    elem.addEventListener('dragover', handleDragOver, false);
    elem.addEventListener('dragleave', handleDragLeave, false);
    elem.addEventListener('drop', handleDrop, false);
    elem.addEventListener('dragend', handleDragEnd, false);
}

document.addEventListener("DOMContentLoaded", function (event) {
    var cols = document.querySelectorAll('#grabOrder li');
    [].forEach.call(cols, addDnDHandlers);
});

/**
 * Set graphs order
 */
function dragChangeOrder()
{
    var cols = document.querySelectorAll('#grabOrder li');
    var i = 1;
    [].forEach.call(cols, function (col) {
        if (col.dataset.id == undefined) {
            return;
        }
        var c = document.getElementById(col.dataset.id);
        c.style.order = i*4;
        ++i;
    });

}

/**
 * Print or hind the grab / swipe buttons at the bottom of the page
 */
function dragDisplayOrderChanger(open, close)
{
    var div = document.getElementById("grabOrder");
    var el = document.getElementById("grabDisplayOrderChanger");

    if (div.style.display == 'block') {
        el.innerHTML = open;
        div.style.display = 'none';
    } else {
        el.innerHTML = close;
        div.style.display = 'block';
    }
}
