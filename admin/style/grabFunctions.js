/**
 * Scripts dedicated to grabbing in admin/index.php
 */

"use strict";

var dragSrcEl = null;

function grabDragStart(e)
{
    dragSrcEl = this;

    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/html', this.outerHTML);
    this.classList.add('dragElem');
}

function grabDragOver(e)
{
    if (e.preventDefault) {
        e.preventDefault();
    }
    if (this.classList.contains('over')) {
        return;
    }
    this.classList.add('over');
 
    e.dataTransfer.dropEffect = 'move';

    return false;
}

function grabDragEnter(e)
{
    /**
     * this / e.target is the current hover target.
     */
}


function grabDragLeave(e)
{
    this.classList.remove('over');
}

function grabDrop(e)
{
    if (e.stopPropagation) {
        e.stopPropagation();
    }
    if (dragSrcEl != this) {
        this.parentNode.removeChild(dragSrcEl);
        var dropHTML = e.dataTransfer.getData('text/html');
        this.insertAdjacentHTML('beforebegin',dropHTML);
        var dropElem = this.previousSibling;
        grabHandlers(dropElem);
    }
    this.classList.remove('over');
    return false;
}

function grabDragEnd(e)
{
    this.classList.remove('over')
    this.classList.remove('dragElem');
}

function grabHandlers(elem)
{
    elem.addEventListener('dragstart', grabDragStart, false);
    //elem.addEventListener('dragenter', grabDragEnter, false)
    elem.addEventListener('dragover', grabDragOver, false);
    elem.addEventListener('dragleave', grabDragLeave, false);
    elem.addEventListener('drop', grabDrop, false);
    elem.addEventListener('dragend', grabDragEnd, false);
}

document.addEventListener("DOMContentLoaded", function (event) {
    var cols = document.querySelectorAll('#grabOrder li');
    [].forEach.call(cols, grabHandlers);
});

/**
 * Set graphs order
 */
function grabChangeOrder()
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
function grabDisplayOrderChanger(btn, open, close)
{
    var div = document.getElementById("grabOrder");

    if (div.style.display == 'block') {
        btn.innerHTML = open;
        div.style.display = 'none';
    } else {
        btn.innerHTML = close;
        div.style.display = 'block';
    }
}
